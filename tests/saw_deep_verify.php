<?php
// Phase 5 deep verification: golden dataset + max-value precision + persistence

declare(strict_types=1);

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/helpers/format.php';
require_once __DIR__ . '/../app/models/Kriteria.php';
require_once __DIR__ . '/../app/models/Penilaian.php';
require_once __DIR__ . '/../app/models/PeriodePenilaian.php';
require_once __DIR__ . '/../app/models/Hasil.php';
require_once __DIR__ . '/../app/services/SawEngine.php';
require_once __DIR__ . '/../app/services/SawService.php';

use App\Core\Database;
use App\Models\Hasil;
use App\Services\SawService;

$results = [];
function rec(array &$results, string $name, bool $ok, string $detail = ''): void {
    $results[] = ['name' => $name, 'ok' => $ok, 'detail' => $detail];
    echo ($ok ? 'OK' : 'FAIL') . ': ' . $name . ($detail ? ' — ' . $detail : '') . PHP_EOL;
}
function close(float $a, float $b, float $eps = 0.000001): bool {
    return abs($a - $b) < $eps;
}

// Golden dataset: rank, name, Vi (PRD expected)
$golden = [
    1 => ['Toni', 1.000],
    2 => ['Aris', 0.925],
    3 => ['Rahmat Hidayat', 0.900],
    4 => ['Apip', 0.850],
    5 => ['Wanto', 0.750],
    6 => ['Heri', 0.750],
    7 => ['IMADE', 0.700],
    8 => ['Ahmad Sahudin', 0.675],
    9 => ['Agus Supriyanto', 0.600],
    10 => ['Asep', 0.575],
];

// 1. Process and read persisted rows
SawService::process('2026-08');
$rows = Hasil::byPeriode('2026-08');

// 2. Golden dataset exact verification against PERSISTED values
$goldenOk = count($rows) === 10;
foreach ($golden as $rank => [$name, $vi]) {
    $r = $rows[$rank - 1] ?? null;
    if ($r === null || $r['ranking'] !== $rank || $r['nama_teknisi'] !== $name || !close((float) $r['nilai_preferensi'], $vi)) {
        $goldenOk = false;
        break;
    }
}
rec($results, 'golden dataset exact (rank+name+Vi) vs persisted tb_hasil', $goldenOk);

// 3. Verify ranking is strictly Vi-descending (allow tie at 5/6)
$descOk = true;
for ($i = 1; $i < count($rows); $i++) {
    $prev = (float) $rows[$i - 1]['nilai_preferensi'];
    $cur = (float) $rows[$i]['nilai_preferensi'];
    if ($cur > $prev + 0.000001) {
        $descOk = false;
        break;
    }
}
rec($results, 'ranking strictly Vi-descending', $descOk);

// 4. Tie at rank 5/6 (Wanto/Heri both 0.750) keeps equal Vi
$tieOk = close((float) $rows[4]['nilai_preferensi'], 0.750) && close((float) $rows[5]['nilai_preferensi'], 0.750);
rec($results, 'tie rank 5/6 both 0.750', $tieOk);

// 5. Max-value precision: check that C1/max derivation is exact for a full-max technician (Toni, C1=4, R1=1.0)
$toni = $rows[0];
$maxC1 = $toni['c1'] / (float) $toni['nilai_c1_normalisasi'];
rec($results, 'max C1 derived = 4.0 (Toni)', close($maxC1, 4.0), "maxC1={$maxC1}");

// 6. Verify stored normalized values are exact reciprocals (precision check across all rows)
$precOk = true;
$detail = '';
foreach ($rows as $r) {
    // Recompute normalization from originals + global max; compare to stored
    $maxC1 = 4.0; $maxC2 = 4.0; $maxC3 = 4.0; // from golden dataset (seed max = 4 for all)
    $expR1 = $r['c1'] / $maxC1;
    $expR2 = $r['c2'] / $maxC2;
    $expR3 = $r['c3'] / $maxC3;
    if (!close((float) $r['nilai_c1_normalisasi'], $expR1, 0.000001) ||
        !close((float) $r['nilai_c2_normalisasi'], $expR2, 0.000001) ||
        !close((float) $r['nilai_c3_normalisasi'], $expR3, 0.000001)) {
        $precOk = false;
        $detail = "row {$r['ranking']} stored={$r['nilai_c1_normalisasi']} expected={$expR1}";
        break;
    }
}
rec($results, 'stored normalized values match exact division (6dp)', $precOk, $detail);

// 7. Verify contributions recompute to Vi exactly
$contribOk = true;
foreach ($rows as $r) {
    $sum = (float) $r['kontribusi_c1'] + (float) $r['kontribusi_c2'] + (float) $r['kontribusi_c3'];
    if (!close($sum, (float) $r['nilai_preferensi'], 0.000001)) {
        $contribOk = false;
        $detail = "row {$r['ranking']} sum={$sum} vi={$r['nilai_preferensi']}";
        break;
    }
}
rec($results, 'kontribusi sum == Vi exactly', $contribOk, $detail);

// 8. Cross-period isolation at DB level
SawService::process('2026-08'); // re-run
$count08 = Hasil::countByPeriode('2026-08');
$count07 = Hasil::countByPeriode('2026-07');
rec($results, 're-run leaves 2026-08 at 10 rows', $count08 === 10, "count={$count08}");
rec($results, '2026-07 untouched by 2026-08 re-run', $count07 === 0, "count={$count07}");

$passed = count(array_filter($results, fn($r) => $r['ok']));
$total = count($results);
echo PHP_EOL . "DEEP VERIFY SUMMARY: {$passed}/{$total} passed" . PHP_EOL;
foreach ($results as $r) {
    if (!$r['ok']) echo 'FAILED: ' . $r['name'] . PHP_EOL;
}
exit($passed === $total ? 0 : 1);
