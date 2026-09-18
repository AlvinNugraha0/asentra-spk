<?php
// ASENTRA SPK — Laporan controller (Owner)

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Hasil;
use App\Models\Kriteria;
use App\Models\PeriodePenilaian;

class LaporanController
{
    /**
     * Period selector page (inside dark app layout).
     *
     * Phase 6H: periode V2 dipresentasikan sebagai pilihan id_periode (lebih
     * dapat diandalkan daripada parsing string), disusun sebelum legacy.
     */
    public function index(): void
    {
        requireOwner();

        $options = [];

        // V2 periods that actually have SAW results.
        $v2 = PeriodePenilaian::allWithEvaluations();
        $v2Kodes = [];
        foreach ($v2 as $p) {
            if (($p['status'] ?? '') === 'legacy') {
                continue;
            }
            $id = (int) $p['id_periode'];
            if (Hasil::countByPeriodeId($id) === 0) {
                continue;
            }
            $v2Kodes[] = (string) $p['kode_periode'];
            $options[] = [
                'value' => (string) $id,
                'label' => sprintf(
                    '%s — %s',
                    (string) $p['kode_periode'],
                    (string) $p['nama_periode']
                ),
            ];
        }

        // Legacy V1 codes from tb_hasil. A quarter code already represented by
        // a V2 entry above is skipped so the period is not listed twice.
        foreach (Hasil::periods() as $code) {
            if (in_array($code, $v2Kodes, true)) {
                continue;
            }
            $options[] = ['value' => $code, 'label' => periodLabel($code)];
        }

        renderWithLayout('owner/laporan_index', [
            'title' => 'Laporan',
            'subtitle' => 'Cetak laporan penilaian kinerja teknisi per periode.',
            'periodOptions' => $options,
        ]);
    }

    /**
     * Printable report for a single period (standalone white document).
     *
     * Phase 6H: selain kode V1 'YYYY-MM', halaman ini juga menerima id_periode
     * numerik V2 maupun kode quarter V2 ('Q1-2026'). Format V1 tetap didukung.
     */
    public function show(string $periode): void
    {
        requireOwner();

        $periode = trim($periode);
        $results = Hasil::byPeriodeFlexible($periode);

        // Empty results could mean either "period not found" or "not yet
        // processed". Only the clearly malformed codes are rejected outright.
        // ponytail: the DB stores quarter codes as 'Q1-2026' (quarter first),
        // so both that shape and the 'YYYY-Q1' shape are accepted.
        $isNumericId = preg_match('/^\d+$/', $periode) === 1;
        $isQuarterCode = preg_match('/^Q[1-4]-\d{4}$/', $periode) === 1
            || preg_match('/^\d{4}-Q[1-4]$/', $periode) === 1;
        $isMonthCode = preg_match('/^\d{4}-\d{2}$/', $periode) === 1;
        $isLegacyCode = str_starts_with($periode, 'LEGACY-');

        if (!$isNumericId && !$isQuarterCode && !$isMonthCode && !$isLegacyCode) {
            setFlash('Periode tidak valid.', 'error');
            redirect('/owner/laporan');
        }

        if (empty($results)) {
            setFlash('Periode ' . e($periode) . ' belum memiliki hasil ranking. Jalankan proses SAW terlebih dahulu.', 'error');
            redirect('/owner/laporan');
        }

        // Phase 6I: resolve the period row (V2 id_periode or quarter code) so
        // the printable heading uses nama_periode + date range. Falls back to
        // periodLabel() for V1 'YYYY-MM' / 'LEGACY-YYYY-MM' codes.
        $periodInfo = null;
        $resolvedId = (int) ($results[0]['id_periode'] ?? 0);
        if ($resolvedId > 0) {
            $periodInfo = PeriodePenilaian::findById($resolvedId);
        }

        $kriteria = Kriteria::all();

        render('owner/laporan', [
            'title' => 'Laporan Penilaian Kinerja Teknisi',
            'periode' => $periode,
            'periodInfo' => $periodInfo,
            'results' => $results,
            'kriteria' => $kriteria,
        ]);
    }
}
