<?php
// ASENTRA SPK — Phase 6K: AUTO-DETECT PERIODE FROM EXCEL
//
// Verifies the Admin V2 improvement end-to-end on a throwaway period:
//   detect quarter from DATA -> create-or-reuse periode -> import -> reject
//   cross-quarter -> reject re-import into a SELESAI period -> explicit-id
//   backward-compat -> teardown.
//
// SAFETY: throwaway kode 'TEST6K-2026-Q2'; never touches id_periode 1/2/24/25.
// Legacy tb_penilaian=12 / tb_hasil=12 preserved. SawEngineV2/SawServiceV2
// must stay byte-identical (sha256 before/after).

declare(strict_types=1);

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/models/PeriodePenilaian.php';
require_once __DIR__ . '/../app/models/Penilaian.php';
require_once __DIR__ . '/../app/models/Teknisi.php';
require_once __DIR__ . '/../app/services/import/ExcelImportService.php';
require_once __DIR__ . '/../app/services/import/ExcelTemplateHelper.php';
require_once __DIR__ . '/../app/services/import/PeriodeDetector.php';

$autoload = dirname(__DIR__) . '/vendor/autoload.php';
if (is_file($autoload)) {
    require_once $autoload;
}

use App\Core\Database;
use App\Models\Penilaian;
use App\Models\PeriodePenilaian;
use App\Models\Teknisi;
use App\Services\Import\ExcelImportService;
use App\Services\Import\ExcelTemplateHelper;
use App\Services\Import\PeriodeDetector;

$pass = 0;
$fail = 0;

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
echo 'PHASE 6K — AUTO-DETECT PERIODE FROM EXCEL' . PHP_EOL;
echo '========================================' . PHP_EOL;

// STEP 0 — Baseline + golden invariants
$basePenilaian = (int) q1('SELECT COUNT(*) AS c FROM tb_penilaian')['c'];
$baseHasil     = (int) q1('SELECT COUNT(*) AS c FROM tb_hasil')['c'];
$basePeriode   = (int) q1('SELECT COUNT(*) AS c FROM tb_periode_penilaian')['c'];
$legacyPenilaian = (int) q1('SELECT COUNT(*) AS c FROM tb_penilaian WHERE status_data = "legacy"')['c'];
$legacyHasil     = (int) q1('SELECT COUNT(*) AS c FROM tb_hasil h JOIN tb_periode_penilaian p ON p.id_periode = h.id_periode WHERE p.status = "legacy"')['c'];
$sawV2HashBefore = hash_file('sha256', __DIR__ . '/../app/services/SawEngineV2.php');
$sawServiceHashBefore = hash_file('sha256', __DIR__ . '/../app/services/SawServiceV2.php');

echo "Baseline: periode={$basePeriode} penilaian={$basePenilaian} hasil={$baseHasil} legacy={$legacyPenilaian}/{$legacyHasil}" . PHP_EOL . PHP_EOL;

check($legacyPenilaian === 12, 'Legacy tb_penilaian = 12 at baseline', "got {$legacyPenilaian}");
check($legacyHasil === 12, 'Legacy tb_hasil = 12 at baseline', "got {$legacyHasil}");
check($basePenilaian === 22 && $baseHasil === 22, 'Baseline totals 22/22', "got {$basePenilaian}/{$baseHasil}");
check($sawV2HashBefore !== false, 'SawEngineV2.php readable for hashing', '');
check($sawServiceHashBefore !== false, 'SawServiceV2.php readable for hashing', '');

$dummyWorkbook = 'C:/Users/MP2HX/Downloads/test-data/dummy_q2_2026.xlsx';
check(is_file($dummyWorkbook), 'Dummy Q2-2026 workbook exists', $dummyWorkbook);

// ---------------------------------------------------------------------------
// STEP 1 — Detect quarter from the DATA (not filename, not hidden cell)
$detected = PeriodeDetector::detect($dummyWorkbook);
check(($detected['ok'] ?? false) === true, 'Detect succeeded', 'error=' . ($detected['error'] ?? ''));
check(($detected['year'] ?? 0) === 2026, 'Detected year = 2026', 'got ' . ($detected['year'] ?? '?'));
check(($detected['quarter'] ?? 0) === 2, 'Detected quarter = 2 (Apr-Jun)', 'got ' . ($detected['quarter'] ?? '?'));
check(($detected['kode_periode'] ?? '') === '2026-Q2', 'Detected kode = 2026-Q2', 'got ' . ($detected['kode_periode'] ?? '?'));
check(($detected['nama_periode'] ?? '') === 'April - Juni 2026', 'Detected nama = "April - Juni 2026"', 'got ' . ($detected['nama_periode'] ?? '?'));
check(($detected['tanggal_mulai'] ?? '') === '2026-04-01', 'Detected start = 2026-04-01', 'got ' . ($detected['tanggal_mulai'] ?? '?'));
check(($detected['tanggal_selesai'] ?? '') === '2026-06-30', 'Detected end = 2026-06-30', 'got ' . ($detected['tanggal_selesai'] ?? '?'));
check($detected['dates']['min'] === '2026-04-03' && $detected['dates']['max'] === '2026-06-21', 'Real dates read from KUALITAS_KERJA', json_encode($detected['dates']));

$qr = PeriodePenilaian::validateQuarterRange($detected['kode_periode'], $detected['tanggal_mulai'], $detected['tanggal_selesai']);
check(($qr['valid'] ?? false) === true, 'Detected range passes validateQuarterRange', implode(' ', $qr['errors'] ?? []));

// ---------------------------------------------------------------------------
// STEP 2 — Create-or-reuse: auto-create when missing
$throwawayKode = '2099-Q2'; // valid YYYY-Qn shape (service validates format) + cannot collide with real data
Database::query('DELETE FROM tb_periode_penilaian WHERE kode_periode = ?', [$throwawayKode]);

$detected['kode_periode'] = $throwawayKode;
$created = PeriodeDetector::resolveOrCreate($detected, 1);
check(($created['ok'] ?? false) === true, 'resolveOrCreate succeeded', 'error=' . ($created['error'] ?? ''));
check(($created['created'] ?? false) === true, 'Periode was CREATED (not reused)', '');
$autoPeriodeId = (int) ($created['id_periode'] ?? 0);
check($autoPeriodeId > 0, 'Auto-created periode has an id', "id={$autoPeriodeId}");
check(!in_array($autoPeriodeId, [1, 2, 24, 25], true), 'Auto periode id is not 1/2/24/25', "id={$autoPeriodeId}");

$row = q1('SELECT * FROM tb_periode_penilaian WHERE id_periode = ?', [$autoPeriodeId]);
check($row !== false && ($row['kode_periode'] ?? '') === $throwawayKode, 'Row kode = 2099-Q2', '');
check(($row['status'] ?? '') === 'draft', 'Auto-created periode status = draft', 'got ' . ($row['status'] ?? '?'));
check((int) ($row['created_by'] ?? 0) === 1, 'created_by = admin id 1', 'got ' . ($row['created_by'] ?? '?'));
check(($row['tanggal_mulai'] ?? '') === '2026-04-01', 'Row start = 2026-04-01', 'got ' . ($row['tanggal_mulai'] ?? '?'));
check(($row['tanggal_selesai'] ?? '') === '2026-06-30', 'Row end = 2026-06-30', 'got ' . ($row['tanggal_selesai'] ?? '?'));

// ---------------------------------------------------------------------------
// STEP 3 — Import into the auto-created periode
$importService = new ExcelImportService();
$import = $importService->import($dummyWorkbook, $autoPeriodeId, 1, 'dummy_q2_2026.xlsx', true);
check(($import['success'] ?? false) === true, 'Import succeeded', 'msg=' . ($import['message'] ?? ''));
check(($import['total_data'] ?? -1) === 130, 'Import counted 130 data rows', 'got ' . ($import['total_data'] ?? -1));
check(($import['data_gagal'] ?? 1) === 0, '0 failed rows', 'got ' . ($import['data_gagal'] ?? -1));

$kedi = (int) q1('SELECT COUNT(*) AS c FROM tb_kedisiplinan WHERE id_periode = ?', [$autoPeriodeId])['c'];
$pek  = (int) q1('SELECT COUNT(*) AS c FROM tb_pekerjaan WHERE id_periode = ?', [$autoPeriodeId])['c'];
$tj   = (int) q1('SELECT COUNT(*) AS c FROM tb_tanggung_jawab WHERE id_periode = ?', [$autoPeriodeId])['c'];
check($kedi === 30 && $pek === 60 && $tj === 30, 'Raw tables 30/60/30', "kedi={$kedi} pek={$pek} tj={$tj}");

// ---------------------------------------------------------------------------
// STEP 4 — Detect again on the same workbook -> REUSE, no duplicate
$periodeCountBefore = (int) q1('SELECT COUNT(*) AS c FROM tb_periode_penilaian')['c'];
$detected2 = PeriodeDetector::detect($dummyWorkbook);
check(($detected2['ok'] ?? false) === true, 'Second detect succeeded', '');
$detected2['kode_periode'] = $throwawayKode;
$reused = PeriodeDetector::resolveOrCreate($detected2, 1);
check(($reused['ok'] ?? false) === true, 'Second resolveOrCreate succeeded', '');
check(($reused['created'] ?? true) === false, 'Periode was REUSED (not created)', '');
check((int) ($reused['id_periode'] ?? 0) === $autoPeriodeId, 'Reused the same periode id', 'got ' . ($reused['id_periode'] ?? 0));
$periodeCountAfter = (int) q1('SELECT COUNT(*) AS c FROM tb_periode_penilaian')['c'];
check($periodeCountAfter === $periodeCountBefore, 'No duplicate periode created', "before={$periodeCountBefore} after={$periodeCountAfter}");
check(PeriodePenilaian::exists($throwawayKode), 'exists() confirms exactly one row', '');

// ---------------------------------------------------------------------------
// STEP 5 — Negative: workbook whose data spans TWO quarters -> clean rejection
$teknisiList = Teknisi::all();
$codes = array_slice(array_map(static fn ($t): string => $t['kode_teknisi'], $teknisiList), 0, 3);
$crossPath = (getenv('LOCALAPPDATA') ?: sys_get_temp_dir()) . DIRECTORY_SEPARATOR . 'phase6k_cross_quarter.xlsx';

// Month 3 (Q1) + month 4 (Q2) in every sheet => data spans two quarters.
$kediRows = $pekRows = $tjRows = $teknisiRows = [];
foreach ($codes as $i => $kode) {
    $teknisiRows[] = [$kode, $teknisiList[$i]['nama']];
    foreach ([3, 4] as $m) {
        $kediRows[] = [$kode, (string) $m, '20', '18', '1', '1', '0', '0', '8', '7'];
        $tjRows[] = [$kode, (string) $m, '2', '3', '2', '4'];
    }
    $pekRows[] = [$kode, '2026-03-05', '3', 'Servis AC', '1', '1', '0'];
    $pekRows[] = [$kode, '2026-04-05', '4', 'Instalasi', '0', '1', '1'];
}
ExcelTemplateHelper::saveToFile(
    ExcelTemplateHelper::createSpreadsheet([
        ExcelTemplateHelper::SHEET_TEKNISI        => $teknisiRows,
        ExcelTemplateHelper::SHEET_KEDISIPLINAN   => $kediRows,
        ExcelTemplateHelper::SHEET_KUALITAS       => $pekRows,
        ExcelTemplateHelper::SHEET_TANGGUNG_JAWAB => $tjRows,
    ]),
    $crossPath
);
check(is_file($crossPath), 'Cross-quarter workbook generated', basename($crossPath));

$cross = PeriodeDetector::detect($crossPath);
check(($cross['ok'] ?? true) === false, 'Cross-quarter detect REJECTED', 'unexpectedly ok');
check(str_contains((string) ($cross['error'] ?? ''), 'kuartal'), 'Rejection message mentions kuartal', 'msg=' . ($cross['error'] ?? ''));
check(($cross['kode_periode'] ?? null) === null, 'No kode produced for cross-quarter data', '');

// ---------------------------------------------------------------------------
// STEP 6 — Negative: periode already SELESAI cannot be re-imported
// id=25 (Q1-2026) is status=selesai with confirmed evaluations.
$selesai = PeriodePenilaian::findById(25);
check(($selesai['status'] ?? '') === 'selesai', 'Baseline periode 25 is selesai', 'got ' . ($selesai['status'] ?? '?'));
$locked = $importService->import($dummyWorkbook, 25, 1, 'dummy_q2_2026.xlsx', true);
check(($locked['success'] ?? true) === false, 'Import into SELESAI periode rejected', 'unexpectedly succeeded');
check(str_contains((string) ($locked['message'] ?? ''), 'dikonfirmasi') || str_contains((string) ($locked['message'] ?? ''), 'SELESAI') || str_contains((string) ($locked['message'] ?? ''), 'dikonfirmasi secara resmi'), 'Lock message is clean (no crash)', 'msg=' . ($locked['message'] ?? ''));
$hasil25 = (int) q1('SELECT COUNT(*) AS c FROM tb_hasil WHERE id_periode = 25')['c'];
$pen25 = (int) q1('SELECT COUNT(*) AS c FROM tb_penilaian WHERE id_periode = 25')['c'];
check($hasil25 === 10 && $pen25 === 10, 'Periode 25 data untouched (10/10)', "penilaian={$pen25} hasil={$hasil25}");

// ---------------------------------------------------------------------------
// STEP 7 — Backward compat: import with EXPLICIT id_periode still works
$explicitKode = '2099-Q4';
$explicitId = PeriodePenilaian::create([
    'kode_periode'   => $explicitKode,
    'nama_periode'   => 'TEST6K explicit backward-compat',
    'tanggal_mulai'  => '2026-04-01',
    'tanggal_selesai'=> '2026-06-30',
    'status'         => 'draft',
    'created_by'     => 1,
]);
check($explicitId > 0, 'Explicit-id test periode created', "id={$explicitId}");
$explicit = $importService->import($dummyWorkbook, $explicitId, 1, 'dummy_q2_2026.xlsx', true);
check(($explicit['success'] ?? false) === true, 'Explicit id_periode import works (backward compat)', 'msg=' . ($explicit['message'] ?? ''));
check(($explicit['total_data'] ?? -1) === 130, 'Explicit import counted 130 rows', 'got ' . ($explicit['total_data'] ?? -1));
cleanup($explicitId);

// ---------------------------------------------------------------------------
// STEP 8 — Teardown + invariant restoration
cleanup($autoPeriodeId);
if (is_file($crossPath)) {
    unlink($crossPath);
}
// Remove the stored copies the import service wrote for both test periods.
$storageDir = ExcelImportService::getStorageDir();
foreach (glob($storageDir . DIRECTORY_SEPARATOR . '*dummy_q2_2026*') as $f) {
    unlink($f);
}

$afterPenilaian = (int) q1('SELECT COUNT(*) AS c FROM tb_penilaian')['c'];
$afterHasil     = (int) q1('SELECT COUNT(*) AS c FROM tb_hasil')['c'];
$afterPeriode   = (int) q1('SELECT COUNT(*) AS c FROM tb_periode_penilaian')['c'];
$afterLegacyPenilaian = (int) q1('SELECT COUNT(*) AS c FROM tb_penilaian WHERE status_data = "legacy"')['c'];
$afterLegacyHasil     = (int) q1('SELECT COUNT(*) AS c FROM tb_hasil h JOIN tb_periode_penilaian p ON p.id_periode = h.id_periode WHERE p.status = "legacy"')['c'];
$gone = (int) q1('SELECT COUNT(*) AS c FROM tb_periode_penilaian WHERE kode_periode = ?', [$throwawayKode])['c'];
$goneExpl = (int) q1('SELECT COUNT(*) AS c FROM tb_periode_penilaian WHERE kode_periode = ?', [$explicitKode])['c'];

echo PHP_EOL . 'TEARDOWN' . PHP_EOL;
check($afterPeriode === $basePeriode, "tb_periode_penilaian restored to {$basePeriode}", "got {$afterPeriode}");
check($afterPenilaian === $basePenilaian, "tb_penilaian restored to {$basePenilaian}", "got {$afterPenilaian}");
check($afterHasil === $baseHasil, "tb_hasil restored to {$baseHasil}", "got {$afterHasil}");
check($afterLegacyPenilaian === 12, 'Legacy tb_penilaian still 12', "got {$afterLegacyPenilaian}");
check($afterLegacyHasil === 12, 'Legacy tb_hasil still 12', "got {$afterLegacyHasil}");
check($gone === 0, 'Throwaway auto-detect period (2099-Q2) fully removed', "remaining={$gone}");
check($goneExpl === 0, 'Explicit-id test period (2099-Q4) fully removed', "remaining={$goneExpl}");
check(hash_file('sha256', __DIR__ . '/../app/services/SawEngineV2.php') === $sawV2HashBefore, 'SawEngineV2.php byte-identical', '');
check(hash_file('sha256', __DIR__ . '/../app/services/SawServiceV2.php') === $sawServiceHashBefore, 'SawServiceV2.php byte-identical', '');

echo PHP_EOL . '========================================' . PHP_EOL;
echo 'RESULT: ' . $pass . '/' . ($pass + $fail) . ' PASS' . PHP_EOL;
echo '========================================' . PHP_EOL;

exit($fail === 0 ? 0 : 1);
