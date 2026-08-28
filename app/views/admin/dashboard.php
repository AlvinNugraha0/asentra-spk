<?php
/** @var string $title */
/** @var string $subtitle */
/** @var int $activeTeknisi */
/** @var int $totalPenilaian */
/** @var int $kriteriaCount */
/** @var ?string $latestPeriode */
/** @var int $countByLatest */
/** @var bool $bobotValid */
/** @var float $totalBobot */
/** @var array<int, array<string, mixed>> $kriteria */
/** @var array<int, array<string, mixed>> $recent */
?>
<div class="page-header" data-reveal="up">
    <h1 class="page-title"><?= e($title) ?></h1>
    <p class="page-subtitle"><?= e($subtitle) ?></p>
</div>

<div class="kpi-grid" data-reveal-group>
    <div class="kpi-card" data-reveal="up">
        <div class="kpi-header">
            <span>Teknisi Aktif</span>
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        </div>
        <div class="kpi-value tabular" data-count="<?= e((string) $activeTeknisi) ?>"><?= e((string) $activeTeknisi) ?></div>
        <div class="kpi-meta">Total teknisi lapangan</div>
    </div>
    <div class="kpi-card" data-reveal="up">
        <div class="kpi-header">
            <span>Penilaian Periode Ini</span>
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="8" height="4" x="8" y="2" rx="1" ry="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="m9 14 2 2 4-4"/></svg>
        </div>
        <div class="kpi-value tabular" data-count="<?= e((string) $countByLatest) ?>"><?= e((string) $countByLatest) ?></div>
        <div class="kpi-meta"><?= $latestPeriode ? e(periodLabel($latestPeriode)) : 'Belum ada periode' ?></div>
    </div>
    <div class="kpi-card" data-reveal="up">
        <div class="kpi-header">
            <span>Kriteria</span>
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 4h-7"/><path d="M17 20V4"/><path d="M3 14h7"/><path d="M7 20v-6"/><path d="M14 15h7"/><path d="M17 10V4"/></svg>
        </div>
        <div class="kpi-value tabular" data-count="<?= e((string) $kriteriaCount) ?>"><?= e((string) $kriteriaCount) ?></div>
        <div class="kpi-meta">C1, C2, C3</div>
    </div>
    <div class="kpi-card" data-reveal="up">
        <div class="kpi-header">
            <span>Total Bobot</span>
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 7.5a4.5 4.5 0 1 1 4.5 4.5M12 7.5A4.5 4.5 0 1 0 7.5 12M12 7.5V9m-4.5 3a4.5 4.5 0 1 0 4.5 4.5M7.5 12H9"/><circle cx="12" cy="12" r="10"/></svg>
        </div>
        <div class="kpi-value tabular <?= $bobotValid ? 'text-success' : 'text-danger' ?>"><?= e(weightPercent($totalBobot)) ?></div>
        <div class="kpi-meta"><?= $bobotValid ? 'Bobot valid' : 'Bobot tidak valid' ?></div>
    </div>
</div>

<div class="grid-2-1" data-reveal="up">
    <div class="card">
        <h2 class="card-title">Penilaian Terbaru</h2>
        <p class="card-subtitle">Data input penilaian kinerja teknisi terakhir.</p>
        <?php if (empty($recent)): ?>
            <div class="empty-state" style="padding: var(--space-8) 0;">
                <p>Belum ada data penilaian.</p>
            </div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Teknisi</th>
                            <th>Periode</th>
                            <th>C1</th>
                            <th>C2</th>
                            <th>C3</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent as $r): ?>
                            <tr>
                                <td>
                                    <strong><?= e($r['kode_teknisi']) ?></strong>
                                    <span class="text-muted" style="margin-left: var(--space-2);"><?= e($r['nama_teknisi']) ?></span>
                                </td>
                                <td><?= e(periodLabel($r['periode'])) ?></td>
                                <td><span class="badge badge-neutral"><?= e((string) $r['c1']) ?></span></td>
                                <td><span class="badge badge-neutral"><?= e((string) $r['c2']) ?></span></td>
                                <td><span class="badge badge-neutral"><?= e((string) $r['c3']) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <div class="card">
        <h2 class="card-title">Ringkasan Kriteria</h2>
        <p class="card-subtitle">Bobot dan atribut penilaian SAW.</p>
        <div class="stack" style="margin-top: var(--space-4);">
            <?php foreach ($kriteria as $k): ?>
                <div class="summary-row">
                    <div>
                        <div class="font-semibold"><?= e($k['kode']) ?> &middot; <?= e($k['nama_kriteria']) ?></div>
                        <div class="meta-text"><?= e(ucfirst($k['atribut'])) ?></div>
                    </div>
                    <div class="font-bold text-gold"><?= e(weightPercent((float) $k['bobot'])) ?></div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php if (!$bobotValid): ?>
            <div class="badge badge-danger" style="margin-top: var(--space-4);">
                Total bobot harus 100%
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="row" data-reveal="up">
    <a href="<?= route('/admin/teknisi/create') ?>" class="btn btn-primary">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5v14"/></svg>
        Tambah Teknisi
    </a>
    <a href="<?= route('/admin/penilaian/create') ?>" class="btn btn-secondary">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="8" height="4" x="8" y="2" rx="1" ry="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="m9 14 2 2 4-4"/></svg>
        Input Penilaian
    </a>
</div>
