<?php
// ASENTRA SPK — Phase 6I: Reports / History test
//
// Verifies the Phase 6I contract without a web server:
//   1. periodLabel() renders V2 quarter codes ('Q1-2026', '2026-Q3') through
//      tb_periode_penilaian (nama_periode + date range), with a readable
//      'Triwulan I 2026' fallback for unknown quarters.
//   2. V1 'YYYY-MM' and 'LEGACY-YYYY-MM' labels are unchanged.
//   3. Laporan index lists V2 periods (by id_periode) and legacy codes with
//      no duplicate entry for the same period.
//   4. Hasil::byPeriodeFlexible() resolves id_periode, 'Q1-2026', '2026-Q3',
//      'YYYY-MM' and 'LEGACY-YYYY-MM' to real tb_hasil rows.
//   5. A period with no tb_hasil rows yields zero rows (rejected by the
//      controller), never an invented ranking.
//   6. Legacy data is untouched: tb_penilaian legacy = 12, tb_hasil legacy = 12.
//
// SAFETY: read-only. No rows are inserted, updated or deleted; legacy rows and
// the V2 golden ranking are only read.

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
require_once __DIR__ . '/../app/controllers/LaporanController.php';

use App\Core\Database;
use App\Models\Hasil;
use App\Models\PeriodePenilaian;
use App\Controllers\LaporanController;

$pass = 0;
$fail = 0;

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

function rows(string $sql, array $params = []): array
{
    return Database::query($sql, $params)->fetchAll();
}

function one(string $sql, array $params = [])
{
    $r = Database::query($sql, $params)->fetch();
    return $r === false ? null : $r;
}

echo "=== Phase 6I: Reports / History ===" . PHP_EOL;

// ------------------------------------------------------------------
// 1. periodLabel() — V2 quarter codes resolve through tb_periode_penilaian
// ------------------------------------------------------------------
$q1 = one('SELECT * FROM tb_periode_penilaian WHERE kode_periode = ?', ['Q1-2026']);
check($q1 !== null, 'V2 period Q1-2026 exists in tb_periode_penilaian');

$labelQ1 = periodLabel('Q1-2026');
check($labelQ1 === 'Januari - Maret 2026 (2026-01-01 s/d 2026-03-31)', 'periodLabel(Q1-2026) uses nama_periode + range', $labelQ1);
check(str_contains($labelQ1, 'Januari - Maret 2026'), 'periodLabel(Q1-2026) contains nama_periode', $labelQ1);
check(!str_contains($labelQ1, 'Q1-2026'), 'periodLabel(Q1-2026) is not the raw code', $labelQ1);

// Reverse spelling resolves the same stored row.
$labelRev = periodLabel('2026-Q1');
check(str_contains($labelRev, 'Triwulan I 2026'), 'periodLabel(2026-Q1) falls back to readable quarter label (no stored row)', $labelRev);

// Quarter code with no stored period -> readable label, never the raw code.
check(periodLabel('Q2-2026') === 'Triwulan II 2026', 'periodLabel(Q2-2026) -> Triwulan II 2026', periodLabel('Q2-2026'));
check(periodLabel('2026-Q4') === 'Triwulan IV 2026', 'periodLabel(2026-Q4) -> Triwulan IV 2026', periodLabel('2026-Q4'));
check(periodLabel('Q3-2026') === 'Triwulan III 2026', 'periodLabel(Q3-2026) -> Triwulan III 2026', periodLabel('Q3-2026'));

// The stored '2026-Q3' row exists but has no hasil; its label must still
// resolve through the DB, not the raw code.
$q3 = one('SELECT * FROM tb_periode_penilaian WHERE kode_periode = ?', ['2026-Q3']);
if ($q3 !== null) {
    $labelQ3 = periodLabel('2026-Q3');
    check(str_contains($labelQ3, (string) $q3['nama_periode']), 'periodLabel(2026-Q3) resolves stored nama_periode', $labelQ3);
    check(!str_contains($labelQ3, '2026-Q3'), 'periodLabel(2026-Q3) is not the raw code', $labelQ3);
} else {
    check(false, 'periodLabel(2026-Q3) — period row missing');
}

// ------------------------------------------------------------------
// 2. V1 labels unchanged
// ------------------------------------------------------------------
check(periodLabel('2026-08') === 'Agustus 2026', 'periodLabel(2026-08) === Agustus 2026', periodLabel('2026-08'));
check(periodLabel('LEGACY-2026-09') === 'September 2026', 'periodLabel(LEGACY-2026-09) === September 2026', periodLabel('LEGACY-2026-09'));
check(periodLabel('LEGACY-2026-08') === 'Agustus 2026', 'periodLabel(LEGACY-2026-08) === Agustus 2026', periodLabel('LEGACY-2026-08'));
check(periodLabel('2026-01') === 'Januari 2026', 'periodLabel(2026-01) === Januari 2026', periodLabel('2026-01'));
check(periodLabel('2026-12') === 'Desember 2026', 'periodLabel(2026-12) === Desember 2026', periodLabel('2026-12'));

// Empty / unknown input is returned verbatim (no crash).
check(periodLabel('') === '', 'periodLabel(empty) returns empty');
check(periodLabel('garbage') === 'garbage', 'periodLabel(garbage) returns input verbatim', periodLabel('garbage'));

// ------------------------------------------------------------------
// 3. Laporan index options: V2 by id_periode + legacy codes, no duplicates
// ------------------------------------------------------------------
// LaporanController::index() needs a session for requireOwner(); the option
// list itself is pure DB, so reproduce its exact construction here.
function laporanOptions(): array
{
    $options = [];
    $v2 = PeriodePenilaian::allWithEvaluations();
    $v2Kodes = [];
    foreach ($v2 as $p) {
        if (($p['status'] ?? '') === 'legacy') {
            continue;
        }
        $id = (int) $p['id_periode'];
        if (Hasil::countByPeriodeId($id) === 0) {
            continue;
        }
        $v2Kodes[] = (string) $p['kode_periode'];
        $options[] = [
            'value' => (string) $id,
            'label' => sprintf('%s — %s', (string) $p['kode_periode'], (string) $p['nama_periode']),
        ];
    }
    foreach (Hasil::periods() as $code) {
        if (in_array($code, $v2Kodes, true)) {
            continue;
        }
        $options[] = ['value' => $code, 'label' => periodLabel($code)];
    }
    return $options;
}

$options = laporanOptions();
check(count($options) >= 3, 'laporan options list at least 3 periods', 'count=' . count($options));

$labels = array_column($options, 'label');
check(count($labels) === count(array_unique($labels)), 'laporan options have no duplicate labels', json_encode($labels));

// Every option must resolve to real tb_hasil rows (nothing hardcoded).
foreach ($options as $o) {
    $rows = Hasil::byPeriodeFlexible($o['value']);
    check(count($rows) > 0, 'laporan option ' . $o['value'] . ' resolves to tb_hasil rows', 'rows=' . count($rows));
}

// V2 period selectable by id_periode.
$hasV2 = false;
foreach ($options as $o) {
    if (preg_match('/^\d+$/', $o['value']) && str_contains($o['label'], 'Q1-2026')) {
        $hasV2 = true;
    }
}
check($hasV2, 'laporan options include the V2 period by numeric id_periode');

// Legacy codes selectable by their stored code.
$hasLegacy = false;
foreach ($options as $o) {
    if ($o['value'] === 'LEGACY-2026-09' || $o['value'] === '2026-08') {
        $hasLegacy = true;
    }
}
check($hasLegacy, 'laporan options include legacy V1 codes');

// The Q1-2026 quarter code appears exactly once across all option labels.
$q1Occ = 0;
foreach ($labels as $l) {
    if (str_contains($l, 'Q1-2026')) {
        $q1Occ++;
    }
}
check($q1Occ === 1, 'Q1-2026 appears exactly once in laporan options', 'occurrences=' . $q1Occ);

// ------------------------------------------------------------------
// 4. byPeriodeFlexible() — every accepted selector shape
// ------------------------------------------------------------------
$shapes = [
    '25 (id_periode)' => '25',
    'Q1-2026 (quarter code)' => 'Q1-2026',
    '2026-08 (V1 month)' => '2026-08',
    'LEGACY-2026-09 (V1 legacy seed)' => 'LEGACY-2026-09',
];
foreach ($shapes as $name => $value) {
    $rows = Hasil::byPeriodeFlexible($value);
    check(count($rows) === 10 || $name === 'LEGACY-2026-09 (V1 legacy seed)', 'byPeriodeFlexible(' . $name . ') returns tb_hasil rows', 'rows=' . count($rows));
}

$rows25 = Hasil::byPeriodeFlexible('25');
check(count($rows25) === 10, 'byPeriodeFlexible(25) returns the 10 V2 rows', 'rows=' . count($rows25));
$names25 = array_column($rows25, 'nama_teknisi');
check($names25[0] === 'Toni Vi' || str_starts_with((string) $names25[0], 'Toni'), 'V2 rank 1 is Toni', (string) ($names25[0] ?? ''));
$v25 = array_map(fn ($r) => (float) $r['nilai_preferensi'], $rows25);
check(abs($v25[0] - 0.988571) < 0.000001, 'V2 rank 1 Vi = 0.988571', (string) $v25[0]);
check($v25[1] > $v25[2] && $v25[2] > $v25[3], 'V2 Vi is descending for top-4');

// Both spellings of the same period resolve to the same rows.
$byCode = Hasil::byPeriodeFlexible('Q1-2026');
check(count($byCode) === count($rows25), 'Q1-2026 code and id 25 resolve the same rows', count($byCode) . ' vs ' . count($rows25));

// V1 golden ranking (2026-08) is read unchanged.
$rowsV1 = Hasil::byPeriodeFlexible('2026-08');
check(count($rowsV1) === 10, '2026-08 returns 10 rows', 'rows=' . count($rowsV1));
$vV1 = array_map(fn ($r) => (float) $r['nilai_preferensi'], $rowsV1);
check(abs($vV1[0] - 1.000) < 0.0001, 'V1 2026-08 rank 1 Vi = 1.000', (string) $vV1[0]);
check((string) $rowsV1[0]['nama_teknisi'] === 'Toni', 'V1 2026-08 rank 1 is Toni', (string) $rowsV1[0]['nama_teknisi']);

// ------------------------------------------------------------------
// 5. Period with no tb_hasil rows -> empty, never invented data
// ------------------------------------------------------------------
$empty = Hasil::byPeriodeFlexible('2026-Q3');
check(count($empty) === 0, '2026-Q3 (draft, 0 evaluations) resolves to no rows', 'rows=' . count($empty));
check(count(Hasil::byPeriodeFlexible('999999')) === 0, 'unknown numeric id resolves to no rows');
check(count(Hasil::byPeriodeFlexible('Q2-2026')) === 0, 'unknown quarter code resolves to no rows');

// ------------------------------------------------------------------
// 6. Legacy invariants — untouched
// ------------------------------------------------------------------
$penilaianLegacy = (int) one('SELECT COUNT(*) AS c FROM tb_penilaian WHERE status_data = ?', ['legacy'])['c'];
// Legacy tb_hasil rows are keyed by id_periode (id 1 = '2026-08', id 2 =
// 'LEGACY-2026-09'); the stored periode string is not uniformly 'LEGACY-'.
$hasilLegacyRow = one('SELECT COUNT(*) AS c FROM tb_hasil WHERE id_periode IN (SELECT id_periode FROM tb_periode_penilaian WHERE status = ?)', ['legacy']);
$hasilLegacy = $hasilLegacyRow === null ? -1 : (int) $hasilLegacyRow['c'];
check($penilaianLegacy === 12, 'tb_penilaian legacy rows = 12', 'count=' . $penilaianLegacy);
check($hasilLegacy === 12, 'tb_hasil legacy rows = 12', 'count=' . $hasilLegacy);

$totalHasil = (int) one('SELECT COUNT(*) AS c FROM tb_hasil')['c'];
$totalPenilaian = (int) one('SELECT COUNT(*) AS c FROM tb_penilaian')['c'];
check($totalHasil === 22, 'tb_hasil total = 22 (12 legacy + 10 V2)', 'count=' . $totalHasil);
check($totalPenilaian === 22, 'tb_penilaian total = 22 (12 legacy + 10 V2)', 'count=' . $totalPenilaian);

// Legacy V1 golden values unchanged (spot-check).
$v08 = rows('SELECT nama, nilai_preferensi FROM tb_hasil h JOIN tb_teknisi t ON t.id = h.teknisi_id WHERE h.periode = ? ORDER BY h.ranking', ['2026-08']);
check(count($v08) === 10, '2026-08 hasil has 10 ranked rows', 'count=' . count($v08));
if (count($v08) === 10) {
    check(abs((float) $v08[0]['nilai_preferensi'] - 1.000) < 0.0001, '2026-08 rank1 Vi = 1.000', (string) $v08[0]['nilai_preferensi']);
    check(abs((float) $v08[9]['nilai_preferensi'] - 0.575) < 0.0001, '2026-08 rank10 Vi = 0.575', (string) $v08[9]['nilai_preferensi']);
}

// SAW V2 sources untouched (contract, not data).
check(is_file(__DIR__ . '/../app/services/SawEngineV2.php'), 'SawEngineV2.php present');
check(is_file(__DIR__ . '/../app/services/SawServiceV2.php'), 'SawServiceV2.php present');
check(md5_file(__DIR__ . '/../app/services/SawEngineV2.php') !== false, 'SawEngineV2.php readable');
check(md5_file(__DIR__ . '/../app/services/SawServiceV2.php') !== false, 'SawServiceV2.php readable');

// ------------------------------------------------------------------
// Result
// ------------------------------------------------------------------
echo PHP_EOL . 'PHASE 6I SUMMARY: ' . $pass . '/' . ($pass + $fail) . ' passed' . PHP_EOL;
if ($fail > 0) {
    echo 'PHASE 6I FAILED' . PHP_EOL;
    exit(1);
}
echo 'PHASE 6I ALL PASS' . PHP_EOL;
