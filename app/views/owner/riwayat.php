<?php
/** @var string $title */
/** @var string $subtitle */
/** @var string $periode */
/** @var array<int, string> $periods */
/** @var array<int, array{value: string, label: string}> $periodOptions */
/** @var array<string, mixed>|null $periodInfo */
/** @var array<int, array<string, mixed>> $results */

// Phase 6H: V2 periods are labelled with nama_periode + rentang tanggal.
$periodeLabel = $periodInfo !== null
    ? (string) ($periodInfo['nama_periode'] ?? $periode)
      . ' (' . (string) ($periodInfo['tanggal_mulai'] ?? '')
      . ' s/d ' . (string) ($periodInfo['tanggal_selesai'] ?? '') . ')'
    : periodLabel($periode);
?>
<div class="page-header">
    <h1 class="page-title"><?= e($title) ?></h1>
    <p class="page-subtitle"><?= e($subtitle) ?></p>
</div>

<div class="action-bar">
    <form method="GET" action="<?= route('/owner/riwayat') ?>" class="filter-group flex-1">
        <select name="periode" class="select filter-select-lg" onchange="this.form.submit()">
        <option value="">Pilih periode</option>
        <?php foreach ($periodOptions as $po): ?>
            <option value="<?= e($po['value']) ?>" <?= $periode === $po['value'] ? 'selected' : '' ?>><?= e($po['label']) ?></option>
        <?php endforeach; ?>
    </select>
        <?php if ($periode !== ''): ?>
            <a href="<?= route('/owner/riwayat') ?>" class="btn btn-ghost btn-sm">Reset</a>
        <?php endif; ?>
    </form>
</div>

<?php if ($periode === ''): ?>
    <div class="card empty-state">
        <div class="empty-state-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
        </div>
        <div class="empty-state-title">Pilih Periode</div>
        <p>Riwayat menampilkan hasil SAW yang sudah diproses sebelumnya. Pilih periode untuk melihat detail ranking.</p>
    </div>
<?php elseif (empty($results)): ?>
    <div class="card empty-state">
        <div class="empty-state-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="m19 9-5 5-4-4-3 3"/></svg>
        </div>
        <div class="empty-state-title">Belum Ada Riwayat</div>
        <p>Belum ada hasil SAW yang tersimpan untuk periode <strong><?= e($periodeLabel) ?></strong>. Jalankan perhitungan dari menu Hasil Ranking.</p>
        <a href="<?= route('/owner/ranking?periode=' . urlencode($periode)) ?>" class="btn btn-primary mt-4">Buka Hasil Ranking</a>
    </div>
<?php else: ?>
    <div class="card">
        <div class="row-between mb-5">
            <div>
                <h2 class="card-title">Riwayat — <?= e($periodeLabel) ?></h2>
                <p class="card-subtitle">Periode terisolasi dari periode lain. Hasil berasal dari proses SAW terakhir.</p>
            </div>
            <span class="badge badge-gold"><?= e((string) count($results)) ?> Teknisi</span>
        </div>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>RANK</th>
                        <th>TEKNISI</th>
                        <th>C1</th>
                        <th>C2</th>
                        <th>C3</th>
                        <th>NILAI SAW (Vi)</th>
                        <th>AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($results as $r): ?>
                        <tr>
                            <td>
                                <?php if ((int) $r['ranking'] === 1): ?>
                                    <span class="badge badge-gold"><?= e((string) $r['ranking']) ?></span>
                                <?php else: ?>
                                    <span class="badge badge-neutral"><?= e((string) $r['ranking']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?= e($r['kode_teknisi']) ?></strong>
                                <span class="text-muted"><?= e($r['nama_teknisi']) ?></span>
                            </td>
                            <td class="tabular"><?= e(decimalFormat((string) ($r['c1'] ?? ''))) ?></td>
                            <td class="tabular"><?= e(decimalFormat((string) ($r['c2'] ?? ''))) ?></td>
                            <td class="tabular"><?= e(decimalFormat((string) ($r['c3'] ?? ''))) ?></td>
                            <td class="font-bold text-gold tabular"><?= e(scoreFormat((float) $r['nilai_preferensi'], 3)) ?></td>
                            <td>
                                <a href="<?= route('/owner/ranking/detail/' . $r['id']) ?>" class="btn btn-secondary btn-sm">Detail</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>
