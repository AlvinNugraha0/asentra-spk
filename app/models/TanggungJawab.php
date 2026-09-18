<?php
// ASENTRA SPK — TanggungJawab model

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class TanggungJawab
{
    /**
     * Get all responsibility records, optionally filtered by period and technician.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function all(int $periodeId = 0, int $teknisiId = 0): array
    {
        $sql = 'SELECT tj.*, t.kode_teknisi, t.nama AS nama_teknisi, p.kode_periode, p.nama_periode
                FROM tb_tanggung_jawab tj
                JOIN tb_teknisi t ON t.id = tj.id_teknisi
                JOIN tb_periode_penilaian p ON p.id_periode = tj.id_periode
                WHERE 1=1';
        $params = [];

        if ($periodeId > 0) {
            $sql .= ' AND tj.id_periode = ?';
            $params[] = $periodeId;
        }

        if ($teknisiId > 0) {
            $sql .= ' AND tj.id_teknisi = ?';
            $params[] = $teknisiId;
        }

        $sql .= ' ORDER BY tj.id_periode DESC, tj.bulan ASC, LENGTH(t.kode_teknisi) ASC, t.kode_teknisi ASC';

        return Database::query($sql, $params)->fetchAll();
    }

    /**
     * Alias for all().
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getAll(int $periodeId = 0, int $teknisiId = 0): array
    {
        return self::all($periodeId, $teknisiId);
    }

    /**
     * Find responsibility record by ID.
     *
     * @return array<string, mixed>|null
     */
    public static function findById(int $id): ?array
    {
        $sql = 'SELECT tj.*, t.kode_teknisi, t.nama AS nama_teknisi, p.kode_periode, p.nama_periode
                FROM tb_tanggung_jawab tj
                JOIN tb_teknisi t ON t.id = tj.id_teknisi
                JOIN tb_periode_penilaian p ON p.id_periode = tj.id_periode
                WHERE tj.id_tanggung_jawab = ? LIMIT 1';
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
     * Find a single record by period, technician, and month.
     *
     * @return array<string, mixed>|null
     */
    public static function findByPeriodeTeknisiBulan(int $periodeId, int $teknisiId, int $bulan): ?array
    {
        $sql = 'SELECT * FROM tb_tanggung_jawab
                WHERE id_periode = ? AND id_teknisi = ? AND bulan = ?
                LIMIT 1';
        $row = Database::query($sql, [$periodeId, $teknisiId, $bulan])->fetch();
        return $row ?: null;
    }

    /**
     * Create a responsibility record.
     */
    public static function create(array $data): int
    {
        Database::query(
            'INSERT INTO tb_tanggung_jawab
                (id_periode, id_teknisi, bulan, perawatan_alat, efisiensi_material, inisiatif, kepatuhan_prosedur)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                $data['id_periode'],
                $data['id_teknisi'],
                $data['bulan'],
                $data['perawatan_alat'],
                $data['efisiensi_material'],
                $data['inisiatif'],
                $data['kepatuhan_prosedur'],
            ]
        );
        return (int) Database::getConnection()->lastInsertId();
    }

    /**
     * Update a responsibility record.
     */
    public static function update(int $id, array $data): void
    {
        Database::query(
            'UPDATE tb_tanggung_jawab
             SET perawatan_alat = ?, efisiensi_material = ?, inisiatif = ?, kepatuhan_prosedur = ?
             WHERE id_tanggung_jawab = ?',
            [
                $data['perawatan_alat'],
                $data['efisiensi_material'],
                $data['inisiatif'],
                $data['kepatuhan_prosedur'],
                $id,
            ]
        );
    }

    /**
     * Insert or update a record on duplicate unique key (id_periode, id_teknisi, bulan).
     */
    public static function upsert(array $data): void
    {
        Database::query(
            'INSERT INTO tb_tanggung_jawab
                (id_periode, id_teknisi, bulan, perawatan_alat, efisiensi_material, inisiatif, kepatuhan_prosedur)
             VALUES (?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                perawatan_alat = VALUES(perawatan_alat),
                efisiensi_material = VALUES(efisiensi_material),
                inisiatif = VALUES(inisiatif),
                kepatuhan_prosedur = VALUES(kepatuhan_prosedur)',
            [
                $data['id_periode'],
                $data['id_teknisi'],
                $data['bulan'],
                $data['perawatan_alat'],
                $data['efisiensi_material'],
                $data['inisiatif'],
                $data['kepatuhan_prosedur'],
            ]
        );
    }

    /**
     * Delete a responsibility record.
     */
    public static function delete(int $id): void
    {
        Database::query('DELETE FROM tb_tanggung_jawab WHERE id_tanggung_jawab = ?', [$id]);
    }
}
