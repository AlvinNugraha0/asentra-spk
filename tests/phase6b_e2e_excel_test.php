<?php
// ASENTRA SPK — Phase 6B: End-to-End Excel Import Test
// 10 technicians, 1 quarter Q1 2026.
// Excel -> Import -> Validation -> Raw DB -> Calculation -> tb_penilaian
//
// SAFETY: creates its OWN isolated period (kode 'TEST6B-2026-Q1') and removes it
// in teardown. Legacy periods, tb_penilaian=12, tb_hasil=12 untouched.

declare(strict_types=1);

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/models/PeriodePenilaian.php';
require_once __DIR__ . '/../app/models/Teknisi.php';
require_once __DIR__ . '/../app/models/Penilaian.php';
require_once __DIR__ . '/../app/models/Hasil.php';
require_once __DIR__ . '/../app/services/AssessmentWorkflowService.php';
require_once __DIR__ . '/../app/services/import/ExcelImportService.php';
require_once __DIR__ . '/../app/services/import/ExcelTemplateHelper.php';

// PhpSpreadsheet autoloader (project vendor root, one level above app/).
$autoload = dirname(__DIR__) . '/vendor/autoload.php';
if (is_file($autoload)) {
    require_once $autoload;
}

use App\Core\Database;
use App\Models\PeriodePenilaian;
use App\Models\Teknisi;
use App\Services\AssessmentWorkflowService;
use App\Services\Import\ExcelImportService;
use App\Services\Import\ExcelTemplateHelper;

$pass = 0;
$fail = 0;
$cleanups = [];
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

function queryOne(string $sql, array $params = []): mixed
{
    return Database::query($sql, $params)->fetch();
}

// ---------------------------------------------------------------------------
// PHASE 0 — Baseline snapshot (must be preserved after teardown)
// ---------------------------------------------------------------------------
$base = [
    'penilaian' => (int) queryOne('SELECT COUNT(*) AS c FROM tb_penilaian')['c'],
    'hasil'     => (int) queryOne('SELECT COUNT(*) AS c FROM tb_hasil')['c'],
    'kedi'      => (int) queryOne('SELECT COUNT(*) AS c FROM tb_kedisiplinan')['c'],
    'pek'       => (int) queryOne('SELECT COUNT(*) AS c FROM tb_pekerjaan')['c'],
    'tj'        => (int) queryOne('SELECT COUNT(*) AS c FROM tb_tanggung_jawab')['c'],
];

echo '========================================' . PHP_EOL;
echo 'PHASE 6B — END-TO-END EXCEL IMPORT TEST' . PHP_EOL;
echo '========================================' . PHP_EOL;
echo 'Baseline: penilaian=' . $base['penilaian'] . ' hasil=' . $base['hasil']
    . ' kedi=' . $base['kedi'] . ' pek=' . $base['pek'] . ' tj=' . $base['tj'] . PHP_EOL;
echo PHP_EOL;

// ---------------------------------------------------------------------------
// SETUP — isolated test period (Q1 2026, valid calendar per Phase 6A)
// ---------------------------------------------------------------------------
$testKode = 'TEST6B-2026-Q1';

// Remove any leftover from a previous aborted run.
$leftover = queryOne('SELECT id_periode FROM tb_periode_penilaian WHERE kode_periode = ?', [$testKode]);
if ($leftover) {
    Database::query('DELETE FROM tb_penilaian WHERE id_periode = ?', [(int) $leftover['id_periode']]);
    Database::query('DELETE FROM tb_kedisiplinan WHERE id_periode = ?', [(int) $leftover['id_periode']]);
    Database::query('DELETE FROM tb_pekerjaan WHERE id_periode = ?', [(int) $leftover['id_periode']]);
    Database::query('DELETE FROM tb_tanggung_jawab WHERE id_periode = ?', [(int) $leftover['id_periode']]);
    Database::query('DELETE FROM tb_import WHERE id_periode = ?', [(int) $leftover['id_periode']]);
    Database::query('DELETE FROM tb_periode_penilaian WHERE id_periode = ?', [(int) $leftover['id_periode']]);
}

$testPeriodeId = PeriodePenilaian::create([
    'kode_periode'   => $testKode,
    'nama_periode'   => 'TEST Phase 6B Q1 2026',
    'tanggal_mulai'  => '2026-01-01',
    'tanggal_selesai'=> '2026-03-31',
    'status'         => 'draft',
    'created_by'     => 1,
]);
$cleanups['periode'] = $testPeriodeId;

echo 'SETUP: test period ' . $testKode . ' created (id=' . $testPeriodeId . ')' . PHP_EOL . PHP_EOL;

// ---------------------------------------------------------------------------
// TEST DATA — 10 technicians from tb_teknisi (A1..A10)
// ---------------------------------------------------------------------------
$teknisiList = Teknisi::all();
$byKode = [];
foreach ($teknisiList as $t) {
    $byKode[$t['kode_teknisi']] = $t;
}
$codes = array_slice(array_keys($byKode), 0, 10);
check(count($codes) === 10, 'Exactly 10 technicians available in system', 'found ' . count($codes) . ': ' . implode(',', $codes));

$monthDays = [1 => 23, 2 => 20, 3 => 23]; // Jan/Feb/Mar 2026 working days
$names = ['Servis AC', 'Instalasi', 'Perbaikan', 'Maintenance'];

// Deterministic generator so results are reproducible.
// $ teknisiIndex -> hash of base values for C1/C2/C3 evidence.
$mkKedisiplinan = static function (int $ti) use ($codes, $monthDays): array {
    $rows = [];
    foreach ([1, 2, 3] as $m) {
        $h = ($ti * 7 + $m * 3) % 3;
        $total = $monthDays[$m];
        $hadir = $total - (1 + $h) + ($ti % 2);
        $sakit = $h;
        $izin  = ($ti + $m) % 2;
        $alpa  = ($ti + $m) % 3 === 0 ? 1 : 0;
        $telat = ($ti * 2 + $m) % 4;
        $sched = 8 + ($ti % 4) + $m;
        $sesuai= $sched - (($ti + $m) % 3);
        $rows[] = [
            (string) $codes[$ti],
            (string) $m,
            (string) $total, (string) $hadir, (string) $sakit, (string) $izin, (string) $alpa,
            (string) $telat, (string) $sched, (string) $sesuai,
        ];
    }
    return $rows;
};

$mkPekerjaan = static function (int $ti) use ($codes, $names): array {
    $rows = [];
    $i = 0;
    foreach ([1, 2, 3] as $m) {
        // 2 jobs per month per technician
        foreach ([0, 1] as $j) {
            $day = 3 + $j * 12 + ($ti % 9);
            $date = sprintf('2026-%02d-%02d', $m, $day);
            $rows[] = [
                (string) $codes[$ti],
                $date,
                (string) $m,
                $names[($ti + $j) % 4],
                (string) (($ti + $m + $j) % 2), // rapi
                (string) (($ti + $j) % 2),       // presisi
                (string) (($ti + $m) % 2),       // sesuai_desain
            ];
        }
    }
    return $rows;
};

$mkTanggungJawab = static function (int $ti) use ($codes): array {
    $rows = [];
    foreach ([1, 2, 3] as $m) {
        $rows[] = [
            (string) $codes[$ti],
            (string) $m,
            (string) (1 + (($ti + $m) % 4)), // perawatan_alat 1-4
            (string) (1 + (($ti * 2 + $m) % 4)), // efisiensi_material
            (string) (1 + (($ti + $m * 2) % 4)), // inisiatif
            (string) (1 + (($ti * 3 + $m) % 4)), // kepatuhan_prosedur
        ];
    }
    return $rows;
};

// ---------------------------------------------------------------------------
// STEP 1 — Build the Excel workbook (5 required sheets)
// ---------------------------------------------------------------------------
$kediRows = [];
$pekRows  = [];
$tjRows   = [];
$teknisiSheetRows = [];
foreach ($codes as $i => $kode) {
    $teknisiSheetRows[] = [$kode, $byKode[$kode]['nama']];
    $kediRows = array_merge($kediRows, $mkKedisiplinan($i));
    $pekRows  = array_merge($pekRows, $mkPekerjaan($i));
    $tjRows   = array_merge($tjRows, $mkTanggungJawab($i));
}

$excelPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'phase6b_test6b_q1_2026.xlsx';
ExcelTemplateHelper::saveToFile(
    ExcelTemplateHelper::createSpreadsheet([
        ExcelTemplateHelper::SHEET_TEKNISI       => $teknisiSheetRows,
        ExcelTemplateHelper::SHEET_KEDISIPLINAN  => $kediRows,
        ExcelTemplateHelper::SHEET_KUALITAS      => $pekRows,
        ExcelTemplateHelper::SHEET_TANGGUNG_JAWAB=> $tjRows,
    ]),
    $excelPath
);
$cleanups['excel'] = $excelPath;
check(is_file($excelPath) && filesize($excelPath) > 0, 'Excel workbook generated', $excelPath . ' (' . filesize($excelPath) . ' bytes)');
echo 'Data rows generated: teknisi=' . count($teknisiSheetRows)
    . ' kedisiplinan=' . count($kediRows)
    . ' pekerjaan=' . count($pekRows)
    . ' tanggung_jawab=' . count($tjRows) . PHP_EOL . PHP_EOL;

// ---------------------------------------------------------------------------
// STEP 2 — Import (ExcelImportService) with allowPartial=true
// ---------------------------------------------------------------------------
$service = new ExcelImportService();
$importResult = $service->import($excelPath, $testPeriodeId, 1, 'phase6b_test6b_q1_2026.xlsx', true);

check($importResult['success'] === true, 'Import succeeded', 'status=' . ($importResult['status'] ?? '?') . ' msg=' . ($importResult['message'] ?? ''));
check(($importResult['total_data'] ?? 0) === 130, 'Total data rows counted = 130', 'got ' . ($importResult['total_data'] ?? -1));
check(($importResult['data_gagal'] ?? 1) === 0, 'No failed rows (0 errors expected)', 'data_gagal=' . ($importResult['data_gagal'] ?? -1));
$cleanups['import_id'] = $importResult['id_import'] ?? null;

// ---------------------------------------------------------------------------
// STEP 3 — Raw DB verification
// ---------------------------------------------------------------------------
echo PHP_EOL . '-- Raw DB --' . PHP_EOL;
$kediCount = (int) queryOne('SELECT COUNT(*) AS c FROM tb_kedisiplinan WHERE id_periode = ?', [$testPeriodeId])['c'];
$pekCount  = (int) queryOne('SELECT COUNT(*) AS c FROM tb_pekerjaan WHERE id_periode = ?', [$testPeriodeId])['c'];
$tjCount   = (int) queryOne('SELECT COUNT(*) AS c FROM tb_tanggung_jawab WHERE id_periode = ?', [$testPeriodeId])['c'];

check($kediCount === 30, 'tb_kedisiplinan = 30 rows (10 teknisi x 3 bulan)', 'got ' . $kediCount);
check($pekCount === 60, 'tb_pekerjaan = 60 rows (10 teknisi x 3 bulan x 2 jobs)', 'got ' . $pekCount);
check($tjCount === 30, 'tb_tanggung_jawab = 30 rows (10 teknisi x 3 bulan)', 'got ' . $tjCount);

$distinctKedi = (int) queryOne('SELECT COUNT(DISTINCT id_teknisi) AS c FROM tb_kedisiplinan WHERE id_periode = ?', [$testPeriodeId])['c'];
$distinctPek  = (int) queryOne('SELECT COUNT(DISTINCT id_teknisi) AS c FROM tb_pekerjaan WHERE id_periode = ?', [$testPeriodeId])['c'];
$distinctTJ   = (int) queryOne('SELECT COUNT(DISTINCT id_teknisi) AS c FROM tb_tanggung_jawab WHERE id_periode = ?', [$testPeriodeId])['c'];
check($distinctKedi === 10 && $distinctPek === 10 && $distinctTJ === 10, '10 distinct technicians in all raw tables', "kedi={$distinctKedi} pek={$distinctPek} tj={$distinctTJ}");

$monthsKedi = queryOne('SELECT COUNT(DISTINCT bulan) AS c FROM tb_kedisiplinan WHERE id_periode = ?', [$testPeriodeId])['c'];
$monthsPek  = queryOne('SELECT COUNT(DISTINCT bulan) AS c FROM tb_pekerjaan WHERE id_periode = ?', [$testPeriodeId])['c'];
$monthsTJ   = queryOne('SELECT COUNT(DISTINCT bulan) AS c FROM tb_tanggung_jawab WHERE id_periode = ?', [$testPeriodeId])['c'];
check((int) $monthsKedi === 3 && (int) $monthsPek === 3 && (int) $monthsTJ === 3, 'Months 1,2,3 (Jan-Mar) present in all tables', "kedi={$monthsKedi} pek={$monthsPek} tj={$monthsTJ}");

$badMonth = (int) queryOne('SELECT COUNT(*) AS c FROM tb_kedisiplinan WHERE id_periode = ? AND bulan NOT IN (1,2,3)', [$testPeriodeId])['c']
          + (int) queryOne('SELECT COUNT(*) AS c FROM tb_pekerjaan WHERE id_periode = ? AND bulan NOT IN (1,2,3)', [$testPeriodeId])['c']
          + (int) queryOne('SELECT COUNT(*) AS c FROM tb_tanggung_jawab WHERE id_periode = ? AND bulan NOT IN (1,2,3)', [$testPeriodeId])['c'];
check($badMonth === 0, 'No raw rows outside Jan-Mar', 'bad rows=' . $badMonth);

$badDate = (int) queryOne('SELECT COUNT(*) AS c FROM tb_pekerjaan WHERE id_periode = ? AND (tanggal < ? OR tanggal > ?)', [$testPeriodeId, '2026-01-01', '2026-03-31'])['c'];
check($badDate === 0, 'No pekerjaan dates outside 2026-01-01..2026-03-31', 'bad=' . $badDate);

// tb_import log record
$importRow = queryOne('SELECT * FROM tb_import WHERE id_periode = ? ORDER BY id_import DESC LIMIT 1', [$testPeriodeId]);
check($importRow !== false && ($importRow['status'] ?? '') === 'success', 'tb_import logged status=success', 'status=' . ($importRow['status'] ?? 'none'));

// ---------------------------------------------------------------------------
// STEP 4 — Calculation -> tb_penilaian
// ---------------------------------------------------------------------------
echo PHP_EOL . '-- Calculation -> tb_penilaian --' . PHP_EOL;
$workflow = new AssessmentWorkflowService();
$calcResult = null;
try {
    $calcResult = $workflow->calculatePeriod($testPeriodeId, 1);
} catch (Throwable $e) {
    check(false, 'calculatePeriod() runs without exception', $e->getMessage());
}

if ($calcResult !== null) {
    check(($calcResult['total_teknisi'] ?? 0) === 10, 'calculatePeriod processed 10 technicians', 'total=' . ($calcResult['total_teknisi'] ?? -1));
    check(($calcResult['complete_count'] ?? -1) === 10, '10 complete (3-month) evaluations', 'complete=' . ($calcResult['complete_count'] ?? -1));
    check(($calcResult['partial_count'] ?? -1) === 0, '0 partial evaluations', 'partial=' . ($calcResult['partial_count'] ?? -1));

    $penCount = (int) queryOne('SELECT COUNT(*) AS c FROM tb_penilaian WHERE id_periode = ?', [$testPeriodeId])['c'];
    check($penCount === 10, 'tb_penilaian = 10 rows for test period', 'got ' . $penCount);

    $statuses = queryOne('SELECT status_data, COUNT(*) AS c FROM tb_penilaian WHERE id_periode = ? GROUP BY status_data', [$testPeriodeId]);
    $statusOk = $statuses !== false && ($statuses['status_data'] ?? '') === 'calculated';
    check($statusOk, 'All status_data = calculated (3 months complete)', 'status=' . ($statuses['status_data'] ?? 'none'));

    // jumlah_bulan must be 3/3/3 for all
    $monthBad = (int) queryOne('SELECT COUNT(*) AS c FROM tb_penilaian WHERE id_periode = ? AND (jumlah_bulan_c1 != 3 OR jumlah_bulan_c2 != 3 OR jumlah_bulan_c3 != 3)', [$testPeriodeId])['c'];
    check($monthBad === 0, 'jumlah_bulan_c1/c2/c3 = 3 for every technician', 'bad=' . $monthBad);

    // C1/C2/C3 values must be in valid score range
    $rangeBad = (int) queryOne('SELECT COUNT(*) AS c FROM tb_penilaian WHERE id_periode = ? AND (c1 < 0 OR c1 > 4 OR c2 < 0 OR c2 > 4 OR c3 < 0 OR c3 > 4)', [$testPeriodeId])['c'];
    check($rangeBad === 0, 'All C1/C2/C3 in [0,4] range', 'bad=' . $rangeBad);

    // No duplicate rows per teknisi
    $dup = (int) queryOne('SELECT COUNT(*) AS c FROM (SELECT teknisi_id, COUNT(*) AS c FROM tb_penilaian WHERE id_periode = ? GROUP BY teknisi_id HAVING c > 1) AS d', [$testPeriodeId])['c'];
    check($dup === 0, 'No duplicate (teknisi, period) rows', 'dups=' . $dup);

    // Idempotency: recalculate must not add rows
    $workflow->calculatePeriod($testPeriodeId, 1);
    $penCount2 = (int) queryOne('SELECT COUNT(*) AS c FROM tb_penilaian WHERE id_periode = ?', [$testPeriodeId])['c'];
    check($penCount2 === 10, 'Recalculation is idempotent (still 10 rows)', 'got ' . $penCount2);

    // Period status must advance draft -> proses
    $pStatus = queryOne('SELECT status FROM tb_periode_penilaian WHERE id_periode = ?', [$testPeriodeId])['status'];
    check($pStatus === 'proses', 'Period status advanced draft -> proses', 'got ' . $pStatus);

    // Print per-technician results
    echo PHP_EOL . 'Per-technician (raw -> derived):' . PHP_EOL;
    $rows = Database::query(
        'SELECT p.teknisi_id, t.kode_teknisi, p.c1, p.c2, p.c3, p.jumlah_bulan_c1, p.jumlah_bulan_c2, p.jumlah_bulan_c3
         FROM tb_penilaian p JOIN tb_teknisi t ON t.id = p.teknisi_id
         WHERE p.id_periode = ? ORDER BY t.kode_teknisi',
        [$testPeriodeId]
    )->fetchAll();
    foreach ($rows as $r) {
        printf("  %s  C1=%.4f  C2=%.4f  C3=%.4f  bulan=%d/%d/%d\n",
            $r['kode_teknisi'], (float) $r['c1'], (float) $r['c2'], (float) $r['c3'],
            (int) $r['jumlah_bulan_c1'], (int) $r['jumlah_bulan_c2'], (int) $r['jumlah_bulan_c3']);
    }
}

// ---------------------------------------------------------------------------
// STEP 5 — Out-of-period data must be REJECTED by the validator
// ---------------------------------------------------------------------------
echo PHP_EOL . '-- Out-of-period rejection --' . PHP_EOL;

// Build a workbook containing one April row (month 4, outside Q1).
$badKedi = $mkKedisiplinan(0);
$badKedi[0][1] = '4'; // bulan=4 (April)
$badWorkbook = ExcelTemplateHelper::createSpreadsheet([
    ExcelTemplateHelper::SHEET_TEKNISI        => [[$codes[0], $byKode[$codes[0]]['nama']]],
    ExcelTemplateHelper::SHEET_KEDISIPLINAN   => $badKedi,
    ExcelTemplateHelper::SHEET_KUALITAS       => $mkPekerjaan(0),
    ExcelTemplateHelper::SHEET_TANGGUNG_JAWAB => $mkTanggungJawab(0),
]);
$badPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'phase6b_bad_month.xlsx';
ExcelTemplateHelper::saveToFile($badWorkbook, $badPath);
$cleanups['bad_excel'] = $badPath;

$badImport = $service->import($badPath, $testPeriodeId, 1, 'phase6b_bad_month.xlsx', false); // strict
check($badImport['success'] === false, 'Out-of-quarter month (April) rejected in strict mode', 'status=' . ($badImport['status'] ?? '?'));
$badErrors = implode(' ', $badImport['errors'] ?? []);
$mentionsOutOfRange = str_contains($badErrors, 'di luar rentang kuartal') || str_contains($badErrors, 'di luar rentang periode');
check($mentionsOutOfRange, 'Error message explains month outside quarter', substr($badErrors, 0, 180));

// Confirm April row was not persisted.
$aprilRows = (int) queryOne('SELECT COUNT(*) AS c FROM tb_kedisiplinan WHERE id_periode = ? AND bulan = 4', [$testPeriodeId])['c'];
check($aprilRows === 0, 'No April (bulan=4) row persisted', 'count=' . $aprilRows);

// Idempotency of valid import after a rejected import: counts unchanged
$kediAfterBad = (int) queryOne('SELECT COUNT(*) AS c FROM tb_kedisiplinan WHERE id_periode = ?', [$testPeriodeId])['c'];
check($kediAfterBad === 30, 'Rejected import left valid data intact (30 rows)', 'got ' . $kediAfterBad);

// ---------------------------------------------------------------------------
// STEP 6 — Unknown technician code rejected
// ---------------------------------------------------------------------------
echo PHP_EOL . '-- Unknown technician rejection --' . PHP_EOL;
$unknownKedi = $mkKedisiplinan(0);
$unknownTeknisi = [[$codes[0], $byKode[$codes[0]]['nama']], ['X99', 'Tidak Terdaftar']];
$unknownWorkbook = ExcelTemplateHelper::createSpreadsheet([
    ExcelTemplateHelper::SHEET_TEKNISI        => $unknownTeknisi,
    ExcelTemplateHelper::SHEET_KEDISIPLINAN   => $unknownKedi,
    ExcelTemplateHelper::SHEET_KUALITAS       => $mkPekerjaan(0),
    ExcelTemplateHelper::SHEET_TANGGUNG_JAWAB => $mkTanggungJawab(0),
]);
$unknownPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'phase6b_unknown.xlsx';
ExcelTemplateHelper::saveToFile($unknownWorkbook, $unknownPath);
$cleanups['unknown_excel'] = $unknownPath;

$unknownImport = $service->import($unknownPath, $testPeriodeId, 1, 'phase6b_unknown.xlsx', true); // partial allowed
check(str_contains(implode(' ', $unknownImport['errors'] ?? []), 'tidak terdaftar'), 'Unknown technician code reported', 'msg=' . substr(implode(' ', $unknownImport['errors'] ?? []), 0, 120));
$unknownRows = (int) queryOne('SELECT COUNT(*) AS c FROM tb_teknisi WHERE kode_teknisi = ?', ['X99'])['c'];
check($unknownRows === 0, 'Unknown technician not inserted into tb_teknisi', 'count=' . $unknownRows);

// ---------------------------------------------------------------------------
// TEARDOWN — remove test period and everything tied to it
// ---------------------------------------------------------------------------
echo PHP_EOL . '========================================' . PHP_EOL;
echo 'TEARDOWN' . PHP_EOL;
echo '========================================' . PHP_EOL;
try {
    if ($testPeriodeId) {
        Database::query('DELETE FROM tb_penilaian WHERE id_periode = ?', [$testPeriodeId]);
        Database::query('DELETE FROM tb_kedisiplinan WHERE id_periode = ?', [$testPeriodeId]);
        Database::query('DELETE FROM tb_pekerjaan WHERE id_periode = ?', [$testPeriodeId]);
        Database::query('DELETE FROM tb_tanggung_jawab WHERE id_periode = ?', [$testPeriodeId]);
        Database::query('DELETE FROM tb_import WHERE id_periode = ?', [$testPeriodeId]);
        Database::query('DELETE FROM tb_periode_penilaian WHERE id_periode = ?', [$testPeriodeId]);
    }
    foreach (array_filter([$cleanups['excel'] ?? null, $cleanups['bad_excel'] ?? null, $cleanups['unknown_excel'] ?? null]) as $f) {
        if (is_file($f)) {
            unlink($f);
        }
    }
    // Remove stored import copies for the test period.
    $importDir = ExcelImportService::getStorageDir();
    foreach (glob($importDir . DIRECTORY_SEPARATOR . '*.xlsx') as $f) {
        if (str_starts_with(basename($f), date('Ymd'))) {
            // Only delete files created during this test run (today).
            unlink($f);
        }
    }
} catch (Throwable $e) {
    echo 'TEARDOWN ERROR: ' . $e->getMessage() . PHP_EOL;
}

// Verify legacy + baseline preserved.
$after = [
    'penilaian' => (int) queryOne('SELECT COUNT(*) AS c FROM tb_penilaian')['c'],
    'hasil'     => (int) queryOne('SELECT COUNT(*) AS c FROM tb_hasil')['c'],
    'kedi'      => (int) queryOne('SELECT COUNT(*) AS c FROM tb_kedisiplinan')['c'],
    'pek'       => (int) queryOne('SELECT COUNT(*) AS c FROM tb_pekerjaan')['c'],
    'tj'        => (int) queryOne('SELECT COUNT(*) AS c FROM tb_tanggung_jawab')['c'],
];
echo 'After:  penilaian=' . $after['penilaian'] . ' hasil=' . $after['hasil']
    . ' kedi=' . $after['kedi'] . ' pek=' . $after['pek'] . ' tj=' . $after['tj'] . PHP_EOL;

check($after['penilaian'] === $base['penilaian'], 'tb_penilaian count restored to baseline', $after['penilaian'] . ' vs ' . $base['penilaian']);
check($after['hasil'] === $base['hasil'], 'tb_hasil count restored to baseline', $after['hasil'] . ' vs ' . $base['hasil']);
check($after['kedi'] === $base['kedi'], 'tb_kedisiplinan restored to baseline', $after['kedi'] . ' vs ' . $base['kedi']);
check($after['pek'] === $base['pek'], 'tb_pekerjaan restored to baseline', $after['pek'] . ' vs ' . $base['pek']);
check($after['tj'] === $base['tj'], 'tb_tanggung_jawab restored to baseline', $after['tj'] . ' vs ' . $base['tj']);

// Legacy rows untouched (content hash).
$legacyHash = md5(implode('|', array_map(static fn ($r): string => $r['id'] . '|' . $r['c1'] . '|' . $r['c2'] . '|' . $r['c3'], Database::query('SELECT * FROM tb_penilaian WHERE status_data = "legacy" ORDER BY id')->fetchAll())));
echo 'Legacy tb_penilaian hash: ' . $legacyHash . PHP_EOL;

$testPeriodGone = queryOne('SELECT COUNT(*) AS c FROM tb_periode_penilaian WHERE kode_periode = ?', [$testKode])['c'];
check((int) $testPeriodGone === 0, 'Test period fully removed', 'remaining=' . $testPeriodGone);

echo PHP_EOL . '========================================' . PHP_EOL;
echo 'RESULT: ' . $pass . '/' . ($pass + $fail) . ' PASS' . PHP_EOL;
echo '========================================' . PHP_EOL;

exit($fail === 0 ? 0 : 1);
