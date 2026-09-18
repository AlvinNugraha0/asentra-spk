<?php
// ASENTRA SPK — Quality Calculator (C2)

declare(strict_types=1);

namespace App\Services\Calculators;

class QualityCalculator
{
    public const WEIGHT = 0.40;

    /**
     * Rating scale for C2 subindicators:
     *   >= 90%      => 4
     *   75% - <90%  => 3
     *   60% - <75%  => 2
     *   < 60%       => 1
     */
    public static function rateQualityIndicator(float $percentage): int
    {
        if ($percentage >= 90.0) {
            return 4;
        }
        if ($percentage >= 75.0) {
            return 3;
        }
        if ($percentage >= 60.0) {
            return 2;
        }
        return 1;
    }

    /**
     * Calculate monthly C2 score from a list of job inspections in a month.
     *
     * @param array<int, array<string, mixed>> $jobs List of jobs in that month. Each item must contain:
     *   - rapi (int|bool: 1/0 or true/false)
     *   - presisi (int|bool: 1/0 or true/false)
     *   - sesuai_desain (int|bool: 1/0 or true/false)
     *
     * @return array{
     *   status: string,
     *   value: ?float,
     *   total_pekerjaan: int,
     *   c2_1: ?int,
     *   c2_2: ?int,
     *   c2_3: ?int,
     *   rapi_count: int,
     *   presisi_count: int,
     *   desain_count: int,
     *   persentase_rapi: ?float,
     *   persentase_presisi: ?float,
     *   persentase_desain: ?float,
     *   error: ?string
     * }
     */
    public static function calculateMonthly(array $jobs): array
    {
        $totalPekerjaan = count($jobs);

        // Denominator safety check: if no jobs in this month, status is no_data
        if ($totalPekerjaan <= 0) {
            return [
                'status' => 'no_data',
                'value' => null,
                'total_pekerjaan' => 0,
                'c2_1' => null,
                'c2_2' => null,
                'c2_3' => null,
                'rapi_count' => 0,
                'presisi_count' => 0,
                'desain_count' => 0,
                'persentase_rapi' => null,
                'persentase_presisi' => null,
                'persentase_desain' => null,
                'error' => 'Tidak ada pekerjaan tercatat untuk bulan ini.',
            ];
        }

        $rapiCount = 0;
        $presisiCount = 0;
        $desainCount = 0;

        foreach ($jobs as $job) {
            if (!empty($job['rapi'])) {
                $rapiCount++;
            }
            if (!empty($job['presisi'])) {
                $presisiCount++;
            }
            if (!empty($job['sesuai_desain'])) {
                $desainCount++;
            }
        }

        $persenRapi = ($rapiCount / $totalPekerjaan) * 100.0;
        $c2_1 = self::rateQualityIndicator($persenRapi);

        $persenPresisi = ($presisiCount / $totalPekerjaan) * 100.0;
        $c2_2 = self::rateQualityIndicator($persenPresisi);

        $persenDesain = ($desainCount / $totalPekerjaan) * 100.0;
        $c2_3 = self::rateQualityIndicator($persenDesain);

        // Monthly C2: average of 3 subindicators, retain decimal
        $monthlyValue = ($c2_1 + $c2_2 + $c2_3) / 3.0;

        return [
            'status' => 'valid',
            'value' => $monthlyValue,
            'total_pekerjaan' => $totalPekerjaan,
            'c2_1' => $c2_1,
            'c2_2' => $c2_2,
            'c2_3' => $c2_3,
            'rapi_count' => $rapiCount,
            'presisi_count' => $presisiCount,
            'desain_count' => $desainCount,
            'persentase_rapi' => $persenRapi,
            'persentase_presisi' => $persenPresisi,
            'persentase_desain' => $persenDesain,
            'error' => null,
        ];
    }

    /**
     * Aggregate monthly jobs across a 3-month period.
     * Only months with valid work records are included in the average denominator.
     *
     * @param array<int, array<int, array<string, mixed>>> $jobsByMonth Grouped by month number (e.g. 1 => [...], 2 => [...], 3 => [...])
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
    public static function aggregateQuarterly(array $jobsByMonth, array $expectedMonths = [1, 2, 3]): array
    {
        $monthlyDetails = [];
        $validValues = [];
        $missingMonths = [];

        foreach ($expectedMonths as $monthNum) {
            $monthJobs = $jobsByMonth[$monthNum] ?? [];
            $calc = self::calculateMonthly($monthJobs);
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
                'warning' => 'Data C2 Kualitas Hasil Kerja tidak tersedia untuk seluruh bulan.',
            ];
        }

        // Average of valid months only (do not assume 0 for missing months)
        $quarterlyValue = array_sum($validValues) / $monthsAvailable;

        $status = $monthsAvailable >= count($expectedMonths) ? 'complete' : 'partial';

        $warning = null;
        if ($status === 'partial') {
            $missingStr = implode(', ', $missingMonths);
            $warning = "C2 Kualitas Hasil Kerja hanya memiliki data {$monthsAvailable} dari " . count($expectedMonths) . " bulan (Bulan {$missingStr} tidak tersedia).";
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
