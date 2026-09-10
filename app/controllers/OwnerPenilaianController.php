<?php
// ASENTRA SPK — Owner Penilaian controller

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Penilaian;
use App\Models\Teknisi;
use App\Models\Kriteria;
use App\Services\SawService;

class OwnerPenilaianController
{
    /**
     * Daftar penilaian — menampilkan semua teknisi aktif dengan status sudah/belum dinilai.
     */
    public function index(): void
    {
        requireOwner();

        $periode = trim((string) ($_GET['periode'] ?? ''));
        $search = trim((string) ($_GET['search'] ?? ''));
        $statusFilter = trim((string) ($_GET['status'] ?? ''));

        // Default ke periode terbaru jika belum dipilih
        if ($periode === '') {
            $periode = Penilaian::latestPeriode() ?? date('Y-m');
        }

        $list = Penilaian::allWithStatus($periode, $search, $statusFilter);
        $periods = Penilaian::periods();

        // Jika periode saat ini belum ada di daftar, tambahkan
        if (!in_array($periode, $periods, true)) {
            array_unshift($periods, $periode);
        }

        // Stats
        $totalTeknisi = Teknisi::countActive();
        $sudahDinilai = Penilaian::countEvaluatedByPeriode($periode);
        $belumDinilai = $totalTeknisi - $sudahDinilai;

        renderWithLayout('owner/penilaian_list', [
            'title' => 'Penilaian Kinerja',
            'subtitle' => 'Nilai kinerja teknisi berdasarkan hasil pengamatan lapangan.',
            'list' => $list,
            'periods' => $periods,
            'periode' => $periode,
            'search' => $search,
            'statusFilter' => $statusFilter,
            'totalTeknisi' => $totalTeknisi,
            'sudahDinilai' => $sudahDinilai,
            'belumDinilai' => $belumDinilai,
        ]);
    }

    /**
     * Form input penilaian baru.
     */
    public function create(): void
    {
        requireOwner();

        $teknisi = Teknisi::all('', 'active');
        $periods = Penilaian::periods();
        $kriteria = Kriteria::all();

        renderWithLayout('owner/penilaian_form', [
            'title' => 'Input Penilaian',
            'subtitle' => 'Berikan penilaian kinerja teknisi berdasarkan pengamatan lapangan.',
            'penilaian' => null,
            'teknisi' => $teknisi,
            'periods' => $periods,
            'kriteria' => $kriteria,
            'errors' => $_SESSION['form_errors'] ?? [],
            'old' => $_SESSION['form_old'] ?? [],
        ]);
        unset($_SESSION['form_errors'], $_SESSION['form_old']);
    }

    /**
     * Simpan penilaian baru + auto-trigger SAW calculation.
     */
    public function store(): void
    {
        csrfCheck();
        requireOwner();

        $data = $this->validate();
        if ($data === null) {
            $_SESSION['form_old'] = $_POST;
            redirect('/owner/penilaian/create');
        }

        // Cek duplikasi
        if (Penilaian::exists($data['teknisi_id'], $data['periode'])) {
            setFlash('Teknisi ini sudah dinilai pada periode tersebut.', 'error');
            $_SESSION['form_old'] = $_POST;
            redirect('/owner/penilaian/create');
        }

        // Simpan penilaian dengan created_by = user yang sedang login
        $data['created_by'] = (int) (currentUser()['id'] ?? 0);
        $penilaianId = Penilaian::create($data);

        // Auto-trigger SAW calculation untuk periode ini
        try {
            SawService::process($data['periode']);
        } catch (\Throwable $e) {
            // SAW gagal bukan fatal — penilaian tetap tersimpan
            error_log('Auto SAW calculation failed: ' . $e->getMessage());
        }

        setFlash('Penilaian berhasil disimpan.', 'success');
        redirect('/owner/penilaian/detail/' . $penilaianId);
    }

    /**
     * Form edit penilaian yang sudah ada.
     */
    public function edit(string $id): void
    {
        requireOwner();

        $penilaian = Penilaian::findById((int) $id);
        if ($penilaian === null) {
            setFlash('Data penilaian tidak ditemukan.', 'error');
            redirect('/owner/penilaian');
        }

        $teknisi = Teknisi::all('', 'active');
        $periods = Penilaian::periods();
        $kriteria = Kriteria::all();

        renderWithLayout('owner/penilaian_form', [
            'title' => 'Edit Penilaian',
            'subtitle' => 'Perbarui penilaian kinerja teknisi.',
            'penilaian' => $penilaian,
            'teknisi' => $teknisi,
            'periods' => $periods,
            'kriteria' => $kriteria,
            'errors' => $_SESSION['form_errors'] ?? [],
            'old' => $_SESSION['form_old'] ?? [],
        ]);
        unset($_SESSION['form_errors'], $_SESSION['form_old']);
    }

    /**
     * Update penilaian yang sudah ada + re-trigger SAW.
     */
    public function update(): void
    {
        csrfCheck();
        requireOwner();

        $id = (int) ($_POST['id'] ?? 0);
        $penilaian = Penilaian::findById($id);
        if ($penilaian === null) {
            setFlash('Data penilaian tidak ditemukan.', 'error');
            redirect('/owner/penilaian');
        }

        $data = $this->validate();
        if ($data === null) {
            $_SESSION['form_old'] = $_POST;
            redirect('/owner/penilaian/edit/' . $id);
        }

        // Cek duplikasi (exclude self)
        if (Penilaian::exists($data['teknisi_id'], $data['periode'], $id)) {
            setFlash('Teknisi ini sudah dinilai pada periode tersebut.', 'error');
            $_SESSION['form_old'] = $_POST;
            redirect('/owner/penilaian/edit/' . $id);
        }

        Penilaian::update($id, $data);

        // Re-trigger SAW calculation
        try {
            SawService::process($data['periode']);
        } catch (\Throwable $e) {
            error_log('Auto SAW calculation failed: ' . $e->getMessage());
        }

        setFlash('Penilaian berhasil diperbarui.', 'success');
        redirect('/owner/penilaian/detail/' . $id);
    }

    /**
     * Detail penilaian individual — nilai, normalisasi, kontribusi, ranking.
     */
    public function detail(string $id): void
    {
        requireOwner();

        $detail = Penilaian::findDetailWithSaw((int) $id);
        if ($detail === null) {
            setFlash('Data penilaian tidak ditemukan.', 'error');
            redirect('/owner/penilaian');
        }

        $kriteria = Kriteria::all();
        $weights = [];
        foreach ($kriteria as $k) {
            $weights[$k['kode']] = (float) $k['bobot'];
        }

        renderWithLayout('owner/penilaian_detail', [
            'title' => 'Detail Penilaian',
            'subtitle' => ($detail['kode_teknisi'] ?? '') . ' — ' . ($detail['nama_teknisi'] ?? '') . ' — ' . periodLabel($detail['periode']),
            'detail' => $detail,
            'weights' => $weights,
            'kriteria' => $kriteria,
        ]);
    }

    /**
     * Riwayat penilaian Owner — histori semua penilaian.
     */
    public function history(): void
    {
        requireOwner();

        $periode = trim((string) ($_GET['periode'] ?? ''));
        $list = Penilaian::all($periode);
        $periods = Penilaian::periods();

        renderWithLayout('owner/riwayat_penilaian', [
            'title' => 'Riwayat Penilaian',
            'subtitle' => 'Histori penilaian kinerja per periode.',
            'list' => $list,
            'periods' => $periods,
            'periode' => $periode,
        ]);
    }

    /**
     * Validasi input penilaian.
     *
     * @return array<string, mixed>|null
     */
    private function validate(): ?array
    {
        $errors = [];
        $teknisiId = (int) ($_POST['teknisi_id'] ?? 0);
        $periode = trim((string) ($_POST['periode'] ?? ''));
        $c1 = (int) ($_POST['c1'] ?? 0);
        $c2 = (int) ($_POST['c2'] ?? 0);
        $c3 = (int) ($_POST['c3'] ?? 0);

        if ($teknisiId <= 0) {
            $errors['teknisi_id'] = 'Teknisi wajib dipilih.';
        } else {
            $t = Teknisi::findById($teknisiId);
            if ($t === null || $t['status'] !== 'active') {
                $errors['teknisi_id'] = 'Teknisi tidak aktif atau tidak ditemukan.';
            }
        }

        if ($periode === '' || !preg_match('/^\d{4}-\d{2}$/', $periode)) {
            $errors['periode'] = 'Periode wajib dalam format YYYY-MM.';
        }

        foreach (['c1' => 'C1', 'c2' => 'C2', 'c3' => 'C3'] as $key => $label) {
            $val = (int) ($_POST[$key] ?? 0);
            if ($val < 1 || $val > 4) {
                $errors[$key] = "{$label} wajib antara 1 sampai 4.";
            }
        }

        if (!empty($errors)) {
            $_SESSION['form_errors'] = $errors;
            return null;
        }

        return [
            'teknisi_id' => $teknisiId,
            'periode' => $periode,
            'c1' => $c1,
            'c2' => $c2,
            'c3' => $c3,
        ];
    }
}
