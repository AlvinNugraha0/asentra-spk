<?php
// ASENTRA SPK — Admin dashboard controller

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Teknisi;
use App\Models\Penilaian;
use App\Models\Kriteria;

class AdminDashboardController
{
    public function index(): void
    {
        requireAdmin();

        $latestPeriode = Penilaian::latestPeriode();
        $countByLatest = $latestPeriode ? Penilaian::countByPeriode($latestPeriode) : 0;
        $activeTeknisi = Teknisi::countActive();
        $totalPenilaian = Penilaian::count();
        $kriteria = Kriteria::all();
        $recent = Penilaian::recent(5);

        $totalBobot = array_sum(array_column($kriteria, 'bobot'));
        $bobotValid = abs($totalBobot - 1.0) < 0.0001;

        renderWithLayout('admin/dashboard', [
            'title' => 'Dashboard Admin',
            'subtitle' => 'Kelola data teknisi dan penilaian kinerja.',
            'activeTeknisi' => $activeTeknisi,
            'totalPenilaian' => $totalPenilaian,
            'kriteriaCount' => count($kriteria),
            'latestPeriode' => $latestPeriode,
            'countByLatest' => $countByLatest,
            'bobotValid' => $bobotValid,
            'totalBobot' => $totalBobot,
            'kriteria' => $kriteria,
            'recent' => $recent,
        ]);
    }
}
