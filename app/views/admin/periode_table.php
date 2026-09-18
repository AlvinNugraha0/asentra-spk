<?php
// ASENTRA SPK — Shared periode table (Phase 6L).
// Rendered on /admin/import (main section) and /admin/periode (backward compat).
//
// Expected per-row keys (see PeriodePenilaian::allWithProgress()):
//   kode_periode, nama_periode, tanggal_mulai, tanggal_selesai, status,
//   file_import, id_periode, operational_counts{total,kedisiplinan,pekerjaan,
//   tanggung_jawab}, can_calculate, cannot_calculate_reason.
/** @var array<int, array<string, mixed>> $periods */
?>
<div class="card" data-reveal="up">
    <h2 class="card-title mb-3">Daftar Periode Penilaian</h2>
    <div class="table-wrap">
        <table class="table" id="periode-table">
            <thead>
                <tr>
                    <th>KODE</th>
                    <th>NAMA PERIODE</th>
                    <th>RENTANG TANGGAL</th>
                    <th>DATA OPERASIONAL</th>
                    <th>STATUS</th>
                    <th>FILE IMPORT</th>
                    <th class="text-right">AKSI</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($periods)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-secondary py-3">Belum ada periode penilaian.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($periods as $p): ?>
                        <tr>
                            <td class="font-bold text-gold"><?= e($p['kode_periode']) ?></td>
                            <td class="font-semibold"><?= e($p['nama_periode']) ?></td>
                            <td><?= e($p['tanggal_mulai']) ?> s/d <?= e($p['tanggal_selesai']) ?></td>
                            <td>
                                <span class="text-sm">
                                    Disiplin: <strong><?= e((string) ($p['operational_counts']['kedisiplinan'] ?? 0)) ?></strong> |
                                    Kerja: <strong><?= e((string) ($p['operational_counts']['pekerjaan'] ?? 0)) ?></strong> |
                                    TJ: <strong><?= e((string) ($p['operational_counts']['tanggung_jawab'] ?? 0)) ?></strong>
                                </span>
                            </td>
                            <td>
                                <?php
                                $st = $p['status'] ?? 'draft';
                                $badgeClass = match ($st) {
                                    'selesai' => 'success',
                                    'proses' => 'warning',
                                    'legacy' => 'neutral',
                                    default => 'neutral',
                                };
                                ?>
                                <span class="badge badge-<?= $badgeClass ?>"><?= strtoupper(e($st)) ?></span>
                            </td>
                            <td class="text-sm text-secondary">
                                <?= !empty($p['file_import']) ? e($p['file_import']) : '-' ?>
                            </td>
                            <td class="text-right">
                                <div style="display: flex; justify-content: flex-end; gap: 6px; align-items: center;">
                                    <?php if ($st === 'legacy'): ?>
                                        <span class="text-xs text-secondary">Read-only</span>
                                    <?php else: ?>
                                        <?php if ($p['can_calculate']): ?>
                                            <form method="POST" action="<?= route('/admin/periode/kalkulasi/' . $p['id_periode']) ?>" style="margin: 0;">
                                                <?= csrfField() ?>
                                                <button type="submit" class="btn btn-sm btn-primary" onclick="return confirm('Jalankan kalkulasi C1, C2, C3 untuk periode <?= e($p['kode_periode']) ?>?')">
                                                    Hitung V2
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-xs text-secondary" title="<?= e($p['cannot_calculate_reason'] ?? '') ?>">
                                                <?= $st === 'selesai' ? 'Terkonfirmasi' : 'Belum Ada Data' ?>
                                            </span>
                                        <?php endif; ?>

                                        <?php if ($st !== 'draft' || ($p['operational_counts']['total'] ?? 0) > 0): ?>
                                            <a href="<?= route('/admin/periode/hasil/' . $p['id_periode']) ?>" class="btn btn-sm btn-secondary">
                                                Hasil
                                            </a>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
