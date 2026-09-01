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
$hasResults = !empty($results);
$perfLabels = [];
$perfValues = [];
$perfRanks = [];
if ($hasResults) {
    foreach ($results as $r) {
        $perfLabels[] = $r['nama_teknisi'];
        $perfValues[] = round((float)$r['nilai_preferensi'], 3);
        $perfRanks[] = (int) ($r['ranking'] ?? 0); // ponytail: correct key is ranking
    }
}
?>

    <div class="stack" style="gap: var(--space-6);">
        <!-- Performance Bar Chart -->
        <div class="card" data-reveal="up">
            <div class="section-header" style="align-items: flex-start;">
                <div class="section-header-left">
                    <h3>Grafik Performa Skor SAW</h3>
                    <p>Distribusi nilai preferensi (Vi) seluruh teknisi periode <?= e(periodLabel($processedPeriode)) ?>.</p>
                </div>
                <?php if ($hasResults && $topResult): ?>
                <div style="text-align: right;">
                    <div class="tabular font-bold" style="font-size: 1.5rem; color: var(--brand); line-height: 1;">
                        <?= e(scoreFormat((float)$topResult['nilai_preferensi'], 3)) ?>
                    </div>
                    <div style="font-size: var(--text-sm); margin-top: 4px; color: var(--text-muted);">
                        Peringkat #1: <?= e($topResult['nama_teknisi']) ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <div style="position: relative; height: <?= max(260, count($results) * 30 + 40) ?>px; width: 100%;">
                <?php if (!$hasResults): ?>
                    <div style="height: 100%; display: flex; align-items: center; justify-content: center; border: 1px dashed var(--border); border-radius: var(--radius-md); background: var(--bg-surface);">
                        <div style="text-align: center; color: var(--text-muted);">
                            <i class="ph ph-chart-bar-horizontal" style="font-size: 2rem; margin-bottom: 8px; opacity: 0.5;"></i>
                            <div>Belum ada data evaluasi untuk ditampilkan.</div>
                        </div>
                    </div>
                <?php else: ?>
                    <canvas id="performanceChart"></canvas>
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
                                <td><?= rankBadge((int) $r['ranking']) ?></td>
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
    var ctx = document.getElementById('performanceChart');
    if (!ctx) return;

    var prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var perfRanks = <?= json_encode($perfRanks) ?>;
    var perfValues = <?= json_encode($perfValues) ?>;

    function getPerfColors(isDark) {
        var bgColors = [];
        var hoverColors = [];
        var borderColors = [];

        for (var i = 0; i < perfValues.length; i++) {
            if (i === 0) {
                // Rank 1: Gold highlight
                bgColors.push(isDark ? 'rgba(214, 178, 76, 0.9)' : 'rgba(214, 178, 76, 0.9)');
                hoverColors.push('#E6C968');
                borderColors.push('#D6B24C');
            } else if (i < 3) {
                // Rank 2-3: Secondary Accent
                bgColors.push(isDark ? 'rgba(214, 178, 76, 0.45)' : 'rgba(22, 101, 216, 0.75)');
                hoverColors.push(isDark ? 'rgba(214, 178, 76, 0.65)' : '#0052cc');
                borderColors.push(isDark ? '#D6B24C' : '#1665d8');
            } else {
                // Other ranks
                bgColors.push(isDark ? 'rgba(255, 255, 255, 0.12)' : 'rgba(22, 101, 216, 0.35)');
                hoverColors.push(isDark ? 'rgba(255, 255, 255, 0.2)' : 'rgba(22, 101, 216, 0.55)');
                borderColors.push(isDark ? 'rgba(255, 255, 255, 0.2)' : 'rgba(22, 101, 216, 0.5)');
            }
        }

        return {
            bgColors: bgColors,
            hoverColors: hoverColors,
            borderColors: borderColors,
            gridColor: isDark ? 'rgba(255, 255, 255, 0.06)' : 'rgba(0, 0, 0, 0.05)',
            tickColor: isDark ? '#A7A7A3' : '#6b7280',
            tooltipBg: isDark ? '#1C1C1F' : '#1f2937',
            tooltipTitleColor: isDark ? '#F5F5F3' : '#ffffff',
            tooltipBodyColor: isDark ? '#D6B24C' : '#ffffff',
            tooltipBorderColor: isDark ? 'rgba(255, 255, 255, 0.1)' : 'transparent',
        };
    }

    var isDarkMode = (document.documentElement.getAttribute('data-theme') === 'dark');
    var themeCfg = getPerfColors(isDarkMode);

    var perfChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?= json_encode($perfLabels) ?>,
            datasets: [{
                label: 'Skor SAW',
                data: perfValues,
                backgroundColor: themeCfg.bgColors,
                hoverBackgroundColor: themeCfg.hoverColors,
                borderColor: themeCfg.borderColors,
                borderWidth: 1,
                borderRadius: 4,
                maxBarThickness: 20,
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            animation: prefersReducedMotion ? false : {
                duration: 600,
                easing: 'easeOutQuart'
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: themeCfg.tooltipBg,
                    titleColor: themeCfg.tooltipTitleColor,
                    bodyColor: themeCfg.tooltipBodyColor,
                    borderColor: themeCfg.tooltipBorderColor,
                    borderWidth: 1,
                    padding: 12,
                    cornerRadius: 8,
                    titleFont: { family: "'Inter', sans-serif", size: 13 },
                    bodyFont: { family: "'Inter', sans-serif", size: 14, weight: 'bold' },
                    displayColors: false,
                    callbacks: {
                        label: function(context) {
                            var rank = perfRanks[context.dataIndex] || (context.dataIndex + 1);
                            return 'Skor SAW: ' + context.parsed.x.toFixed(3) + ' (Peringkat #' + rank + ')';
                        }
                    }
                }
            },
            scales: {
                x: {
                    border: { display: false },
                    grid: { color: themeCfg.gridColor },
                    beginAtZero: true,
                    suggestedMin: 0,
                    suggestedMax: 1,
                    ticks: {
                        stepSize: 0.2,
                        font: { family: "'Inter', sans-serif", size: 11 },
                        color: themeCfg.tickColor,
                        padding: 6,
                        callback: function(value) {
                            return value.toFixed(2);
                        }
                    }
                },
                y: {
                    grid: { display: false, drawBorder: false },
                    ticks: {
                        font: { family: "'Inter', sans-serif", size: 12, weight: '500' },
                        color: themeCfg.tickColor
                    }
                }
            }
        }
    });

    window.addEventListener('themechange', function(e) {
        if (!perfChart) return;
        var isDark = e.detail.theme === 'dark';
        var newCfg = getPerfColors(isDark);

        perfChart.data.datasets[0].backgroundColor = newCfg.bgColors;
        perfChart.data.datasets[0].hoverBackgroundColor = newCfg.hoverColors;
        perfChart.data.datasets[0].borderColor = newCfg.borderColors;

        perfChart.options.scales.x.grid.color = newCfg.gridColor;
        perfChart.options.scales.x.ticks.color = newCfg.tickColor;
        perfChart.options.scales.y.ticks.color = newCfg.tickColor;
        perfChart.options.plugins.tooltip.backgroundColor = newCfg.tooltipBg;
        perfChart.options.plugins.tooltip.titleColor = newCfg.tooltipTitleColor;
        perfChart.options.plugins.tooltip.bodyColor = newCfg.tooltipBodyColor;
        perfChart.options.plugins.tooltip.borderColor = newCfg.tooltipBorderColor;

        perfChart.update();
    });
});
</script>