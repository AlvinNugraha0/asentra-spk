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
$user = currentUser() ?? [];
$userName = $user['nama'] ?? 'Admin';
?>

<!-- Welcome Banner -->
<div class="welcome-banner" data-reveal="up">
    <div class="welcome-banner-content">
        <div class="welcome-banner-top">
            <div>
                <h1>Welcome back, <?= e($userName) ?></h1>
                <p>Berikut adalah ringkasan data operasional teknisi hari ini.</p>
            </div>
            <div class="welcome-banner-actions">
                <a href="<?= route('/admin/penilaian/create') ?>" class="btn-banner">
                    <i class="ph ph-pencil-simple text-lg"></i>
                    Input Penilaian
                </a>
            </div>
        </div>

        <!-- Embedded Stats -->
        <div class="welcome-stats">
            <div class="welcome-stat">
                <div class="welcome-stat-label">
                    <i class="ph ph-users text-lg"></i>
                    Teknisi Aktif
                </div>
                <div class="welcome-stat-value tabular" data-count="<?= e((string) $activeTeknisi) ?>"><?= e((string) $activeTeknisi) ?></div>
                <div class="welcome-stat-meta">Total teknisi lapangan</div>
            </div>
            <div class="welcome-stat">
                <div class="welcome-stat-label">
                    <i class="ph ph-clipboard-text text-lg"></i>
                    Penilaian Periode
                </div>
                <div class="welcome-stat-value tabular" data-count="<?= e((string) $countByLatest) ?>"><?= e((string) $countByLatest) ?></div>
                <div class="welcome-stat-meta"><?= $latestPeriode ? e(periodLabel($latestPeriode)) : 'Belum ada periode' ?></div>
            </div>
            <div class="welcome-stat">
                <div class="welcome-stat-label">
                    <i class="ph ph-sliders-horizontal text-lg"></i>
                    Total Kriteria
                </div>
                <div class="welcome-stat-value tabular" data-count="<?= e((string) $kriteriaCount) ?>"><?= e((string) $kriteriaCount) ?></div>
                <div class="welcome-stat-meta">C1, C2, C3</div>
            </div>
            <div class="welcome-stat">
                <div class="welcome-stat-label">
                    <i class="ph ph-chart-pie-slice text-lg"></i>
                    Total Bobot
                </div>
                <div class="welcome-stat-value tabular"><?= e(weightPercent($totalBobot)) ?></div>
                <div class="welcome-stat-meta"><?= $bobotValid ? 'Distribusi bobot valid' : 'Bobot tidak valid!' ?></div>
            </div>
        </div>
    </div>
</div>

<?php
$criteriaAvg = $criteriaAvg ?? ['c1' => 0, 'c2' => 0, 'c3' => 0, 'count' => 0, 'periode' => $latestPeriode];
$hasCriteriaData = ($criteriaAvg['count'] ?? 0) > 0;
$overallAvg = $hasCriteriaData ? round(($criteriaAvg['c1'] + $criteriaAvg['c2'] + $criteriaAvg['c3']) / 3, 2) : 0;
?>

<div class="stack" style="gap: var(--space-6);">
    <!-- Criteria Average Bar Chart -->
    <div class="card" data-reveal="up">
        <div class="section-header" style="align-items: flex-start;">
            <div class="section-header-left">
                <h3>Rata-rata Capaian Kriteria</h3>
                <p>Perbandingan nilai rata-rata kriteria periode <?= e($criteriaAvg['periode'] ? periodLabel($criteriaAvg['periode']) : 'saat ini') ?> (Skala 1 - 4).</p>
            </div>
            <?php if ($hasCriteriaData): ?>
            <div style="text-align: right;">
                <div class="tabular font-bold" style="font-size: 1.5rem; color: var(--brand); line-height: 1;">
                    <?= e(number_format($overallAvg, 2)) ?> <span style="font-size: var(--text-sm); font-weight: normal; color: var(--text-muted);">/ 4.00</span>
                </div>
                <div style="font-size: var(--text-sm); margin-top: 4px; color: var(--text-muted);">
                    <?= e((string) $criteriaAvg['count']) ?> Teknisi Dinilai
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <div style="position: relative; height: 260px; width: 100%;">
            <?php if (!$hasCriteriaData): ?>
                <div style="height: 100%; display: flex; align-items: center; justify-content: center; border: 1px dashed var(--border); border-radius: var(--radius-md); background: var(--bg-surface);">
                    <div style="text-align: center; color: var(--text-muted);">
                        <i class="ph ph-chart-bar" style="font-size: 2rem; margin-bottom: 8px; opacity: 0.5;"></i>
                        <div>Belum ada data penilaian pada periode ini.</div>
                    </div>
                </div>
            <?php else: ?>
                <canvas id="criteriaChart"></canvas>
            <?php endif; ?>
        </div>
    </div>

    <!-- Main Content Grid: Table (2/3) + Sidebar (1/3) -->
    <div class="grid-2-1" data-reveal="up">

    <!-- Recent Evaluations Table -->
    <div class="card">
        <div class="section-header">
            <div class="section-header-left">
                <h3>Penilaian Terbaru</h3>
                <p>Data input penilaian kinerja teknisi terakhir.</p>
            </div>
            <a href="<?= route('/admin/penilaian') ?>" class="btn-link">Lihat Semua</a>
        </div>

        <?php if (empty($recent)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">
                    <i class="ph ph-clipboard-text text-3xl"></i>
                </div>
                <div class="empty-state-title">Belum Ada Data Penilaian</div>
                <p>Mulai dengan input penilaian teknisi.</p>
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
                                    <div class="flex items-center gap-3">
                                        <div class="tech-avatar tech-avatar-blue"><?= e(strtoupper(mb_substr($r['nama_teknisi'], 0, 2))) ?></div>
                                        <div>
                                            <strong><?= e($r['nama_teknisi']) ?></strong>
                                            <div class="meta-text"><?= e($r['kode_teknisi']) ?></div>
                                        </div>
                                    </div>
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

    <!-- Right Sidebar: Criteria + Quick Actions -->
    <div class="stack" style="gap: var(--space-6);">

        <!-- Criteria Summary -->
        <div class="card">
            <div class="section-header">
                <div class="section-header-left">
                    <h3>Ringkasan Kriteria</h3>
                    <p>Bobot dan atribut penilaian SAW.</p>
                </div>
            </div>

            <div class="stack">
                <?php
                $criteriaIcons = [
                    0 => ['class' => 'criteria-icon-blue', 'svg' => '<i class="ph ph-target text-xl"></i>'],
                    1 => ['class' => 'criteria-icon-green', 'svg' => '<i class="ph ph-star text-xl"></i>'],
                    2 => ['class' => 'criteria-icon-purple', 'svg' => '<i class="ph ph-chart-bar text-xl"></i>'],
                ];
                foreach ($kriteria as $idx => $k):
                    $iconData = $criteriaIcons[$idx] ?? $criteriaIcons[0];
                ?>
                    <div class="criteria-item">
                        <div class="criteria-item-left">
                            <div class="criteria-icon <?= $iconData['class'] ?>">
                                <?= $iconData['svg'] ?>
                            </div>
                            <div>
                                <div class="criteria-item-name"><?= e($k['kode']) ?> · <?= e($k['nama_kriteria']) ?></div>
                                <span class="badge badge-brand criteria-item-type"><?= e(ucfirst($k['atribut'])) ?></span>
                            </div>
                        </div>
                        <div class="criteria-weight"><?= e(weightPercent((float) $k['bobot'])) ?></div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if (!$bobotValid): ?>
                <div class="badge badge-danger mt-4">
                    Total bobot harus 100%
                </div>
            <?php endif; ?>
        </div>

        <!-- Quick Actions -->
        <div class="quick-action-grid">
            <a href="<?= route('/admin/teknisi/create') ?>" class="quick-action">
                <div class="quick-action-icon">
                    <i class="ph ph-user-plus text-2xl"></i>
                </div>
                <span class="quick-action-label">Tambah Teknisi</span>
            </a>
            <a href="<?= route('/admin/penilaian/create') ?>" class="quick-action">
                <div class="quick-action-icon">
                    <i class="ph ph-pencil-simple text-2xl"></i>
                </div>
                <span class="quick-action-label">Input Penilaian</span>
            </a>
        </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var ctx = document.getElementById('criteriaChart');
    if (!ctx) return;

    var prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    function getCriteriaChartThemeConfig(isDark) {
        return {
            barBg: isDark ? 'rgba(214, 178, 76, 0.85)' : 'rgba(22, 101, 216, 0.85)',
            barHoverBg: isDark ? '#E6C968' : '#0052cc',
            barBorder: isDark ? '#D6B24C' : '#1665d8',
            gridColor: isDark ? 'rgba(255, 255, 255, 0.06)' : 'rgba(0, 0, 0, 0.05)',
            tickColor: isDark ? '#A7A7A3' : '#6b7280',
            tooltipBg: isDark ? '#1C1C1F' : '#1f2937',
            tooltipTitleColor: isDark ? '#F5F5F3' : '#ffffff',
            tooltipBodyColor: isDark ? '#D6B24C' : '#ffffff',
            tooltipBorderColor: isDark ? 'rgba(255, 255, 255, 0.1)' : 'transparent',
        };
    }

    var isDarkMode = (document.documentElement.getAttribute('data-theme') === 'dark');
    var themeCfg = getCriteriaChartThemeConfig(isDarkMode);

    var criteriaChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['C1: Kedisiplinan', 'C2: Kualitas Kerja', 'C3: Tanggung Jawab'],
            datasets: [{
                label: 'Nilai Rata-rata',
                data: [<?= $criteriaAvg['c1'] ?>, <?= $criteriaAvg['c2'] ?>, <?= $criteriaAvg['c3'] ?>],
                backgroundColor: themeCfg.barBg,
                hoverBackgroundColor: themeCfg.barHoverBg,
                borderColor: themeCfg.barBorder,
                borderWidth: 1,
                borderRadius: 6,
                maxBarThickness: 48,
            }]
        },
        options: {
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
                            return 'Rata-rata: ' + context.parsed.y.toFixed(2) + ' / 4.00';
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false, drawBorder: false },
                    ticks: {
                        font: { family: "'Inter', sans-serif", size: 12, weight: '500' },
                        color: themeCfg.tickColor
                    }
                },
                y: {
                    border: { display: false },
                    grid: { color: themeCfg.gridColor },
                    beginAtZero: true,
                    suggestedMin: 0,
                    suggestedMax: 4,
                    ticks: {
                        stepSize: 1,
                        font: { family: "'Inter', sans-serif", size: 12 },
                        color: themeCfg.tickColor,
                        padding: 8,
                        callback: function(value) {
                            return value.toFixed(1);
                        }
                    }
                }
            }
        }
    });

    window.addEventListener('themechange', function(e) {
        if (!criteriaChart) return;
        var isDark = e.detail.theme === 'dark';
        var newCfg = getCriteriaChartThemeConfig(isDark);

        criteriaChart.data.datasets[0].backgroundColor = newCfg.barBg;
        criteriaChart.data.datasets[0].hoverBackgroundColor = newCfg.barHoverBg;
        criteriaChart.data.datasets[0].borderColor = newCfg.barBorder;

        criteriaChart.options.scales.y.grid.color = newCfg.gridColor;
        criteriaChart.options.scales.x.ticks.color = newCfg.tickColor;
        criteriaChart.options.scales.y.ticks.color = newCfg.tickColor;
        criteriaChart.options.plugins.tooltip.backgroundColor = newCfg.tooltipBg;
        criteriaChart.options.plugins.tooltip.titleColor = newCfg.tooltipTitleColor;
        criteriaChart.options.plugins.tooltip.bodyColor = newCfg.tooltipBodyColor;
        criteriaChart.options.plugins.tooltip.borderColor = newCfg.tooltipBorderColor;

        criteriaChart.update();
    });
});
</script>
