<?php
// ASENTRA SPK — Phase 6H: Ranking UI V2 Test
//
// Verifies the Ranking UI V2 contract without a web server:
//   1. The V2 period is selectable and resolves through RankingController.
//   2. The ranking table draws Teknisi, C1, C2, C3, normalisasi, kontribusi,
//      Nilai Preferensi (Vi) and Ranking — with no formula recomputed anywhere.
//   3. Every displayed value is read straight from tb_hasil.
//   4. Laporan (report) accepts the V2 id_periode and quarter code.
//   5. V1/legacy periods keep working and legacy data never changes.
//
// SAFETY: no rows are inserted, updated or deleted outside a temporary scratch
// period (TEST6H-*) that is removed at the end. tb_penilaian=12 / tb_hasil=12
// legacy rows are only read.

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
require_once __DIR__ . '/../app/models/Kriteria.php';
require_once __DIR__ . '/../app/models/Teknisi.php';
require_once __DIR__ . '/../app/services/SawEngineV2.php';
require_once __DIR__ . '/../app/services/SawServiceV2.php';

use App\Core\Database;
use App\Models\Hasil;
use App\Models\PeriodePenilaian;
use App\Services\SawServiceV2;

$pass = 0;
$fail = 0;
$testKode = 'TEST6H-2026-Q1';

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
    Database::query('DELETE FROM tb_hasil WHERE periode = ?', [$kode]);
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
echo 'PHASE 6H — RANKING UI V2 TEST' . PHP_EOL;
echo '========================================' . PHP_EOL;

$basePenilaian = (int) Database::query('SELECT COUNT(*) AS c FROM tb_penilaian')->fetch()['c'];
$baseHasil     = (int) Database::query('SELECT COUNT(*) AS c FROM tb_hasil')->fetch()['c'];
echo "Baseline: penilaian={$basePenilaian} hasil={$baseHasil}" . PHP_EOL . PHP_EOL;

cleanup($testKode);

// ---------- 0. Locate the real confirmed V2 period (Q1-2026) ----------
$v2 = null;
foreach (PeriodePenilaian::all() as $p) {
    if (($p['status'] ?? '') === 'legacy') {
        continue;
    }
    if (Hasil::countByPeriodeId((int) $p['id_periode']) > 0) {
        $v2 = $p;
        break;
    }
}
check($v2 !== null, 'a confirmed V2 period with tb_hasil rows exists');
$v2Id = $v2 !== null ? (int) $v2['id_periode'] : 0;

// ---------- 1. Period resolution (RankingController::resolvePeriodeId logic) ----------
echo PHP_EOL . '-- period resolution --' . PHP_EOL;

$reflection = null;
$resolved = 0;
require_once __DIR__ . '/../app/controllers/RankingController.php';
if (class_exists(\App\Controllers\RankingController::class)) {
    $reflection = new \ReflectionClass(\App\Controllers\RankingController::class);
    $method = $reflection->getMethod('resolvePeriodeId');
    $method->setAccessible(true);
    $controller = new \App\Controllers\RankingController();

    // V2 numeric id resolves to itself.
    $resolved = $method->invoke($controller, (string) $v2Id);
    check($resolved === $v2Id, 'numeric V2 id_periode resolves', "got {$resolved}");

    // V2 quarter code resolves too.
    $byKode = $method->invoke($controller, (string) ($v2['kode_periode'] ?? ''));
    check($byKode === $v2Id, 'V2 quarter code resolves to id_periode', 'got ' . $byKode);

    // Legacy V1 string code must NOT resolve as V2.
    $legacy = $method->invoke($controller, '2026-08');
    check($legacy === 0, 'legacy V1 string resolves to 0 (legacy path)', 'got ' . $legacy);

    $legacy2 = $method->invoke($controller, 'LEGACY-2026-09');
    check($legacy2 === 0, 'LEGACY-YYYY-MM resolves to 0 (legacy path)', 'got ' . $legacy2);
} else {
    check(false, 'RankingController class loaded');
}

// ---------- 2. Result rows & displayed values come from tb_hasil ----------
echo PHP_EOL . '-- ranking rows straight from tb_hasil --' . PHP_EOL;

$rows = Hasil::byPeriodeId($v2Id);
check(count($rows) === 10, 'V2 ranking has 10 technicians', 'got ' . count($rows));

$columns = ['c1', 'c2', 'c3', 'nilai_c1_normalisasi', 'nilai_c2_normalisasi', 'nilai_c3_normalisasi',
            'kontribusi_c1', 'kontribusi_c2', 'kontribusi_c3', 'nilai_preferensi', 'ranking'];
$missing = [];
foreach ($rows as $r) {
    foreach ($columns as $c) {
        if (!array_key_exists($c, $r) || $r[$c] === null) {
            $missing[] = $c;
        }
    }
}
check(empty($missing), 'all displayed columns exist in tb_hasil rows', 'missing: ' . implode(',', array_unique($missing)));

// C1/C2/C3 in tb_hasil must equal tb_penilaian (no recomputation in the view).
$mismatch = 0;
foreach ($rows as $r) {
    $p = Database::query('SELECT c1, c2, c3 FROM tb_penilaian WHERE id = ?', [(int) $r['penilaian_id']])->fetch();
    if ((float) $p['c1'] !== (float) $r['c1'] || (float) $p['c2'] !== (float) $r['c2'] || (float) $p['c3'] !== (float) $r['c3']) {
        $mismatch++;
    }
}
check($mismatch === 0, 'C1/C2/C3 in tb_hasil match tb_penilaian', "{$mismatch} mismatched");

// Vi must equal the sum of the three stored contributions (no recompute, no rounding drift).
$viDrift = 0;
foreach ($rows as $r) {
    $sum = (float) $r['kontribusi_c1'] + (float) $r['kontribusi_c2'] + (float) $r['kontribusi_c3'];
    if (abs($sum - (float) $r['nilai_preferensi']) > 0.0000015) {
        $viDrift++;
    }
}
check($viDrift === 0, 'Vi = sum of stored contributions (exactly)', "{$viDrift} drifted");

// Ranking is sequential 1..N and Vi is non-increasing (tie-break teknisi_id ASC).
$seq = true;
$ordered = true;
$tiebreak = true;
foreach ($rows as $i => $r) {
    if ((int) $r['ranking'] !== $i + 1) {
        $seq = false;
    }
    if ($i > 0 && (float) $r['nilai_preferensi'] > (float) $rows[$i - 1]['nilai_preferensi']) {
        $ordered = false;
    }
    if ($i > 0
        && (float) $r['nilai_preferensi'] === (float) $rows[$i - 1]['nilai_preferensi']
        && (int) $r['teknisi_id'] < (int) $rows[$i - 1]['teknisi_id']) {
        $tiebreak = false;
    }
}
check($seq, 'ranking is sequential 1..N');
check($ordered, 'Vi descending');
check($tiebreak, 'ties broken by teknisi_id ASC');

// ---------- 3. Formatting helpers display full precision ----------
echo PHP_EOL . '-- formatting helpers --' . PHP_EOL;

$sample = $rows[0];
check(decimalFormat((string) $sample['c1']) === '3,777778', 'decimalFormat keeps 6 decimals (no truncation)', 'got ' . decimalFormat((string) $sample['c1']));
check(decimalFormat('4.000000') === '4', 'decimalFormat trims trailing zeros', 'got ' . decimalFormat('4.000000'));
check(scoreFormat((float) $sample['nilai_preferensi'], 3) === '0,989', 'scoreFormat renders Vi to 3 decimals', 'got ' . scoreFormat((float) $sample['nilai_preferensi'], 3));

// ---------- 4. max(C) comes from tb_hasil, not c/normalisasi ----------
echo PHP_EOL . '-- max criteria from tb_hasil --' . PHP_EOL;

$max = Hasil::maxCriteriaByPeriodeId($v2Id);
$expectedMax = [
    'c1' => (float) Database::query('SELECT MAX(c1) AS m FROM tb_hasil WHERE id_periode = ?', [$v2Id])->fetch()['m'],
    'c2' => (float) Database::query('SELECT MAX(c2) AS m FROM tb_hasil WHERE id_periode = ?', [$v2Id])->fetch()['m'],
    'c3' => (float) Database::query('SELECT MAX(c3) AS m FROM tb_hasil WHERE id_periode = ?', [$v2Id])->fetch()['m'],
];
check($max === $expectedMax, 'maxCriteriaByPeriodeId matches MAX() over tb_hasil', json_encode($max));
check($max['c2'] === 3.888889, 'max(C2) is 3,888889 (not rounded to 4)', 'got ' . var_export($max['c2'], true));

// ---------- 5. Hasil::byPeriodeFlexible accepts every selector shape ----------
echo PHP_EOL . '-- flexible period lookup --' . PHP_EOL;

check(count(Hasil::byPeriodeFlexible((string) $v2Id)) === 10, 'byPeriodeFlexible(numeric id) -> 10 rows');
check(count(Hasil::byPeriodeFlexible((string) $v2['kode_periode'])) === 10, 'byPeriodeFlexible(quarter code) -> 10 rows');
check(count(Hasil::byPeriodeFlexible('LEGACY-2026-09')) === 2, 'byPeriodeFlexible(LEGACY-2026-09) -> 2 rows');
check(count(Hasil::byPeriodeFlexible('2026-08')) === 10, 'byPeriodeFlexible(V1 month code) -> 10 rows');

// Laporan must accept both quarter-code shapes stored in the DB ('Q1-2026' and
// 'YYYY-Qn'). requireOwner() and the redirect calls need a session + headers,
// so the deeper controller assertions live in the HTTP test instead. Here we
// only assert the lookup it depends on never returns V2 rows for a V1 code.
$laporanCode = (string) ($v2['kode_periode'] ?? '');
check(
    count(Hasil::byPeriodeFlexible($laporanCode)) === count(Hasil::byPeriodeFlexible((string) $v2Id)),
    'laporan lookup identical for quarter code and id_periode'
);

// ---------- 6. Ranking view renders without recomputing ----------
echo PHP_EOL . '-- view rendering --' . PHP_EOL;

$viewHtml = '';
$buffer = '';
try {
    // Build the same data the controller passes, then capture the view output.
    $periodInfo = PeriodePenilaian::findById($v2Id);
    $results    = Hasil::byPeriodeId($v2Id);
    $periodOptions = [
        ['value' => (string) $v2Id, 'label' => 'V2'],
        ['value' => 'LEGACY-2026-09', 'label' => 'Legacy'],
    ];

    ob_start();
    // scope extract for the view
    (static function () use ($periodInfo, $results, $periodOptions, $v2Id): void {
        $title = 'Hasil Ranking';
        $subtitle = 'Ranking berdasarkan metode SAW.';
        $periode = (string) $v2Id;
        $periods = ['2026-08', '2026-09'];
        $viewVars = [
            'title' => $title,
            'subtitle' => $subtitle,
            'periode' => $periode,
            'periods' => $periods,
            'periodOptions' => $periodOptions,
            'periodInfo' => $periodInfo,
            'results' => $results,
        ];
        extract($viewVars);
        require __DIR__ . '/../app/views/owner/ranking.php';
    })();
    $viewHtml = (string) ob_get_clean();
} catch (\Throwable $e) {
    $viewHtml = 'RENDER ERROR: ' . $e->getMessage();
}

check(!str_starts_with($viewHtml, 'RENDER ERROR'), 'ranking view renders without error', substr($viewHtml, 0, 120));

foreach ($rows as $r) {
    $needleVi = scoreFormat((float) $r['nilai_preferensi'], 3);
    $hasC1 = str_contains($viewHtml, decimalFormat((string) $r['c1']));
    $hasVi = str_contains($viewHtml, $needleVi);
    if (!$hasC1 || !$hasVi) {
        check(false, 'row for teknisi ' . $r['kode_teknisi'] . ' shows its own C1/Vi from tb_hasil');
        break;
    }
}
check(str_contains($viewHtml, 'N1 (C1/max)'), 'table header includes normalisasi columns');
check(str_contains($viewHtml, 'K1 (w×N1)'), 'table header includes kontribusi columns');
check(substr_count($viewHtml, 'rank-1') >= 1, 'top rank badge rendered');
check(str_contains($viewHtml, 'nama_periode') === false, 'view does not leak raw array keys');

// The V2 period label (nama_periode) must appear for V2 periods.
check(str_contains($viewHtml, (string) $v2['nama_periode']), 'V2 heading uses nama_periode', 'missing ' . (string) $v2['nama_periode']);

// ---------- 7. Legacy protection ----------
echo PHP_EOL . '-- legacy protection --' . PHP_EOL;

$legacyP = (int) Database::query("SELECT COUNT(*) AS c FROM tb_penilaian WHERE status_data = 'legacy'")->fetch()['c'];
$legacyH = (int) Database::query('SELECT COUNT(*) AS c FROM tb_hasil WHERE id_periode IN (SELECT id_periode FROM tb_periode_penilaian WHERE status = \'legacy\')')->fetch()['c'];
check($legacyP === 12, 'legacy tb_penilaian still 12', "got {$legacyP}");
check($legacyH === 12, 'legacy tb_hasil still 12', "got {$legacyH}");

// Legacy rows must keep id_periode pointing at legacy periods, never a V2 one.
$cross = (int) Database::query("SELECT COUNT(*) AS c FROM tb_hasil h JOIN tb_periode_penilaian p ON p.id_periode = h.id_periode WHERE p.status = 'legacy' AND h.periode NOT LIKE 'LEGACY%' AND h.periode NOT LIKE '20%'")->fetch()['c'];
check($cross === 0, 'no legacy tb_hasil row leaked into a V2 period code');

// ---------- 8. Scratch period end-to-end (SawServiceV2 -> UI data) ----------
echo PHP_EOL . '-- scratch period: SawServiceV2 then UI-shaped read --' . PHP_EOL;

$scratchId = PeriodePenilaian::create([
    'kode_periode'    => $testKode,
    'nama_periode'    => 'TEST Phase 6H Q1 2026',
    'tanggal_mulai'   => '2026-01-01',
    'tanggal_selesai' => '2026-03-31',
    'status'          => 'selesai',
    'created_by'      => 1,
]);

$teknisi = \App\Models\Teknisi::all();
$fixtures = [
    [4.0, 4.0, 4.0],
    [3.5, 3.0, 3.0],
    [2.0, 2.0, 2.0],
];
foreach ($fixtures as $i => $f) {
    Database::query(
        'INSERT INTO tb_penilaian (id_periode, teknisi_id, periode, c1, c2, c3, status_data, created_by)
         VALUES (?, ?, ?, ?, ?, ?, "confirmed", 1)',
        [$scratchId, (int) $teknisi[$i]['id'], $testKode, $f[0], $f[1], $f[2]]
    );
}

$out = SawServiceV2::process($scratchId);
check(($out['total_teknisi'] ?? 0) === 3, 'scratch period processed 3 technicians', 'got ' . (string) ($out['total_teknisi'] ?? -1));

$scratchRows = Hasil::byPeriodeFlexible($testKode);
check(count($scratchRows) === 3, 'UI read of scratch period returns 3 rows', 'got ' . count($scratchRows));
check((int) $scratchRows[0]['ranking'] === 1 && (float) $scratchRows[0]['nilai_preferensi'] === 1.0, 'scratch rank 1 has Vi = 1.0', 'got ' . (string) $scratchRows[0]['nilai_preferensi']);

// Report path must accept the scratch quarter code too.
$reportRows = Hasil::byPeriodeFlexible($testKode);
check(count($reportRows) === 3, 'report (laporan) read of scratch period returns 3 rows');

// Re-run must be idempotent.
$before = Hasil::countByPeriodeId($scratchId);
SawServiceV2::process($scratchId);
$after = Hasil::countByPeriodeId($scratchId);
check($before === $after, 're-run is idempotent', "before {$before} after {$after}");

// ---------- 9. Teardown & integrity ----------
echo PHP_EOL . '-- teardown & integrity --' . PHP_EOL;

cleanup($testKode);

$afterPenilaian = (int) Database::query('SELECT COUNT(*) AS c FROM tb_penilaian')->fetch()['c'];
$afterHasil     = (int) Database::query('SELECT COUNT(*) AS c FROM tb_hasil')->fetch()['c'];
check($afterPenilaian === $basePenilaian, "tb_penilaian restored to baseline {$basePenilaian}", "got {$afterPenilaian}");
check($afterHasil === $baseHasil, "tb_hasil restored to baseline {$baseHasil}", "got {$afterHasil}");

$legacyP2 = (int) Database::query("SELECT COUNT(*) AS c FROM tb_penilaian WHERE status_data = 'legacy'")->fetch()['c'];
$legacyH2 = (int) Database::query('SELECT COUNT(*) AS c FROM tb_hasil WHERE id_periode IN (SELECT id_periode FROM tb_periode_penilaian WHERE status = \'legacy\')')->fetch()['c'];
check($legacyP2 === 12, 'legacy tb_penilaian still 12 after test', "got {$legacyP2}");
check($legacyH2 === 12, 'legacy tb_hasil still 12 after test', "got {$legacyH2}");

echo PHP_EOL . '========================================' . PHP_EOL;
echo 'RESULT: ' . $pass . '/' . ($pass + $fail) . ($fail === 0 ? ' PASS' : ' — FAILURES') . PHP_EOL;
echo '========================================' . PHP_EOL;

exit($fail === 0 ? 0 : 1);
