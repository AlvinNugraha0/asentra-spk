<?php
/** @var string $title */
/** @var string $subtitle */
/** @var array<string, mixed> $hasil */
/** @var array<string, float> $weights */
?>
<div class="page-header" data-reveal="up">
    <h1 class="page-title"><?= e($title) ?></h1>
    <p class="page-subtitle"><?= e($subtitle) ?></p>
</div>

<div class="card" style="margin-bottom: var(--space-5);" data-reveal="up">
    <div class="row-between" style="flex-wrap: wrap;">
        <div>
            <div class="meta-text">Teknisi</div>
            <div class="highlight-name"><?= e($hasil['kode_teknisi']) ?> — <?= e($hasil['nama_teknisi']) ?></div>
        </div>
        <div class="rank-hero">
            <div class="meta-text">Ranking</div>
            <div class="rank-hero-value">#<?= e((string) $hasil['ranking']) ?></div>
        </div>
    </div>
</div>

<div class="saw-formula" data-reveal="up">
    Semua kriteria bersifat Benefit. Normalisasi: <strong>r<sub>ij</sub> = x<sub>ij</sub> / max(x<sub>j</sub>)</strong>
    &nbsp;&nbsp;|&nbsp;&nbsp;
    Nilai preferensi: <strong>V<sub>i</sub> = Σ(w<sub>j</sub> × r<sub>ij</sub>)</strong>
</div>

<div class="saw-flow" data-reveal-group>
    <!-- Original values -->
    <div class="saw-flow-step" data-reveal="up">
        <div class="saw-flow-arrow">
            <div class="saw-flow-dot"></div>
            <div class="saw-flow-line"></div>
        </div>
        <div class="saw-flow-card">
            <div class="saw-flow-label">Nilai Asli (X)</div>
            <div class="grid-3">
                <div>
                    <div class="meta-text">C1 — Kedisiplinan</div>
                    <div class="saw-flow-value"><?= e((string) $hasil['c1']) ?></div>
                </div>
                <div>
                    <div class="meta-text">C2 — Kualitas Hasil Kerja</div>
                    <div class="saw-flow-value"><?= e((string) $hasil['c2']) ?></div>
                </div>
                <div>
                    <div class="meta-text">C3 — Tanggung Jawab</div>
                    <div class="saw-flow-value"><?= e((string) $hasil['c3']) ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Maximum values -->
    <div class="saw-flow-step" data-reveal="up">
        <div class="saw-flow-arrow">
            <div class="saw-flow-dot"></div>
            <div class="saw-flow-line"></div>
        </div>
        <div class="saw-flow-card">
            <div class="saw-flow-label">Nilai Maksimum per Kriteria</div>
            <div class="grid-3">
                <div>
                    <div class="meta-text">max(C1)</div>
                    <div class="saw-flow-value"><?= e(scoreFormat((float) $hasil['c1'] / (float) $hasil['nilai_c1_normalisasi'], 0)) ?></div>
                </div>
                <div>
                    <div class="meta-text">max(C2)</div>
                    <div class="saw-flow-value"><?= e(scoreFormat((float) $hasil['c2'] / (float) $hasil['nilai_c2_normalisasi'], 0)) ?></div>
                </div>
                <div>
                    <div class="meta-text">max(C3)</div>
                    <div class="saw-flow-value"><?= e(scoreFormat((float) $hasil['c3'] / (float) $hasil['nilai_c3_normalisasi'], 0)) ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Normalized values -->
    <div class="saw-flow-step" data-reveal="up">
        <div class="saw-flow-arrow">
            <div class="saw-flow-dot"></div>
            <div class="saw-flow-line"></div>
        </div>
        <div class="saw-flow-card">
            <div class="saw-flow-label">Normalisasi (R)</div>
            <div class="grid-3">
                <div>
                    <div class="meta-text">R1 = C1 / max(C1)</div>
                    <div class="saw-flow-value"><?= e(scoreFormat((float) $hasil['nilai_c1_normalisasi'], 6)) ?></div>
                </div>
                <div>
                    <div class="meta-text">R2 = C2 / max(C2)</div>
                    <div class="saw-flow-value"><?= e(scoreFormat((float) $hasil['nilai_c2_normalisasi'], 6)) ?></div>
                </div>
                <div>
                    <div class="meta-text">R3 = C3 / max(C3)</div>
                    <div class="saw-flow-value"><?= e(scoreFormat((float) $hasil['nilai_c3_normalisasi'], 6)) ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Weights -->
    <div class="saw-flow-step" data-reveal="up">
        <div class="saw-flow-arrow">
            <div class="saw-flow-dot"></div>
            <div class="saw-flow-line"></div>
        </div>
        <div class="saw-flow-card">
            <div class="saw-flow-label">Bobot (W)</div>
            <div class="grid-3">
                <div>
                    <div class="meta-text">w1</div>
                    <div class="saw-flow-value"><?= e(weightPercent($weights['C1'])) ?></div>
                </div>
                <div>
                    <div class="meta-text">w2</div>
                    <div class="saw-flow-value"><?= e(weightPercent($weights['C2'])) ?></div>
                </div>
                <div>
                    <div class="meta-text">w3</div>
                    <div class="saw-flow-value"><?= e(weightPercent($weights['C3'])) ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Contributions -->
    <div class="saw-flow-step" data-reveal="up">
        <div class="saw-flow-arrow">
            <div class="saw-flow-dot"></div>
            <div class="saw-flow-line"></div>
        </div>
        <div class="saw-flow-card">
            <div class="saw-flow-label">Kontribusi Terbobot (W × R)</div>
            <div class="grid-3">
                <div>
                    <div class="meta-text">C1 × w1</div>
                    <div class="saw-flow-value"><?= e(scoreFormat((float) $hasil['kontribusi_c1'], 6)) ?></div>
                </div>
                <div>
                    <div class="meta-text">C2 × w2</div>
                    <div class="saw-flow-value"><?= e(scoreFormat((float) $hasil['kontribusi_c2'], 6)) ?></div>
                </div>
                <div>
                    <div class="meta-text">C3 × w3</div>
                    <div class="saw-flow-value"><?= e(scoreFormat((float) $hasil['kontribusi_c3'], 6)) ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Preference value -->
    <div class="saw-flow-step" data-reveal="up">
        <div class="saw-flow-arrow">
            <div class="saw-flow-dot"></div>
        </div>
        <div class="saw-flow-card" style="border-color: var(--gold); background: var(--gold-soft);">
            <div class="saw-flow-label">Nilai Preferensi (V<sub>i</sub>)</div>
            <div class="saw-flow-value" style="font-size: var(--text-3xl); color: var(--gold);"><?= e(scoreFormat((float) $hasil['nilai_preferensi'], 6)) ?></div>
            <div class="meta-text" style="margin-top: var(--space-2);">Jumlah kontribusi C1 + C2 + C3</div>
        </div>
    </div>
</div>

<div class="row" style="margin-top: var(--space-6);" data-reveal="up">
    <a href="<?= route('/owner/ranking?periode=' . urlencode($hasil['periode'])) ?>" class="btn btn-secondary">Kembali ke Ranking</a>
    <a href="<?= route('/owner/riwayat?periode=' . urlencode($hasil['periode'])) ?>" class="btn btn-ghost">Lihat Riwayat</a>
</div>
