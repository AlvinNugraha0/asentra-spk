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
