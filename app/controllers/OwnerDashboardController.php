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

        renderWithLayout('owner/dashboard', [
            'title' => 'Dashboard Owner',
            'subtitle' => 'Ringkasan evaluasi kinerja teknisi.',
            'activeTeknisi' => Teknisi::countActive(),
            'latestPeriode' => $latestPeriode,
            'processedPeriode' => $processedPeriode,
            'evaluatedCount' => $evaluatedCount,
            'topResult' => $topResult,
            'results' => $results,
        ]);
    }
}
