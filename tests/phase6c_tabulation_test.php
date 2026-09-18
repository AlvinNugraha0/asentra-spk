<?php
// ASENTRA SPK — Phase 6C: Owner Tabulation Test
// Verifies Penilaian::tabulasiByPeriode() returns database-driven tabulation
// with required columns: No, Teknisi, C1, C2, C3, Status, Bulan Data, Warning.
//
// SAFETY: creates its OWN isolated period (kode 'TEST6C-2026-Q1') and removes it
// in teardown. Legacy periods, tb_penilaian=12, tb_hasil=12 untouched.

declare(strict_types=1);

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/models/PeriodePenilaian.php';
require_once __DIR__ . '/../app/models/Penilaian.php';
require_once __DIR__ . '/../app/models/Teknisi.php';

use App\Core\Database;
use App\Models\Penilaian;
use App\Models\PeriodePenilaian;
use App\Models\Teknisi;

$pass = 0;
$fail = 0;
$testKode = 'TEST6C-2026-Q1';
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

echo '========================================' . PHP_EOL;
echo 'PHASE 6C — OWNER TABULATION TEST' . PHP_EOL;
echo '========================================' . PHP_EOL;

$basePenilaian = (int) Database::query('SELECT COUNT(*) AS c FROM tb_penilaian')->fetch()['c'];
echo 'Baseline tb_penilaian = ' . $basePenilaian . PHP_EOL . PHP_EOL;

// Remove leftover from previous aborted run
$leftover = Database::query('SELECT id_periode FROM tb_periode_penilaian WHERE kode_periode = ?', [$testKode])->fetch();
if ($leftover) {
    Database::query('DELETE FROM tb_penilaian WHERE id_periode = ?', [(int) $leftover['id_periode']]);
    Database::query('DELETE FROM tb_periode_penilaian WHERE id_periode = ?', [(int) $leftover['id_periode']]);
}

// ---- Setup: isolated test period + 3 evaluations in tb_penilaian ----
$testPeriodeId = PeriodePenilaian::create([
    'kode_periode'    => $testKode,
    'nama_periode'    => 'TEST Phase 6C Q1 2026',
    'tanggal_mulai'   => '2026-01-01',
    'tanggal_selesai' => '2026-03-31',
    'status'          => 'draft',
    'created_by'      => 1,
]);

$teknisi = Teknisi::all();
$sample = array_slice($teknisi, 0, 3);

// Known fixture values so we can assert exact derived results.
$fixtures = [
    ['c1' => 4.0,  'c2' => 3.0,  'c3' => 2.0,  'status' => 'calculated', 'b1' => 3, 'b2' => 3, 'b3' => 3, 'warning' => null],
    ['c1' => 3.0,  'c2' => 2.0,  'c3' => 1.0,  'status' => 'partial',    'b1' => 2, 'b2' => 3, 'b3' => 1, 'warning' => 'C1 hanya 2 bulan'],
    ['c1' => 2.5,  'c2' => 3.5,  'c3' => 4.0,  'status' => 'confirmed',  'b1' => 3, 'b2' => 3, 'b3' => 3, 'warning' => null],
];

foreach ($sample as $i => $t) {
    Database::query(
        'INSERT INTO tb_penilaian
            (id_periode, teknisi_id, periode, c1, c2, c3, status_data,
             jumlah_bulan_c1, jumlah_bulan_c2, jumlah_bulan_c3, warning, created_by)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
        [
            $testPeriodeId,
            (int) $t['id'],
            '2026-Q1',
            $fixtures[$i]['c1'],
            $fixtures[$i]['c2'],
            $fixtures[$i]['c3'],
            $fixtures[$i]['status'],
            $fixtures[$i]['b1'],
            $fixtures[$i]['b2'],
            $fixtures[$i]['b3'],
            $fixtures[$i]['warning'],
            1,
        ]
    );
}

// ---------------------------------------------------------------- TESTS
$tab = Penilaian::tabulasiByPeriode($testPeriodeId);

check(count($tab) === 3, 'Returns one row per technician', 'count=' . count($tab));

// Required columns present
$required = ['no', 'kode_teknisi', 'nama_teknisi', 'c1', 'c2', 'c3', 'status_data', 'jumlah_bulan_c1', 'jumlah_bulan_c2', 'jumlah_bulan_c3', 'warning'];
$first = $tab[0] ?? [];
$missing = [];
foreach ($required as $col) {
    if (!array_key_exists($col, $first)) {
        $missing[] = $col;
    }
}
check($missing === [], 'All required columns present (No, Teknisi, C1, C2, C3, Status, Bulan, Warning)', 'missing=' . implode(',', $missing));

// No (sequence 1..N)
$nos = array_map(static fn (array $r): int => $r['no'], $tab);
check($nos === [1, 2, 3], 'NO column sequential 1..N', 'nos=' . implode(',', $nos));

// Values match fixture (database-driven, not hardcoded)
check(abs($tab[0]['c1'] - 4.0) < 0.0001 && abs($tab[0]['c2'] - 3.0) < 0.0001 && abs($tab[0]['c3'] - 2.0) < 0.0001, 'Row 1 C1/C2/C3 match DB fixture');
check(abs($tab[2]['c1'] - 2.5) < 0.0001 && abs($tab[2]['c2'] - 3.5) < 0.0001 && abs($tab[2]['c3'] - 4.0) < 0.0001, 'Row 3 C1/C2/C3 match DB fixture');
check($tab[0]['status_data'] === 'calculated' && $tab[1]['status_data'] === 'partial' && $tab[2]['status_data'] === 'confirmed', 'Status column matches DB fixture');

// Bulan data
check($tab[1]['jumlah_bulan_c1'] === 2 && $tab[1]['jumlah_bulan_c2'] === 3 && $tab[1]['jumlah_bulan_c3'] === 1, 'Row 2 bulan data matches DB fixture (2/3/1)');

// Warning carried through
check($tab[1]['warning'] === 'C1 hanya 2 bulan', 'Row 2 warning text preserved', 'got ' . (string) $tab[1]['warning']);
check($tab[0]['warning'] === null, 'Row 1 warning null preserved');

// Contribution math: Vi = 0.30*C1 + 0.40*C2 + 0.30*C3
$expectedVi1 = 0.30 * 4.0 + 0.40 * 3.0 + 0.30 * 2.0; // 1.2 + 1.2 + 0.6 = 3.0
$expectedVi2 = 0.30 * 3.0 + 0.40 * 2.0 + 0.30 * 1.0; // 0.9 + 0.8 + 0.3 = 2.0
$expectedVi3 = 0.30 * 2.5 + 0.40 * 3.5 + 0.30 * 4.0; // 0.75 + 1.4 + 1.2 = 3.35
check(abs($tab[0]['vi'] - $expectedVi1) < 0.0001, 'Vi row 1 = 0.30*4 + 0.40*3 + 0.30*2 = 3.0000', 'got ' . number_format($tab[0]['vi'], 4));
check(abs($tab[1]['vi'] - $expectedVi2) < 0.0001, 'Vi row 2 = 0.30*3 + 0.40*2 + 0.30*1 = 2.0000', 'got ' . number_format($tab[1]['vi'], 4));
check(abs($tab[2]['vi'] - $expectedVi3) < 0.0001, 'Vi row 3 = 0.30*2.5 + 0.40*3.5 + 0.30*4 = 3.3500', 'got ' . number_format($tab[2]['vi'], 4));
check(abs($tab[0]['kontribusi_c1'] - 1.2) < 0.0001, 'kontribusi_c1 row 1 = 1.2', 'got ' . number_format($tab[0]['kontribusi_c1'], 4));

// Empty period returns []
$emptyTab = Penilaian::tabulasiByPeriode(999999);
check($emptyTab === [], 'Nonexistent period returns empty array', 'count=' . count($emptyTab));

// Teknisi identity preserved
check($tab[0]['kode_teknisi'] === $sample[0]['kode_teknisi'] && $tab[0]['nama_teknisi'] === $sample[0]['nama'], 'Teknisi identity (kode + nama) from DB join', $tab[0]['kode_teknisi']);

// ---------------------------------------------- TEARDOWN
Database::query('DELETE FROM tb_penilaian WHERE id_periode = ?', [$testPeriodeId]);
Database::query('DELETE FROM tb_periode_penilaian WHERE id_periode = ?', [$testPeriodeId]);

$afterPenilaian = (int) Database::query('SELECT COUNT(*) AS c FROM tb_penilaian')->fetch()['c'];
check($afterPenilaian === $basePenilaian, 'tb_penilaian restored to baseline', $afterPenilaian . ' vs ' . $basePenilaian);

$gone = (int) Database::query('SELECT COUNT(*) AS c FROM tb_periode_penilaian WHERE kode_periode = ?', [$testKode])->fetch()['c'];
check($gone === 0, 'Test period removed', 'remaining=' . $gone);

// Legacy untouched
$legacy = (int) Database::query('SELECT COUNT(*) AS c FROM tb_penilaian WHERE status_data = "legacy"')->fetch()['c'];
check($legacy === 12, 'Legacy tb_penilaian still 12', 'got ' . $legacy);
$legacyHasil = (int) Database::query('SELECT COUNT(*) AS c FROM tb_hasil h JOIN tb_periode_penilaian p ON p.id_periode = h.id_periode WHERE p.status = "legacy"')->fetch()['c'];
check($legacyHasil === 12, 'Legacy tb_hasil still 12', 'got ' . $legacyHasil);

echo PHP_EOL . '========================================' . PHP_EOL;
echo 'RESULT: ' . $pass . '/' . ($pass + $fail) . ' PASS' . PHP_EOL;
echo '========================================' . PHP_EOL;

exit($fail === 0 ? 0 : 1);
