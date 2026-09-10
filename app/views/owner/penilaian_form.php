<?php
/** @var string $title */
/** @var string $subtitle */
/** @var ?array<string, mixed> $penilaian */
/** @var array<int, array<string, mixed>> $teknisi */
/** @var array<int, string> $periods */
/** @var array<int, array<string, mixed>> $kriteria */
/** @var array<string, string> $errors */
/** @var array<string, mixed> $old */

$isEdit = $penilaian !== null;
$id = $penilaian['id'] ?? ($old['id'] ?? '');
$selectedTeknisi = $penilaian['teknisi_id'] ?? ($old['teknisi_id'] ?? ($_GET['teknisi_id'] ?? ''));
$selectedPeriode = $penilaian['periode'] ?? ($old['periode'] ?? ($_GET['periode'] ?? date('Y-m')));
$c1 = $penilaian['c1'] ?? ($old['c1'] ?? '');
$c2 = $penilaian['c2'] ?? ($old['c2'] ?? '');
$c3 = $penilaian['c3'] ?? ($old['c3'] ?? '');

// Kriteria metadata with descriptions from PRD
$kriteriaInfo = [
    'c1' => [
        'kode' => 'C1',
        'nama' => 'Kedisiplinan',
        'bobot' => '30%',
        'deskripsi' => 'Kepatuhan jam hadir di lokasi proyek, ketepatan waktu penyelesaian target harian, dan kepatuhan terhadap jadwal operasional.',
    ],
    'c2' => [
        'kode' => 'C2',
        'nama' => 'Kualitas Hasil Kerja',
        'bobot' => '40%',
        'deskripsi' => 'Kerapian pengerjaan fisik, presisi pengukuran/pemasangan, kekuatan struktural, dan kesesuaian hasil pengerjaan dengan desain.',
    ],
    'c3' => [
        'kode' => 'C3',
        'nama' => 'Tanggung Jawab',
        'bobot' => '30%',
        'deskripsi' => 'Pemeliharaan alat kerja perusahaan, efisiensi penggunaan material proyek, dan inisiatif di lapangan.',
    ],
];

// Override bobot from database kriteria if available
foreach ($kriteria as $k) {
    $key = strtolower($k['kode']);
    if (isset($kriteriaInfo[$key])) {
        $kriteriaInfo[$key]['bobot'] = weightPercent((float) $k['bobot']);
        if (!empty($k['deskripsi'])) {
            $kriteriaInfo[$key]['deskripsi'] = $k['deskripsi'];
        }
    }
}

$ratingOptions = [
    4 => ['label' => 'Sangat Baik', 'desc' => 'Performa sangat memuaskan'],
    3 => ['label' => 'Baik', 'desc' => 'Performa memenuhi standar'],
    2 => ['label' => 'Cukup', 'desc' => 'Performa perlu peningkatan'],
    1 => ['label' => 'Kurang', 'desc' => 'Performa di bawah standar'],
];

// Find selected technician name for preview
$selectedTeknisiNama = '';
foreach ($teknisi as $t) {
    if ((string) $t['id'] === (string) $selectedTeknisi) {
        $selectedTeknisiNama = $t['kode_teknisi'] . ' — ' . $t['nama'];
        break;
    }
}
?>
<div class="page-header" data-reveal="up">
    <h1 class="page-title"><?= e($title) ?></h1>
    <p class="page-subtitle"><?= e($subtitle) ?></p>
</div>

<form method="POST" action="<?= route($isEdit ? '/owner/penilaian/update' : '/owner/penilaian/store') ?>" id="penilaianForm" data-reveal="up">
    <?= csrfField() ?>
    <?php if ($isEdit): ?>
        <input type="hidden" name="id" value="<?= e((string) $id) ?>">
    <?php endif; ?>

    <!-- Teknisi & Periode -->
    <div class="card mb-5">
        <div class="section-header">
            <div class="section-header-left">
                <h3><i class="ph ph-user-circle text-lg" style="color: var(--brand);"></i> Data Penilaian</h3>
            </div>
        </div>
        <div class="grid-2">
            <div class="form-group mb-0">
                <label class="label" for="teknisi_id">Teknisi <span style="color: var(--error);">*</span></label>
                <select id="teknisi_id" name="teknisi_id" class="select" required>
                    <option value="">Pilih teknisi</option>
                    <?php foreach ($teknisi as $t): ?>
                        <option value="<?= e((string) $t['id']) ?>" <?= (string) $selectedTeknisi === (string) $t['id'] ? 'selected' : '' ?>
                            data-nama="<?= e($t['kode_teknisi'] . ' — ' . $t['nama']) ?>">
                            <?= e($t['kode_teknisi']) ?> &mdash; <?= e($t['nama']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (!empty($errors['teknisi_id'])): ?>
                    <div class="form-error"><?= e($errors['teknisi_id']) ?></div>
                <?php endif; ?>
            </div>
            <div class="form-group mb-0">
                <label class="label" for="periode">Periode <span style="color: var(--error);">*</span></label>
                <input type="month" id="periode" name="periode" class="input" value="<?= e($selectedPeriode) ?>" required>
                <?php if (!empty($errors['periode'])): ?>
                    <div class="form-error"><?= e($errors['periode']) ?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Kriteria Rating Cards -->
    <div class="grid-3 mb-5">
        <?php foreach ($kriteriaInfo as $key => $k): ?>
            <?php $val = $$key; ?>
            <div class="card">
                <div class="mb-4">
                    <div class="row-between">
                        <span class="font-bold text-gold"><?= e($k['kode']) ?></span>
                        <span class="badge badge-gold"><?= e($k['bobot']) ?></span>
                    </div>
                    <h3 class="criteria-title"><?= e($k['nama']) ?></h3>
                    <div class="meta-text" style="margin-top: var(--space-1);"><?= e($k['deskripsi']) ?></div>
                    <div class="meta-text" style="margin-top: var(--space-1);">Benefit</div>
                </div>

                <div class="stack gap-2">
                    <?php foreach ($ratingOptions as $rating => $rInfo): ?>
                        <label class="rating-option <?= (string) $val === (string) $rating ? 'selected' : '' ?>">
                            <input type="radio" name="<?= e($key) ?>" value="<?= e((string) $rating) ?>"
                                <?= (string) $val === (string) $rating ? 'checked' : '' ?> required
                                data-kriteria="<?= e($key) ?>">
                            <span class="font-semibold"><?= e((string) $rating) ?></span>
                            <span class="text-secondary"><?= e($rInfo['label']) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <?php if (!empty($errors[$key])): ?>
                    <div class="form-error mt-3"><?= e($errors[$key]) ?></div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Preview Summary -->
    <div class="card mb-5" id="previewCard" style="display: none;">
        <div class="section-header">
            <div class="section-header-left">
                <h3><i class="ph ph-clipboard-text text-lg" style="color: var(--brand);"></i> Ringkasan Penilaian</h3>
                <p>Periksa data sebelum menyimpan.</p>
            </div>
        </div>
        <div class="preview-grid">
            <div class="preview-item">
                <span class="preview-label">Teknisi</span>
                <span class="preview-value" id="prevTeknisi">-</span>
            </div>
            <div class="preview-item">
                <span class="preview-label">Periode</span>
                <span class="preview-value" id="prevPeriode">-</span>
            </div>
            <div class="preview-item">
                <span class="preview-label">C1 · Kedisiplinan</span>
                <span class="preview-value" id="prevC1">-</span>
            </div>
            <div class="preview-item">
                <span class="preview-label">C2 · Kualitas Hasil Kerja</span>
                <span class="preview-value" id="prevC2">-</span>
            </div>
            <div class="preview-item">
                <span class="preview-label">C3 · Tanggung Jawab</span>
                <span class="preview-value" id="prevC3">-</span>
            </div>
        </div>
    </div>

    <div class="row-end" style="gap: var(--space-3);">
        <a href="<?= route('/owner/penilaian') ?>" class="btn btn-secondary">Batal</a>
        <button type="submit" class="btn btn-primary" id="submitBtn">
            <i class="ph ph-floppy-disk text-lg"></i>
            <?= $isEdit ? 'Simpan Perubahan' : 'Simpan Penilaian' ?>
        </button>
    </div>
</form>

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
    .grid-2 { grid-template-columns: 1fr !important; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var form = document.getElementById('penilaianForm');
    var previewCard = document.getElementById('previewCard');
    var teknisiSelect = document.getElementById('teknisi_id');
    var periodeInput = document.getElementById('periode');
    var ratingLabels = {1: 'Kurang', 2: 'Cukup', 3: 'Baik', 4: 'Sangat Baik'};
    var months = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];

    function formatPeriode(val) {
        if (!val) return '-';
        var parts = val.split('-');
        if (parts.length !== 2) return val;
        var m = parseInt(parts[1], 10);
        return (months[m - 1] || parts[1]) + ' ' + parts[0];
    }

    function updatePreview() {
        var teknisiOpt = teknisiSelect.options[teknisiSelect.selectedIndex];
        var teknisiName = teknisiOpt && teknisiOpt.value ? (teknisiOpt.getAttribute('data-nama') || teknisiOpt.textContent.trim()) : '-';
        var periodeVal = periodeInput.value;

        var c1 = document.querySelector('input[name="c1"]:checked');
        var c2 = document.querySelector('input[name="c2"]:checked');
        var c3 = document.querySelector('input[name="c3"]:checked');

        document.getElementById('prevTeknisi').textContent = teknisiName;
        document.getElementById('prevPeriode').textContent = formatPeriode(periodeVal);
        document.getElementById('prevC1').textContent = c1 ? c1.value + ' — ' + ratingLabels[parseInt(c1.value)] : '-';
        document.getElementById('prevC2').textContent = c2 ? c2.value + ' — ' + ratingLabels[parseInt(c2.value)] : '-';
        document.getElementById('prevC3').textContent = c3 ? c3.value + ' — ' + ratingLabels[parseInt(c3.value)] : '-';

        // Show preview if at least one field is filled
        var hasData = teknisiOpt && teknisiOpt.value || c1 || c2 || c3;
        previewCard.style.display = hasData ? 'block' : 'none';
    }

    teknisiSelect.addEventListener('change', updatePreview);
    periodeInput.addEventListener('change', updatePreview);
    document.querySelectorAll('input[type="radio"]').forEach(function(radio) {
        radio.addEventListener('change', updatePreview);
    });

    // Initial preview
    updatePreview();
});
</script>
