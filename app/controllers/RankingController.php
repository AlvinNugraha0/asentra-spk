<?php
// ASENTRA SPK — Ranking controller (Owner)

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Hasil;
use App\Models\Kriteria;
use App\Models\Penilaian;
use App\Services\SawService;

class RankingController
{
    public function index(): void
    {
        requireOwner();

        $periode = trim((string) ($_GET['periode'] ?? ''));
        $periods = Penilaian::periods();
        $results = [];

        if ($periode !== '') {
            $results = Hasil::byPeriode($periode);
        }

        renderWithLayout('owner/ranking', [
            'title' => 'Hasil Ranking',
            'subtitle' => 'Ranking berdasarkan metode SAW.',
            'periode' => $periode,
            'periods' => $periods,
            'results' => $results,
        ]);
    }

    public function process(): void
    {
        csrfCheck();
        requireOwner();

        $periode = trim((string) ($_POST['periode'] ?? ''));
        if ($periode === '') {
            setFlash('Periode wajib dipilih.', 'error');
            redirect('/owner/ranking');
        }

        try {
            $result = SawService::process($periode);
            setFlash($result['message'], empty($result['rows']) ? 'warning' : 'success');
        } catch (\Throwable $e) {
            setFlash($e->getMessage(), 'error');
        }

        redirect('/owner/ranking?periode=' . urlencode($periode));
    }

    public function detail(string $id): void
    {
        requireOwner();

        $hasil = Hasil::findById((int) $id);
        if ($hasil === null) {
            setFlash('Data perhitungan tidak ditemukan.', 'error');
            redirect('/owner/ranking');
        }

        $kriteria = Kriteria::all();
        $weights = [];
        foreach ($kriteria as $k) {
            $weights[$k['kode']] = (float) $k['bobot'];
        }

        renderWithLayout('owner/detail_saw', [
            'title' => 'Detail Perhitungan SAW',
            'subtitle' => $hasil['kode_teknisi'] . ' — ' . $hasil['nama_teknisi'] . ' — ' . periodLabel($hasil['periode']),
            'hasil' => $hasil,
            'weights' => $weights,
        ]);
    }

    public function history(): void
    {
        requireOwner();

        $periode = trim((string) ($_GET['periode'] ?? ''));
        $periods = Hasil::periods();
        $results = [];

        if ($periode !== '') {
            $results = Hasil::byPeriode($periode);
        }

        renderWithLayout('owner/riwayat', [
            'title' => 'Riwayat Ranking',
            'subtitle' => 'Histori hasil perhitungan SAW per periode.',
            'periode' => $periode,
            'periods' => $periods,
            'results' => $results,
        ]);
    }
}
