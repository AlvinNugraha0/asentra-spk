<?php
// ASENTRA SPK — Kriteria & Bobot controller

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Kriteria;

class KriteriaController
{
    public function index(): void
    {
        requireAdmin();

        $kriteria = Kriteria::all();
        $totalBobot = Kriteria::totalBobot();
        $valid = abs($totalBobot - 1.0) < 0.001;

        renderWithLayout('admin/kriteria', [
            'title' => 'Kriteria & Bobot',
            'subtitle' => 'Parameter yang digunakan dalam perhitungan SAW.',
            'kriteria' => $kriteria,
            'totalBobot' => $totalBobot,
            'valid' => $valid,
            'errors' => $_SESSION['form_errors'] ?? [],
            'old' => $_SESSION['form_old'] ?? [],
        ]);
        unset($_SESSION['form_errors'], $_SESSION['form_old']);
    }

    public function update(): void
    {
        csrfCheck();
        requireAdmin();

        $rows = $_POST['kriteria'] ?? [];
        if (!is_array($rows) || count($rows) !== 3) {
            setFlash('Data kriteria tidak valid.', 'error');
            redirect('/admin/kriteria');
        }

        $total = 0.0;
        $errors = [];
        $processed = [];

        foreach ($rows as $id => $fields) {
            $id = (int) $id;
            $nama = trim((string) ($fields['nama_kriteria'] ?? ''));
            $bobot = (float) str_replace(',', '.', (string) ($fields['bobot'] ?? '0'));
            $deskripsi = trim((string) ($fields['deskripsi'] ?? ''));

            if ($nama === '') {
                $errors[$id]['nama_kriteria'] = 'Nama kriteria wajib diisi.';
            }

            if ($bobot < 0) {
                $errors[$id]['bobot'] = 'Bobot tidak boleh negatif.';
            }

            $processed[$id] = [
                'nama_kriteria' => $nama,
                'bobot' => $bobot,
                'deskripsi' => $deskripsi === '' ? null : $deskripsi,
            ];

            $total += $bobot;
        }

        if (abs($total - 1.0) > 0.001) {
            $errors['total'] = 'Total bobot harus berjumlah 100% (saat ini ' . weightPercent($total) . ').';
        }

        if (!empty($errors)) {
            $_SESSION['form_errors'] = $errors;
            $_SESSION['form_old'] = $_POST;
            setFlash('Gagal menyimpan: total bobot harus 100%.', 'error');
            redirect('/admin/kriteria');
        }

        foreach ($processed as $id => $fields) {
            $k = Kriteria::findById($id);
            if ($k === null) {
                continue;
            }
            $fields['atribut'] = $k['atribut'];
            Kriteria::update($id, $fields);
        }

        setFlash('Kriteria dan bobot berhasil diperbarui.', 'success');
        redirect('/admin/kriteria');
    }
}
