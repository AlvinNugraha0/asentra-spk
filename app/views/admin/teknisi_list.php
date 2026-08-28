<?php
/** @var string $title */
/** @var string $subtitle */
/** @var array<int, array<string, mixed>> $list */
/** @var string $search */
/** @var string $status */
?>
<div class="page-header">
    <h1 class="page-title"><?= e($title) ?></h1>
    <p class="page-subtitle"><?= e($subtitle) ?></p>
</div>

<div class="action-bar">
    <form method="GET" action="<?= route('/admin/teknisi') ?>" class="filter-group" style="flex: 1;">
        <div class="search-bar" style="min-width: 240px;">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            <input type="text" name="search" placeholder="Cari kode atau nama..." value="<?= e($search) ?>">
        </div>
        <select name="status" class="select" style="min-width: 160px;" onchange="this.form.submit()">
            <option value="" <?= $status === '' ? 'selected' : '' ?>>Semua Status</option>
            <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Aktif</option>
            <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Tidak Aktif</option>
        </select>
        <?php if ($search !== '' || $status !== ''): ?>
            <a href="<?= route('/admin/teknisi') ?>" class="btn btn-ghost btn-sm">Reset</a>
        <?php endif; ?>
    </form>
    <a href="<?= route('/admin/teknisi/create') ?>" class="btn btn-primary">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
        Tambah Teknisi
    </a>
</div>

<div class="card">
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>KODE</th>
                    <th>TEKNISI</th>
                    <th>STATUS</th>
                    <th>KETERANGAN</th>
                    <th>DIPERBARUI</th>
                    <th>AKSI</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($list)): ?>
                    <tr>
                        <td colspan="6">
                            <div class="empty-state" style="padding: var(--space-10) 0;">
                                <p>Belum ada data teknisi.</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($list as $t): ?>
                        <tr>
                            <td class="font-semibold"><?= e($t['kode_teknisi']) ?></td>
                            <td><?= e($t['nama']) ?></td>
                            <td>
                                <?php if ($t['status'] === 'active'): ?>
                                    <span class="badge badge-success"><span class="status-dot success"></span> Aktif</span>
                                <?php else: ?>
                                    <span class="badge badge-neutral"><span class="status-dot neutral"></span> Tidak Aktif</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-secondary"><?= e($t['keterangan'] ?? '-') ?></td>
                            <td class="meta-text"><?= e(dateFormat($t['updated_at'], 'd M Y H:i')) ?></td>
                            <td>
                                <div class="cell-actions">
                                    <a href="<?= route('/admin/teknisi/edit/' . $t['id']) ?>" class="icon-btn icon-btn-sm" title="Edit">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>
                                    </a>
                                    <form method="POST" action="<?= route('/admin/teknisi/toggle-status') ?>" style="margin: 0;">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="id" value="<?= e((string) $t['id']) ?>">
                                        <button type="submit" class="icon-btn icon-btn-sm" title="<?= $t['status'] === 'active' ? 'Nonaktifkan' : 'Aktifkan' ?>">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v4"/><path d="m16.2 7.8 2.9-2.9"/><path d="M18 12h4"/><path d="m16.2 16.2 2.9 2.9"/><path d="M12 18v4"/><path d="m4.9 19.1 2.9-2.9"/><path d="M2 12h4"/><path d="m4.9 4.9 2.9 2.9"/></svg>
                                        </button>
                                    </form>
                                    <form method="POST" action="<?= route('/admin/teknisi/delete') ?>" style="margin: 0;" data-confirm="Teknisi ini akan dihapus permanen. Lanjutkan?">
                                        <?= csrfField() ?>
                                        <input type="hidden" name="id" value="<?= e((string) $t['id']) ?>">
                                        <button type="submit" class="icon-btn icon-btn-sm icon-btn-danger" title="Hapus">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/></svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
