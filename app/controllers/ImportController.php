<?php
// ASENTRA SPK — Import Controller (Admin)
// Controller handles HTTP request, CSRF, and delegation to ExcelImportService.
// STRICT: NO Excel parsing logic inside the controller!

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Import;
use App\Models\Penilaian;
use App\Models\PeriodePenilaian;
use App\Services\Import\ExcelImportService;
use App\Services\Import\PeriodeDetector;

class ImportController
{
    private ExcelImportService $importService;

    public function __construct()
    {
        $this->importService = new ExcelImportService();
    }

    /**
     * Tampilkan form upload Excel, kelola periode, & riwayat import (satu halaman).
     */
    public function index(): void
    {
        requireAdmin();

        // Only active/non-legacy periods are eligible for new imports
        $allPeriods = PeriodePenilaian::allWithProgress();
        $periods = array_filter($allPeriods, static function (array $p): bool {
            return ($p['status'] ?? '') !== 'legacy';
        });

        $recentImports = Import::all();

        renderWithLayout('admin/import', [
            'title' => 'Import Data Operasional V2',
            'subtitle' => 'Upload file Excel — sistem mendeteksi periode kuartal secara otomatis.',
            'periods' => array_values($periods),
            'allPeriods' => $allPeriods,
            'recentImports' => array_slice($recentImports, 0, 10),
            'errors' => $_SESSION['import_errors'] ?? [],
            'importResult' => $_SESSION['import_result'] ?? null,
            'detectedPeriode' => $_SESSION['detected_periode'] ?? null,
            'form_errors' => $_SESSION['form_errors'] ?? [],
            'form_old' => $_SESSION['form_old'] ?? [],
        ]);

        unset(
            $_SESSION['import_errors'],
            $_SESSION['import_result'],
            $_SESSION['detected_periode'],
            $_SESSION['form_errors'],
            $_SESSION['form_old']
        );
    }

    /**
     * Proses unggahan file Excel.
     *
     * Jalur default: auto-detect periode dari data Excel -> buat jika belum ada -> import.
     * Jalur explicit (opsi lanjutan): pakai id_periode yang dipilih admin (backward compat).
     */
    public function store(): void
    {
        requireAdmin();
        csrfCheck();

        $user = currentUser();
        $userId = (int) ($user['id'] ?? 0);
        $explicitPeriodeId = filter_input(INPUT_POST, 'id_periode', FILTER_VALIDATE_INT);
        $allowPartial = isset($_POST['allow_partial']);

        if (empty($_FILES['file_excel']) || !is_uploaded_file($_FILES['file_excel']['tmp_name'])) {
            setFlash('Silakan pilih file Excel (.xlsx) yang akan diunggah.', 'error');
            redirect('/admin/import');
        }

        $file = $_FILES['file_excel'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            setFlash('Terjadi kesalahan saat mengunggah file (Kode: ' . $file['error'] . ').', 'error');
            redirect('/admin/import');
        }

        $tmpPath = $file['tmp_name'];
        $originalName = $file['name'] ?? '';

        // --- Explicit periode (opsi lanjutan): skip detection, keep old flow ---------
        if ($explicitPeriodeId && $explicitPeriodeId > 0) {
            $existing = PeriodePenilaian::findById((int) $explicitPeriodeId);
            if ($existing === null) {
                setFlash("Periode penilaian ID {$explicitPeriodeId} tidak ditemukan.", 'error');
                redirect('/admin/import');
            }
            if (($existing['status'] ?? '') === 'selesai' || Penilaian::isPeriodConfirmed((int) $explicitPeriodeId)) {
                setFlash(
                    'Periode ' . $existing['kode_periode'] . ' sudah berstatus SELESAI. '
                    . 'Import ulang tidak diizinkan untuk menjaga integritas hasil yang final.',
                    'error'
                );
                redirect('/admin/import');
            }
            // Explicit periode path: surface the chosen periode in the same panel
            // so the admin sees exactly which periode received the data.
            $_SESSION['detected_periode'] = [
                'quarter' => 0,
                'kode_periode' => (string) ($existing['kode_periode'] ?? ''),
                'nama_periode' => (string) ($existing['nama_periode'] ?? ''),
                'tanggal_mulai' => (string) ($existing['tanggal_mulai'] ?? ''),
                'tanggal_selesai' => (string) ($existing['tanggal_selesai'] ?? ''),
                'created' => false,
                'id_periode' => (int) $explicitPeriodeId,
                'status' => (string) ($existing['status'] ?? 'draft'),
                'nama_file_asli' => $originalName,
            ];

            $this->importAndRedirect($tmpPath, (int) $explicitPeriodeId, $userId, $originalName, $allowPartial);
        }

        // --- Default: auto-detect periode dari data Excel ---------------------------
        $detected = PeriodeDetector::detect($tmpPath);
        if (!$detected['ok']) {
            setFlash('Gagal mendeteksi periode dari file Excel: ' . $detected['error'], 'error');
            redirect('/admin/import');
        }

        $resolved = PeriodeDetector::resolveOrCreate($detected, $userId);
        if (!$resolved['ok'] || $resolved['id_periode'] === null) {
            setFlash('Gagal menyiapkan periode: ' . $resolved['error'], 'error');
            redirect('/admin/import');
        }

        $periodeId = (int) $resolved['id_periode'];
        $label = $detected['kode_periode'] . ' — ' . $detected['nama_periode'];

        // Persist the detection result so the UI can show a "Periode Terdeteksi"
        // panel after the redirect (same flash pattern as import_result).
        $_SESSION['detected_periode'] = [
            'quarter' => (int) ($detected['quarter'] ?? 0),
            'kode_periode' => (string) ($detected['kode_periode'] ?? ''),
            'nama_periode' => (string) ($detected['nama_periode'] ?? ''),
            'tanggal_mulai' => (string) ($detected['tanggal_mulai'] ?? ''),
            'tanggal_selesai' => (string) ($detected['tanggal_selesai'] ?? ''),
            'created' => (bool) ($resolved['created'] ?? false),
            'id_periode' => $periodeId,
            'status' => (string) ($resolved['periode']['status'] ?? 'draft'),
            'nama_file_asli' => $originalName,
        ];

        if ($resolved['created']) {
            setFlash("Periode {$label} berhasil dibuat secara otomatis (status Draft). Melanjutkan import.", 'success');
        } else {
            $status = (string) ($resolved['periode']['status'] ?? '');
            if ($status === 'selesai') {
                setFlash(
                    "Periode {$label} sudah berstatus SELESAI. Import ulang tidak diizinkan "
                    . 'untuk menjaga integritas hasil yang final.',
                    'error'
                );
                redirect('/admin/import');
            }
            setFlash("Periode {$label} sudah ada. Melanjutkan import data.", 'info');
        }

        $this->importAndRedirect($tmpPath, $periodeId, $userId, $originalName, $allowPartial);
    }

    /**
     * Jalankan import service dan kembalikan ke halaman import dengan hasilnya.
     */
    private function importAndRedirect(
        string $tmpPath,
        int $periodeId,
        int $userId,
        string $originalName,
        bool $allowPartial
    ): never {
        $result = $this->importService->import($tmpPath, $periodeId, $userId, $originalName, $allowPartial);

        if ($result['success']) {
            setFlash($result['message'], ($result['status'] === 'success') ? 'success' : 'warning');
        } else {
            setFlash($result['message'], 'error');
        }

        $_SESSION['import_result'] = $result;
        if (!empty($result['errors'])) {
            $_SESSION['import_errors'] = $result['errors'];
        }

        redirect('/admin/import');
    }

    /**
     * Buat periode penilaian baru langsung dari halaman import (mini-form, opsi sekunder).
     * Delegasi validasi tetap pada model — controller tidak mengurai Excel.
     */
    public function storePeriode(): void
    {
        requireAdmin();
        csrfCheck();

        $user = currentUser();
        $adminId = (int) ($user['id'] ?? 0);

        $kode = strtoupper(trim((string) ($_POST['kode_periode'] ?? '')));
        $nama = trim((string) ($_POST['nama_periode'] ?? ''));
        $mulai = trim((string) ($_POST['tanggal_mulai'] ?? ''));
        $selesai = trim((string) ($_POST['tanggal_selesai'] ?? ''));

        $errors = [];
        if ($kode === '') {
            $errors['kode_periode'] = 'Kode periode wajib diisi (misal: 2026-Q4).';
        } elseif (PeriodePenilaian::exists($kode)) {
            $errors['kode_periode'] = "Kode periode '{$kode}' sudah digunakan.";
        }

        if ($nama === '') {
            $errors['nama_periode'] = 'Nama periode wajib diisi.';
        }

        if ($mulai === '' || !strtotime($mulai)) {
            $errors['tanggal_mulai'] = 'Tanggal mulai tidak valid.';
        }

        if ($selesai === '' || !strtotime($selesai)) {
            $errors['tanggal_selesai'] = 'Tanggal selesai tidak valid.';
        } elseif ($mulai !== '' && strtotime($mulai) > strtotime($selesai)) {
            $errors['tanggal_selesai'] = 'Tanggal selesai harus sama atau setelah tanggal mulai.';
        }

        if ($mulai !== '' && $selesai !== '' && strtotime($mulai) && strtotime($selesai)) {
            $quarterCheck = PeriodePenilaian::validateQuarterRange($kode, $mulai, $selesai);
            if (!$quarterCheck['valid']) {
                foreach ($quarterCheck['errors'] as $idx => $msg) {
                    $field = ($idx === 0) ? 'tanggal_mulai' : 'tanggal_selesai';
                    if (!isset($errors[$field])) {
                        $errors[$field] = $msg;
                    }
                }
            }
        }

        if (!empty($errors)) {
            $_SESSION['form_errors'] = $errors;
            $_SESSION['form_old'] = $_POST;
            redirect('/admin/import');
        }

        try {
            PeriodePenilaian::create([
                'kode_periode' => $kode,
                'nama_periode' => $nama,
                'tanggal_mulai' => $mulai,
                'tanggal_selesai' => $selesai,
                'status' => 'draft',
                'created_by' => $adminId,
            ]);
            setFlash("Periode {$nama} ({$kode}) berhasil dibuat dengan status Draft.", 'success');
        } catch (Throwable $e) {
            $_SESSION['form_errors'] = ['general' => 'Gagal menyimpan periode: ' . $e->getMessage()];
            $_SESSION['form_old'] = $_POST;
        }

        redirect('/admin/import');
    }
}
