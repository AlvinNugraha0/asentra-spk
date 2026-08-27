<?php
/** @var string $title */
/** @var string $subtitle */
/** @var array<int, array<string, mixed>> $kriteria */
/** @var float $totalBobot */
/** @var bool $valid */
/** @var array<string, mixed> $errors */
/** @var array<string, mixed> $old */
?>
<div class="page-header">
    <h1 class="page-title"><?= e($title) ?></h1>
    <p class="page-subtitle"><?= e($subtitle) ?></p>
</div>

<div class="card">
    <form method="POST" action="<?= route('/admin/kriteria/update') ?>">
        <?= csrfField() ?>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>KODE</th>
                        <th>KRITERIA</th>
                        <th>ATRIBUT</th>
                        <th>BOBOT</th>
                        <th>DESKRIPSI</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($kriteria as $k): ?>
                        <tr>
                            <td class="font-semibold"><?= e($k['kode']) ?></td>
                            <td>
                                <input type="text" name="kriteria[<?= e((string) $k['id']) ?>][nama_kriteria]" class="input" value="<?= e($old['kriteria'][$k['id']]['nama_kriteria'] ?? $k['nama_kriteria']) ?>" required>
                                <?php if (!empty($errors[$k['id']]['nama_kriteria'])): ?>
                                    <div class="form-error"><?= e($errors[$k['id']]['nama_kriteria']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge badge-neutral"><?= e(ucfirst($k['atribut'])) ?></span></td>
                            <td>
                                <input type="number" step="0.01" min="0" max="1" name="kriteria[<?= e((string) $k['id']) ?>][bobot]" class="input" value="<?= e((string) ($old['kriteria'][$k['id']]['bobot'] ?? $k['bobot'])) ?>" required style="max-width: 120px;">
                                <?php if (!empty($errors[$k['id']]['bobot'])): ?>
                                    <div class="form-error"><?= e($errors[$k['id']]['bobot']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <input type="text" name="kriteria[<?= e((string) $k['id']) ?>][deskripsi]" class="input" value="<?= e($old['kriteria'][$k['id']]['deskripsi'] ?? ($k['deskripsi'] ?? '')) ?>">
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div style="display: flex; align-items: center; justify-content: space-between; margin-top: var(--space-5);">
            <div>
                <span class="text-secondary" style="font-size: var(--text-sm);">Total Bobot</span>
                <div class="font-bold text-gold" style="font-size: var(--text-xl);"><?= e(weightPercent($totalBobot)) ?></div>
                <?php if (!$valid): ?>
                    <div class="form-error" style="margin-top: var(--space-1);"><?= e($errors['total'] ?? 'Total bobot harus 100%.') ?></div>
                <?php endif; ?>
            </div>
            <div style="display: flex; gap: var(--space-3);">
                <a href="<?= route('/admin/dashboard') ?>" class="btn btn-secondary">Batal</a>
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            </div>
        </div>
    </form>
</div>
