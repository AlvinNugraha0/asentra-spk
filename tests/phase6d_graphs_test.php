<?php
// ASENTRA SPK — Phase 6D: Owner Graphs Test
// Verifies ChartDataService produces database-driven chart datasets:
//  1. indicatorComparison  — C1/C2/C3 per teknisi (tb_penilaian)
//  2. preferenceValues     — Vi per teknisi (tb_hasil if SAW ran, else preview)
//  3. technicianIndicatorDetail / technicianSubindicators — detail page
//
// SAFETY: creates its OWN isolated period (kode 'TEST6D-2026-Q1') and removes it
// in teardown. Legacy periods, tb_penilaian=12, tb_hasil=12 untouched.

declare(strict_types=1);

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/models/PeriodePenilaian.php';
require_once __DIR__ . '/../app/models/Penilaian.php';
require_once __DIR__ . '/../app/models/Hasil.php';
require_once __DIR__ . '/../app/models/Teknisi.php';
require_once __DIR__ . '/../app/models/Kedisiplinan.php';
require_once __DIR__ . '/../app/models/Pekerjaan.php';
require_once __DIR__ . '/../app/models/TanggungJawab.php';
require_once __DIR__ . '/../app/services/CalculationEngine.php';
require_once __DIR__ . '/../app/services/calculators/DisciplineCalculator.php';
require_once __DIR__ . '/../app/services/calculators/QualityCalculator.php';
require_once __DIR__ . '/../app/services/calculators/ResponsibilityCalculator.php';
require_once __DIR__ . '/../app/services/AssessmentWorkflowService.php';
require_once __DIR__ . '/../app/services/ChartDataService.php';

use App\Core\Database;
use App\Models\Penilaian;
use App\Models\PeriodePenilaian;
use App\Models\Teknisi;
use App\Services\AssessmentWorkflowService;
use App\Services\ChartDataService;

$pass = 0;
$fail = 0;
$testKode = 'TEST6D-2026-Q1';
$testPeriodeId = null;

function check(bool $ok, string $name, string $detail = ''): void
{
    global $pass, $fail;
    if ($ok) {
        $pass++;
    } else {
        $fail++;
    }
    echo ($ok ? 'OK  ' : 'FAIL') . ': ' . $name . ($detail !== '' ? ' — ' . $detail : '') . PHP_EOL;
}

echo '========================================' . PHP_EOL;
echo 'PHASE 6D — OWNER GRAPHS TEST' . PHP_EOL;
echo '========================================' . PHP_EOL;

$basePenilaian = (int) Database::query('SELECT COUNT(*) AS c FROM tb_penilaian')->fetch()['c'];
$baseHasil     = (int) Database::query('SELECT COUNT(*) AS c FROM tb_hasil')->fetch()['c'];
echo "Baseline: penilaian={$basePenilaian} hasil={$baseHasil}" . PHP_EOL . PHP_EOL;

// Remove leftover from previous aborted run
$leftover = Database::query('SELECT id_periode FROM tb_periode_penilaian WHERE kode_periode = ?', [$testKode])->fetch();
if ($leftover) {
    Database::query('DELETE FROM tb_hasil WHERE id_periode = ?', [(int) $leftover['id_periode']]);
    Database::query('DELETE FROM tb_penilaian WHERE id_periode = ?', [(int) $leftover['id_periode']]);
    Database::query('DELETE FROM tb_periode_penilaian WHERE id_periode = ?', [(int) $leftover['id_periode']]);
}

// ---- Setup: isolated test period + 3 evaluations ----
$testPeriodeId = PeriodePenilaian::create([
    'kode_periode'    => $testKode,
    'nama_periode'    => 'TEST Phase 6D Q1 2026',
    'tanggal_mulai'   => '2026-01-01',
    'tanggal_selesai' => '2026-03-31',
    'status'          => 'draft',
    'created_by'      => 1,
]);

$teknisi = Teknisi::all();
$sample = array_slice($teknisi, 0, 3);

$fixtures = [
    ['c1' => 4.0, 'c2' => 3.0, 'c3' => 2.0],
    ['c1' => 3.0, 'c2' => 2.0, 'c3' => 1.0],
    ['c1' => 2.5, 'c2' => 3.5, 'c3' => 4.0],
];
$penilaianIds = [];
foreach ($sample as $i => $t) {
    Database::query(
        'INSERT INTO tb_penilaian
            (id_periode, teknisi_id, periode, c1, c2, c3, status_data,
             jumlah_bulan_c1, jumlah_bulan_c2, jumlah_bulan_c3, warning, created_by)
         VALUES (?, ?, ?, ?, ?, ?, ?, 3, 3, 3, NULL, ?)',
        [$testPeriodeId, (int) $t['id'], '2026-Q1', $fixtures[$i]['c1'], $fixtures[$i]['c2'], $fixtures[$i]['c3'], 'calculated', 1]
    );
    $penilaianIds[$i] = (int) Database::getConnection()->lastInsertId();
}

// ================================================ 1. indicatorComparison
echo '-- indicatorComparison (C1/C2/C3 per teknisi) --' . PHP_EOL;
$ind = ChartDataService::indicatorComparison($testPeriodeId);

check(count($ind['labels']) === 3, '3 labels (one per teknisi)', 'count=' . count($ind['labels']));
check($ind['labels'] === [$sample[0]['kode_teknisi'], $sample[1]['kode_teknisi'], $sample[2]['kode_teknisi']], 'Labels are teknisi codes from DB', implode(',', $ind['labels']));
check(count($ind['c1']) === 3 && count($ind['c2']) === 3 && count($ind['c3']) === 3, 'C1/C2/C3 arrays have 3 values each');
check(abs($ind['c1'][0] - 4.0) < 0.0001, 'C1[0] matches fixture 4.0', 'got ' . (string) $ind['c1'][0]);
check(abs($ind['c2'][2] - 3.5) < 0.0001, 'C2[2] matches fixture 3.5', 'got ' . (string) $ind['c2'][2]);
check(abs($ind['c3'][1] - 1.0) < 0.0001, 'C3[1] matches fixture 1.0', 'got ' . (string) $ind['c3'][1]);

$emptyInd = ChartDataService::indicatorComparison(999999);
check($emptyInd['labels'] === [] && $emptyInd['c1'] === [], 'Empty period returns empty dataset');

// ================================================ 2. preferenceValues (preview)
echo PHP_EOL . '-- preferenceValues (pre-SAW preview) --' . PHP_EOL;
$vi = ChartDataService::preferenceValues($testPeriodeId);

check($vi['source'] === 'preview', 'source = preview when tb_hasil empty for period', 'source=' . $vi['source']);
check(count($vi['vi']) === 3, '3 Vi values', 'count=' . count($vi['vi']));
// Preview Vi = weighted sum from tabulation
$expectedVi0 = 0.30 * 4.0 + 0.40 * 3.0 + 0.30 * 2.0; // 3.0
check(abs($vi['vi'][0] - $expectedVi0) < 0.0001, 'Vi[0] = 3.0000 (preview weighted sum)', 'got ' . number_format((float) $vi['vi'][0], 4));
check($vi['labels'] === $ind['labels'], 'Vi labels match indicator labels');

// ================================================ 3. preferenceValues (SAW source)
echo PHP_EOL . '-- preferenceValues (SAW source from tb_hasil) --' . PHP_EOL;
// Insert SAW results for the test period. nilai_preferensi is authoritative here.
$sawVi = [0.9000, 0.5000, 0.7500];
foreach ($sample as $i => $t) {
    Database::query(
        'INSERT INTO tb_hasil
            (id_periode, penilaian_id, teknisi_id, periode, c1, c2, c3,
             nilai_c1_normalisasi, nilai_c2_normalisasi, nilai_c3_normalisasi,
             kontribusi_c1, kontribusi_c2, kontribusi_c3,
             bobot_c1, bobot_c2, bobot_c3, nilai_preferensi, ranking)
         VALUES (?, ?, ?, ?, ?, ?, ?, 1.0, 1.0, 1.0, 0.3, 0.4, 0.3, 0.30, 0.40, 0.30, ?, ?)',
        [
            $testPeriodeId,
            $penilaianIds[$i],
            (int) $t['id'],
            '2026-Q1',
            $fixtures[$i]['c1'],
            $fixtures[$i]['c2'],
            $fixtures[$i]['c3'],
            $sawVi[$i],
            $i + 1,
        ]
    );
}

$viSaw = ChartDataService::preferenceValues($testPeriodeId);
check($viSaw['source'] === 'saw', 'source = saw when tb_hasil has rows for period', 'source=' . $viSaw['source']);
check(count($viSaw['vi']) === 3, '3 Vi values from tb_hasil', 'count=' . count($viSaw['vi']));
check(abs($viSaw['vi'][0] - 0.9) < 0.0001, 'Vi[0] = 0.9000 from tb_hasil.nilai_preferensi', 'got ' . (string) $viSaw['vi'][0]);
check(abs($viSaw['vi'][1] - 0.5) < 0.0001, 'Vi[1] = 0.5000 from tb_hasil.nilai_preferensi', 'got ' . (string) $viSaw['vi'][1]);
check(abs($viSaw['vi'][2] - 0.75) < 0.0001, 'Vi[2] = 0.7500 from tb_hasil.nilai_preferensi', 'got ' . (string) $viSaw['vi'][2]);

// ================================================ 4. detail page datasets
echo PHP_EOL . '-- technicianIndicatorDetail / technicianSubindicators --' . PHP_EOL;
$workflow = new AssessmentWorkflowService();
$detail = $workflow->getTechnicianIndicatorDetail($testPeriodeId, (int) $sample[0]['id']);

$radar = ChartDataService::technicianIndicatorDetail($detail);
check($radar['labels'] === ['C1 Kedisiplinan', 'C2 Kualitas Kerja', 'C3 Tanggung Jawab'], 'Radar labels = 3 criteria');
check(count($radar['values']) === 3, 'Radar has 3 values');
// Chart must mirror the backend calculation exactly (no recomputation, no hardcode).
$calcC1 = (float) ($detail['calculation']['c1']['value'] ?? 0);
$calcC2 = (float) ($detail['calculation']['c2']['value'] ?? 0);
$calcC3 = (float) ($detail['calculation']['c3']['value'] ?? 0);
check(abs($radar['values'][0] - $calcC1) < 0.0001, 'Radar C1 mirrors CalculationEngine result', 'got ' . (string) $radar['values'][0] . ' vs ' . (string) $calcC1);
check(abs($radar['values'][1] - $calcC2) < 0.0001, 'Radar C2 mirrors CalculationEngine result', 'got ' . (string) $radar['values'][1] . ' vs ' . (string) $calcC2);
check(abs($radar['values'][2] - $calcC3) < 0.0001, 'Radar C3 mirrors CalculationEngine result', 'got ' . (string) $radar['values'][2] . ' vs ' . (string) $calcC3);

$sub = ChartDataService::technicianSubindicators($detail);
check(count($sub['labels']) === 10, 'Subindicator chart has 10 labels (3+3+4)', 'count=' . count($sub['labels']));
check(count($sub['values']) === 10, 'Subindicator chart has 10 values');
$allInRange = true;
foreach ($sub['values'] as $v) {
    if ($v < 0 || $v > 4) {
        $allInRange = false;
    }
}
check($allInRange, 'All subindicator values in [0,4] range');
check($sub['labels'][0] === 'Kehadiran' && $sub['labels'][9] === 'Kepatuhan Prosedur', 'Subindicator label order correct');

// All datasets must be JSON-serializable (they are embedded in views)
$jsonOk = json_encode($ind) !== false && json_encode($viSaw) !== false && json_encode($radar) !== false && json_encode($sub) !== false;
check($jsonOk, 'All datasets JSON-serializable (embedded in views)');

// ================================================ TEARDOWN
Database::query('DELETE FROM tb_hasil WHERE id_periode = ?', [$testPeriodeId]);
Database::query('DELETE FROM tb_penilaian WHERE id_periode = ?', [$testPeriodeId]);
Database::query('DELETE FROM tb_periode_penilaian WHERE id_periode = ?', [$testPeriodeId]);

$afterPenilaian = (int) Database::query('SELECT COUNT(*) AS c FROM tb_penilaian')->fetch()['c'];
$afterHasil     = (int) Database::query('SELECT COUNT(*) AS c FROM tb_hasil')->fetch()['c'];
check($afterPenilaian === $basePenilaian, "tb_penilaian restored to baseline {$basePenilaian}", 'got ' . $afterPenilaian);
check($afterHasil === $baseHasil, "tb_hasil restored to baseline {$baseHasil}", 'got ' . $afterHasil);

$legacy = (int) Database::query('SELECT COUNT(*) AS c FROM tb_penilaian WHERE status_data = "legacy"')->fetch()['c'];
check($legacy === 12, 'Legacy tb_penilaian still 12', 'got ' . $legacy);
$legacyHasil = (int) Database::query('SELECT COUNT(*) AS c FROM tb_hasil h JOIN tb_periode_penilaian p ON p.id_periode = h.id_periode WHERE p.status = "legacy"')->fetch()['c'];
check($legacyHasil === 12, 'Legacy tb_hasil still 12', 'got ' . $legacyHasil);

echo PHP_EOL . '========================================' . PHP_EOL;
echo 'RESULT: ' . $pass . '/' . ($pass + $fail) . ' PASS' . PHP_EOL;
echo '========================================' . PHP_EOL;

exit($fail === 0 ? 0 : 1);
