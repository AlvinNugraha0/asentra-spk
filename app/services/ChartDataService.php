<?php
// ASENTRA SPK — Chart Data Service (Phase 6D)
// Prepares backend/database-sourced datasets for Owner graphs.
//
// RULES (V2_FEATURE_ROADMAP.md #18, #37):
// - All values come from tb_penilaian / tb_hasil — never hardcoded.
// - No SAW formula recomputation here, no ranking computed in JS or here.
//   Vi after SAW is read from tb_hasil.nilai_preferensi when SAW has run.
// - Before SAW is run (tb_hasil empty), the Vi preview uses the same weighted
//   sum as the Phase 6C tabulation (0.30/0.40/0.30) and is clearly labelled
//   as a pre-SAW preview.

declare(strict_types=1);

namespace App\Services;

use App\Models\Hasil;
use App\Models\Penilaian;

class ChartDataService
{
    /**
     * Dataset 1: C1/C2/C3 comparison across technicians in a period.
     *
     * @return array{labels: array<int, string>, c1: array<int, float>, c2: array<int, float>, c3: array<int, float>}
     */
    public static function indicatorComparison(int $periodeId): array
    {
        $rows = Penilaian::tabulasiByPeriode($periodeId);

        $labels = [];
        $c1 = [];
        $c2 = [];
        $c3 = [];

        foreach ($rows as $r) {
            $labels[] = (string) $r['kode_teknisi'];
            $c1[] = (float) $r['c1'];
            $c2[] = (float) $r['c2'];
            $c3[] = (float) $r['c3'];
        }

        return ['labels' => $labels, 'c1' => $c1, 'c2' => $c2, 'c3' => $c3];
    }

    /**
     * Dataset 2: Preference value (Vi) per technician.
     *
     * Source priority:
     *   1. tb_hasil (SAW has run) — nilai_preferensi is authoritative.
     *   2. tb_penilaian weighted preview (pre-SAW).
     *
     * @return array{labels: array<int, string>, vi: array<int, float>, source: string}
     */
    public static function preferenceValues(int $periodeId): array
    {
        $hasilRows = Hasil::byPeriodeId($periodeId);

        if (!empty($hasilRows)) {
            $labels = [];
            $vi = [];
            foreach ($hasilRows as $h) {
                $labels[] = (string) $h['kode_teknisi'];
                $vi[] = (float) ($h['nilai_preferensi'] ?? 0);
            }
            return ['labels' => $labels, 'vi' => $vi, 'source' => 'saw'];
        }

        // Pre-SAW preview from tb_penilaian (same weighted sum as tabulation).
        $rows = Penilaian::tabulasiByPeriode($periodeId);
        $labels = [];
        $vi = [];
        foreach ($rows as $r) {
            $labels[] = (string) $r['kode_teknisi'];
            $vi[] = (float) $r['vi'];
        }

        return ['labels' => $labels, 'vi' => $vi, 'source' => 'preview'];
    }

    /**
     * Dataset 3: Indicator detail for one technician (radar chart).
     *
     * Combines the three criteria with their quarterly subindicator averages
     * from AssessmentWorkflowService::getTechnicianIndicatorDetail().
     *
     * @return array{labels: array<int, string>, values: array<int, float>}
     */
    public static function technicianIndicatorDetail(array $detail): array
    {
        $c1 = $detail['calculation']['c1'] ?? [];
        $c2 = $detail['calculation']['c2'] ?? [];
        $c3 = $detail['calculation']['c3'] ?? [];

        $labels = [
            'C1 Kedisiplinan',
            'C2 Kualitas Kerja',
            'C3 Tanggung Jawab',
        ];
        $values = [
            (float) ($c1['value'] ?? 0),
            (float) ($c2['value'] ?? 0),
            (float) ($c3['value'] ?? 0),
        ];

        return ['labels' => $labels, 'values' => $values];
    }

    /**
     * Dataset 4: Subindicator ratings for one technician (bar chart).
     * Shows the raw evidence scale (0-4) behind each criterion.
     *
     * @return array{labels: array<int, string>, values: array<int, float>}
     */
    public static function technicianSubindicators(array $detail): array
    {
        $c1s = $detail['calculation']['c1']['subindicators'] ?? [];
        $c2s = $detail['calculation']['c2']['subindicators'] ?? [];
        $c3s = $detail['calculation']['c3']['subindicators'] ?? [];

        $pairs = [
            ['Kehadiran', 'kehadiran', $c1s],
            ['Ketepatan Waktu', 'terlambat', $c1s],
            ['Kepatuhan Jadwal', 'jadwal', $c1s],
            ['Kerapian', 'rapi', $c2s],
            ['Presisi', 'presisi', $c2s],
            ['Sesuai Desain', 'sesuai_desain', $c2s],
            ['Perawatan Alat', 'perawatan_alat', $c3s],
            ['Efisiensi Material', 'efisiensi_material', $c3s],
            ['Inisiatif', 'inisiatif', $c3s],
            ['Kepatuhan Prosedur', 'kepatuhan_prosedur', $c3s],
        ];

        $labels = [];
        $values = [];
        foreach ($pairs as [$label, $key, $src]) {
            $labels[] = $label;
            $values[] = (float) ($src[$key] ?? 0);
        }

        return ['labels' => $labels, 'values' => $values];
    }
}
