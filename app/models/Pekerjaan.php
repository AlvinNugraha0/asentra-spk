<?php
// ASENTRA SPK — Pekerjaan model

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class Pekerjaan
{
    /**
     * Get all work quality records, optionally filtered by period, technician, and month.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function all(int $periodeId = 0, int $teknisiId = 0, int $bulan = 0): array
    {
        $sql = 'SELECT pk.*, t.kode_teknisi, t.nama AS nama_teknisi, p.kode_periode, p.nama_periode
                FROM tb_pekerjaan pk
                JOIN tb_teknisi t ON t.id = pk.id_teknisi
                JOIN tb_periode_penilaian p ON p.id_periode = pk.id_periode
                WHERE 1=1';
        $params = [];

        if ($periodeId > 0) {
            $sql .= ' AND pk.id_periode = ?';
            $params[] = $periodeId;
        }

        if ($teknisiId > 0) {
            $sql .= ' AND pk.id_teknisi = ?';
            $params[] = $teknisiId;
        }

        if ($bulan > 0) {
            $sql .= ' AND pk.bulan = ?';
            $params[] = $bulan;
        }

        $sql .= ' ORDER BY pk.tanggal DESC, pk.id_pekerjaan DESC';

        return Database::query($sql, $params)->fetchAll();
    }

    /**
     * Alias for all().
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getAll(int $periodeId = 0, int $teknisiId = 0, int $bulan = 0): array
    {
        return self::all($periodeId, $teknisiId, $bulan);
    }

    /**
     * Find work record by ID.
     *
     * @return array<string, mixed>|null
     */
    public static function findById(int $id): ?array
    {
        $sql = 'SELECT pk.*, t.kode_teknisi, t.nama AS nama_teknisi, p.kode_periode, p.nama_periode
                FROM tb_pekerjaan pk
                JOIN tb_teknisi t ON t.id = pk.id_teknisi
                JOIN tb_periode_penilaian p ON p.id_periode = pk.id_periode
                WHERE pk.id_pekerjaan = ? LIMIT 1';
        $row = Database::query($sql, [$id])->fetch();
        return $row ?: null;
    }

    /**
     * Alias for findById().
     *
     * @return array<string, mixed>|null
     */
    public static function find(int $id): ?array
    {
        return self::findById($id);
    }

    /**
     * Get records for a specific period and technician.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function findByPeriodeAndTeknisi(int $periodeId, int $teknisiId): array
    {
        return self::all($periodeId, $teknisiId);
    }

    /**
     * Create a work quality record.
     */
    public static function create(array $data): int
    {
        Database::query(
            'INSERT INTO tb_pekerjaan
                (id_periode, id_teknisi, tanggal, bulan, nama_pekerjaan, rapi, presisi, sesuai_desain)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $data['id_periode'],
                $data['id_teknisi'],
                $data['tanggal'],
                $data['bulan'],
                $data['nama_pekerjaan'],
                $data['rapi'] ?? 0,
                $data['presisi'] ?? 0,
                $data['sesuai_desain'] ?? 0,
            ]
        );
        return (int) Database::getConnection()->lastInsertId();
    }

    /**
     * Update a work quality record.
     */
    public static function update(int $id, array $data): void
    {
        Database::query(
            'UPDATE tb_pekerjaan
             SET tanggal = ?, bulan = ?, nama_pekerjaan = ?, rapi = ?, presisi = ?, sesuai_desain = ?
             WHERE id_pekerjaan = ?',
            [
                $data['tanggal'],
                $data['bulan'],
                $data['nama_pekerjaan'],
                $data['rapi'] ?? 0,
                $data['presisi'] ?? 0,
                $data['sesuai_desain'] ?? 0,
                $id,
            ]
        );
    }

    /**
     * Delete a work record.
     */
    public static function delete(int $id): void
    {
        Database::query('DELETE FROM tb_pekerjaan WHERE id_pekerjaan = ?', [$id]);
    }

    /**
     * Count total work records for period and technician.
     */
    public static function countByPeriodeAndTeknisi(int $periodeId, int $teknisiId): int
    {
        return (int) Database::query(
            'SELECT COUNT(*) AS c FROM tb_pekerjaan WHERE id_periode = ? AND id_teknisi = ?',
            [$periodeId, $teknisiId]
        )->fetch()['c'];
    }
}
