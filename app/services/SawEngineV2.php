<?php
// ASENTRA SPK — Pure SAW V2 calculation engine (logic only, no direct DB / UI)

declare(strict_types=1);

namespace App\Services;

use InvalidArgumentException;
use RuntimeException;

class SawEngineV2
{
    /**
     * Bobot standar kriteria SPK V2:
     * C1 (Kedisiplinan) = 0.30
     * C2 (Kualitas Hasil Kerja) = 0.40
     * C3 (Tanggung Jawab) = 0.30
     */
    public const DEFAULT_WEIGHTS = [
        'C1' => 0.30,
        'C2' => 0.40,
        'C3' => 0.30,
    ];

    /**
     * Hitung hasil SAW V2 lengkap dari himpunan penilaian yang terisolasi per periode.
     *
     * @param array<int, array<string, mixed>> $evaluations
     *   Setiap elemen harus memuat minimal:
     *   - id (atau penilaian_id)
     *   - teknisi_id
     *   - kode_teknisi
     *   - nama (atau nama_teknisi)
     *   - c1 (float/desimal)
     *   - c2 (float/desimal)
     *   - c3 (float/desimal)
     *   - opsional: id_periode, periode
     * @param array<string, float> $weights Keyed by 'C1', 'C2', 'C3'
     * @return array<int, array<string, mixed>> Hasil terurut preferensi DESC, tie-break teknisi_id ASC, ranking 1..N
     * @throws InvalidArgumentException|RuntimeException
     */
    public static function calculate(array $evaluations, array $weights = self::DEFAULT_WEIGHTS): array
    {
        if (empty($evaluations)) {
            return [];
        }

        // 1. Validasi bobot kriteria
        self::validateWeights($weights);

        $criteria = ['c1', 'c2', 'c3'];

        // 2. Susun matriks keputusan awal dengan presisi float penuh
        $rows = [];
        foreach ($evaluations as $e) {
            $penilaianId = (int) ($e['penilaian_id'] ?? ($e['id'] ?? 0));
            $teknisiId   = (int) $e['teknisi_id'];
            $kodeTeknisi = (string) ($e['kode_teknisi'] ?? '');
            $nama        = (string) ($e['nama_teknisi'] ?? ($e['nama'] ?? ''));
            $idPeriode   = isset($e['id_periode']) ? (int) $e['id_periode'] : null;
            $periodeStr  = (string) ($e['periode'] ?? '');

            if (!isset($e['c1']) || !isset($e['c2']) || !isset($e['c3'])) {
                throw new InvalidArgumentException("Data penilaian teknisi {$nama} ({$kodeTeknisi}) tidak memiliki nilai C1/C2/C3 lengkap.");
            }

            if (!is_numeric($e['c1']) || !is_numeric($e['c2']) || !is_numeric($e['c3'])) {
                throw new InvalidArgumentException("Nilai kriteria C1/C2/C3 harus numerik untuk teknisi {$nama}.");
            }

            $c1 = (float) $e['c1'];
            $c2 = (float) $e['c2'];
            $c3 = (float) $e['c3'];

            if ($c1 < 0.0 || $c2 < 0.0 || $c3 < 0.0) {
                throw new InvalidArgumentException("Nilai kriteria C1/C2/C3 tidak boleh negatif untuk teknisi {$nama}.");
            }

            $rows[] = [
                'penilaian_id' => $penilaianId,
                'id_periode'   => $idPeriode,
                'teknisi_id'   => $teknisiId,
                'kode_teknisi' => $kodeTeknisi,
                'nama'         => $nama,
                'nama_teknisi' => $nama,
                'periode'      => $periodeStr,
                'c1'           => $c1,
                'c2'           => $c2,
                'c3'           => $c3,
                'bobot_c1'     => $weights['C1'],
                'bobot_c2'     => $weights['C2'],
                'bobot_c3'     => $weights['C3'],
            ];
        }

        // 3. Tentukan nilai maksimum per kriteria HANYA dari periode yang sedang dihitung (isolasi periode)
        $max = [];
        foreach ($criteria as $c) {
            $values = array_column($rows, $c);
            $maxVal = !empty($values) ? (float) max($values) : 0.0;

            // Zero denominator check: cegah pembagian dengan nol
            if ($maxVal <= 0.0) {
                $code = strtoupper($c);
                throw new RuntimeException("Nilai maksimum kriteria {$code} bernilai 0 (pembagian dengan nol dicegah).");
            }
            $max[$c] = $maxVal;
        }

        // 4. Normalisasi matriks keputusan (benefit) & hitung kontribusi terbobot
        foreach ($rows as $i => $row) {
            foreach ($criteria as $c) {
                $code = strtoupper($c);
                $x = (float) $row[$c];

                // rij = xij / max(xj)
                $norm = $x / $max[$c];

                // kontribusi = normalisasi * bobot
                $contrib = $norm * $weights[$code];

                $rows[$i]['max_' . $c] = $max[$c];
                $rows[$i]['normal_' . $c] = $norm;
                $rows[$i]['nilai_' . $c . '_normalisasi'] = $norm;
                $rows[$i]['kontribusi_' . $c] = $contrib;
            }

            // Vi = 0.30(rC1) + 0.40(rC2) + 0.30(rC3) (tanpa pembulatan prematur)
            $rows[$i]['nilai_preferensi'] =
                $rows[$i]['kontribusi_c1'] +
                $rows[$i]['kontribusi_c2'] +
                $rows[$i]['kontribusi_c3'];
        }

        // 5. Sorting: nilai_preferensi DESC, tie-break teknisi_id ASC
        usort($rows, function (array $a, array $b): int {
            $delta = $b['nilai_preferensi'] <=> $a['nilai_preferensi'];
            if ($delta !== 0) {
                return $delta;
            }
            return $a['teknisi_id'] <=> $b['teknisi_id'];
        });

        // 6. Penomoran ranking sekuensial (1, 2, 3, ...)
        foreach ($rows as $i => $row) {
            $rows[$i]['ranking'] = $i + 1;
        }

        return $rows;
    }

    /**
     * Validasi kelayakan bobot kriteria (harus mencakup C1, C2, C3 dan total 1.0 / 100%).
     *
     * @param array<string, float> $weights
     * @throws InvalidArgumentException
     */
    public static function validateWeights(array $weights): void
    {
        foreach (['C1', 'C2', 'C3'] as $code) {
            if (!isset($weights[$code])) {
                throw new InvalidArgumentException("Bobot untuk kriteria {$code} tidak ditemukan.");
            }
        }

        $total = array_sum($weights);
        if (abs($total - 1.0) > 0.0001) {
            throw new InvalidArgumentException('Total bobot kriteria harus 100% (1.0). Saat ini: ' . ($total * 100) . '%');
        }
    }
}
