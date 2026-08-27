<?php
// ASENTRA SPK — SAW service: fetch data, run engine, persist atomically

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\Hasil;
use App\Models\Kriteria;
use App\Models\Penilaian;
use InvalidArgumentException;
use PDOException;
use RuntimeException;

class SawService
{
    /**
     * Process SAW for a single period and persist the result atomically.
     *
     * @return array{rows: array<int, array<string, mixed>>, message: string}
     * @throws InvalidArgumentException|RuntimeException
     */
    public static function process(string $periode): array
    {
        if ($periode === '' || !preg_match('/^\d{4}-\d{2}$/', $periode)) {
            throw new InvalidArgumentException('Periode tidak valid.');
        }

        // Load criteria weights
        $kriteria = Kriteria::all();
        $weights = [];
        foreach ($kriteria as $k) {
            $weights[$k['kode']] = (float) $k['bobot'];
        }
        SawEngine::validateWeights($weights);

        // Load evaluations for the selected period
        $rawEvaluations = Penilaian::all($periode);
        if (empty($rawEvaluations)) {
            return [
                'rows' => [],
                'message' => 'Tidak ada data penilaian untuk periode ' . periodLabel($periode) . '.',
            ];
        }

        $evaluations = [];
        foreach ($rawEvaluations as $e) {
            $evaluations[] = [
                'id'           => (int) $e['id'],
                'teknisi_id'   => (int) $e['teknisi_id'],
                'kode_teknisi' => (string) $e['kode_teknisi'],
                'nama'         => (string) ($e['nama_teknisi'] ?? ($e['nama'] ?? '')),
                'periode'      => (string) $e['periode'],
                'c1'           => (int) $e['c1'],
                'c2'           => (int) $e['c2'],
                'c3'           => (int) $e['c3'],
            ];
        }

        // Build result set
        $rows = SawEngine::calculate($evaluations, $weights);

        // Persist atomically: replace old results for this period only
        $db = Database::getConnection();
        try {
            $db->beginTransaction();
            Hasil::deleteByPeriode($periode);
            Hasil::insertBatch($rows);
            $db->commit();
        } catch (PDOException $e) {
            $db->rollBack();
            error_log('SAW persistence failed: ' . $e->getMessage());
            throw new RuntimeException('Gagal menyimpan hasil SAW.');
        }

        return [
            'rows' => $rows,
            'message' => 'Perhitungan SAW untuk periode ' . periodLabel($periode) . ' berhasil disimpan.',
        ];
    }
}
