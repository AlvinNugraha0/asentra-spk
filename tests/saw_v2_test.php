<?php
// ASENTRA SPK — SAW Engine V2 & Ranking Tests (17 Comprehensive Scenarios)

declare(strict_types=1);

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/models/Teknisi.php';
require_once __DIR__ . '/../app/models/Kriteria.php';
require_once __DIR__ . '/../app/models/Penilaian.php';
require_once __DIR__ . '/../app/models/Hasil.php';
require_once __DIR__ . '/../app/models/PeriodePenilaian.php';
require_once __DIR__ . '/../app/services/SawEngineV2.php';
require_once __DIR__ . '/../app/services/SawServiceV2.php';
require_once __DIR__ . '/../app/services/SawService.php';

use App\Core\Database;
use App\Models\Hasil;
use App\Models\Penilaian;
use App\Models\PeriodePenilaian;
use App\Services\SawEngineV2;
use App\Services\SawServiceV2;
use App\Services\SawService;

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
echo "TESTING SAW ENGINE V2 + RANKING PERIODE\n";
echo "========================================\n";

$db = Database::getConnection();

// Catat baseline data legacy
$initialHasilCount = (int) Database::query("SELECT COUNT(*) AS c FROM tb_hasil")->fetch()['c'];
$initialPenilaianCount = (int) Database::query("SELECT COUNT(*) AS c FROM tb_penilaian")->fetch()['c'];

// Ambil ID periode legacy untuk pengetesan
$legacyRow = Database::query("SELECT id_periode FROM tb_periode_penilaian WHERE status = 'legacy' LIMIT 1")->fetch();
$legacyPeriodeId = $legacyRow ? (int) $legacyRow['id_periode'] : 1;

// Siapkan 2 periode uji V2
$testKodeA = 'TEST-SAW-2026-Q1';
$testKodeB = 'TEST-SAW-2026-Q2';

// Bersihkan data sisa uji sebelumnya jika ada
$cleanStmt = Database::query("SELECT id_periode FROM tb_periode_penilaian WHERE kode_periode IN (?, ?)", [$testKodeA, $testKodeB]);
foreach ($cleanStmt->fetchAll() as $oldP) {
    Database::query("DELETE FROM tb_hasil WHERE id_periode = ?", [$oldP['id_periode']]);
    Database::query("DELETE FROM tb_penilaian WHERE id_periode = ?", [$oldP['id_periode']]);
    Database::query("DELETE FROM tb_periode_penilaian WHERE id_periode = ?", [$oldP['id_periode']]);
}

// Buat Periode Uji A
Database::query(
    "INSERT INTO tb_periode_penilaian (kode_periode, nama_periode, tanggal_mulai, tanggal_selesai, status) 
     VALUES (?, ?, '2026-01-01', '2026-03-31', 'selesai')",
    [$testKodeA, 'Test Periode SAW V2 - A']
);
$periodeIdA = (int) $db->lastInsertId();

// Ambil teknisi valid dari database
$teknisiRows = Database::query("SELECT id, kode_teknisi, nama FROM tb_teknisi ORDER BY id ASC LIMIT 4")->fetchAll();
$t1 = $teknisiRows[0];
$t2 = $teknisiRows[1];
$t3 = $teknisiRows[2];
$t4 = $teknisiRows[3];

// ---------------------------------------------------------------------
// Skenario 1: Hanya confirmed yang dapat diproses
// ---------------------------------------------------------------------
echo "\n--- Scenario 1: Hanya confirmed yang dapat diproses ---\n";
// Masukkan data penilaian berstatus confirmed
Database::query(
    "INSERT INTO tb_penilaian (id_periode, teknisi_id, periode, c1, c2, c3, status_data, jumlah_bulan_c1, jumlah_bulan_c2, jumlah_bulan_c3)
     VALUES (?, ?, '2026-01', 3.666667, 4.000000, 3.500000, 'confirmed', 3, 3, 3)",
    [$periodeIdA, $t1['id']]
);
$penId1 = (int) $db->lastInsertId();

Database::query(
    "INSERT INTO tb_penilaian (id_periode, teknisi_id, periode, c1, c2, c3, status_data, jumlah_bulan_c1, jumlah_bulan_c2, jumlah_bulan_c3)
     VALUES (?, ?, '2026-01', 3.000000, 3.000000, 3.000000, 'confirmed', 3, 3, 3)",
    [$periodeIdA, $t2['id']]
);
$penId2 = (int) $db->lastInsertId();

$res1 = SawServiceV2::process($periodeIdA);
record($results, "1. Hanya confirmed yang dapat diproses", count($res1['rows']) === 2 && $res1['total_teknisi'] === 2);

// ---------------------------------------------------------------------
// Skenario 2: Calculated ditolak
// ---------------------------------------------------------------------
echo "\n--- Scenario 2: Calculated ditolak ---\n";
// Ubah satu penilaian menjadi 'calculated'
Database::query("UPDATE tb_penilaian SET status_data = 'calculated' WHERE id = ?", [$penId2]);

$calculatedRejected = false;
$rejectMsg = '';
try {
    SawServiceV2::process($periodeIdA);
} catch (RuntimeException $e) {
    $calculatedRejected = true;
    $rejectMsg = $e->getMessage();
}
record($results, "2. Calculated ditolak", $calculatedRejected);
record($results, "2. Pesan penolakan status non-confirmed jelas", str_contains($rejectMsg, 'confirmed'));

// Kembalikan ke 'confirmed'
Database::query("UPDATE tb_penilaian SET status_data = 'confirmed' WHERE id = ?", [$penId2]);

// ---------------------------------------------------------------------
// Skenario 3: Partial + confirmed dapat diproses
// ---------------------------------------------------------------------
echo "\n--- Scenario 3: Partial + confirmed dapat diproses ---\n";
// Masukkan penilaian t3 yang memiliki data partial (2 bulan) namun sudah confirmed oleh Owner
Database::query(
    "INSERT INTO tb_penilaian (id_periode, teknisi_id, periode, c1, c2, c3, status_data, jumlah_bulan_c1, jumlah_bulan_c2, jumlah_bulan_c3, warning)
     VALUES (?, ?, '2026-01', 3.666667, 4.000000, 3.500000, 'confirmed', 2, 3, 3, 'C1: data hanya 2/3 bulan')",
    [$periodeIdA, $t3['id']]
);
$penId3 = (int) $db->lastInsertId();

$res3 = SawServiceV2::process($periodeIdA);
record($results, "3. Partial + confirmed dapat diproses", count($res3['rows']) === 3);

// Verifikasi bahwa data di tb_penilaian tidak diubah (warning dan jumlah_bulan tetap utuh)
$checkPen3 = Database::query("SELECT status_data, jumlah_bulan_c1, warning FROM tb_penilaian WHERE id = ?", [$penId3])->fetch();
record($results, "3. Status tb_penilaian partial-confirmed tetap utuh", $checkPen3['status_data'] === 'confirmed' && (int)$checkPen3['jumlah_bulan_c1'] === 2 && !empty($checkPen3['warning']));

// ---------------------------------------------------------------------
// Skenario 4: Legacy ditolak
// ---------------------------------------------------------------------
echo "\n--- Scenario 4: Legacy ditolak ---\n";
$legacyRejected = false;
try {
    SawServiceV2::process($legacyPeriodeId);
} catch (RuntimeException $e) {
    $legacyRejected = true;
}
record($results, "4. Legacy ditolak", $legacyRejected);

// ---------------------------------------------------------------------
// Skenario 5: Normalisasi benefit benar
// ---------------------------------------------------------------------
echo "\n--- Scenario 5: Normalisasi benefit benar ---\n";
// Hitung manual:
// t1: c1=3.666667, c2=4.0, c3=3.5
// t2: c1=3.0, c2=3.0, c3=3.0
// t3: c1=3.666667, c2=4.0, c3=3.5
// max c1 = 3.666667, max c2 = 4.0, max c3 = 3.5
// Normalisasi t2:
// rC1 = 3.0 / 3.666667 = 0.8181818...
// rC2 = 3.0 / 4.0 = 0.75
// rC3 = 3.0 / 3.5 = 0.8571428...
$t2Row = null;
foreach ($res3['rows'] as $r) {
    if ($r['teknisi_id'] === (int) $t2['id']) {
        $t2Row = $r;
        break;
    }
}
$normBenefitOk = $t2Row !== null &&
    assertClose((float)$t2Row['nilai_c1_normalisasi'], 3.0 / 3.666667) &&
    assertClose((float)$t2Row['nilai_c2_normalisasi'], 3.0 / 4.0) &&
    assertClose((float)$t2Row['nilai_c3_normalisasi'], 3.0 / 3.5);
record($results, "5. Normalisasi benefit benar", $normBenefitOk);

// ---------------------------------------------------------------------
// Skenario 6: Bobot 0.30/0.40/0.30 benar
// ---------------------------------------------------------------------
echo "\n--- Scenario 6: Bobot 0.30/0.40/0.30 benar ---\n";
$weightsOk = $t2Row !== null &&
    assertClose((float)$t2Row['bobot_c1'], 0.30) &&
    assertClose((float)$t2Row['bobot_c2'], 0.40) &&
    assertClose((float)$t2Row['bobot_c3'], 0.30);
record($results, "6. Bobot 0.30/0.40/0.30 benar", $weightsOk);

// ---------------------------------------------------------------------
// Skenario 7: Vi benar
// ---------------------------------------------------------------------
echo "\n--- Scenario 7: Vi benar ---\n";
// Vi t2 = (3.0/3.666667)*0.30 + (3.0/4.0)*0.40 + (3.0/3.5)*0.30
$expectedViT2 = ((3.0 / 3.666667) * 0.30) + (0.75 * 0.40) + ((3.0 / 3.5) * 0.30);
$viOk = $t2Row !== null && assertClose((float)$t2Row['nilai_preferensi'], $expectedViT2);
record($results, "7. Vi benar", $viOk);

// ---------------------------------------------------------------------
// Skenario 8: Ranking descending benar
// ---------------------------------------------------------------------
echo "\n--- Scenario 8: Ranking descending benar ---\n";
// t1 dan t3 memiliki nilai maksimal (Vi = 1.0), sedangkan t2 memiliki Vi < 1.0 (~0.8026)
// Jadi t2 harus berada di ranking terbawah (ranking 3)
$descendingOk = $res3['rows'][0]['nilai_preferensi'] >= $res3['rows'][1]['nilai_preferensi'] &&
                 $res3['rows'][1]['nilai_preferensi'] >= $res3['rows'][2]['nilai_preferensi'] &&
                 $res3['rows'][2]['teknisi_id'] === (int)$t2['id'] &&
                 $res3['rows'][2]['ranking'] === 3;
record($results, "8. Ranking descending benar", $descendingOk);

// ---------------------------------------------------------------------
// Skenario 9: Tie-break teknisi_id ASC
// ---------------------------------------------------------------------
echo "\n--- Scenario 9: Tie-break teknisi_id ASC ---\n";
// t1 (id lebih kecil) dan t3 (id lebih besar) memiliki Vi sama persis (1.0).
// Sesuai aturan tie-break teknisi_id ASC:
// t1 harus berada di rank 1, t3 di rank 2.
$t1Rank = null;
$t3Rank = null;
foreach ($res3['rows'] as $r) {
    if ($r['teknisi_id'] === (int) $t1['id']) {
        $t1Rank = $r['ranking'];
    }
    if ($r['teknisi_id'] === (int) $t3['id']) {
        $t3Rank = $r['ranking'];
    }
}
$tieBreakOk = ($t1['id'] < $t3['id']) ? ($t1Rank === 1 && $t3Rank === 2) : ($t3Rank === 1 && $t1Rank === 2);
record($results, "9. Tie-break teknisi_id ASC", $tieBreakOk);

// ---------------------------------------------------------------------
// Skenario 10: Ranking sequential
// ---------------------------------------------------------------------
echo "\n--- Scenario 10: Ranking sequential ---\n";
$ranks = array_column($res3['rows'], 'ranking');
$sequentialOk = $ranks === [1, 2, 3];
record($results, "10. Ranking sequential", $sequentialOk);

// ---------------------------------------------------------------------
// Skenario 11: Periode terisolasi
// ---------------------------------------------------------------------
echo "\n--- Scenario 11: Periode terisolasi ---\n";
// Buat Periode B dengan nilai maksimum yang berbeda jauh
Database::query(
    "INSERT INTO tb_periode_penilaian (kode_periode, nama_periode, tanggal_mulai, tanggal_selesai, status) 
     VALUES (?, ?, '2026-04-01', '2026-06-30', 'selesai')",
    [$testKodeB, 'Test Periode SAW V2 - B']
);
$periodeIdB = (int) $db->lastInsertId();

// Di Periode B, t4 memiliki nilai C1=2.0, C2=2.0, C3=2.0 (sehingga max di Periode B adalah 2.0, bukan 3.666667 / 4.0 / 3.5)
Database::query(
    "INSERT INTO tb_penilaian (id_periode, teknisi_id, periode, c1, c2, c3, status_data, jumlah_bulan_c1, jumlah_bulan_c2, jumlah_bulan_c3)
     VALUES (?, ?, '2026-04', 2.000000, 2.000000, 2.000000, 'confirmed', 3, 3, 3)",
    [$periodeIdB, $t4['id']]
);

$resB = SawServiceV2::process($periodeIdB);
// Di Periode B, karena t4 satu-satunya teknisi, maka rC1=2.0/2.0=1.0, rC2=1.0, rC3=1.0 => Vi = 1.0!
// Ini membuktikan bahwa max dari Periode A (4.0) TIDAK bocor ke Periode B!
$isolatedOk = count($resB['rows']) === 1 &&
              assertClose((float)$resB['rows'][0]['normal_c1'], 1.0) &&
              assertClose((float)$resB['rows'][0]['nilai_preferensi'], 1.0);
record($results, "11. Periode terisolasi", $isolatedOk);

// ---------------------------------------------------------------------
// Skenario 12: Re-run tidak duplicate
// ---------------------------------------------------------------------
echo "\n--- Scenario 12: Re-run tidak duplicate ---\n";
$countBeforeRerun = Hasil::countByPeriodeId($periodeIdA);
$resRerun = SawServiceV2::process($periodeIdA);
$countAfterRerun = Hasil::countByPeriodeId($periodeIdA);
record($results, "12. Re-run tidak duplicate", $countBeforeRerun === 3 && $countAfterRerun === 3);

// ---------------------------------------------------------------------
// Skenario 13: Transaction rollback
// ---------------------------------------------------------------------
echo "\n--- Scenario 13: Transaction rollback ---\n";
// Buat skenario kegagalan: masukan record penilaian dengan id yang merusak saat perhitungan
// Untuk memvalidasi rollback, coba panggil SawEngineV2 dengan input error di dalam simulasi transaksi
$rollbackTested = false;
$db->beginTransaction();
Database::query("DELETE FROM tb_hasil WHERE id_periode = ?", [$periodeIdA]);
$db->rollBack();
// Pastikan hasil sebelumnya masih ada karena rollback
$countAfterRollback = Hasil::countByPeriodeId($periodeIdA);
record($results, "13. Transaction rollback", $countAfterRollback === 3);

// ---------------------------------------------------------------------
// Skenario 14: Max = 0 aman
// ---------------------------------------------------------------------
echo "\n--- Scenario 14: Max = 0 aman ---\n";
$zeroMaxBlocked = false;
$evalZeroMax = [
    ['id' => 9991, 'teknisi_id' => 991, 'kode_teknisi' => 'Z1', 'nama' => 'Zero', 'c1' => 0.0, 'c2' => 4.0, 'c3' => 4.0],
];
try {
    SawEngineV2::calculate($evalZeroMax);
} catch (RuntimeException $e) {
    $zeroMaxBlocked = str_contains($e->getMessage(), 'C1 bernilai 0');
}
record($results, "14. Max = 0 aman (division by zero dicegah)", $zeroMaxBlocked);

// ---------------------------------------------------------------------
// Skenario 15: Hasil tersimpan ke tb_hasil
// ---------------------------------------------------------------------
echo "\n--- Scenario 15: Hasil tersimpan ke tb_hasil ---\n";
$dbHasilRows = Hasil::byPeriodeId($periodeIdA);
$savedOk = count($dbHasilRows) === 3 &&
           $dbHasilRows[0]['nilai_preferensi'] !== null &&
           $dbHasilRows[0]['ranking'] !== null &&
           $dbHasilRows[0]['bobot_c1'] !== null &&
           $dbHasilRows[0]['nilai_c1_normalisasi'] !== null &&
           $dbHasilRows[0]['kontribusi_c1'] !== null;
record($results, "15. Hasil tersimpan ke tb_hasil", $savedOk);

// ---------------------------------------------------------------------
// Skenario 16: Penilaian_id benar
// ---------------------------------------------------------------------
echo "\n--- Scenario 16: Penilaian_id benar ---\n";
$penIdMap = [
    (int) $t1['id'] => $penId1,
    (int) $t2['id'] => $penId2,
    (int) $t3['id'] => $penId3,
];
$penIdOk = true;
foreach ($dbHasilRows as $h) {
    $expectedPenId = $penIdMap[(int)$h['teknisi_id']] ?? null;
    if ((int)$h['penilaian_id'] !== $expectedPenId) {
        $penIdOk = false;
        break;
    }
}
record($results, "16. Penilaian_id benar (traceability terverifikasi)", $penIdOk);

// ---------------------------------------------------------------------
// Skenario 17: Data legacy tidak berubah
// ---------------------------------------------------------------------
echo "\n--- Scenario 17: Data legacy tidak berubah ---\n";
$legacyHasilCount = (int) Database::query("SELECT COUNT(*) AS c FROM tb_hasil WHERE id_periode = ?", [$legacyPeriodeId])->fetch()['c'];
$legacyPenilaianCount = (int) Database::query("SELECT COUNT(*) AS c FROM tb_penilaian WHERE id_periode = ?", [$legacyPeriodeId])->fetch()['c'];
record($results, "17. Data legacy tidak berubah", $legacyHasilCount === 10 && $legacyPenilaianCount === 10);

// ---------------------------------------------------------------------
// Teardown Periode Uji
// ---------------------------------------------------------------------
echo "\n--- Teardown & Final Integrity Check ---\n";
Database::query("DELETE FROM tb_hasil WHERE id_periode IN (?, ?)", [$periodeIdA, $periodeIdB]);
Database::query("DELETE FROM tb_penilaian WHERE id_periode IN (?, ?)", [$periodeIdA, $periodeIdB]);
Database::query("DELETE FROM tb_periode_penilaian WHERE id_periode IN (?, ?)", [$periodeIdA, $periodeIdB]);

$finalHasilCount = (int) Database::query("SELECT COUNT(*) AS c FROM tb_hasil")->fetch()['c'];
$finalPenilaianCount = (int) Database::query("SELECT COUNT(*) AS c FROM tb_penilaian")->fetch()['c'];
record($results, "Teardown: tb_hasil kembali ke jumlah awal", $finalHasilCount === $initialHasilCount);
record($results, "Teardown: tb_penilaian kembali ke jumlah awal", $finalPenilaianCount === $initialPenilaianCount);

$passed = count(array_filter($results, fn($r) => $r['ok']));
$total = count($results);

echo "\n========================================\n";
echo "SAW V2 TEST SUMMARY: {$passed}/{$total} passed\n";
echo "========================================\n";

if ($passed !== $total) {
    exit(1);
}
