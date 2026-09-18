<?php
/** @var string $title */
/** @var string $subtitle */
/** @var array<int, array<string, mixed>> $periods */
/** @var int $selectedPeriodeId */
/** @var array<string, mixed>|null $summary */
/** @var string $mode */ // ringkasan | tabulasi | grafik

$p = $summary['periode'] ?? null;
$evals = $summary['evaluations'] ?? [];
$tabulasi = $summary['tabulasi'] ?? [];
$chartIndicator = $summary['chart_indicator'] ?? ['labels' => [], 'c1' => [], 'c2' => [], 'c3' => []];
$chartVi = $summary['chart_vi'] ?? ['labels' => [], 'vi' => [], 'source' => 'preview'];
$isConfirmed = $summary['is_confirmed'] ?? false;
$canConfirm = $summary['can_confirm'] ?? false;

$mode = in_array($mode, ['ringkasan', 'tabulasi', 'grafik'], true) ? $mode : 'ringkasan';

$badgeClass = static fn (string $sd): string => match ($sd) {
    'confirmed' => 'success',
    'calculated' => 'primary',
    'partial' => 'warning',
    default => 'neutral',
};
?>
<div class="page-header" data-reveal="up">
    <div class="row-between">
        <div>
            <h1 class="page-title"><?= e($title) ?></h1>
            <p class="page-subtitle"><?= e($subtitle) ?></p>
        </div>
        <div class="actions">
            <?php if ($canConfirm && $p): ?>
                <form method="POST" action="<?= route('/owner/assessment/' . $p['id_periode'] . '/confirm') ?>" style="margin: 0;">
                    <?= csrfField() ?>
                    <button type="submit" class="btn btn-primary" onclick="return confirm('Konfirmasi hasil penilaian periode <?= e($p['nama_periode']) ?>? Penilaian yang telah dikonfirmasi akan dikunci dan siap diproses ke tahap SAW.')">
                        ✓ Konfirmasi Penilaian
                    </button>
                </form>
            <?php elseif ($isConfirmed): ?>
                <span class="badge badge-success p-2" style="font-size: 0.9rem;">
                    ✓ Terkonfirmasi Resmi
                </span>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Periode Selector -->
<div class="card mb-4" data-reveal="up">
    <form method="GET" action="<?= route('/owner/assessment') ?>" style="display: flex; gap: 1rem; align-items: flex-end; flex-wrap: wrap;">
        <div class="form-group" style="flex: 1; min-width: 250px; margin-bottom: 0;">
            <label for="periode_id" class="form-label font-semibold">Pilih Periode Triwulan</label>
            <select name="periode_id" id="periode_id" class="input" onchange="this.form.submit()">
                <?php if (empty($periods)): ?>
                    <option value="">-- Belum ada periode kuartal aktif --</option>
                <?php else: ?>
                    <?php foreach ($periods as $item): ?>
                        <option value="<?= e((string) $item['id_periode']) ?>" <?= $item['id_periode'] == $selectedPeriodeId ? 'selected' : '' ?>>
                            <?= e($item['kode_periode']) ?> — <?= e($item['nama_periode']) ?> (<?= strtoupper(e((string) $item['status'])) ?>)
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-secondary">Tampilkan</button>
    </form>
</div>

<?php if ($p && $summary): ?>
    <!-- Mode Tabs: Ringkasan / Tabulasi / Grafik -->
    <div class="card mb-4" data-reveal="up" style="padding: 0.5rem 1rem;">
        <div class="tab-nav" role="tablist" style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
            <a href="<?= route('/owner/assessment?periode_id=' . $p['id_periode'] . '&mode=ringkasan') ?>"
               class="tab-item <?= $mode === 'ringkasan' ? 'tab-active' : '' ?>"
               role="tab" aria-selected="<?= $mode === 'ringkasan' ? 'true' : 'false' ?>">
                Ringkasan
            </a>
            <a href="<?= route('/owner/assessment?periode_id=' . $p['id_periode'] . '&mode=tabulasi') ?>"
               class="tab-item <?= $mode === 'tabulasi' ? 'tab-active' : '' ?>"
               role="tab" aria-selected="<?= $mode === 'tabulasi' ? 'true' : 'false' ?>">
                Tabulasi
            </a>
            <a href="<?= route('/owner/assessment?periode_id=' . $p['id_periode'] . '&mode=grafik') ?>"
               class="tab-item <?= $mode === 'grafik' ? 'tab-active' : '' ?>"
               role="tab" aria-selected="<?= $mode === 'grafik' ? 'true' : 'false' ?>">
                Grafik
            </a>
        </div>
    </div>

    <?php if ($mode === 'ringkasan'): ?>
        <!-- ============ MODE RINGKASAN ============ -->
        <?php if ($canConfirm): ?>
            <?= viewPartial('partials.pre_confirmation_checklist', ['summary' => $summary]) ?>
        <?php elseif ($isConfirmed): ?>
            <?= viewPartial('partials.pre_confirmation_checklist', ['summary' => $summary]) ?>
        <?php endif; ?>

        <div class="grid grid-4 mb-4" data-reveal="up" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
            <div class="card p-3">
                <div class="text-secondary text-sm">Total Teknisi Dinilai</div>
                <div class="font-bold weight-big text-gold"><?= e((string) $summary['total_evaluations']) ?></div>
            </div>
            <div class="card p-3">
                <div class="text-secondary text-sm">Status Lengkap (3 Bulan Penuh)</div>
                <div class="font-bold weight-big text-success"><?= e((string) $summary['calculated_count']) ?></div>
            </div>
            <div class="card p-3">
                <div class="text-secondary text-sm">Status Parsial (&lt; 3 Bulan)</div>
                <div class="font-bold weight-big text-warning"><?= e((string) $summary['partial_count']) ?></div>
            </div>
            <div class="card p-3">
                <div class="text-secondary text-sm">Status Konfirmasi</div>
                <div class="font-bold weight-big <?= $isConfirmed ? 'text-success' : 'text-warning' ?>">
                    <?= $isConfirmed ? 'CONFIRMED' : 'MENUNGGU REVIEW' ?>
                </div>
            </div>
        </div>

        <?php if ($summary['has_warnings']): ?>
            <div class="card mb-4" data-reveal="up" style="border-left: 4px solid #f59e0b;">
                <h4 class="font-bold text-warning mb-1">Perhatian (Data Parsial):</h4>
                <p class="text-sm mb-0">Terdapat teknisi dengan observasi kurang dari 3 bulan triwulan. Nilai dihitung proporsional dari data yang tersedia. Anda dapat melihat rincian bulan melalui tombol <strong>Detail</strong> atau mode <strong>Tabulasi</strong>.</p>
            </div>
        <?php endif; ?>

        <!-- Evaluation Table -->
        <div class="card" data-reveal="up">
            <h2 class="card-title mb-3">Tabel Preview Penilaian Kinerja Triwulan</h2>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>KODE</th>
                            <th>NAMA TEKNISI</th>
                            <th>C1 (KEDISIPLINAN)</th>
                            <th>C2 (KUALITAS HASIL)</th>
                            <th>C3 (TANGGUNG JAWAB)</th>
                            <th>BULAN TERSEDIA</th>
                            <th>STATUS</th>
                            <th>WARNING / CATATAN</th>
                            <th class="text-right">RINCIAN</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($evals)): ?>
                            <tr>
                                <td colspan="9" class="text-center text-secondary py-3">Belum ada data penilaian pada periode ini. Pastikan Admin telah menjalankan kalkulasi.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($evals as $ev): ?>
                                <tr>
                                    <td class="font-bold text-gold"><?= e($ev['kode_teknisi']) ?></td>
                                    <td class="font-semibold"><?= e($ev['nama_teknisi']) ?></td>
                                    <td>
                                        <span class="font-bold"><?= e(number_format((float) $ev['c1'], 4, '.', '')) ?></span>
                                        <div class="text-xs text-secondary">Hadir • Telat • Jadwal</div>
                                    </td>
                                    <td>
                                        <span class="font-bold"><?= e(number_format((float) $ev['c2'], 4, '.', '')) ?></span>
                                        <div class="text-xs text-secondary">Rapi • Presisi • Desain</div>
                                    </td>
                                    <td>
                                        <span class="font-bold"><?= e(number_format((float) $ev['c3'], 4, '.', '')) ?></span>
                                        <div class="text-xs text-secondary">Alat • Bahan • Prosedur</div>
                                    </td>
                                    <td>
                                        <span class="text-xs">
                                            C1: <?= e((string) ($ev['jumlah_bulan_c1'] ?? 0)) ?>/3 |
                                            C2: <?= e((string) ($ev['jumlah_bulan_c2'] ?? 0)) ?>/3 |
                                            C3: <?= e((string) ($ev['jumlah_bulan_c3'] ?? 0)) ?>/3
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?= $badgeClass((string) ($ev['status_data'] ?? 'draft')) ?>"><?= strtoupper(e((string) ($ev['status_data'] ?? 'draft'))) ?></span>
                                    </td>
                                    <td class="text-sm">
                                        <?php if (!empty($ev['warning'])): ?>
                                            <span class="text-warning text-xs"><?= e($ev['warning']) ?></span>
                                        <?php else: ?>
                                            <span class="text-secondary text-xs">Observasi lengkap</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-right">
                                        <a href="<?= route('/owner/assessment/' . $p['id_periode'] . '/detail/' . $ev['teknisi_id']) ?>" class="btn btn-sm btn-secondary">
                                            Detail
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php elseif ($mode === 'tabulasi'): ?>
        <!-- ============ MODE TABULASI ============ -->
        <div class="card" data-reveal="up">
            <div class="row-between mb-3">
                <div>
                    <h2 class="card-title">Tabulasi Hasil Perhitungan</h2>
                    <p class="text-sm text-secondary mb-0">
                        Periode: <strong><?= e($p['nama_periode']) ?> (<?= e($p['kode_periode']) ?>)</strong> &nbsp;•&nbsp;
                        Sumber data: <strong>tb_penilaian</strong> (hasil Calculation Engine, bukan hardcoded).
                    </p>
                </div>
                <div class="text-sm text-secondary">
                    Bobot: C1 = 30% • C2 = 40% • C3 = 30%
                </div>
            </div>

            <?php if (empty($tabulasi)): ?>
                <div class="p-5 text-center text-secondary">
                    Belum ada data tabulasi pada periode ini. Pastikan Admin telah menjalankan kalkulasi penilaian.
                </div>
            <?php else: ?>
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 50px;">NO</th>
                                <th>TEKNISI</th>
                                <th>C1 (KEDISIPLINAN)</th>
                                <th>C2 (KUALITAS HASIL)</th>
                                <th>C3 (TANGGUNG JAWAB)</th>
                                <th>KONTRIBUSI C1 (30%)</th>
                                <th>KONTRIBUSI C2 (40%)</th>
                                <th>KONTRIBUSI C3 (30%)</th>
                                <th>NILAI SEMENTARA (Vi)</th>
                                <th>STATUS</th>
                                <th>BULAN DATA</th>
                                <th>WARNING</th>
                                <th class="text-right">RINCIAN</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tabulasi as $row): ?>
                                <tr>
                                    <td class="text-center font-bold text-secondary"><?= e((string) $row['no']) ?></td>
                                    <td>
                                        <span class="font-bold text-gold"><?= e($row['kode_teknisi']) ?></span>
                                        <div class="text-xs text-secondary"><?= e($row['nama_teknisi']) ?></div>
                                    </td>
                                    <td><span class="font-bold"><?= e(number_format($row['c1'], 4, '.', '')) ?></span></td>
                                    <td><span class="font-bold"><?= e(number_format($row['c2'], 4, '.', '')) ?></span></td>
                                    <td><span class="font-bold"><?= e(number_format($row['c3'], 4, '.', '')) ?></span></td>
                                    <td class="text-sm"><?= e(number_format($row['kontribusi_c1'], 4, '.', '')) ?></td>
                                    <td class="text-sm"><?= e(number_format($row['kontribusi_c2'], 4, '.', '')) ?></td>
                                    <td class="text-sm"><?= e(number_format($row['kontribusi_c3'], 4, '.', '')) ?></td>
                                    <td><span class="font-bold text-primary"><?= e(number_format($row['vi'], 4, '.', '')) ?></span></td>
                                    <td>
                                        <span class="badge badge-<?= $badgeClass($row['status_data']) ?>"><?= strtoupper(e($row['status_data'])) ?></span>
                                    </td>
                                    <td class="text-xs">
                                        C1: <?= e((string) $row['jumlah_bulan_c1']) ?>/3<br>
                                        C2: <?= e((string) $row['jumlah_bulan_c2']) ?>/3<br>
                                        C3: <?= e((string) $row['jumlah_bulan_c3']) ?>/3
                                    </td>
                                    <td class="text-sm">
                                        <?php if (!empty($row['warning'])): ?>
                                            <span class="text-warning text-xs"><?= e($row['warning']) ?></span>
                                        <?php else: ?>
                                            <span class="text-secondary text-xs">Observasi lengkap</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-right">
                                        <a href="<?= route('/owner/assessment/' . $p['id_periode'] . '/detail/' . $row['teknisi_id']) ?>" class="btn btn-sm btn-secondary">
                                            Detail
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="mt-3 text-xs text-secondary">
                    Nilai Sementara (Vi) = 0.30×C1 + 0.40×C2 + 0.30×C3 (bobot kriteria, sebelum normalisasi SAW).
                    Nilai final SAW dihitung terpisah oleh SawEngineV2 setelah konfirmasi Owner.
                </div>
            <?php endif; ?>
        </div>
    <?php elseif ($mode === 'grafik'): ?>
        <!-- ============ MODE GRAFIK ============ -->
        <?php if (empty($chartIndicator['labels'])): ?>
            <div class="card p-5 text-center text-secondary" data-reveal="up">
                Belum ada data untuk grafik pada periode ini. Pastikan Admin telah menjalankan kalkulasi penilaian.
            </div>
        <?php else: ?>
            <div class="grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(420px, 1fr)); gap: 1rem;">
                <!-- Chart 1: C1/C2/C3 per teknisi -->
                <div class="card" data-reveal="up">
                    <h2 class="card-title mb-1">Perbandingan Indikator C1 / C2 / C3</h2>
                    <p class="text-xs text-secondary mb-3">Nilai kuartal per teknisi — sumber: tb_penilaian.</p>
                    <div style="position: relative; height: 320px;">
                        <canvas id="chartIndicator" aria-label="Grafik C1 C2 C3 per teknisi" role="img"></canvas>
                    </div>
                </div>

                <!-- Chart 2: Vi per teknisi -->
                <div class="card" data-reveal="up">
                    <div class="row-between mb-1">
                        <h2 class="card-title">Nilai Preferensi (Vi) per Teknisi</h2>
                        <?php if ($chartVi['source'] === 'saw'): ?>
                            <span class="badge badge-success">SAW (tb_hasil)</span>
                        <?php else: ?>
                            <span class="badge badge-warning">Pra-SAW (preview)</span>
                        <?php endif; ?>
                    </div>
                    <p class="text-xs text-secondary mb-3">
                        <?php if ($chartVi['source'] === 'saw'): ?>
                            Hasil normalisasi SAW tersimpan di tb_hasil — sumber otoritatif.
                        <?php else: ?>
                            SAW belum dijalankan untuk periode ini. Menampilkan preview tertimbang 0.30/0.40/0.30 dari tb_penilaian.
                        <?php endif; ?>
                    </p>
                    <div style="position: relative; height: 320px;">
                        <canvas id="chartVi" aria-label="Grafik nilai preferensi Vi per teknisi" role="img"></canvas>
                    </div>
                </div>
            </div>

            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
            <script>
                // Semua nilai disediakan backend dari tb_penilaian / tb_hasil.
                // Tidak ada perhitungan ranking atau normalisasi di JavaScript.
                (function () {
                    const palette = {
                        grid: 'rgba(107, 114, 128, 0.15)',
                        text: '#6b7280'
                    };

                    const indData = <?= json_encode($chartIndicator, JSON_THROW_ON_ERROR) ?>;
                    const viData  = <?= json_encode($chartVi, JSON_THROW_ON_ERROR) ?>;

                    const baseOpts = {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { position: 'top' },
                            tooltip: { mode: 'index', intersect: false }
                        },
                        scales: {
                            x: { grid: { color: palette.grid }, ticks: { color: palette.text } },
                            y: {
                                grid: { color: palette.grid },
                                ticks: { color: palette.text },
                                beginAtZero: true,
                                suggestedMax: 4
                            }
                        }
                    };

                    // Chart 1: grouped bar C1/C2/C3
                    const ctxInd = document.getElementById('chartIndicator');
                    if (ctxInd) {
                        new Chart(ctxInd, {
                            type: 'bar',
                            data: {
                                labels: indData.labels,
                                datasets: [
                                    { label: 'C1 Kedisiplinan (30%)', data: indData.c1, backgroundColor: 'rgba(22, 101, 216, 0.75)', borderColor: 'rgba(22, 101, 216, 1)', borderWidth: 1 },
                                    { label: 'C2 Kualitas Kerja (40%)', data: indData.c2, backgroundColor: 'rgba(245, 158, 11, 0.75)', borderColor: 'rgba(245, 158, 11, 1)', borderWidth: 1 },
                                    { label: 'C3 Tanggung Jawab (30%)', data: indData.c3, backgroundColor: 'rgba(16, 185, 129, 0.75)', borderColor: 'rgba(16, 185, 129, 1)', borderWidth: 1 }
                                ]
                            },
                            options: baseOpts
                        });
                    }

                    // Chart 2: Vi bar
                    const ctxVi = document.getElementById('chartVi');
                    if (ctxVi) {
                        new Chart(ctxVi, {
                            type: 'bar',
                            data: {
                                labels: viData.labels,
                                datasets: [
                                    {
                                        label: viData.source === 'saw' ? 'Nilai Preferensi (SAW)' : 'Preview Vi (pra-SAW)',
                                        data: viData.vi,
                                        backgroundColor: viData.source === 'saw' ? 'rgba(22, 101, 216, 0.75)' : 'rgba(245, 158, 11, 0.75)',
                                        borderColor: viData.source === 'saw' ? 'rgba(22, 101, 216, 1)' : 'rgba(245, 158, 11, 1)',
                                        borderWidth: 1
                                    }
                                ]
                            },
                            options: baseOpts
                        });
                    }
                })();
            </script>
        <?php endif; ?>
    <?php endif; ?>
<?php else: ?>
    <div class="card p-5 text-center text-secondary" data-reveal="up">
        Pilih periode penilaian di atas untuk melihat preview penilaian.
    </div>
<?php endif; ?>
