<?php
/** @var string $title */
/** @var string $subtitle */
/** @var array<int, array<string, mixed>> $list */
/** @var array<int, string> $periods */
/** @var string $periode */
/** @var string $search */
/** @var string $statusFilter */
/** @var int $totalTeknisi */
/** @var int $sudahDinilai */
/** @var int $belumDinilai */
$progressPct = $totalTeknisi > 0 ? round(($sudahDinilai / $totalTeknisi) * 100) : 0;
?>
<div class="page-header" data-reveal="up">
    <h1 class="page-title"><?= e($title) ?></h1>
    <p class="page-subtitle"><?= e($subtitle) ?></p>
</div>

<!-- Progress Card -->
<div class="card mb-5" data-reveal="up">
    <div class="section-header">
        <div class="section-header-left">
            <h3>Progress Penilaian — <?= e(periodLabel($periode)) ?></h3>
            <p><?= e((string) $sudahDinilai) ?> dari <?= e((string) $totalTeknisi) ?> teknisi sudah dinilai.</p>
        </div>
        <div style="text-align: right;">
            <div class="tabular font-bold" style="font-size: 1.5rem; color: var(--brand); line-height: 1;">
                <?= e((string) $progressPct) ?>%</div>
            <div style="font-size: var(--text-sm); margin-top: 4px; color: var(--text-muted);">Selesai</div>
        </div>
    </div>
    <div style="display: flex; gap: var(--space-4); margin-bottom: var(--space-4); flex-wrap: wrap;">
        <div class="stat-mini">
            <i class="ph ph-users text-lg" style="color: var(--brand);"></i>
            <span class="tabular font-bold"><?= e((string) $totalTeknisi) ?></span>
            <span class="text-muted">Total</span>
        </div>
        <div class="stat-mini">
            <i class="ph ph-check-circle text-lg" style="color: var(--success);"></i>
            <span class="tabular font-bold" style="color: var(--success);"><?= e((string) $sudahDinilai) ?></span>
            <span class="text-muted">Sudah Dinilai</span>
        </div>
        <div class="stat-mini">
            <i class="ph ph-clock text-lg" style="color: var(--warning);"></i>
            <span class="tabular font-bold" style="color: var(--warning);"><?= e((string) $belumDinilai) ?></span>
            <span class="text-muted">Belum Dinilai</span>
        </div>
    </div>
    <div class="progress-bar-track">
        <div class="progress-bar-fill" style="width: <?= e((string) $progressPct) ?>%;"></div>
    </div>
</div>

<div class="action-bar" data-reveal="up">
    <form method="GET" action="<?= route('/owner/penilaian') ?>" class="filter-group flex-1">
        <select name="periode" class="select filter-select" onchange="this.form.submit()">
            <?php foreach ($periods as $p): ?>
                <option value="<?= e($p) ?>" <?= $periode === $p ? 'selected' : '' ?>><?= e(periodLabel($p)) ?></option>
            <?php endforeach; ?>
        </select>
        <select name="status" class="select filter-select" onchange="this.form.submit()">
            <option value="">Semua Status</option>
            <option value="sudah_dinilai" <?= $statusFilter === 'sudah_dinilai' ? 'selected' : '' ?>>Sudah Dinilai</option>
            <option value="belum_dinilai" <?= $statusFilter === 'belum_dinilai' ? 'selected' : '' ?>>Belum Dinilai</option>
        </select>
        <div class="search-bar search-bar-flex">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
            <input type="text" name="search" placeholder="Cari teknisi..." value="<?= e($search) ?>">
        </div>
        <?php if ($statusFilter !== '' || $search !== ''): ?>
            <a href="<?= route('/owner/penilaian') ?>?periode=<?= e(urlencode($periode)) ?>" class="btn btn-ghost btn-sm">Reset</a>
        <?php endif; ?>
    </form>
    <a href="<?= route('/owner/penilaian/create') ?>" class="btn btn-primary">
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
                    <th>NILAI AKHIR</th>
                    <th>STATUS</th>
                    <th>TANGGAL</th>
                    <th>AKSI</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($list)): ?>
                    <tr>
                        <td colspan="10">
                            <div class="empty-state">
                                <div class="empty-state-icon">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="8" height="4" x="8" y="2" rx="1" ry="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="m9 14 2 2 4-4"/></svg>
                                </div>
                                <div class="empty-state-title">Belum Ada Data</div>
                                <p>Belum ada teknisi aktif atau periode belum dipilih.</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($list as $i => $row): ?>
                        <?php
                        $isDinilai = $row['status_penilaian'] === 'sudah_dinilai';
                        ?>
                        <tr>
                            <td class="tabular text-muted"><?= e((string) ($i + 1)) ?></td>
                            <td>
                                <div class="flex items-center gap-3">
                                    <div class="tech-avatar tech-avatar-blue">
                                        <?= e(strtoupper(mb_substr($row['nama_teknisi'], 0, 2))) ?></div>
                                    <div>
                                        <strong><?= e($row['nama_teknisi']) ?></strong>
                                        <div class="meta-text"><?= e($row['kode_teknisi']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><?= e(periodLabel($periode)) ?></td>
                            <td>
                                <?php if ($isDinilai): ?>
                                    <span class="badge badge-neutral"><?= e((string) $row['c1']) ?></span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($isDinilai): ?>
                                    <span class="badge badge-neutral"><?= e((string) $row['c2']) ?></span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($isDinilai): ?>
                                    <span class="badge badge-neutral"><?= e((string) $row['c3']) ?></span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($isDinilai && $row['nilai_preferensi'] !== null): ?>
                                    <span class="font-bold tabular" style="color: var(--brand);"><?= e(scoreFormat((float) $row['nilai_preferensi'], 3)) ?></span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($isDinilai): ?>
                                    <span class="badge badge-success">Sudah Dinilai</span>
                                <?php else: ?>
                                    <span class="badge badge-warning">Belum Dinilai</span>
                                <?php endif; ?>
                            </td>
                            <td class="meta-text">
                                <?= $isDinilai ? e(dateFormat($row['created_at'], 'd M Y H:i')) : '-' ?>
                            </td>
                            <td>
                                <?php if ($isDinilai): ?>
                                    <a href="<?= route('/owner/penilaian/detail/' . $row['penilaian_id']) ?>" class="btn btn-ghost btn-sm" title="Lihat Detail">
                                        <i class="ph ph-eye text-base"></i>
                                        <span class="hide-mobile">Detail</span>
                                    </a>
                                <?php else: ?>
                                    <a href="<?= route('/owner/penilaian/create') ?>?teknisi_id=<?= e((string) $row['teknisi_id']) ?>&periode=<?= e(urlencode($periode)) ?>" class="btn btn-primary btn-sm" title="Nilai Teknisi">
                                        <i class="ph ph-pencil-simple text-base"></i>
                                        <span class="hide-mobile">Nilai</span>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
.stat-mini {
    display: flex;
    align-items: center;
    gap: var(--space-2);
    padding: var(--space-2) var(--space-3);
    background: var(--bg-surface);
    border-radius: var(--radius-md);
    border: 1px solid var(--border);
    font-size: var(--text-sm);
}
.progress-bar-track {
    width: 100%;
    height: 8px;
    background: var(--bg-surface);
    border-radius: 999px;
    overflow: hidden;
    border: 1px solid var(--border);
}
.progress-bar-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--brand), var(--brand-hover, var(--brand)));
    border-radius: 999px;
    transition: width 0.6s ease;
    min-width: 0;
}
.hide-mobile { display: inline; }
@media (max-width: 640px) {
    .hide-mobile { display: none; }
}
</style>
