<?php
// ASENTRA SPK — SAW Service V2: Workflow & atomicity orchestration for V2 quarterly periods

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\Hasil;
use App\Models\Kriteria;
use App\Models\Penilaian;
use App\Models\PeriodePenilaian;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

class SawServiceV2
{
    /**
     * Jalankan perhitungan SAW V2 untuk periode tertentu dan simpan hasil ke tb_hasil.
     *
     * @param int $periodeId
     * @return array{
     *     periode_id: int,
     *     kode_periode: string,
     *     nama_periode: string,
     *     total_teknisi: int,
     *     rows: array<int, array<string, mixed>>,
     *     message: string
     * }
     * @throws InvalidArgumentException|RuntimeException
     */
    public static function process(int $periodeId): array
    {
        // 1. Validasi periode exists
        $periode = PeriodePenilaian::findById($periodeId);
        if ($periode === null) {
            throw new InvalidArgumentException("Periode penilaian dengan ID {$periodeId} tidak ditemukan.");
        }

        // 2. Validasi bukan legacy
        if (($periode['status'] ?? '') === 'legacy' || str_starts_with((string) $periode['kode_periode'], 'LEGACY-')) {
            throw new RuntimeException("Periode legacy (ID {$periodeId}) tidak dapat diproses oleh SAW V2.");
        }

        // 3. Ambil bobot kriteria (dari tb_kriteria jika valid, fallback ke konstanta default)
        $weights = SawEngineV2::DEFAULT_WEIGHTS;
        try {
            $kriteriaRows = Kriteria::all();
            if (!empty($kriteriaRows)) {
                $dbWeights = [];
                foreach ($kriteriaRows as $k) {
                    $code = strtoupper((string) $k['kode']);
                    $dbWeights[$code] = (float) $k['bobot'];
                }
                if (isset($dbWeights['C1'], $dbWeights['C2'], $dbWeights['C3'])) {
                    SawEngineV2::validateWeights($dbWeights);
                    $weights = $dbWeights;
                }
            }
        } catch (Throwable $e) {
            $weights = SawEngineV2::DEFAULT_WEIGHTS;
        }

        // 4. Ambil data penilaian dari tb_penilaian
        $evaluations = Penilaian::findByPeriodeId($periodeId);
        if (empty($evaluations)) {
            throw new RuntimeException("Tidak ada data penilaian untuk periode '{$periode['nama_periode']}'.");
        }

        // 5. Validasi status penilaian:
        // SAW hanya boleh dijalankan untuk penilaian yang SUDAH CONFIRMED oleh Owner.
        // Status draft, calculated, partial (belum dikonfirmasi), dan legacy secara ketat ditolak.
        foreach ($evaluations as $e) {
            $statusData = (string) ($e['status_data'] ?? '');
            if ($statusData === 'legacy') {
                throw new RuntimeException("Data penilaian legacy tidak boleh diproses oleh SAW V2.");
            }
            if ($statusData !== 'confirmed') {
                throw new RuntimeException(
                    "SAW V2 hanya dapat dijalankan untuk penilaian yang sudah berstatus 'confirmed' oleh Owner. " .
                    "Ditemukan penilaian teknisi '{$e['nama_teknisi']}' berstatus '{$statusData}'."
                );
            }

            // Validasi nilai C1, C2, C3 harus numerik dan > 0
            if (!isset($e['c1']) || !isset($e['c2']) || !isset($e['c3']) ||
                !is_numeric($e['c1']) || !is_numeric($e['c2']) || !is_numeric($e['c3']) ||
                (float) $e['c1'] <= 0.0 || (float) $e['c2'] <= 0.0 || (float) $e['c3'] <= 0.0
            ) {
                throw new RuntimeException("Nilai kriteria C1/C2/C3 tidak valid untuk teknisi '{$e['nama_teknisi']}'. Nilai harus lebih besar dari 0.");
            }
        }

        // 6. Jalankan perhitungan SAW via SawEngineV2
        $rows = SawEngineV2::calculate($evaluations, $weights);

        // Siapkan metadata baris untuk tb_hasil.
        // Phase 6G: gunakan kode_periode APA SAHANYA — jangan potong dengan substr().
        // Kode V1 'YYYY-MM' (7 char) dan V2 'YYYY-QX' (8 char) sama-sama valid;
        // memotong 'YYYY-Q4' menjadi 'YYYY-Q' merusak NOT NULL + UNIQUE lookup.
        $periodeStr = (string) $periode['kode_periode'];
        foreach ($rows as $i => $row) {
            $rows[$i]['id_periode'] = $periodeId;
            $rows[$i]['periode']    = !empty($row['periode']) ? (string) $row['periode'] : $periodeStr;
        }

        // 7. Simpan hasil secara atomik menggunakan Database Transaction
        $db = Database::getConnection();
        try {
            $db->beginTransaction();

            // Re-run safety: hapus hasil lama untuk periode ini saja (isolasi periode)
            Hasil::deleteByPeriodeId($periodeId);

            // Simpan seluruh hasil baru secara batch
            Hasil::insertBatch($rows);

            $db->commit();
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log('SAW V2 persistence failed: ' . $e->getMessage());
            throw new RuntimeException('Gagal menyimpan hasil SAW V2: ' . $e->getMessage(), 0, $e);
        }

        return [
            'periode_id'    => $periodeId,
            'kode_periode'  => (string) $periode['kode_periode'],
            'nama_periode'  => (string) $periode['nama_periode'],
            'total_teknisi' => count($rows),
            'rows'          => $rows,
            'message'       => "Perhitungan SAW V2 untuk periode '{$periode['nama_periode']}' berhasil disimpan.",
        ];
    }
}
