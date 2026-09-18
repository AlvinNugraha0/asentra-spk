<?php
// ASENTRA SPK — Responsibility Calculator (C3)

declare(strict_types=1);

namespace App\Services\Calculators;

class ResponsibilityCalculator
{
    public const WEIGHT = 0.30;

    /**
     * Validate rating scale (1-4).
     */
    public static function isValidRating(int $rating): bool
    {
        return $rating >= 1 && $rating <= 4;
    }

    /**
     * Calculate monthly C3 score from a single month's raw operational record.
     *
     * @param array<string, mixed> $record Must contain:
     *   - perawatan_alat (int 1-4)
     *   - efisiensi_material (int 1-4)
     *   - inisiatif (int 1-4)
     *   - kepatuhan_prosedur (int 1-4)
     *
     * @return array{
     *   status: string,
     *   value: ?float,
     *   c3_1: ?int,
     *   c3_2: ?int,
     *   c3_3: ?int,
     *   c3_4: ?int,
     *   error: ?string
     * }
     */
    public static function calculateMonthly(array $record): array
    {
        if (empty($record)) {
            return [
                'status' => 'no_data',
                'value' => null,
                'c3_1' => null,
                'c3_2' => null,
                'c3_3' => null,
                'c3_4' => null,
                'error' => 'Data tanggung jawab tidak tersedia untuk bulan ini.',
            ];
        }

        $c3_1 = isset($record['perawatan_alat']) ? (int) $record['perawatan_alat'] : 0;
        $c3_2 = isset($record['efisiensi_material']) ? (int) $record['efisiensi_material'] : 0;
        $c3_3 = isset($record['inisiatif']) ? (int) $record['inisiatif'] : 0;
        $c3_4 = isset($record['kepatuhan_prosedur']) ? (int) $record['kepatuhan_prosedur'] : 0;

        if (!self::isValidRating($c3_1) || !self::isValidRating($c3_2) || !self::isValidRating($c3_3) || !self::isValidRating($c3_4)) {
            return [
                'status' => 'no_data',
                'value' => null,
                'c3_1' => $c3_1,
                'c3_2' => $c3_2,
                'c3_3' => $c3_3,
                'c3_4' => $c3_4,
                'error' => 'Nilai indikator tanggung jawab harus berada pada skala 1-4.',
            ];
        }

        // Monthly C3: average of 4 subindicators, retain decimal
        $monthlyValue = ($c3_1 + $c3_2 + $c3_3 + $c3_4) / 4.0;

        return [
            'status' => 'valid',
            'value' => $monthlyValue,
            'c3_1' => $c3_1,
            'c3_2' => $c3_2,
            'c3_3' => $c3_3,
            'c3_4' => $c3_4,
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
                'warning' => 'Data C3 Tanggung Jawab tidak tersedia untuk seluruh bulan.',
            ];
        }

        // Average of valid months only (do not assume 0 for missing months)
        $quarterlyValue = array_sum($validValues) / $monthsAvailable;

        $status = $monthsAvailable >= count($expectedMonths) ? 'complete' : 'partial';

        $warning = null;
        if ($status === 'partial') {
            $missingStr = implode(', ', $missingMonths);
            $warning = "C3 Tanggung Jawab hanya memiliki data {$monthsAvailable} dari " . count($expectedMonths) . " bulan (Bulan {$missingStr} tidak tersedia).";
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
