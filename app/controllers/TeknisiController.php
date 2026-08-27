<?php
// ASENTRA SPK — Teknisi controller

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Teknisi;

class TeknisiController
{
    public function index(): void
    {
        requireAdmin();

        $search = trim((string) ($_GET['search'] ?? ''));
        $status = trim((string) ($_GET['status'] ?? ''));
        $list = Teknisi::all($search, $status);

        renderWithLayout('admin/teknisi_list', [
            'title' => 'Data Teknisi',
            'subtitle' => 'Kelola daftar teknisi lapangan.',
            'list' => $list,
            'search' => $search,
            'status' => $status,
        ]);
    }

    public function create(): void
    {
        requireAdmin();

        renderWithLayout('admin/teknisi_form', [
            'title' => 'Tambah Teknisi',
            'teknisi' => null,
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
            redirect('/admin/teknisi/create');
        }

        if (Teknisi::findByKode($data['kode_teknisi']) !== null) {
            setFlash('Kode teknisi sudah digunakan.', 'error');
            $_SESSION['form_old'] = $_POST;
            redirect('/admin/teknisi/create');
        }

        Teknisi::create($data);
        setFlash('Teknisi berhasil ditambahkan.', 'success');
        redirect('/admin/teknisi');
    }

    public function edit(string $id): void
    {
        requireAdmin();

        $teknisi = Teknisi::findById((int) $id);
        if ($teknisi === null) {
            setFlash('Data teknisi tidak ditemukan.', 'error');
            redirect('/admin/teknisi');
        }

        renderWithLayout('admin/teknisi_form', [
            'title' => 'Edit Teknisi',
            'teknisi' => $teknisi,
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
        $teknisi = Teknisi::findById($id);
        if ($teknisi === null) {
            setFlash('Data teknisi tidak ditemukan.', 'error');
            redirect('/admin/teknisi');
        }

        $data = $this->validate($id);
        if ($data === null) {
            $_SESSION['form_old'] = $_POST;
            redirect('/admin/teknisi/edit/' . $id);
        }

        $existing = Teknisi::findByKode($data['kode_teknisi']);
        if ($existing !== null && (int) $existing['id'] !== $id) {
            setFlash('Kode teknisi sudah digunakan oleh teknisi lain.', 'error');
            $_SESSION['form_old'] = $_POST;
            redirect('/admin/teknisi/edit/' . $id);
        }

        Teknisi::update($id, $data);
        setFlash('Teknisi berhasil diperbarui.', 'success');
        redirect('/admin/teknisi');
    }

    public function delete(): void
    {
        csrfCheck();
        requireAdmin();

        $id = (int) ($_POST['id'] ?? 0);
        $teknisi = Teknisi::findById($id);

        if ($teknisi === null) {
            setFlash('Data teknisi tidak ditemukan.', 'error');
            redirect('/admin/teknisi');
        }

        if (Teknisi::hasRelatedRecords($id)) {
            setFlash('Teknisi tidak dapat dihapus karena memiliki data penilaian. Nonaktifkan jika perlu.', 'error');
            redirect('/admin/teknisi');
        }

        Teknisi::delete($id);
        setFlash('Teknisi berhasil dihapus.', 'success');
        redirect('/admin/teknisi');
    }

    public function toggleStatus(): void
    {
        csrfCheck();
        requireAdmin();

        $id = (int) ($_POST['id'] ?? 0);
        $teknisi = Teknisi::findById($id);
        if ($teknisi === null) {
            setFlash('Data teknisi tidak ditemukan.', 'error');
            redirect('/admin/teknisi');
        }

        Teknisi::toggleStatus($id);
        setFlash('Status teknisi diperbarui.', 'success');
        redirect('/admin/teknisi');
    }

    /**
     * @return array<string, mixed>|null
     */
    private function validate(?int $excludeId = null): ?array
    {
        $errors = [];
        $kode = trim((string) ($_POST['kode_teknisi'] ?? ''));
        $nama = trim((string) ($_POST['nama'] ?? ''));
        $status = $_POST['status'] ?? 'active';
        $keterangan = trim((string) ($_POST['keterangan'] ?? ''));

        if ($kode === '') {
            $errors['kode_teknisi'] = 'Kode teknisi wajib diisi.';
        } elseif (!preg_match('/^[A-Za-z0-9]+$/', $kode)) {
            $errors['kode_teknisi'] = 'Kode teknisi hanya boleh huruf dan angka.';
        }

        if ($nama === '') {
            $errors['nama'] = 'Nama teknisi wajib diisi.';
        }

        if (!in_array($status, ['active', 'inactive'], true)) {
            $status = 'active';
        }

        if (!empty($errors)) {
            $_SESSION['form_errors'] = $errors;
            return null;
        }

        return [
            'kode_teknisi' => strtoupper($kode),
            'nama' => $nama,
            'status' => $status,
            'keterangan' => $keterangan === '' ? null : $keterangan,
        ];
    }
}
