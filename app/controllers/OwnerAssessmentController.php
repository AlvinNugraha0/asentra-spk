<?php
// ASENTRA SPK — Owner Assessment Controller (V2)
// Handles Owner review of C1, C2, C3, detailed indicators, and official confirmation.
// STRICT: Owner cannot edit raw data, cannot import, and confirmed assessments are locked.

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Penilaian;
use App\Models\PeriodePenilaian;
use App\Services\AssessmentWorkflowService;
use App\Services\ChartDataService;
use Throwable;

class OwnerAssessmentController
{
    private AssessmentWorkflowService $workflowService;

    public function __construct()
    {
        $this->workflowService = new AssessmentWorkflowService();
    }

    /**
     * Preview assessment C1, C2, C3 triwulan untuk Owner.
     */
    public function index(): void
    {
        requireOwner();

        // Get all periods, exclude legacy
        $allPeriods = PeriodePenilaian::all();
        $periods = array_values(array_filter($allPeriods, static function (array $p): bool {
            return ($p['status'] ?? '') !== 'legacy';
        }));

        $selectedPeriodeId = filter_input(INPUT_GET, 'periode_id', FILTER_VALIDATE_INT) ?: 0;

        // Phase 6C: mode tampilan — ringkasan | tabulasi | grafik.
        $mode = (string) filter_input(INPUT_GET, 'mode', FILTER_DEFAULT);
        if (!in_array($mode, ['ringkasan', 'tabulasi', 'grafik'], true)) {
            $mode = 'ringkasan';
        }

        // Default to the most recent period if not selected
        if ($selectedPeriodeId <= 0 && !empty($periods)) {
            $selectedPeriodeId = (int) $periods[0]['id_periode'];
        }

        $summary = null;
        if ($selectedPeriodeId > 0) {
            try {
                $summary = $this->workflowService->getPeriodAssessmentSummary($selectedPeriodeId);

                // Phase 6C: data tabulasi dari database (no hardcoded values).
                $summary['tabulasi'] = Penilaian::tabulasiByPeriode($selectedPeriodeId);

                // Phase 6D: dataset grafik dari database (tb_penilaian / tb_hasil).
                $summary['chart_indicator'] = ChartDataService::indicatorComparison($selectedPeriodeId);
                $summary['chart_vi'] = ChartDataService::preferenceValues($selectedPeriodeId);
            } catch (Throwable $e) {
                setFlash($e->getMessage(), 'error');
            }
        }

        renderWithLayout('owner/assessment_preview', [
            'title' => 'Review Penilaian Kinerja V2',
            'subtitle' => 'Telaah nilai C1, C2, C3 teknisi dan lakukan konfirmasi resmi sebelum SAW.',
            'periods' => $periods,
            'selectedPeriodeId' => $selectedPeriodeId,
            'summary' => $summary,
            'mode' => $mode,
        ]);
    }

    /**
     * Tampilkan detail rincian indikator transparan per teknisi.
     */
    public function detail(string $periodeIdStr, string $teknisiIdStr): void
    {
        requireOwner();

        $periodeId = filter_var($periodeIdStr, FILTER_VALIDATE_INT);
        $teknisiId = filter_var($teknisiIdStr, FILTER_VALIDATE_INT);

        if (!$periodeId || !$teknisiId || $periodeId <= 0 || $teknisiId <= 0) {
            setFlash('Parameter periode atau teknisi tidak valid.', 'error');
            redirect('/owner/assessment');
        }

        try {
            $detail = $this->workflowService->getTechnicianIndicatorDetail($periodeId, $teknisiId);

            renderWithLayout('owner/assessment_detail', [
                'title' => "Detail Indikator: {$detail['teknisi']['nama']} ({$detail['teknisi']['kode_teknisi']})",
                'subtitle' => "Dasar perhitungan operasional C1, C2, C3 untuk periode {$detail['periode']['nama_periode']}.",
                'detail' => $detail,
            ]);
        } catch (Throwable $e) {
            setFlash('Gagal memuat detail indikator: ' . $e->getMessage(), 'error');
            redirect('/owner/assessment');
        }
    }

    /**
     * Phase 6F: Tampilkan halaman tinjauan akhir sebelum konfirmasi resmi.
     * Owner melihat ringkasan periode, jumlah teknisi, data lengkap/partial,
     * jumlah warning, dan konsekuensi penguncian — sebelum menekan tombol.
     */
    public function confirmForm(string $periodeIdStr): void
    {
        requireOwner();

        $periodeId = filter_var($periodeIdStr, FILTER_VALIDATE_INT);
        if (!$periodeId || $periodeId <= 0) {
            setFlash('ID Periode tidak valid.', 'error');
            redirect('/owner/assessment');
        }

        try {
            $summary = $this->workflowService->getPeriodAssessmentSummary($periodeId);
            $summary['tabulasi'] = Penilaian::tabulasiByPeriode($periodeId);

            renderWithLayout('owner/assessment_confirm', [
                'title' => 'Konfirmasi Penilaian',
                'subtitle' => 'Tinjauan akhir sebelum penguncian resmi periode penilaian.',
                'summary' => $summary,
            ]);
        } catch (Throwable $e) {
            setFlash('Gagal memuat halaman konfirmasi: ' . $e->getMessage(), 'error');
            redirect('/owner/assessment');
        }
    }

    /**
     * Konfirmasi resmi penilaian oleh Owner (final executor).
     */
    public function confirm(string $periodeIdStr): void
    {
        requireOwner();
        csrfCheck();

        $periodeId = filter_var($periodeIdStr, FILTER_VALIDATE_INT);
        if (!$periodeId || $periodeId <= 0) {
            setFlash('ID Periode tidak valid.', 'error');
            redirect('/owner/assessment');
        }

        $user = currentUser();
        $ownerId = (int) ($user['id'] ?? 0);

        try {
            $result = $this->workflowService->confirmAssessment($periodeId, $ownerId);
            setFlash($result['message'], 'success');
        } catch (Throwable $e) {
            setFlash('Gagal mengonfirmasi penilaian: ' . $e->getMessage(), 'error');
        }

        redirect("/owner/assessment?periode_id={$periodeId}");
    }
}
