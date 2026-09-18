<?php
/** @var string $title */
/** @var string $subtitle */
/** @var array<string, mixed> $detail */

$t = $detail['teknisi'];
$p = $detail['periode'];
$calc = $detail['calculation'];
$c1 = $calc['c1'] ?? [];
$c2 = $calc['c2'] ?? [];
$c3 = $calc['c3'] ?? [];
$penilaian = $detail['penilaian'] ?? null;
?>
<div class="page-header" data-reveal="up">
    <div class="row-between">
        <div>
            <h1 class="page-title"><?= e($title) ?></h1>
            <p class="page-subtitle"><?= e($subtitle) ?></p>
        </div>
        <div class="actions">
            <a href="<?= route('/owner/assessment?periode_id=' . $p['id_periode']) ?>" class="btn btn-secondary">
                ← Kembali ke Preview
            </a>
        </div>
    </div>
</div>

<!-- Overview Profile Card -->
<div class="card mb-4" data-reveal="up" style="display: flex; gap: 2rem; align-items: center; justify-content: space-between; flex-wrap: wrap;">
    <div>
        <span class="text-secondary text-sm">Teknisi</span>
        <h2 class="font-bold text-gold mb-1"><?= e($t['nama']) ?> (<?= e($t['kode_teknisi']) ?>)</h2>
        <div class="text-sm text-secondary">
            Periode: <strong><?= e($p['nama_periode']) ?> (<?= e($p['kode_periode']) ?>)</strong> |
            Rentang: <?= e($p['tanggal_mulai']) ?> s/d <?= e($p['tanggal_selesai']) ?>
        </div>
    </div>
    <div style="display: flex; gap: 1.5rem; text-align: right;">
        <div>
            <div class="text-xs text-secondary">C1 Kedisiplinan</div>
            <div class="font-bold weight-big text-primary"><?= e(number_format((float) ($c1['value'] ?? 0), 4, '.', '')) ?></div>
        </div>
        <div>
            <div class="text-xs text-secondary">C2 Kualitas Kerja</div>
            <div class="font-bold weight-big text-primary"><?= e(number_format((float) ($c2['value'] ?? 0), 4, '.', '')) ?></div>
        </div>
        <div>
            <div class="text-xs text-secondary">C3 Tanggung Jawab</div>
            <div class="font-bold weight-big text-primary"><?= e(number_format((float) ($c3['value'] ?? 0), 4, '.', '')) ?></div>
        </div>
    </div>
</div>

<?php if (!empty($calc['warning'])): ?>
    <div class="card mb-4" data-reveal="up" style="border-left: 4px solid #f59e0b;">
        <h4 class="font-bold text-warning mb-1">Catatan Observasi:</h4>
        <p class="text-sm mb-0"><?= e($calc['warning']) ?></p>
    </div>
<?php endif; ?>

<!-- Phase 6D: Grafik detail indikator teknisi -->
<div class="card mb-4" data-reveal="up">
    <div class="row-between mb-1" style="flex-wrap: wrap; gap: 0.5rem;">
        <div>
            <h3 class="card-title">Grafik Indikator</h3>
            <p class="text-xs text-secondary mb-0">Skor kuartal C1/C2/C3 (0-4) — sumber: Calculation Engine dari data operasional.</p>
        </div>
        <div class="grid-2charts" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1rem; width: 100%;">
            <div style="height: 260px; position: relative;">
                <canvas id="chartTeknisiRadar" aria-label="Grafik radar indikator C1 C2 C3 teknisi" role="img"></canvas>
            </div>
            <div style="height: 260px; position: relative;">
                <canvas id="chartTeknisiSub" aria-label="Grafik subindikator teknisi" role="img"></canvas>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Data dari backend (CalculationEngine + database operasional). Tidak ada perhitungan di JS.
    (function () {
        const radarData = <?= json_encode(App\Services\ChartDataService::technicianIndicatorDetail($detail), JSON_THROW_ON_ERROR) ?>;
        const subData   = <?= json_encode(App\Services\ChartDataService::technicianSubindicators($detail), JSON_THROW_ON_ERROR) ?>;

        const gridColor = 'rgba(107, 114, 128, 0.15)';
        const tickColor = '#6b7280';

        const ctxRadar = document.getElementById('chartTeknisiRadar');
        if (ctxRadar) {
            new Chart(ctxRadar, {
                type: 'radar',
                data: {
                    labels: radarData.labels,
                    datasets: [{
                        label: 'Skor Indikator (0-4)',
                        data: radarData.values,
                        backgroundColor: 'rgba(22, 101, 216, 0.25)',
                        borderColor: 'rgba(22, 101, 216, 1)',
                        borderWidth: 2,
                        pointBackgroundColor: 'rgba(22, 101, 216, 1)'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'top' } },
                    scales: {
                        r: {
                            beginAtZero: true,
                            suggestedMin: 0,
                            suggestedMax: 4,
                            grid: { color: gridColor },
                            angleLines: { color: gridColor },
                            pointLabels: { color: tickColor, font: { size: 11 } },
                            ticks: { color: tickColor, stepSize: 1 }
                        }
                    }
                }
            });
        }

        const ctxSub = document.getElementById('chartTeknisiSub');
        if (ctxSub) {
            new Chart(ctxSub, {
                type: 'bar',
                data: {
                    labels: subData.labels,
                    datasets: [{
                        label: 'Rata-rata Rating Subindikator (0-4)',
                        data: subData.values,
                        backgroundColor: 'rgba(245, 158, 11, 0.75)',
                        borderColor: 'rgba(245, 158, 11, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { position: 'top' } },
                    scales: {
                        x: {
                            grid: { color: gridColor },
                            ticks: {
                                color: tickColor,
                                // Hindari tumpang tindih label pada layar sempit
                                autoSkip: false,
                                maxRotation: 45,
                                minRotation: 0,
                                font: { size: 10 }
                            }
                        },
                        y: {
                            grid: { color: gridColor },
                            ticks: { color: tickColor },
                            beginAtZero: true,
                            suggestedMax: 4
                        }
                    }
                }
            });
        }
    })();
</script>

<!-- Phase 6E: Jejak perhitungan raw vs derived -->
<?= viewPartial('partials.calculation_trace', ['detail' => $detail, 'penilaian' => $penilaian]) ?>

<!-- C1 Kedisiplinan Breakdown -->
<div class="card mb-4" data-reveal="up">
    <div class="row-between mb-3">
        <h3 class="card-title">C1 — Kedisiplinan (Bobot 30%)</h3>
        <span class="badge badge-<?= ($c1['status'] ?? '') === 'complete' ? 'success' : 'warning' ?>">
            Skor Kuartal: <?= e(number_format((float) ($c1['value'] ?? 0), 4, '.', '')) ?> (<?= e((string) ($c1['months_available'] ?? 0)) ?>/3 Bulan)
        </span>
    </div>

    <div class="grid grid-3 mb-4" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
        <div class="p-3 bg-light rounded border">
            <div class="text-xs text-secondary font-semibold">Subindikator 1.1</div>
            <div class="font-bold text-gold">Kehadiran (Absensi)</div>
            <div class="text-sm mt-1">Rata-rata rating: <strong><?= e(number_format((float) ($c1['subindicators']['kehadiran'] ?? 0), 2, '.', '')) ?></strong> / 4.00</div>
            <div class="text-xs text-secondary mt-1">&ge;95% (4), 85-94% (3), 75-84% (2), &lt;75% (1)</div>
        </div>
        <div class="p-3 bg-light rounded border">
            <div class="text-xs text-secondary font-semibold">Subindikator 1.2</div>
            <div class="font-bold text-gold">Ketepatan Waktu</div>
            <div class="text-sm mt-1">Rata-rata rating: <strong><?= e(number_format((float) ($c1['subindicators']['terlambat'] ?? 0), 2, '.', '')) ?></strong> / 4.00</div>
            <div class="text-xs text-secondary mt-1">0-2 telat (4), 3-5 (3), 6-8 (2), &ge;9 (1)</div>
        </div>
        <div class="p-3 bg-light rounded border">
            <div class="text-xs text-secondary font-semibold">Subindikator 1.3</div>
            <div class="font-bold text-gold">Kepatuhan Jadwal Operasional</div>
            <div class="text-sm mt-1">Rata-rata rating: <strong><?= e(number_format((float) ($c1['subindicators']['jadwal'] ?? 0), 2, '.', '')) ?></strong> / 4.00</div>
            <div class="text-xs text-secondary mt-1">&ge;95% (4), 80-94% (3), 65-79% (2), &lt;65% (1)</div>
        </div>
    </div>

    <h4 class="font-bold text-sm mb-2 text-secondary">Rincian Data Bulanan:</h4>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>BULAN</th>
                    <th>HARI KERJA</th>
                    <th>HADIR</th>
                    <th>SAKIT / IZIN / ALPA</th>
                    <th>TERLAMBAT</th>
                    <th>TERJADWAL / SESUAI</th>
                    <th>NILAI BULANAN</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($detail['raw_data']['kedisiplinan'])): ?>
                    <tr><td colspan="7" class="text-center text-secondary py-2">Tidak ada data kedisiplinan.</td></tr>
                <?php else: ?>
                    <?php foreach ($detail['raw_data']['kedisiplinan'] as $kd): ?>
                        <tr>
                            <td>Bulan <?= e((string) $kd['bulan']) ?></td>
                            <td><?= e((string) $kd['total_hari_kerja']) ?> hari</td>
                            <td class="font-semibold text-success"><?= e((string) $kd['hadir']) ?></td>
                            <td><?= e((string) $kd['sakit']) ?> / <?= e((string) $kd['izin']) ?> / <?= e((string) $kd['alpa']) ?></td>
                            <td class="<?= $kd['terlambat'] > 0 ? 'text-warning font-semibold' : '' ?>"><?= e((string) $kd['terlambat']) ?> kali</td>
                            <td><?= e((string) $kd['sesuai_jadwal']) ?> dari <?= e((string) $kd['pekerjaan_terjadwal']) ?></td>
                            <td class="font-bold text-gold">
                                <?php
                                $mScore = $c1['monthly_details'][$kd['bulan']]['value'] ?? null;
                                echo $mScore !== null ? e(number_format((float) $mScore, 4, '.', '')) : '-';
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- C2 Kualitas Hasil Kerja Breakdown -->
<div class="card mb-4" data-reveal="up">
    <div class="row-between mb-3">
        <h3 class="card-title">C2 — Kualitas Hasil Kerja (Bobot 40%)</h3>
        <span class="badge badge-<?= ($c2['status'] ?? '') === 'complete' ? 'success' : 'warning' ?>">
            Skor Kuartal: <?= e(number_format((float) ($c2['value'] ?? 0), 4, '.', '')) ?> (<?= e((string) ($c2['months_available'] ?? 0)) ?>/3 Bulan)
        </span>
    </div>

    <div class="grid grid-3 mb-4" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
        <div class="p-3 bg-light rounded border">
            <div class="text-xs text-secondary font-semibold">Subindikator 2.1</div>
            <div class="font-bold text-gold">Kerapian Kerja</div>
            <div class="text-sm mt-1">Rata-rata rating: <strong><?= e(number_format((float) ($c2['subindicators']['rapi'] ?? 0), 2, '.', '')) ?></strong> / 4.00</div>
            <div class="text-xs text-secondary mt-1">&ge;90% (4), 75-89% (3), 60-74% (2), &lt;60% (1)</div>
        </div>
        <div class="p-3 bg-light rounded border">
            <div class="text-xs text-secondary font-semibold">Subindikator 2.2</div>
            <div class="font-bold text-gold">Presisi & Akurasi</div>
            <div class="text-sm mt-1">Rata-rata rating: <strong><?= e(number_format((float) ($c2['subindicators']['presisi'] ?? 0), 2, '.', '')) ?></strong> / 4.00</div>
            <div class="text-xs text-secondary mt-1">&ge;90% (4), 75-89% (3), 60-74% (2), &lt;60% (1)</div>
        </div>
        <div class="p-3 bg-light rounded border">
            <div class="text-xs text-secondary font-semibold">Subindikator 2.3</div>
            <div class="font-bold text-gold">Kesesuaian Desain</div>
            <div class="text-sm mt-1">Rata-rata rating: <strong><?= e(number_format((float) ($c2['subindicators']['sesuai_desain'] ?? 0), 2, '.', '')) ?></strong> / 4.00</div>
            <div class="text-xs text-secondary mt-1">&ge;90% (4), 75-89% (3), 60-74% (2), &lt;60% (1)</div>
        </div>
    </div>

    <h4 class="font-bold text-sm mb-2 text-secondary">Log Sampel Inspeksi Pekerjaan:</h4>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>TANGGAL</th>
                    <th>BULAN</th>
                    <th>NAMA PEKERJAAN</th>
                    <th>RAPI</th>
                    <th>PRESISI</th>
                    <th>SESUAI DESAIN</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($detail['raw_data']['pekerjaan'])): ?>
                    <tr><td colspan="6" class="text-center text-secondary py-2">Tidak ada log inspeksi pekerjaan.</td></tr>
                <?php else: ?>
                    <?php foreach ($detail['raw_data']['pekerjaan'] as $pk): ?>
                        <tr>
                            <td><?= e($pk['tanggal']) ?></td>
                            <td>Bulan <?= e((string) $pk['bulan']) ?></td>
                            <td class="font-medium"><?= e($pk['nama_pekerjaan']) ?></td>
                            <td>
                                <span class="badge badge-<?= $pk['rapi'] ? 'success' : 'danger' ?> text-xs">
                                    <?= $pk['rapi'] ? 'Ya' : 'Tidak' ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-<?= $pk['presisi'] ? 'success' : 'danger' ?> text-xs">
                                    <?= $pk['presisi'] ? 'Ya' : 'Tidak' ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge badge-<?= $pk['sesuai_desain'] ? 'success' : 'danger' ?> text-xs">
                                    <?= $pk['sesuai_desain'] ? 'Ya' : 'Tidak' ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- C3 Tanggung Jawab Breakdown -->
<div class="card mb-4" data-reveal="up">
    <div class="row-between mb-3">
        <h3 class="card-title">C3 — Tanggung Jawab (Bobot 30%)</h3>
        <span class="badge badge-<?= ($c3['status'] ?? '') === 'complete' ? 'success' : 'warning' ?>">
            Skor Kuartal: <?= e(number_format((float) ($c3['value'] ?? 0), 4, '.', '')) ?> (<?= e((string) ($c3['months_available'] ?? 0)) ?>/3 Bulan)
        </span>
    </div>

    <div class="grid grid-4 mb-4" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem;">
        <div class="p-3 bg-light rounded border">
            <div class="text-xs text-secondary font-semibold">Subindikator 3.1</div>
            <div class="font-bold text-gold">Perawatan Alat</div>
            <div class="text-sm mt-1">Rata-rata: <strong><?= e(number_format((float) ($c3['subindicators']['perawatan_alat'] ?? 0), 2, '.', '')) ?></strong> / 4.00</div>
            <div class="text-xs text-secondary mt-1">Skala Likert 1-4</div>
        </div>
        <div class="p-3 bg-light rounded border">
            <div class="text-xs text-secondary font-semibold">Subindikator 3.2</div>
            <div class="font-bold text-gold">Efisiensi Material</div>
            <div class="text-sm mt-1">Rata-rata: <strong><?= e(number_format((float) ($c3['subindicators']['efisiensi_material'] ?? 0), 2, '.', '')) ?></strong> / 4.00</div>
            <div class="text-xs text-secondary mt-1">Skala Likert 1-4</div>
        </div>
        <div class="p-3 bg-light rounded border">
            <div class="text-xs text-secondary font-semibold">Subindikator 3.3</div>
            <div class="font-bold text-gold">Inisiatif Lapangan</div>
            <div class="text-sm mt-1">Rata-rata: <strong><?= e(number_format((float) ($c3['subindicators']['inisiatif'] ?? 0), 2, '.', '')) ?></strong> / 4.00</div>
            <div class="text-xs text-secondary mt-1">Skala Likert 1-4</div>
        </div>
        <div class="p-3 bg-light rounded border">
            <div class="text-xs text-secondary font-semibold">Subindikator 3.4</div>
            <div class="font-bold text-gold">Kepatuhan Prosedur</div>
            <div class="text-sm mt-1">Rata-rata: <strong><?= e(number_format((float) ($c3['subindicators']['kepatuhan_prosedur'] ?? 0), 2, '.', '')) ?></strong> / 4.00</div>
            <div class="text-xs text-secondary mt-1">Skala Likert 1-4</div>
        </div>
    </div>

    <h4 class="font-bold text-sm mb-2 text-secondary">Rincian Rating Bulanan:</h4>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>BULAN</th>
                    <th>PERAWATAN ALAT</th>
                    <th>EFISIENSI MATERIAL</th>
                    <th>INISIATIF</th>
                    <th>KEPATUHAN PROSEDUR</th>
                    <th>NILAI BULANAN</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($detail['raw_data']['tanggung_jawab'])): ?>
                    <tr><td colspan="6" class="text-center text-secondary py-2">Tidak ada data tanggung jawab.</td></tr>
                <?php else: ?>
                    <?php foreach ($detail['raw_data']['tanggung_jawab'] as $tj): ?>
                        <tr>
                            <td>Bulan <?= e((string) $tj['bulan']) ?></td>
                            <td>Skala <?= e((string) $tj['perawatan_alat']) ?>/4</td>
                            <td>Skala <?= e((string) $tj['efisiensi_material']) ?>/4</td>
                            <td>Skala <?= e((string) $tj['inisiatif']) ?>/4</td>
                            <td>Skala <?= e((string) $tj['kepatuhan_prosedur']) ?>/4</td>
                            <td class="font-bold text-gold">
                                <?php
                                $mScore3 = $c3['monthly_details'][$tj['bulan']]['value'] ?? null;
                                echo $mScore3 !== null ? e(number_format((float) $mScore3, 4, '.', '')) : '-';
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
