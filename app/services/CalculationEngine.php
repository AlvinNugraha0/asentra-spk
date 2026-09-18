<?php
// ASENTRA SPK — Calculation Engine (V2 Operational Aggregator)

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Models\Kedisiplinan;
use App\Models\Pekerjaan;
use App\Models\PeriodePenilaian;
use App\Models\TanggungJawab;
use App\Models\Teknisi;
use App\Services\Calculators\DisciplineCalculator;
use App\Services\Calculators\QualityCalculator;
use App\Services\Calculators\ResponsibilityCalculator;
use InvalidArgumentException;
use RuntimeException;

class CalculationEngine
{
    /**
     * Derive expected month numbers (e.g. [1, 2, 3]) from period record dates.
     *
     * @param array<string, mixed> $periode
     * @return array<int, int>
     */
    public static function getMonthsFromPeriod(array $periode): array
    {
        if (empty($periode['tanggal_mulai']) || empty($periode['tanggal_selesai'])) {
            return [1, 2, 3];
        }

        $startMonth = (int) date('n', strtotime((string) $periode['tanggal_mulai']));
        $endMonth = (int) date('n', strtotime((string) $periode['tanggal_selesai']));

        if ($startMonth <= 0 || $endMonth <= 0 || $endMonth < $startMonth) {
            return [1, 2, 3];
        }

        $months = [];
        for ($m = $startMonth; $m <= $endMonth; $m++) {
            $months[] = $m;
        }

        return !empty($months) ? $months : [1, 2, 3];
    }

    /**
     * Pure-data calculation: calculate C1, C2, and C3 for a technician from supplied arrays.
     * Does not perform DB queries — ideal for unit tests and preview calculations.
     *
     * @param array<int, array<string, mixed>> $disiplinRows Monthly discipline records.
     * @param array<int, array<string, mixed>> $pekerjaanRows Work item inspection records.
     * @param array<int, array<string, mixed>> $tanggungJawabRows Monthly responsibility records.
     * @param array<int, int> $expectedMonths Expected months in quarter (default [1, 2, 3]).
     *
     * @return array<string, mixed>
     */
    public static function calculateFromArrays(
        array $disiplinRows,
        array $pekerjaanRows,
        array $tanggungJawabRows,
        array $expectedMonths = [1, 2, 3]
    ): array {
        // Group pekerjaan by month
        $pekerjaanByMonth = [];
        foreach ($pekerjaanRows as $job) {
            $m = (int) ($job['bulan'] ?? (isset($job['tanggal']) ? date('n', strtotime((string) $job['tanggal'])) : 0));
            if ($m > 0) {
                $pekerjaanByMonth[$m][] = $job;
            }
        }

        // 1. Calculate C1
        $c1Result = DisciplineCalculator::aggregateQuarterly($disiplinRows, $expectedMonths);

        // 2. Calculate C2
        $c2Result = QualityCalculator::aggregateQuarterly($pekerjaanByMonth, $expectedMonths);

        // 3. Calculate C3
        $c3Result = ResponsibilityCalculator::aggregateQuarterly($tanggungJawabRows, $expectedMonths);

        // Determine overall status
        $isComplete = ($c1Result['status'] === 'complete') &&
                      ($c2Result['status'] === 'complete') &&
                      ($c3Result['status'] === 'complete');

        $isAllNoData = ($c1Result['status'] === 'no_data') &&
                       ($c2Result['status'] === 'no_data') &&
                       ($c3Result['status'] === 'no_data');

        if ($isComplete) {
            $overallStatus = 'complete';
        } elseif ($isAllNoData) {
            $overallStatus = 'no_data';
        } else {
            $overallStatus = 'partial';
        }

        // Consolidate warning messages
        $warnings = [];
        if (!empty($c1Result['warning'])) {
            $warnings[] = $c1Result['warning'];
        }
        if (!empty($c2Result['warning'])) {
            $warnings[] = $c2Result['warning'];
        }
        if (!empty($c3Result['warning'])) {
            $warnings[] = $c3Result['warning'];
        }
        $warningText = !empty($warnings) ? implode(' | ', $warnings) : null;

        return [
            'status' => $overallStatus,
            'c1' => $c1Result,
            'c2' => $c2Result,
            'c3' => $c3Result,
            'c1_value' => $c1Result['value'],
            'c2_value' => $c2Result['value'],
            'c3_value' => $c3Result['value'],
            'jumlah_bulan_c1' => $c1Result['months_available'],
            'jumlah_bulan_c2' => $c2Result['months_available'],
            'jumlah_bulan_c3' => $c3Result['months_available'],
            'warning' => $warningText,
        ];
    }

    /**
     * Calculate C1, C2, and C3 for a single technician in a period from the database.
     *
     * @param int $periodeId
     * @param int $teknisiId
     * @param array<int, int> $expectedMonths Optional override for expected quarter months.
     * @return array<string, mixed>
     * @throws InvalidArgumentException
     */
    public static function calculateTechnician(int $periodeId, int $teknisiId, array $expectedMonths = []): array
    {
        $periode = PeriodePenilaian::findById($periodeId);
        if ($periode === null) {
            throw new InvalidArgumentException("Periode penilaian dengan ID {$periodeId} tidak ditemukan.");
        }

        $teknisi = Teknisi::findById($teknisiId);
        if ($teknisi === null) {
            throw new InvalidArgumentException("Teknisi dengan ID {$teknisiId} tidak ditemukan.");
        }

        if (empty($expectedMonths)) {
            $expectedMonths = self::getMonthsFromPeriod($periode);
        }

        // Fetch operational data from models
        $disiplinRows = Kedisiplinan::findByPeriodeAndTeknisi($periodeId, $teknisiId);
        $pekerjaanRows = Pekerjaan::findByPeriodeAndTeknisi($periodeId, $teknisiId);
        $tanggungJawabRows = TanggungJawab::findByPeriodeAndTeknisi($periodeId, $teknisiId);

        $result = self::calculateFromArrays($disiplinRows, $pekerjaanRows, $tanggungJawabRows, $expectedMonths);
        $result['id_periode'] = $periodeId;
        $result['id_teknisi'] = $teknisiId;
        $result['kode_teknisi'] = $teknisi['kode_teknisi'];
        $result['nama_teknisi'] = $teknisi['nama'];
        $result['kode_periode'] = $periode['kode_periode'];

        return $result;
    }

    /**
     * Calculate operational scores for all technicians in a period.
     *
     * @param int $periodeId
     * @param array<int, int> $expectedMonths
     * @return array<int, array<string, mixed>> Keyed by teknisi_id
     */
    public static function calculatePeriod(int $periodeId, array $expectedMonths = []): array
    {
        $periode = PeriodePenilaian::findById($periodeId);
        if ($periode === null) {
            throw new InvalidArgumentException("Periode penilaian dengan ID {$periodeId} tidak ditemukan.");
        }

        if (empty($expectedMonths)) {
            $expectedMonths = self::getMonthsFromPeriod($periode);
        }

        // Find all technicians that have any operational records in this period
        $sql = 'SELECT DISTINCT id_teknisi FROM (
                    SELECT id_teknisi FROM tb_kedisiplinan WHERE id_periode = ?
                    UNION
                    SELECT id_teknisi FROM tb_pekerjaan WHERE id_periode = ?
                    UNION
                    SELECT id_teknisi FROM tb_tanggung_jawab WHERE id_periode = ?
                ) AS u';
        $rows = Database::query($sql, [$periodeId, $periodeId, $periodeId])->fetchAll();
        $teknisiIds = array_column($rows, 'id_teknisi');

        // If no records in operational tables, fetch active technicians as fallback
        if (empty($teknisiIds)) {
            $activeTeknisi = Teknisi::all('', 'active');
            $teknisiIds = array_column($activeTeknisi, 'id');
        }

        $results = [];
        foreach ($teknisiIds as $tId) {
            $tId = (int) $tId;
            $results[$tId] = self::calculateTechnician($periodeId, $tId, $expectedMonths);
        }

        return $results;
    }

    /**
     * Persist calculation results to tb_penilaian for a technician in a period.
     *
     * RULES:
     * - Does NOT touch or overwrite legacy data (status_data = 'legacy').
     * - Does NOT create or touch tb_hasil (SAW is separate).
     * - confirmed_by and confirmed_at remain NULL.
     * - status_data is 'calculated' if complete, or 'partial' if partial.
     *
     * @param int $periodeId
     * @param int $teknisiId
     * @param array<string, mixed> $calcResult
     * @param ?int $createdBy
     * @return int Evaluation record ID in tb_penilaian
     * @throws RuntimeException
     */
    public static function saveEvaluation(
        int $periodeId,
        int $teknisiId,
        array $calcResult,
        ?int $createdBy = null
    ): int {
        $periode = PeriodePenilaian::findById($periodeId);
        if ($periode === null) {
            throw new InvalidArgumentException("Periode penilaian ID {$periodeId} tidak ditemukan.");
        }

        // Check if existing evaluation exists
        $stmt = Database::query(
            'SELECT id, status_data FROM tb_penilaian WHERE id_periode = ? AND teknisi_id = ? LIMIT 1',
            [$periodeId, $teknisiId]
        );
        $existing = $stmt->fetch();

        // Safety check: NEVER overwrite legacy data
        if ($existing && ($existing['status_data'] ?? '') === 'legacy') {
            throw new RuntimeException("Data penilaian legacy (ID: {$existing['id']}) tidak boleh diubah atau dihitung ulang.");
        }

        // Determine status_data for V2: calculated or partial
        $statusData = 'calculated';
        if ($calcResult['status'] === 'partial') {
            $statusData = 'partial';
        } elseif ($calcResult['status'] === 'no_data') {
            $statusData = 'draft';
        }

        // Prepare values (default to 0.0 if no_data, otherwise exact decimal)
        $c1 = $calcResult['c1_value'] !== null ? (float) $calcResult['c1_value'] : 0.0;
        $c2 = $calcResult['c2_value'] !== null ? (float) $calcResult['c2_value'] : 0.0;
        $c3 = $calcResult['c3_value'] !== null ? (float) $calcResult['c3_value'] : 0.0;

        $jbC1 = (int) ($calcResult['jumlah_bulan_c1'] ?? 0);
        $jbC2 = (int) ($calcResult['jumlah_bulan_c2'] ?? 0);
        $jbC3 = (int) ($calcResult['jumlah_bulan_c3'] ?? 0);

        $warning = $calcResult['warning'] ?? null;

        // Phase 6G: Compatibility string for the `periode` column.
        // Kode V1 'YYYY-MM' (7) dan V2 'YYYY-QX' (8) keduanya valid apa adanya.
        // JANGAN potong dengan substr() — '2026-Q4' akan rusak menjadi '2026-Q'.
        $periodeString = (string) $periode['kode_periode'];

        if ($existing) {
            // Update existing non-legacy evaluation
            $id = (int) $existing['id'];
            Database::query(
                'UPDATE tb_penilaian
                 SET c1 = ?, c2 = ?, c3 = ?,
                     status_data = ?,
                     jumlah_bulan_c1 = ?, jumlah_bulan_c2 = ?, jumlah_bulan_c3 = ?,
                     warning = ?,
                     confirmed_by = NULL, confirmed_at = NULL,
                     updated_at = CURRENT_TIMESTAMP
                 WHERE id = ?',
                [$c1, $c2, $c3, $statusData, $jbC1, $jbC2, $jbC3, $warning, $id]
            );
            return $id;
        }

        // Insert new evaluation record
        Database::query(
            'INSERT INTO tb_penilaian
                (id_periode, teknisi_id, periode, c1, c2, c3,
                 status_data, jumlah_bulan_c1, jumlah_bulan_c2, jumlah_bulan_c3,
                 warning, confirmed_by, confirmed_at, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NULL, NULL, ?)',
            [
                $periodeId,
                $teknisiId,
                $periodeString,
                $c1,
                $c2,
                $c3,
                $statusData,
                $jbC1,
                $jbC2,
                $jbC3,
                $warning,
                $createdBy,
            ]
        );

        return (int) Database::getConnection()->lastInsertId();
    }
}
