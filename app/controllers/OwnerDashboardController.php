<?php
// ASENTRA SPK — Owner dashboard controller

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Hasil;
use App\Models\Penilaian;
use App\Models\Teknisi;

class OwnerDashboardController
{
    public function index(): void
    {
        requireOwner();

        $latestPeriode = Penilaian::latestPeriode();
        $processedPeriode = Hasil::latestPeriode();

        $evaluatedCount = 0;
        $topResult = null;
        $results = [];

        if ($processedPeriode !== null) {
            $results = Hasil::byPeriode($processedPeriode);
            $evaluatedCount = count($results);
            $topResult = $results[0] ?? null;
        }

        $trendSummary = Hasil::getTrendSummary();

        // Penilaian progress for active period
        $activeTeknisi = Teknisi::countActive();
        $activePenilaianPeriode = $latestPeriode ?? date('Y-m');
        $dinilaiCount = Penilaian::countEvaluatedByPeriode($activePenilaianPeriode);
        $belumDinilaiCount = max(0, $activeTeknisi - $dinilaiCount);

        // Average criteria scores
        $criteriaAvg = Penilaian::getCriteriaAverages($activePenilaianPeriode);

        renderWithLayout('owner/dashboard', [
            'title' => 'Dashboard Owner',
            'subtitle' => 'Ringkasan evaluasi kinerja teknisi.',
            'activeTeknisi' => $activeTeknisi,
            'latestPeriode' => $latestPeriode,
            'processedPeriode' => $processedPeriode,
            'evaluatedCount' => $evaluatedCount,
            'topResult' => $topResult,
            'results' => $results,
            'trendSummary' => $trendSummary,
            'activePenilaianPeriode' => $activePenilaianPeriode,
            'dinilaiCount' => $dinilaiCount,
            'belumDinilaiCount' => $belumDinilaiCount,
            'criteriaAvg' => $criteriaAvg,
        ]);
    }
}

