<?php
// ASENTRA SPK — Penilaian controller (Admin — monitoring only)

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Penilaian;

class PenilaianController
{
    /**
     * Daftar penilaian — monitoring (read-only) untuk Admin.
     */
    public function index(): void
    {
        requireAdmin();

        $periode = trim((string) ($_GET['periode'] ?? ''));
        $search = trim((string) ($_GET['search'] ?? ''));
        $list = Penilaian::all($periode, $search);
        $periods = Penilaian::periods();

        renderWithLayout('admin/penilaian_list', [
            'title' => 'Hasil Penilaian',
            'subtitle' => 'Monitoring penilaian kinerja teknisi.',
            'list' => $list,
            'periods' => $periods,
            'periode' => $periode,
            'search' => $search,
        ]);
    }

    /**
     * Riwayat penilaian — monitoring untuk Admin.
     */
    public function history(): void
    {
        requireAdmin();

        $periode = trim((string) ($_GET['periode'] ?? ''));
        $list = Penilaian::all($periode);
        $periods = Penilaian::periods();

        renderWithLayout('admin/riwayat', [
            'title' => 'Riwayat Penilaian',
            'subtitle' => 'Histori penilaian kinerja per periode.',
            'list' => $list,
            'periods' => $periods,
            'periode' => $periode,
        ]);
    }
}

