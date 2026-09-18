<?php
// ASENTRA SPK — Phase 6G: SAW UI Integration Test
//
// Verifies the end-to-end V2 chain:
//   confirmed assessment (tb_penilaian) -> SawServiceV2::process(id_periode)
//   -> tb_hasil (full quarter code) -> RankingController lookup
//
// Covers the fixed bug: period codes must NEVER be truncated to '2026-Q'.
//
// SAFETY: uses its own isolated period 'TEST6G-2026-Q4'. Legacy periods and
// tb_penilaian=12 / tb_hasil=12 legacy rows are never touched.

declare(strict_types=1);

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/helpers/view.php';
require_once __DIR__ . '/../app/helpers/format.php';
require_once __DIR__ . '/../app/helpers/url.php';
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/models/PeriodePenilaian.php';
require_once __DIR__ . '/../app/models/Penilaian.php';
require_once __DIR__ . '/../app/models/Hasil.php';
require_once __DIR__ . '/../app/models/User.php';
require_once __DIR__ . '/../app/models/Teknisi.php';
require_once __DIR__ . '/../app/models/Kriteria.php';
require_once __DIR__ . '/../app/services/SawEngineV2.php';
require_once __DIR__ . '/../app/services/SawServiceV2.php';

use App\Core\Database;
use App\Models\Hasil;
use App\Models\Penilaian;
use App\Models\PeriodePenilaian;
use App\Services\SawServiceV2;

$pass = 0;
$fail = 0;
$testKode = 'TEST6G-2026-Q4';

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

function cleanup(string $kode): void
{
    // Order matters: tb_hasil references tb_penilaian (ON DELETE RESTRICT).
    Database::query('DELETE FROM tb_hasil WHERE periode = ?', [$kode]);

    // Also catch orphan rows whose id_periode is NULL (left by aborted runs).
    Database::query('DELETE FROM tb_penilaian WHERE periode = ?', [$kode]);

    $row = Database::query('SELECT id_periode FROM tb_periode_penilaian WHERE kode_periode = ?', [$kode])->fetch();
    if (!$row) {
        return;
    }
    $pid = (int) $row['id_periode'];
    Database::query('DELETE FROM tb_hasil WHERE id_periode = ?', [$pid]);
    Database::query('DELETE FROM tb_penilaian WHERE id_periode = ?', [$pid]);
    Database::query('DELETE FROM tb_periode_penilaian WHERE id_periode = ?', [$pid]);
}

echo '========================================' . PHP_EOL;
echo 'PHASE 6G — SAW UI INTEGRATION TEST' . PHP_EOL;
echo '========================================' . PHP_EOL;

$basePenilaian = (int) Database::query('SELECT COUNT(*) AS c FROM tb_penilaian')->fetch()['c'];
$baseHasil     = (int) Database::query('SELECT COUNT(*) AS c FROM tb_hasil')->fetch()['c'];
echo "Baseline: penilaian={$basePenilaian} hasil={$baseHasil}" . PHP_EOL . PHP_EOL;

cleanup($testKode);

// ---------- column width check ----------
echo '-- schema: periode columns are wide enough for V2 codes --' . PHP_EOL;
$colPenilaian = Database::getConnection()->query("SHOW COLUMNS FROM tb_penilaian LIKE 'periode'")->fetch();
$colHasil     = Database::getConnection()->query("SHOW COLUMNS FROM tb_hasil LIKE 'periode'")->fetch();
check(str_contains((string) $colPenilaian['Type'], '30'), 'tb_penilaian.periode is VARCHAR(30)', (string) $colPenilaian['Type']);
check(str_contains((string) $colHasil['Type'], '30'), 'tb_hasil.periode is VARCHAR(30)', (string) $colHasil['Type']);

// ---------- setup: isolated CONFIRMED V2 period ----------
$testPeriodeId = PeriodePenilaian::create([
    'kode_periode'    => $testKode,
    'nama_periode'    => 'TEST Phase 6G Q4 2026',
    'tanggal_mulai'   => '2026-10-01',
    'tanggal_selesai' => '2026-12-31',
    'status'          => 'selesai',
    'created_by'      => 1,
]);

$owner = \App\Models\User::findByUsername('owner');
$ownerId = $owner !== null ? (int) $owner['id'] : 2;

$teknisi = \App\Models\Teknisi::all();

// Three technicians with distinct scores so ranking is deterministic.
$fixtures = [
    [4.0, 4.0, 4.0],
    [3.0, 3.0, 3.0],
    [2.0, 2.0, 2.0],
];
foreach ($fixtures as $i => $f) {
    // Penilaian::create() does not accept id_periode/status_data; insert directly
    // so the V2 chain (SawServiceV2 reads findByPeriodeId + confirmed status) works.
    Database::query(
        'INSERT INTO tb_penilaian (id_periode, teknisi_id, periode, c1, c2, c3, status_data, created_by)
         VALUES (?, ?, ?, ?, ?, ?, "confirmed", ?)',
        [$testPeriodeId, (int) $teknisi[$i]['id'], $testKode, $f[0], $f[1], $f[2], $ownerId]
    );
}

// ================================================ 1. SawServiceV2 end-to-end
echo PHP_EOL . '-- SawServiceV2::process(id_periode) end-to-end --' . PHP_EOL;

$result = SawServiceV2::process($testPeriodeId);
check(($result['total_teknisi'] ?? 0) === 3, '3 technicians processed', 'got ' . (string) ($result['total_teknisi'] ?? -1));
check(($result['kode_periode'] ?? '') === $testKode, 'kode_periode returned intact', 'got ' . (string) ($result['kode_periode'] ?? ''));

// THE CORE BUG CHECK: tb_hasil.periode must be the FULL code, never '2026-Q'.
$hasilRows = Hasil::byPeriodeId($testPeriodeId);
check(count($hasilRows) === 3, '3 rows in tb_hasil', 'got ' . count($hasilRows));
foreach ($hasilRows as $h) {
    check(($h['periode'] ?? '') === $testKode, 'tb_hasil.periode = full code ' . $testKode, 'got [' . (string) ($h['periode'] ?? '') . ']');
    check(($h['id_periode'] ?? 0) === $testPeriodeId, 'tb_hasil.id_periode set correctly');
}

// tb_penilaian.periode must also be intact.
$penRows = Penilaian::findByPeriodeId($testPeriodeId);
foreach ($penRows as $p) {
    check(($p['periode'] ?? '') === $testKode, 'tb_penilaian.periode = full code (not truncated)', 'got [' . (string) ($p['periode'] ?? '') . ']');
}

// No truncated '2026-Q' anywhere in the DB.
$truncated = (int) Database::query("SELECT COUNT(*) AS c FROM tb_hasil WHERE periode = '2026-Q'")->fetch()['c'];
check($truncated === 0, 'no truncated "2026-Q" row exists in tb_hasil', 'found ' . $truncated);
$truncP = (int) Database::query("SELECT COUNT(*) AS c FROM tb_penilaian WHERE periode = '2026-Q'")->fetch()['c'];
check($truncP === 0, 'no truncated "2026-Q" row exists in tb_penilaian', 'found ' . $truncP);

// Ranking correctness: A (4,4,4) rank 1, B (3,3,3) rank 2, C (2,2,2) rank 3.
$byRank = [];
foreach ($hasilRows as $h) {
    $byRank[(int) $h['ranking']] = $h;
}
check(($byRank[1]['c1'] ?? 0) == 4.0, 'rank 1 has C1 = 4 (highest)', 'got ' . (string) ($byRank[1]['c1'] ?? 'none'));
check(($byRank[2]['c1'] ?? 0) == 3.0, 'rank 2 has C1 = 3', 'got ' . (string) ($byRank[2]['c1'] ?? 'none'));
check(($byRank[3]['c1'] ?? 0) == 2.0, 'rank 3 has C1 = 2', 'got ' . (string) ($byRank[3]['c1'] ?? 'none'));

// Vi values: SAW benefit rij = xij / max, Vi = 0.30*rC1 + 0.40*rC2 + 0.30*rC3.
// max = 4 for all criteria, so top = 1.0 exactly.
$vi1 = (float) $byRank[1]['nilai_preferensi'];
check(abs($vi1 - 1.0) < 0.000001, 'rank 1 Vi = 1.0 exactly (rij=1 all criteria)', 'got ' . $vi1);
check(abs((float) $byRank[3]['nilai_preferensi'] - 0.5) < 0.000001, 'rank 3 Vi = 0.5 (2/4 * 1.0)', 'got ' . (string) $byRank[3]['nilai_preferensi']);

// Idempotency: re-run produces the same rows, no duplicates.
$before = Hasil::countByPeriodeId($testPeriodeId);
SawServiceV2::process($testPeriodeId);
$after = Hasil::countByPeriodeId($testPeriodeId);
check($before === $after, 'Re-run is idempotent (still ' . $before . ' rows)', 'before ' . $before . ' after ' . $after);

// ================================================ 2. confirmed-only gate
echo PHP_EOL . '-- only CONFIRMED data can be processed --' . PHP_EOL;

$draftId = PeriodePenilaian::create([
    'kode_periode'    => 'TEST6G-DRAFT-Q4',
    'nama_periode'    => 'TEST 6G draft (not confirmed)',
    'tanggal_mulai'   => '2026-10-01',
    'tanggal_selesai' => '2026-12-31',
    'status'          => 'draft',
    'created_by'      => 1,
]);
Database::query(
    'INSERT INTO tb_penilaian (id_periode, teknisi_id, periode, c1, c2, c3, status_data, created_by)
     VALUES (?, ?, ?, ?, ?, ?, "calculated", ?)',
    [$draftId, (int) $teknisi[0]['id'], 'TEST6G-DRAFT-Q4', 3.0, 3.0, 3.0, 1]
);
try {
    SawServiceV2::process($draftId);
    check(false, 'Non-confirmed period must be REJECTED by SawServiceV2');
} catch (Throwable $e) {
    check(str_contains($e->getMessage(), 'confirmed'), 'Non-confirmed period rejected with clear reason', substr($e->getMessage(), 0, 70));
}
check(Hasil::countByPeriodeId($draftId) === 0, 'No tb_hasil written for non-confirmed period');

// Legacy period cannot be processed by V2.
$legacy = Database::query('SELECT id_periode FROM tb_periode_penilaian WHERE status = "legacy" LIMIT 1')->fetch();
if ($legacy) {
    try {
        SawServiceV2::process((int) $legacy['id_periode']);
        check(false, 'Legacy period must be REJECTED');
    } catch (Throwable $e) {
        check(str_contains($e->getMessage(), 'legacy'), 'Legacy period rejected by SawServiceV2', substr($e->getMessage(), 0, 60));
    }
} else {
    check(true, 'No legacy period (skip)');
}

// ================================================ 3. Controller integration
echo PHP_EOL . '-- RankingController integration (id_periode) --' . PHP_EOL;

// resolvePeriodeId equivalents: numeric id and quarter code both resolve.
$byId = PeriodePenilaian::findById($testPeriodeId);
check($byId !== null && ($byId['kode_periode'] ?? '') === $testKode, 'PeriodePenilaian::findById resolves V2 period');
$byKode = PeriodePenilaian::findByKode($testKode);
check($byKode !== null && (int) $byKode['id_periode'] === $testPeriodeId, 'PeriodePenilaian::findByKode resolves V2 period');

$opts = PeriodePenilaian::allWithEvaluations();
$found = false;
foreach ($opts as $o) {
    if ((int) $o['id_periode'] === $testPeriodeId) {
        $found = true;
        break;
    }
}
check($found, 'allWithEvaluations() lists the V2 period with evaluations');

$hasilByPeriode = Hasil::byPeriode($testKode);
check(count($hasilByPeriode) === 3, 'Hasil::byPeriode(full V2 code) returns 3 rows (string lookup works too)', 'got ' . count($hasilByPeriode));

// ================================================ TEARDOWN
cleanup($testKode);
cleanup('TEST6G-DRAFT-Q4');

$afterPenilaian = (int) Database::query('SELECT COUNT(*) AS c FROM tb_penilaian')->fetch()['c'];
$afterHasil     = (int) Database::query('SELECT COUNT(*) AS c FROM tb_hasil')->fetch()['c'];
check($afterPenilaian === $basePenilaian, "tb_penilaian restored to baseline {$basePenilaian}", 'got ' . $afterPenilaian);
check($afterHasil === $baseHasil, "tb_hasil restored to baseline {$baseHasil}", 'got ' . $afterHasil);

$legacyP = (int) Database::query('SELECT COUNT(*) AS c FROM tb_penilaian WHERE status_data = "legacy"')->fetch()['c'];
$legacyH = (int) Database::query('SELECT COUNT(*) AS c FROM tb_hasil h JOIN tb_periode_penilaian p ON p.id_periode = h.id_periode WHERE p.status = "legacy"')->fetch()['c'];
check($legacyP === 12, 'Legacy tb_penilaian still 12', 'got ' . $legacyP);
check($legacyH === 12, 'Legacy tb_hasil still 12', 'got ' . $legacyH);

echo PHP_EOL . '========================================' . PHP_EOL;
echo 'RESULT: ' . $pass . '/' . ($pass + $fail) . ' PASS' . PHP_EOL;
echo '========================================' . PHP_EOL;

exit($fail === 0 ? 0 : 1);
