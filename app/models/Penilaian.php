<?php
// ASENTRA SPK — Penilaian model

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use RuntimeException;

class Penilaian
{
    public static function all(string $periode = '', string $search = ''): array
    {
        $sql = 'SELECT p.*, t.kode_teknisi, t.nama AS nama_teknisi, t.status AS status_teknisi, u.nama AS nama_user
                FROM tb_penilaian p
                JOIN tb_teknisi t ON t.id = p.teknisi_id
                LEFT JOIN tb_user u ON u.id = p.created_by
                WHERE 1=1';
        $params = [];

        if ($periode !== '') {
            $sql .= ' AND p.periode = ?';
            $params[] = $periode;
        }

        if ($search !== '') {
            $sql .= ' AND (t.kode_teknisi LIKE ? OR t.nama LIKE ?)';
            $like = '%' . $search . '%';
            $params[] = $like;
            $params[] = $like;
        }

        $sql .= ' ORDER BY p.periode DESC, LENGTH(t.kode_teknisi) ASC, t.kode_teknisi ASC';

        return Database::query($sql, $params)->fetchAll();
    }

    public static function findById(int $id): ?array
    {
        $sql = 'SELECT p.*, t.kode_teknisi, t.nama AS nama_teknisi
                FROM tb_penilaian p
                JOIN tb_teknisi t ON t.id = p.teknisi_id
                WHERE p.id = ? LIMIT 1';
        $row = Database::query($sql, [$id])->fetch();
        return $row ?: null;
    }

    public static function exists(int $teknisiId, string $periode, ?int $excludeId = null): bool
    {
        $sql = 'SELECT COUNT(*) AS c FROM tb_penilaian WHERE teknisi_id = ? AND periode = ?';
        $params = [$teknisiId, $periode];

        if ($excludeId !== null) {
            $sql .= ' AND id != ?';
            $params[] = $excludeId;
        }

        return (int) Database::query($sql, $params)->fetch()['c'] > 0;
    }

    public static function create(array $data): int
    {
        Database::query(
            'INSERT INTO tb_penilaian (teknisi_id, periode, c1, c2, c3, created_by) VALUES (?, ?, ?, ?, ?, ?)',
            [$data['teknisi_id'], $data['periode'], $data['c1'], $data['c2'], $data['c3'], $data['created_by']]
        );
        return (int) Database::getConnection()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        // Phase 6F: LOCK — confirmed evaluations cannot be modified.
        $existing = self::findById($id);
        if ($existing !== null && ($existing['status_data'] ?? '') === 'confirmed') {
            throw new RuntimeException(
                'Data penilaian ini sudah dikonfirmasi secara resmi dan tidak dapat diubah.'
            );
        }

        Database::query(
            'UPDATE tb_penilaian SET teknisi_id = ?, periode = ?, c1 = ?, c2 = ?, c3 = ? WHERE id = ?',
            [$data['teknisi_id'], $data['periode'], $data['c1'], $data['c2'], $data['c3'], $id]
        );
    }

    public static function delete(int $id): void
    {
        // Phase 6F: LOCK — confirmed evaluations cannot be deleted.
        $existing = self::findById($id);
        if ($existing !== null && ($existing['status_data'] ?? '') === 'confirmed') {
            throw new RuntimeException(
                'Data penilaian ini sudah dikonfirmasi secara resmi dan tidak dapat dihapus.'
            );
        }

        Database::query('DELETE FROM tb_penilaian WHERE id = ?', [$id]);
    }

    public static function count(): int
    {
        return (int) Database::query('SELECT COUNT(*) AS c FROM tb_penilaian')->fetch()['c'];
    }

    public static function countByPeriode(string $periode): int
    {
        return (int) Database::query('SELECT COUNT(*) AS c FROM tb_penilaian WHERE periode = ?', [$periode])->fetch()['c'];
    }

    public static function countPeriode(): int
    {
        return (int) Database::query('SELECT COUNT(DISTINCT periode) AS c FROM tb_penilaian')->fetch()['c'];
    }

    /**
     * @return array<int, string>
     */
    public static function periods(): array
    {
        $stmt = Database::query('SELECT DISTINCT periode FROM tb_penilaian ORDER BY periode DESC');
        return array_column($stmt->fetchAll(), 'periode');
    }

    public static function latestPeriode(): ?string
    {
        $row = Database::query('SELECT periode FROM tb_penilaian ORDER BY periode DESC LIMIT 1')->fetch();
        return $row['periode'] ?? null;
    }

    public static function recent(int $limit = 5): array
    {
        $sql = 'SELECT p.*, t.kode_teknisi, t.nama AS nama_teknisi
                FROM tb_penilaian p
                JOIN tb_teknisi t ON t.id = p.teknisi_id
                ORDER BY p.created_at DESC
                LIMIT ?';
        return Database::query($sql, [$limit])->fetchAll();
    }

    /**
     * Get all active technicians with their evaluation status for a given period.
     * Returns rows for ALL active technicians — those with penilaian have their values,
     * those without have NULL c1/c2/c3 fields and status 'belum_dinilai'.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function allWithStatus(string $periode, string $search = '', string $statusFilter = ''): array
    {
        $sql = 'SELECT t.id AS teknisi_id, t.kode_teknisi, t.nama AS nama_teknisi,
                       p.id AS penilaian_id, p.periode, p.c1, p.c2, p.c3,
                       p.created_at, p.created_by,
                       u.nama AS nama_user,
                       h.nilai_preferensi, h.ranking,
                       CASE WHEN p.id IS NOT NULL THEN \'sudah_dinilai\' ELSE \'belum_dinilai\' END AS status_penilaian
                FROM tb_teknisi t
                LEFT JOIN tb_penilaian p ON p.teknisi_id = t.id AND p.periode = ?
                LEFT JOIN tb_user u ON u.id = p.created_by
                LEFT JOIN tb_hasil h ON h.penilaian_id = p.id
                WHERE t.status = \'active\'';
        $params = [$periode];

        if ($search !== '') {
            $sql .= ' AND (t.kode_teknisi LIKE ? OR t.nama LIKE ?)';
            $like = '%' . $search . '%';
            $params[] = $like;
            $params[] = $like;
        }

        if ($statusFilter === 'sudah_dinilai') {
            $sql .= ' AND p.id IS NOT NULL';
        } elseif ($statusFilter === 'belum_dinilai') {
            $sql .= ' AND p.id IS NULL';
        }

        $sql .= ' ORDER BY LENGTH(t.kode_teknisi) ASC, t.kode_teknisi ASC';

        return Database::query($sql, $params)->fetchAll();
    }

    /**
     * Find a penilaian with its SAW result data (normalisasi, kontribusi, ranking).
     *
     * @return array<string, mixed>|null
     */
    public static function findDetailWithSaw(int $id): ?array
    {
        $sql = 'SELECT p.*, t.kode_teknisi, t.nama AS nama_teknisi,
                       u.nama AS nama_user,
                       h.nilai_c1_normalisasi, h.nilai_c2_normalisasi, h.nilai_c3_normalisasi,
                       h.kontribusi_c1, h.kontribusi_c2, h.kontribusi_c3,
                       h.nilai_preferensi, h.ranking
                FROM tb_penilaian p
                JOIN tb_teknisi t ON t.id = p.teknisi_id
                LEFT JOIN tb_user u ON u.id = p.created_by
                LEFT JOIN tb_hasil h ON h.penilaian_id = p.id
                WHERE p.id = ? LIMIT 1';
        $row = Database::query($sql, [$id])->fetch();
        return $row ?: null;
    }

    /**
     * Count how many technicians have been evaluated in a given period.
     */
    public static function countEvaluatedByPeriode(string $periode): int
    {
        return (int) Database::query(
            'SELECT COUNT(DISTINCT teknisi_id) AS c FROM tb_penilaian WHERE periode = ?',
            [$periode]
        )->fetch()['c'];
    }

    // ponytail: single clean query for criteria averages
    public static function getCriteriaAverages(?string $periode = null): array
    {
        $periode = $periode ?? self::latestPeriode();
        if (!$periode) {
            return ['c1' => 0.0, 'c2' => 0.0, 'c3' => 0.0, 'count' => 0, 'periode' => null];
        }
        $row = Database::query(
            'SELECT AVG(c1) AS avg_c1, AVG(c2) AS avg_c2, AVG(c3) AS avg_c3, COUNT(*) AS c FROM tb_penilaian WHERE periode = ?',
            [$periode]
        )->fetch();
        return [
            'c1' => round((float) ($row['avg_c1'] ?? 0), 2),
            'c2' => round((float) ($row['avg_c2'] ?? 0), 2),
            'c3' => round((float) ($row['avg_c3'] ?? 0), 2),
            'count' => (int) ($row['c'] ?? 0),
            'periode' => $periode,
        ];
    }

    /**
     * Get all evaluation records for a specific V2 period ID.
     *
     * @param int $periodeId
     * @return array<int, array<string, mixed>>
     */
    public static function findByPeriodeId(int $periodeId): array
    {
        $sql = 'SELECT p.*, t.kode_teknisi, t.nama AS nama_teknisi, t.status AS status_teknisi,
                       u.nama AS nama_user, oc.nama AS confirmed_by_nama
                FROM tb_penilaian p
                JOIN tb_teknisi t ON t.id = p.teknisi_id
                LEFT JOIN tb_user u ON u.id = p.created_by
                LEFT JOIN tb_user oc ON oc.id = p.confirmed_by
                WHERE p.id_periode = ?
                ORDER BY LENGTH(t.kode_teknisi) ASC, t.kode_teknisi ASC';
        return Database::query($sql, [$periodeId])->fetchAll();
    }

    /**
     * Alias for findByPeriodeId().
     *
     * @param int $periodeId
     * @return array<int, array<string, mixed>>
     */
    public static function findByPeriode(int $periodeId): array
    {
        return self::findByPeriodeId($periodeId);
    }

    /**
     * Tabulation rows for a period (Phase 6C).
     *
     * One row per evaluated technician with derived C1/C2/C3 values and their
     * contribution to the final SAW preference value, sourced entirely from
     * tb_penilaian (no hardcoded values, no recomputation of formulas here).
     *
     * Weights follow SawEngineV2::DEFAULT_WEIGHTS (0.30/0.40/0.30) as the
     * preview contribution shown to the Owner before SAW is actually run.
     * The authoritative SAW computation remains in SawEngineV2/SawServiceV2
     * and is NOT duplicated here.
     *
     * @return array<int, array{no: int, teknisi_id: int, kode_teknisi: string, nama_teknisi: string, c1: float, c2: float, c3: float, kontribusi_c1: float, kontribusi_c2: float, kontribusi_c3: float, vi: float, status_data: string, jumlah_bulan_c1: int, jumlah_bulan_c2: int, jumlah_bulan_c3: int, warning: ?string, confirmed_by_nama: ?string}>
     */
    public static function tabulasiByPeriode(int $periodeId): array
    {
        $rows = self::findByPeriodeId($periodeId);

        $w1 = 0.30;
        $w2 = 0.40;
        $w3 = 0.30;

        $out = [];
        $no = 0;
        foreach ($rows as $r) {
            $no++;
            $c1 = (float) ($r['c1'] ?? 0);
            $c2 = (float) ($r['c2'] ?? 0);
            $c3 = (float) ($r['c3'] ?? 0);

            // Preview contributions: weighted value before normalization.
            // Raw indicator scores are already in the same scale, so this is a
            // transparent weighted sum — Owner sees WHY an indicator matters.
            $k1 = $c1 * $w1;
            $k2 = $c2 * $w2;
            $k3 = $c3 * $w3;

            $out[] = [
                'no'                => $no,
                'teknisi_id'        => (int) $r['teknisi_id'],
                'kode_teknisi'      => (string) ($r['kode_teknisi'] ?? ''),
                'nama_teknisi'      => (string) ($r['nama_teknisi'] ?? ''),
                'c1'                => $c1,
                'c2'                => $c2,
                'c3'                => $c3,
                'kontribusi_c1'     => $k1,
                'kontribusi_c2'     => $k2,
                'kontribusi_c3'     => $k3,
                'vi'                => $k1 + $k2 + $k3,
                'status_data'       => (string) ($r['status_data'] ?? 'draft'),
                'jumlah_bulan_c1'   => (int) ($r['jumlah_bulan_c1'] ?? 0),
                'jumlah_bulan_c2'   => (int) ($r['jumlah_bulan_c2'] ?? 0),
                'jumlah_bulan_c3'   => (int) ($r['jumlah_bulan_c3'] ?? 0),
                'warning'           => $r['warning'] ?? null,
                'confirmed_by_nama' => $r['confirmed_by_nama'] ?? null,
            ];
        }

        return $out;
    }

    /**
     * Find single evaluation record by period ID and technician ID.
     *
     * @param int $periodeId
     * @param int $teknisiId
     * @return array<string, mixed>|null
     */
    public static function findByPeriodeAndTeknisi(int $periodeId, int $teknisiId): ?array
    {
        $sql = 'SELECT p.*, t.kode_teknisi, t.nama AS nama_teknisi,
                       u.nama AS nama_user, oc.nama AS confirmed_by_nama
                FROM tb_penilaian p
                JOIN tb_teknisi t ON t.id = p.teknisi_id
                LEFT JOIN tb_user u ON u.id = p.created_by
                LEFT JOIN tb_user oc ON oc.id = p.confirmed_by
                WHERE p.id_periode = ? AND p.teknisi_id = ?
                LIMIT 1';
        $row = Database::query($sql, [$periodeId, $teknisiId])->fetch();
        return $row ?: null;
    }

    /**
     * Check if a period has already been confirmed by Owner.
     */
    public static function isPeriodConfirmed(int $periodeId): bool
    {
        $sql = 'SELECT COUNT(*) AS c FROM tb_penilaian 
                WHERE id_periode = ? AND status_data = "confirmed"';
        $count = (int) Database::query($sql, [$periodeId])->fetch()['c'];
        return $count > 0;
    }

    /**
     * Confirm all assessments for a period.
     *
     * @param int $periodeId
     * @param int $ownerId
     * @return int Number of rows confirmed
     */
    public static function confirmPeriod(int $periodeId, int $ownerId): int
    {
        $stmt = Database::query(
            'UPDATE tb_penilaian
             SET status_data = "confirmed",
                 confirmed_by = ?,
                 confirmed_at = CURRENT_TIMESTAMP
             WHERE id_periode = ? AND status_data != "legacy"',
            [$ownerId, $periodeId]
        );
        return $stmt->rowCount();
    }
}
