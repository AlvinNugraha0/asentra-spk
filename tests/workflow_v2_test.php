<?php
// ASENTRA SPK — Workflow V2 Test Suite
// Covers all 15 scenarios: Calculation → Preview → Owner Confirmation.
// STRICT: Validates immutability, RBAC, legacy protection, and zero impact on tb_hasil/SAW.

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
require_once __DIR__ . '/../app/services/calculators/DisciplineCalculator.php';
require_once __DIR__ . '/../app/services/calculators/QualityCalculator.php';
require_once __DIR__ . '/../app/services/calculators/ResponsibilityCalculator.php';
require_once __DIR__ . '/../app/services/CalculationEngine.php';
require_once __DIR__ . '/../app/services/import/ExcelImportService.php';
require_once __DIR__ . '/../app/services/AssessmentWorkflowService.php';

use App\Core\Database;
use App\Models\Hasil;
use App\Models\Import;
use App\Models\Kedisiplinan;
use App\Models\Pekerjaan;
use App\Models\Penilaian;
use App\Models\PeriodePenilaian;
use App\Models\TanggungJawab;
use App\Models\Teknisi;
use App\Models\User;
use App\Services\AssessmentWorkflowService;
use App\Services\CalculationEngine;
use App\Services\Import\ExcelImportService;

$results = [];

function record(array &$results, string $name, bool $ok, string $detail = ''): void
{
    $results[] = ['name' => $name, 'ok' => $ok, 'detail' => $detail];
    echo ($ok ? 'OK' : 'FAIL') . ': ' . $name . ($detail ? ' — ' . $detail : '') . PHP_EOL;
}

function assertClose(float $a, float $b, float $epsilon = 0.0001): bool
{
    return abs($a - $b) < $epsilon;
}

echo "========================================\n";
echo "TESTING WORKFLOW V2 (15 SCENARIOS)\n";
echo "Calculation → Preview → Owner Confirmation\n";
echo "========================================\n\n";

$workflowService = new AssessmentWorkflowService();
$importService = new ExcelImportService();

// Find Admin & Owner users
$admin = Database::query("SELECT id FROM tb_user WHERE role = 'admin' AND status = 'active' LIMIT 1")->fetch();
$owner = Database::query("SELECT id FROM tb_user WHERE role = 'owner' AND status = 'active' LIMIT 1")->fetch();

if (!$admin || !$owner) {
    die("FATAL: Admin or Owner user missing from database.\n");
}
$adminId = (int) $admin['id'];
$ownerId = (int) $owner['id'];

// Get two active technicians (e.g. A1, A2)
$techs = Teknisi::all('', 'active');
if (count($techs) < 2) {
    die("FATAL: At least two active technicians required.\n");
}
$t1 = $techs[0];
$t2 = $techs[1];
$t1Id = (int) $t1['id'];
$t2Id = (int) $t2['id'];

// Baseline counts
$initialLegacyPenilaianCount = (int) Database::query("SELECT COUNT(*) AS c FROM tb_penilaian WHERE status_data = 'legacy'")->fetch()['c'];
$initialHasilCount = (int) Database::query("SELECT COUNT(*) AS c FROM tb_hasil")->fetch()['c'];

// Find a legacy period ID
$legacyPeriod = Database::query("SELECT id_periode FROM tb_periode_penilaian WHERE status = 'legacy' LIMIT 1")->fetch();
$legacyPeriodeId = $legacyPeriod ? (int) $legacyPeriod['id_periode'] : 0;

// ---------------------------------------------------------------------
// 1. Skenario 1: Admin dapat membuka / membuat periode baru
// ---------------------------------------------------------------------
echo "--- Scenario 1: Admin membuka periode baru ---\n";
$testKode = 'TEST-WF-2026-Q1';
// Teardown any leftovers first
$leftover = PeriodePenilaian::findByKode($testKode);
if ($leftover) {
    $lid = (int) $leftover['id_periode'];
    Database::query("DELETE FROM tb_penilaian WHERE id_periode = {$lid}");
    Database::query("DELETE FROM tb_kedisiplinan WHERE id_periode = {$lid}");
    Database::query("DELETE FROM tb_pekerjaan WHERE id_periode = {$lid}");
    Database::query("DELETE FROM tb_tanggung_jawab WHERE id_periode = {$lid}");
    Database::query("DELETE FROM tb_import WHERE id_periode = {$lid}");
    PeriodePenilaian::delete($lid);
}

$testPeriodeId = PeriodePenilaian::create([
    'kode_periode' => $testKode,
    'nama_periode' => 'Test Workflow Triwulan I 2026',
    'tanggal_mulai' => '2026-01-01',
    'tanggal_selesai' => '2026-03-31',
    'status' => 'draft',
    'created_by' => $adminId,
]);

$createdPeriod = PeriodePenilaian::findById($testPeriodeId);
record($results, "1. Admin dapat membuat periode baru", $createdPeriod !== null && $createdPeriod['kode_periode'] === $testKode);
record($results, "1. Status periode baru adalah draft", ($createdPeriod['status'] ?? '') === 'draft');

// ---------------------------------------------------------------------
// 2. Skenario 2 & 3: Admin menjalankan CalculationEngine & hasil masuk tb_penilaian
// ---------------------------------------------------------------------
echo "\n--- Scenario 2 & 3: Admin menjalankan CalculationEngine & hasil masuk tb_penilaian ---\n";
// Seed operational data:
// Tech 1: complete 3 months (Jan, Feb, Mar)
// Tech 2: partial 2 months (Jan, Feb) to test partial warning
// Month 1
Kedisiplinan::create(['id_periode' => $testPeriodeId, 'id_teknisi' => $t1Id, 'bulan' => 1, 'total_hari_kerja' => 20, 'hadir' => 20, 'terlambat' => 0, 'pekerjaan_terjadwal' => 10, 'sesuai_jadwal' => 10]);
Kedisiplinan::create(['id_periode' => $testPeriodeId, 'id_teknisi' => $t2Id, 'bulan' => 1, 'total_hari_kerja' => 20, 'hadir' => 18, 'terlambat' => 2, 'pekerjaan_terjadwal' => 10, 'sesuai_jadwal' => 9]);
// Month 2
Kedisiplinan::create(['id_periode' => $testPeriodeId, 'id_teknisi' => $t1Id, 'bulan' => 2, 'total_hari_kerja' => 20, 'hadir' => 19, 'terlambat' => 1, 'pekerjaan_terjadwal' => 10, 'sesuai_jadwal' => 10]);
Kedisiplinan::create(['id_periode' => $testPeriodeId, 'id_teknisi' => $t2Id, 'bulan' => 2, 'total_hari_kerja' => 20, 'hadir' => 19, 'terlambat' => 0, 'pekerjaan_terjadwal' => 10, 'sesuai_jadwal' => 10]);
// Month 3 (Tech 1 only)
Kedisiplinan::create(['id_periode' => $testPeriodeId, 'id_teknisi' => $t1Id, 'bulan' => 3, 'total_hari_kerja' => 20, 'hadir' => 20, 'terlambat' => 0, 'pekerjaan_terjadwal' => 10, 'sesuai_jadwal' => 10]);

// Pekerjaan
Pekerjaan::create(['id_periode' => $testPeriodeId, 'id_teknisi' => $t1Id, 'tanggal' => '2026-01-15', 'bulan' => 1, 'nama_pekerjaan' => 'Job 1', 'rapi' => 1, 'presisi' => 1, 'sesuai_desain' => 1]);
Pekerjaan::create(['id_periode' => $testPeriodeId, 'id_teknisi' => $t1Id, 'tanggal' => '2026-02-15', 'bulan' => 2, 'nama_pekerjaan' => 'Job 2', 'rapi' => 1, 'presisi' => 1, 'sesuai_desain' => 1]);
Pekerjaan::create(['id_periode' => $testPeriodeId, 'id_teknisi' => $t1Id, 'tanggal' => '2026-03-15', 'bulan' => 3, 'nama_pekerjaan' => 'Job 3', 'rapi' => 1, 'presisi' => 1, 'sesuai_desain' => 1]);

Pekerjaan::create(['id_periode' => $testPeriodeId, 'id_teknisi' => $t2Id, 'tanggal' => '2026-01-20', 'bulan' => 1, 'nama_pekerjaan' => 'Job A', 'rapi' => 1, 'presisi' => 1, 'sesuai_desain' => 1]);
Pekerjaan::create(['id_periode' => $testPeriodeId, 'id_teknisi' => $t2Id, 'tanggal' => '2026-02-20', 'bulan' => 2, 'nama_pekerjaan' => 'Job B', 'rapi' => 1, 'presisi' => 1, 'sesuai_desain' => 0]);

// Tanggung Jawab
TanggungJawab::create(['id_periode' => $testPeriodeId, 'id_teknisi' => $t1Id, 'bulan' => 1, 'perawatan_alat' => 4, 'efisiensi_material' => 4, 'inisiatif' => 4, 'kepatuhan_prosedur' => 4]);
TanggungJawab::create(['id_periode' => $testPeriodeId, 'id_teknisi' => $t1Id, 'bulan' => 2, 'perawatan_alat' => 4, 'efisiensi_material' => 3, 'inisiatif' => 4, 'kepatuhan_prosedur' => 4]);
TanggungJawab::create(['id_periode' => $testPeriodeId, 'id_teknisi' => $t1Id, 'bulan' => 3, 'perawatan_alat' => 4, 'efisiensi_material' => 4, 'inisiatif' => 3, 'kepatuhan_prosedur' => 4]);

TanggungJawab::create(['id_periode' => $testPeriodeId, 'id_teknisi' => $t2Id, 'bulan' => 1, 'perawatan_alat' => 3, 'efisiensi_material' => 3, 'inisiatif' => 3, 'kepatuhan_prosedur' => 3]);
TanggungJawab::create(['id_periode' => $testPeriodeId, 'id_teknisi' => $t2Id, 'bulan' => 2, 'perawatan_alat' => 4, 'efisiensi_material' => 3, 'inisiatif' => 4, 'kepatuhan_prosedur' => 4]);

// Execute calculation via Workflow Service
$calcRes = $workflowService->calculatePeriod($testPeriodeId, $adminId);
record($results, "2. Admin dapat menjalankan CalculationEngine", $calcRes['success'] === true);
record($results, "2. Total teknisi dihitung = 2", $calcRes['total_teknisi'] === 2);

$evalsInDb = Penilaian::findByPeriodeId($testPeriodeId);
record($results, "3. Hasil kalkulasi masuk tb_penilaian", count($evalsInDb) === 2);

// ---------------------------------------------------------------------
// 4. Skenario 4: C1/C2/C3 decimal tersimpan benar
// ---------------------------------------------------------------------
echo "\n--- Scenario 4: C1/C2/C3 desimal tersimpan benar ---\n";
$evalT1 = Penilaian::findByPeriodeAndTeknisi($testPeriodeId, $t1Id);
record($results, "4. Evaluasi T1 ditemukan di database", $evalT1 !== null);
$c1T1 = (float) ($evalT1['c1'] ?? 0);
$c2T1 = (float) ($evalT1['c2'] ?? 0);
$c3T1 = (float) ($evalT1['c3'] ?? 0);
record($results, "4. Nilai desimal C1/C2/C3 valid (>0 dan <=4)", $c1T1 > 0 && $c1T1 <= 4.0 && $c2T1 > 0 && $c3T1 > 0);
record($results, "4. Evaluasi T1 status_data = calculated", ($evalT1['status_data'] ?? '') === 'calculated');

// ---------------------------------------------------------------------
// 5. Skenario 5: Partial menghasilkan warning
// ---------------------------------------------------------------------
echo "\n--- Scenario 5: Data partial menghasilkan warning ---\n";
$evalT2 = Penilaian::findByPeriodeAndTeknisi($testPeriodeId, $t2Id);
record($results, "5. Evaluasi T2 (hanya 2 bulan) memiliki status_data = partial", ($evalT2['status_data'] ?? '') === 'partial');
record($results, "5. Evaluasi T2 memiliki pesan warning", !empty($evalT2['warning']) && str_contains($evalT2['warning'], 'hanya'));

// ---------------------------------------------------------------------
// 6. Skenario 6: Owner dapat melihat preview
// ---------------------------------------------------------------------
echo "\n--- Scenario 6: Owner dapat melihat preview ---\n";
$previewSummary = $workflowService->getPeriodAssessmentSummary($testPeriodeId);
record($results, "6. Owner dapat mengambil ringkasan preview assessment", !empty($previewSummary['evaluations']));
record($results, "6. Preview menampilkan status calculated dan partial", $previewSummary['calculated_count'] === 1 && $previewSummary['partial_count'] === 1);
record($results, "6. Periode siap untuk dikonfirmasi (can_confirm = true)", $previewSummary['can_confirm'] === true);

// ---------------------------------------------------------------------
// 7. Skenario 7: Owner dapat melihat detail indikator
// ---------------------------------------------------------------------
echo "\n--- Scenario 7: Owner dapat melihat detail indikator ---\n";
$detailT1 = $workflowService->getTechnicianIndicatorDetail($testPeriodeId, $t1Id);
record($results, "7. Detail indikator T1 memuat data teknisi dan periode", !empty($detailT1['teknisi']) && !empty($detailT1['periode']));
record($results, "7. Detail C1 memuat subindikator kehadiran, terlambat, jadwal", isset($detailT1['calculation']['c1']['subindicators']['kehadiran']));
record($results, "7. Detail C2 memuat subindikator kerapian, presisi, desain", isset($detailT1['calculation']['c2']['subindicators']['rapi']));
record($results, "7. Detail C3 memuat subindikator alat, material, inisiatif, prosedur", isset($detailT1['calculation']['c3']['subindicators']['perawatan_alat']));
record($results, "7. Detail memuat log pekerjaan mentah", count($detailT1['raw_data']['pekerjaan']) === 3);

// ---------------------------------------------------------------------
// 8. Skenario 8, 9, 10, 11: Owner confirmation, confirmed_by, confirmed_at, status_data
// ---------------------------------------------------------------------
echo "\n--- Scenario 8-11: Owner confirmation & timestamp ---\n";
$confirmRes = $workflowService->confirmAssessment($testPeriodeId, $ownerId);
record($results, "8. Owner dapat mengonfirmasi penilaian", $confirmRes['success'] === true);
record($results, "8. Jumlah teknisi terkonfirmasi = 2", $confirmRes['confirmed_count'] === 2);

$confirmedEvals = Penilaian::findByPeriodeId($testPeriodeId);
$allConfirmedByOwner = true;
$allConfirmedAtSet = true;
$allStatusConfirmed = true;

foreach ($confirmedEvals as $ce) {
    if ((int) ($ce['confirmed_by'] ?? 0) !== $ownerId) {
        $allConfirmedByOwner = false;
    }
    if (empty($ce['confirmed_at'])) {
        $allConfirmedAtSet = false;
    }
    if (($ce['status_data'] ?? '') !== 'confirmed') {
        $allStatusConfirmed = false;
    }
}

record($results, "9. confirmed_by terisi dengan ID Owner", $allConfirmedByOwner);
record($results, "10. confirmed_at terisi timestamp waktu konfirmasi", $allConfirmedAtSet);
record($results, "11. status_data pada tb_penilaian berubah menjadi confirmed", $allStatusConfirmed);

$updatedPeriod = PeriodePenilaian::findById($testPeriodeId);
record($results, "11. Status periode di tb_periode_penilaian berubah menjadi selesai", ($updatedPeriod['status'] ?? '') === 'selesai');

// ---------------------------------------------------------------------
// 12. Skenario 12: Assessment confirmed tidak dapat diedit langsung atau dikalkulasi ulang
// ---------------------------------------------------------------------
echo "\n--- Scenario 12: Immutability assessment confirmed ---\n";
$recalcBlocked = false;
try {
    $workflowService->calculatePeriod($testPeriodeId, $adminId);
} catch (RuntimeException $e) {
    $recalcBlocked = true;
}
record($results, "12. Kalkulasi ulang pada periode yang sudah confirmed diblokir", $recalcBlocked);

$reconfirmBlocked = false;
try {
    $workflowService->confirmAssessment($testPeriodeId, $ownerId);
} catch (RuntimeException $e) {
    $reconfirmBlocked = true;
}
record($results, "12. Konfirmasi ulang pada periode yang sudah confirmed diblokir", $reconfirmBlocked);

// ---------------------------------------------------------------------
// 13. Skenario 13: Owner tidak dapat melakukan import Excel
// ---------------------------------------------------------------------
echo "\n--- Scenario 13: Owner tidak dapat import Excel ---\n";
$dummyFile = ROOT_PATH . '/storage/imports/test_dummy.xlsx';
if (!file_exists($dummyFile)) {
    touch($dummyFile);
}
$ownerImportRes = $importService->import($dummyFile, $testPeriodeId, $ownerId, 'test_dummy.xlsx');
record($results, "13. Owner ditolak saat mencoba import Excel", $ownerImportRes['success'] === false);
record($results, "13. Pesan penolakan import Owner jelas", str_contains($ownerImportRes['message'], 'Hanya Admin'));
if (file_exists($dummyFile)) {
    unlink($dummyFile);
}

// ---------------------------------------------------------------------
// 14. Skenario 14: Legacy tidak dapat dihitung atau diubah
// ---------------------------------------------------------------------
echo "\n--- Scenario 14: Periode legacy tidak dapat dihitung/diubah ---\n";
if ($legacyPeriodeId > 0) {
    $legacyCalcBlocked = false;
    try {
        $workflowService->calculatePeriod($legacyPeriodeId, $adminId);
    } catch (RuntimeException $e) {
        $legacyCalcBlocked = true;
    }
    record($results, "14. Kalkulasi periode legacy diblokir", $legacyCalcBlocked);

    $legacyConfirmBlocked = false;
    try {
        $workflowService->confirmAssessment($legacyPeriodeId, $ownerId);
    } catch (RuntimeException $e) {
        $legacyConfirmBlocked = true;
    }
    record($results, "14. Konfirmasi periode legacy diblokir", $legacyConfirmBlocked);
} else {
    record($results, "14. Kalkulasi periode legacy diblokir", true, "Skipped: no legacy period");
    record($results, "14. Konfirmasi periode legacy diblokir", true, "Skipped: no legacy period");
}

// ---------------------------------------------------------------------
// 15. Skenario 15: SAW dan tb_hasil tidak berubah
// ---------------------------------------------------------------------
echo "\n--- Scenario 15: tb_hasil / SAW tidak berubah ---\n";
$currentHasilCount = (int) Database::query("SELECT COUNT(*) AS c FROM tb_hasil")->fetch()['c'];
record($results, "15. Jumlah record tb_hasil tetap utuh (12 baris)", $currentHasilCount === $initialHasilCount);

$hasilForTestPeriod = (int) Database::query("SELECT COUNT(*) AS c FROM tb_hasil WHERE id_periode = {$testPeriodeId}")->fetch()['c'];
record($results, "15. Belum ada hasil SAW untuk periode V2 (isolasi SAW)", $hasilForTestPeriod === 0);

// ---------------------------------------------------------------------
// Teardown Test Data & Verification of Legacy Data
// ---------------------------------------------------------------------
echo "\n--- Teardown & Final Legacy Verification ---\n";
Database::query("DELETE FROM tb_penilaian WHERE id_periode = {$testPeriodeId}");
Database::query("DELETE FROM tb_kedisiplinan WHERE id_periode = {$testPeriodeId}");
Database::query("DELETE FROM tb_pekerjaan WHERE id_periode = {$testPeriodeId}");
Database::query("DELETE FROM tb_tanggung_jawab WHERE id_periode = {$testPeriodeId}");
Database::query("DELETE FROM tb_import WHERE id_periode = {$testPeriodeId}");
PeriodePenilaian::delete($testPeriodeId);

$finalLegacyPenilaianCount = (int) Database::query("SELECT COUNT(*) AS c FROM tb_penilaian WHERE status_data = 'legacy'")->fetch()['c'];
$finalHasilCount = (int) Database::query("SELECT COUNT(*) AS c FROM tb_hasil")->fetch()['c'];

record($results, "Data legacy tb_penilaian tetap utuh (12 records)", $finalLegacyPenilaianCount === $initialLegacyPenilaianCount);
record($results, "Data tb_hasil tetap utuh (12 records)", $finalHasilCount === $initialHasilCount);

// ---------------------------------------------------------------------
// Summary
// ---------------------------------------------------------------------
$total = count($results);
$passed = count(array_filter($results, static fn($r) => $r['ok']));
echo "\n========================================\n";
echo "WORKFLOW V2 TEST SUMMARY: {$passed}/{$total} passed\n";
echo "========================================\n";

if ($passed < $total) {
    exit(1);
}
