<?php
/**
 * ASENTRA SPK — Phase 6F: Owner Confirmation Page
 *
 * Halaman tinjauan akhir sebelum konfirmasi resmi. Owner WAJIB melihat:
 *  - ringkasan periode
 *  - jumlah teknisi
 *  - jumlah data lengkap / partial
 *  - jumlah warning
 *  - konsekuensi: penilaian terkunci, periode selesai, siap SAW
 *
 * Hanya Owner yang bisa menekan tombol (route dilindungi requireOwner).
 *
 * @var string $title
 * @var string $subtitle
 * @var array<string, mixed> $summary
 */

use App\Helpers\Url;

$p = $summary['periode'];
$oc = $summary['operational_counts'] ?? [];
$tabulasi = $summary['tabulasi'] ?? [];
$total = $summary['total_evaluations'] ?? 0;
$calculated = $summary['calculated_count'] ?? 0;
$partial = $summary['partial_count'] ?? 0;
$confirmed = $summary['confirmed_count'] ?? 0;
$draft = $summary['draft_count'] ?? 0;
$isConfirmed = $summary['is_confirmed'] ?? false;
$canConfirm = $summary['can_confirm'] ?? false;
$hasWarnings = $summary['has_warnings'] ?? false;

$warningCount = 0;
foreach ($tabulasi as $row) {
    if (!empty($row['warning'])) {
        $warningCount++;
    }
}
?>

<div class="page-header" data-reveal="up">
    <div class="row-between">
        <div>
            <h1 class="page-title"><?= e($title) ?></h1>
            <p class="page-subtitle"><?= e($subtitle) ?></p>
        </div>
        <a href="<?= route('/owner/assessment?periode_id=' . $p['id_periode']) ?>" class="btn btn-secondary">
            ← Kembali ke Review
        </a>
    </div>
</div>

<?php if ($isConfirmed): ?>
    <div class="card p-5" data-reveal="up" style="border-left: 4px solid var(--success, #16a34a);">
        <h2 class="font-bold text-success mb-1">✓ Periode ini sudah dikonfirmasi resmi</h2>
        <p class="text-sm text-secondary">
            Periode <strong><?= e((string) $p['kode_periode']) ?> — <?= e((string) $p['nama_periode']) ?></strong>
            telah dikonfirmasi oleh Owner. Penilaian <strong>terkunci</strong> dan tidak dapat diubah.
            <?php if (!empty($tabulasi[0]['confirmed_by_nama'])): ?>
                Dikonfirmasi oleh <strong><?= e($tabulasi[0]['confirmed_by_nama']) ?></strong>.
            <?php endif; ?>
        </p>
        <p class="text-sm text-secondary mt-2">
            Data ini siap diproses ke tahap SAW untuk perhitungan ranking akhir.
        </p>
    </div>
<?php elseif (!$canConfirm): ?>
    <div class="card p-5" data-reveal="up" style="border-left: 4px solid var(--warning, #f59e0b);">
        <h2 class="font-bold text-warning mb-1">⚠ Belum dapat dikonfirmasi</h2>
        <p class="text-sm text-secondary">
            Periode <strong><?= e((string) $p['kode_periode']) ?></strong>
            <?php if ($total === 0): ?>
                belum memiliki data penilaian. Minta Admin menjalankan kalkulasi terlebih dahulu.
            <?php elseif (($p['status'] ?? '') === 'legacy'): ?>
                adalah periode legacy dan tidak dapat dikonfirmasi.
            <?php else: ?>
                tidak memenuhi syarat konfirmasi saat ini.
            <?php endif; ?>
        </p>
    </div>
<?php else: ?>

    <!-- ============ RINGKASAN PERIODE ============ -->
    <div class="card mb-4" data-reveal="up">
        <h2 class="card-title mb-3">Ringkasan Periode</h2>
        <div class="grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 1rem;">
            <div class="p-3 bg-light rounded border">
                <div class="text-xs text-secondary">Kode Periode</div>
                <div class="font-bold text-lg"><?= e((string) $p['kode_periode']) ?></div>
            </div>
            <div class="p-3 bg-light rounded border">
                <div class="text-xs text-secondary">Nama Periode</div>
                <div class="font-bold text-lg"><?= e((string) $p['nama_periode']) ?></div>
            </div>
            <div class="p-3 bg-light rounded border">
                <div class="text-xs text-secondary">Rentang Waktu</div>
                <div class="font-bold text-lg"><?= e(dateFormat((string) $p['tanggal_mulai'], 'd M Y')) ?> → <?= e(dateFormat((string) $p['tanggal_selesai'], 'd M Y')) ?></div>
            </div>
            <div class="p-3 bg-light rounded border">
                <div class="text-xs text-secondary">Status Periode</div>
                <div class="font-bold text-lg text-capitalize"><?= e((string) ($p['status'] ?? 'draft')) ?></div>
            </div>
        </div>
    </div>

    <!-- ============ STATISTIK DATA ============ -->
    <div class="card mb-4" data-reveal="up">
        <h2 class="card-title mb-3">Statistik Data Penilaian</h2>
        <div class="grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 1rem;">
            <div class="p-3 bg-light rounded border">
                <div class="text-xs text-secondary">Jumlah Teknisi</div>
                <div class="font-bold text-gold" style="font-size: 1.75rem;"><?= e((string) $total) ?></div>
                <div class="text-xs text-secondary">dalam tb_penilaian</div>
            </div>
            <div class="p-3 bg-light rounded border">
                <div class="text-xs text-secondary">Data Lengkap (3 bulan)</div>
                <div class="font-bold text-success" style="font-size: 1.75rem;"><?= e((string) $calculated) ?></div>
                <div class="text-xs text-secondary">C1, C2, C3 = 3/3</div>
            </div>
            <div class="p-3 bg-light rounded border" style="<?= $partial > 0 ? 'border-color: var(--warning, #f59e0b);' : '' ?>">
                <div class="text-xs text-secondary">Data Partial (&lt; 3 bulan)</div>
                <div class="font-bold <?= $partial > 0 ? 'text-warning' : 'text-success' ?>" style="font-size: 1.75rem;"><?= e((string) $partial) ?></div>
                <div class="text-xs text-secondary">dihitung proporsional</div>
            </div>
            <div class="p-3 bg-light rounded border" style="<?= $warningCount > 0 ? 'border-color: var(--warning, #f59e0b);' : '' ?>">
                <div class="text-xs text-secondary">Jumlah Warning</div>
                <div class="font-bold <?= $warningCount > 0 ? 'text-warning' : 'text-success' ?>" style="font-size: 1.75rem;"><?= e((string) $warningCount) ?></div>
                <div class="text-xs text-secondary"><?= $warningCount > 0 ? 'perlu diperhatikan' : 'tidak ada' ?></div>
            </div>
        </div>

        <?php if ($draft > 0): ?>
            <div class="text-xs text-secondary mt-3">
                Catatan: <?= e((string) $draft) ?> teknisi masih berstatus <strong>draft</strong>
                (belum dikalkulasi penuh) dan akan ikut terkunci apa adanya.
            </div>
        <?php endif; ?>

        <?php if ($hasWarnings || $warningCount > 0): ?>
            <div class="p-3 rounded border mt-3" style="background: var(--gold-soft, rgba(245,158,11,0.10)); border-color: var(--warning, #f59e0b);">
                <div class="font-bold text-warning mb-2">⚠ Detail Warning (<?= e((string) $warningCount) ?> teknisi)</div>
                <ul class="text-sm mb-0" style="padding-left: 1.25rem;">
                    <?php foreach ($tabulasi as $row): ?>
                        <?php if (!empty($row['warning'])): ?>
                            <li><strong><?= e($row['kode_teknisi']) ?> — <?= e($row['nama_teknisi']) ?></strong>: <?= e($row['warning']) ?></li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
                <div class="text-xs text-secondary mt-2">
                    Data partial tetap valid — dihitung hanya dari bulan yang tersedia (bukan dianggap 0).
                    Anda masih bisa konfirmasi; partial tidak memblokir, tapi tercatat.
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- ============ KONSEKUENSI ============ -->
    <div class="card mb-4" data-reveal="up" style="border-left: 4px solid var(--danger, #dc2626);">
        <h2 class="card-title mb-2 text-danger">Konsekuensi Konfirmasi</h2>
        <ul class="text-sm" style="padding-left: 1.25rem; line-height: 1.8;">
            <li>Seluruh nilai C1, C2, C3 pada periode ini <strong>DIKUNCI</strong> (status → <strong>CONFIRMED</strong>).</li>
            <li>Periode berubah menjadi <strong>SELESAI</strong> dan tidak bisa menerima import atau kalkulasi ulang.</li>
            <li>Nilai yang sudah dikonfirmasi <strong>tidak dapat diubah</strong> melalui workflow V2 (input manual maupun import ulang).</li>
            <li>Nilai final akan menjadi <strong>sumber otoritatif untuk perhitungan SAW</strong> dan ranking teknisi.</li>
            <li>Rumus dan bobot (0.30 / 0.40 / 0.30) tidak berubah — yang dikunci adalah <strong>data masukan</strong>, bukan cara menghitung.</li>
        </ul>
    </div>

    <!-- ============ TABEL RINCIAN ============ -->
    <div class="card mb-4" data-reveal="up">
        <h2 class="card-title mb-3">Tabel Penilaian yang Akan Dikunci</h2>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>NO</th>
                        <th>TEKNISI</th>
                        <th class="text-right">C1</th>
                        <th class="text-right">C2</th>
                        <th class="text-right">C3</th>
                        <th class="text-right">VI</th>
                        <th>STATUS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tabulasi as $row): ?>
                        <tr>
                            <td><?= e((string) $row['no']) ?></td>
                            <td><span class="font-bold text-gold"><?= e($row['kode_teknisi']) ?></span> <?= e($row['nama_teknisi']) ?></td>
                            <td class="text-right"><?= e(number_format((float) $row['c1'], 4, '.', '')) ?></td>
                            <td class="text-right"><?= e(number_format((float) $row['c2'], 4, '.', '')) ?></td>
                            <td class="text-right"><?= e(number_format((float) $row['c3'], 4, '.', '')) ?></td>
                            <td class="text-right font-bold text-primary"><?= e(number_format((float) $row['vi'], 4, '.', '')) ?></td>
                            <td>
                                <span class="badge badge-<?= $row['status_data'] === 'partial' ? 'warning' : 'success' ?>">
                                    <?= e(strtoupper($row['status_data'])) ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ============ TOMBOL AKSI ============ -->
    <div class="card p-4" data-reveal="up">
        <div class="row-between" style="flex-wrap: wrap; gap: 1rem;">
            <div>
                <h3 class="font-bold mb-1">Apakah Anda yakin?</h3>
                <p class="text-sm text-secondary mb-0">
                    Dengan menekan tombol konfirmasi, Anda menyatakan telah menelaah
                    seluruh <?= e((string) $total) ?> data teknisi dan menerima <?= e((string) $warningCount) ?> warning
                    yang ada. Tindakan ini <strong>tidak dapat dibatalkan</strong>.
                </p>
            </div>
            <form method="POST" action="<?= route('/owner/assessment/' . $p['id_periode'] . '/confirm') ?>">
                <?= csrfField() ?>
                <button type="submit" class="btn btn-primary" style="padding: 0.75rem 1.5rem; font-size: 1rem;"
                        onclick="return confirm('PERINGATAN: Konfirmasi akan mengunci penilaian periode <?= e(jsSafe((string) $p['nama_periode'])) ?> secara permanen (<?= e((string) $total) ?> teknisi, <?= e((string) $warningCount) ?> warning). Lanjutkan?')">
                    ✓ Konfirmasi & Kunci Penilaian
                </button>
            </form>
        </div>
    </div>

<?php endif; ?>
