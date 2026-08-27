<?php
/** @var string $title */
/** @var ?array<string, mixed> $penilaian */
/** @var array<int, array<string, mixed>> $teknisi */
/** @var array<int, string> $periods */
/** @var array<string, string> $errors */
/** @var array<string, mixed> $old */

$isEdit = $penilaian !== null;
$id = $penilaian['id'] ?? ($old['id'] ?? '');
$selectedTeknisi = $penilaian['teknisi_id'] ?? ($old['teknisi_id'] ?? '');
$selectedPeriode = $penilaian['periode'] ?? ($old['periode'] ?? (date('Y-m')));
$c1 = $penilaian['c1'] ?? ($old['c1'] ?? '');
$c2 = $penilaian['c2'] ?? ($old['c2'] ?? '');
$c3 = $penilaian['c3'] ?? ($old['c3'] ?? '');

$kriteria = [
    ['key' => 'c1', 'kode' => 'C1', 'nama' => 'Kedisiplinan', 'bobot' => '30%'],
    ['key' => 'c2', 'kode' => 'C2', 'nama' => 'Kualitas Hasil Kerja', 'bobot' => '40%'],
    ['key' => 'c3', 'kode' => 'C3', 'nama' => 'Tanggung Jawab', 'bobot' => '30%'],
];

$ratingOptions = [
    4 => 'Sangat Baik',
    3 => 'Baik',
    2 => 'Cukup',
    1 => 'Kurang',
];
?>
<div class="page-header">
    <h1 class="page-title"><?= e($title) ?></h1>
</div>

<form method="POST" action="<?= route($isEdit ? '/admin/penilaian/update' : '/admin/penilaian/store') ?>">
    <?= csrfField() ?>
    <?php if ($isEdit): ?>
        <input type="hidden" name="id" value="<?= e((string) $id) ?>">
    <?php endif; ?>

    <div class="card" style="margin-bottom: var(--space-5);">
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: var(--space-5);">
            <div class="form-group" style="margin-bottom: 0;">
                <label class="label" for="periode">Periode</label>
                <input type="month" id="periode" name="periode" class="input" value="<?= e($selectedPeriode) ?>" required>
                <?php if (!empty($errors['periode'])): ?>
                    <div class="form-error"><?= e($errors['periode']) ?></div>
                <?php endif; ?>
            </div>
            <div class="form-group" style="margin-bottom: 0;">
                <label class="label" for="teknisi_id">Teknisi</label>
                <select id="teknisi_id" name="teknisi_id" class="select" required>
                    <option value="">Pilih teknisi</option>
                    <?php foreach ($teknisi as $t): ?>
                        <option value="<?= e((string) $t['id']) ?>" <?= (string) $selectedTeknisi === (string) $t['id'] ? 'selected' : '' ?>>
                            <?= e($t['kode_teknisi']) ?> &mdash; <?= e($t['nama']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (!empty($errors['teknisi_id'])): ?>
                    <div class="form-error"><?= e($errors['teknisi_id']) ?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: var(--space-5); margin-bottom: var(--space-5);">
        <?php foreach ($kriteria as $k): ?>
            <?php $key = $k['key']; $val = $$key; ?>
            <div class="card">
                <div style="margin-bottom: var(--space-4);">
                    <div style="display: flex; align-items: center; justify-content: space-between;">
                        <span class="font-bold text-gold"><?= e($k['kode']) ?></span>
                        <span class="badge badge-gold"><?= e($k['bobot']) ?></span>
                    </div>
                    <h3 style="margin: var(--space-2) 0 0; font-size: var(--text-lg);"><?= e($k['nama']) ?></h3>
                    <div class="meta-text">Benefit</div>
                </div>

                <div style="display: flex; flex-direction: column; gap: var(--space-2);">
                    <?php foreach ($ratingOptions as $rating => $label): ?>
                        <label class="rating-option <?= (string) $val === (string) $rating ? 'selected' : '' ?>" style="cursor: pointer;">
                            <input type="radio" name="<?= e($key) ?>" value="<?= e((string) $rating) ?>" <?= (string) $val === (string) $rating ? 'checked' : '' ?> required style="margin-right: var(--space-3);">
                            <span class="font-semibold"><?= e((string) $rating) ?></span>
                            <span class="text-secondary" style="margin-left: var(--space-2);"><?= e($label) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <?php if (!empty($errors[$key])): ?>
                    <div class="form-error" style="margin-top: var(--space-3);"><?= e($errors[$key]) ?></div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <div style="display: flex; gap: var(--space-3); justify-content: flex-end;">
        <a href="<?= route('/admin/penilaian') ?>" class="btn btn-secondary">Batal</a>
        <button type="submit" class="btn btn-primary"><?= $isEdit ? 'Simpan Perubahan' : 'Simpan Penilaian' ?></button>
    </div>
</form>
