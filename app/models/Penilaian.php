<?php
// ASENTRA SPK — Penilaian model

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

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
        Database::query(
            'UPDATE tb_penilaian SET teknisi_id = ?, periode = ?, c1 = ?, c2 = ?, c3 = ? WHERE id = ?',
            [$data['teknisi_id'], $data['periode'], $data['c1'], $data['c2'], $data['c3'], $id]
        );
    }

    public static function delete(int $id): void
    {
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
}
