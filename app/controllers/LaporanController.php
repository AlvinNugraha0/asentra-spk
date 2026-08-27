<?php
// ASENTRA SPK — Laporan controller (Owner)

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Hasil;
use App\Models\Kriteria;

class LaporanController
{
    /**
     * Period selector page (inside dark app layout).
     */
    public function index(): void
    {
        requireOwner();

        $periods = Hasil::periods();

        renderWithLayout('owner/laporan_index', [
            'title' => 'Laporan',
            'subtitle' => 'Cetak laporan penilaian kinerja teknisi per periode.',
            'periods' => $periods,
        ]);
    }

    /**
     * Printable report for a single period (standalone white document).
     */
    public function show(string $periode): void
    {
        requireOwner();

        // Validate period format before any DB access.
        if (!preg_match('/^\d{4}-\d{2}$/', $periode)) {
            setFlash('Periode tidak valid.', 'error');
            redirect('/owner/laporan');
        }

        $results = Hasil::byPeriode($periode);

        if (empty($results)) {
            setFlash('Periode ' . periodLabel($periode) . ' belum memiliki hasil ranking. Jalankan proses SAW terlebih dahulu.', 'error');
            redirect('/owner/laporan');
        }

        $kriteria = Kriteria::all();

        render('owner/laporan', [
            'title' => 'Laporan Penilaian Kinerja Teknisi',
            'periode' => $periode,
            'results' => $results,
            'kriteria' => $kriteria,
        ]);
    }
}
