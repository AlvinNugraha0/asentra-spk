<?php
// ASENTRA SPK — Phase 6F: Owner Confirmation UX Test
//
// Verifies the full confirmation contract:
//  1. Pre-confirmation review data is complete (counts, warnings, source).
//  2. Only Owner can confirm (admin rejected, inactive rejected).
//  3. Confirmation locks evaluations (status -> confirmed, period -> selesai).
//  4. After confirmation: recalc / import / update / delete are all BLOCKED.
//  5. Legacy periods remain untouched and cannot be confirmed.
//
// SAFETY: creates its OWN isolated period (kode 'TEST6F-2026-Q1'), raw data,
// and evaluations. Everything is removed in teardown.
// tb_penilaian=12 legacy + tb_hasil=12 legacy untouched.

declare(strict_types=1);

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/helpers/view.php';
require_once __DIR__ . '/../app/helpers/format.php';
require_once __DIR__ . '/../app/helpers/url.php';
require_once __DIR__ . '/../app/helpers/auth.php';
require_once __DIR__ . '/../app/models/PeriodePenilaian.php';
require_once __DIR__ . '/../app/models/Penilaian.php';
require_once __DIR__ . '/../app/models/User.php';
require_once __DIR__ . '/../app/models/Teknisi.php';
require_once __DIR__ . '/../app/models/Kedisiplinan.php';
require_once __DIR__ . '/../app/models/Pekerjaan.php';
require_once __DIR__ . '/../app/models/TanggungJawab.php';
require_once __DIR__ . '/../app/services/CalculationEngine.php';
require_once __DIR__ . '/../app/services/calculators/DisciplineCalculator.php';
require_once __DIR__ . '/../app/services/calculators/QualityCalculator.php';
require_once __DIR__ . '/../app/services/calculators/ResponsibilityCalculator.php';
require_once __DIR__ . '/../app/services/AssessmentWorkflowService.php';
require_once __DIR__ . '/../app/services/import/ExcelImportService.php';
require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;
use App\Models\Kedisiplinan;
use App\Models\Pekerjaan;
use App\Models\Penilaian;
use App\Models\PeriodePenilaian;
use App\Models\TanggungJawab;
use App\Models\Teknisi;
use App\Models\User;
use App\Services\AssessmentWorkflowService;
use App\Services\Import\ExcelImportService;
use App\Services\Import\ExcelTemplateHelper;

$pass = 0;
$fail = 0;
$testKode = 'TEST6F-2026-Q1';
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

function isoDate(int $month, int $day): string
{
    return sprintf('2026-%02d-%02d', $month, $day);
}

function cleanup(string $kode): void
{
    $row = Database::query('SELECT id_periode FROM tb_periode_penilaian WHERE kode_periode = ?', [$kode])->fetch();
    if (!$row) {
        return;
    }
    $pid = (int) $row['id_periode'];
    foreach (['tb_hasil', 'tb_penilaian', 'tb_kedisiplinan', 'tb_pekerjaan', 'tb_tanggung_jawab'] as $t) {
        Database::query("DELETE FROM {$t} WHERE id_periode = ?", [$pid]);
    }
    Database::query('DELETE FROM tb_import WHERE id_periode = ?', [$pid]);
    Database::query('DELETE FROM tb_periode_penilaian WHERE id_periode = ?', [$pid]);
}

echo '========================================' . PHP_EOL;
echo 'PHASE 6F — OWNER CONFIRMATION UX TEST' . PHP_EOL;
echo '========================================' . PHP_EOL;

$basePenilaian = (int) Database::query('SELECT COUNT(*) AS c FROM tb_penilaian')->fetch()['c'];
$baseHasil     = (int) Database::query('SELECT COUNT(*) AS c FROM tb_hasil')->fetch()['c'];
echo "Baseline: penilaian={$basePenilaian} hasil={$baseHasil}" . PHP_EOL . PHP_EOL;

cleanup($testKode);

// ---------- setup ----------
$testPeriodeId = PeriodePenilaian::create([
    'kode_periode'    => $testKode,
    'nama_periode'    => 'TEST Phase 6F Q1 2026',
    'tanggal_mulai'   => '2026-01-01',
    'tanggal_selesai' => '2026-03-31',
    'status'          => 'draft',
    'created_by'      => 1,
]);

$teknisi = Teknisi::all();
$t1 = $teknisi[0]; // complete
$t2 = $teknisi[1]; // partial (2 months)

foreach ([1, 2, 3] as $m) {
    Kedisiplinan::upsert([
        'id_periode' => $testPeriodeId, 'id_teknisi' => (int) $t1['id'], 'bulan' => $m,
        'total_hari_kerja' => 22, 'hadir' => 21, 'sakit' => 1, 'izin' => 0, 'alpa' => 0,
        'terlambat' => 1, 'pekerjaan_terjadwal' => 8, 'sesuai_jadwal' => 7,
    ]);
}
foreach ([1, 2] as $m) {
    Kedisiplinan::upsert([
        'id_periode' => $testPeriodeId, 'id_teknisi' => (int) $t2['id'], 'bulan' => $m,
        'total_hari_kerja' => 22, 'hadir' => 18, 'sakit' => 2, 'izin' => 2, 'alpa' => 0,
        'terlambat' => 4, 'pekerjaan_terjadwal' => 8, 'sesuai_jadwal' => 5,
    ]);
}

foreach ([$t1, $t2] as $idx => $t) {
    $months = $idx === 0 ? [1, 2, 3] : [1, 2];
    foreach ($months as $m) {
        foreach ([5, 15] as $day) {
            Pekerjaan::create([
                'id_periode' => $testPeriodeId,
                'id_teknisi' => (int) $t['id'],
                'tanggal' => isoDate($m, $day),
                'bulan' => $m,
                'nama_pekerjaan' => 'Servis',
                'rapi' => 1,
                'presisi' => 1,
                'sesuai_desain' => 0,
            ]);
        }
    }
}

foreach ([$t1, $t2] as $idx => $t) {
    $months = $idx === 0 ? [1, 2, 3] : [1, 2];
    foreach ($months as $m) {
        TanggungJawab::upsert([
            'id_periode' => $testPeriodeId, 'id_teknisi' => (int) $t['id'], 'bulan' => $m,
            'perawatan_alat' => 4, 'efisiensi_material' => 3, 'inisiatif' => 3, 'kepatuhan_prosedur' => 4,
        ]);
    }
}

$workflow = new AssessmentWorkflowService();

$owner = User::findByUsername('owner');
$admin = User::findByUsername('admin');
$ownerId = $owner !== null ? (int) $owner['id'] : 0;
$adminId = $admin !== null ? (int) $admin['id'] : 0;
check($ownerId > 0, 'Owner user exists', 'id=' . $ownerId);
check($adminId > 0, 'Admin user exists', 'id=' . $adminId);

// ================================================ 1. PRE-CONFIRM REVIEW DATA
echo PHP_EOL . '-- pre-confirm review data --' . PHP_EOL;
$workflow->calculatePeriod($testPeriodeId, $adminId);

$summary = $workflow->getPeriodAssessmentSummary($testPeriodeId);
$summary['tabulasi'] = Penilaian::tabulasiByPeriode($testPeriodeId);

check(($summary['periode']['kode_periode'] ?? '') === $testKode, 'Summary shows periode kode');
check(($summary['periode']['nama_periode'] ?? '') === 'TEST Phase 6F Q1 2026', 'Summary shows periode nama');
check($summary['total_evaluations'] === 2, 'Summary shows jumlah teknisi = 2', 'got ' . (string) $summary['total_evaluations']);
check($summary['calculated_count'] === 1, 'Summary shows data lengkap = 1', 'got ' . (string) $summary['calculated_count']);
check($summary['partial_count'] === 1, 'Summary shows data partial = 1', 'got ' . (string) $summary['partial_count']);
check($summary['has_warnings'] === true, 'Summary flags warnings present');

$warnCount = 0;
foreach ($summary['tabulasi'] as $row) {
    if (!empty($row['warning'])) {
        $warnCount++;
    }
}
check($warnCount === 1, 'Tabulasi warning count = 1 (partial technician)', 'got ' . (string) $warnCount);

// Confirm page renders with all required sections
$html = viewPartial('owner.assessment_confirm', [
    'title' => 'Konfirmasi Penilaian',
    'subtitle' => 'test',
    'summary' => $summary,
]);
check($html !== '' && strlen($html) > 1000, 'Confirm page renders', strlen($html) . ' bytes');
check(str_contains($html, 'Ringkasan Periode'), 'Confirm page: ringkasan periode section');
check(str_contains($html, 'Jumlah Teknisi'), 'Confirm page: jumlah teknisi stat');
check(str_contains($html, 'Data Lengkap (3 bulan)'), 'Confirm page: data lengkap stat');
check(str_contains($html, 'Data Partial'), 'Confirm page: data partial stat');
check(str_contains($html, 'Jumlah Warning'), 'Confirm page: jumlah warning stat');
check(str_contains($html, 'Konsekuensi Konfirmasi'), 'Confirm page: konsekuensi section');
check(str_contains($html, 'DIKUNCI'), 'Confirm page: states that values are locked');
check(str_contains($html, 'SELESAI'), 'Confirm page: states period becomes selesai');
check(str_contains($html, 'tidak dapat diubah'), 'Confirm page: states data cannot be changed');
check(str_contains($html, 'Konfirmasi & Kunci Penilaian'), 'Confirm page: confirm button present');
check(str_contains($html, 'csrf_token'), 'Confirm page: CSRF token in form');

// ================================================ 2. AUTHORIZATION
echo PHP_EOL . '-- authorization: only Owner --' . PHP_EOL;
try {
    $workflow->confirmAssessment($testPeriodeId, $adminId);
    check(false, 'Admin CANNOT confirm (must throw)');
} catch (Throwable $e) {
    check(str_contains($e->getMessage(), 'Hanya Owner'), 'Admin confirm rejected with Owner-only message', substr($e->getMessage(), 0, 60));
}

// Inactive owner rejected
Database::query('UPDATE tb_user SET status = "inactive" WHERE id = ?', [$ownerId]);
try {
    $workflow->confirmAssessment($testPeriodeId, $ownerId);
    check(false, 'Inactive Owner CANNOT confirm (must throw)');
} catch (Throwable $e) {
    check(true, 'Inactive Owner confirm rejected');
}
Database::query('UPDATE tb_user SET status = "active" WHERE id = ?', [$ownerId]);

// ================================================ 3. CONFIRMATION
echo PHP_EOL . '-- confirmation executes --' . PHP_EOL;
$before = Penilaian::findByPeriodeId($testPeriodeId);
$c1Before = (float) $before[0]['c1'];

$result = $workflow->confirmAssessment($testPeriodeId, $ownerId);
check($result['success'] === true, 'confirmAssessment returns success');
check(($result['confirmed_count'] ?? 0) === 2, 'confirmed_count = 2 teknisi', 'got ' . (string) ($result['confirmed_count'] ?? -1));

$after = Penilaian::findByPeriodeId($testPeriodeId);
$confirmedRows = array_filter($after, static fn (array $r): bool => ($r['status_data'] ?? '') === 'confirmed');
check(count($confirmedRows) === 2, 'All 2 evaluations status -> CONFIRMED', 'got ' . count($confirmedRows));
foreach ($after as $r) {
    check($r['confirmed_by'] === $ownerId, 'confirmed_by = Owner id for teknisi ' . (string) $r['teknisi_id']);
    check($r['confirmed_at'] !== null, 'confirmed_at set for teknisi ' . (string) $r['teknisi_id']);
}

$periodeAfter = PeriodePenilaian::findById($testPeriodeId);
check(($periodeAfter['status'] ?? '') === 'selesai', 'Periode status -> SELESAI', 'got ' . (string) ($periodeAfter['status'] ?? ''));

// Values unchanged by confirmation (lock = freeze, not recompute)
$afterRow = $after[0];
check(abs((float) $afterRow['c1'] - $c1Before) < 0.000001, 'C1 value frozen (unchanged by confirmation)', 'before ' . $c1Before . ' after ' . (float) $afterRow['c1']);

// Double confirm rejected
try {
    $workflow->confirmAssessment($testPeriodeId, $ownerId);
    check(false, 'Double confirm rejected (must throw)');
} catch (Throwable $e) {
    check(str_contains($e->getMessage(), 'sudah dikonfirmasi'), 'Double confirm rejected', substr($e->getMessage(), 0, 60));
}

// Confirm page now shows locked state
$summary2 = $workflow->getPeriodAssessmentSummary($testPeriodeId);
$summary2['tabulasi'] = Penilaian::tabulasiByPeriode($testPeriodeId);
$html2 = viewPartial('owner.assessment_confirm', [
    'title' => 'Konfirmasi Penilaian',
    'subtitle' => 'test',
    'summary' => $summary2,
]);
check(str_contains($html2, 'sudah dikonfirmasi resmi'), 'Confirm page: shows locked/confirmed state after confirm');
check(str_contains($html2, 'Konfirmasi & Kunci Penilaian') === false, 'Confirm page: NO confirm button after lock');
check(str_contains($html2, 'Kembali ke Review'), 'Confirm page: has back link');

// ================================================ 4. LOCK ENFORCEMENT
echo PHP_EOL . '-- lock enforcement (data cannot change) --' . PHP_EOL;

// 4a. Recalculation blocked
$canCalc = PeriodePenilaian::canCalculate($testPeriodeId);
check($canCalc['allowed'] === false, 'canCalculate() -> false after confirm');
check(str_contains($canCalc['reason'] ?? '', 'sudah dikonfirmasi'), 'canCalculate reason mentions confirmed');

try {
    $workflow->calculatePeriod($testPeriodeId, $adminId);
    check(false, 'Recalculate after confirm CANNOT run');
} catch (Throwable $e) {
    check(str_contains($e->getMessage(), 'sudah dikonfirmasi'), 'Recalculate blocked with clear reason', substr($e->getMessage(), 0, 70));
}

// 4b. Update blocked at model level
$lockedId = (int) $after[0]['id'];
try {
    Penilaian::update($lockedId, [
        'teknisi_id' => (int) $after[0]['teknisi_id'],
        'periode' => (string) $after[0]['periode'],
        'c1' => 1.0,
        'c2' => 1.0,
        'c3' => 1.0,
    ]);
    check(false, 'Penilaian::update on confirmed row CANNOT run');
} catch (Throwable $e) {
    check(str_contains($e->getMessage(), 'tidak dapat diubah'), 'Model update guard fires', substr($e->getMessage(), 0, 60));
}

// DB truly unchanged
$recheck = Penilaian::findById($lockedId);
check(abs((float) $recheck['c1'] - $c1Before) < 0.000001, 'C1 still frozen after attempted update', 'got ' . (float) $recheck['c1']);

// 4c. Delete blocked at model level
try {
    Penilaian::delete($lockedId);
    check(false, 'Penilaian::delete on confirmed row CANNOT run');
} catch (Throwable $e) {
    check(str_contains($e->getMessage(), 'tidak dapat dihapus'), 'Model delete guard fires', substr($e->getMessage(), 0, 60));
}
check(Penilaian::findById($lockedId) !== null, 'Row still exists after attempted delete');

// 4d. Import blocked
$tmpXlsx = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'test6f_lock.xlsx';
$sheet = ExcelTemplateHelper::createSpreadsheet();
ExcelTemplateHelper::saveToFile($sheet, $tmpXlsx);
$importService = new ExcelImportService();
$imp = $importService->import($tmpXlsx, $testPeriodeId, $adminId, 'test6f_lock.xlsx', allowPartial: true);
check(($imp['success'] ?? false) === false, 'Import to confirmed period rejected', 'got ' . (string) ($imp['success'] ?? null));
check(str_contains((string) ($imp['message'] ?? ''), 'sudah dikonfirmasi'), 'Import rejection message mentions confirmed lock', substr((string) ($imp['message'] ?? ''), 0, 70));
@unlink($tmpXlsx);

// Raw tables untouched by the blocked import
$rawCount = (int) Database::query('SELECT COUNT(*) AS c FROM tb_kedisiplinan WHERE id_periode = ?', [$testPeriodeId])->fetch()['c'];
check($rawCount === 5, 'Raw kedisiplinan still 5 (import added nothing)', 'got ' . $rawCount);

// 4e. isPeriodConfirmed flag
check(Penilaian::isPeriodConfirmed($testPeriodeId) === true, 'isPeriodConfirmed() = true');

// ================================================ 5. LEGACY PROTECTION
echo PHP_EOL . '-- legacy protection --' . PHP_EOL;
$legacyPeriode = Database::query('SELECT id_periode FROM tb_periode_penilaian WHERE status = "legacy" LIMIT 1')->fetch();
if ($legacyPeriode) {
    $lpId = (int) $legacyPeriode['id_periode'];
    try {
        $workflow->confirmAssessment($lpId, $ownerId);
        check(false, 'Legacy period CANNOT be confirmed');
    } catch (Throwable $e) {
        check(str_contains($e->getMessage(), 'legacy'), 'Legacy confirm rejected', substr($e->getMessage(), 0, 60));
    }
} else {
    check(true, 'No legacy period present (skip)');
}

// ================================================ TEARDOWN
cleanup($testKode);

$afterPenilaian = (int) Database::query('SELECT COUNT(*) AS c FROM tb_penilaian')->fetch()['c'];
$afterHasil     = (int) Database::query('SELECT COUNT(*) AS c FROM tb_hasil')->fetch()['c'];
check($afterPenilaian === $basePenilaian, "tb_penilaian restored to baseline {$basePenilaian}", 'got ' . $afterPenilaian);
check($afterHasil === $baseHasil, "tb_hasil restored to baseline {$baseHasil}", 'got ' . $afterHasil);

$legacy = (int) Database::query('SELECT COUNT(*) AS c FROM tb_penilaian WHERE status_data = "legacy"')->fetch()['c'];
check($legacy === 12, 'Legacy tb_penilaian still 12', 'got ' . $legacy);
$legacyHasil = (int) Database::query('SELECT COUNT(*) AS c FROM tb_hasil h JOIN tb_periode_penilaian p ON p.id_periode = h.id_periode WHERE p.status = "legacy"')->fetch()['c'];
check($legacyHasil === 12, 'Legacy tb_hasil still 12', 'got ' . $legacyHasil);

echo PHP_EOL . '========================================' . PHP_EOL;
echo 'RESULT: ' . $pass . '/' . ($pass + $fail) . ' PASS' . PHP_EOL;
echo '========================================' . PHP_EOL;

exit($fail === 0 ? 0 : 1);
