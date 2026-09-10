<?php
/** @var string $title */
/** @var string $subtitle */
/** @var array<int, array<string, mixed>> $list */
/** @var string $search */
/** @var string $status */
?>
<div class="page-header" data-reveal="up">
    <h1 class="page-title"><?= e($title) ?></h1>
    <p class="page-subtitle"><?= e($subtitle) ?></p>
</div>

<div class="action-bar" data-reveal="up">
    <form method="GET" action="<?= route('/owner/teknisi') ?>" class="filter-group flex-1">
        <select name="status" class="select filter-select" onchange="this.form.submit()">
            <option value="">Semua Status</option>
            <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Aktif</option>
            <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Nonaktif</option>
        </select>
        <div class="search-bar search-bar-flex">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            <input type="text" name="search" placeholder="Cari teknisi..." value="<?= e($search) ?>">
        </div>
        <?php if ($search !== '' || $status !== ''): ?>
            <a href="<?= route('/owner/teknisi') ?>" class="btn btn-ghost btn-sm">Reset</a>
        <?php endif; ?>
    </form>
</div>

<div class="card" data-reveal="up">
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>NO</th>
                    <th>KODE</th>
                    <th>NAMA</th>
                    <th>STATUS</th>
                    <th>KETERANGAN</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($list)): ?>
                    <tr>
                        <td colspan="5">
                            <div class="empty-state">
                                <div class="empty-state-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                                </div>
                                <div class="empty-state-title">Belum Ada Data Teknisi</div>
                                <p>Belum ada data teknisi yang tersedia.</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($list as $i => $t): ?>
                        <tr>
                            <td class="tabular text-muted"><?= e((string) ($i + 1)) ?></td>
                            <td><strong><?= e($t['kode_teknisi']) ?></strong></td>
                            <td>
                                <div class="flex items-center gap-3">
                                    <div class="tech-avatar tech-avatar-blue">
                                        <?= e(strtoupper(mb_substr($t['nama'], 0, 2))) ?></div>
                                    <span><?= e($t['nama']) ?></span>
                                </div>
                            </td>
                            <td>
                                <?php if ($t['status'] === 'active'): ?>
                                    <span class="badge badge-success">Aktif</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Nonaktif</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-muted"><?= e($t['keterangan'] ?? '-') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
