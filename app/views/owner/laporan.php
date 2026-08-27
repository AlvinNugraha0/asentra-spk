<?php
/** @var string $title */
/** @var string $periode */
/** @var array<int, array<string, mixed>> $results */
/** @var array<int, array<string, mixed>> $kriteria */
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title) ?></title>
    <link rel="stylesheet" href="<?= asset('css/print.css') ?>">
</head>
<body>
    <div class="report-toolbar no-print">
        <a href="<?= route('/owner/laporan') ?>" class="btn btn-secondary btn-sm">&larr; Kembali</a>
        <button type="button" class="btn btn-primary btn-sm" onclick="window.print()">&#128424; Cetak Laporan</button>
    </div>

    <div class="report">
        <header class="report-header">
            <div class="report-brand">CV ARSITEK SEMESTA NUSANTARA</div>
            <div class="report-sub">Arsitektur &amp; Konstruksi — Cirebon</div>
            <h1>LAPORAN PENILAIAN KINERJA TEKNISI</h1>
            <p>Metode: Simple Additive Weighting (SAW)</p>
            <p>Periode: <strong><?= e(periodLabel($periode)) ?></strong></p>
        </header>

        <section class="report-meta">
            <h2>Kriteria dan Bobot</h2>
            <table class="report-criteria">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Kriteria</th>
                        <th>Atribut</th>
                        <th>Bobot</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($kriteria as $k): ?>
                        <tr>
                            <td><?= e($k['kode']) ?></td>
                            <td><?= e($k['nama_kriteria']) ?></td>
                            <td><?= e(ucfirst($k['atribut'])) ?></td>
                            <td class="numeric"><?= e(weightPercent((float) $k['bobot'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>

        <section class="report-ranking">
            <h2>Ranking Teknisi</h2>
            <table class="report-table">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Kode</th>
                        <th>Teknisi</th>
                        <th>C1</th>
                        <th>C2</th>
                        <th>C3</th>
                        <th>Nilai SAW</th>
                        <th>Peringkat</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no = 1; foreach ($results as $r): ?>
                        <tr>
                            <td class="numeric"><?= $no++ ?></td>
                            <td><?= e($r['kode_teknisi']) ?></td>
                            <td><?= e($r['nama_teknisi']) ?></td>
                            <td class="numeric"><?= e((string) $r['c1']) ?></td>
                            <td class="numeric"><?= e((string) $r['c2']) ?></td>
                            <td class="numeric"><?= e((string) $r['c3']) ?></td>
                            <td class="numeric"><?= e(scoreFormat((float) $r['nilai_preferensi'], 3)) ?></td>
                            <td class="numeric"><?= e((string) $r['ranking']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>

        <section class="report-conclusion">
            <h2>Kesimpulan</h2>
            <p>
                Teknisi dengan nilai preferensi tertinggi menjadi alternatif dengan peringkat
                terbaik berdasarkan metode SAW. Nilai preferensi dihitung dari normalisasi
                Benefit <span class="formula">r<sub>ij</sub> = x<sub>ij</sub> / max(x<sub>j</sub>)</span>
                dan pembobotan <span class="formula">V<sub>i</sub> = &Sigma;(w<sub>j</sub> &times; r<sub>ij</sub>)</span>.
            </p>
        </section>

        <footer class="report-footer">
            <p>Tanggal cetak: <?= date('d M Y') ?></p>
        </footer>
    </div>
</body>
</html>
