<?php
// ASENTRA SPK — Ranking controller (Owner)
//
// Phase 6G: SAW UI Integration.
// - V2 periods are selected by ID and processed by SawServiceV2::process(id_periode),
//   which itself only accepts CONFIRMED evaluations and writes tb_hasil.
// - V1/legacy string periods keep using the legacy SawService path.
// - The controller contains NO SAW formula — it only calls the services.

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Hasil;
use App\Models\Kriteria;
use App\Models\Penilaian;
use App\Models\PeriodePenilaian;
use App\Services\SawService;
use App\Services\SawServiceV2;

class RankingController
{
    public function index(): void
    {
        requireOwner();

        $periode = trim((string) ($_GET['periode'] ?? ''));
        $results = [];
        $periods = Penilaian::periods();
        $periodInfo = null;

        if ($periode !== '') {
            // Phase 6G: prefer id_periode lookup (V2), fall back to string (V1).
            $periodeId = $this->resolvePeriodeId($periode);
            if ($periodeId > 0) {
                $results = Hasil::byPeriodeId($periodeId);
                $periodInfo = PeriodePenilaian::findById($periodeId);
            } else {
                $results = Hasil::byPeriode($periode);
            }
        }

        renderWithLayout('owner/ranking', [
            'title' => 'Hasil Ranking',
            'subtitle' => 'Ranking berdasarkan metode SAW.',
            'periode' => $periode,
            'periods' => $periods,
            'periodOptions' => $this->periodOptions(),
            'periodInfo' => $periodInfo,
            'results' => $results,
        ]);
    }

    public function process(): void
    {
        csrfCheck();
        requireOwner();

        $periode = trim((string) ($_POST['periode'] ?? ''));
        if ($periode === '') {
            setFlash('Periode wajib dipilih.', 'error');
            redirect('/owner/ranking');
        }

        // Phase 6G: numeric value means V2 period id; string means V1/legacy.
        $periodeId = $this->resolvePeriodeId($periode);

        try {
            if ($periodeId > 0) {
                // V2 route — SawServiceV2 enforces CONFIRMED-only and writes tb_hasil.
                $result = SawServiceV2::process($periodeId);
            } else {
                // V1/legacy route (unchanged behaviour).
                $result = SawService::process($periode);
            }
            setFlash($result['message'], empty($result['rows']) ? 'warning' : 'success');
        } catch (\Throwable $e) {
            setFlash($e->getMessage(), 'error');
        }

        redirect('/owner/ranking?periode=' . urlencode($periode));
    }

    public function detail(string $id): void
    {
        requireOwner();

        $hasil = Hasil::findById((int) $id);
        if ($hasil === null) {
            setFlash('Data perhitungan tidak ditemukan.', 'error');
            redirect('/owner/ranking');
        }

        $kriteria = Kriteria::all();
        $weights = [];
        foreach ($kriteria as $k) {
            $weights[$k['kode']] = (float) $k['bobot'];
        }

        // Phase 6H: max per kriteria langsung dari tb_hasil (V2) supaya tidak
        // kehilangan presisi dan tidak membagi dengan normalisasi 0.
        $maxCriteria = null;
        $periodeId = (int) ($hasil['id_periode'] ?? 0);
        if ($periodeId > 0) {
            $maxCriteria = Hasil::maxCriteriaByPeriodeId($periodeId);
        }

        // Phase 6H: label periode V2 memakai nama periode + rentang tanggal.
        $periode = PeriodePenilaian::findById($periodeId);
        $periodeLabel = $periode !== null
            ? $periode['nama_periode'] . ' (' . $periode['tanggal_mulai'] . ' s/d ' . $periode['tanggal_selesai'] . ')'
            : periodLabel((string) ($hasil['periode'] ?? ''));

        renderWithLayout('owner/detail_saw', [
            'title' => 'Detail Perhitungan SAW',
            'subtitle' => $hasil['kode_teknisi'] . ' — ' . $hasil['nama_teknisi'] . ' — ' . $periodeLabel,
            'hasil' => $hasil,
            'weights' => $weights,
            'maxCriteria' => $maxCriteria,
        ]);
    }

    public function history(): void
    {
        requireOwner();

        $periode = trim((string) ($_GET['periode'] ?? ''));
        $results = [];
        $periods = Hasil::periods();
        $periodInfo = null;

        if ($periode !== '') {
            $periodeId = $this->resolvePeriodeId($periode);
            if ($periodeId > 0) {
                $results = Hasil::byPeriodeId($periodeId);
                $periodInfo = PeriodePenilaian::findById($periodeId);
            } else {
                $results = Hasil::byPeriode($periode);
            }
        }

        renderWithLayout('owner/riwayat', [
            'title' => 'Riwayat Ranking',
            'subtitle' => 'Histori hasil perhitungan SAW per periode.',
            'periode' => $periode,
            'periods' => $periods,
            'periodOptions' => $this->periodOptions(),
            'periodInfo' => $periodInfo,
            'results' => $results,
        ]);
    }

    /**
     * Resolve a submitted period selector value to a V2 periode ID.
     *
     * Phase 6G: V2 periods are addressed by id_periode so the quarter code
     * ('2026-Q4' etc.) never needs string parsing in the controller.
     * Returns 0 for V1/legacy string periods (handled by the legacy path).
     */
    private function resolvePeriodeId(string $value): int
    {
        $value = trim($value);

        // Direct numeric id (from the V2 selector).
        if (preg_match('/^\d+$/', $value)) {
            $id = (int) $value;
            if ($id > 0 && PeriodePenilaian::findById($id) !== null) {
                return $id;
            }
            return 0;
        }

        // Otherwise try to match a V2 (non-legacy) quarter code.
        $periode = PeriodePenilaian::findByKode($value);
        if ($periode !== null && ($periode['status'] ?? '') !== 'legacy') {
            return (int) $periode['id_periode'];
        }

        return 0;
    }

    /**
     * Build the period selector options: every period that has evaluations,
     * V2 first (by id) then legacy V1 string codes.
     *
     * @return array<int, array{value: string, label: string}>
     */
    private function periodOptions(): array
    {
        $options = [];

        // V2 periods that actually have evaluations.
        $v2 = PeriodePenilaian::allWithEvaluations();
        foreach ($v2 as $p) {
            if (($p['status'] ?? '') === 'legacy') {
                continue;
            }
            $options[] = [
                'value' => (string) $p['id_periode'],
                'label' => sprintf(
                    '%s — %s%s',
                    (string) $p['kode_periode'],
                    (string) $p['nama_periode'],
                    ($p['status'] ?? '') === 'selesai' ? ' (confirmed)' : ''
                ),
            ];
        }

        // Legacy V1 string codes.
        foreach (Penilaian::periods() as $code) {
            // Skip codes already covered by a V2 period above.
            $dup = false;
            foreach ($options as $o) {
                if (str_contains($o['label'], $code)) {
                    $dup = true;
                    break;
                }
            }
            if (!$dup) {
                $options[] = ['value' => $code, 'label' => periodLabel($code)];
            }
        }

        // Legacy results stored under 'LEGACY-YYYY-MM' codes (V1 seed data).
        // These are never in Penilaian::periods() (which returns 'YYYY-MM'),
        // so expose them by their stored code to keep the selector usable.
        foreach (Hasil::periods() as $code) {
            if (!str_starts_with($code, 'LEGACY-')) {
                continue;
            }
            $options[] = ['value' => $code, 'label' => periodLabel($code)];
        }

        return $options;
    }
}
