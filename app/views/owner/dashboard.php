<?php
/** @var string $title */
/** @var string $subtitle */
/** @var int $activeTeknisi */
/** @var ?string $latestPeriode */
/** @var ?string $processedPeriode */
/** @var int $evaluatedCount */
/** @var ?array<string, mixed> $topResult */
/** @var array<int, array<string, mixed>> $results */
?>
<div class="page-header" data-reveal="up">
    <h1 class="page-title"><?= e($title) ?></h1>
    <p class="page-subtitle"><?= e($subtitle) ?></p>
</div>

<div class="kpi-grid" data-reveal-group>
    <div class="kpi-card" data-reveal="up">
        <div class="kpi-header">
            <span>Periode Terbaru</span>
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="8" height="4" x="8" y="2" rx="1" ry="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="M12 11v6"/><path d="M9 14h6"/></svg>
        </div>
        <div class="kpi-value tabular kpi-value-sm"><?= $latestPeriode ? e(periodLabel($latestPeriode)) : '-' ?></div>
        <div class="kpi-meta">Data penilaian terakhir</div>
    </div>
    <div class="kpi-card" data-reveal="up">
        <div class="kpi-header">
            <span>Teknisi Aktif</span>
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        </div>
        <div class="kpi-value tabular" data-count="<?= e((string) $activeTeknisi) ?>"><?= e((string) $activeTeknisi) ?></div>
        <div class="kpi-meta">Total teknisi aktif</div>
    </div>
    <div class="kpi-card" data-reveal="up">
        <div class="kpi-header">
            <span>Peringkat #1</span>
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"/><path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"/><path d="M4 22h16"/><path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22"/><path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22"/><path d="M18 2H6v7a6 6 0 0 0 12 0V2Z"/></svg>
        </div>
        <div class="kpi-value tabular kpi-value-gold"><?= $topResult ? e($topResult['nama_teknisi']) : '-' ?></div>
        <div class="kpi-meta">Teknisi terbaik</div>
    </div>
    <div class="kpi-card" data-reveal="up">
        <div class="kpi-header">
            <span>Skor Tertinggi</span>
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 7.5a4.5 4.5 0 1 1 4.5 4.5M12 7.5A4.5 4.5 0 1 0 7.5 12M12 7.5V9m-4.5 3a4.5 4.5 0 1 0 4.5 4.5M7.5 12H9"/><circle cx="12" cy="12" r="10"/></svg>
        </div>
        <div class="kpi-value tabular kpi-value-gold"><?= $topResult ? e(scoreFormat((float) $topResult['nilai_preferensi'], 3)) : '-' ?></div>
        <div class="kpi-meta">Nilai SAW</div>
    </div>
</div>

<?php if ($processedPeriode === null): ?>
    <div class="card empty-state" data-reveal="scale">
        <div class="empty-state-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="m19 9-5 5-4-4-3 3"/></svg>
        </div>
        <div class="empty-state-title">Belum ada hasil SAW</div>
        <p>Pilih periode dan jalankan perhitungan SAW di menu Hasil Ranking.</p>
        <a href="<?= route('/owner/ranking') ?>" class="btn btn-primary mt-4">Hitung SAW</a>
    </div>
<?php else: ?>
    <div class="card mb-6" data-reveal="up">
        <div class="row-between mb-5">
            <div>
                <h2 class="card-title">Ranking Terakhir — <?= e(periodLabel($processedPeriode)) ?></h2>
                <p class="card-subtitle"><?= e((string) $evaluatedCount) ?> teknisi dievaluasi.</p>
            </div>
            <a href="<?= route('/owner/ranking?periode=' . urlencode($processedPeriode)) ?>" class="btn btn-secondary">Lihat Detail</a>
        </div>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>RANK</th>
                        <th>TEKNISI</th>
                        <th>C1</th>
                        <th>C2</th>
                        <th>C3</th>
                        <th>NILAI SAW</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($results, 0, 5) as $r): ?>
                        <tr>
                            <td><?= e(rankBadge((int) $r['ranking'])) ?></td>
                            <td>
                                <strong><?= e($r['kode_teknisi']) ?></strong>
                                <span class="text-muted"><?= e($r['nama_teknisi']) ?></span>
                            </td>
                            <td><span class="badge badge-neutral"><?= e((string) $r['c1']) ?></span></td>
                            <td><span class="badge badge-neutral"><?= e((string) $r['c2']) ?></span></td>
                            <td><span class="badge badge-neutral"><?= e((string) $r['c3']) ?></span></td>
                            <td class="font-bold text-gold tabular"><?= e(scoreFormat((float) $r['nilai_preferensi'], 3)) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<div class="row" data-reveal="up">
    <a href="<?= route('/owner/ranking') ?>" class="btn btn-primary">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"/><path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"/><path d="M4 22h16"/><path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22"/><path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22"/><path d="M18 2H6v7a6 6 0 0 0 12 0V2Z"/></svg>
        Hasil Ranking
    </a>
    <a href="<?= route('/owner/riwayat') ?>" class="btn btn-secondary">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
        Riwayat Ranking
    </a>
</div>
