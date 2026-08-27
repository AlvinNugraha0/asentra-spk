<?php
// ASENTRA SPK — SAW calculation engine (pure logic, no DB / no UI)

declare(strict_types=1);

namespace App\Services;

use InvalidArgumentException;

class SawEngine
{
    /**
     * Build the complete SAW result set from evaluations and criteria weights.
     *
     * @param array<int, array<string, mixed>> $evaluations
     *   Each item must contain: id, teknisi_id, kode_teknisi, nama, periode, c1, c2, c3.
     * @param array<string, float> $weights Keyed by criterion code, e.g. ['C1' => 0.30, ...]
     *
     * @return array<int, array<string, mixed>> Result rows sorted by preference descending,
     *                                          with deterministic technical tie-break by teknisi_id.
     */
    public static function calculate(array $evaluations, array $weights): array
    {
        if (empty($evaluations)) {
            return [];
        }

        foreach (['C1', 'C2', 'C3'] as $code) {
            if (!isset($weights[$code])) {
                throw new InvalidArgumentException("Bobot untuk kriteria {$code} tidak ditemukan.");
            }
        }

        $criteria = ['c1', 'c2', 'c3'];

        // Decision matrix: collect original values
        $rows = [];
        foreach ($evaluations as $e) {
            $rows[] = [
                'penilaian_id' => (int) $e['id'],
                'teknisi_id'   => (int) $e['teknisi_id'],
                'kode_teknisi' => (string) $e['kode_teknisi'],
                'nama'         => (string) $e['nama'],
                'periode'      => (string) $e['periode'],
                'c1'           => (int) $e['c1'],
                'c2'           => (int) $e['c2'],
                'c3'           => (int) $e['c3'],
            ];
        }

        // Maximum value per criterion
        $max = [];
        foreach ($criteria as $c) {
            $values = array_column($rows, $c);
            $max[$c] = !empty($values) ? (float) max($values) : 0.0;
        }

        // Normalization (benefit only) and weighted contributions
        foreach ($rows as $i => $row) {
            foreach ($criteria as $c) {
                $code = strtoupper($c);
                $x = (float) $row[$c];
                $normal = $max[$c] > 0.0 ? ($x / $max[$c]) : 0.0;
                $contrib = $normal * $weights[$code];

                $rows[$i]['max_' . $c] = $max[$c];
                $rows[$i]['normal_' . $c] = $normal;
                $rows[$i]['kontribusi_' . $c] = $contrib;
            }

            $rows[$i]['nilai_preferensi'] =
                $rows[$i]['kontribusi_c1'] +
                $rows[$i]['kontribusi_c2'] +
                $rows[$i]['kontribusi_c3'];
        }

        // Sort: preference descending, then teknisi_id ascending as neutral tie-break
        usort($rows, function (array $a, array $b): int {
            $delta = $b['nilai_preferensi'] <=> $a['nilai_preferensi'];
            if ($delta !== 0) {
                return $delta;
            }
            return $a['teknisi_id'] <=> $b['teknisi_id'];
        });

        // Assign ranking positions (1-based). Equal preference values keep equal math;
        // rank number follows the sorted position for deterministic presentation.
        foreach ($rows as $i => $row) {
            $rows[$i]['ranking'] = $i + 1;
        }

        return $rows;
    }

    /**
     * Validate that the supplied weights sum to approximately 1.0.
     *
     * @param array<string, float> $weights
     */
    public static function validateWeights(array $weights): void
    {
        $total = array_sum($weights);
        if (abs($total - 1.0) > 0.0001) {
            throw new InvalidArgumentException('Total bobot kriteria harus 100%. Saat ini: ' . ($total * 100) . '%');
        }
    }
}
