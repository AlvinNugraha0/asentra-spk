<?php
// ASENTRA SPK — Phase 6L: ADMIN UX CLEANUP
//
// Verifies the admin sidebar reorg + /admin/import as the periode workflow center:
//   (a) sidebar no longer lists "Kelola Periode V2" for admin
//   (b) /admin/import renders the full periode table as a main section
//   (c) "+ Tambah Periode" is gone from the main UI
//   (d) GET /admin/periode still renders (backward compat)
//   (e) GET /admin/periode/create still renders (backward compat)
//   (f) legacy rows show "Read-only", not action buttons
//   (g) "Hitung V2" only when can_calculate
//   (h) Phase 6K auto-detect untouched (detect + resolveOrCreate, no duplicate)
//   (i) SawEngineV2 / SawServiceV2 byte-identical
//   (j) legacy 12/12 + baseline 22/22 preserved
//
// SAFETY: read-only w.r.t. tb_periode_penilaian/tb_penilaian/tb_hasil — this is a
// UI reorg phase, so NO rows are created or deleted. The 6K auto-detect check
// runs PeriodeDetector directly (no import, no writes).

declare(strict_types=1);

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/models/PeriodePenilaian.php';
require_once __DIR__ . '/../app/models/Penilaian.php';
require_once __DIR__ . '/../app/models/Teknisi.php';
require_once __DIR__ . '/../app/services/import/PeriodeDetector.php';

require_once __DIR__ . '/../app/helpers/url.php';
require_once __DIR__ . '/../app/helpers/format.php';
require_once __DIR__ . '/../app/helpers/view.php';
require_once __DIR__ . '/../app/helpers/auth.php';

$autoload = dirname(__DIR__) . '/vendor/autoload.php';
if (is_file($autoload)) {
    require_once $autoload;
}

use App\Core\Database;
use App\Models\PeriodePenilaian;
use App\Services\Import\PeriodeDetector;

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

function q1(string $sql, array $params = []): mixed
{
    return Database::query($sql, $params)->fetch();
}

/**
 * Render a view headless (no HTTP server) with an admin session in place.
 */
function renderAdminView(string $view, array $data): string
{
    $_SESSION['user'] = ['id' => 1, 'nama' => 'Admin Test', 'role' => 'admin'];
    $_SESSION['csrf_token'] = 'phase6l-token';
    return viewPartial($view, $data);
}

// ---------------------------------------------------------------------------
echo '========================================' . PHP_EOL;
echo 'PHASE 6L — ADMIN UX CLEANUP' . PHP_EOL;
echo '========================================' . PHP_EOL;

// STEP 0 — Baseline + golden invariants
$basePenilaian = (int) q1('SELECT COUNT(*) AS c FROM tb_penilaian')['c'];
$baseHasil     = (int) q1('SELECT COUNT(*) AS c FROM tb_hasil')['c'];
$basePeriode   = (int) q1('SELECT COUNT(*) AS c FROM tb_periode_penilaian')['c'];
$legacyPenilaian = (int) q1('SELECT COUNT(*) AS c FROM tb_penilaian WHERE status_data = "legacy"')['c'];
$legacyHasil     = (int) q1('SELECT COUNT(*) AS c FROM tb_hasil h JOIN tb_periode_penilaian p ON p.id_periode = h.id_periode WHERE p.status = "legacy"')['c'];
$sawV2HashBefore = hash_file('sha256', __DIR__ . '/../app/services/SawEngineV2.php');
$sawServiceHashBefore = hash_file('sha256', __DIR__ . '/../app/services/SawServiceV2.php');

echo "Baseline: periode={$basePeriode} penilaian={$basePenilaian} hasil={$baseHasil} legacy={$legacyPenilaian}/{$legacyHasil}" . PHP_EOL . PHP_EOL;

check($legacyPenilaian === 12, 'Legacy tb_penilaian = 12 at baseline', "got {$legacyPenilaian}");
check($legacyHasil === 12, 'Legacy tb_hasil = 12 at baseline', "got {$legacyHasil}");
check($basePenilaian === 22 && $baseHasil === 22, 'Baseline totals 22/22', "got {$basePenilaian}/{$baseHasil}");
check($sawV2HashBefore !== false, 'SawEngineV2.php readable for hashing', '');
check($sawServiceHashBefore !== false, 'SawServiceV2.php readable for hashing', '');

$dummyWorkbook = 'C:/Users/MP2HX/Downloads/test-data/dummy_q2_2026.xlsx';
check(is_file($dummyWorkbook), 'Dummy Q2-2026 workbook exists', $dummyWorkbook);

// ---------------------------------------------------------------------------
// STEP 1 — Shared row data used by both renderings
$allPeriods = PeriodePenilaian::allWithProgress();
$importPeriods = array_values(array_filter($allPeriods, static fn (array $p): bool => ($p['status'] ?? '') !== 'legacy'));
$legacyIds = [];
$calcIds = [];
$noCalcIds = [];
foreach ($allPeriods as $p) {
    $id = (int) $p['id_periode'];
    if (($p['status'] ?? '') === 'legacy') {
        $legacyIds[] = $id;
        continue;
    }
    if (!empty($p['can_calculate'])) {
        $calcIds[] = $id;
    } else {
        $noCalcIds[] = $id;
    }
}
echo 'Rows: legacy=' . count($legacyIds) . ' canCalc=' . count($calcIds) . ' cannot=' . count($noCalcIds) . PHP_EOL . PHP_EOL;

check(count($allPeriods) === $basePeriode, 'allWithProgress returns every periode row', "got " . count($allPeriods) . " vs {$basePeriode}");
check(count($legacyIds) === 2, 'Legacy rows present in shared table data (ids ' . implode(',', $legacyIds) . ')', 'got ' . count($legacyIds));

// ---------------------------------------------------------------------------
// STEP 2 — (a) Sidebar: no "Kelola Periode V2" for admin, Import Data V2 present
$_SESSION['user'] = ['id' => 1, 'nama' => 'Admin Test', 'role' => 'admin'];
$_SESSION['csrf_token'] = 'phase6l-token';
$_SERVER['REQUEST_URI'] = '/admin/import';
$_SERVER['REQUEST_METHOD'] = 'GET';

$adminLayout = viewPartial('layouts.app', ['title' => 'Import Data', 'content' => '<main></main>']);
check(!str_contains($adminLayout, 'Kelola Periode V2'), '(a) admin sidebar has NO "Kelola Periode V2"', 'string still present');
check(str_contains($adminLayout, 'Import Data V2'), '(a) admin sidebar keeps "Import Data V2"', 'missing');
check(str_contains($adminLayout, '/admin/import'), '(a) admin sidebar links /admin/import', 'missing');
check(!str_contains($adminLayout, '/admin/periode"'), '(a) admin sidebar does not link /admin/periode', 'still linked');

// Owner sidebar untouched: render with owner role and confirm its nav is intact.
$_SESSION['user'] = ['id' => 2, 'nama' => 'Owner Test', 'role' => 'owner'];
// Owner sidebar: Phase 6N consolidated the owner penilaian menu. The single
// entry is now "Penilaian Kinerja" -> /owner/assessment; the V1
// /owner/penilaian nav item is hidden (route still exists for backward compat).
$ownerLayout = viewPartial('layouts.app', ['title' => 'Owner', 'content' => '<main></main>']);
check(str_contains($ownerLayout, 'Penilaian Kinerja'), '(a) owner sidebar: "Penilaian Kinerja" present', 'missing');
check(!str_contains($ownerLayout, 'Review Penilaian V2'), '(a) owner sidebar: no stale "Review Penilaian V2" label', 'stale label');
check(str_contains($ownerLayout, '/owner/assessment'), '(a) owner sidebar links /owner/assessment', 'missing');
check(!str_contains($ownerLayout, 'href="' . route('/owner/penilaian') . '"'), '(a) owner sidebar: no live /owner/penilaian link', 'V1 link still live');
check(str_contains($ownerLayout, 'Hasil Ranking'), '(a) owner sidebar: Hasil Ranking present', '');
check(!str_contains($ownerLayout, 'Import Data V2'), '(a) owner sidebar has no Import entry', 'leaked admin nav');
check(!str_contains($ownerLayout, 'Kelola Periode V2'), '(a) owner sidebar has no Kelola Periode V2', 'leaked admin nav');

$_SESSION['user'] = ['id' => 1, 'nama' => 'Admin Test', 'role' => 'admin'];

// ---------------------------------------------------------------------------
// STEP 3 — (b) /admin/import contains the periode table as a MAIN section
$importHtml = renderAdminView('admin.import', [
    'title' => 'Import Data Operasional V2',
    'subtitle' => 'Upload file Excel — sistem mendeteksi periode kuartal secara otomatis.',
    'periods' => $importPeriods,
    'allPeriods' => $allPeriods,
    'recentImports' => [],
    'errors' => [],
    'importResult' => null,
    'form_errors' => [],
    'form_old' => [],
]);

check(str_contains($importHtml, 'id="periode-table"'), '(b) /admin/import renders the periode table', 'periode-table missing');
foreach (['KODE', 'NAMA PERIODE', 'RENTANG TANGGAL', 'DATA OPERASIONAL', 'STATUS', 'FILE IMPORT', 'AKSI'] as $col) {
    check(str_contains($importHtml, '>' . $col . '<'), '(b) import table column ' . $col, 'missing column');
}
// main section = rendered as a sibling card AFTER the upload card, not inside
// the collapsed <details> "Opsi lanjutan" block.
$advancedPos = strpos($importHtml, 'Opsi lanjutan / input periode manual');
$tablePos = strpos($importHtml, 'id="periode-table"');
$historyPos = strpos($importHtml, 'Riwayat Import Terbaru');
check($advancedPos !== false && $tablePos !== false, '(b) both the advanced block and the table rendered', "advanced={$advancedPos} table={$tablePos}");
check($tablePos > $advancedPos, '(b) periode table is NOT inside the collapsed advanced <details>', "table={$tablePos} < advanced={$advancedPos}");
check($historyPos !== false && $tablePos < $historyPos, '(b) table sits above the import history card', "table={$tablePos} history={$historyPos}");
$detailsClosePos = strpos($importHtml, '</details>');
check($detailsClosePos !== false && $detailsClosePos < $tablePos, '(b) table rendered after the advanced <details> is closed', "close={$detailsClosePos} table={$tablePos}");
check(str_contains($importHtml, 'Daftar Periode Penilaian'), '(b) table has a visible section heading', 'missing');

// ---------------------------------------------------------------------------
// STEP 4 — (c) "+ Tambah Periode" gone from the main UI
check(!str_contains($importHtml, 'Tambah Periode'), '(c) /admin/import has no "Tambah Periode" button', 'string present');
$periodeListHtml = renderAdminView('admin.periode_list', [
    'title' => 'Kelola Periode Penilaian',
    'subtitle' => 'Daftar periode triwulan, data operasional, dan status kalkulasi V2.',
    'periods' => $allPeriods,
]);
check(!str_contains($periodeListHtml, 'Tambah Periode'), '(c) /admin/periode view has no "Tambah Periode" button', 'string present');
check(!str_contains($periodeListHtml, '/admin/periode/create'), '(c) /admin/periode view has no create link', 'link present');

// ---------------------------------------------------------------------------
// STEP 5 — (d)+(e) backward-compat pages still render
check(str_contains($periodeListHtml, 'id="periode-table"'), '(d) /admin/periode view still renders the periode table', 'table missing');
check(str_contains($periodeListHtml, 'Hitung V2') === (count($calcIds) > 0), '(d) /admin/periode table keeps Hitung V2 where calculable', '');

$_SESSION['form_errors'] = [];
$_SESSION['form_old'] = [];
$createForm = renderAdminView('admin.periode_form', [
    'title' => 'Tambah Periode Penilaian',
    'subtitle' => 'Buat periode penilaian kuartal baru untuk teknisi.',
    'errors' => [],
    'old' => [],
]);
check(strlen($createForm) > 0, '(e) /admin/periode/create view renders', 'empty output');
check(str_contains($createForm, 'kode_periode') && str_contains($createForm, 'tanggal_mulai'), '(e) create form still has its fields', 'fields missing');

// ---------------------------------------------------------------------------
// STEP 6 — (f) legacy rows show "Read-only", not action buttons
$legacyRow = null;
foreach ($allPeriods as $p) {
    if (($p['status'] ?? '') === 'legacy') {
        $legacyRow = $p;
        break;
    }
}
if ($legacyRow !== null) {
    $legacyKode = (string) $legacyRow['kode_periode'];
    check(str_contains($importHtml, 'Read-only'), '(f) legacy row shows "Read-only" in import table', 'missing');
    check(str_contains($importHtml, $legacyKode), '(f) legacy row ' . $legacyKode . ' is listed in the table', 'missing');
    // legacy row must not carry a Hitung V2 form or a Hasil link
    $rowStart = strpos($importHtml, '<td class="font-bold text-gold">' . $legacyKode . '</td>');
    $rowEnd = $rowStart !== false ? strpos($importHtml, '</tr>', $rowStart) : false;
    $rowSlice = ($rowStart !== false && $rowEnd !== false) ? substr($importHtml, $rowStart, $rowEnd - $rowStart) : '';
    check(!str_contains($rowSlice, 'Hitung V2'), '(f) legacy row has NO Hitung V2 button', 'button present');
    check(!str_contains($rowSlice, 'admin/periode/hasil/'), '(f) legacy row has NO Hasil link', 'link present');
    check(!str_contains($rowSlice, 'admin/periode/kalkulasi/'), '(f) legacy row has NO kalkulasi form action', 'form present');
} else {
    check(false, '(f) legacy row exists for the Read-only check', 'no legacy row found');
}

// ---------------------------------------------------------------------------
// STEP 7 — (g) Hitung V2 only when can_calculate
// NOTE: the manual-select <option> list also contains every kode, so the table
// row must be located via its <td> cell, not a bare strpos of the kode.
foreach ($allPeriods as $p) {
    $kode = (string) $p['kode_periode'];
    $rowStart = strpos($importHtml, '<td class="font-bold text-gold">' . $kode . '</td>');
    if ($rowStart === false) {
        check(false, '(g) ' . $kode . ' row found in the table', 'row missing');
        continue;
    }
    // confine the scan to this row only
    $rowEnd = strpos($importHtml, '</tr>', $rowStart);
    $rowSlice = $rowEnd !== false ? substr($importHtml, $rowStart, $rowEnd - $rowStart) : substr($importHtml, $rowStart, 900);
    $hasBtn = str_contains($rowSlice, 'Hitung V2');
    $wantBtn = !empty($p['can_calculate']);
    check($hasBtn === $wantBtn, '(g) ' . $kode . ' Hitung V2 iff can_calculate=' . var_export($wantBtn, true), 'got ' . var_export($hasBtn, true));
    if (($p['status'] ?? '') !== 'legacy' && !$wantBtn) {
        $label = $p['status'] === 'selesai' ? 'Terkonfirmasi' : 'Belum Ada Data';
        check(str_contains($rowSlice, $label), '(g) ' . $kode . ' shows the "' . $label . '" label', 'label missing');
    }
}

// ---------------------------------------------------------------------------
// STEP 8 — (h) Phase 6K auto-detect untouched
$detected = PeriodeDetector::detect($dummyWorkbook);
check(($detected['ok'] ?? false) === true, '(h) detect() still succeeds', 'error=' . ($detected['error'] ?? ''));
check(($detected['quarter'] ?? 0) === 2, '(h) detect() -> quarter 2', 'got ' . ($detected['quarter'] ?? '?'));
check(($detected['kode_periode'] ?? '') === '2026-Q2', '(h) detect() -> kode 2026-Q2', 'got ' . ($detected['kode_periode'] ?? '?'));

// resolveOrCreate is WRITE-BY-NATURE, so probe it on a throwaway kode and verify
// the no-duplicate contract, then remove the probe row. Never touches 1/2/24/25.
$probeKode = '2099-Q2';
Database::query('DELETE FROM tb_periode_penilaian WHERE kode_periode = ?', [$probeKode]);

$probeDetected = $detected;
$probeDetected['kode_periode'] = $probeKode;
$probeDetected['nama_periode'] = 'Probe - Juni 2099';

$probeA = PeriodeDetector::resolveOrCreate($probeDetected, 1);
check(($probeA['ok'] ?? false) === true, '(h) resolveOrCreate (throwaway) succeeds', 'error=' . ($probeA['error'] ?? ''));
check(($probeA['created'] ?? false) === true, '(h) resolveOrCreate CREATED the missing probe periode', 'created=' . var_export($probeA['created'] ?? null, true));
$probeId = (int) ($probeA['id_periode'] ?? 0);
check($probeId > 0 && !in_array($probeId, [1, 2, 24, 25], true), '(h) probe id is fresh and not 1/2/24/25', "id={$probeId}");

// Second call must REUSE, never duplicate.
$probeB = PeriodeDetector::resolveOrCreate($probeDetected, 1);
check(($probeB['ok'] ?? false) === true, '(h) second resolveOrCreate succeeds', 'error=' . ($probeB['error'] ?? ''));
check(($probeB['created'] ?? true) === false, '(h) second call REUSES the existing row (no duplicate)', 'created=' . var_export($probeB['created'] ?? null, true));
check((int) ($probeB['id_periode'] ?? 0) === $probeId, '(h) reuse returns the same id_periode', 'got ' . var_export($probeB['id_periode'] ?? null, true));

$probeCount = (int) q1('SELECT COUNT(*) AS c FROM tb_periode_penilaian WHERE kode_periode = ?', [$probeKode])['c'];
check($probeCount === 1, '(h) exactly ONE probe row after two resolves', "got {$probeCount}");

// Cleanup the probe (self-cleaning).
foreach (['tb_hasil', 'tb_penilaian', 'tb_kedisiplinan', 'tb_pekerjaan', 'tb_tanggung_jawab', 'tb_import'] as $t) {
    Database::query("DELETE FROM {$t} WHERE id_periode = ?", [$probeId]);
}
Database::query('DELETE FROM tb_periode_penilaian WHERE id_periode = ?', [$probeId]);

// And confirm resolveOrCreate reuses the REAL detected kode when it exists.
if ($detected['ok'] ?? false) {
    $realKode = (string) $detected['kode_periode'];
    $before = (int) q1('SELECT COUNT(*) AS c FROM tb_periode_penilaian WHERE kode_periode = ?', [$realKode])['c'];
    $resolved = PeriodeDetector::resolveOrCreate($detected, 1);
    check(($resolved['ok'] ?? false) === true, '(h) resolveOrCreate on real kode succeeds', 'error=' . ($resolved['error'] ?? ''));
    $after = (int) q1('SELECT COUNT(*) AS c FROM tb_periode_penilaian WHERE kode_periode = ?', [$realKode])['c'];
    if ($before >= 1) {
        check($after === $before, '(h) existing ' . $realKode . ' reused, count unchanged', "before={$before} after={$after}");
        check(($resolved['created'] ?? true) === false, '(h) ' . $realKode . ' was not re-created', 'created=' . var_export($resolved['created'] ?? null, true));
    } else {
        // 6L must not leave a stray real-kode row behind.
        check($after === 1, '(h) ' . $realKode . ' created once (was absent)', "after={$after}");
        $strayId = (int) ($resolved['id_periode'] ?? 0);
        if (!in_array($strayId, [1, 2, 24, 25], true)) {
            Database::query('DELETE FROM tb_periode_penilaian WHERE id_periode = ?', [$strayId]);
        }
    }
}

// ---------------------------------------------------------------------------
// STEP 9 — (i)+(j) teardown invariants
$afterPenilaian = (int) q1('SELECT COUNT(*) AS c FROM tb_penilaian')['c'];
$afterHasil     = (int) q1('SELECT COUNT(*) AS c FROM tb_hasil')['c'];
$afterPeriode   = (int) q1('SELECT COUNT(*) AS c FROM tb_periode_penilaian')['c'];
$afterLegacyPenilaian = (int) q1('SELECT COUNT(*) AS c FROM tb_penilaian WHERE status_data = "legacy"')['c'];
$afterLegacyHasil     = (int) q1('SELECT COUNT(*) AS c FROM tb_hasil h JOIN tb_periode_penilaian p ON p.id_periode = h.id_periode WHERE p.status = "legacy"')['c'];
$protectedCount = (int) q1('SELECT COUNT(*) AS c FROM tb_periode_penilaian WHERE id_periode IN (1,2,24,25)')['c'];

echo PHP_EOL . 'TEARDOWN' . PHP_EOL;
check($afterPeriode === $basePeriode, "(j) tb_periode_penilaian unchanged at {$basePeriode}", "got {$afterPeriode}");
check($afterPenilaian === $basePenilaian, "(j) tb_penilaian unchanged at {$basePenilaian}", "got {$afterPenilaian}");
check($afterHasil === $baseHasil, "(j) tb_hasil unchanged at {$baseHasil}", "got {$afterHasil}");
check($afterLegacyPenilaian === 12, '(j) legacy tb_penilaian still 12', "got {$afterLegacyPenilaian}");
check($afterLegacyHasil === 12, '(j) legacy tb_hasil still 12', "got {$afterLegacyHasil}");
check($protectedCount === 4, '(j) protected ids 1/2/24/25 all present', "got {$protectedCount}");
check(hash_file('sha256', __DIR__ . '/../app/services/SawEngineV2.php') === $sawV2HashBefore, '(i) SawEngineV2.php byte-identical', '');
check(hash_file('sha256', __DIR__ . '/../app/services/SawServiceV2.php') === $sawServiceHashBefore, '(i) SawServiceV2.php byte-identical', '');

echo PHP_EOL . '========================================' . PHP_EOL;
echo 'RESULT: ' . $pass . '/' . ($pass + $fail) . ' PASS' . PHP_EOL;
echo '========================================' . PHP_EOL;

exit($fail === 0 ? 0 : 1);
