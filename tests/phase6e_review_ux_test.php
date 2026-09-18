<?php
// ASENTRA SPK — Phase 6E: Owner Review UX Test
// Verifies the review-UX contract:
//  1. getTechnicianIndicatorDetail() exposes the monthly trace keys the
//     calculation_trace partial needs ('monthly', percentages, ratings).
//  2. Raw vs derived separation is present and distinguishable.
//  3. getPeriodAssessmentSummary() exposes everything the pre-confirmation
//     checklist needs (counts, warnings, source).
//  4. calculation_trace partial renders without error and contains the
//     expected raw/derived markers.
//
// SAFETY: creates its OWN isolated period (kode 'TEST6E-2026-Q1') with raw
// operational data + evaluations, then removes it in teardown.
// Legacy periods, tb_penilaian=12, tb_hasil=12 untouched.

declare(strict_types=1);

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/helpers/view.php';
require_once __DIR__ . '/../app/helpers/format.php';
require_once __DIR__ . '/../app/models/PeriodePenilaian.php';
require_once __DIR__ . '/../app/models/Penilaian.php';
require_once __DIR__ . '/../app/models/Teknisi.php';
require_once __DIR__ . '/../app/models/Kedisiplinan.php';
require_once __DIR__ . '/../app/models/Pekerjaan.php';
require_once __DIR__ . '/../app/models/TanggungJawab.php';
require_once __DIR__ . '/../app/models/User.php';
require_once __DIR__ . '/../app/services/CalculationEngine.php';
require_once __DIR__ . '/../app/services/calculators/DisciplineCalculator.php';
require_once __DIR__ . '/../app/services/calculators/QualityCalculator.php';
require_once __DIR__ . '/../app/services/calculators/ResponsibilityCalculator.php';
require_once __DIR__ . '/../app/services/AssessmentWorkflowService.php';

use App\Core\Database;
use App\Models\Kedisiplinan;
use App\Models\Pekerjaan;
use App\Models\PeriodePenilaian;
use App\Models\TanggungJawab;
use App\Models\Teknisi;
use App\Services\AssessmentWorkflowService;

$pass = 0;
$fail = 0;
$testKode = 'TEST6E-2026-Q1';
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
echo 'PHASE 6E — OWNER REVIEW UX TEST' . PHP_EOL;
echo '========================================' . PHP_EOL;

$basePenilaian = (int) Database::query('SELECT COUNT(*) AS c FROM tb_penilaian')->fetch()['c'];
$baseHasil     = (int) Database::query('SELECT COUNT(*) AS c FROM tb_hasil')->fetch()['c'];
echo "Baseline: penilaian={$basePenilaian} hasil={$baseHasil}" . PHP_EOL . PHP_EOL;

// Clean leftover
$leftover = Database::query('SELECT id_periode FROM tb_periode_penilaian WHERE kode_periode = ?', [$testKode])->fetch();
if ($leftover) {
    $pid = (int) $leftover['id_periode'];
    Database::query('DELETE FROM tb_hasil WHERE id_periode = ?', [$pid]);
    Database::query('DELETE FROM tb_penilaian WHERE id_periode = ?', [$pid]);
    Database::query('DELETE FROM tb_kedisiplinan WHERE id_periode = ?', [$pid]);
    Database::query('DELETE FROM tb_pekerjaan WHERE id_periode = ?', [$pid]);
    Database::query('DELETE FROM tb_tanggung_jawab WHERE id_periode = ?', [$pid]);
    Database::query('DELETE FROM tb_periode_penilaian WHERE id_periode = ?', [$pid]);
}

// ---- Setup: isolated period + raw data for 2 technicians ----
$testPeriodeId = PeriodePenilaian::create([
    'kode_periode'    => $testKode,
    'nama_periode'    => 'TEST Phase 6E Q1 2026',
    'tanggal_mulai'   => '2026-01-01',
    'tanggal_selesai' => '2026-03-31',
    'status'          => 'draft',
    'created_by'      => 1,
]);

$teknisi = Teknisi::all();
$t1 = $teknisi[0]; // complete 3 months
$t2 = $teknisi[1]; // partial: only months 1-2

// C1 raw — t1 complete, t2 partial
foreach ([1, 2, 3] as $m) {
    Kedisiplinan::upsert([
        'id_periode' => $testPeriodeId, 'id_teknisi' => (int) $t1['id'], 'bulan' => $m,
        'total_hari_kerja' => 22, 'hadir' => 21, 'sakit' => 1, 'izin' => 0, 'alpa' => 0,
        'terlambat' => 1, 'pekerjaan_terjadwal' => 8, 'sesuai_jadwal' => 7,
    ]);
}
foreach ([1, 2] as $m) {
    Kedisiplinan::upsert([
        'id_periode' => $testPeriodeId, 'id_teknisi' => (int) $t2['id'], 'bulan' => $m,
        'total_hari_kerja' => 22, 'hadir' => 18, 'sakit' => 2, 'izin' => 2, 'alpa' => 0,
        'terlambat' => 4, 'pekerjaan_terjadwal' => 8, 'sesuai_jadwal' => 5,
    ]);
}

// C2 raw — 2 jobs/month per teknisi
foreach ([$t1, $t2] as $idx => $t) {
    $months = $idx === 0 ? [1, 2, 3] : [1, 2];
    foreach ($months as $m) {
        foreach ([5, 15] as $day) {
            Pekerjaan::create([
                'id_periode' => $testPeriodeId,
                'id_teknisi' => (int) $t['id'],
                'tanggal' => sprintf('2026-%02d-%02d', $m, $day),
                'bulan' => $m,
                'nama_pekerjaan' => 'Servis',
                'rapi' => 1,
                'presisi' => 1,
                'sesuai_desain' => 0,
            ]);
        }
    }
}

// C3 raw
foreach ([$t1, $t2] as $idx => $t) {
    $months = $idx === 0 ? [1, 2, 3] : [1, 2];
    foreach ($months as $m) {
        TanggungJawab::upsert([
            'id_periode' => $testPeriodeId, 'id_teknisi' => (int) $t['id'], 'bulan' => $m,
            'perawatan_alat' => 4, 'efisiensi_material' => 3, 'inisiatif' => 3, 'kepatuhan_prosedur' => 4,
        ]);
    }
}

$workflow = new AssessmentWorkflowService();

// ================================================ 1. Detail trace keys
echo '-- detail trace keys (raw vs derived) --' . PHP_EOL;
$detail = $workflow->getTechnicianIndicatorDetail($testPeriodeId, (int) $t1['id']);

check(($detail['calculation']['c1']['monthly'] ?? null) !== null, 'c1.monthly exists (partial uses this key, NOT monthly_details)');
check(($detail['calculation']['c2']['monthly'] ?? null) !== null, 'c2.monthly exists');
check(($detail['calculation']['c3']['monthly'] ?? null) !== null, 'c3.monthly exists');

$m1 = $detail['calculation']['c1']['monthly'][1] ?? [];
check(($m1['persentase_kehadiran'] ?? null) !== null, 'c1 monthly exposes persentase_kehadiran (trace: raw hadir/total -> %)');
check(($m1['c1_1'] ?? null) !== null, 'c1 monthly exposes rating c1_1 (trace: % -> rating)');
check(($m1['value'] ?? null) !== null, 'c1 monthly exposes derived value');
check(($m1['jumlah_terlambat'] ?? null) === 1, 'c1 monthly exposes raw jumlah_terlambat = 1', 'got ' . (string) ($m1['jumlah_terlambat'] ?? 'null'));

$m2 = $detail['calculation']['c2']['monthly'][1] ?? [];
check(($m2['persentase_rapi'] ?? null) !== null, 'c2 monthly exposes persentase_rapi (raw lulus/total -> %)');
check(($m2['total_pekerjaan'] ?? null) === 2, 'c2 monthly exposes total_pekerjaan = 2', 'got ' . (string) ($m2['total_pekerjaan'] ?? 'null'));

$m3 = $detail['calculation']['c3']['monthly'][1] ?? [];
check(($m3['c3_1'] ?? null) === 4, 'c3 monthly exposes raw rating c3_1 = 4', 'got ' . (string) ($m3['c3_1'] ?? 'null'));

// Raw data present in detail
check(!empty($detail['raw_data']['kedisiplinan']), 'raw kedisiplinan rows present in detail');
check(!empty($detail['raw_data']['pekerjaan']), 'raw pekerjaan rows present in detail');
check(!empty($detail['raw_data']['tanggung_jawab']), 'raw tanggung_jawab rows present in detail');

// Complete technician status
check($detail['calculation']['c1']['status'] === 'complete', 't1 C1 status = complete (3 months)');
check($detail['calculation']['status'] === 'complete', 't1 overall status = complete');

// ================================================ 2. Partial technician
echo PHP_EOL . '-- partial technician warning --' . PHP_EOL;
$detail2 = $workflow->getTechnicianIndicatorDetail($testPeriodeId, (int) $t2['id']);

check($detail2['calculation']['c1']['status'] === 'partial', 't2 C1 status = partial (2 months)');
check($detail2['calculation']['c1']['months_available'] === 2, 't2 C1 months_available = 2', 'got ' . (string) $detail2['calculation']['c1']['months_available']);
check(!empty($detail2['calculation']['c1']['warning']), 't2 C1 has warning text', substr((string) $detail2['calculation']['c1']['warning'], 0, 60));
check(($detail2['calculation']['c1']['monthly'][3]['status'] ?? '') === 'no_data', 't2 month 3 marked no_data in trace');
check($detail2['calculation']['status'] === 'partial', 't2 overall status = partial');

// ================================================ 3. Summary checklist data
echo PHP_EOL . '-- summary checklist data --' . PHP_EOL;
$summary = $workflow->getPeriodAssessmentSummary($testPeriodeId);

check(($summary['operational_counts']['kedisiplinan'] ?? 0) === 5, 'operational counts kedisiplinan = 5 (3+2)', 'got ' . (string) ($summary['operational_counts']['kedisiplinan'] ?? -1));
check(($summary['operational_counts']['pekerjaan'] ?? 0) === 10, 'operational counts pekerjaan = 10 (2x5)', 'got ' . (string) ($summary['operational_counts']['pekerjaan'] ?? -1));
check(($summary['total_evaluations'] ?? 0) === 0, 'no evaluations yet (calc not run) — checklist must show 0');

// Run calculation so evaluations exist
$workflow->calculatePeriod($testPeriodeId, 1);
$summary = $workflow->getPeriodAssessmentSummary($testPeriodeId);

check($summary['total_evaluations'] === 2, 'summary total_evaluations = 2 after calc', 'got ' . (string) $summary['total_evaluations']);
check($summary['calculated_count'] === 1, '1 complete (t1)', 'got ' . (string) $summary['calculated_count']);
check($summary['partial_count'] === 1, '1 partial (t2)', 'got ' . (string) $summary['partial_count']);
check($summary['has_warnings'] === true, 'has_warnings = true (partial present)');
check($summary['can_confirm'] === true, 'can_confirm = true (draft period, evaluations exist)');
check($summary['is_confirmed'] === false, 'is_confirmed = false before owner confirms');
check(!isset($summary['tabulasi']), 'summary does NOT include tabulasi (injected by controller, not service)');

// ================================================ 4. Partials render
echo PHP_EOL . '-- partials render --' . PHP_EOL;
$summary['tabulasi'] = \App\Models\Penilaian::tabulasiByPeriode($testPeriodeId);

$html = viewPartial('partials.calculation_trace', ['detail' => $detail2, 'penilaian' => null]);
check($html !== '' && strlen($html) > 500, 'calculation_trace partial renders output', strlen($html) . ' bytes');
check(str_contains($html, 'Jejak Perhitungan'), 'trace has title "Jejak Perhitungan"');
check(str_contains($html, 'RAW: KEHADIRAN'), 'trace marks raw attendance column');
check(str_contains($html, 'NILAI BULANAN'), 'trace has derived monthly value column');
check(str_contains($html, 'persentase_kehadiran') === false, 'trace does not leak internal key names to user');
check(str_contains($html, '21 / 22 hari') === false, 'trace for t2 does not show t1 raw numbers');

$htmlCheck = viewPartial('partials.pre_confirmation_checklist', ['summary' => $summary]);
check($htmlCheck !== '' && strlen($htmlCheck) > 500, 'pre_confirmation_checklist partial renders', strlen($htmlCheck) . ' bytes');
check(str_contains($htmlCheck, 'Daftar Periksa Sebelum Konfirmasi'), 'checklist has title');
check(str_contains($htmlCheck, 'Data parsial'), 'checklist shows partial data section (t2 is partial)');
check(str_contains($htmlCheck, (string) $t2['kode_teknisi']), 'checklist lists partial technician code', 'expect ' . $t2['kode_teknisi']);
check(str_contains($htmlCheck, 'Siap dikonfirmasi'), 'checklist shows ready-to-confirm badge');
check(str_contains($htmlCheck, 'Kedisiplinan 5 baris'), 'checklist shows raw operational counts', substr($htmlCheck, 0, 0));

// Trace for confirmed evaluation shows confirmed identity
$htmlT1 = viewPartial('partials.calculation_trace', ['detail' => $detail, 'penilaian' => \App\Models\Penilaian::findByPeriodeAndTeknisi($testPeriodeId, (int) $t1['id'])]);
check(str_contains($htmlT1, 'tb_penilaian'), 'trace shows source table tb_penilaian');
check(str_contains($htmlT1, 'CALCULATED'), 'trace shows status CALCULATED for t1');

// Empty detail must not crash partial
$emptyHtml = viewPartial('partials.calculation_trace', ['detail' => ['calculation' => [], 'raw_data' => [], 'periode' => null, 'teknisi' => null]]);
check($emptyHtml !== '' && str_contains($emptyHtml, 'Tidak ada data mentah'), 'trace handles empty detail gracefully');

// ================================================ TEARDOWN
$pid = $testPeriodeId;
Database::query('DELETE FROM tb_hasil WHERE id_periode = ?', [$pid]);
Database::query('DELETE FROM tb_penilaian WHERE id_periode = ?', [$pid]);
Database::query('DELETE FROM tb_kedisiplinan WHERE id_periode = ?', [$pid]);
Database::query('DELETE FROM tb_pekerjaan WHERE id_periode = ?', [$pid]);
Database::query('DELETE FROM tb_tanggung_jawab WHERE id_periode = ?', [$pid]);
Database::query('DELETE FROM tb_periode_penilaian WHERE id_periode = ?', [$pid]);

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
