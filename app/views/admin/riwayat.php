<?php
/** @var string $title */
/** @var string $subtitle */
/** @var array<int, array<string, mixed>> $list */
/** @var array<int, string> $periods */
/** @var string $periode */
?>
<div class="page-header" data-reveal="up">
    <h1 class="page-title"><?= e($title) ?></h1>
    <p class="page-subtitle"><?= e($subtitle) ?></p>
</div>

<div class="action-bar" data-reveal="up">
    <form method="GET" action="<?= route('/admin/riwayat') ?>" class="filter-group flex-1">
        <select name="periode" class="select filter-select" onchange="this.form.submit()">
            <option value="">Semua Periode</option>
            <?php foreach ($periods as $p): ?>
                <option value="<?= e($p) ?>" <?= $periode === $p ? 'selected' : '' ?>><?= e(periodLabel($p)) ?></option>
            <?php endforeach; ?>
        </select>
        <?php if ($periode !== ''): ?>
            <a href="<?= route('/admin/riwayat') ?>" class="btn btn-ghost btn-sm">Reset</a>
        <?php endif; ?>
    </form>
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
                    <th>TANGGAL INPUT</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($list)): ?>
                    <tr>
                        <td colspan="8">
                            <div class="empty-state">
                                <div class="empty-state-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
                                </div>
                                <div class="empty-state-title">Belum Ada Riwayat Penilaian</div>
                                <p>Belum ada penilaian yang tercatat.</p>
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
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
