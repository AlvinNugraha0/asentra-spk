<?php
/** @var string $title */
/** @var string $subtitle */
/** @var array<string, mixed> $detail */
/** @var array<string, float> $weights */
/** @var array<int, array<string, mixed>> $kriteria */

$hasSaw = $detail['nilai_preferensi'] !== null;
?>
<div class="page-header" data-reveal="up">
    <div class="flex items-center gap-4" style="flex-wrap: wrap;">
        <a href="<?= route('/owner/penilaian') ?>" class="btn btn-ghost btn-sm" title="Kembali">
            <i class="ph ph-arrow-left text-lg"></i>
        </a>
        <div>
            <h1 class="page-title"><?= e($title) ?></h1>
            <p class="page-subtitle"><?= e($subtitle) ?></p>
        </div>
    </div>
</div>

<!-- Informasi Penilaian -->
<div class="card mb-5" data-reveal="up">
    <div class="section-header">
        <div class="section-header-left">
            <h3><i class="ph ph-user-circle text-lg" style="color: var(--brand);"></i> Informasi Penilaian</h3>
        </div>
        <a href="<?= route('/owner/penilaian/edit/' . $detail['id']) ?>" class="btn btn-ghost btn-sm">
            <i class="ph ph-pencil-simple text-base"></i> Edit
        </a>
    </div>
    <div class="preview-grid">
        <div class="preview-item">
            <span class="preview-label">Teknisi</span>
            <span class="preview-value"><?= e($detail['kode_teknisi'] . ' — ' . $detail['nama_teknisi']) ?></span>
        </div>
        <div class="preview-item">
            <span class="preview-label">Periode</span>
            <span class="preview-value"><?= e(periodLabel($detail['periode'])) ?></span>
        </div>
        <div class="preview-item">
            <span class="preview-label">Dinilai Oleh</span>
            <span class="preview-value"><?= e($detail['nama_user'] ?? '-') ?></span>
        </div>
        <div class="preview-item">
            <span class="preview-label">Tanggal Penilaian</span>
            <span class="preview-value"><?= e(dateFormat($detail['created_at'], 'd M Y H:i')) ?></span>
        </div>
    </div>
</div>

<!-- Nilai Kriteria -->
<div class="grid-3 mb-5" data-reveal="up">
    <?php
    $kriteriaDisplay = [
        'c1' => ['kode' => 'C1', 'nama' => 'Kedisiplinan'],
        'c2' => ['kode' => 'C2', 'nama' => 'Kualitas Hasil Kerja'],
        'c3' => ['kode' => 'C3', 'nama' => 'Tanggung Jawab'],
    ];
    ?>
    <?php foreach ($kriteriaDisplay as $key => $k): ?>
        <?php
        $val = (int) $detail[$key];
        $bobot = $weights[$k['kode']] ?? 0;
        ?>
        <div class="card">
            <div class="row-between mb-2">
                <span class="font-bold text-gold"><?= e($k['kode']) ?></span>
                <span class="badge badge-gold"><?= e(weightPercent($bobot)) ?></span>
            </div>
            <h3 class="criteria-title"><?= e($k['nama']) ?></h3>
            <div class="meta-text mb-3">Benefit</div>
            <div style="display: flex; align-items: baseline; gap: var(--space-2);">
                <span class="tabular font-bold" style="font-size: 2rem; color: var(--brand);"><?= e((string) $val) ?></span>
                <span class="text-muted"><?= e(ratingLabel($val)) ?></span>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php if ($hasSaw): ?>
<!-- Hasil Perhitungan SAW -->
<div class="card mb-5" data-reveal="up">
    <div class="section-header">
        <div class="section-header-left">
            <h3><i class="ph ph-calculator text-lg" style="color: var(--brand);"></i> Hasil Perhitungan SAW</h3>
            <p>Detail normalisasi dan perhitungan nilai preferensi.</p>
        </div>
    </div>

    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>Kriteria</th>
                    <th>Nilai Asli</th>
                    <th>Normalisasi (rij)</th>
                    <th>Bobot (Wj)</th>
                    <th>Kontribusi (Wj × rij)</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $normalFields = [
                    'c1' => ['kode' => 'C1', 'nama' => 'Kedisiplinan', 'norm' => 'nilai_c1_normalisasi', 'kontribusi' => 'kontribusi_c1'],
                    'c2' => ['kode' => 'C2', 'nama' => 'Kualitas Hasil Kerja', 'norm' => 'nilai_c2_normalisasi', 'kontribusi' => 'kontribusi_c2'],
                    'c3' => ['kode' => 'C3', 'nama' => 'Tanggung Jawab', 'norm' => 'nilai_c3_normalisasi', 'kontribusi' => 'kontribusi_c3'],
                ];
                foreach ($normalFields as $key => $f):
                    $nilaiAsli = (int) $detail[$key];
                    $normal = (float) ($detail[$f['norm']] ?? 0);
                    $bobot = $weights[$f['kode']] ?? 0;
                    $kontribusi = (float) ($detail[$f['kontribusi']] ?? 0);
                ?>
                    <tr>
                        <td><strong><?= e($f['kode']) ?></strong> <span class="text-muted"><?= e($f['nama']) ?></span></td>
                        <td class="tabular"><?= e((string) $nilaiAsli) ?></td>
                        <td class="tabular"><?= e(scoreFormat($normal, 6)) ?></td>
                        <td class="tabular"><?= e(scoreFormat($bobot, 2)) ?></td>
                        <td class="tabular font-bold"><?= e(scoreFormat($kontribusi, 6)) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" class="text-right"><strong>Nilai Preferensi (Vi)</strong></td>
                    <td class="tabular font-bold" style="color: var(--brand); font-size: var(--text-lg);">
                        <?= e(scoreFormat((float) $detail['nilai_preferensi'], 3)) ?>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<!-- Ranking -->
<div class="card" data-reveal="up">
    <div class="section-header">
        <div class="section-header-left">
            <h3><i class="ph ph-trophy text-lg" style="color: var(--brand);"></i> Peringkat</h3>
        </div>
    </div>
    <div style="display: flex; align-items: center; gap: var(--space-4);">
        <?= rankBadge((int) $detail['ranking']) ?>
        <div>
            <div class="font-bold" style="font-size: var(--text-lg);">Peringkat #<?= e((string) $detail['ranking']) ?></div>
            <div class="text-muted">dari seluruh teknisi yang dinilai pada periode <?= e(periodLabel($detail['periode'])) ?></div>
        </div>
        <div style="margin-left: auto; text-align: right;">
            <div class="tabular font-bold" style="font-size: 1.5rem; color: var(--brand);">
                <?= e(scoreFormat((float) $detail['nilai_preferensi'], 3)) ?>
            </div>
            <div class="text-muted" style="font-size: var(--text-sm);">Skor SAW</div>
        </div>
    </div>
</div>
<?php else: ?>
<div class="card" data-reveal="up">
    <div class="empty-state">
        <div class="empty-state-icon">
            <i class="ph ph-calculator text-3xl"></i>
        </div>
        <div class="empty-state-title">Belum Ada Hasil SAW</div>
        <p>Perhitungan SAW belum diproses untuk periode ini. Hasil akan muncul otomatis setelah proses SAW dijalankan.</p>
        <a href="<?= route('/owner/ranking') ?>" class="btn btn-primary mt-4">Proses SAW</a>
    </div>
</div>
<?php endif; ?>

<style>
.preview-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: var(--space-4);
}
.preview-item {
    display: flex;
    flex-direction: column;
    gap: var(--space-1);
}
.preview-label {
    font-size: var(--text-sm);
    color: var(--text-muted);
    font-weight: 500;
}
.preview-value {
    font-size: var(--text-base);
    font-weight: 600;
    color: var(--text-primary);
}
@media (max-width: 768px) {
    .grid-3 { grid-template-columns: 1fr !important; }
}
</style>
