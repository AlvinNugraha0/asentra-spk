<?php
/** @var string $title */
/** @var string $subtitle */
/** @var array<int, array<string, mixed>> $periods */
/** @var array<int, array<string, mixed>> $allPeriods */
/** @var array<int, array<string, mixed>> $recentImports */
/** @var array<int, string> $errors */
/** @var array<string, mixed>|null $importResult */
/** @var array<string, mixed>|null $detectedPeriode */
/** @var array<string, string> $form_errors */
/** @var array<string, string> $form_old */
?>
<style>
    /* ASENTRA black-gold upload zone (scoped to this page) */
    .upload-zone {
        position: relative;
        display: block;
        border: 2px dashed var(--border);
        border-radius: var(--radius-lg);
        background: var(--bg-elevated);
        padding: 1.75rem 1.25rem;
        text-align: center;
        cursor: pointer;
        transition: border-color var(--motion-normal) var(--ease-out),
                    background var(--motion-normal) var(--ease-out),
                    box-shadow var(--motion-normal) var(--ease-out);
        max-width: 560px;
    }
    .upload-zone:hover,
    .upload-zone.dragover {
        border-color: var(--gold);
        background: var(--gold-soft);
        box-shadow: 0 0 0 4px var(--gold-soft);
    }
    .upload-zone.has-file { border-style: solid; border-color: var(--gold); }
    .upload-zone input[type="file"] { position: absolute; opacity: 0; width: 1px; height: 1px; }
    .upload-zone-icon { font-size: 1.9rem; line-height: 1; color: var(--gold); }
    .upload-zone-text { margin-top: .5rem; font-weight: var(--font-semibold); color: var(--text-primary); }
    .upload-zone-hint { margin-top: .25rem; font-size: var(--text-sm); color: var(--text-secondary); }
    .upload-zone-name {
        margin-top: .65rem; display: inline-flex; align-items: center; gap: .4rem;
        font-family: var(--font-mono); font-size: var(--text-sm); color: var(--gold);
        background: var(--gold-soft); border: 1px solid rgba(214, 178, 76, .35);
        padding: .3rem .65rem; border-radius: var(--radius-full); max-width: 100%;
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
    }
    .upload-zone-name:empty { display: none; }

    /* Detected-periode panel */
    .periode-panel { border: 1px solid rgba(214, 178, 76, .35); border-left: 4px solid var(--gold); }
    .periode-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: .85rem 1.25rem; }
    .periode-fact dt { font-size: var(--text-xs); letter-spacing: .06em; text-transform: uppercase; color: var(--text-secondary); }
    .periode-fact dd { margin: .15rem 0 0; font-weight: var(--font-semibold); color: var(--text-primary); }
    .periode-fact dd.text-gold { color: var(--gold); }

    /* Result card states */
    .result-card { border-left: 4px solid var(--danger); }
    .result-card.result-success { border-left-color: var(--success); }
    .result-card.result-partial { border-left-color: var(--warning); }
    .result-card.result-failed  { border-left-color: var(--danger); }
    .result-nums { display: flex; flex-wrap: wrap; gap: 1.25rem; }
    .result-num { display: flex; align-items: baseline; gap: .35rem; }
    .result-num b { font-size: 1.15rem; }
</style>

<div class="page-header" data-reveal="up">
    <h1 class="page-title"><?= e($title) ?></h1>
    <p class="page-subtitle"><?= e($subtitle) ?></p>
</div>

<?php
// Unified "periode yang dipakai" panel: auto-detected (6K) or explicitly chosen
// (backward-compat path) — both set $_SESSION['detected_periode'] in the controller.
$dp = is_array($detectedPeriode ?? null) ? $detectedPeriode : null;
$dpStatus = is_array($dp) ? (string) ($dp['status'] ?? 'draft') : 'draft';
$dpBadge = match ($dpStatus) {
    'selesai' => 'success',
    'proses' => 'warning',
    default => 'neutral',
};
$dpBadgeText = match ($dpStatus) {
    'selesai' => 'SELESAI',
    'proses' => 'PROSES',
    default => 'DRAFT',
};
?>

<?php if (is_array($dp)): ?>
    <div class="card mb-4 periode-panel" data-reveal="up" id="detected-periode">
        <div class="flex items-center justify-between mb-3" style="flex-wrap: wrap; gap: .5rem;">
            <h3 class="font-bold" style="margin: 0;">Periode Terdeteksi</h3>
            <?php if (!empty($dp['created'])): ?>
                <span class="badge badge-success">PERIODE BARU DIBUAT</span>
            <?php elseif (($dp['quarter'] ?? 0) !== 0): ?>
                <span class="badge badge-<?= $dpBadge ?>"><?= $dpBadgeText ?></span>
            <?php else: ?>
                <span class="badge badge-neutral">PERIODE DIPILIH MANUAL</span>
            <?php endif; ?>
        </div>
        <dl class="periode-grid" style="margin: 0;">
            <div class="periode-fact">
                <dt>Kode Periode</dt>
                <dd class="text-gold"><?= e((string) ($dp['kode_periode'] ?? '-')) ?></dd>
            </div>
            <div class="periode-fact">
                <dt>Nama Periode</dt>
                <dd><?= e((string) ($dp['nama_periode'] ?? '-')) ?></dd>
            </div>
            <div class="periode-fact">
                <dt>Rentang Tanggal</dt>
                <dd><?= e((string) ($dp['tanggal_mulai'] ?? '-')) ?> &rarr; <?= e((string) ($dp['tanggal_selesai'] ?? '-')) ?></dd>
            </div>
            <?php if (($dp['quarter'] ?? 0) !== 0): ?>
                <div class="periode-fact">
                    <dt>Sumber Deteksi</dt>
                    <dd>Triwulan <?= e((string) $dp['quarter']) ?> dibaca dari data Excel</dd>
                </div>
            <?php endif; ?>
            <?php if (!empty($dp['nama_file_asli'])): ?>
                <div class="periode-fact">
                    <dt>File</dt>
                    <dd><?= e((string) $dp['nama_file_asli']) ?></dd>
                </div>
            <?php endif; ?>
        </dl>
    </div>
<?php endif; ?>

<?php if (!empty($importResult)): ?>
    <?php
    $st = (string) ($importResult['status'] ?? 'failed');
    $stClass = match ($st) {
        'success' => 'result-success',
        'partial' => 'result-partial',
        default => 'result-failed',
    };
    $stBadge = match ($st) {
        'success' => 'success',
        'partial' => 'warning',
        default => 'danger',
    };
    $stLabel = match ($st) {
        'success' => 'BERHASIL — semua data tersimpan',
        'partial' => 'SEBAGIAN — sebagian data dilewati',
        default => 'GAGAL — tidak ada data tersimpan',
    };
    ?>
    <div class="card mb-4 result-card <?= $stClass ?>" data-reveal="up" id="import-result">
        <div class="flex items-center justify-between mb-2" style="flex-wrap: wrap; gap: .5rem;">
            <h3 class="font-bold" style="margin: 0;">Hasil Import</h3>
            <span class="badge badge-<?= $stBadge ?>"><?= e($stLabel) ?></span>
        </div>
        <p class="mb-3 text-secondary"><?= e((string) ($importResult['message'] ?? '')) ?></p>
        <div class="result-nums">
            <span class="result-num"><span class="text-secondary text-sm">Total Baris</span> <b><?= e((string) ($importResult['total_data'] ?? 0)) ?></b></span>
            <span class="result-num"><span class="text-success text-sm">Berhasil</span> <b class="text-success"><?= e((string) ($importResult['data_berhasil'] ?? 0)) ?></b></span>
            <span class="result-num"><span class="text-danger text-sm">Gagal / Dilewati</span> <b class="text-danger"><?= e((string) ($importResult['data_gagal'] ?? 0)) ?></b></span>
        </div>
        <?php if (!empty($errors)): ?>
            <div class="mt-3 p-3 rounded" style="max-height: 220px; overflow-y: auto; font-size: var(--text-sm); background: var(--danger-soft); border: 1px solid var(--border);">
                <strong>Catatan / Error per baris:</strong>
                <ul class="mt-1" style="margin: .25rem 0 0; padding-left: 1.1rem;">
                    <?php foreach ($errors as $err): ?>
                        <li class="text-danger mb-1"><?= e($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<div class="card mb-4" data-reveal="up">
    <h2 class="card-title mb-1">Upload File Excel (.xlsx)</h2>
    <p class="text-secondary text-sm mb-3">
        Sistem membaca tanggal &amp; bulan pada data, mendeteksi kuartal secara otomatis, lalu membuat atau
        menggunakan periode yang sudah ada. Periode baru dibuat dengan status <strong>Draft</strong>.
    </p>
    <form method="POST" action="<?= route('/admin/import') ?>" enctype="multipart/form-data" id="import-form">
        <?= csrfField() ?>

        <div class="form-group mb-3">
            <label for="file_excel" class="form-label font-semibold">File Excel (.xlsx)</label>
            <label class="upload-zone" for="file_excel" id="upload-zone">
                <input type="file" name="file_excel" id="file_excel" accept=".xlsx" required aria-describedby="file_excel_hint">
                <div class="upload-zone-icon">&#8682;</div>
                <div class="upload-zone-text" id="upload-zone-text">Klik atau seret file .xlsx ke sini</div>
                <div class="upload-zone-hint" id="file_excel_hint">5 sheet: DATA_TEKNISI, KEDISIPLINAN, KUALITAS_KERJA, TANGGUNG_JAWAB, PETUNJUK. Satu file = satu triwulan.</div>
                <div class="upload-zone-name" id="file_excel_name"></div>
            </label>
            <small class="text-secondary d-block mt-1">Validasi: ekstensi harus <strong>.xlsx</strong>, ukuran maksimal 20&nbsp;MB. Tidak ada baris yang diproses sebelum Anda menekan tombol import.</small>
        </div>

        <div class="form-group mb-4">
            <label class="font-normal" style="display: flex; align-items: flex-start; gap: 8px; cursor: pointer;">
                <input type="checkbox" name="allow_partial" value="1" id="allow_partial">
                <span>
                    <strong>Izinkan Partial Import</strong> (opsional, non-aktif secara default)<br>
                    <small class="text-secondary">Centang hanya jika Anda menerima risiko: baris yang valid disimpan, baris yang error <strong>dilewati tanpa peringatan</strong>. Tanpa centang, seluruh import dibatalkan bila ada satu baris error.</small>
                </span>
            </label>
        </div>

        <button type="submit" class="btn btn-primary" id="import-submit">Mulai Import Data</button>
    </form>

    <details class="mt-5" style="border-top: 1px dashed var(--border); padding-top: 1rem;">
        <summary style="cursor: pointer; font-weight: 600; color: var(--text-secondary);">Opsi lanjutan / input periode manual</summary>
        <p class="text-secondary text-sm mt-2 mb-3">
            Pilih periode yang sudah ada, atau buat periode baru secara manual jika file Excel tidak sesuai
            satu kuartal kalender. Jalur ini tetap didukung untuk kompatibilitas.
        </p>

        <form method="POST" action="<?= route('/admin/import') ?>" enctype="multipart/form-data" class="mb-4">
            <?= csrfField() ?>
            <div class="form-group mb-3">
                <label for="id_periode" class="form-label font-semibold">Periode Penilaian (Kuartal)</label>
                <select name="id_periode" id="id_periode" class="input" style="width: 100%; max-width: 400px;">
                    <option value="">-- Pilih Periode --</option>
                    <?php foreach ($periods as $p): ?>
                        <option value="<?= e((string) $p['id_periode']) ?>">
                            <?= e($p['kode_periode']) ?> — <?= e($p['nama_periode']) ?> (<?= e($p['tanggal_mulai']) ?> s/d <?= e($p['tanggal_selesai']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
                <small class="text-secondary d-block mt-1">Hanya periode berstatus draft / proses yang dapat diimport. Periode legacy dilindungi. Periode berstatus SELESAI akan ditolak.</small>
            </div>
            <div class="form-group mb-3">
                <label for="file_excel_explicit" class="form-label font-semibold">File Excel (.xlsx)</label>
                <label class="upload-zone" for="file_excel_explicit" id="upload-zone-explicit">
                    <input type="file" name="file_excel" id="file_excel_explicit" accept=".xlsx" required>
                    <div class="upload-zone-icon">&#8682;</div>
                    <div class="upload-zone-text">Klik atau seret file .xlsx ke sini</div>
                    <div class="upload-zone-hint">File akan diimport ke periode yang dipilih di atas.</div>
                    <div class="upload-zone-name" id="file_excel_explicit_name"></div>
                </label>
            </div>
            <button type="submit" class="btn btn-secondary">Import ke Periode Terpilih</button>
        </form>

        <h3 class="font-bold mb-2" style="font-size: 0.95rem;">
            Buat Periode Manual
            <span class="text-secondary font-normal" style="font-size: 0.8rem; font-weight: 400;">
                (jalur sekunder — biasanya periode dibuat otomatis saat import)
            </span>
        </h3>
        <?php if (!empty($form_errors['general'])): ?>
            <div class="text-danger mb-2" style="font-size: 0.85rem;"><?= e($form_errors['general']) ?></div>
        <?php endif; ?>
        <form method="POST" action="<?= route('/admin/import/periode') ?>" class="mb-3">
            <?= csrfField() ?>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 0.75rem; align-items: end;">
                <div class="form-group">
                    <label for="kode_periode" class="form-label text-sm">Kode Periode</label>
                    <input type="text" name="kode_periode" id="kode_periode" class="input" placeholder="2026-Q4"
                           value="<?= e((string) ($form_old['kode_periode'] ?? '')) ?>" required>
                    <?php if (!empty($form_errors['kode_periode'])): ?>
                        <small class="text-danger d-block"><?= e($form_errors['kode_periode']) ?></small>
                    <?php endif; ?>
                </div>
                <div class="form-group">
                    <label for="nama_periode" class="form-label text-sm">Nama Periode</label>
                    <input type="text" name="nama_periode" id="nama_periode" class="input" placeholder="Oktober - Desember 2026"
                           value="<?= e((string) ($form_old['nama_periode'] ?? '')) ?>" required>
                    <?php if (!empty($form_errors['nama_periode'])): ?>
                        <small class="text-danger d-block"><?= e($form_errors['nama_periode']) ?></small>
                    <?php endif; ?>
                </div>
                <div class="form-group">
                    <label for="tanggal_mulai" class="form-label text-sm">Tanggal Mulai</label>
                    <input type="date" name="tanggal_mulai" id="tanggal_mulai" class="input"
                           value="<?= e((string) ($form_old['tanggal_mulai'] ?? '')) ?>" required>
                    <?php if (!empty($form_errors['tanggal_mulai'])): ?>
                        <small class="text-danger d-block"><?= e($form_errors['tanggal_mulai']) ?></small>
                    <?php endif; ?>
                </div>
                <div class="form-group">
                    <label for="tanggal_selesai" class="form-label text-sm">Tanggal Selesai</label>
                    <input type="date" name="tanggal_selesai" id="tanggal_selesai" class="input"
                           value="<?= e((string) ($form_old['tanggal_selesai'] ?? '')) ?>" required>
                    <?php if (!empty($form_errors['tanggal_selesai'])): ?>
                        <small class="text-danger d-block"><?= e($form_errors['tanggal_selesai']) ?></small>
                    <?php endif; ?>
                </div>
            </div>
            <button type="submit" class="btn btn-secondary mt-2">Buat Periode</button>
        </form>

    </details>
</div>

<?= viewPartial('admin.periode_table', ['periods' => $allPeriods]) ?>

<div class="card" data-reveal="up">
    <h2 class="card-title mb-3">Riwayat Import Terbaru</h2>
    <div class="table-wrap">
        <table class="table">
            <thead>
                <tr>
                    <th>WAKTU</th>
                    <th>PERIODE</th>
                    <th>NAMA FILE ASLI</th>
                    <th>TOTAL</th>
                    <th>BERHASIL</th>
                    <th>GAGAL</th>
                    <th>STATUS</th>
                    <th>USER</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recentImports)): ?>
                    <tr>
                        <td colspan="8" class="text-center text-secondary py-3">Belum ada riwayat import data.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recentImports as $imp): ?>
                        <tr>
                            <td><?= e(substr((string) ($imp['tanggal_import'] ?? ''), 0, 16)) ?></td>
                            <td><?= e($imp['kode_periode'] ?? '-') ?></td>
                            <td class="font-medium"><?= e($imp['nama_file_asli'] ?? '-') ?></td>
                            <td><?= e((string) ($imp['total_data'] ?? 0)) ?></td>
                            <td class="text-success"><?= e((string) ($imp['data_berhasil'] ?? 0)) ?></td>
                            <td class="text-danger"><?= e((string) ($imp['data_gagal'] ?? 0)) ?></td>
                            <td>
                                <span class="badge badge-<?= ($imp['status'] ?? '') === 'success' ? 'success' : (($imp['status'] ?? '') === 'partial' ? 'warning' : 'danger') ?>">
                                    <?= strtoupper(e((string) ($imp['status'] ?? 'pending'))) ?>
                                </span>
                            </td>
                            <td><?= e($imp['user_nama'] ?? '-') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// Filename display + client-side hint for the two upload zones. Vanilla, no deps.
(function () {
    'use strict';
    var MAX_BYTES = 20 * 1024 * 1024; // 20 MB hint ceiling, matches the hint text

    function fmtBytes(n) {
        if (n < 1024) return n + ' B';
        if (n < 1048576) return (n / 1024).toFixed(1) + ' KB';
        return (n / 1048576).toFixed(1) + ' MB';
    }

    function bind(zoneId, inputId, nameId) {
        var zone = document.getElementById(zoneId);
        var input = document.getElementById(inputId);
        var name = document.getElementById(nameId);
        if (!zone || !input || !name) return;

        function render(file) {
            if (!file) { name.textContent = ''; zone.classList.remove('has-file'); return; }
            var bad = [];
            if (!/\.xlsx$/i.test(file.name)) bad.push('ekstensi bukan .xlsx');
            if (file.size > MAX_BYTES) bad.push('ukuran di atas 20 MB');
            name.textContent = file.name + ' — ' + fmtBytes(file.size) + (bad.length ? '  ⚠ ' + bad.join(', ') : '');
            zone.classList.add('has-file');
        }

        input.addEventListener('change', function () { render(input.files && input.files[0]); });

        ['dragenter', 'dragover'].forEach(function (ev) {
            zone.addEventListener(ev, function (e) { e.preventDefault(); zone.classList.add('dragover'); });
        });
        ['dragleave', 'drop'].forEach(function (ev) {
            zone.addEventListener(ev, function (e) { e.preventDefault(); zone.classList.remove('dragover'); });
        });
        zone.addEventListener('drop', function (e) {
            var dt = e.dataTransfer;
            if (dt && dt.files && dt.files.length) { input.files = dt.files; render(dt.files[0]); }
        });
    }

    bind('upload-zone', 'file_excel', 'file_excel_name');
    bind('upload-zone-explicit', 'file_excel_explicit', 'file_excel_explicit_name');

    var form = document.getElementById('import-form');
    var submit = document.getElementById('import-submit');
    if (form && submit) {
        form.addEventListener('submit', function () {
            submit.disabled = true;
            submit.textContent = 'Memproses import\u2026';
        });
    }
})();
</script>
