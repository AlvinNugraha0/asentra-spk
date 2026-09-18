<?php
/** @var string $title */
/** @var string $subtitle */
/** @var array<string, string> $errors */
/** @var array<string, mixed> $old */
?>
<div class="page-header" data-reveal="up">
    <h1 class="page-title"><?= e($title) ?></h1>
    <p class="page-subtitle"><?= e($subtitle) ?></p>
</div>

<div class="card" data-reveal="up" style="max-width: 600px;">
    <?php if (!empty($errors['general'])): ?>
        <div class="p-3 mb-4 rounded bg-danger text-white">
            <?= e($errors['general']) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="<?= route('/admin/periode/store') ?>">
        <?= csrfField() ?>

        <div class="form-group mb-3">
            <label for="kode_periode" class="form-label font-semibold">Kode Periode</label>
            <input type="text" name="kode_periode" id="kode_periode" class="input" placeholder="Misal: 2026-Q4" value="<?= e($old['kode_periode'] ?? '') ?>" required>
            <?php if (!empty($errors['kode_periode'])): ?>
                <div class="form-error mt-1"><?= e($errors['kode_periode']) ?></div>
            <?php endif; ?>
            <small class="text-secondary d-block mt-1">Format rekomendasi: YYYY-QX (contoh: 2026-Q4). Jika kode kuartal digunakan, tanggal harus sesuai kalender kuartal: Q1 = 01 Jan - 31 Mar, Q2 = 01 Apr - 30 Jun, Q3 = 01 Jul - 30 Sep, Q4 = 01 Okt - 31 Des.</small>
        </div>

        <div class="form-group mb-3">
            <label for="nama_periode" class="form-label font-semibold">Nama Periode</label>
            <input type="text" name="nama_periode" id="nama_periode" class="input" placeholder="Misal: Penilaian Triwulan IV 2026" value="<?= e($old['nama_periode'] ?? '') ?>" required>
            <?php if (!empty($errors['nama_periode'])): ?>
                <div class="form-error mt-1"><?= e($errors['nama_periode']) ?></div>
            <?php endif; ?>
        </div>

        <div class="form-group mb-3">
            <label for="tanggal_mulai" class="form-label font-semibold">Tanggal Mulai</label>
            <input type="date" name="tanggal_mulai" id="tanggal_mulai" class="input" value="<?= e($old['tanggal_mulai'] ?? '') ?>" required>
            <?php if (!empty($errors['tanggal_mulai'])): ?>
                <div class="form-error mt-1"><?= e($errors['tanggal_mulai']) ?></div>
            <?php endif; ?>
        </div>

        <div class="form-group mb-4">
            <label for="tanggal_selesai" class="form-label font-semibold">Tanggal Selesai</label>
            <input type="date" name="tanggal_selesai" id="tanggal_selesai" class="input" value="<?= e($old['tanggal_selesai'] ?? '') ?>" required>
            <?php if (!empty($errors['tanggal_selesai'])): ?>
                <div class="form-error mt-1"><?= e($errors['tanggal_selesai']) ?></div>
            <?php endif; ?>
        </div>

        <div class="actions" style="display: flex; gap: 10px;">
            <button type="submit" class="btn btn-primary">Simpan Periode</button>
            <a href="<?= route('/admin/periode') ?>" class="btn btn-secondary">Batal</a>
        </div>
    </form>
</div>
