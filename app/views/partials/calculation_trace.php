<?php
/**
 * ASENTRA SPK — Partial: Calculation Trace (Phase 6E)
 *
 * Menampilkan jejak perhitungan: RAW DATA -> persentase/rating -> nilai bulanan.
 * Membedakan jelas data mentah (apa yang diinput) dari derived value
 * (apa yang dihitung engine) agar Owner dapat memverifikasi setiap angka.
 *
 * @var array<string, mixed> $detail output AssessmentWorkflowService::getTechnicianIndicatorDetail()
 * @var array<string, mixed>|null $penilaian record tb_penilaian (bisa null sebelum kalkulasi)
 */

use App\Services\ChartDataService;

$calc = $detail['calculation'] ?? [];
$c1 = $calc['c1'] ?? [];
$c2 = $calc['c2'] ?? [];
$c3 = $calc['c3'] ?? [];
$penilaian = $penilaian ?? $detail['penilaian'] ?? null;

$rawKedi = $detail['raw_data']['kedisiplinan'] ?? [];
$rawPek  = $detail['raw_data']['pekerjaan'] ?? [];
$rawTJ   = $detail['raw_data']['tanggung_jawab'] ?? [];

$months = [1, 2, 3];
$monthName = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret'];
?>

<div class="card mb-4" data-reveal="up" style="border-left: 4px solid var(--brand);">
    <h2 class="card-title mb-1">Jejak Perhitungan — Data Mentah vs Hasil Derif</h2>
    <p class="text-sm text-secondary mb-3">
        Setiap baris menampilkan <strong>data mentah</strong> (apa yang diinput Admin dari Excel),
        <strong>persentase / rating</strong> (konversi sesuai aturan skala), dan
        <strong>nilai derif</strong> (yang dihitung Calculation Engine — bukan diketik manual).
        Nilai kuartal = rata-rata nilai bulanan yang tersedia.
    </p>

    <?php if ($penilaian !== null): ?>
        <div class="text-xs text-secondary mb-3">
            Sumber tersimpan: <strong>tb_penilaian</strong> (id=<?= e((string) ($penilaian['id'] ?? '?')) ?>) •
            Status: <span class="badge badge-<?= ($penilaian['status_data'] ?? '') === 'confirmed' ? 'success' : 'primary' ?>"><?= strtoupper(e((string) ($penilaian['status_data'] ?? 'draft'))) ?></span>
            <?php if (!empty($penilaian['confirmed_by_nama'])): ?>
                • Dikonfirmasi oleh <strong><?= e($penilaian['confirmed_by_nama']) ?></strong>
                <?php if (!empty($penilaian['confirmed_at'])): ?> pada <?= e(dateFormat((string) $penilaian['confirmed_at'], 'd M Y H:i')) ?><?php endif; ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- ==================================================== C1 -->
    <h3 class="font-bold mb-2">C1 — Kedisiplinan (bobot 30%)</h3>
    <div class="table-wrap mb-4">
        <table class="table">
            <thead>
                <tr>
                    <th>BULAN</th>
                    <th>RAW: KEHADIRAN</th>
                    <th>RAW: TERLAMBAT</th>
                    <th>RAW: JADWAL</th>
                    <th>KONVERSI</th>
                    <th>NILAI BULANAN</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rawKedi)): ?>
                    <tr><td colspan="6" class="text-center text-secondary py-2">Tidak ada data mentah kedisiplinan.</td></tr>
                <?php else: ?>
                    <?php foreach ($rawKedi as $kd): ?>
                        <?php $m = (int) $kd['bulan']; $md = $c1['monthly'][$m] ?? null; ?>
                        <tr>
                            <td><?= e($monthName[$m] ?? "Bulan {$m}") ?></td>
                            <td class="text-sm">
                                <?= e((string) $kd['hadir']) ?> / <?= e((string) $kd['total_hari_kerja']) ?> hari
                                <span class="text-secondary text-xs">(sakit <?= e((string) $kd['sakit']) ?>, izin <?= e((string) $kd['izin']) ?>, alpa <?= e((string) $kd['alpa']) ?>)</span>
                            </td>
                            <td class="text-sm"><?= e((string) $kd['terlambat']) ?> kali</td>
                            <td class="text-sm"><?= e((string) $kd['sesuai_jadwal']) ?> / <?= e((string) $kd['pekerjaan_terjadwal']) ?></td>
                            <td class="text-sm text-secondary">
                                <?php if ($md && ($md['status'] ?? '') === 'valid'): ?>
                                    Kehadiran <?= e(number_format((float) $md['persentase_kehadiran'], 1, '.', '')) ?>% &rarr; rating <?= e((string) $md['c1_1']) ?>
                                    &nbsp;•&nbsp; Telat &rarr; rating <?= e((string) $md['c1_2']) ?>
                                    &nbsp;•&nbsp; Jadwal <?= e(number_format((float) $md['persentase_jadwal'], 1, '.', '')) ?>% &rarr; rating <?= e((string) $md['c1_3']) ?>
                                <?php elseif ($md && !empty($md['error'])): ?>
                                    <span class="text-danger"><?= e($md['error']) ?></span>
                                <?php else: ?>
                                    <span class="text-muted">Tidak dihitung</span>
                                <?php endif; ?>
                            </td>
                            <td class="font-bold text-gold">
                                <?= ($md && $md['value'] !== null) ? e(number_format((float) $md['value'], 4, '.', '')) : '-' ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="text-xs text-secondary mb-4">
        Nilai kuartal C1 = <strong><?= e(number_format((float) ($c1['value'] ?? 0), 4, '.', '')) ?></strong>
        (<?= e((string) ($c1['months_available'] ?? 0)) ?>/3 bulan) —
        rata-rata <?= e((string) ($c1['months_available'] ?? 0)) ?> nilai bulanan valid.
        Rumus rating: &ge;95% (4), 85-94% (3), 75-84% (2), &lt;75% (1) — kehadiran & jadwal;
        0-2 telat (4), 3-5 (3), 6-8 (2), &ge;9 (1) — ketepatan waktu.
    </div>

    <!-- ==================================================== C2 -->
    <h3 class="font-bold mb-2">C2 — Kualitas Hasil Kerja (bobot 40%)</h3>
    <div class="table-wrap mb-4">
        <table class="table">
            <thead>
                <tr>
                    <th>BULAN</th>
                    <th>RAW: PEKERJAAN DIINSPEKSI</th>
                    <th>KONVERSI (% lulus)</th>
                    <th>NILAI BULANAN</th>
                </tr>
            </thead>
            <tbody>
                <?php $pekByMonth = [];
                foreach ($rawPek as $pk) { $pekByMonth[(int) $pk['bulan']][] = $pk; } ?>
                <?php if (empty($pekByMonth)): ?>
                    <tr><td colspan="4" class="text-center text-secondary py-2">Tidak ada data mentah inspeksi pekerjaan.</td></tr>
                <?php else: ?>
                    <?php foreach ($pekByMonth as $m => $jobs): ?>
                        <?php $md = $c2['monthly'][$m] ?? null; ?>
                        <tr>
                            <td><?= e($monthName[$m] ?? "Bulan {$m}") ?></td>
                            <td class="text-sm"><?= e((string) count($jobs)) ?> pekerjaan diinspeksi</td>
                            <td class="text-sm text-secondary">
                                <?php if ($md && ($md['status'] ?? '') === 'valid'): ?>
                                    Rapi <?= e(number_format((float) $md['persentase_rapi'], 1, '.', '')) ?>% &rarr; rating <?= e((string) $md['c2_1']) ?>
                                    &nbsp;•&nbsp; Presisi <?= e(number_format((float) $md['persentase_presisi'], 1, '.', '')) ?>% &rarr; rating <?= e((string) $md['c2_2']) ?>
                                    &nbsp;•&nbsp; Desain <?= e(number_format((float) $md['persentase_desain'], 1, '.', '')) ?>% &rarr; rating <?= e((string) $md['c2_3']) ?>
                                <?php elseif ($md && !empty($md['error'])): ?>
                                    <span class="text-danger"><?= e($md['error']) ?></span>
                                <?php else: ?>
                                    <span class="text-muted">Tidak dihitung</span>
                                <?php endif; ?>
                            </td>
                            <td class="font-bold text-gold">
                                <?= ($md && $md['value'] !== null) ? e(number_format((float) $md['value'], 4, '.', '')) : '-' ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="text-xs text-secondary mb-4">
        Nilai kuartal C2 = <strong><?= e(number_format((float) ($c2['value'] ?? 0), 4, '.', '')) ?></strong>
        (<?= e((string) ($c2['months_available'] ?? 0)) ?>/3 bulan).
        Rumus rating: &ge;90% (4), 75-89% (3), 60-74% (2), &lt;60% (1) untuk rapi / presisi / sesuai desain.
        Detail tiap pekerjaan ada di tabel <strong>Log Sampel Inspeksi</strong> di bawah.
    </div>

    <!-- ==================================================== C3 -->
    <h3 class="font-bold mb-2">C3 — Tanggung Jawab (bobot 30%)</h3>
    <div class="table-wrap mb-4">
        <table class="table">
            <thead>
                <tr>
                    <th>BULAN</th>
                    <th>RAW: RATING LANGSUNG (1-4)</th>
                    <th>NILAI BULANAN</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rawTJ)): ?>
                    <tr><td colspan="3" class="text-center text-secondary py-2">Tidak ada data mentah tanggung jawab.</td></tr>
                <?php else: ?>
                    <?php foreach ($rawTJ as $tj): ?>
                        <?php $m = (int) $tj['bulan']; $md = $c3['monthly'][$m] ?? null; ?>
                        <tr>
                            <td><?= e($monthName[$m] ?? "Bulan {$m}") ?></td>
                            <td class="text-sm">
                                Alat <?= e((string) $tj['perawatan_alat']) ?>/4 •
                                Material <?= e((string) $tj['efisiensi_material']) ?>/4 •
                                Inisiatif <?= e((string) $tj['inisiatif']) ?>/4 •
                                Prosedur <?= e((string) $tj['kepatuhan_prosedur']) ?>/4
                                <span class="text-secondary text-xs">(skala Likert langsung dari observer)</span>
                            </td>
                            <td class="font-bold text-gold">
                                <?= ($md && $md['value'] !== null) ? e(number_format((float) $md['value'], 4, '.', '')) : '-' ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="text-xs text-secondary mb-2">
        Nilai kuartal C3 = <strong><?= e(number_format((float) ($c3['value'] ?? 0), 4, '.', '')) ?></strong>
        (<?= e((string) ($c3['months_available'] ?? 0)) ?>/3 bulan) — rata-rata 4 subindikator per bulan,
        lalu rata-rata antar bulan.
    </div>
</div>
