<?php
/** @var string $title */
/** @var string $subtitle */
/** @var string $periode */
/** @var array<int, string> $periods */
/** @var array<int, array<string, mixed>> $results */
?>
<div class="page-header" data-reveal="up">
    <h1 class="page-title"><?= e($title) ?></h1>
    <p class="page-subtitle"><?= e($subtitle) ?></p>
</div>

<div class="action-bar" data-reveal="up">
    <form method="GET" action="<?= route('/owner/ranking') ?>" class="filter-group flex-1">
        <select name="periode" class="select filter-select-lg" onchange="this.form.submit()">
            <option value="">Pilih periode</option>
            <?php foreach ($periods as $p): ?>
                <option value="<?= e($p) ?>" <?= $periode === $p ? 'selected' : '' ?>><?= e(periodLabel($p)) ?></option>
            <?php endforeach; ?>
        </select>
        <?php if ($periode !== ''): ?>
            <a href="<?= route('/owner/ranking') ?>" class="btn btn-ghost btn-sm">Reset</a>
        <?php endif; ?>
    </form>

    <?php if ($periode !== ''): ?>
        <div class="row">
            <a href="<?= route('/owner/laporan/' . urlencode($periode)) ?>" class="btn btn-secondary">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 22V4a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v18Z"/><path d="M6 12H4a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h2"/><path d="M18 9h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2h-2"/><path d="M10 6h4"/><path d="M10 10h4"/><path d="M10 14h4"/></svg>
                Cetak Laporan
            </a>
            <form method="POST" action="<?= route('/owner/ranking/process') ?>" class="form-reset" data-loading>
                <?= csrfField() ?>
                <input type="hidden" name="periode" value="<?= e($periode) ?>">
                <button type="submit" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16"/><path d="M16 21h5v-5"/></svg>
                    Proses Ulang SAW
                </button>
            </form>
        </div>
    <?php endif; ?>
</div>

<?php if ($periode === ''): ?>
    <div class="card empty-state" data-reveal="scale">
        <div class="empty-state-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="8" height="4" x="8" y="2" rx="1" ry="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="M12 11v6"/><path d="M9 14h6"/></svg>
        </div>
        <div class="empty-state-title">Pilih Periode</div>
        <p>Silakan pilih periode evaluasi untuk melihat hasil ranking SAW.</p>
    </div>
<?php elseif (empty($results)): ?>
    <div class="card empty-state" data-reveal="scale">
        <div class="empty-state-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3v18h18"/><path d="m19 9-5 5-4-4-3 3"/></svg>
        </div>
        <div class="empty-state-title">Data Ranking Tidak Tersedia</div>
        <p>Periode <strong><?= e(periodLabel($periode)) ?></strong> memiliki data penilaian, tetapi belum diproses dengan SAW.</p>
        <form method="POST" action="<?= route('/owner/ranking/process') ?>" class="mt-4" data-loading>
            <?= csrfField() ?>
            <input type="hidden" name="periode" value="<?= e($periode) ?>">
            <button type="submit" class="btn btn-primary">Proses SAW Sekarang</button>
        </form>
    </div>
<?php else: ?>
    <div class="card" data-reveal="up">
        <div class="row-between mb-5">
            <div>
                <h2 class="card-title">Ranking SAW — <?= e(periodLabel($periode)) ?></h2>
                <p class="card-subtitle">Hasil perhitungan berdasarkan kriteria C1, C2, dan C3.</p>
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
                        <th>NILAI SAW</th>
                        <th>AKSI</th>
                    </tr>
                </thead>
                <tbody data-reveal-group>
                    <?php foreach ($results as $r): ?>
                        <tr data-reveal>
                            <td><?= rankBadge((int) $r['ranking']) ?></td>
                            <td>
                                <strong><?= e($r['kode_teknisi']) ?></strong>
                                <span class="text-muted"><?= e($r['nama_teknisi']) ?></span>
                            </td>
                            <td><span class="badge badge-neutral"><?= e((string) $r['c1']) ?></span></td>
                            <td><span class="badge badge-neutral"><?= e((string) $r['c2']) ?></span></td>
                            <td><span class="badge badge-neutral"><?= e((string) $r['c3']) ?></span></td>
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
