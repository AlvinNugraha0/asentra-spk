<?php
// ASENTRA SPK — Phase 6A: Period (Quarter) Calendar Validation Tests
// Verifies PeriodePenilaian::validateQuarterRange() and parseQuarterCode().
// Pure unit tests — no database writes, no legacy data touched.

declare(strict_types=1);

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/models/PeriodePenilaian.php';

use App\Models\PeriodePenilaian;

$results = [];

function record(array &$results, string $name, bool $ok, string $detail = ''): void
{
    $results[] = ['name' => $name, 'ok' => $ok, 'detail' => $detail];
    echo ($ok ? 'OK  ' : 'FAIL') . ': ' . $name . ($detail ? ' — ' . $detail : '') . PHP_EOL;
}

echo "========================================\n";
echo "PHASE 6A — QUARTER CALENDAR VALIDATION\n";
echo "========================================\n";

// ---------------------------------------------------------------- parseQuarterCode
echo "\n-- parseQuarterCode --\n";

$cases = [
    '2026-Q1' => 1,
    '2026-Q2' => 2,
    '2026-Q3' => 3,
    '2026-Q4' => 4,
    '2026-q2' => 2,        // case-insensitive
    ' 2026-Q3 ' => 3,      // whitespace tolerated
];
foreach ($cases as $kode => $expected) {
    $got = PeriodePenilaian::parseQuarterCode($kode);
    record($results, "parseQuarterCode('{$kode}') === {$expected}", $got === $expected, 'got ' . var_export($got, true));
}

$nonQuarters = ['LEGACY-2026-08', '2026-08', '', 'Q1', '2026-Q5', '2026-Q0', '2026-Q', '2026-Q12', 'abc'];
foreach ($nonQuarters as $kode) {
    $got = PeriodePenilaian::parseQuarterCode($kode);
    record($results, "parseQuarterCode('{$kode}') === null (non-quarter)", $got === null, 'got ' . var_export($got, true));
}

// ------------------------------------------------------- validateQuarterRange: valid
echo "\n-- validateQuarterRange: VALID --\n";

$validCases = [
    // [kode, mulai, selesai]
    ['2026-Q1', '2026-01-01', '2026-03-31'],
    ['2026-Q2', '2026-04-01', '2026-06-30'],
    ['2026-Q3', '2026-07-01', '2026-09-30'],
    ['2026-Q4', '2026-10-01', '2026-12-31'],
    ['2025-Q1', '2025-01-01', '2025-03-31'],   // year other than 2026
    ['2027-Q2', '2027-04-01', '2027-06-30'],
    // leap-year Q1 end still Feb-based quarter -> March 31 (unchanged)
    ['2024-Q1', '2024-01-01', '2024-03-31'],
    // Non-quarter codes: only mulai<=selesai required
    ['LEGACY-2026-08', '2026-08-01', '2026-08-31'],
    ['2026-08', '2026-08-01', '2026-08-20'],
    ['BEbas', '2026-01-15', '2026-02-20'],
];
foreach ($validCases as [$kode, $mulai, $selesai]) {
    $r = PeriodePenilaian::validateQuarterRange($kode, $mulai, $selesai);
    record($results, "valid: {$kode} {$mulai}..{$selesai}", $r['valid'] === true && $r['errors'] === [], 'errors=' . json_encode($r['errors']));
}

// ----------------------------------------------------- validateQuarterRange: invalid
echo "\n-- validateQuarterRange: INVALID --\n";

$invalidCases = [
    // The historical bug: Q1 declared as 20 Feb - 20 Feb
    ['2026-Q1', '2026-02-20', '2026-02-20'],
    // Wrong start month
    ['2026-Q1', '2026-02-01', '2026-03-31'],
    // Wrong end day
    ['2026-Q1', '2026-01-01', '2026-03-30'],
    // Q4 wrong both
    ['2026-Q4', '2026-09-01', '2026-12-15'],
    // Q2 swapped dates
    ['2026-Q2', '2026-06-30', '2026-04-01'],
    // Q3 wrong year in dates vs code
    ['2026-Q3', '2025-07-01', '2025-09-30'],
    // Q2 across into Q3
    ['2026-Q2', '2026-04-01', '2026-07-31'],
    // Non-quarter code with reversed dates still caught by general rule
    ['BEBAS', '2026-03-01', '2026-01-01'],
];

foreach ($invalidCases as [$kode, $mulai, $selesai]) {
    $r = PeriodePenilaian::validateQuarterRange($kode, $mulai, $selesai);
    $ok = $r['valid'] === false && !empty($r['errors']);
    record($results, "invalid: {$kode} {$mulai}..{$selesai}", $ok, 'errors=' . json_encode($r['errors']));
}

// -------------------------------------------------- error message content checks
echo "\n-- error message content --\n";

$r = PeriodePenilaian::validateQuarterRange('2026-Q1', '2026-02-20', '2026-02-20');
$hasStart = strpos(implode(' ', $r['errors']), '01 Januari 2026') !== false;
record($results, 'error mentions expected start 01 Januari 2026', $hasStart);

$r = PeriodePenilaian::validateQuarterRange('2026-Q4', '2026-10-01', '2026-12-15');
$hasEnd = strpos(implode(' ', $r['errors']), '2026-12-31') !== false;
record($results, 'error mentions expected end 2026-12-31', $hasEnd);

// ------------------------------------------------------------ summary
$pass = count(array_filter($results, static fn (array $r): bool => $r['ok']));
$total = count($results);
echo "\n========================================\n";
echo "RESULT: {$pass}/{$total} PASS\n";
echo "========================================\n";

exit($pass === $total ? 0 : 1);
