<?php
// ASENTRA SPK — Admin Periode Controller
// Handles period lifecycle, calculation execution, and assessment result monitoring.
// STRICT: Does NOT perform Excel parsing or math formulas in controller!

declare(strict_types=1);

namespace App\Controllers;

use App\Models\PeriodePenilaian;
use App\Services\AssessmentWorkflowService;
use Throwable;

class AdminPeriodeController
{
    private AssessmentWorkflowService $workflowService;

    public function __construct()
    {
        $this->workflowService = new AssessmentWorkflowService();
    }

    /**
     * Tampilkan daftar periode penilaian.
     */
    public function index(): void
    {
        requireAdmin();

        $periodsWithData = PeriodePenilaian::allWithProgress();

        renderWithLayout('admin/periode_list', [
            'title' => 'Kelola Periode Penilaian',
            'subtitle' => 'Daftar periode triwulan, data operasional, dan status kalkulasi V2.',
            'periods' => $periodsWithData,
        ]);
    }

    /**
     * Form tambah periode baru.
     */
    public function create(): void
    {
        requireAdmin();

        renderWithLayout('admin/periode_form', [
            'title' => 'Tambah Periode Penilaian',
            'subtitle' => 'Buat periode penilaian kuartal baru untuk teknisi.',
            'errors' => $_SESSION['form_errors'] ?? [],
            'old' => $_SESSION['form_old'] ?? [],
        ]);

        unset($_SESSION['form_errors'], $_SESSION['form_old']);
    }

    /**
     * Simpan periode penilaian baru.
     */
    public function store(): void
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
        }

        if ($mulai !== '' && $selesai !== '' && strtotime($mulai) > strtotime($selesai)) {
            $errors['tanggal_selesai'] = 'Tanggal selesai harus sama atau setelah tanggal mulai.';
        }

        // Phase 6A: validasi kalender kuartal (V2_FEATURE_ROADMAP.md #10).
        // Mencegah konfigurasi seperti "Q1 = 20 Feb - 20 Feb".
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
            redirect('/admin/periode/create');
        }

        try {
            $newId = PeriodePenilaian::create([
                'kode_periode' => $kode,
                'nama_periode' => $nama,
                'tanggal_mulai' => $mulai,
                'tanggal_selesai' => $selesai,
                'status' => 'draft',
                'created_by' => $adminId,
            ]);

            setFlash("Periode {$nama} ({$kode}) berhasil dibuat dengan status Draft.", 'success');
            redirect('/admin/periode');
        } catch (Throwable $e) {
            $_SESSION['form_errors'] = ['general' => 'Gagal menyimpan periode: ' . $e->getMessage()];
            $_SESSION['form_old'] = $_POST;
            redirect('/admin/periode/create');
        }
    }

    /**
     * Jalankan CalculationEngine untuk periode tertentu.
     */
    public function calculate(string $idStr): void
    {
        requireAdmin();
        csrfCheck();

        $periodeId = filter_var($idStr, FILTER_VALIDATE_INT);
        if (!$periodeId || $periodeId <= 0) {
            setFlash('ID Periode tidak valid.', 'error');
            redirect('/admin/periode');
        }

        $user = currentUser();
        $adminId = (int) ($user['id'] ?? 0);

        try {
            $result = $this->workflowService->calculatePeriod($periodeId, $adminId);
            setFlash($result['message'], 'success');
            redirect("/admin/periode/hasil/{$periodeId}");
        } catch (Throwable $e) {
            setFlash('Gagal menjalankan kalkulasi: ' . $e->getMessage(), 'error');
            redirect('/admin/periode');
        }
    }

    /**
     * Tampilkan hasil kalkulasi operasional untuk Admin.
     */
    public function results(string $idStr): void
    {
        requireAdmin();

        $periodeId = filter_var($idStr, FILTER_VALIDATE_INT);
        if (!$periodeId || $periodeId <= 0) {
            setFlash('ID Periode tidak valid.', 'error');
            redirect('/admin/periode');
        }

        try {
            $summary = $this->workflowService->getPeriodAssessmentSummary($periodeId);

            renderWithLayout('admin/periode_hasil', [
                'title' => 'Hasil Kalkulasi Operasional V2',
                'subtitle' => "Rekapitulasi C1, C2, C3 periode {$summary['periode']['nama_periode']} ({$summary['periode']['kode_periode']}).",
                'summary' => $summary,
            ]);
        } catch (Throwable $e) {
            setFlash($e->getMessage(), 'error');
            redirect('/admin/periode');
        }
    }
}
