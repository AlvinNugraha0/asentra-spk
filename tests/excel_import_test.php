<?php
// ASENTRA SPK — Excel Importer V2 Test Suite
// Covers all 17 scenarios required by specification.
// Validates raw storage only: NO C1/C2/C3 calculation, NO SAW, NO confirmation, legacy protected.

declare(strict_types=1);

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/helpers/format.php';
require_once __DIR__ . '/../app/models/User.php';
require_once __DIR__ . '/../app/models/Teknisi.php';
require_once __DIR__ . '/../app/models/PeriodePenilaian.php';
require_once __DIR__ . '/../app/models/Import.php';
require_once __DIR__ . '/../app/models/Kedisiplinan.php';
require_once __DIR__ . '/../app/models/Pekerjaan.php';
require_once __DIR__ . '/../app/models/TanggungJawab.php';
require_once __DIR__ . '/../app/models/Penilaian.php';
require_once __DIR__ . '/../app/models/Hasil.php';
require_once __DIR__ . '/../app/services/import/ExcelTemplateHelper.php';
require_once __DIR__ . '/../app/services/import/ExcelValidator.php';
require_once __DIR__ . '/../app/services/import/ExcelImportService.php';

use App\Core\Database;
use App\Models\Import;
use App\Models\Kedisiplinan;
use App\Models\Pekerjaan;
use App\Models\Penilaian;
use App\Models\PeriodePenilaian;
use App\Models\TanggungJawab;
use App\Models\Teknisi;
use App\Models\User;
use App\Services\Import\ExcelImportService;
use App\Services\Import\ExcelTemplateHelper;
use App\Services\Import\ExcelValidator;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$results = [];

function record(array &$results, string $name, bool $ok, string $detail = ''): void
{
    $results[] = ['name' => $name, 'ok' => $ok, 'detail' => $detail];
    echo ($ok ? 'OK' : 'FAIL') . ': ' . $name . ($detail ? ' — ' . $detail : '') . PHP_EOL;
}

echo "========================================\n";
echo "TESTING EXCEL IMPORTER V2 (17 SCENARIOS)\n";
echo "========================================\n\n";

$pdo = Database::getConnection();

// Directory for test fixture files
$fixtureDir = ROOT_PATH . '/storage/imports/test_fixtures';
if (!is_dir($fixtureDir)) {
    mkdir($fixtureDir, 0755, true);
}

// Find Admin and Owner users
$adminUser = Database::query("SELECT id FROM tb_user WHERE role = 'admin' AND status = 'active' LIMIT 1")->fetch();
$ownerUser = Database::query("SELECT id FROM tb_user WHERE role = 'owner' AND status = 'active' LIMIT 1")->fetch();

if (!$adminUser || !$ownerUser) {
    die("FATAL: Admin or Owner user not found in database.\n");
}
$adminId = (int) $adminUser['id'];
$ownerId = (int) $ownerUser['id'];

// Create non-legacy test period for Q4 2026 (Months 10, 11, 12)
$testKodePeriode = 'TEST-2026-Q4';
$existingPeriod = PeriodePenilaian::findByKode($testKodePeriode);
if ($existingPeriod) {
    $testPeriodeId = (int) $existingPeriod['id_periode'];
} else {
    $testPeriodeId = PeriodePenilaian::create([
        'kode_periode' => $testKodePeriode,
        'nama_periode' => 'Test Periode Kuartal 4 2026',
        'tanggal_mulai' => '2026-10-01',
        'tanggal_selesai' => '2026-12-31',
        'status' => 'draft',
        'created_by' => $adminId,
    ]);
}

// Find existing legacy period (e.g. 2026-08)
$legacyPeriod = Database::query("SELECT id_periode FROM tb_periode_penilaian WHERE status = 'legacy' LIMIT 1")->fetch();
$legacyPeriodeId = $legacyPeriod ? (int) $legacyPeriod['id_periode'] : 0;

// Find valid technicians (e.g. A1, A2)
$allTeknisi = Teknisi::all();
if (count($allTeknisi) < 2) {
    die("FATAL: At least 2 technicians required in tb_teknisi.\n");
}
$teknisi1 = $allTeknisi[0];
$teknisi2 = $allTeknisi[1];
$k1 = $teknisi1['kode_teknisi'];
$k2 = $teknisi2['kode_teknisi'];
$n1 = $teknisi1['nama'];
$n2 = $teknisi2['nama'];
$t1Id = (int) $teknisi1['id'];
$t2Id = (int) $teknisi2['id'];

// Record initial baseline count for legacy records
$initialLegacyPenilaianCount = (int) Database::query("SELECT COUNT(*) AS c FROM tb_penilaian WHERE status_data = 'legacy'")->fetch()['c'];
$initialLegacyHasilCount = (int) Database::query("SELECT COUNT(*) AS c FROM tb_hasil")->fetch()['c'];

$importService = new ExcelImportService();

// Helper to create standard valid custom data
function getStandardValidData(string $k1, string $k2, string $n1, string $n2): array
{
    return [
        ExcelTemplateHelper::SHEET_TEKNISI => [
            [$k1, $n1],
            [$k2, $n2],
        ],
        ExcelTemplateHelper::SHEET_KEDISIPLINAN => [
            [$k1, 10, 22, 22, 0, 0, 0, 0, 10, 10],
            [$k1, 11, 21, 20, 1, 0, 0, 1, 12, 11],
            [$k1, 12, 20, 19, 0, 1, 0, 2, 8, 8],
            [$k2, 10, 22, 21, 1, 0, 0, 0, 10, 9],
            [$k2, 11, 21, 21, 0, 0, 0, 0, 10, 10],
            [$k2, 12, 20, 20, 0, 0, 0, 0, 8, 8],
        ],
        ExcelTemplateHelper::SHEET_KUALITAS => [
            [$k1, '2026-10-15', 10, 'Instalasi Server Rak A', 1, 1, 1],
            [$k1, '2026-11-10', 11, 'Troubleshoot Switching Lantai 2', 1, 1, 1],
            [$k1, '2026-12-05', 12, 'Maintenance Genset Ruang Daya', 1, 1, 0],
            [$k2, '2026-10-18', 10, 'Instalasi Kabel UTP Cat6', 1, 1, 1],
            [$k2, '2026-11-20', 11, 'Konfigurasi Mikrotik CCR', 1, 0, 1],
            [$k2, '2026-12-12', 12, 'Pemasangan Access Point Unifi', 1, 1, 1],
        ],
        ExcelTemplateHelper::SHEET_TANGGUNG_JAWAB => [
            [$k1, 10, 4, 4, 3, 4],
            [$k1, 11, 4, 3, 4, 4],
            [$k1, 12, 3, 4, 4, 3],
            [$k2, 10, 4, 4, 4, 4],
            [$k2, 11, 3, 3, 4, 4],
            [$k2, 12, 4, 3, 3, 4],
        ],
    ];
}

// ---------------------------------------------------------------------
// 1. Test: Valid .xlsx file format
// ---------------------------------------------------------------------
echo "--- Scenario 1: Valid .xlsx file ---\n";
$fileValid = $fixtureDir . '/test_1_valid.xlsx';
$ssValid = ExcelTemplateHelper::createSpreadsheet(getStandardValidData($k1, $k2, $n1, $n2));
ExcelTemplateHelper::saveToFile($ssValid, $fileValid);

$res1 = $importService->import($fileValid, $testPeriodeId, $adminId, 'test_1_valid.xlsx');
record($results, "1. Valid .xlsx accepted", $res1['success'] === true && $res1['status'] === 'success');
record($results, "1. Valid .xlsx total rows match", $res1['total_data'] === 20 && $res1['data_berhasil'] === 20, "Got total={$res1['total_data']}, berhasil={$res1['data_berhasil']}");

// ---------------------------------------------------------------------
// 2. Test: File bukan .xlsx
// ---------------------------------------------------------------------
echo "\n--- Scenario 2: File bukan .xlsx ---\n";
$fileTxt = $fixtureDir . '/test_2_invalid.txt';
file_put_contents($fileTxt, "Ini file teks biasa, bukan file excel.");

$res2 = $importService->import($fileTxt, $testPeriodeId, $adminId, 'test_2_invalid.txt');
record($results, "2. File bukan .xlsx ditolak", $res2['success'] === false && $res2['status'] === 'failed');
record($results, "2. File bukan .xlsx pesan error jelas", str_contains($res2['message'], '.xlsx'));

// ---------------------------------------------------------------------
// 3. Test: Sheet wajib hilang
// ---------------------------------------------------------------------
echo "\n--- Scenario 3: Sheet wajib hilang ---\n";
$fileMissingSheet = $fixtureDir . '/test_3_missing_sheet.xlsx';
$ssMissing = ExcelTemplateHelper::createSpreadsheet(getStandardValidData($k1, $k2, $n1, $n2));
// Remove sheet KEDISIPLINAN
$sheetIndex = $ssMissing->getIndex($ssMissing->getSheetByName(ExcelTemplateHelper::SHEET_KEDISIPLINAN));
$ssMissing->removeSheetByIndex($sheetIndex);
ExcelTemplateHelper::saveToFile($ssMissing, $fileMissingSheet);

$res3 = $importService->import($fileMissingSheet, $testPeriodeId, $adminId, 'test_3_missing_sheet.xlsx');
record($results, "3. Sheet wajib hilang ditolak", $res3['success'] === false && $res3['status'] === 'failed');
record($results, "3. Sheet wajib hilang pesan error menyebutkan sheet", str_contains($res3['message'], 'KEDISIPLINAN'));

// ---------------------------------------------------------------------
// 4. Test: Header salah
// ---------------------------------------------------------------------
echo "\n--- Scenario 4: Header salah ---\n";
$fileBadHeader = $fixtureDir . '/test_4_bad_header.xlsx';
$ssBadHeader = ExcelTemplateHelper::createSpreadsheet(getStandardValidData($k1, $k2, $n1, $n2));
$sheetD = $ssBadHeader->getSheetByName(ExcelTemplateHelper::SHEET_KEDISIPLINAN);
$sheetD->setCellValue('D1', 'kehadiran_salah'); // was 'hadir'
ExcelTemplateHelper::saveToFile($ssBadHeader, $fileBadHeader);

$res4 = $importService->import($fileBadHeader, $testPeriodeId, $adminId, 'test_4_bad_header.xlsx');
record($results, "4. Header salah ditolak", $res4['success'] === false && $res4['status'] === 'failed');
record($results, "4. Header salah pesan error menyebutkan header hadir", str_contains($res4['message'], 'hadir'));

// ---------------------------------------------------------------------
// 5. Test: Teknisi tidak ditemukan
// ---------------------------------------------------------------------
echo "\n--- Scenario 5: Teknisi tidak ditemukan ---\n";
$fileBadTech = $fixtureDir . '/test_5_bad_tech.xlsx';
$dataBadTech = getStandardValidData($k1, $k2, $n1, $n2);
$dataBadTech[ExcelTemplateHelper::SHEET_TEKNISI][] = ['T999', 'Teknisi Fiktif'];
$dataBadTech[ExcelTemplateHelper::SHEET_KEDISIPLINAN][] = ['T999', 10, 20, 20, 0, 0, 0, 0, 10, 10];
$ssBadTech = ExcelTemplateHelper::createSpreadsheet($dataBadTech);
ExcelTemplateHelper::saveToFile($ssBadTech, $fileBadTech);

$res5 = $importService->import($fileBadTech, $testPeriodeId, $adminId, 'test_5_bad_tech.xlsx');
$hasTechError = false;
foreach ($res5['errors'] as $err) {
    if (str_contains($err, 'T999')) {
        $hasTechError = true;
        break;
    }
}
record($results, "5. Teknisi tidak terdaftar dicatat error", $hasTechError);

// ---------------------------------------------------------------------
// 6. Test: Bulan invalid (<1 atau >12)
// ---------------------------------------------------------------------
echo "\n--- Scenario 6: Bulan invalid ---\n";
$fileBadMonth = $fixtureDir . '/test_6_bad_month.xlsx';
$dataBadMonth = getStandardValidData($k1, $k2, $n1, $n2);
$dataBadMonth[ExcelTemplateHelper::SHEET_KEDISIPLINAN][0][1] = 13; // bulan 13
$ssBadMonth = ExcelTemplateHelper::createSpreadsheet($dataBadMonth);
ExcelTemplateHelper::saveToFile($ssBadMonth, $fileBadMonth);

$res6 = $importService->import($fileBadMonth, $testPeriodeId, $adminId, 'test_6_bad_month.xlsx');
$hasMonthError = false;
foreach ($res6['errors'] as $err) {
    if (str_contains($err, 'bulan harus berupa angka 1-12') || str_contains($err, 'di luar rentang kuartal')) {
        $hasMonthError = true;
        break;
    }
}
record($results, "6. Bulan invalid (13) dicatat error", $hasMonthError);

// ---------------------------------------------------------------------
// 7. Test: Tanggal di luar periode
// ---------------------------------------------------------------------
echo "\n--- Scenario 7: Tanggal di luar periode ---\n";
$fileOutOfPeriod = $fixtureDir . '/test_7_out_of_period.xlsx';
$dataOutOfPeriod = getStandardValidData($k1, $k2, $n1, $n2);
$dataOutOfPeriod[ExcelTemplateHelper::SHEET_KUALITAS][0][1] = '2026-05-15'; // target is Oct-Dec (2026-10-01 to 2026-12-31)
$dataOutOfPeriod[ExcelTemplateHelper::SHEET_KUALITAS][0][2] = 5;
$ssOutOfPeriod = ExcelTemplateHelper::createSpreadsheet($dataOutOfPeriod);
ExcelTemplateHelper::saveToFile($ssOutOfPeriod, $fileOutOfPeriod);

$res7 = $importService->import($fileOutOfPeriod, $testPeriodeId, $adminId, 'test_7_out_of_period.xlsx');
$hasDateError = false;
foreach ($res7['errors'] as $err) {
    if (str_contains($err, 'di luar rentang periode')) {
        $hasDateError = true;
        break;
    }
}
record($results, "7. Tanggal di luar periode dicatat error", $hasDateError);

// ---------------------------------------------------------------------
// 8. Test: Rating bukan 1-4
// ---------------------------------------------------------------------
echo "\n--- Scenario 8: Rating bukan 1-4 ---\n";
$fileBadRating = $fixtureDir . '/test_8_bad_rating.xlsx';
$dataBadRating = getStandardValidData($k1, $k2, $n1, $n2);
$dataBadRating[ExcelTemplateHelper::SHEET_TANGGUNG_JAWAB][0][2] = 5; // perawatan_alat = 5 (invalid)
$ssBadRating = ExcelTemplateHelper::createSpreadsheet($dataBadRating);
ExcelTemplateHelper::saveToFile($ssBadRating, $fileBadRating);

$res8 = $importService->import($fileBadRating, $testPeriodeId, $adminId, 'test_8_bad_rating.xlsx');
$hasRatingError = false;
foreach ($res8['errors'] as $err) {
    if (str_contains($err, 'perawatan_alat harus bernilai skala 1-4')) {
        $hasRatingError = true;
        break;
    }
}
record($results, "8. Rating di luar 1-4 (5) dicatat error", $hasRatingError);

// ---------------------------------------------------------------------
// 9. Test: Nilai 0/1 kualitas invalid
// ---------------------------------------------------------------------
echo "\n--- Scenario 9: Nilai 0/1 kualitas invalid ---\n";
$fileBadBool = $fixtureDir . '/test_9_bad_bool.xlsx';
$dataBadBool = getStandardValidData($k1, $k2, $n1, $n2);
$dataBadBool[ExcelTemplateHelper::SHEET_KUALITAS][0][4] = 99; // rapi = 99 (invalid)
$ssBadBool = ExcelTemplateHelper::createSpreadsheet($dataBadBool);
ExcelTemplateHelper::saveToFile($ssBadBool, $fileBadBool);

$res9 = $importService->import($fileBadBool, $testPeriodeId, $adminId, 'test_9_bad_bool.xlsx');
$hasBoolError = false;
foreach ($res9['errors'] as $err) {
    if (str_contains($err, 'rapi harus bernilai 1 (Ya) atau 0 (Tidak)')) {
        $hasBoolError = true;
        break;
    }
}
record($results, "9. Nilai kualitas bukan 0/1 dicatat error", $hasBoolError);

// ---------------------------------------------------------------------
// 10. Test: Denominator / data operasional invalid (hadir > total_hari_kerja)
// ---------------------------------------------------------------------
echo "\n--- Scenario 10: Denominator operasional invalid ---\n";
$fileBadDenom = $fixtureDir . '/test_10_bad_denom.xlsx';
$dataBadDenom = getStandardValidData($k1, $k2, $n1, $n2);
$dataBadDenom[ExcelTemplateHelper::SHEET_KEDISIPLINAN][0][2] = 20; // total_hari_kerja
$dataBadDenom[ExcelTemplateHelper::SHEET_KEDISIPLINAN][0][3] = 25; // hadir > total (invalid!)
$ssBadDenom = ExcelTemplateHelper::createSpreadsheet($dataBadDenom);
ExcelTemplateHelper::saveToFile($ssBadDenom, $fileBadDenom);

$res10 = $importService->import($fileBadDenom, $testPeriodeId, $adminId, 'test_10_bad_denom.xlsx');
$hasDenomError = false;
foreach ($res10['errors'] as $err) {
    if (str_contains($err, 'tidak boleh melebihi total_hari_kerja')) {
        $hasDenomError = true;
        break;
    }
}
record($results, "10. hadir > total_hari_kerja dicatat error", $hasDenomError);

// ---------------------------------------------------------------------
// 11. Test: Duplicate monthly data
// ---------------------------------------------------------------------
echo "\n--- Scenario 11: Duplicate monthly data ---\n";
$fileDup = $fixtureDir . '/test_11_duplicate.xlsx';
$dataDup = getStandardValidData($k1, $k2, $n1, $n2);
// Add duplicate row for k1 on month 10
$dataDup[ExcelTemplateHelper::SHEET_KEDISIPLINAN][] = [$k1, 10, 22, 22, 0, 0, 0, 0, 10, 10];
$ssDup = ExcelTemplateHelper::createSpreadsheet($dataDup);
ExcelTemplateHelper::saveToFile($ssDup, $fileDup);

$res11 = $importService->import($fileDup, $testPeriodeId, $adminId, 'test_11_duplicate.xlsx');
$hasDupError = false;
foreach ($res11['errors'] as $err) {
    if (str_contains($err, 'Duplikasi data kedisiplinan')) {
        $hasDupError = true;
        break;
    }
}
record($results, "11. Duplikasi bulan teknisi dicatat error", $hasDupError);

// ---------------------------------------------------------------------
// 12. Test: Import sukses & no calculation/SAW triggered
// ---------------------------------------------------------------------
echo "\n--- Scenario 12: Import sukses & isolasi Calculation/SAW ---\n";
$fileSuccess = $fixtureDir . '/test_12_success.xlsx';
$ssSuccess = ExcelTemplateHelper::createSpreadsheet(getStandardValidData($k1, $k2, $n1, $n2));
ExcelTemplateHelper::saveToFile($ssSuccess, $fileSuccess);

$penilaianBefore = (int) Database::query("SELECT COUNT(*) AS c FROM tb_penilaian WHERE id_periode = {$testPeriodeId}")->fetch()['c'];
$hasilBefore = (int) Database::query("SELECT COUNT(*) AS c FROM tb_hasil WHERE penilaian_id IN (SELECT id FROM tb_penilaian WHERE id_periode = {$testPeriodeId})")->fetch()['c'];

$res12 = $importService->import($fileSuccess, $testPeriodeId, $adminId, 'test_12_success.xlsx');
record($results, "12. Import sukses status success", $res12['success'] === true && $res12['status'] === 'success');
record($results, "12. Import sukses data_berhasil > 0", $res12['data_berhasil'] === 20 && $res12['data_gagal'] === 0);

// Check database operational tables
$kediCount = (int) Database::query("SELECT COUNT(*) AS c FROM tb_kedisiplinan WHERE id_periode = {$testPeriodeId}")->fetch()['c'];
$pekCount = (int) Database::query("SELECT COUNT(*) AS c FROM tb_pekerjaan WHERE id_periode = {$testPeriodeId}")->fetch()['c'];
$tjCount = (int) Database::query("SELECT COUNT(*) AS c FROM tb_tanggung_jawab WHERE id_periode = {$testPeriodeId}")->fetch()['c'];
record($results, "12. Operational data stored in tb_kedisiplinan (6 rows)", $kediCount === 6);
record($results, "12. Operational data stored in tb_pekerjaan (6 rows)", $pekCount === 6);
record($results, "12. Operational data stored in tb_tanggung_jawab (6 rows)", $tjCount === 6);

// Verify NO C1/C2/C3 calculation or SAW triggered by importer
$penilaianAfter = (int) Database::query("SELECT COUNT(*) AS c FROM tb_penilaian WHERE id_periode = {$testPeriodeId}")->fetch()['c'];
$hasilAfter = (int) Database::query("SELECT COUNT(*) AS c FROM tb_hasil WHERE penilaian_id IN (SELECT id FROM tb_penilaian WHERE id_periode = {$testPeriodeId})")->fetch()['c'];
record($results, "12. Importer strictly DOES NOT calculate C1/C2/C3 (tb_penilaian untouched)", $penilaianAfter === $penilaianBefore);
record($results, "12. Importer strictly DOES NOT run SAW or ranking (tb_hasil untouched)", $hasilAfter === $hasilBefore);

// ---------------------------------------------------------------------
// 13. Test: Partial import
// ---------------------------------------------------------------------
echo "\n--- Scenario 13: Partial import ---\n";
$filePartial = $fixtureDir . '/test_13_partial.xlsx';
$dataPartial = getStandardValidData($k1, $k2, $n1, $n2);
// Make 1 row invalid in KEDISIPLINAN (hadir > total)
$dataPartial[ExcelTemplateHelper::SHEET_KEDISIPLINAN][0][3] = 99; // hadir = 99 > 22
$ssPartial = ExcelTemplateHelper::createSpreadsheet($dataPartial);
ExcelTemplateHelper::saveToFile($ssPartial, $filePartial);

$res13 = $importService->import($filePartial, $testPeriodeId, $adminId, 'test_13_partial.xlsx', allowPartial: true);
record($results, "13. Partial import status partial", $res13['success'] === true && $res13['status'] === 'partial');
record($results, "13. Partial import counts valid and invalid rows", $res13['data_berhasil'] === 19 && $res13['data_gagal'] === 1);
record($results, "13. Partial import logs rejected row error", count($res13['errors']) === 1);

// ---------------------------------------------------------------------
// 14. Test: Import gagal (strict mode with errors, or 100% invalid)
// ---------------------------------------------------------------------
echo "\n--- Scenario 14: Import gagal ---\n";
// Using the file with invalid row from test 13 in strict mode (allowPartial = false)
$res14 = $importService->import($filePartial, $testPeriodeId, $adminId, 'test_13_partial.xlsx', allowPartial: false);
record($results, "14. Strict mode with errors returns failed", $res14['success'] === false && $res14['status'] === 'failed');
record($results, "14. Strict mode data_berhasil = 0", $res14['data_berhasil'] === 0);

// ---------------------------------------------------------------------
// 15. Test: Transaksi rollback on fatal failure
// ---------------------------------------------------------------------
echo "\n--- Scenario 15: Transaksi rollback ---\n";
// If allowPartial = false, any row error should prevent partial write and roll back
$kedisiplinanBefore = (int) Database::query("SELECT COUNT(*) AS c FROM tb_kedisiplinan WHERE id_periode = {$testPeriodeId}")->fetch()['c'];
$fileFailAll = $fixtureDir . '/test_15_fail.xlsx';
$dataFail = [
    ExcelTemplateHelper::SHEET_TEKNISI => [['T999', 'Fake']],
    ExcelTemplateHelper::SHEET_KEDISIPLINAN => [['T999', 10, 20, 30, 0, 0, 0, 0, 10, 10]], // bad tech + bad denom
    ExcelTemplateHelper::SHEET_KUALITAS => [['T999', '2026-10-01', 10, 'Fake', 1, 1, 1]],
    ExcelTemplateHelper::SHEET_TANGGUNG_JAWAB => [['T999', 10, 5, 5, 5, 5]], // bad tech + bad rating
];
$ssFail = ExcelTemplateHelper::createSpreadsheet($dataFail);
ExcelTemplateHelper::saveToFile($ssFail, $fileFailAll);

$res15 = $importService->import($fileFailAll, $testPeriodeId, $adminId, 'test_15_fail.xlsx', allowPartial: false);
record($results, "15. All-invalid file import returns failed", $res15['success'] === false && $res15['status'] === 'failed');
$kedisiplinanAfter = (int) Database::query("SELECT COUNT(*) AS c FROM tb_kedisiplinan WHERE id_periode = {$testPeriodeId}")->fetch()['c'];
record($results, "15. Transaksi rollback: tidak ada data korup yang masuk", $kedisiplinanAfter === $kedisiplinanBefore);

// ---------------------------------------------------------------------
// 16. Test: tb_import tercatat
// ---------------------------------------------------------------------
echo "\n--- Scenario 16: tb_import tercatat ---\n";
$latestImport = Database::query("SELECT * FROM tb_import WHERE id_periode = {$testPeriodeId} ORDER BY id_import DESC LIMIT 1")->fetch();
record($results, "16. tb_import record exists", $latestImport !== false);
record($results, "16. tb_import has id_periode", (int) $latestImport['id_periode'] === $testPeriodeId);
record($results, "16. tb_import has nama_file_asli", !empty($latestImport['nama_file_asli']));
record($results, "16. tb_import has id_user matching admin", (int) $latestImport['id_user'] === $adminId);
record($results, "16. tb_import has valid status", in_array($latestImport['status'], ['success', 'partial', 'failed'], true));

// ---------------------------------------------------------------------
// 17. Test: Otorisasi Admin & Proteksi Periode Legacy
// ---------------------------------------------------------------------
echo "\n--- Scenario 17: Otorisasi Admin & Proteksi Periode Legacy ---\n";
// Non-admin attempt (Owner)
$res17Owner = $importService->import($fileValid, $testPeriodeId, $ownerId, 'test_1_valid.xlsx');
record($results, "17. Non-admin (Owner) ditolak", $res17Owner['success'] === false);
record($results, "17. Pesan penolakan non-admin jelas", str_contains($res17Owner['message'], 'Hanya Admin'));

// Import to legacy period attempt
if ($legacyPeriodeId > 0) {
    $res17Legacy = $importService->import($fileValid, $legacyPeriodeId, $adminId, 'test_1_valid.xlsx');
    record($results, "17. Import ke periode legacy ditolak", $res17Legacy['success'] === false);
    record($results, "17. Pesan penolakan periode legacy jelas", str_contains($res17Legacy['message'], 'legacy'));
} else {
    record($results, "17. Import ke periode legacy ditolak", true, "Skipped: no legacy period in database");
}

// ---------------------------------------------------------------------
// Teardown Test Data & Verification of Legacy Data
// ---------------------------------------------------------------------
echo "\n--- Teardown & Legacy Data Verification ---\n";
// Clean test period operational records and logs
Database::query("DELETE FROM tb_kedisiplinan WHERE id_periode = {$testPeriodeId}");
Database::query("DELETE FROM tb_pekerjaan WHERE id_periode = {$testPeriodeId}");
Database::query("DELETE FROM tb_tanggung_jawab WHERE id_periode = {$testPeriodeId}");
Database::query("DELETE FROM tb_import WHERE id_periode = {$testPeriodeId}");
Database::query("DELETE FROM tb_periode_penilaian WHERE id_periode = {$testPeriodeId}");

// Clean up fixture files
$testFiles = glob($fixtureDir . '/*');
if ($testFiles) {
    foreach ($testFiles as $f) {
        if (is_file($f)) {
            unlink($f);
        }
    }
}
@rmdir($fixtureDir);

// Verify legacy data untouched
$finalLegacyPenilaianCount = (int) Database::query("SELECT COUNT(*) AS c FROM tb_penilaian WHERE status_data = 'legacy'")->fetch()['c'];
$finalLegacyHasilCount = (int) Database::query("SELECT COUNT(*) AS c FROM tb_hasil")->fetch()['c'];

record($results, "Legacy tb_penilaian data intact (12 records)", $finalLegacyPenilaianCount === $initialLegacyPenilaianCount);
record($results, "Legacy tb_hasil data intact (12 records)", $finalLegacyHasilCount === $initialLegacyHasilCount);

// ---------------------------------------------------------------------
// Summary
// ---------------------------------------------------------------------
$total = count($results);
$passed = count(array_filter($results, static fn($r) => $r['ok']));
echo "\n========================================\n";
echo "EXCEL IMPORTER TEST SUMMARY: {$passed}/{$total} passed\n";
echo "========================================\n";

if ($passed < $total) {
    exit(1);
}
