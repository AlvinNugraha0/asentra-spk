<?php
// ASENTRA SPK — Operational Calculation Engine Unit & Integration Tests

declare(strict_types=1);

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/helpers/format.php';
require_once __DIR__ . '/../app/models/PeriodePenilaian.php';
require_once __DIR__ . '/../app/models/Teknisi.php';
require_once __DIR__ . '/../app/models/Kedisiplinan.php';
require_once __DIR__ . '/../app/models/Pekerjaan.php';
require_once __DIR__ . '/../app/models/TanggungJawab.php';
require_once __DIR__ . '/../app/models/Penilaian.php';
require_once __DIR__ . '/../app/services/calculators/DisciplineCalculator.php';
require_once __DIR__ . '/../app/services/calculators/QualityCalculator.php';
require_once __DIR__ . '/../app/services/calculators/ResponsibilityCalculator.php';
require_once __DIR__ . '/../app/services/CalculationEngine.php';

use App\Core\Database;
use App\Models\Kedisiplinan;
use App\Models\Pekerjaan;
use App\Models\Penilaian;
use App\Models\PeriodePenilaian;
use App\Models\TanggungJawab;
use App\Services\CalculationEngine;
use App\Services\Calculators\DisciplineCalculator;
use App\Services\Calculators\QualityCalculator;
use App\Services\Calculators\ResponsibilityCalculator;

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
echo "TESTING OPERATIONAL CALCULATION ENGINE V2\n";
echo "========================================\n\n";

// ============================================================
// 1. C1 KEDISIPLINAN THRESHOLD TESTS
// ============================================================
echo "--- 1. C1 Kedisiplinan Thresholds ---\n";

// 1.1 C1.1 Kehadiran
record($results, 'C1.1 Kehadiran 100% = 4', DisciplineCalculator::rateKehadiran(100.0) === 4);
record($results, 'C1.1 Kehadiran 95% = 4', DisciplineCalculator::rateKehadiran(95.0) === 4);
record($results, 'C1.1 Kehadiran 94.99% = 3', DisciplineCalculator::rateKehadiran(94.99) === 3);
record($results, 'C1.1 Kehadiran 85% = 3', DisciplineCalculator::rateKehadiran(85.0) === 3);
record($results, 'C1.1 Kehadiran 84.99% = 2', DisciplineCalculator::rateKehadiran(84.99) === 2);
record($results, 'C1.1 Kehadiran 75% = 2', DisciplineCalculator::rateKehadiran(75.0) === 2);
record($results, 'C1.1 Kehadiran 74.99% = 1', DisciplineCalculator::rateKehadiran(74.99) === 1);
record($results, 'C1.1 Kehadiran 0% = 1', DisciplineCalculator::rateKehadiran(0.0) === 1);

// 1.2 C1.2 Ketepatan Waktu (terlambat count)
record($results, 'C1.2 Terlambat 0 = 4', DisciplineCalculator::rateKetepatanWaktu(0) === 4);
record($results, 'C1.2 Terlambat 2 = 4', DisciplineCalculator::rateKetepatanWaktu(2) === 4);
record($results, 'C1.2 Terlambat 3 = 3', DisciplineCalculator::rateKetepatanWaktu(3) === 3);
record($results, 'C1.2 Terlambat 5 = 3', DisciplineCalculator::rateKetepatanWaktu(5) === 3);
record($results, 'C1.2 Terlambat 6 = 2', DisciplineCalculator::rateKetepatanWaktu(6) === 2);
record($results, 'C1.2 Terlambat 8 = 2', DisciplineCalculator::rateKetepatanWaktu(8) === 2);
record($results, 'C1.2 Terlambat 9 = 1', DisciplineCalculator::rateKetepatanWaktu(9) === 1);
record($results, 'C1.2 Terlambat 15 = 1', DisciplineCalculator::rateKetepatanWaktu(15) === 1);

// 1.3 C1.3 Kepatuhan Jadwal
record($results, 'C1.3 Jadwal 95% = 4', DisciplineCalculator::rateKepatuhanJadwal(95.0) === 4);
record($results, 'C1.3 Jadwal 94.99% = 3', DisciplineCalculator::rateKepatuhanJadwal(94.99) === 3);
record($results, 'C1.3 Jadwal 80% = 3', DisciplineCalculator::rateKepatuhanJadwal(80.0) === 3);
record($results, 'C1.3 Jadwal 79.99% = 2', DisciplineCalculator::rateKepatuhanJadwal(79.99) === 2);
record($results, 'C1.3 Jadwal 65% = 2', DisciplineCalculator::rateKepatuhanJadwal(65.0) === 2);
record($results, 'C1.3 Jadwal 64.99% = 1', DisciplineCalculator::rateKepatuhanJadwal(64.99) === 1);
record($results, 'C1.3 Jadwal 50% = 1', DisciplineCalculator::rateKepatuhanJadwal(50.0) === 1);

// 1.4 Denominator safety: total_hari_kerja = 0 or pekerjaan_terjadwal = 0
$c1ZeroHari = DisciplineCalculator::calculateMonthly([
    'total_hari_kerja' => 0,
    'hadir' => 0,
    'terlambat' => 0,
    'pekerjaan_terjadwal' => 10,
    'sesuai_jadwal' => 10,
]);
record($results, 'C1 denominator 0 (hari kerja) returns no_data', $c1ZeroHari['status'] === 'no_data' && $c1ZeroHari['value'] === null);

$c1ZeroJadwal = DisciplineCalculator::calculateMonthly([
    'total_hari_kerja' => 20,
    'hadir' => 20,
    'terlambat' => 0,
    'pekerjaan_terjadwal' => 0,
    'sesuai_jadwal' => 0,
]);
record($results, 'C1 denominator 0 (jadwal) returns no_data', $c1ZeroJadwal['status'] === 'no_data' && $c1ZeroJadwal['value'] === null);

// 1.5 C1 Monthly calculation with full decimals
$c1Valid = DisciplineCalculator::calculateMonthly([
    'total_hari_kerja' => 20,
    'hadir' => 19, // 95% -> 4
    'terlambat' => 3, // 3 -> 3
    'pekerjaan_terjadwal' => 20,
    'sesuai_jadwal' => 19, // 95% -> 4
]);
// (4 + 3 + 4) / 3 = 11 / 3 = 3.666667
record($results, 'C1 monthly decimal (4+3+4)/3 = 3.666667', $c1Valid['status'] === 'valid' && assertClose($c1Valid['value'], 11 / 3));

// ============================================================
// 2. C2 KUALITAS HASIL KERJA THRESHOLD TESTS
// ============================================================
echo "\n--- 2. C2 Kualitas Hasil Kerja Thresholds ---\n";

record($results, 'C2 90% = 4', QualityCalculator::rateQualityIndicator(90.0) === 4);
record($results, 'C2 89.99% = 3', QualityCalculator::rateQualityIndicator(89.99) === 3);
record($results, 'C2 75% = 3', QualityCalculator::rateQualityIndicator(75.0) === 3);
record($results, 'C2 74.99% = 2', QualityCalculator::rateQualityIndicator(74.99) === 2);
record($results, 'C2 60% = 2', QualityCalculator::rateQualityIndicator(60.0) === 2);
record($results, 'C2 59.99% = 1', QualityCalculator::rateQualityIndicator(59.99) === 1);
record($results, 'C2 0% = 1', QualityCalculator::rateQualityIndicator(0.0) === 1);

// C2 Zero jobs safety check
$c2Empty = QualityCalculator::calculateMonthly([]);
record($results, 'C2 0 jobs returns no_data (not 0)', $c2Empty['status'] === 'no_data' && $c2Empty['value'] === null);

// C2 Monthly calculation from jobs list
// 10 jobs: 9 rapi (90%->4), 8 presisi (80%->3), 7 sesuai_desain (70%->2) -> (4+3+2)/3 = 3.0
$sampleJobs = [];
for ($i = 0; $i < 10; $i++) {
    $sampleJobs[] = [
        'rapi' => $i < 9 ? 1 : 0,
        'presisi' => $i < 8 ? 1 : 0,
        'sesuai_desain' => $i < 7 ? 1 : 0,
    ];
}
$c2Monthly = QualityCalculator::calculateMonthly($sampleJobs);
record($results, 'C2 monthly calculation (4+3+2)/3 = 3.0', $c2Monthly['status'] === 'valid' && assertClose($c2Monthly['value'], 3.0));

// ============================================================
// 3. C3 TANGGUNG JAWAB SCALE TESTS
// ============================================================
echo "\n--- 3. C3 Tanggung Jawab Scale Tests ---\n";

$c3Max = ResponsibilityCalculator::calculateMonthly([
    'perawatan_alat' => 4,
    'efisiensi_material' => 4,
    'inisiatif' => 4,
    'kepatuhan_prosedur' => 4,
]);
record($results, 'C3 [4,4,4,4] = 4.0', $c3Max['status'] === 'valid' && assertClose($c3Max['value'], 4.0));

$c3Min = ResponsibilityCalculator::calculateMonthly([
    'perawatan_alat' => 1,
    'efisiensi_material' => 1,
    'inisiatif' => 1,
    'kepatuhan_prosedur' => 1,
]);
record($results, 'C3 [1,1,1,1] = 1.0', $c3Min['status'] === 'valid' && assertClose($c3Min['value'], 1.0));

$c3Mixed = ResponsibilityCalculator::calculateMonthly([
    'perawatan_alat' => 4,
    'efisiensi_material' => 3,
    'inisiatif' => 4,
    'kepatuhan_prosedur' => 3,
]);
// (4 + 3 + 4 + 3) / 4 = 14 / 4 = 3.5
record($results, 'C3 [4,3,4,3] = 3.5', $c3Mixed['status'] === 'valid' && assertClose($c3Mixed['value'], 3.5));

$c3Empty = ResponsibilityCalculator::calculateMonthly([]);
record($results, 'C3 empty record returns no_data', $c3Empty['status'] === 'no_data' && $c3Empty['value'] === null);

$c3InvalidScale = ResponsibilityCalculator::calculateMonthly([
    'perawatan_alat' => 5, // Invalid > 4
    'efisiensi_material' => 3,
    'inisiatif' => 3,
    'kepatuhan_prosedur' => 3,
]);
record($results, 'C3 invalid scale (>4) rejected', $c3InvalidScale['status'] === 'no_data');

// ============================================================
// 4. 3-MONTH AGGREGATION TESTS
// ============================================================
echo "\n--- 4. Quarterly Aggregation Tests ---\n";

// 4.1 Complete 3 months (3.00, 3.50, 4.00 -> 3.50)
$monthlyC3_3Months = [
    1 => ['perawatan_alat' => 3, 'efisiensi_material' => 3, 'inisiatif' => 3, 'kepatuhan_prosedur' => 3], // 3.0
    2 => ['perawatan_alat' => 4, 'efisiensi_material' => 3, 'inisiatif' => 4, 'kepatuhan_prosedur' => 3], // 3.5
    3 => ['perawatan_alat' => 4, 'efisiensi_material' => 4, 'inisiatif' => 4, 'kepatuhan_prosedur' => 4], // 4.0
];
$agg3 = ResponsibilityCalculator::aggregateQuarterly($monthlyC3_3Months, [1, 2, 3]);
record($results, 'Aggregation 3 months: (3.0+3.5+4.0)/3 = 3.50', assertClose($agg3['value'], 3.5));
record($results, 'Aggregation 3 months status complete', $agg3['status'] === 'complete' && $agg3['months_available'] === 3);
record($results, 'Aggregation 3 months has no warning', $agg3['warning'] === null);

// 4.2 Partial 2 months (3.00, no_data, 4.00 -> (3.0+4.0)/2 = 3.50)
$monthlyC3_2Months = [
    1 => ['perawatan_alat' => 3, 'efisiensi_material' => 3, 'inisiatif' => 3, 'kepatuhan_prosedur' => 3], // 3.0
    // month 2 omitted
    3 => ['perawatan_alat' => 4, 'efisiensi_material' => 4, 'inisiatif' => 4, 'kepatuhan_prosedur' => 4], // 4.0
];
$agg2 = ResponsibilityCalculator::aggregateQuarterly($monthlyC3_2Months, [1, 2, 3]);
record($results, 'Aggregation 2 months: (3.0+4.0)/2 = 3.50', assertClose($agg2['value'], 3.5));
record($results, 'Aggregation 2 months status partial', $agg2['status'] === 'partial' && $agg2['months_available'] === 2);
record($results, 'Aggregation 2 months provides warning', $agg2['warning'] !== null && str_contains($agg2['warning'], '2'));

// 4.3 Partial 1 month (3.00, no_data, no_data -> 3.00)
$monthlyC3_1Month = [
    1 => ['perawatan_alat' => 3, 'efisiensi_material' => 3, 'inisiatif' => 3, 'kepatuhan_prosedur' => 3], // 3.0
];
$agg1 = ResponsibilityCalculator::aggregateQuarterly($monthlyC3_1Month, [1, 2, 3]);
record($results, 'Aggregation 1 month: 3.00', assertClose($agg1['value'], 3.0));
record($results, 'Aggregation 1 month status partial', $agg1['status'] === 'partial' && $agg1['months_available'] === 1);

// 4.4 No data at all (no division by zero)
$agg0 = ResponsibilityCalculator::aggregateQuarterly([], [1, 2, 3]);
record($results, 'Aggregation 0 months: value null and no division by zero', $agg0['value'] === null && $agg0['status'] === 'no_data');

// ============================================================
// 5. CALCULATION ENGINE ORCHESTRATION & ARRAY TESTS
// ============================================================
echo "\n--- 5. CalculationEngine Orchestration ---\n";

$mockDisiplin = [
    ['bulan' => 1, 'total_hari_kerja' => 20, 'hadir' => 20, 'terlambat' => 0, 'pekerjaan_terjadwal' => 20, 'sesuai_jadwal' => 20], // 4.0
    ['bulan' => 2, 'total_hari_kerja' => 20, 'hadir' => 19, 'terlambat' => 1, 'pekerjaan_terjadwal' => 20, 'sesuai_jadwal' => 19], // 4.0
    ['bulan' => 3, 'total_hari_kerja' => 20, 'hadir' => 18, 'terlambat' => 4, 'pekerjaan_terjadwal' => 20, 'sesuai_jadwal' => 18], // (3+3+3)/3 = 3.0
]; // avg C1 = (4 + 4 + 3) / 3 = 3.666667

$mockJobs = [
    // Month 1 (all 100% -> 4)
    ['bulan' => 1, 'rapi' => 1, 'presisi' => 1, 'sesuai_desain' => 1],
    ['bulan' => 1, 'rapi' => 1, 'presisi' => 1, 'sesuai_desain' => 1],
    // Month 2 (all 100% -> 4)
    ['bulan' => 2, 'rapi' => 1, 'presisi' => 1, 'sesuai_desain' => 1],
    // Month 3 (all 100% -> 4)
    ['bulan' => 3, 'rapi' => 1, 'presisi' => 1, 'sesuai_desain' => 1],
]; // avg C2 = 4.0

$mockTJ = [
    ['bulan' => 1, 'perawatan_alat' => 4, 'efisiensi_material' => 3, 'inisiatif' => 4, 'kepatuhan_prosedur' => 3], // 3.5
    ['bulan' => 2, 'perawatan_alat' => 4, 'efisiensi_material' => 4, 'inisiatif' => 4, 'kepatuhan_prosedur' => 4], // 4.0
    ['bulan' => 3, 'perawatan_alat' => 3, 'efisiensi_material' => 3, 'inisiatif' => 3, 'kepatuhan_prosedur' => 3], // 3.0
]; // avg C3 = (3.5 + 4.0 + 3.0) / 3 = 3.5

$engineResult = CalculationEngine::calculateFromArrays($mockDisiplin, $mockJobs, $mockTJ, [1, 2, 3]);
record($results, 'Engine combined status complete', $engineResult['status'] === 'complete');
record($results, 'Engine C1 value = 3.666667', assertClose($engineResult['c1_value'], 11 / 3));
record($results, 'Engine C2 value = 4.000000', assertClose($engineResult['c2_value'], 4.0));
record($results, 'Engine C3 value = 3.500000', assertClose($engineResult['c3_value'], 3.5));
record($results, 'Engine months C1=3, C2=3, C3=3', $engineResult['jumlah_bulan_c1'] === 3 && $engineResult['jumlah_bulan_c2'] === 3 && $engineResult['jumlah_bulan_c3'] === 3);

// Test partial when C2 has only month 1 & 2
$mockJobsPartial = [
    ['bulan' => 1, 'rapi' => 1, 'presisi' => 1, 'sesuai_desain' => 1],
    ['bulan' => 2, 'rapi' => 1, 'presisi' => 1, 'sesuai_desain' => 1],
];
$enginePartial = CalculationEngine::calculateFromArrays($mockDisiplin, $mockJobsPartial, $mockTJ, [1, 2, 3]);
record($results, 'Engine partial status when one criteria incomplete', $enginePartial['status'] === 'partial');
record($results, 'Engine partial has warning for C2', $enginePartial['warning'] !== null && str_contains($enginePartial['warning'], 'C2'));

// ============================================================
// 6. DATABASE INTEGRATION & PERSISTENCE TESTS
// ============================================================
echo "\n--- 6. Database Integration & Persistence ---\n";

// 6.1 Create a temporary test period in DB
$testPeriodId = PeriodePenilaian::create([
    'kode_periode' => 'TEST-2026-Q1',
    'nama_periode' => 'Test Kuartal 1 2026',
    'tanggal_mulai' => '2026-01-01',
    'tanggal_selesai' => '2026-03-31',
    'status' => 'proses',
    'created_by' => 1,
]);
record($results, 'Created test period in DB', $testPeriodId > 0);

// Insert operational data for teknisi 1 (Toni)
Kedisiplinan::create([
    'id_periode' => $testPeriodId,
    'id_teknisi' => 1,
    'bulan' => 1,
    'total_hari_kerja' => 20,
    'hadir' => 20,
    'terlambat' => 0,
    'pekerjaan_terjadwal' => 20,
    'sesuai_jadwal' => 20,
]);
Kedisiplinan::create([
    'id_periode' => $testPeriodId,
    'id_teknisi' => 1,
    'bulan' => 2,
    'total_hari_kerja' => 20,
    'hadir' => 19,
    'terlambat' => 1,
    'pekerjaan_terjadwal' => 20,
    'sesuai_jadwal' => 19,
]);
Kedisiplinan::create([
    'id_periode' => $testPeriodId,
    'id_teknisi' => 1,
    'bulan' => 3,
    'total_hari_kerja' => 20,
    'hadir' => 18,
    'terlambat' => 4,
    'pekerjaan_terjadwal' => 20,
    'sesuai_jadwal' => 18,
]);

// Pekerjaan
Pekerjaan::create([
    'id_periode' => $testPeriodId,
    'id_teknisi' => 1,
    'tanggal' => '2026-01-10',
    'bulan' => 1,
    'nama_pekerjaan' => 'Instalasi Server 1',
    'rapi' => 1,
    'presisi' => 1,
    'sesuai_desain' => 1,
]);
Pekerjaan::create([
    'id_periode' => $testPeriodId,
    'id_teknisi' => 1,
    'tanggal' => '2026-02-10',
    'bulan' => 2,
    'nama_pekerjaan' => 'Instalasi Server 2',
    'rapi' => 1,
    'presisi' => 1,
    'sesuai_desain' => 1,
]);
Pekerjaan::create([
    'id_periode' => $testPeriodId,
    'id_teknisi' => 1,
    'tanggal' => '2026-03-10',
    'bulan' => 3,
    'nama_pekerjaan' => 'Instalasi Server 3',
    'rapi' => 1,
    'presisi' => 1,
    'sesuai_desain' => 1,
]);

// Tanggung Jawab
TanggungJawab::create([
    'id_periode' => $testPeriodId,
    'id_teknisi' => 1,
    'bulan' => 1,
    'perawatan_alat' => 4,
    'efisiensi_material' => 3,
    'inisiatif' => 4,
    'kepatuhan_prosedur' => 3,
]);
TanggungJawab::create([
    'id_periode' => $testPeriodId,
    'id_teknisi' => 1,
    'bulan' => 2,
    'perawatan_alat' => 4,
    'efisiensi_material' => 4,
    'inisiatif' => 4,
    'kepatuhan_prosedur' => 4,
]);
TanggungJawab::create([
    'id_periode' => $testPeriodId,
    'id_teknisi' => 1,
    'bulan' => 3,
    'perawatan_alat' => 3,
    'efisiensi_material' => 3,
    'inisiatif' => 3,
    'kepatuhan_prosedur' => 3,
]);

// 6.2 Calculate from DB
$dbCalc = CalculationEngine::calculateTechnician($testPeriodId, 1);
record($results, 'calculateTechnician from DB returns complete status', $dbCalc['status'] === 'complete');
record($results, 'calculateTechnician C1 value match', assertClose($dbCalc['c1_value'], 11 / 3));

// 6.3 Save evaluation to tb_penilaian
$savedId = CalculationEngine::saveEvaluation($testPeriodId, 1, $dbCalc, 1);
record($results, 'saveEvaluation returns valid ID in tb_penilaian', $savedId > 0);

$savedRow = Database::query('SELECT * FROM tb_penilaian WHERE id = ?', [$savedId])->fetch();
record($results, 'tb_penilaian has status_data = calculated', $savedRow['status_data'] === 'calculated');
record($results, 'tb_penilaian has confirmed_by = NULL', $savedRow['confirmed_by'] === null && $savedRow['confirmed_at'] === null);
record($results, 'tb_penilaian c1 stored with decimal precision', assertClose((float)$savedRow['c1'], 11 / 3));
record($results, 'tb_penilaian has jumlah_bulan_c1 = 3', (int)$savedRow['jumlah_bulan_c1'] === 3);

// 6.4 Safety check: attempt to overwrite legacy evaluation is blocked
$legacyEval = Database::query('SELECT * FROM tb_penilaian WHERE status_data = "legacy" LIMIT 1')->fetch();
$legacyBlocked = false;
if ($legacyEval) {
    try {
        CalculationEngine::saveEvaluation((int)$legacyEval['id_periode'], (int)$legacyEval['teknisi_id'], $dbCalc);
    } catch (RuntimeException $e) {
        $legacyBlocked = true;
    }
}
record($results, 'Overwriting legacy evaluation is strictly blocked', $legacyBlocked);

// 6.5 Teardown test data from DB
Penilaian::delete($savedId);
Database::query('DELETE FROM tb_tanggung_jawab WHERE id_periode = ?', [$testPeriodId]);
Database::query('DELETE FROM tb_pekerjaan WHERE id_periode = ?', [$testPeriodId]);
Database::query('DELETE FROM tb_kedisiplinan WHERE id_periode = ?', [$testPeriodId]);
PeriodePenilaian::delete($testPeriodId);

$teardownClean = (Penilaian::findById($savedId) === null) && (PeriodePenilaian::findById($testPeriodId) === null);
record($results, 'Test data cleanly torn down from database', $teardownClean);

// ============================================================
// SUMMARY
// ============================================================
$passed = count(array_filter($results, fn($r) => $r['ok']));
$total = count($results);
echo PHP_EOL . "OPERATIONAL CALCULATION ENGINE SUMMARY: {$passed}/{$total} passed" . PHP_EOL;

$failed = array_filter($results, fn($r) => !$r['ok']);
foreach ($failed as $f) {
    echo 'FAILED: ' . $f['name'] . PHP_EOL;
}

exit($failed ? 1 : 0);
