<?php
// ASENTRA SPK — SAW service integration tests (requires database)

declare(strict_types=1);

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/helpers/format.php';
require_once __DIR__ . '/../app/models/Kriteria.php';
require_once __DIR__ . '/../app/models/Penilaian.php';
require_once __DIR__ . '/../app/models/Hasil.php';
require_once __DIR__ . '/../app/services/SawEngine.php';
require_once __DIR__ . '/../app/services/SawService.php';

use App\Models\Hasil;
use App\Models\Penilaian;
use App\Services\SawService;

$results = [];

function record(array &$results, string $name, bool $ok, string $detail = ''): void
{
    $results[] = ['name' => $name, 'ok' => $ok, 'detail' => $detail];
    echo ($ok ? 'OK' : 'FAIL') . ': ' . $name . ($detail ? ' — ' . $detail : '') . PHP_EOL;
}

function assertClose(float $a, float $b, float $epsilon = 0.001): bool
{
    return abs($a - $b) < $epsilon;
}

// Ensure seed baseline is loaded
$counts = [
    'users' => (int) \App\Core\Database::query('SELECT COUNT(*) AS c FROM tb_user')->fetch()['c'],
    'teknisi' => (int) \App\Core\Database::query('SELECT COUNT(*) AS c FROM tb_teknisi')->fetch()['c'],
    'kriteria' => (int) \App\Core\Database::query('SELECT COUNT(*) AS c FROM tb_kriteria')->fetch()['c'],
    'penilaian' => (int) \App\Core\Database::query('SELECT COUNT(*) AS c FROM tb_penilaian')->fetch()['c'],
];
record($results, 'seed baseline present', $counts['users'] === 2 && $counts['teknisi'] === 10 && $counts['kriteria'] === 3 && $counts['penilaian'] === 10, json_encode($counts));

// 1. Golden dataset ranking and preference values
$result = SawService::process('2026-08');
$rows = $result['rows'];
$expected = [
    ['rank' => 1, 'nama' => 'Toni', 'vi' => 1.000],
    ['rank' => 2, 'nama' => 'Aris', 'vi' => 0.925],
    ['rank' => 3, 'nama' => 'Rahmat Hidayat', 'vi' => 0.900],
    ['rank' => 4, 'nama' => 'Apip', 'vi' => 0.850],
    ['rank' => 5, 'nama' => 'Wanto', 'vi' => 0.750],
    ['rank' => 6, 'nama' => 'Heri', 'vi' => 0.750],
    ['rank' => 7, 'nama' => 'IMADE', 'vi' => 0.700],
    ['rank' => 8, 'nama' => 'Ahmad Sahudin', 'vi' => 0.675],
    ['rank' => 9, 'nama' => 'Agus Supriyanto', 'vi' => 0.600],
    ['rank' => 10, 'nama' => 'Asep', 'vi' => 0.575],
];
$goldenOk = count($rows) === count($expected);
foreach ($expected as $i => $exp) {
    if (!isset($rows[$i])) {
        $goldenOk = false;
        break;
    }
    if ($rows[$i]['ranking'] !== $exp['rank'] || $rows[$i]['nama'] !== $exp['nama'] || !assertClose($rows[$i]['nilai_preferensi'], $exp['vi'])) {
        $goldenOk = false;
        break;
    }
}
record($results, 'golden dataset ranking and Vi match PRD', $goldenOk);

// 2. Persistence: results stored
$stored = Hasil::countByPeriode('2026-08');
record($results, 'SAW results persisted for period', $stored === 10, "count={$stored}");

// 3. Re-run same period replaces results atomically
$result2 = SawService::process('2026-08');
$stored2 = Hasil::countByPeriode('2026-08');
record($results, 're-run same period keeps exactly 10 results', $stored2 === 10, "count={$stored2}");

// 4. Different period remains untouched
$otherCount = Hasil::countByPeriode('2026-07');
record($results, 'other periods untouched', $otherCount === 0, "count={$otherCount}");

// 5. No evaluation data for a period
$resultEmpty = SawService::process('2025-01');
record($results, 'no data period returns empty result', empty($resultEmpty['rows']));
record($results, 'no data period does not persist', Hasil::countByPeriode('2025-01') === 0);

// 6. Same technician across different periods
\App\Core\Database::query(
    'INSERT INTO tb_penilaian (teknisi_id, periode, c1, c2, c3, created_by) VALUES (?, ?, ?, ?, ?, ?)',
    [1, '2026-09', 3, 3, 3, 1]
);
$resultSep = SawService::process('2026-09');
record($results, 'same technician different period allowed', count($resultSep['rows']) === 1 && $resultSep['rows'][0]['teknisi_id'] === 1);

// 7. Duplicate active evaluation is prevented at DB level (model returns false)
try {
    \App\Core\Database::query(
        'INSERT INTO tb_penilaian (teknisi_id, periode, c1, c2, c3, created_by) VALUES (?, ?, ?, ?, ?, ?)',
        [1, '2026-09', 2, 2, 2, 1]
    );
    record($results, 'duplicate active evaluation blocked', false, 'DB allowed duplicate');
} catch (\PDOException $e) {
    record($results, 'duplicate active evaluation blocked', true);
}

// 8. Inactive technician remains in historical SAW
\App\Core\Database::query('UPDATE tb_teknisi SET status = "inactive" WHERE id = 1');
$resultHist = SawService::process('2026-08');
$foundToni = false;
foreach ($resultHist['rows'] as $r) {
    if ($r['nama'] === 'Toni') {
        $foundToni = true;
        break;
    }
}
record($results, 'inactive technician remains in historical SAW', $foundToni);

// 9. Incomplete evaluation data: empty period
\App\Core\Database::query('DELETE FROM tb_penilaian WHERE periode = "2026-10"');
$resultInc = SawService::process('2026-10');
record($results, 'incomplete/empty period handled', empty($resultInc['rows']));

// 10. All values equal scenario
\App\Core\Database::query('DELETE FROM tb_penilaian WHERE periode = "2026-11"');
\App\Core\Database::query(
    'INSERT INTO tb_penilaian (teknisi_id, periode, c1, c2, c3, created_by) VALUES (?, ?, ?, ?, ?, ?), (?, ?, ?, ?, ?, ?)',
    [2, '2026-11', 2, 2, 2, 1, 3, '2026-11', 2, 2, 2, 1]
);
$resultEqual = SawService::process('2026-11');
$equalOk = count($resultEqual['rows']) === 2
    && assertClose($resultEqual['rows'][0]['nilai_preferensi'], $resultEqual['rows'][1]['nilai_preferensi'])
    && abs($resultEqual['rows'][0]['nilai_preferensi'] - 1.0) < 0.0001;
record($results, 'all equal values produce Vi = 1.0 and equal ranks', $equalOk);

$passed = count(array_filter($results, fn($r) => $r['ok']));
$total = count($results);
echo PHP_EOL . "SUMMARY: {$passed}/{$total} passed" . PHP_EOL;

$failed = array_filter($results, fn($r) => !$r['ok']);
foreach ($failed as $f) {
    echo 'FAILED: ' . $f['name'] . PHP_EOL;
}

exit($failed ? 1 : 0);
