<?php
/**
 * ASENTRA SPK — Partial: Pre-confirmation Checklist (Phase 6E)
 *
 * Ringkasan apa yang akan dikunci Owner sebelum menekan tombol Konfirmasi.
 * Mencegah konfirmasi buta: Owner melihat jumlah data, kelengkapan,
 * warning, dan sumber data.
 *
 * @var array<string, mixed>|null $summary output getPeriodAssessmentSummary()
 */

if ($summary === null || empty($summary['periode'])) {
    return;
}

$p = $summary['periode'];
$oc = $summary['operational_counts'] ?? ['kedisiplinan' => 0, 'pekerjaan' => 0, 'tanggung_jawab' => 0, 'total' => 0];
$isConfirmed = $summary['is_confirmed'] ?? false;
$canConfirm = $summary['can_confirm'] ?? false;
$hasWarnings = $summary['has_warnings'] ?? false;
$partialCount = $summary['partial_count'] ?? 0;
$calculatedCount = $summary['calculated_count'] ?? 0;
$total = $summary['total_evaluations'] ?? 0;
$tabulasi = $summary['tabulasi'] ?? [];
?>

<div class="card mb-4" data-reveal="up" style="border-left: 4px solid <?= $isConfirmed ? 'var(--success, #16a34a)' : 'var(--gold, #f59e0b)' ?>;">
    <div class="row-between mb-2" style="flex-wrap: wrap; gap: 0.5rem;">
        <h2 class="card-title">Daftar Periksa Sebelum Konfirmasi</h2>
        <?php if ($isConfirmed): ?>
            <span class="badge badge-success">✓ Sudah dikonfirmasi resmi</span>
        <?php elseif ($canConfirm): ?>
            <span class="badge badge-warning">Siap dikonfirmasi — periksa dulu</span>
        <?php else: ?>
            <span class="badge badge-neutral">Belum dapat dikonfirmasi</span>
        <?php endif; ?>
    </div>

    <div class="grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 0.75rem; margin-bottom: 1rem;">
        <div class="p-3 bg-light rounded border">
            <div class="text-xs text-secondary">Periode</div>
            <div class="font-bold"><?= e((string) ($p['kode_periode'] ?? '')) ?></div>
            <div class="text-xs text-secondary"><?= e((string) ($p['nama_periode'] ?? '')) ?></div>
        </div>
        <div class="p-3 bg-light rounded border">
            <div class="text-xs text-secondary">Jumlah teknisi dinilai</div>
            <div class="font-bold text-gold"><?= e((string) $total) ?></div>
            <div class="text-xs text-secondary">baris di tb_penilaian</div>
        </div>
        <div class="p-3 bg-light rounded border">
            <div class="text-xs text-secondary">Data lengkap (3 bulan)</div>
            <div class="font-bold text-success"><?= e((string) $calculatedCount) ?></div>
            <div class="text-xs text-secondary">C1, C2, C3 masing-masing 3/3</div>
        </div>
        <div class="p-3 bg-light rounded border">
            <div class="text-xs text-secondary">Data parsial (&lt; 3 bulan)</div>
            <div class="font-bold <?= $partialCount > 0 ? 'text-warning' : 'text-success' ?>"><?= e((string) $partialCount) ?></div>
            <div class="text-xs text-secondary">dihitung proporsional</div>
        </div>
    </div>

    <div class="text-sm mb-3">
        <strong>Data operasional mentah</strong> tersimpan untuk periode ini:
        <span class="text-secondary text-xs">
            Kedisiplinan <?= e((string) ($oc['kedisiplinan'] ?? 0)) ?> baris •
            Inspeksi pekerjaan <?= e((string) ($oc['pekerjaan'] ?? 0)) ?> baris •
            Tanggung jawab <?= e((string) ($oc['tanggung_jawab'] ?? 0)) ?> baris
        </span>
    </div>

    <?php if ($hasWarnings || $partialCount > 0): ?>
        <div class="p-3 rounded border mb-3" style="background: var(--gold-soft, rgba(245,158,11,0.10)); border-color: var(--gold, #f59e0b);">
            <div class="font-bold text-warning mb-1">⚠ Perhatian — ada data parsial</div>
            <div class="text-sm">
                Terdapat <strong><?= e((string) $partialCount) ?></strong> teknisi dengan observasi kurang dari 3 bulan.
                Nilainya dihitung hanya dari bulan yang tersedia (proporsional, bukan diasumsikan 0).
                Buka mode <strong>Tabulasi</strong> atau tombol <strong>Detail</strong> untuk melihat bulan mana yang kurang.
            </div>
            <?php if (!empty($tabulasi)): ?>
                <ul class="text-sm mt-2 mb-0" style="padding-left: 1.25rem;">
                    <?php foreach ($tabulasi as $row): ?>
                        <?php if (!empty($row['warning'])): ?>
                            <li><strong><?= e($row['kode_teknisi']) ?></strong> — <?= e($row['warning']) ?></li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="text-sm text-success mb-3">✓ Semua teknisi memiliki observasi lengkap 3 bulan. Tidak ada warning.</div>
    <?php endif; ?>

    <div class="text-xs text-secondary">
        Dengan mengonfirmasi, nilai C1/C2/C3 periode ini <strong>dikunci</strong> (status &rarr; CONFIRMED)
        dan tidak dapat diubah. Periode berubah menjadi <strong>selesai</strong>.
        Hanya data confirmed yang akan diproses SAW. Rumus dan bobot (0.30/0.40/0.30) tidak berubah.
    </div>
</div>
