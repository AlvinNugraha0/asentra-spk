<?php
// ASENTRA SPK — Phase 6M: ADMIN IMPORT WORKFLOW POLISH
//
// Verifies the /admin/import UI polish without changing any backend logic:
//   (a) import.php renders a styled black-gold upload zone + filename hook
//   (b) allow_partial checkbox is UNCHECKED by default (opt-in)
//   (c) detected-periode panel renders when $_SESSION['detected_periode'] is set
//       and is ABSENT when it is not (real assertion, fails if feature removed)
//   (d) result card distinguishes success / partial / failed states
//   (e) Phase 6K auto-detect still works end-to-end (detect -> quarter 2 ->
//       resolveOrCreate no-duplicate, throwaway 2099-Q probe, self-cleaning)
//   (f) explicit id_periode backward-compat path still imports with
//       allow_partial OFF and ON
//   (g) SAW sha256 unchanged; legacy 12/12 + baseline restored; 4 periode rows
//   (h) no new route added (route table for /admin/import unchanged)
//
// SAFETY: all imports go into throwaway periodes (kode 2099-Q2 / 2099-Q4)
// never id_periode 1/2/24/25. Teardown restores the baseline counts and wipes
// the storage copies. SawEngineV2/SawServiceV2 stay byte-identical.

declare(strict_types=1);

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/models/PeriodePenilaian.php';
require_once __DIR__ . '/../app/models/Penilaian.php';
require_once __DIR__ . '/../app/models/Teknisi.php';
require_once __DIR__ . '/../app/services/import/ExcelImportService.php';
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
use App\Services\Import\ExcelImportService;
use App\Services\Import\PeriodeDetector;

$pass = 0;
$fail = 0;

/**
 * Minimal regex HTML element parser: find the element carrying id="$id" and
 * return ['tag' => name, 'attrs' => [name => value]] with normalised attribute
 * names. Self-closing and valueless (boolean) attributes both supported.
 * Deliberately tiny — this test only needs tag name + attribute presence.
 */
function parseHtmlById(string $html, string $id): ?array
{
    $pattern = '/<([a-zA-Z][a-zA-Z0-9-]*)([^>]*?)>/';
    if (preg_match_all($pattern, $html, $matches, PREG_SET_ORDER) === false) {
        return null;
    }
    foreach ($matches as $m) {
        $attrs = [];
        $ok = preg_match_all('/([a-zA-Z_:][a-zA-Z0-9_:.-]*)(\s*=\s*"([^"]*)"|\s*=\s*\'([^\']*)\')?/', $m[2], $am, PREG_SET_ORDER);
        if ($ok !== false) {
            foreach ($am as $a) {
                $attrs[strtolower($a[1])] = $a[3] ?? ($a[4] ?? '');
            }
        }
        if (($attrs['id'] ?? '') === $id) {
            return ['tag' => strtolower($m[1]), 'attrs' => $attrs];
        }
    }
    return null;
}

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
 * Reuses the exact helper shape proven by tests/phase6l_admin_ux_test.php.
 */
function renderAdminView(string $view, array $data): string
{
    $_SESSION['user'] = ['id' => 1, 'nama' => 'Admin Test', 'role' => 'admin'];
    $_SESSION['csrf_token'] = 'phase6m-token';
    return viewPartial($view, $data);
}

function cleanupPeriode(int $periodeId): void
{
    foreach ([
        'tb_hasil', 'tb_penilaian', 'tb_kedisiplinan', 'tb_pekerjaan',
        'tb_tanggung_jawab', 'tb_import',
    ] as $t) {
        Database::query("DELETE FROM {$t} WHERE id_periode = ?", [$periodeId]);
    }
    Database::query('DELETE FROM tb_periode_penilaian WHERE id_periode = ?', [$periodeId]);
}

// ---------------------------------------------------------------------------
echo '========================================' . PHP_EOL;
echo 'PHASE 6M — ADMIN IMPORT WORKFLOW POLISH' . PHP_EOL;
echo '========================================' . PHP_EOL;

// STEP 0 — Baseline + golden invariants
$basePenilaian = (int) q1('SELECT COUNT(*) AS c FROM tb_penilaian')['c'];
$baseHasil     = (int) q1('SELECT COUNT(*) AS c FROM tb_hasil')['c'];
$basePeriode   = (int) q1('SELECT COUNT(*) AS c FROM tb_periode_penilaian')['c'];
$legacyPenilaian = (int) q1('SELECT COUNT(*) AS c FROM tb_penilaian WHERE status_data = "legacy"')['c'];
$legacyHasil     = (int) q1('SELECT COUNT(*) AS c FROM tb_hasil h JOIN tb_periode_penilaian p ON p.id_periode = h.id_periode WHERE p.status = "legacy"')['c'];
$sawV2HashBefore = hash_file('sha256', __DIR__ . '/../app/services/SawEngineV2.php');
$sawServiceHashBefore = hash_file('sha256', __DIR__ . '/../app/services/SawServiceV2.php');
$routerBefore = file_get_contents(__DIR__ . '/../app/core/Router.php');

echo "Baseline: periode={$basePeriode} penilaian={$basePenilaian} hasil={$baseHasil} legacy={$legacyPenilaian}/{$legacyHasil}" . PHP_EOL . PHP_EOL;

check($legacyPenilaian === 12, 'Legacy tb_penilaian = 12 at baseline', "got {$legacyPenilaian}");
check($legacyHasil === 12, 'Legacy tb_hasil = 12 at baseline', "got {$legacyHasil}");
check($sawV2HashBefore !== false, 'SawEngineV2.php readable for hashing', '');
check($sawServiceHashBefore !== false, 'SawServiceV2.php readable for hashing', '');

$dummyWorkbook = 'C:/Users/MP2HX/Downloads/test-data/dummy_q2_2026.xlsx';
check(is_file($dummyWorkbook), 'Dummy Q2-2026 workbook exists', $dummyWorkbook);

// ---------------------------------------------------------------------------
// STEP 1 — Shared row data for the render assertions
$allPeriods = PeriodePenilaian::allWithProgress();
$importPeriods = array_values(array_filter($allPeriods, static fn (array $p): bool => ($p['status'] ?? '') !== 'legacy'));

$plainViewData = [
    'title' => 'Import Data Operasional V2',
    'subtitle' => 'Upload file Excel — sistem mendeteksi periode kuartal secara otomatis.',
    'periods' => $importPeriods,
    'allPeriods' => $allPeriods,
    'recentImports' => [],
    'errors' => [],
    'importResult' => null,
    'detectedPeriode' => null,
    'form_errors' => [],
    'form_old' => [],
];

// ---------------------------------------------------------------------------
// STEP 2 — (a) Styled upload zone + filename display hook
$importHtml = renderAdminView('admin.import', $plainViewData);
check(str_contains($importHtml, 'upload-zone'), '(a) styled upload zone class present', '');
check(str_contains($importHtml, 'id="upload-zone"'), '(a) main upload zone id present', '');
check(str_contains($importHtml, 'id="file_excel_name"'), '(a) filename display hook id present', '');
check(str_contains($importHtml, 'class="upload-zone-name"'), '(a) filename display element class present', '');
check(str_contains($importHtml, 'id="upload-zone-explicit"'), '(a) advanced-path upload zone also styled', '');
check(str_contains($importHtml, 'id="file_excel_explicit_name"'), '(a) advanced-path filename hook present', '');
check(str_contains($importHtml, 'has-file'), '(a) zone gets a has-file state class via JS', '');
check(str_contains($importHtml, 'dragover'), '(a) drag-over interaction class present', '');
check(str_contains($importHtml, 'addEventListener'), '(a) vanilla JS attached (no new dependency)', '');
check(str_contains($importHtml, 'fmtBytes'), '(a) client-side size hint implemented', '');
// The visible file input stays a real, accessible <input type="file">.
check(str_contains($importHtml, '<input type="file" name="file_excel" id="file_excel"'), '(a) real file input kept for form submission', '');

// ---------------------------------------------------------------------------
// STEP 3 — (b) allow_partial checkbox default OFF
$parsed = parseHtmlById($importHtml, 'allow_partial');
check($parsed !== null, '(b) allow_partial input parsed from rendered view', '');
check(($parsed['tag'] ?? '') === 'input', '(b) allow_partial is an <input> element', 'got ' . var_export($parsed['tag'] ?? null, true));
check(($parsed['attrs']['type'] ?? '') === 'checkbox', '(b) allow_partial is a checkbox', 'got ' . var_export($parsed['attrs']['type'] ?? null, true));
check(!array_key_exists('checked', $parsed['attrs']), '(b) allow_partial renders UNCHECKED (opt-in)', 'attrs=' . json_encode($parsed['attrs']));
check(str_contains($importHtml, 'opsional, non-aktif secara default'), '(b) label states the opt-in default', '');
check(str_contains($importHtml, 'dilewati tanpa peringatan'), '(b) partial-import risk hint shown', '');

// The raw view source must not carry a checked attribute on that input either.
$src = file_get_contents(__DIR__ . '/../app/views/admin/import.php');
$srcParsed = parseHtmlById($src, 'allow_partial');
check($srcParsed !== null && !array_key_exists('checked', $srcParsed['attrs']), '(b) view SOURCE has no checked attr on allow_partial', 'attrs=' . json_encode($srcParsed['attrs'] ?? null));

// ---------------------------------------------------------------------------
// STEP 4 — (c) detected-periode panel: present when the session var is set
$detectedSample = [
    'quarter' => 2,
    'kode_periode' => '2026-Q2',
    'nama_periode' => 'April - Juni 2026',
    'tanggal_mulai' => '2026-04-01',
    'tanggal_selesai' => '2026-06-30',
    'created' => true,
    'id_periode' => 9999,
    'status' => 'draft',
    'nama_file_asli' => 'dummy_q2_2026.xlsx',
];

$withPanel = renderAdminView('admin.import', array_merge($plainViewData, ['detectedPeriode' => $detectedSample]));
check(str_contains($withPanel, 'id="detected-periode"'), '(c) detected-periode panel rendered when session var set', '');
check(str_contains($withPanel, 'Periode Terdeteksi'), '(c) panel heading present', '');
check(str_contains($withPanel, '2026-Q2'), '(c) panel shows kode_periode', '');
check(str_contains($withPanel, 'April - Juni 2026'), '(c) panel shows nama_periode', '');
check(str_contains($withPanel, '2026-04-01'), '(c) panel shows tanggal_mulai', '');
check(str_contains($withPanel, '2026-06-30'), '(c) panel shows tanggal_selesai', '');
check(str_contains($withPanel, 'PERIODE BARU DIBUAT'), '(c) created badge shown when created=true', '');
check(str_contains($withPanel, 'Triwulan 2 dibaca dari data Excel'), '(c) quarter source fact shown', '');
check(str_contains($withPanel, 'dummy_q2_2026.xlsx'), '(c) original filename shown in panel', '');
check(!str_contains($importHtml, 'id="detected-periode"'), '(c) panel ABSENT when detectedPeriode is null', 'leaked into the plain render');

// Reused periode -> draft badge, not the created badge.
$reusedPanel = renderAdminView('admin.import', array_merge($plainViewData, ['detectedPeriode' => ['quarter' => 2, 'kode_periode' => '2026-Q2', 'nama_periode' => 'April - Juni 2026', 'tanggal_mulai' => '2026-04-01', 'tanggal_selesai' => '2026-06-30', 'created' => false, 'id_periode' => 9999, 'status' => 'proses', 'nama_file_asli' => '']]));
check(str_contains($reusedPanel, 'badge-warning') && str_contains($reusedPanel, 'PROSES'), '(c) reused periode shows its live status badge', '');
check(!str_contains($reusedPanel, 'PERIODE BARU DIBUAT'), '(c) reused periode has no created badge', '');

// ---------------------------------------------------------------------------
// STEP 5 — (d) result card distinguishes success / partial / failed
foreach (['success' => 'result-success', 'partial' => 'result-partial', 'failed' => 'result-failed'] as $st => $cls) {
    $msg = $st === 'success' ? 'Import berhasil' : ($st === 'partial' ? 'Sebagian data dilewati' : 'Import gagal');
    $html = renderAdminView('admin.import', array_merge($plainViewData, [
        'importResult' => ['success' => $st !== 'failed', 'status' => $st, 'message' => $msg, 'total_data' => 10, 'data_berhasil' => $st === 'failed' ? 0 : ($st === 'partial' ? 7 : 10), 'data_gagal' => $st === 'partial' ? 3 : 0],
        'errors' => $st === 'partial' ? ['Baris 4: nilai tidak valid'] : [],
    ]));
    check(str_contains($html, 'id="import-result"') && str_contains($html, $cls), "(d) result card uses the {$st} state class", 'missing ' . $cls);
    check(str_contains($html, 'badge-' . ($st === 'success' ? 'success' : ($st === 'partial' ? 'warning' : 'danger'))), "(d) {$st} badge class", '');
    $wantLabel = $st === 'success' ? 'BERHASIL' : ($st === 'partial' ? 'SEBAGIAN' : 'GAGAL');
    check(str_contains($html, $wantLabel), "(d) {$st} label text", '');
    if ($st === 'partial') {
        check(str_contains($html, 'Baris 4: nilai tidak valid'), '(d) per-row error list rendered', '');
    }
}
// Zero-state: no result card at all.
check(!str_contains($importHtml, 'id="import-result"'), '(d) no result card when importResult is null', '');

// ---------------------------------------------------------------------------
// STEP 6 — (e) Phase 6K auto-detect untouched, end-to-end
$detected = PeriodeDetector::detect($dummyWorkbook);
check(($detected['ok'] ?? false) === true, '(e) detect() succeeds', 'error=' . ($detected['error'] ?? ''));
check(($detected['quarter'] ?? 0) === 2, '(e) detect() -> quarter 2', 'got ' . ($detected['quarter'] ?? '?'));
check(($detected['kode_periode'] ?? '') === '2026-Q2', '(e) detect() -> kode 2026-Q2', 'got ' . ($detected['kode_periode'] ?? '?'));

$probeKode = '2099-Q2';
Database::query('DELETE FROM tb_periode_penilaian WHERE kode_periode = ?', [$probeKode]);

$probeDetected = $detected;
$probeDetected['kode_periode'] = $probeKode;

$probeA = PeriodeDetector::resolveOrCreate($probeDetected, 1);
check(($probeA['ok'] ?? false) === true, '(e) resolveOrCreate (throwaway) succeeds', 'error=' . ($probeA['error'] ?? ''));
check(($probeA['created'] ?? false) === true, '(e) throwaway periode CREATED', '');
$probeId = (int) ($probeA['id_periode'] ?? 0);
check($probeId > 0 && !in_array($probeId, [1, 2, 24, 25], true), '(e) probe id fresh, not 1/2/24/25', "id={$probeId}");

$probeB = PeriodeDetector::resolveOrCreate($probeDetected, 1);
check(($probeB['created'] ?? true) === false, '(e) second resolveOrCreate REUSES (no duplicate)', 'created=' . var_export($probeB['created'] ?? null, true));
check((int) ($probeB['id_periode'] ?? 0) === $probeId, '(e) reuse returns the same id_periode', '');
$probeCount = (int) q1('SELECT COUNT(*) AS c FROM tb_periode_penilaian WHERE kode_periode = ?', [$probeKode])['c'];
check($probeCount === 1, '(e) exactly ONE probe row after two resolves', "got {$probeCount}");

// Import into the throwaway periode with partial OFF (the new default).
$importService = new ExcelImportService();
$importStrict = $importService->import($dummyWorkbook, $probeId, 1, 'dummy_q2_2026.xlsx', false);
check(($importStrict['success'] ?? false) === true, '(e) import with allow_partial=OFF succeeds', 'msg=' . ($importStrict['message'] ?? ''));
check(($importStrict['total_data'] ?? -1) === 130, '(e) import counted 130 rows', 'got ' . ($importStrict['total_data'] ?? -1));
check(($importStrict['data_gagal'] ?? -1) === 0, '(e) 0 failed rows (clean workbook)', 'got ' . ($importStrict['data_gagal'] ?? -1));

cleanupPeriode($probeId);

// ---------------------------------------------------------------------------
// STEP 7 — (f) Explicit id_periode backward-compat, partial OFF and ON
$explicitKode = '2099-Q4';
$explicitId = PeriodePenilaian::create([
    'kode_periode'   => $explicitKode,
    'nama_periode'   => 'TEST6M explicit backward-compat',
    'tanggal_mulai'  => '2026-04-01',
    'tanggal_selesai'=> '2026-06-30',
    'status'         => 'draft',
    'created_by'     => 1,
]);
check($explicitId > 0, '(f) explicit-id test periode created', "id={$explicitId}");

$explicitOff = $importService->import($dummyWorkbook, $explicitId, 1, 'dummy_q2_2026.xlsx', false);
check(($explicitOff['success'] ?? false) === true, '(f) explicit id import works with partial OFF', 'msg=' . ($explicitOff['message'] ?? ''));
check(($explicitOff['total_data'] ?? -1) === 130, '(f) explicit import counted 130 rows', 'got ' . ($explicitOff['total_data'] ?? -1));
cleanupPeriode($explicitId);

$explicitId2 = PeriodePenilaian::create([
    'kode_periode'   => $explicitKode,
    'nama_periode'   => 'TEST6M explicit partial-on',
    'tanggal_mulai'  => '2026-04-01',
    'tanggal_selesai'=> '2026-06-30',
    'status'         => 'draft',
    'created_by'     => 1,
]);
$explicitOn = $importService->import($dummyWorkbook, $explicitId2, 1, 'dummy_q2_2026.xlsx', true);
check(($explicitOn['success'] ?? false) === true, '(f) explicit id import works with partial ON', 'msg=' . ($explicitOn['message'] ?? ''));
check(($explicitOn['status'] ?? '') === 'success', '(f) clean workbook yields status=success even with partial on', 'got ' . var_export($explicitOn['status'] ?? null, true));
cleanupPeriode($explicitId2);

// ---------------------------------------------------------------------------
// STEP 8 — (g)+(h) teardown invariants
foreach (glob(ExcelImportService::getStorageDir() . DIRECTORY_SEPARATOR . '*dummy_q2_2026*') as $f) {
    unlink($f);
}

$afterPenilaian = (int) q1('SELECT COUNT(*) AS c FROM tb_penilaian')['c'];
$afterHasil     = (int) q1('SELECT COUNT(*) AS c FROM tb_hasil')['c'];
$afterPeriode   = (int) q1('SELECT COUNT(*) AS c FROM tb_periode_penilaian')['c'];
$afterLegacyPenilaian = (int) q1('SELECT COUNT(*) AS c FROM tb_penilaian WHERE status_data = "legacy"')['c'];
$afterLegacyHasil     = (int) q1('SELECT COUNT(*) AS c FROM tb_hasil h JOIN tb_periode_penilaian p ON p.id_periode = h.id_periode WHERE p.status = "legacy"')['c'];
$protectedCount = (int) q1('SELECT COUNT(*) AS c FROM tb_periode_penilaian WHERE id_periode IN (1,2,24,25)')['c'];
$probeGone = (int) q1('SELECT COUNT(*) AS c FROM tb_periode_penilaian WHERE kode_periode = ?', [$probeKode])['c'];
$explicitGone = (int) q1('SELECT COUNT(*) AS c FROM tb_periode_penilaian WHERE kode_periode = ?', [$explicitKode])['c'];

echo PHP_EOL . 'TEARDOWN' . PHP_EOL;
check($afterPeriode === $basePeriode, "(g) tb_periode_penilaian restored to {$basePeriode}", "got {$afterPeriode}");
check($afterPenilaian === $basePenilaian, "(g) tb_penilaian restored to {$basePenilaian}", "got {$afterPenilaian}");
check($afterHasil === $baseHasil, "(g) tb_hasil restored to {$baseHasil}", "got {$afterHasil}");
check($afterLegacyPenilaian === 12, '(g) legacy tb_penilaian still 12', "got {$afterLegacyPenilaian}");
check($afterLegacyHasil === 12, '(g) legacy tb_hasil still 12', "got {$afterLegacyHasil}");
check($protectedCount === 4, '(g) protected ids 1/2/24/25 all present', "got {$protectedCount}");
check(hash_file('sha256', __DIR__ . '/../app/services/SawEngineV2.php') === $sawV2HashBefore, '(g) SawEngineV2.php byte-identical', '');
check(hash_file('sha256', __DIR__ . '/../app/services/SawServiceV2.php') === $sawServiceHashBefore, '(g) SawServiceV2.php byte-identical', '');
check($probeGone === 0, '(g) throwaway 2099-Q2 fully removed', "remaining={$probeGone}");
check($explicitGone === 0, '(g) throwaway 2099-Q4 fully removed', "remaining={$explicitGone}");

$routerAfter = file_get_contents(__DIR__ . '/../app/core/Router.php');
check($routerAfter === $routerBefore, '(h) Router.php byte-identical (no new route)', '');
check(hash('sha256', $routerBefore) === hash('sha256', $routerAfter), '(h) route table hash unchanged', '');

echo PHP_EOL . '========================================' . PHP_EOL;
echo 'RESULT: ' . $pass . '/' . ($pass + $fail) . ' PASS' . PHP_EOL;
echo '========================================' . PHP_EOL;

exit($fail === 0 ? 0 : 1);
