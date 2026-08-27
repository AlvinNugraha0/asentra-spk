<?php
// ASENTRA SPK — Penilaian controller

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Penilaian;
use App\Models\Teknisi;

class PenilaianController
{
    public function index(): void
    {
        requireAdmin();

        $periode = trim((string) ($_GET['periode'] ?? ''));
        $search = trim((string) ($_GET['search'] ?? ''));
        $list = Penilaian::all($periode, $search);
        $periods = Penilaian::periods();

        renderWithLayout('admin/penilaian_list', [
            'title' => 'Penilaian Kinerja',
            'subtitle' => 'Daftar penilaian teknisi.',
            'list' => $list,
            'periods' => $periods,
            'periode' => $periode,
            'search' => $search,
        ]);
    }

    public function create(): void
    {
        requireAdmin();

        $teknisi = Teknisi::all('', 'active');
        $periods = Penilaian::periods();

        renderWithLayout('admin/penilaian_form', [
            'title' => 'Input Penilaian',
            'penilaian' => null,
            'teknisi' => $teknisi,
            'periods' => $periods,
            'errors' => $_SESSION['form_errors'] ?? [],
            'old' => $_SESSION['form_old'] ?? [],
        ]);
        unset($_SESSION['form_errors'], $_SESSION['form_old']);
    }

    public function store(): void
    {
        csrfCheck();
        requireAdmin();

        $data = $this->validate();
        if ($data === null) {
            $_SESSION['form_old'] = $_POST;
            redirect('/admin/penilaian/create');
        }

        if (Penilaian::exists($data['teknisi_id'], $data['periode'])) {
            setFlash('Penilaian untuk teknisi pada periode tersebut sudah tersedia.', 'error');
            $_SESSION['form_old'] = $_POST;
            redirect('/admin/penilaian/create');
        }

        $data['created_by'] = (int) (currentUser()['id'] ?? 0);
        Penilaian::create($data);
        setFlash('Penilaian berhasil disimpan.', 'success');
        redirect('/admin/penilaian');
    }

    public function edit(string $id): void
    {
        requireAdmin();

        $penilaian = Penilaian::findById((int) $id);
        if ($penilaian === null) {
            setFlash('Data penilaian tidak ditemukan.', 'error');
            redirect('/admin/penilaian');
        }

        $teknisi = Teknisi::all('', 'active');
        $periods = Penilaian::periods();

        renderWithLayout('admin/penilaian_form', [
            'title' => 'Edit Penilaian',
            'penilaian' => $penilaian,
            'teknisi' => $teknisi,
            'periods' => $periods,
            'errors' => $_SESSION['form_errors'] ?? [],
            'old' => $_SESSION['form_old'] ?? [],
        ]);
        unset($_SESSION['form_errors'], $_SESSION['form_old']);
    }

    public function update(): void
    {
        csrfCheck();
        requireAdmin();

        $id = (int) ($_POST['id'] ?? 0);
        $penilaian = Penilaian::findById($id);
        if ($penilaian === null) {
            setFlash('Data penilaian tidak ditemukan.', 'error');
            redirect('/admin/penilaian');
        }

        $data = $this->validate();
        if ($data === null) {
            $_SESSION['form_old'] = $_POST;
            redirect('/admin/penilaian/edit/' . $id);
        }

        if (Penilaian::exists($data['teknisi_id'], $data['periode'], $id)) {
            setFlash('Penilaian untuk teknisi pada periode tersebut sudah tersedia.', 'error');
            $_SESSION['form_old'] = $_POST;
            redirect('/admin/penilaian/edit/' . $id);
        }

        Penilaian::update($id, $data);
        setFlash('Penilaian berhasil diperbarui.', 'success');
        redirect('/admin/penilaian');
    }

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

    /**
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
