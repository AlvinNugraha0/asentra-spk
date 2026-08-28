<?php
/** @var string $title */
/** @var ?array<string, mixed> $teknisi */
/** @var array<string, string> $errors */
/** @var array<string, mixed> $old */

$isEdit = $teknisi !== null;
$id = $teknisi['id'] ?? ($old['id'] ?? '');
$kode = $teknisi['kode_teknisi'] ?? ($old['kode_teknisi'] ?? '');
$nama = $teknisi['nama'] ?? ($old['nama'] ?? '');
$status = $teknisi['status'] ?? ($old['status'] ?? 'active');
$keterangan = $teknisi['keterangan'] ?? ($old['keterangan'] ?? '');
?>
<div class="page-header" data-reveal="up">
    <h1 class="page-title"><?= e($title) ?></h1>
</div>

<div class="card card-form" data-reveal="up">
    <form method="POST" action="<?= route($isEdit ? '/admin/teknisi/update' : '/admin/teknisi/store') ?>">
        <?= csrfField() ?>
        <?php if ($isEdit): ?>
            <input type="hidden" name="id" value="<?= e((string) $id) ?>">
        <?php endif; ?>

        <div class="form-group">
            <label class="label" for="kode_teknisi">Kode Teknisi</label>
            <input type="text" id="kode_teknisi" name="kode_teknisi" class="input" value="<?= e($kode) ?>" placeholder="Contoh: A11" required>
            <?php if (!empty($errors['kode_teknisi'])): ?>
                <div class="form-error"><?= e($errors['kode_teknisi']) ?></div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label class="label" for="nama">Nama Teknisi</label>
            <input type="text" id="nama" name="nama" class="input" value="<?= e($nama) ?>" placeholder="Nama lengkap" required>
            <?php if (!empty($errors['nama'])): ?>
                <div class="form-error"><?= e($errors['nama']) ?></div>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label class="label" for="status">Status</label>
            <select id="status" name="status" class="select">
                <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Aktif</option>
                <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Tidak Aktif</option>
            </select>
        </div>

        <div class="form-group">
            <label class="label" for="keterangan">Keterangan</label>
            <textarea id="keterangan" name="keterangan" class="input" rows="3" placeholder="Keterangan opsional"><?= e($keterangan) ?></textarea>
        </div>

        <div class="row-end mt-6">
            <a href="<?= route('/admin/teknisi') ?>" class="btn btn-secondary">Batal</a>
            <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Simpan Perubahan' : 'Simpan Teknisi' ?></button>
        </div>
    </form>
</div>
