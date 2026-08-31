<?php
/** @var string $title */
/** @var string $subtitle */
/** @var int $activeTeknisi */
/** @var ?string $latestPeriode */
/** @var ?string $processedPeriode */
/** @var int $evaluatedCount */
/** @var ?array<string, mixed> $topResult */
/** @var array<int, array<string, mixed>> $results */
$user = currentUser() ?? [];
$userName = $user['nama'] ?? 'Owner';
?>

<!-- Welcome Banner -->
<div class="welcome-banner" data-reveal="up">
    <div class="welcome-banner-content">
        <div class="welcome-banner-top">
            <div>
                <h1>Welcome back, <?= e($userName) ?></h1>
                <p>Pantau hasil evaluasi dan ranking kinerja teknisi.</p>
            </div>
            <div class="welcome-banner-actions">
                <a href="<?= route('/owner/ranking') ?>" class="btn-banner">
                    <i class="ph ph-trophy text-lg"></i>
                    Hasil Ranking
                </a>
            </div>
        </div>

        <!-- Embedded Stats -->
        <div class="welcome-stats">
            <div class="welcome-stat">
                <div class="welcome-stat-label">
                    <i class="ph ph-calendar-blank text-lg"></i>
                    Periode Terbaru
                </div>
                <div class="welcome-stat-value" style="font-size: var(--text-lg);"><?= $latestPeriode ? e(periodLabel($latestPeriode)) : '-' ?></div>
                <div class="welcome-stat-meta">Data penilaian terakhir</div>
            </div>
            <div class="welcome-stat">
                <div class="welcome-stat-label">
                    <i class="ph ph-users text-lg"></i>
                    Teknisi Aktif
                </div>
                <div class="welcome-stat-value tabular" data-count="<?= e((string) $activeTeknisi) ?>"><?= e((string) $activeTeknisi) ?></div>
                <div class="welcome-stat-meta">Total teknisi aktif</div>
            </div>
            <div class="welcome-stat">
                <div class="welcome-stat-label">
                    <i class="ph ph-trophy text-lg"></i>
                    Peringkat #1
                </div>
                <div class="welcome-stat-value" style="font-size: var(--text-lg); color: var(--gold-light);"><?= $topResult ? e($topResult['nama_teknisi']) : '-' ?></div>
                <div class="welcome-stat-meta">Teknisi terbaik</div>
            </div>
            <div class="welcome-stat">
                <div class="welcome-stat-label">
                    <i class="ph ph-chart-line-up text-lg"></i>
                    Skor Tertinggi
                </div>
                <div class="welcome-stat-value tabular" style="color: var(--gold-light);"><?= $topResult ? e(scoreFormat((float) $topResult['nilai_preferensi'], 3)) : '-' ?></div>
                <div class="welcome-stat-meta">Nilai SAW</div>
            </div>
        </div>
    </div>
</div>

<?php if ($processedPeriode === null): ?>
    <div class="card empty-state" data-reveal="scale">
        <div class="empty-state-icon">
            <i class="ph ph-warning-circle text-3xl"></i>
        </div>
        <div class="empty-state-title">Belum ada hasil SAW</div>
        <p>Pilih periode dan jalankan perhitungan SAW di menu Hasil Ranking.</p>
        <a href="<?= route('/owner/ranking') ?>" class="btn btn-primary mt-4">Hitung SAW</a>
    </div>
<?php else: ?>

    <!-- Main Content Grid: Ranking Table (2/3) + Top Performers (1/3) -->
    <div class="grid-2-1" data-reveal="up">

        <!-- Ranking Table -->
        <div class="card">
            <div class="section-header">
                <div class="section-header-left">
                    <h3>Ranking Terakhir — <?= e(periodLabel($processedPeriode)) ?></h3>
                    <p><?= e((string) $evaluatedCount) ?> teknisi dievaluasi.</p>
                </div>
                <a href="<?= route('/owner/ranking?periode=' . urlencode($processedPeriode)) ?>" class="btn-link">Lihat Detail</a>
            </div>

            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Rank</th>
                            <th>Teknisi</th>
                            <th>C1</th>
                            <th>C2</th>
                            <th>C3</th>
                            <th>Nilai SAW</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($results, 0, 5) as $r): ?>
                            <tr>
                                <td><?= e(rankBadge((int) $r['ranking'])) ?></td>
                                <td>
                                    <div class="flex items-center gap-3">
                                        <div class="tech-avatar tech-avatar-blue"><?= e(strtoupper(mb_substr($r['nama_teknisi'], 0, 2))) ?></div>
                                        <div>
                                            <strong><?= e($r['nama_teknisi']) ?></strong>
                                            <div class="meta-text"><?= e($r['kode_teknisi']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="badge badge-neutral"><?= e((string) $r['c1']) ?></span></td>
                                <td><span class="badge badge-neutral"><?= e((string) $r['c2']) ?></span></td>
                                <td><span class="badge badge-neutral"><?= e((string) $r['c3']) ?></span></td>
                                <td class="font-bold tabular" style="color: var(--brand);"><?= e(scoreFormat((float) $r['nilai_preferensi'], 3)) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Right: Top Performers + Quick Actions -->
        <div class="stack" style="gap: var(--space-6);">

            <!-- Top Performers Card -->
            <?php if (!empty($results)): ?>
            <div class="card">
                <div class="section-header">
                    <div class="section-header-left">
                        <h3>Top Teknisi</h3>
                        <p>Peringkat tertinggi periode ini.</p>
                    </div>
                </div>

                <div class="stack">
                    <?php foreach (array_slice($results, 0, 3) as $idx => $r): ?>
                        <div class="top-performer-item">
                            <div class="performer-avatar <?= $idx === 0 ? 'performer-avatar-gold' : 'performer-avatar-silver' ?>">
                                <?= e(strtoupper(mb_substr($r['nama_teknisi'], 0, 2))) ?>
                                <span class="performer-rank <?= $idx === 0 ? 'performer-rank-gold' : 'performer-rank-default' ?>"><?= e((string) ($idx + 1)) ?></span>
                            </div>
                            <div class="performer-info">
                                <div class="performer-name"><?= e($r['nama_teknisi']) ?></div>
                                <div class="performer-meta"><?= e($r['kode_teknisi']) ?></div>
                            </div>
                            <div class="performer-score">
                                <span class="performer-score-value"><?= e(scoreFormat((float) $r['nilai_preferensi'], 3)) ?></span>
                                <span class="performer-score-label">Skor Akhir</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Quick Actions -->
            <div class="quick-action-grid">
                <a href="<?= route('/owner/ranking') ?>" class="quick-action">
                    <div class="quick-action-icon">
                        <i class="ph ph-trophy text-2xl"></i>
                    </div>
                    <span class="quick-action-label">Hasil Ranking</span>
                </a>
                <a href="<?= route('/owner/riwayat') ?>" class="quick-action">
                    <div class="quick-action-icon">
                        <i class="ph ph-clock-counter-clockwise text-2xl"></i>
                    </div>
                    <span class="quick-action-label">Riwayat Ranking</span>
                </a>
            </div>
        </div>
    </div>

<?php endif; ?>
