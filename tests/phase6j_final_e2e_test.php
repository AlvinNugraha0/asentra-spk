<?php
// ASENTRA SPK — Phase 6J: FINAL END-TO-END TEST
//
// Full V2 chain on a throwaway period, then legacy/golden invariants:
//   create period -> Excel -> validate -> import -> calculate -> summary/views
//   -> confirm -> SAW V2 -> ranking view -> report -> teardown.
//
// SAFETY: own isolated period (kode 'TEST6J-2026-Q1'); never touches
// id_periode 1/2/24/25. Legacy tb_penilaian=12 / tb_hasil=12 preserved.

declare(strict_types=1);

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/models/PeriodePenilaian.php';
require_once __DIR__ . '/../app/models/Teknisi.php';
require_once __DIR__ . '/../app/models/Penilaian.php';
require_once __DIR__ . '/../app/models/Hasil.php';
require_once __DIR__ . '/../app/services/AssessmentWorkflowService.php';
require_once __DIR__ . '/../app/services/SawServiceV2.php';
require_once __DIR__ . '/../app/services/ChartDataService.php';
require_once __DIR__ . '/../app/services/import/ExcelImportService.php';
require_once __DIR__ . '/../app/services/import/ExcelTemplateHelper.php';
// View helpers (e(), route(), scoreFormat(), ...) used by the views we render.
require_once __DIR__ . '/../app/helpers/format.php';
require_once __DIR__ . '/../app/helpers/url.php';
require_once __DIR__ . '/../app/helpers/auth.php';

$autoload = dirname(__DIR__) . '/vendor/autoload.php';
if (is_file($autoload)) {
    require_once $autoload;
}

use App\Core\Database;
use App\Models\PeriodePenilaian;
use App\Models\Teknisi;
use App\Models\Penilaian;
use App\Models\Hasil;
use App\Services\AssessmentWorkflowService;
use App\Services\SawServiceV2;
use App\Services\ChartDataService;
use App\Services\Import\ExcelImportService;
use App\Services\Import\ExcelTemplateHelper;

$pass = 0;
$fail = 0;
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

function q1(string $sql, array $params = []): mixed
{
    return Database::query($sql, $params)->fetch();
}

function cleanup(int $periodeId): void
{
    foreach ([
        'tb_hasil', 'tb_penilaian', 'tb_kedisiplinan', 'tb_pekerjaan',
        'tb_tanggung_jawab', 'tb_import',
    ] as $t) {
        Database::query("DELETE FROM {$t} WHERE id_periode = ?", [$periodeId]);
    }
    Database::query('DELETE FROM tb_periode_penilaian WHERE id_periode = ?', [$periodeId]);
}

// ---------------------------------------------------------------------------
echo '========================================' . PHP_EOL;
echo 'PHASE 6J — FINAL END-TO-END TEST' . PHP_EOL;
echo '========================================' . PHP_EOL;

// STEP 0 — Baseline + golden invariants
$basePenilaian = (int) q1('SELECT COUNT(*) AS c FROM tb_penilaian')['c'];
$baseHasil     = (int) q1('SELECT COUNT(*) AS c FROM tb_hasil')['c'];
$legacyPenilaian = (int) q1('SELECT COUNT(*) AS c FROM tb_penilaian WHERE status_data = "legacy"')['c'];
$legacyHasil     = (int) q1('SELECT COUNT(*) AS c FROM tb_hasil h JOIN tb_periode_penilaian p ON p.id_periode = h.id_periode WHERE p.status = "legacy"')['c'];
$legacyHash = md5(implode('|', array_map(
    static fn ($r): string => $r['id'] . '|' . $r['c1'] . '|' . $r['c2'] . '|' . $r['c3'] . '|' . $r['nilai_preferensi'],
    Database::query('SELECT * FROM tb_hasil WHERE id_periode = 1 ORDER BY ranking')->fetchAll()
)));
$sawV2HashBefore = hash_file('sha256', __DIR__ . '/../app/services/SawEngineV2.php');
$sawServiceHashBefore = hash_file('sha256', __DIR__ . '/../app/services/SawServiceV2.php');

echo "Baseline: penilaian={$basePenilaian} hasil={$baseHasil} legacy={$legacyPenilaian}/{$legacyHasil}" . PHP_EOL . PHP_EOL;

check($legacyPenilaian === 12, 'Legacy tb_penilaian = 12 at baseline', "got {$legacyPenilaian}");
check($legacyHasil === 12, 'Legacy tb_hasil = 12 at baseline', "got {$legacyHasil}");
check($basePenilaian === 22 && $baseHasil === 22, 'Baseline totals 22/22', "got {$basePenilaian}/{$baseHasil}");
check($sawV2HashBefore !== false, 'SawEngineV2.php readable for hashing', '');
check($sawServiceHashBefore !== false, 'SawServiceV2.php readable for hashing', '');

// V1 golden 2026-08 exact compare
$v1Golden = [
    'Toni' => '1.000000', 'Aris' => '0.925000', 'Rahmat Hidayat' => '0.900000',
    'Apip' => '0.850000', 'Wanto' => '0.750000', 'Heri' => '0.750000',
    'IMADE' => '0.700000', 'Ahmad Sahudin' => '0.675000',
    'Agus Supriyanto' => '0.600000', 'Asep' => '0.575000',
];
$v1Rows = Database::query(
    'SELECT t.nama, h.nilai_preferensi FROM tb_hasil h
     JOIN tb_teknisi t ON t.id = h.teknisi_id
     WHERE h.id_periode = 1 ORDER BY h.ranking'
)->fetchAll();
$v1Ok = true;
foreach ($v1Rows as $i => $r) {
    $nama = $r['nama'];
    if (!isset($v1Golden[$nama]) || $r['nilai_preferensi'] !== $v1Golden[$nama]) {
        $v1Ok = false;
    }
}
check($v1Ok && count($v1Rows) === 10, 'V1 golden 2026-08 unchanged (exact decimal)', 'mismatch see above');

// V2 golden Q1-2026
$v2Golden = [
    'Toni' => '0.988571', 'Aris' => '0.986957', 'Rahmat Hidayat' => '0.917760',
    'Agus Supriyanto' => '0.884465', 'Ahmad Sahudin' => '0.858619',
    'IMADE' => '0.813350', 'Apip' => '0.793036', 'Heri' => '0.781286',
    'Wanto' => '0.744395', 'Asep' => '0.625265',
];
$v2Rows = Database::query(
    'SELECT t.nama, h.nilai_preferensi FROM tb_hasil h
     JOIN tb_teknisi t ON t.id = h.teknisi_id
     WHERE h.id_periode = 25 ORDER BY h.ranking'
)->fetchAll();
$v2Ok = true;
foreach ($v2Rows as $r) {
    $nama = $r['nama'];
    if (!isset($v2Golden[$nama]) || $r['nilai_preferensi'] !== $v2Golden[$nama]) {
        $v2Ok = false;
    }
}
check($v2Ok && count($v2Rows) === 10, 'V2 golden Q1-2026 unchanged (exact decimal)', '');

// ---------------------------------------------------------------------------
// STEP 1 — Create isolated test period
$testKode = 'TEST6J-2026-Q1';
$leftover = q1('SELECT id_periode FROM tb_periode_penilaian WHERE kode_periode = ?', [$testKode]);
if ($leftover) {
    cleanup((int) $leftover['id_periode']);
}
$testPeriodeId = PeriodePenilaian::create([
    'kode_periode'   => $testKode,
    'nama_periode'   => 'TEST Phase 6J Final E2E',
    'tanggal_mulai'  => '2026-01-01',
    'tanggal_selesai'=> '2026-03-31',
    'status'         => 'draft',
    'created_by'     => 1,
]);
check($testPeriodeId > 0, 'Test period created', "id={$testPeriodeId} kode={$testKode}");
check(!in_array($testPeriodeId, [1, 2, 24, 25], true), 'Test period id is not 1/2/24/25', "id={$testPeriodeId}");

// ---------------------------------------------------------------------------
// STEP 2 — Generate Excel workbook (deterministic, same generator as 6B)
$teknisiList = Teknisi::all();
$byKode = [];
foreach ($teknisiList as $t) {
    $byKode[$t['kode_teknisi']] = $t;
}
$codes = array_slice(array_keys($byKode), 0, 10);
check(count($codes) === 10, '10 technicians available', 'got ' . count($codes));

$monthDays = [1 => 23, 2 => 20, 3 => 23];
$names = ['Servis AC', 'Instalasi', 'Perbaikan', 'Maintenance'];

$mkKedisiplinan = static function (int $ti) use ($codes, $monthDays): array {
    $rows = [];
    foreach ([1, 2, 3] as $m) {
        $h = ($ti * 7 + $m * 3) % 3;
        $total = $monthDays[$m];
        $hadir = $total - (1 + $h) + ($ti % 2);
        $rows[] = [
            (string) $codes[$ti], (string) $m,
            (string) $total, (string) max(0, $hadir), (string) $h,
            (string) (($ti + $m) % 2), (string) (($ti + $m) % 3 === 0 ? 1 : 0),
            (string) (($ti * 2 + $m) % 4), (string) (8 + ($ti % 4) + $m),
            (string) max(0, (8 + ($ti % 4) + $m) - (($ti + $m) % 3)),
        ];
    }
    return $rows;
};
$mkPekerjaan = static function (int $ti) use ($codes, $names): array {
    $rows = [];
    foreach ([1, 2, 3] as $m) {
        foreach ([0, 1] as $j) {
            $rows[] = [
                (string) $codes[$ti],
                sprintf('2026-%02d-%02d', $m, 3 + $j * 12 + ($ti % 9)),
                (string) $m, $names[($ti + $j) % 4],
                (string) (($ti + $m + $j) % 2), (string) (($ti + $j) % 2),
                (string) (($ti + $m) % 2),
            ];
        }
    }
    return $rows;
};
$mkTanggungJawab = static function (int $ti) use ($codes): array {
    $rows = [];
    foreach ([1, 2, 3] as $m) {
        $rows[] = [
            (string) $codes[$ti], (string) $m,
            (string) (1 + (($ti + $m) % 4)), (string) (1 + (($ti * 2 + $m) % 4)),
            (string) (1 + (($ti + $m * 2) % 4)), (string) (1 + (($ti * 3 + $m) % 4)),
        ];
    }
    return $rows;
};

$kediRows = $pekRows = $tjRows = $teknisiSheetRows = [];
foreach ($codes as $i => $kode) {
    $teknisiSheetRows[] = [$kode, $byKode[$kode]['nama']];
    $kediRows = array_merge($kediRows, $mkKedisiplinan($i));
    $pekRows  = array_merge($pekRows, $mkPekerjaan($i));
    $tjRows   = array_merge($tjRows, $mkTanggungJawab($i));
}

$tmpDir = getenv('LOCALAPPDATA') ?: sys_get_temp_dir();
$excelPath = $tmpDir . DIRECTORY_SEPARATOR . 'phase6j_e2e_q1_2026.xlsx';
ExcelTemplateHelper::saveToFile(
    ExcelTemplateHelper::createSpreadsheet([
        ExcelTemplateHelper::SHEET_TEKNISI        => $teknisiSheetRows,
        ExcelTemplateHelper::SHEET_KEDISIPLINAN   => $kediRows,
        ExcelTemplateHelper::SHEET_KUALITAS       => $pekRows,
        ExcelTemplateHelper::SHEET_TANGGUNG_JAWAB => $tjRows,
    ]),
    $excelPath
);
check(is_file($excelPath) && filesize($excelPath) > 0, 'Excel workbook generated', basename($excelPath));

// ---------------------------------------------------------------------------
// STEP 3 — Import
$importService = new ExcelImportService();
$import = $importService->import($excelPath, $testPeriodeId, 1, 'phase6j_e2e_q1_2026.xlsx', true);
check(($import['success'] ?? false) === true, 'Import succeeded', 'msg=' . ($import['message'] ?? ''));
check(($import['total_data'] ?? -1) === 130, 'Import counted 130 data rows', 'got ' . ($import['total_data'] ?? -1));
check(($import['data_gagal'] ?? 1) === 0, '0 failed rows', 'got ' . ($import['data_gagal'] ?? -1));

$kedi = (int) q1('SELECT COUNT(*) AS c FROM tb_kedisiplinan WHERE id_periode = ?', [$testPeriodeId])['c'];
$pek  = (int) q1('SELECT COUNT(*) AS c FROM tb_pekerjaan WHERE id_periode = ?', [$testPeriodeId])['c'];
$tj   = (int) q1('SELECT COUNT(*) AS c FROM tb_tanggung_jawab WHERE id_periode = ?', [$testPeriodeId])['c'];
check($kedi === 30 && $pek === 60 && $tj === 30, 'Raw tables 30/60/30', "kedi={$kedi} pek={$pek} tj={$tj}");

// ---------------------------------------------------------------------------
// STEP 4 — Calculate -> tb_penilaian
$workflow = new AssessmentWorkflowService();
$calc = $workflow->calculatePeriod($testPeriodeId, 1);
check(($calc['total_teknisi'] ?? -1) === 10, 'calculatePeriod: 10 technicians', 'total=' . ($calc['total_teknisi'] ?? -1));
check(($calc['complete_count'] ?? -1) === 10, '10 complete evaluations', 'complete=' . ($calc['complete_count'] ?? -1));
$penCount = (int) q1('SELECT COUNT(*) AS c FROM tb_penilaian WHERE id_periode = ?', [$testPeriodeId])['c'];
check($penCount === 10, 'tb_penilaian = 10 for test period', "got {$penCount}");
$st = q1('SELECT status_data, COUNT(*) AS c FROM tb_penilaian WHERE id_periode = ? GROUP BY status_data', [$testPeriodeId]);
check($st !== false && ($st['status_data'] ?? '') === 'calculated', 'All status_data=calculated', 'got ' . ($st['status_data'] ?? 'none'));

$rangeBad = (int) q1('SELECT COUNT(*) AS c FROM tb_penilaian WHERE id_periode = ? AND (c1 < 0 OR c1 > 4 OR c2 < 0 OR c2 > 4 OR c3 < 0 OR c3 > 4)', [$testPeriodeId])['c'];
check($rangeBad === 0, 'All C1/C2/C3 in [0,4]', "bad={$rangeBad}");

// ---------------------------------------------------------------------------
// STEP 5 — Owner review (summary + tabulation + graphs + detail)
$summary = $workflow->getPeriodAssessmentSummary($testPeriodeId);
check(($summary['total_evaluations'] ?? -1) === 10, 'Review summary: 10 evaluations', 'got ' . ($summary['total_evaluations'] ?? -1));
check(($summary['calculated_count'] ?? -1) === 10, 'Review summary: 10 calculated', 'got ' . ($summary['calculated_count'] ?? -1));
check(($summary['partial_count'] ?? 1) === 0, 'Review summary: 0 partial', 'got ' . ($summary['partial_count'] ?? -1));
check(($summary['draft_count'] ?? 1) === 0, 'Review summary: 0 draft', '');
check(($summary['confirmed_count'] ?? 1) === 0, 'Review summary: 0 confirmed (pre-confirm)', '');

$tab = Penilaian::tabulasiByPeriode($testPeriodeId);
check(count($tab) === 10, 'Tabulation has 10 rows', 'got ' . count($tab));

$detail = $workflow->getTechnicianIndicatorDetail($testPeriodeId, (int) ($tab[0]['teknisi_id'] ?? 0));
check(!empty($detail), 'Technician indicator detail populated', '');
check(isset($detail['calculation']['c1']['subindicators']), 'Detail exposes C1 subindicators', '');
check(isset($detail['calculation']['c2']['subindicators']), 'Detail exposes C2 subindicators', '');
check(isset($detail['calculation']['c3']['subindicators']), 'Detail exposes C3 subindicators', '');

$preSaw = ChartDataService::preferenceValues($testPeriodeId);
check(($preSaw['source'] ?? '') === 'preview', 'Chart source = preview before SAW', 'source=' . ($preSaw['source'] ?? '?'));

// ---------------------------------------------------------------------------
// STEP 6 — Confirm (owner)
$confirm = $workflow->confirmAssessment($testPeriodeId, 2);
check(($confirm['success'] ?? false) === true, 'confirmAssessment succeeded', 'msg=' . ($confirm['message'] ?? ''));
$conf = q1('SELECT COUNT(*) AS c FROM tb_penilaian WHERE id_periode = ? AND status_data = "confirmed"', [$testPeriodeId]);
check((int) $conf['c'] === 10, 'All 10 rows status_data=confirmed', 'got ' . $conf['c']);
$periodStatus = q1('SELECT status FROM tb_periode_penilaian WHERE id_periode = ?', [$testPeriodeId])['status'];
check($periodStatus === 'selesai', 'Period status = selesai after confirm', "got {$periodStatus}");
$confirmedAt = q1('SELECT COUNT(*) AS c FROM tb_penilaian WHERE id_periode = ? AND confirmed_at IS NOT NULL', [$testPeriodeId]);
check((int) $confirmedAt['c'] === 10, 'confirmed_at set on all 10 rows', '');

// Double-confirm must be rejected (already confirmed) — clean RuntimeException.
$doubleOk = false;
try {
    $workflow->confirmAssessment($testPeriodeId, 2);
} catch (RuntimeException $e) {
    $doubleOk = str_contains($e->getMessage(), 'sudah dikonfirmasi');
}
check($doubleOk, 'Re-confirm rejected with clean message', 'expected RuntimeException sudah dikonfirmasi');

// ---------------------------------------------------------------------------
// STEP 7 — SAW V2
$saw = SawServiceV2::process($testPeriodeId);
check(($saw['total_teknisi'] ?? -1) === 10, 'SawServiceV2::process produced 10 rows', 'total=' . ($saw['total_teknisi'] ?? -1));
$hasilCount = (int) q1('SELECT COUNT(*) AS c FROM tb_hasil WHERE id_periode = ?', [$testPeriodeId])['c'];
check($hasilCount === 10, 'tb_hasil = 10 for test period', "got {$hasilCount}");

$rankOk = true;
$prev = null;
foreach (Database::query('SELECT ranking, nilai_preferensi FROM tb_hasil WHERE id_periode = ? ORDER BY ranking', [$testPeriodeId])->fetchAll() as $r) {
    if ($prev !== null && (float) $r['nilai_preferensi'] > (float) $prev) {
        $rankOk = false;
    }
    $prev = $r['nilai_preferensi'];
}
$ranks = array_column(Database::query('SELECT ranking FROM tb_hasil WHERE id_periode = ? ORDER BY ranking', [$testPeriodeId])->fetchAll(), 'ranking');
check($rankOk, 'Vi monotonically non-increasing by rank', '');
check($ranks === array_map(static fn ($i): int => $i, range(1, 10)), 'Ranking is sequential 1..10', implode(',', array_map('strval', $ranks)));

$vi = array_map(static fn ($r): string => $r['nilai_preferensi'], Database::query('SELECT nilai_preferensi FROM tb_hasil WHERE id_periode = ? ORDER BY ranking', [$testPeriodeId])->fetchAll());
$contrib = array_map(static fn ($r): float => (float) $r['kontribusi_c1'] + (float) $r['kontribusi_c2'] + (float) $r['kontribusi_c3'], Database::query('SELECT kontribusi_c1, kontribusi_c2, kontribusi_c3 FROM tb_hasil WHERE id_periode = ? ORDER BY ranking', [$testPeriodeId])->fetchAll());
$drift = 0;
foreach ($vi as $i => $v) {
    if (abs((float) $v - $contrib[$i]) > 0.000001) {
        $drift++;
    }
}
check($drift === 0, 'Vi = k1+k2+k3 for every row (0 drift)', "drift={$drift}");

// Re-run SAW is idempotent
SawServiceV2::process($testPeriodeId);
$hasilCount2 = (int) q1('SELECT COUNT(*) AS c FROM tb_hasil WHERE id_periode = ?', [$testPeriodeId])['c'];
check($hasilCount2 === 10, 'SAW re-run idempotent (still 10)', "got {$hasilCount2}");

$postSaw = ChartDataService::preferenceValues($testPeriodeId);
check(($postSaw['source'] ?? '') === 'saw', 'Chart source = saw after SAW', 'source=' . ($postSaw['source'] ?? '?'));

// Ranking view renders without error
$hasilRows = Hasil::byPeriodeId($testPeriodeId);
ob_start();
$viewData = [
    'title' => 'Ranking', 'subtitle' => 'test', 'periode' => $testKode,
    'results' => $hasilRows, 'periodInfo' => PeriodePenilaian::findById($testPeriodeId),
    'periodOptions' => [], 'canProcess' => false,
];
extract($viewData, EXTR_SKIP);
require __DIR__ . '/../app/views/owner/ranking.php';
$rankingHtml = (string) ob_get_clean();
check(str_contains($rankingHtml, 'Ranking'), 'Ranking view renders', '');
check(str_contains($rankingHtml, 'Nilai Preferensi') || str_contains($rankingHtml, 'Vi'), 'Ranking view shows Vi column', '');

// Laporan view renders
ob_start();
$laporanData = [
    'title' => 'Laporan', 'periode' => $testKode, 'results' => $hasilRows,
    'periodInfo' => PeriodePenilaian::findById($testPeriodeId),
    'periodeLabel' => 'TEST Phase 6J Final E2E (2026-01-01 s/d 2026-03-31)',
];
extract($laporanData, EXTR_SKIP);
require __DIR__ . '/../app/views/owner/laporan.php';
$laporanHtml = (string) ob_get_clean();
check(str_contains($laporanHtml, 'LAPORAN'), 'Laporan view renders', '');

// ---------------------------------------------------------------------------
// STEP 8 — Teardown + invariant restoration
cleanup($testPeriodeId);
if (is_file($excelPath)) {
    unlink($excelPath);
}
// Also remove the stored import copy the service wrote.
$storageDir = ExcelImportService::getStorageDir();
foreach (glob($storageDir . DIRECTORY_SEPARATOR . '*phase6j*') as $f) {
    unlink($f);
}

$afterPenilaian = (int) q1('SELECT COUNT(*) AS c FROM tb_penilaian')['c'];
$afterHasil     = (int) q1('SELECT COUNT(*) AS c FROM tb_hasil')['c'];
$afterLegacyPenilaian = (int) q1('SELECT COUNT(*) AS c FROM tb_penilaian WHERE status_data = "legacy"')['c'];
$afterLegacyHasil     = (int) q1('SELECT COUNT(*) AS c FROM tb_hasil h JOIN tb_periode_penilaian p ON p.id_periode = h.id_periode WHERE p.status = "legacy"')['c'];
$legacyHashAfter = md5(implode('|', array_map(
    static fn ($r): string => $r['id'] . '|' . $r['c1'] . '|' . $r['c2'] . '|' . $r['c3'] . '|' . $r['nilai_preferensi'],
    Database::query('SELECT * FROM tb_hasil WHERE id_periode = 1 ORDER BY ranking')->fetchAll()
)));
$gone = (int) q1('SELECT COUNT(*) AS c FROM tb_periode_penilaian WHERE kode_periode = ?', [$testKode])['c'];

echo PHP_EOL . 'TEARDOWN' . PHP_EOL;
check($afterPenilaian === $basePenilaian, "tb_penilaian restored to {$basePenilaian}", "got {$afterPenilaian}");
check($afterHasil === $baseHasil, "tb_hasil restored to {$baseHasil}", "got {$afterHasil}");
check($afterLegacyPenilaian === 12, 'Legacy tb_penilaian still 12', "got {$afterLegacyPenilaian}");
check($afterLegacyHasil === 12, 'Legacy tb_hasil still 12', "got {$afterLegacyHasil}");
check($legacyHashAfter === $legacyHash, 'Legacy tb_hasil content hash unchanged', '');
check($gone === 0, 'Test period fully removed', "remaining={$gone}");
check(hash_file('sha256', __DIR__ . '/../app/services/SawEngineV2.php') === $sawV2HashBefore, 'SawEngineV2.php unmodified', '');
check(hash_file('sha256', __DIR__ . '/../app/services/SawServiceV2.php') === $sawServiceHashBefore, 'SawServiceV2.php unmodified', '');

// AuthController working tree must be valid PHP starting with exactly <?php
$authHead = file_get_contents(__DIR__ . '/../app/controllers/AuthController.php', false, null, 0, 5);
check($authHead === '<?php', 'AuthController working tree starts with exactly <?php', bin2hex($authHead));

echo PHP_EOL . '========================================' . PHP_EOL;
echo 'RESULT: ' . $pass . '/' . ($pass + $fail) . ' PASS' . PHP_EOL;
echo '========================================' . PHP_EOL;

exit($fail === 0 ? 0 : 1);
