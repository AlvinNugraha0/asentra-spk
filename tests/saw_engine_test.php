<?php
// ASENTRA SPK — SAW engine unit tests (pure logic, no database)

declare(strict_types=1);

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../app/helpers/format.php';
require_once __DIR__ . '/../app/services/SawEngine.php';

use App\Services\SawEngine;

$weights = ['C1' => 0.30, 'C2' => 0.40, 'C3' => 0.30];
$results = [];

function record(array &$results, string $name, bool $ok, string $detail = ''): void
{
    $results[] = ['name' => $name, 'ok' => $ok, 'detail' => $detail];
    echo ($ok ? 'OK' : 'FAIL') . ': ' . $name . ($detail ? ' — ' . $detail : '') . PHP_EOL;
}

// 1. Decision matrix construction
$evaluations = [
    ['id' => 1, 'teknisi_id' => 1, 'kode_teknisi' => 'A1', 'nama' => 'Toni', 'periode' => '2026-08', 'c1' => 4, 'c2' => 4, 'c3' => 4],
    ['id' => 2, 'teknisi_id' => 2, 'kode_teknisi' => 'A2', 'nama' => 'Apip', 'periode' => '2026-08', 'c1' => 3, 'c2' => 4, 'c3' => 3],
];
$rows = SawEngine::calculate($evaluations, $weights);
record($results, 'decision matrix contains original values', $rows[0]['c1'] === 4 && $rows[0]['c2'] === 4 && $rows[0]['c3'] === 4);
record($results, 'decision matrix preserves technician metadata', $rows[0]['kode_teknisi'] === 'A1' && $rows[0]['nama'] === 'Toni');

// 2. Maximum value calculation
record($results, 'maximum values computed', $rows[0]['max_c1'] === 4.0 && $rows[0]['max_c2'] === 4.0 && $rows[0]['max_c3'] === 4.0);

// 3. Benefit normalization: Toni = 4/4 = 1.0
record($results, 'benefit normalization', abs($rows[0]['normal_c1'] - 1.0) < 0.0001 && abs($rows[1]['normal_c1'] - 0.75) < 0.0001);

// 4-6. Weighted contributions
record($results, 'C1 weighting', abs($rows[0]['kontribusi_c1'] - 0.30) < 0.0001);
record($results, 'C2 weighting', abs($rows[0]['kontribusi_c2'] - 0.40) < 0.0001);
record($results, 'C3 weighting', abs($rows[0]['kontribusi_c3'] - 0.30) < 0.0001);

// 7. Preference value
record($results, 'preference value', abs($rows[0]['nilai_preferensi'] - 1.0) < 0.0001);

// 8. Descending ranking
record($results, 'descending ranking', $rows[0]['ranking'] === 1 && $rows[1]['ranking'] === 2);

// 9. Tie score behavior
$tieEvals = [
    ['id' => 1, 'teknisi_id' => 1, 'kode_teknisi' => 'A1', 'nama' => 'Toni', 'periode' => '2026-08', 'c1' => 4, 'c2' => 4, 'c3' => 4],
    ['id' => 2, 'teknisi_id' => 2, 'kode_teknisi' => 'A2', 'nama' => 'Apip', 'periode' => '2026-08', 'c1' => 4, 'c2' => 4, 'c3' => 4],
];
$rows = SawEngine::calculate($tieEvals, $weights);
record($results, 'tie score keeps equal preference values', abs($rows[0]['nilai_preferensi'] - $rows[1]['nilai_preferensi']) < 0.0001);
record($results, 'tie score uses neutral technical ordering', $rows[0]['teknisi_id'] === 1 && $rows[1]['teknisi_id'] === 2);

// 10. Incomplete data (empty input)
$empty = SawEngine::calculate([], $weights);
record($results, 'empty evaluations return empty result', empty($empty));

// 11. Selected-period isolation (different values, same engine call)
$mixed = [
    ['id' => 1, 'teknisi_id' => 1, 'kode_teknisi' => 'A1', 'nama' => 'Toni', 'periode' => '2026-08', 'c1' => 4, 'c2' => 4, 'c3' => 4],
    ['id' => 2, 'teknisi_id' => 2, 'kode_teknisi' => 'A2', 'nama' => 'Apip', 'periode' => '2026-09', 'c1' => 2, 'c2' => 2, 'c3' => 2],
];
$rows = SawEngine::calculate($mixed, $weights);
record($results, 'engine does not isolate periods internally', count($rows) === 2);

// 12. All values equal
$equal = [
    ['id' => 1, 'teknisi_id' => 1, 'kode_teknisi' => 'A1', 'nama' => 'Toni', 'periode' => '2026-08', 'c1' => 2, 'c2' => 2, 'c3' => 2],
    ['id' => 2, 'teknisi_id' => 2, 'kode_teknisi' => 'A2', 'nama' => 'Apip', 'periode' => '2026-08', 'c1' => 2, 'c2' => 2, 'c3' => 2],
];
$rows = SawEngine::calculate($equal, $weights);
record($results, 'all equal values produce equal preference', abs($rows[0]['nilai_preferensi'] - $rows[1]['nilai_preferensi']) < 0.0001);

// 13. Maximum value = 1
$lowMax = [
    ['id' => 1, 'teknisi_id' => 1, 'kode_teknisi' => 'A1', 'nama' => 'Toni', 'periode' => '2026-08', 'c1' => 1, 'c2' => 1, 'c3' => 1],
];
$rows = SawEngine::calculate($lowMax, $weights);
record($results, 'max value 1 normalization', abs($rows[0]['normal_c1'] - 1.0) < 0.0001);

// 14. Maximum value = 4
$highMax = [
    ['id' => 1, 'teknisi_id' => 1, 'kode_teknisi' => 'A1', 'nama' => 'Toni', 'periode' => '2026-08', 'c1' => 4, 'c2' => 4, 'c3' => 4],
];
$rows = SawEngine::calculate($highMax, $weights);
record($results, 'max value 4 normalization', abs($rows[0]['normal_c1'] - 1.0) < 0.0001);

// 15. Weight validation
$bad = ['C1' => 0.5, 'C2' => 0.4, 'C3' => 0.3];
try {
    SawEngine::validateWeights($bad);
    record($results, 'invalid weights rejected', false);
} catch (\Throwable $e) {
    record($results, 'invalid weights rejected', true);
}

$passed = count(array_filter($results, fn($r) => $r['ok']));
$total = count($results);
echo PHP_EOL . "SUMMARY: {$passed}/{$total} passed" . PHP_EOL;

$failed = array_filter($results, fn($r) => !$r['ok']);
foreach ($failed as $f) {
    echo 'FAILED: ' . $f['name'] . PHP_EOL;
}

exit($failed ? 1 : 0);
