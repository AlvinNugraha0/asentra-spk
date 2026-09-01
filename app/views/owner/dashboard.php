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
                <div class="welcome-stat-value" style="font-size: var(--text-lg);">
                    <?= $latestPeriode ? e(periodLabel($latestPeriode)) : '-' ?></div>
                <div class="welcome-stat-meta">Data penilaian terakhir</div>
            </div>
            <div class="welcome-stat">
                <div class="welcome-stat-label">
                    <i class="ph ph-users text-lg"></i>
                    Teknisi Aktif
                </div>
                <div class="welcome-stat-value tabular" data-count="<?= e((string) $activeTeknisi) ?>">
                    <?= e((string) $activeTeknisi) ?></div>
                <div class="welcome-stat-meta">Total teknisi aktif</div>
            </div>
            <div class="welcome-stat">
                <div class="welcome-stat-label">
                    <i class="ph ph-trophy text-lg"></i>
                    Peringkat #1
                </div>
                <div class="welcome-stat-value" style="font-size: var(--text-lg); color: var(--gold-light);">
                    <?= $topResult ? e($topResult['nama_teknisi']) : '-' ?></div>
                <div class="welcome-stat-meta">Teknisi terbaik</div>
            </div>
            <div class="welcome-stat">
                <div class="welcome-stat-label">
                    <i class="ph ph-chart-line-up text-lg"></i>
                    Skor Tertinggi
                </div>
                <div class="welcome-stat-value tabular" style="color: var(--gold-light);">
                    <?= $topResult ? e(scoreFormat((float) $topResult['nilai_preferensi'], 3)) : '-' ?></div>
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

    <?php
    $trendSummary = $trendSummary ?? ['trendData' => [], 'latestAvg' => null, 'deltaLabel' => '', 'deltaType' => 'neutral'];
    $hasTrendData = count($trendSummary['trendData']) > 0;
    $hasSufficientTrendData = count($trendSummary['trendData']) > 1;
    $trendLabels = [];
    $trendValues = [];
    foreach ($trendSummary['trendData'] as $td) {
        $trendLabels[] = periodLabel($td['periode']);
        $trendValues[] = round((float)$td['avg_score'], 3);
    }
    ?>

    <div class="stack" style="gap: var(--space-6);">
        <!-- Trend Chart -->
        <div class="card" data-reveal="up">
            <div class="section-header" style="align-items: flex-start;">
                <div class="section-header-left">
                    <h3>Tren Kinerja</h3>
                    <p>Rata-rata skor akhir SAW berdasarkan periode penilaian.</p>
                </div>
                <?php if ($hasTrendData): ?>
                <div style="text-align: right;">
                    <div class="tabular font-bold" style="font-size: 1.5rem; color: var(--brand); line-height: 1;">
                        <?= e(number_format($trendSummary['latestAvg'], 3)) ?>
                    </div>
                    <?php if ($hasSufficientTrendData): ?>
                    <div style="font-size: var(--text-sm); margin-top: 4px; color: <?= $trendSummary['deltaType'] === 'positive' ? 'var(--success)' : ($trendSummary['deltaType'] === 'negative' ? 'var(--danger)' : 'var(--text-light)') ?>;">
                        <?= e($trendSummary['deltaLabel']) ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <div style="position: relative; height: 250px; width: 100%;">
                <?php if (!$hasTrendData): ?>
                    <div style="height: 100%; display: flex; align-items: center; justify-content: center; border: 1px dashed var(--border-light); border-radius: var(--radius-md); background: var(--bg-body);">
                        <div style="text-align: center; color: var(--text-light);">
                            <i class="ph ph-chart-line" style="font-size: 2rem; margin-bottom: 8px; opacity: 0.5;"></i>
                            <div>Belum ada data historis evaluasi.</div>
                        </div>
                    </div>
                <?php elseif (!$hasSufficientTrendData): ?>
                    <div style="height: 100%; display: flex; align-items: center; justify-content: center; border: 1px dashed var(--border-light); border-radius: var(--radius-md); background: var(--bg-body);">
                        <div style="text-align: center; color: var(--text-light);">
                            <i class="ph ph-chart-line" style="font-size: 2rem; margin-bottom: 8px; opacity: 0.5;"></i>
                            <div>Tren kinerja membutuhkan lebih dari satu periode evaluasi.</div>
                            <div style="font-size: var(--text-sm); margin-top: 4px;">Periode saat ini: <?= e($trendLabels[0]) ?> (<?= e(number_format($trendValues[0], 3)) ?>)</div>
                        </div>
                    </div>
                <?php else: ?>
                    <canvas id="trendChart"></canvas>
                <?php endif; ?>
            </div>
        </div>

        <!-- Main Content Grid: Ranking Table (2/3) + Top Performers (1/3) -->
        <div class="grid-2-1" data-reveal="up">

        <!-- Ranking Table -->
        <div class="card">
            <div class="section-header">
                <div class="section-header-left">
                    <h3>Ranking Terakhir — <?= e(periodLabel($processedPeriode)) ?></h3>
                    <p><?= e((string) $evaluatedCount) ?> teknisi dievaluasi.</p>
                </div>
                <a href="<?= route('/owner/ranking?periode=' . urlencode($processedPeriode)) ?>" class="btn-link">Lihat
                    Detail</a>
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
                                        <div class="tech-avatar tech-avatar-blue">
                                            <?= e(strtoupper(mb_substr($r['nama_teknisi'], 0, 2))) ?></div>
                                        <div>
                                            <strong><?= e($r['nama_teknisi']) ?></strong>
                                            <div class="meta-text"><?= e($r['kode_teknisi']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="badge badge-neutral"><?= e((string) $r['c1']) ?></span></td>
                                <td><span class="badge badge-neutral"><?= e((string) $r['c2']) ?></span></td>
                                <td><span class="badge badge-neutral"><?= e((string) $r['c3']) ?></span></td>
                                <td class="font-bold tabular" style="color: var(--brand);">
                                    <?= e(scoreFormat((float) $r['nilai_preferensi'], 3)) ?></td>
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
                                <div
                                    class="performer-avatar <?= $idx === 0 ? 'performer-avatar-gold' : 'performer-avatar-silver' ?>">
                                    <?= e(strtoupper(mb_substr($r['nama_teknisi'], 0, 2))) ?>
                                    <span
                                        class="performer-rank <?= $idx === 0 ? 'performer-rank-gold' : 'performer-rank-default' ?>"><?= e((string) ($idx + 1)) ?></span>
                                </div>
                                <div class="performer-info">
                                    <div class="performer-name"><?= e($r['nama_teknisi']) ?></div>
                                    <div class="performer-meta"><?= e($r['kode_teknisi']) ?></div>
                                </div>
                                <div class="performer-score">
                                    <span
                                        class="performer-score-value"><?= e(scoreFormat((float) $r['nilai_preferensi'], 3)) ?></span>
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

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var ctx = document.getElementById('trendChart');
    if (!ctx) return;

    var prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode($trendLabels) ?>,
            datasets: [{
                label: 'Rata-rata Skor SAW',
                data: <?= json_encode($trendValues) ?>,
                borderColor: '#1665d8',
                backgroundColor: 'rgba(22, 101, 216, 0.05)',
                borderWidth: 2,
                pointBackgroundColor: '#ffffff',
                pointBorderColor: '#1665d8',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6,
                fill: true,
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: prefersReducedMotion ? false : {
                duration: 750,
                easing: 'easeOutQuart'
            },
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#1f2937',
                    padding: 12,
                    cornerRadius: 8,
                    titleFont: { family: "'Inter', sans-serif", size: 13 },
                    bodyFont: { family: "'Inter', sans-serif", size: 14, weight: 'bold' },
                    displayColors: false,
                    callbacks: {
                        label: function(context) {
                            return 'Skor: ' + context.parsed.y.toFixed(3);
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false, drawBorder: false },
                    ticks: {
                        font: { family: "'Inter', sans-serif", size: 12 },
                        color: '#6b7280'
                    }
                },
                y: {
                    border: { display: false },
                    grid: { color: 'rgba(0, 0, 0, 0.05)' },
                    beginAtZero: false,
                    suggestedMin: 0,
                    suggestedMax: 1,
                    ticks: {
                        font: { family: "'Inter', sans-serif", size: 12 },
                        color: '#6b7280',
                        padding: 10,
                        callback: function(value) {
                            return value.toFixed(2);
                        }
                    }
                }
            }
        }
    });
});
</script>