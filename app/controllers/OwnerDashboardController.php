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

        // Phase 6N: progress stats now follow the V2 assessment workflow.
        // Hasil::latestPeriode() resolves the newest processed quarter code
        // (e.g. 'Q1-2026'); the V1 Penilaian::latestPeriode() returned a
        // 'YYYY-MM' legacy string that carries no V2 data and made the card
        // report the wrong count. $criteriaAvg was dead here (never rendered)
        // and is removed; getCriteriaAverages stays for the admin dashboard.
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

        // Penilaian progress for the active V2 period.
        $activeTeknisi = Teknisi::countActive();
        $activePenilaianPeriode = $processedPeriode;
        $dinilaiCount = $activePenilaianPeriode !== null
            ? Penilaian::countEvaluatedByPeriode($activePenilaianPeriode)
            : 0;
        $belumDinilaiCount = max(0, $activeTeknisi - $dinilaiCount);

        renderWithLayout('owner/dashboard', [
            'title' => 'Dashboard Owner',
            'subtitle' => 'Ringkasan evaluasi kinerja teknisi.',
            'activeTeknisi' => $activeTeknisi,
            'processedPeriode' => $processedPeriode,
            'evaluatedCount' => $evaluatedCount,
            'topResult' => $topResult,
            'results' => $results,
            'trendSummary' => $trendSummary,
            'activePenilaianPeriode' => $activePenilaianPeriode,
            'dinilaiCount' => $dinilaiCount,
            'belumDinilaiCount' => $belumDinilaiCount,
        ]);
    }
}

