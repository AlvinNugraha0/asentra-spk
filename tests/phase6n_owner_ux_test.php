<?php
// ASENTRA SPK — Phase 6N: OWNER UX CLEANUP
//
// Verifies the owner UI now exposes only the V2 assessment workflow:
//   (a) owner sidebar: no live link to /owner/penilaian or /owner/riwayat-penilaian;
//       exactly ONE "Penilaian Kinerja" entry -> /owner/assessment
//   (b) owner dashboard: zero hrefs containing '/owner/penilaian' (all 4 rewritten)
//   (c) GET /owner/penilaian        still renders 200 (backward compat)
//   (d) GET /owner/penilaian/create still renders 200 (backward compat)
//   (e) GET /owner/riwayat-penilaian still renders 200 (backward compat)
//   (f) progress card uses the V2 periode (Q1-2026 -> 'Januari - Maret 2026'),
//       NOT a YYYY-MM string; dinilaiCount = 10 for that periode
//   (g) $criteriaAvg no longer computed/passed by OwnerDashboardController
//   (h) SawEngineV2/SawServiceV2, Penilaian.php, OwnerPenilaianController.php,
//       Router.php all byte-identical
//   (i) legacy 12/12 + baseline 22/22 preserved; 4 periode rows; ids 1/2/24/25
//   (j) /owner/assessment renders with the V2 tabulasi intact
//
// SAFETY: read-only UI cleanup. No rows written or deleted. (c)/(d)/(e) render
// the V1 controller methods in-process (requireOwner satisfied via session),
// never over HTTP, so no server is needed.

declare(strict_types=1);

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/models/PeriodePenilaian.php';
require_once __DIR__ . '/../app/models/Penilaian.php';
require_once __DIR__ . '/../app/models/Hasil.php';
require_once __DIR__ . '/../app/models/Teknisi.php';

require_once __DIR__ . '/../app/helpers/url.php';
require_once __DIR__ . '/../app/helpers/format.php';
require_once __DIR__ . '/../app/helpers/view.php';
require_once __DIR__ . '/../app/helpers/auth.php';

// Router defines renderWithLayout/captureView/render used by the controllers.
require_once __DIR__ . '/../app/core/Router.php';

$autoload = dirname(__DIR__) . '/vendor/autoload.php';
if (is_file($autoload)) {
    require_once $autoload;
}

use App\Core\Database;
use App\Controllers\OwnerDashboardController;
use App\Controllers\OwnerPenilaianController;
use App\Controllers\OwnerAssessmentController;
use App\Models\Hasil;
use App\Models\Penilaian;
use App\Models\PeriodePenilaian;

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
 * Establish an owner session so requireOwner()/requireRole('owner') pass when
 * controllers are invoked in-process.
 */
function loginAsOwner(): void
{
    $_SESSION['user'] = ['id' => 2, 'nama' => 'Owner Test', 'role' => 'owner'];
    $_SESSION['csrf_token'] = 'phase6n-token';
}

/**
 * Invoke a controller method in-process and capture its rendered output.
 * renderWithLayout echoes, so buffer it.
 */
function callController(callable $fn): string
{
    ob_start();
    try {
        $fn();
    } finally {
        return ob_get_clean();
    }
}

// ---------------------------------------------------------------------------
echo '========================================' . PHP_EOL;
echo 'PHASE 6N — OWNER UX CLEANUP' . PHP_EOL;
echo '========================================' . PHP_EOL;

// STEP 0 — Baseline + golden invariants + golden hashes
$basePenilaian = (int) q1('SELECT COUNT(*) AS c FROM tb_penilaian')['c'];
$baseHasil     = (int) q1('SELECT COUNT(*) AS c FROM tb_hasil')['c'];
$basePeriode   = (int) q1('SELECT COUNT(*) AS c FROM tb_periode_penilaian')['c'];
$legacyPenilaian = (int) q1('SELECT COUNT(*) AS c FROM tb_penilaian WHERE status_data = "legacy"')['c'];
$legacyHasil     = (int) q1('SELECT COUNT(*) AS c FROM tb_hasil h JOIN tb_periode_penilaian p ON p.id_periode = h.id_periode WHERE p.status = "legacy"')['c'];
$activeTeknisi   = (int) q1('SELECT COUNT(*) AS c FROM tb_teknisi WHERE status = "active"')['c'];

$goldenFiles = [
    'SawEngineV2.php'            => 'app/services/SawEngineV2.php',
    'SawServiceV2.php'           => 'app/services/SawServiceV2.php',
    'Penilaian.php'              => 'app/models/Penilaian.php',
    'OwnerPenilaianController.php' => 'app/controllers/OwnerPenilaianController.php',
    'Router.php'                 => 'app/core/Router.php',
];
$hashBefore = [];
foreach ($goldenFiles as $label => $rel) {
    $hashBefore[$label] = hash_file('sha256', dirname(__DIR__) . '/' . $rel);
    check($hashBefore[$label] !== false, "(h) {$label} readable for hashing", '');
}

echo "Baseline: periode={$basePeriode} penilaian={$basePenilaian} hasil={$baseHasil} legacy={$legacyPenilaian}/{$legacyHasil} activeTeknisi={$activeTeknisi}" . PHP_EOL . PHP_EOL;

check($legacyPenilaian === 12, 'Legacy tb_penilaian = 12 at baseline', "got {$legacyPenilaian}");
check($legacyHasil === 12, 'Legacy tb_hasil = 12 at baseline', "got {$legacyHasil}");
check($basePenilaian === 22 && $baseHasil === 22, 'Baseline totals 22/22', "got {$basePenilaian}/{$baseHasil}");
check($basePeriode === 4, 'Baseline 4 periode rows', "got {$basePeriode}");
check($activeTeknisi === 10, 'Baseline 10 active teknisi', "got {$activeTeknisi}");

// ---------------------------------------------------------------------------
// STEP 1 — (a) Owner sidebar: one "Penilaian Kinerja" -> /owner/assessment,
//          no live V1 links.
loginAsOwner();
$_SERVER['REQUEST_URI'] = '/owner/dashboard';
$_SERVER['REQUEST_METHOD'] = 'GET';

$ownerLayout = viewPartial('layouts.app', ['title' => 'Dashboard Owner', 'content' => '<main></main>']);

// The V1 routes are still served by the app (backward compat) and the V1
// labels survive inside the commented block — what must be gone is a LIVE
// anchor pointing at them. So assert on <a href>, not on bare strings.
// route() returns an absolute URL, so match with str_contains.
$liveAnchors = [];
if (preg_match_all('#<a\s[^>]*href="([^"]+)"#i', $ownerLayout, $m)) {
    $liveAnchors = $m[1];
}
$v1OwnerLinks = array_values(array_filter($liveAnchors, static function (string $href): bool {
    return str_contains($href, '/owner/penilaian') || str_contains($href, '/owner/riwayat-penilaian');
}));
check($v1OwnerLinks === [], '(a) owner sidebar has NO live <a> to /owner/penilaian* or /owner/riwayat-penilaian', 'found: ' . implode(', ', $v1OwnerLinks));

$pkItems = array_values(array_filter($liveAnchors, static fn (string $href): bool => str_contains($href, '/owner/assessment')));
check(count($pkItems) === 1, '(a) exactly ONE owner link to /owner/assessment', 'found ' . count($pkItems));
check(str_contains($ownerLayout, 'Penilaian Kinerja'), '(a) sidebar renders the label "Penilaian Kinerja"', 'label missing');
check(!str_contains($ownerLayout, 'Review Penilaian V2'), '(a) sidebar no longer says "Review Penilaian V2"', 'label still present');
// The consolidated entry must be the V2 route, not a repurposed V1 one.
check(count($pkItems) === 1 && str_contains($pkItems[0], '/owner/assessment'), '(a) the single "Penilaian Kinerja" link is /owner/assessment', 'got ' . implode(', ', $pkItems));

// Admin sidebar untouched.
$_SESSION['user'] = ['id' => 1, 'nama' => 'Admin Test', 'role' => 'admin'];
$adminLayout = viewPartial('layouts.app', ['title' => 'Admin', 'content' => '<main></main>']);
check(str_contains($adminLayout, '/admin/import'), '(a) admin sidebar still links /admin/import', 'admin nav broken');
check(!str_contains($adminLayout, '/owner/assessment'), '(a) admin sidebar has no owner entry', 'owner nav leaked');
$_SESSION['user'] = ['id' => 2, 'nama' => 'Owner Test', 'role' => 'owner'];

// ---------------------------------------------------------------------------
// STEP 2 — (b) Owner dashboard view: no /owner/penilaian href anywhere.
$dashHtml = viewPartial('owner.dashboard', [
    'title' => 'Dashboard Owner',
    'subtitle' => 'Ringkasan evaluasi kinerja teknisi.',
    'activeTeknisi' => $activeTeknisi,
    'processedPeriode' => Hasil::latestPeriode(),
    'evaluatedCount' => 10,
    'topResult' => null,
    'results' => [],
    'trendSummary' => Hasil::getTrendSummary(),
    'activePenilaianPeriode' => Hasil::latestPeriode(),
    'dinilaiCount' => 10,
    'belumDinilaiCount' => 0,
]);
check(!str_contains($dashHtml, '/owner/penilaian'), '(b) owner dashboard has NO href to /owner/penilaian', 'link still present');
check(str_contains($dashHtml, '/owner/assessment'), '(b) owner dashboard links /owner/assessment', 'V2 link missing');
check(!str_contains($dashHtml, 'criteriaAvg'), '(b) owner dashboard no longer references $criteriaAvg', 'dead var still referenced');

// Count the rewritten cross-links: banner "Input Penilaian", "Lihat Semua",
// quick-action "Input Penilaian", quick-action "Penilaian Kinerja".
$dashAnchors = [];
if (preg_match_all('#<a\s[^>]*href="([^"]+)"#i', $dashHtml, $m)) {
    $dashAnchors = $m[1];
}
$assessmentLinks = array_values(array_filter($dashAnchors, static fn (string $h): bool => str_contains($h, '/owner/assessment')));
check(count($assessmentLinks) >= 4, '(b) at least 4 dashboard links now point at /owner/assessment', 'found ' . count($assessmentLinks));

// ---------------------------------------------------------------------------
// STEP 3 — (f) V2 progress stats. Drive the real controller, not a stub.
$processedPeriode = Hasil::latestPeriode();
check($processedPeriode === 'Q1-2026', '(f) Hasil::latestPeriode() = Q1-2026 (V2, not YYYY-MM)', "got {$processedPeriode}");
check(Penilaian::countEvaluatedByPeriode('Q1-2026') === 10, '(f) countEvaluatedByPeriode("Q1-2026") = 10', '');
$v2Label = periodLabel((string) $processedPeriode);
check($v2Label === 'Januari - Maret 2026 (2026-01-01 s/d 2026-03-31)', "(f) periodLabel(Q1-2026) resolves the stored V2 period", "got {$v2Label}");
check(str_contains($v2Label, 'Januari - Maret 2026'), "(f) periodLabel(Q1-2026) contains the V2 quarter range", "got {$v2Label}");
check(!preg_match('/^\d{4}-\d{2}$/', (string) $processedPeriode), '(f) active periode is NOT a YYYY-MM string', "got {$processedPeriode}");

$ownerDashHtml = '';
$dashEx = null;
try {
    loginAsOwner();
    $ownerDashHtml = callController(static function (): void {
        (new OwnerDashboardController())->index();
    });
} catch (Throwable $e) {
    $dashEx = $e;
}
check($dashEx === null, '(f) OwnerDashboardController::index() renders without throwing', $dashEx !== null ? $dashEx->getMessage() : '');
check(str_contains($ownerDashHtml, 'Progress Penilaian'), '(f) progress card rendered', 'card missing');
check(str_contains($ownerDashHtml, $v2Label), "(f) progress card heading shows the V2 label '{$v2Label}'", 'heading missing');
check(!str_contains($ownerDashHtml, 'September 2026'), '(f) progress card does NOT show the V1 YYYY-MM label', 'V1 label present');
check(str_contains($ownerDashHtml, '10 dari 10 teknisi sudah dinilai'), '(f) progress card says "10 dari 10 teknisi sudah dinilai"', 'count text mismatch');

// ---------------------------------------------------------------------------
// STEP 4 — (g) $criteriaAvg removed from the owner path only.
$controllerSrc = (string) file_get_contents(dirname(__DIR__) . '/app/controllers/OwnerDashboardController.php');
// The removal must hold even inside the explaining comment, so the test
// greps the live code path only (strip /* ... */ and // comments first).
$controllerLiveCode = $controllerSrc;
$controllerLiveCode = (string) preg_replace('#/\*.*?\*/#s', '', $controllerLiveCode);
$controllerLiveCode = (string) preg_replace('#//.*$#m', '', $controllerLiveCode);
check(!str_contains($controllerLiveCode, 'criteriaAvg'), '(g) OwnerDashboardController no longer computes/passes $criteriaAvg', 'still present');
check(!str_contains($controllerLiveCode, 'getCriteriaAverages'), '(g) OwnerDashboardController no longer calls getCriteriaAverages', 'call still present');
check(str_contains((string) file_get_contents(dirname(__DIR__) . '/app/controllers/AdminDashboardController.php'), 'getCriteriaAverages'), '(g) AdminDashboardController still uses getCriteriaAverages (unchanged)', 'admin regression');
check(str_contains($controllerSrc, 'Penilaian::countEvaluatedByPeriode'), '(g) owner still uses Penilaian::countEvaluatedByPeriode (method untouched)', 'count call missing');

// ---------------------------------------------------------------------------
// STEP 5 — (c)(d)(e) V1 owner pages still render (backward compat, in-process).
$riwayatEx = null;
try {
    loginAsOwner();
    $riwayatHtml = callController(static function (): void {
        (new OwnerPenilaianController())->history();
    });
} catch (Throwable $e) {
    $riwayatHtml = '';
    $riwayatEx = $e;
}
check($riwayatEx === null && strlen($riwayatHtml) > 0, '(e) OwnerPenilaianController::history() renders (V1 page intact)', $riwayatEx !== null ? $riwayatEx->getMessage() : 'empty output');

$createEx = null;
try {
    loginAsOwner();
    $createForm = callController(static function (): void {
        (new OwnerPenilaianController())->create();
    });
} catch (Throwable $e) {
    $createForm = '';
    $createEx = $e;
}
check($createEx === null && strlen($createForm) > 0, '(d) OwnerPenilaianController::create() renders (V1 page intact)', $createEx !== null ? $createEx->getMessage() : 'empty output');

$indexEx = null;
try {
    loginAsOwner();
    $listHtml = callController(static function (): void {
        (new OwnerPenilaianController())->index();
    });
} catch (Throwable $e) {
    $listHtml = '';
    $indexEx = $e;
}
check($indexEx === null && strlen($listHtml) > 0, '(c) OwnerPenilaianController::index() renders (V1 page intact)', $indexEx !== null ? $indexEx->getMessage() : 'empty output');

// ---------------------------------------------------------------------------
// STEP 6 — (j) /owner/assessment still renders the V2 tabulasi.
$_SERVER['REQUEST_URI'] = '/owner/assessment?mode=tabulasi';
$assessEx = null;
try {
    loginAsOwner();
    $assessHtml = callController(static function (): void {
        (new OwnerAssessmentController())->index();
    });
} catch (Throwable $e) {
    $assessHtml = '';
    $assessEx = $e;
}
check($assessEx === null && strlen($assessHtml) > 0, '(j) OwnerAssessmentController::index() renders', $assessEx !== null ? $assessEx->getMessage() : 'empty output');
check(str_contains($assessHtml, 'tabulasi') || str_contains($assessHtml, 'Tabulasi') || str_contains($assessHtml, 'Tabel'), '(j) V2 tabulasi section rendered', 'tabulasi missing');
$v2Periods = PeriodePenilaian::all();
check(count($v2Periods) === $basePeriode, '(j) periode rows intact for the assessment view', 'got ' . count($v2Periods));

$_SERVER['REQUEST_URI'] = '/owner/dashboard';

// ---------------------------------------------------------------------------
// STEP 7 — (h) golden files byte-identical
echo PHP_EOL . 'TEARDOWN' . PHP_EOL;
foreach ($goldenFiles as $label => $rel) {
    check(hash_file('sha256', dirname(__DIR__) . '/' . $rel) === $hashBefore[$label], "(h) {$label} byte-identical", '');
}

// ---------------------------------------------------------------------------
// STEP 8 — (i) DB untouched
$afterPenilaian = (int) q1('SELECT COUNT(*) AS c FROM tb_penilaian')['c'];
$afterHasil     = (int) q1('SELECT COUNT(*) AS c FROM tb_hasil')['c'];
$afterPeriode   = (int) q1('SELECT COUNT(*) AS c FROM tb_periode_penilaian')['c'];
$afterLegacyPenilaian = (int) q1('SELECT COUNT(*) AS c FROM tb_penilaian WHERE status_data = "legacy"')['c'];
$afterLegacyHasil     = (int) q1('SELECT COUNT(*) AS c FROM tb_hasil h JOIN tb_periode_penilaian p ON p.id_periode = h.id_periode WHERE p.status = "legacy"')['c'];
$protectedCount = (int) q1('SELECT COUNT(*) AS c FROM tb_periode_penilaian WHERE id_periode IN (1,2,24,25)')['c'];
$distinctPeriode = (int) q1('SELECT COUNT(*) AS c FROM (SELECT DISTINCT periode FROM tb_penilaian) x')['c'];

check($afterPeriode === $basePeriode, "(i) tb_periode_penilaian unchanged at {$basePeriode}", "got {$afterPeriode}");
check($afterPenilaian === $basePenilaian, "(i) tb_penilaian unchanged at {$basePenilaian}", "got {$afterPenilaian}");
check($afterHasil === $baseHasil, "(i) tb_hasil unchanged at {$baseHasil}", "got {$afterHasil}");
check($afterLegacyPenilaian === 12, '(i) legacy tb_penilaian still 12', "got {$afterLegacyPenilaian}");
check($afterLegacyHasil === 12, '(i) legacy tb_hasil still 12', "got {$afterLegacyHasil}");
check($protectedCount === 4, '(i) protected ids 1/2/24/25 all present', "got {$protectedCount}");
check($distinctPeriode === 3, '(i) 3 distinct periode codes (2026-08 / 2026-09 / Q1-2026)', "got {$distinctPeriode}");

echo PHP_EOL . '========================================' . PHP_EOL;
echo 'RESULT: ' . $pass . '/' . ($pass + $fail) . ' PASS' . PHP_EOL;
echo '========================================' . PHP_EOL;

exit($fail === 0 ? 0 : 1);
