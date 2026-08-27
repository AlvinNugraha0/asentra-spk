<?php
/** @var string $title */
/** @var string $subtitle */
/** @var array<int, string> $periods */
?>
<div class="page-header">
    <h1 class="page-title"><?= e($title) ?></h1>
    <p class="page-subtitle"><?= e($subtitle) ?></p>
</div>

<?php if (empty($periods)): ?>
    <div class="card empty-state">
        <div class="empty-state-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"/><path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"/><path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"/><path d="M10 6h4"/><path d="M10 10h4"/><path d="M10 14h4"/></svg>
        </div>
        <div class="empty-state-title">Belum Ada Data Laporan</div>
        <p>Belum ada hasil ranking SAW yang diproses. Jalankan perhitungan dari menu Hasil Ranking terlebih dahulu.</p>
        <a href="<?= route('/owner/ranking') ?>" class="btn btn-primary" style="margin-top: var(--space-4);">Buka Hasil Ranking</a>
    </div>
<?php else: ?>
    <div class="card">
        <h2 class="card-title">Pilih Periode Laporan</h2>
        <p class="card-subtitle">Laporan berisi hasil SAW untuk satu periode evaluasi. Pilih periode untuk melihat pratinjau cetak.</p>
        <div class="filter-group">
            <?php foreach ($periods as $p): ?>
                <a href="<?= route('/owner/laporan/' . urlencode($p)) ?>" class="btn btn-secondary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"/><path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"/><path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"/></svg>
                    <?= e(periodLabel($p)) ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>
