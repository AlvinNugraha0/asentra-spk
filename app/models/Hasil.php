<?php
// ASENTRA SPK — Hasil (SAW result) model

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Models\PeriodePenilaian;

class Hasil
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function byPeriode(string $periode): array
    {
        $sql = 'SELECT h.*, t.kode_teknisi, t.nama AS nama_teknisi, p.c1, p.c2, p.c3
                FROM tb_hasil h
                JOIN tb_teknisi t ON t.id = h.teknisi_id
                JOIN tb_penilaian p ON p.id = h.penilaian_id
                WHERE h.periode = ?
                ORDER BY h.ranking ASC, t.kode_teknisi ASC';
        return Database::query($sql, [$periode])->fetchAll();
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function findById(int $id): ?array
    {
        $sql = 'SELECT h.*, t.kode_teknisi, t.nama AS nama_teknisi, p.c1, p.c2, p.c3
                FROM tb_hasil h
                JOIN tb_teknisi t ON t.id = h.teknisi_id
                JOIN tb_penilaian p ON p.id = h.penilaian_id
                WHERE h.id = ? LIMIT 1';
        $row = Database::query($sql, [$id])->fetch();
        return $row ?: null;
    }

    /**
     * Delete SAW results for a period code.
     *
     * Phase 6G: the stored code may be either the legacy string ('2026-08'
     * in tb_penilaian, 'LEGACY-2026-08' in tb_hasil from the V1 seed) or the
     * full V2 quarter code. Try both spellings plus the id_periode lookup so
     * the legacy SawService can replace its own results.
     */
    public static function deleteByPeriode(string $periode): void
    {
        $codes = [$periode];
        if (!str_starts_with($periode, 'LEGACY-')) {
            $codes[] = 'LEGACY-' . $periode;
        }

        $placeholders = implode(',', array_fill(0, count($codes), '?'));
        Database::query("DELETE FROM tb_hasil WHERE periode IN ({$placeholders})", $codes);

        // Also clear by id_periode when the code matches a known period.
        $row = PeriodePenilaian::findByKode($periode);
        if ($row !== null) {
            Database::query('DELETE FROM tb_hasil WHERE id_periode = ?', [(int) $row['id_periode']]);
        }
    }

    /**
     * Delete SAW results for a specific period ID, with strict legacy protection.
     *
     * @param int $periodeId
     * @throws \RuntimeException
     */
    public static function deleteByPeriodeId(int $periodeId): void
    {
        // Safety: Do not delete legacy periods
        $periode = PeriodePenilaian::findById($periodeId);
        if ($periode && (($periode['status'] ?? '') === 'legacy' || str_starts_with((string) $periode['kode_periode'], 'LEGACY-'))) {
            throw new \RuntimeException("Tidak dapat menghapus hasil SAW untuk periode legacy.");
        }
        Database::query('DELETE FROM tb_hasil WHERE id_periode = ?', [$periodeId]);
    }

    /**
     * Get SAW results by period ID (V2).
     *
     * @param int $periodeId
     * @return array<int, array<string, mixed>>
     */
    public static function byPeriodeId(int $periodeId): array
    {
        $sql = 'SELECT h.*, t.kode_teknisi, t.nama AS nama_teknisi,
                       h.c1, h.c2, h.c3,
                       p.status_data, p.warning, p.jumlah_bulan_c1, p.jumlah_bulan_c2, p.jumlah_bulan_c3
                FROM tb_hasil h
                JOIN tb_teknisi t ON t.id = h.teknisi_id
                JOIN tb_penilaian p ON p.id = h.penilaian_id
                WHERE h.id_periode = ?
                ORDER BY h.ranking ASC, t.kode_teknisi ASC';
        return Database::query($sql, [$periodeId])->fetchAll();
    }

    /**
     * Count SAW results by period ID (V2).
     *
     * @param int $periodeId
     * @return int
     */
    public static function countByPeriodeId(int $periodeId): int
    {
        return (int) Database::query('SELECT COUNT(*) AS c FROM tb_hasil WHERE id_periode = ?', [$periodeId])->fetch()['c'];
    }

    /**
     * Resolve any submitted period selector value to result rows.
     *
     * Phase 6H: the ranking/report pages may receive either a V2 numeric
     * id_periode, a V2 quarter code ('Q1-2026') or a legacy V1 string code
     * ('2026-08' / 'LEGACY-2026-08'). Try the V2 paths first, then fall back
     * to the legacy string lookup so no existing link breaks.
     *
     * @param string|int $value
     * @return array<int, array<string, mixed>>
     */
    public static function byPeriodeFlexible(string|int $value): array
    {
        $value = trim((string) $value);

        // V2 numeric id_periode.
        if (preg_match('/^\d+$/', $value)) {
            $id = (int) $value;
            if ($id > 0) {
                return self::byPeriodeId($id);
            }
        }

        // V2 quarter code (non-legacy).
        $periode = PeriodePenilaian::findByKode($value);
        if ($periode !== null && ($periode['status'] ?? '') !== 'legacy') {
            return self::byPeriodeId((int) $periode['id_periode']);
        }

        // Legacy V1 string code ('2026-08' or 'LEGACY-2026-08').
        return self::byPeriode($value);
    }

    /**
     * Maximum criterion values across one V2 period.
     *
     * Phase 6H: used by the SAW detail page to display max(C1/C2/C3) directly
     * from tb_hasil instead of re-deriving it as c1/normalisasi (which loses
     * precision on rounded DB decimals and divides by zero when a normalized
     * value is 0).
     *
     * @return array{c1: float, c2: float, c3: float}
     */
    public static function maxCriteriaByPeriodeId(int $periodeId): array
    {
        $row = Database::query(
            'SELECT MAX(c1) AS max_c1, MAX(c2) AS max_c2, MAX(c3) AS max_c3
             FROM tb_hasil WHERE id_periode = ?',
            [$periodeId]
        )->fetch();

        return [
            'c1' => (float) ($row['max_c1'] ?? 0),
            'c2' => (float) ($row['max_c2'] ?? 0),
            'c3' => (float) ($row['max_c3'] ?? 0),
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    public static function insertBatch(array $rows): void
    {
        if (empty($rows)) {
            return;
        }

        $sql = 'INSERT INTO tb_hasil (
                    id_periode, penilaian_id, teknisi_id, periode,
                    c1, c2, c3,
                    nilai_c1_normalisasi, nilai_c2_normalisasi, nilai_c3_normalisasi,
                    kontribusi_c1, kontribusi_c2, kontribusi_c3,
                    bobot_c1, bobot_c2, bobot_c3,
                    nilai_preferensi, ranking
                ) VALUES ';

        $placeholders = [];
        $params = [];
        foreach ($rows as $row) {
            $placeholders[] = '(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
            $params[] = $row['id_periode'] ?? null;
            $params[] = $row['penilaian_id'] ?? $row['id'];
            $params[] = $row['teknisi_id'];
            $params[] = $row['periode'];
            $params[] = $row['c1'] ?? null;
            $params[] = $row['c2'] ?? null;
            $params[] = $row['c3'] ?? null;
            $params[] = $row['nilai_c1_normalisasi'] ?? $row['normal_c1'];
            $params[] = $row['nilai_c2_normalisasi'] ?? $row['normal_c2'];
            $params[] = $row['nilai_c3_normalisasi'] ?? $row['normal_c3'];
            $params[] = $row['kontribusi_c1'];
            $params[] = $row['kontribusi_c2'];
            $params[] = $row['kontribusi_c3'];
            $params[] = $row['bobot_c1'] ?? null;
            $params[] = $row['bobot_c2'] ?? null;
            $params[] = $row['bobot_c3'] ?? null;
            $params[] = $row['nilai_preferensi'];
            $params[] = $row['ranking'];
        }

        Database::query($sql . implode(', ', $placeholders), $params);

        // Fallback: Ensure any missing id_periode, c1-c3, or weights are linked from tb_penilaian and tb_kriteria
        Database::query('UPDATE tb_hasil h
                         JOIN tb_penilaian p ON p.id = h.penilaian_id
                         SET h.id_periode = COALESCE(h.id_periode, p.id_periode),
                             h.c1 = COALESCE(h.c1, p.c1),
                             h.c2 = COALESCE(h.c2, p.c2),
                             h.c3 = COALESCE(h.c3, p.c3)
                         WHERE h.id_periode IS NULL OR h.c1 IS NULL');
        Database::query("UPDATE tb_hasil h
                         SET h.bobot_c1 = COALESCE(h.bobot_c1, (SELECT bobot FROM tb_kriteria WHERE kode = 'C1')),
                             h.bobot_c2 = COALESCE(h.bobot_c2, (SELECT bobot FROM tb_kriteria WHERE kode = 'C2')),
                             h.bobot_c3 = COALESCE(h.bobot_c3, (SELECT bobot FROM tb_kriteria WHERE kode = 'C3'))
                         WHERE h.bobot_c1 IS NULL");
    }

    public static function countByPeriode(string $periode): int
    {
        return (int) Database::query('SELECT COUNT(*) AS c FROM tb_hasil WHERE periode = ?', [$periode])->fetch()['c'];
    }


    /**
     * @return array<int, string>
     */
    public static function periods(): array
    {
        $stmt = Database::query('SELECT DISTINCT periode FROM tb_hasil ORDER BY periode DESC');
        return array_column($stmt->fetchAll(), 'periode');
    }

    public static function latestPeriode(): ?string
    {
        $row = Database::query('SELECT periode FROM tb_hasil ORDER BY periode DESC LIMIT 1')->fetch();
        return $row['periode'] ?? null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function topByPeriode(string $periode): ?array
    {
        $rows = self::byPeriode($periode);
        return $rows[0] ?? null;
    }

    /**
     * @return array<string, mixed>
     */
    public static function getTrendSummary(): array
    {
        $sql = 'SELECT periode, AVG(nilai_preferensi) as avg_score
                FROM tb_hasil
                GROUP BY periode
                ORDER BY periode ASC';
        
        $trendData = Database::query($sql)->fetchAll();
        
        $latestAvg = null;
        $deltaLabel = '';
        $deltaType = 'neutral';
        
        if (count($trendData) > 0) {
            $latestAvg = round((float)$trendData[count($trendData) - 1]['avg_score'], 3);
            if (count($trendData) > 1) {
                $prevAvg = round((float)$trendData[count($trendData) - 2]['avg_score'], 3);
                $delta = $latestAvg - $prevAvg;
                
                if ($delta > 0) {
                    $deltaPercent = round(($delta / $prevAvg) * 100, 1);
                    $deltaLabel = "▲ {$deltaPercent}% dari periode sebelumnya";
                    $deltaType = 'positive';
                } elseif ($delta < 0) {
                    $deltaPercent = round((abs($delta) / $prevAvg) * 100, 1);
                    $deltaLabel = "▼ {$deltaPercent}% dari periode sebelumnya";
                    $deltaType = 'negative';
                } else {
                    $deltaLabel = "Tidak ada perubahan";
                    $deltaType = 'neutral';
                }
            }
        }
        
        return [
            'trendData' => $trendData,
            'latestAvg' => $latestAvg,
            'deltaLabel' => $deltaLabel,
            'deltaType' => $deltaType
        ];
    }
}
