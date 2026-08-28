<?php
/** @var string $title */
/** @var string $subtitle */
/** @var array<int, array<string, mixed>> $list */
/** @var array<int, string> $periods */
/** @var string $periode */
/** @var string $search */
?>
<div class="page-header" data-reveal="up">
    <h1 class="page-title"><?= e($title) ?></h1>
    <p class="page-subtitle"><?= e($subtitle) ?></p>
</div>

<div class="action-bar" data-reveal="up">
    <form method="GET" action="<?= route('/admin/penilaian') ?>" class="filter-group flex-1">
        <select name="periode" class="select filter-select" onchange="this.form.submit()">
            <option value="">Semua Periode</option>
            <?php foreach ($periods as $p): ?>
                <option value="<?= e($p) ?>" <?= $periode === $p ? 'selected' : '' ?>><?= e(periodLabel($p)) ?></option>
            <?php endforeach; ?>
        </select>
        <div class="search-bar search-bar-flex">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            <input type="text" name="search" placeholder="Cari teknisi..." value="<?= e($search) ?>">
        </div>
        <?php if ($periode !== '' || $search !== ''): ?>
            <a href="<?= route('/admin/penilaian') ?>" class="btn btn-ghost btn-sm">Reset</a>
        <?php endif; ?>
    </form>
    <a href="<?= route('/admin/penilaian/create') ?>" class="btn btn-primary">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
        Input Penilaian
    </a>
</div>

<div class="card" data-reveal="up">
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>NO</th>
                    <th>TEKNISI</th>
                    <th>PERIODE</th>
                    <th>C1</th>
                    <th>C2</th>
                    <th>C3</th>
                    <th>DINILAI OLEH</th>
                    <th>TANGGAL</th>
                    <th>AKSI</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($list)): ?>
                    <tr>
                        <td colspan="9">
                            <div class="empty-state">
                                <div class="empty-state-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="8" height="4" x="8" y="2" rx="1" ry="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="m9 14 2 2 4-4"/></svg>
                                </div>
                                <div class="empty-state-title">Belum Ada Data Penilaian</div>
                                <p>Belum ada penilaian untuk periode ini.</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($list as $i => $p): ?>
                        <tr>
                            <td class="tabular text-muted"><?= e((string) ($i + 1)) ?></td>
                            <td>
                                <strong><?= e($p['kode_teknisi']) ?></strong>
                                <span class="text-muted"><?= e($p['nama_teknisi']) ?></span>
                            </td>
                            <td><?= e(periodLabel($p['periode'])) ?></td>
                            <td><span class="badge badge-neutral"><?= e((string) $p['c1']) ?></span></td>
                            <td><span class="badge badge-neutral"><?= e((string) $p['c2']) ?></span></td>
                            <td><span class="badge badge-neutral"><?= e((string) $p['c3']) ?></span></td>
                            <td class="text-secondary"><?= e($p['nama_user'] ?? '-') ?></td>
                            <td class="meta-text"><?= e(dateFormat($p['created_at'], 'd M Y H:i')) ?></td>
                            <td>
                                <a href="<?= route('/admin/penilaian/edit/' . $p['id']) ?>" class="icon-btn icon-btn-sm" title="Edit" aria-label="Edit penilaian <?= e($p['nama_teknisi']) ?>">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/></svg>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
