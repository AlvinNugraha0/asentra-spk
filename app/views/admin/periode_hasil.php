<?php
/** @var string $title */
/** @var string $subtitle */
/** @var array<string, mixed> $summary */
$p = $summary['periode'];
$evals = $summary['evaluations'];
?>
<div class="page-header" data-reveal="up">
    <div class="row-between">
        <div>
            <h1 class="page-title"><?= e($title) ?></h1>
            <p class="page-subtitle"><?= e($subtitle) ?></p>
        </div>
        <div class="actions">
            <a href="<?= route('/admin/periode') ?>" class="btn btn-secondary">← Kembali ke Periode</a>
            <?php if (!$summary['is_confirmed'] && ($p['status'] ?? '') !== 'legacy'): ?>
                <form method="POST" action="<?= route('/admin/periode/kalkulasi/' . $p['id_periode']) ?>" style="display: inline-block; margin: 0;">
                    <?= csrfField() ?>
                    <button type="submit" class="btn btn-primary" onclick="return confirm('Kalkulasi ulang penilaian periode ini?')">
                        Hitung Ulang V2
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="grid grid-4 mb-4" data-reveal="up" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem;">
    <div class="card p-3">
        <div class="text-secondary text-sm">Total Penilaian</div>
        <div class="font-bold weight-big text-gold"><?= e((string) $summary['total_evaluations']) ?></div>
    </div>
    <div class="card p-3">
        <div class="text-secondary text-sm">Status Lengkap (3 Bulan)</div>
        <div class="font-bold weight-big text-success"><?= e((string) $summary['calculated_count']) ?></div>
    </div>
    <div class="card p-3">
        <div class="text-secondary text-sm">Status Parsial (&lt; 3 Bulan)</div>
        <div class="font-bold weight-big text-warning"><?= e((string) $summary['partial_count']) ?></div>
    </div>
    <div class="card p-3">
        <div class="text-secondary text-sm">Status Periode</div>
        <div class="font-bold weight-big text-primary" style="text-transform: uppercase;">
            <?= e((string) ($p['status'] ?? 'draft')) ?>
        </div>
    </div>
</div>

<?php if ($summary['has_warnings']): ?>
    <div class="card mb-4" data-reveal="up" style="border-left: 4px solid #f59e0b;">
        <h4 class="font-bold text-warning mb-1">Catatan Peringatan Data Parsial:</h4>
        <p class="text-sm mb-0">Beberapa teknisi belum memiliki observasi penuh 3 bulan. Nilai dihitung dari rata-rata bulan yang tersedia (agregasi parsial).</p>
    </div>
<?php endif; ?>

<div class="card" data-reveal="up">
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>KODE</th>
                    <th>NAMA TEKNISI</th>
                    <th>C1 (KEDISIPLINAN)</th>
                    <th>C2 (KUALITAS)</th>
                    <th>C3 (TANGGUNG JAWAB)</th>
                    <th>BULAN TERSEDIA</th>
                    <th>STATUS DATA</th>
                    <th>CATATAN / WARNING</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($evals)): ?>
                    <tr>
                        <td colspan="8" class="text-center text-secondary py-3">Belum ada hasil kalkulasi untuk periode ini.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($evals as $ev): ?>
                        <tr>
                            <td class="font-bold text-gold"><?= e($ev['kode_teknisi']) ?></td>
                            <td class="font-semibold"><?= e($ev['nama_teknisi']) ?></td>
                            <td>
                                <strong><?= e(number_format((float) $ev['c1'], 4, '.', '')) ?></strong>
                            </td>
                            <td>
                                <strong><?= e(number_format((float) $ev['c2'], 4, '.', '')) ?></strong>
                            </td>
                            <td>
                                <strong><?= e(number_format((float) $ev['c3'], 4, '.', '')) ?></strong>
                            </td>
                            <td>
                                <span class="text-xs">
                                    C1: <?= e((string) ($ev['jumlah_bulan_c1'] ?? 0)) ?> bln |
                                    C2: <?= e((string) ($ev['jumlah_bulan_c2'] ?? 0)) ?> bln |
                                    C3: <?= e((string) ($ev['jumlah_bulan_c3'] ?? 0)) ?> bln
                                </span>
                            </td>
                            <td>
                                <?php
                                $sd = $ev['status_data'] ?? 'draft';
                                $badgeClass = match ($sd) {
                                    'confirmed' => 'success',
                                    'calculated' => 'primary',
                                    'partial' => 'warning',
                                    default => 'neutral',
                                };
                                ?>
                                <span class="badge badge-<?= $badgeClass ?>"><?= strtoupper(e($sd)) ?></span>
                            </td>
                            <td class="text-sm">
                                <?php if (!empty($ev['warning'])): ?>
                                    <span class="text-warning"><?= e($ev['warning']) ?></span>
                                <?php else: ?>
                                    <span class="text-secondary">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
