<?php
// ASENTRA SPK — Discipline Calculator (C1)

declare(strict_types=1);

namespace App\Services\Calculators;

class DisciplineCalculator
{
    public const WEIGHT = 0.30;

    /**
     * Rating scale for C1.1 Kehadiran:
     *   >= 95%      => 4
     *   85% - <95%  => 3
     *   75% - <85%  => 2
     *   < 75%       => 1
     */
    public static function rateKehadiran(float $percentage): int
    {
        if ($percentage >= 95.0) {
            return 4;
        }
        if ($percentage >= 85.0) {
            return 3;
        }
        if ($percentage >= 75.0) {
            return 2;
        }
        return 1;
    }

    /**
     * Rating scale for C1.2 Ketepatan Waktu:
     *   0 - 2   => 4
     *   3 - 5   => 3
     *   6 - 8   => 2
     *   >= 9    => 1
     */
    public static function rateKetepatanWaktu(int $terlambat): int
    {
        if ($terlambat <= 2) {
            return 4;
        }
        if ($terlambat <= 5) {
            return 3;
        }
        if ($terlambat <= 8) {
            return 2;
        }
        return 1;
    }

    /**
     * Rating scale for C1.3 Kepatuhan terhadap Jadwal:
     *   >= 95%      => 4
     *   80% - <95%  => 3
     *   65% - <80%  => 2
     *   < 65%       => 1
     */
    public static function rateKepatuhanJadwal(float $percentage): int
    {
        if ($percentage >= 95.0) {
            return 4;
        }
        if ($percentage >= 80.0) {
            return 3;
        }
        if ($percentage >= 65.0) {
            return 2;
        }
        return 1;
    }

    /**
     * Calculate monthly C1 score from a single month's raw operational record.
     *
     * @param array<string, mixed> $record Must contain:
     *   - total_hari_kerja (int)
     *   - hadir (int)
     *   - terlambat (int)
     *   - pekerjaan_terjadwal (int)
     *   - sesuai_jadwal (int)
     *   - bulan (int, optional)
     *
     * @return array{
     *   status: string,
     *   value: ?float,
     *   c1_1: ?int,
     *   c1_2: ?int,
     *   c1_3: ?int,
     *   persentase_kehadiran: ?float,
     *   jumlah_terlambat: ?int,
     *   persentase_jadwal: ?float,
     *   error: ?string
     * }
     */
    public static function calculateMonthly(array $record): array
    {
        $totalHariKerja = (int) ($record['total_hari_kerja'] ?? 0);
        $hadir = (int) ($record['hadir'] ?? 0);
        $terlambat = (int) ($record['terlambat'] ?? 0);
        $pekerjaanTerjadwal = (int) ($record['pekerjaan_terjadwal'] ?? 0);
        $sesuaiJadwal = (int) ($record['sesuai_jadwal'] ?? 0);

        // Denominator safety check: total_hari_kerja must be > 0
        if ($totalHariKerja <= 0) {
            return [
                'status' => 'no_data',
                'value' => null,
                'c1_1' => null,
                'c1_2' => null,
                'c1_3' => null,
                'persentase_kehadiran' => null,
                'jumlah_terlambat' => $terlambat,
                'persentase_jadwal' => null,
                'error' => 'Total hari kerja bernilai 0 atau tidak valid.',
            ];
        }

        // Denominator safety check: pekerjaan_terjadwal must be > 0
        if ($pekerjaanTerjadwal <= 0) {
            return [
                'status' => 'no_data',
                'value' => null,
                'c1_1' => null,
                'c1_2' => null,
                'c1_3' => null,
                'persentase_kehadiran' => null,
                'jumlah_terlambat' => $terlambat,
                'persentase_jadwal' => null,
                'error' => 'Pekerjaan terjadwal bernilai 0 atau tidak valid.',
            ];
        }

        // Negative value sanity check
        if ($hadir < 0 || $terlambat < 0 || $sesuaiJadwal < 0) {
            return [
                'status' => 'no_data',
                'value' => null,
                'c1_1' => null,
                'c1_2' => null,
                'c1_3' => null,
                'persentase_kehadiran' => null,
                'jumlah_terlambat' => null,
                'persentase_jadwal' => null,
                'error' => 'Terdapat data kehadiran/pekerjaan bernilai negatif.',
            ];
        }

        // Calculate subindicators
        $persenKehadiran = ($hadir / $totalHariKerja) * 100.0;
        $c1_1 = self::rateKehadiran($persenKehadiran);

        $c1_2 = self::rateKetepatanWaktu($terlambat);

        $persenJadwal = ($sesuaiJadwal / $pekerjaanTerjadwal) * 100.0;
        $c1_3 = self::rateKepatuhanJadwal($persenJadwal);

        // Monthly C1: average of 3 subindicators, retain decimal
        $monthlyValue = ($c1_1 + $c1_2 + $c1_3) / 3.0;

        return [
            'status' => 'valid',
            'value' => $monthlyValue,
            'c1_1' => $c1_1,
            'c1_2' => $c1_2,
            'c1_3' => $c1_3,
            'persentase_kehadiran' => $persenKehadiran,
            'jumlah_terlambat' => $terlambat,
            'persentase_jadwal' => $persenJadwal,
            'error' => null,
        ];
    }

    /**
     * Aggregate monthly records across a 3-month period.
     * Only months with valid data are included in the average denominator.
     *
     * @param array<int, array<string, mixed>> $monthlyRecords Keyed by month number (e.g. 1, 2, 3) or list with 'bulan' key.
     * @param array<int, int> $expectedMonths Specific 3 months of the quarter (e.g. [1, 2, 3] or [7, 8, 9]).
     *
     * @return array{
     *   value: ?float,
     *   monthly: array<int, array<string, mixed>>,
     *   months_available: int,
     *   status: string,
     *   warning: ?string
     * }
     */
    public static function aggregateQuarterly(array $monthlyRecords, array $expectedMonths = [1, 2, 3]): array
    {
        // Re-index by month number for direct lookup
        $byMonth = [];
        foreach ($monthlyRecords as $key => $rec) {
            $m = (int) ($rec['bulan'] ?? $key);
            $byMonth[$m] = $rec;
        }

        $monthlyDetails = [];
        $validValues = [];
        $missingMonths = [];

        foreach ($expectedMonths as $monthNum) {
            if (!isset($byMonth[$monthNum])) {
                $monthlyDetails[$monthNum] = [
                    'status' => 'no_data',
                    'value' => null,
                    'error' => "Data bulan {$monthNum} tidak tersedia.",
                ];
                $missingMonths[] = $monthNum;
                continue;
            }

            $calc = self::calculateMonthly($byMonth[$monthNum]);
            $monthlyDetails[$monthNum] = $calc;

            if ($calc['status'] === 'valid' && $calc['value'] !== null) {
                $validValues[] = $calc['value'];
            } else {
                $missingMonths[] = $monthNum;
            }
        }

        $monthsAvailable = count($validValues);

        if ($monthsAvailable === 0) {
            return [
                'value' => null,
                'monthly' => $monthlyDetails,
                'months_available' => 0,
                'status' => 'no_data',
                'warning' => 'Data C1 Kedisiplinan tidak tersedia untuk seluruh bulan.',
            ];
        }

        // Average of valid months only (do not assume 0 for missing months)
        $quarterlyValue = array_sum($validValues) / $monthsAvailable;

        $status = $monthsAvailable >= count($expectedMonths) ? 'complete' : 'partial';

        $warning = null;
        if ($status === 'partial') {
            $missingStr = implode(', ', $missingMonths);
            $warning = "C1 Kedisiplinan hanya memiliki data {$monthsAvailable} dari " . count($expectedMonths) . " bulan (Bulan {$missingStr} tidak tersedia).";
        }

        return [
            'value' => $quarterlyValue,
            'monthly' => $monthlyDetails,
            'months_available' => $monthsAvailable,
            'status' => $status,
            'warning' => $warning,
        ];
    }
}
