<?php
// ASENTRA SPK — Teknisi model

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class Teknisi
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function all(string $search = '', string $status = ''): array
    {
        $sql = 'SELECT * FROM tb_teknisi WHERE 1=1';
        $params = [];

        if ($search !== '') {
            $sql .= ' AND (kode_teknisi LIKE ? OR nama LIKE ? OR keterangan LIKE ?)';
            $like = '%' . $search . '%';
            $params = [$like, $like, $like];
        }

        if ($status !== '') {
            $sql .= ' AND status = ?';
            $params[] = $status;
        }

        $sql .= ' ORDER BY kode_teknisi ASC';

        return Database::query($sql, $params)->fetchAll();
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function findById(int $id): ?array
    {
        $stmt = Database::query('SELECT * FROM tb_teknisi WHERE id = ? LIMIT 1', [$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function findByKode(string $kode): ?array
    {
        $stmt = Database::query('SELECT * FROM tb_teknisi WHERE kode_teknisi = ? LIMIT 1', [$kode]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function create(array $data): int
    {
        Database::query(
            'INSERT INTO tb_teknisi (kode_teknisi, nama, status, keterangan) VALUES (?, ?, ?, ?)',
            [$data['kode_teknisi'], $data['nama'], $data['status'], $data['keterangan'] ?? null]
        );
        return (int) Database::getConnection()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        Database::query(
            'UPDATE tb_teknisi SET kode_teknisi = ?, nama = ?, status = ?, keterangan = ? WHERE id = ?',
            [$data['kode_teknisi'], $data['nama'], $data['status'], $data['keterangan'] ?? null, $id]
        );
    }

    public static function delete(int $id): void
    {
        Database::query('DELETE FROM tb_teknisi WHERE id = ?', [$id]);
    }

    public static function toggleStatus(int $id): void
    {
        Database::query(
            'UPDATE tb_teknisi SET status = IF(status = "active", "inactive", "active") WHERE id = ?',
            [$id]
        );
    }

    public static function countActive(): int
    {
        return (int) Database::query('SELECT COUNT(*) AS c FROM tb_teknisi WHERE status = "active"')->fetch()['c'];
    }

    public static function hasRelatedRecords(int $id): bool
    {
        $penilaian = (int) Database::query('SELECT COUNT(*) AS c FROM tb_penilaian WHERE teknisi_id = ?', [$id])->fetch()['c'];
        $hasil = (int) Database::query('SELECT COUNT(*) AS c FROM tb_hasil WHERE teknisi_id = ?', [$id])->fetch()['c'];
        return $penilaian > 0 || $hasil > 0;
    }
}
