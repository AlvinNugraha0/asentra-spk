<?php
// ASENTRA SPK — Kedisiplinan model

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class Kedisiplinan
{
    /**
     * Get all discipline records, optionally filtered by period and technician.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function all(int $periodeId = 0, int $teknisiId = 0): array
    {
        $sql = 'SELECT k.*, t.kode_teknisi, t.nama AS nama_teknisi, p.kode_periode, p.nama_periode
                FROM tb_kedisiplinan k
                JOIN tb_teknisi t ON t.id = k.id_teknisi
                JOIN tb_periode_penilaian p ON p.id_periode = k.id_periode
                WHERE 1=1';
        $params = [];

        if ($periodeId > 0) {
            $sql .= ' AND k.id_periode = ?';
            $params[] = $periodeId;
        }

        if ($teknisiId > 0) {
            $sql .= ' AND k.id_teknisi = ?';
            $params[] = $teknisiId;
        }

        $sql .= ' ORDER BY k.id_periode DESC, k.bulan ASC, LENGTH(t.kode_teknisi) ASC, t.kode_teknisi ASC';

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
     * Find discipline record by ID.
     *
     * @return array<string, mixed>|null
     */
    public static function findById(int $id): ?array
    {
        $sql = 'SELECT k.*, t.kode_teknisi, t.nama AS nama_teknisi, p.kode_periode, p.nama_periode
                FROM tb_kedisiplinan k
                JOIN tb_teknisi t ON t.id = k.id_teknisi
                JOIN tb_periode_penilaian p ON p.id_periode = k.id_periode
                WHERE k.id_kedisiplinan = ? LIMIT 1';
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
     * Find records for a specific period and technician.
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
        $sql = 'SELECT * FROM tb_kedisiplinan
                WHERE id_periode = ? AND id_teknisi = ? AND bulan = ?
                LIMIT 1';
        $row = Database::query($sql, [$periodeId, $teknisiId, $bulan])->fetch();
        return $row ?: null;
    }

    /**
     * Create a discipline record.
     */
    public static function create(array $data): int
    {
        Database::query(
            'INSERT INTO tb_kedisiplinan
                (id_periode, id_teknisi, bulan, total_hari_kerja, hadir, sakit, izin, alpa, terlambat, pekerjaan_terjadwal, sesuai_jadwal)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $data['id_periode'],
                $data['id_teknisi'],
                $data['bulan'],
                $data['total_hari_kerja'] ?? 0,
                $data['hadir'] ?? 0,
                $data['sakit'] ?? 0,
                $data['izin'] ?? 0,
                $data['alpa'] ?? 0,
                $data['terlambat'] ?? 0,
                $data['pekerjaan_terjadwal'] ?? 0,
                $data['sesuai_jadwal'] ?? 0,
            ]
        );
        return (int) Database::getConnection()->lastInsertId();
    }

    /**
     * Update a discipline record.
     */
    public static function update(int $id, array $data): void
    {
        Database::query(
            'UPDATE tb_kedisiplinan
             SET total_hari_kerja = ?, hadir = ?, sakit = ?, izin = ?, alpa = ?,
                 terlambat = ?, pekerjaan_terjadwal = ?, sesuai_jadwal = ?
             WHERE id_kedisiplinan = ?',
            [
                $data['total_hari_kerja'] ?? 0,
                $data['hadir'] ?? 0,
                $data['sakit'] ?? 0,
                $data['izin'] ?? 0,
                $data['alpa'] ?? 0,
                $data['terlambat'] ?? 0,
                $data['pekerjaan_terjadwal'] ?? 0,
                $data['sesuai_jadwal'] ?? 0,
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
            'INSERT INTO tb_kedisiplinan
                (id_periode, id_teknisi, bulan, total_hari_kerja, hadir, sakit, izin, alpa, terlambat, pekerjaan_terjadwal, sesuai_jadwal)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                total_hari_kerja = VALUES(total_hari_kerja),
                hadir = VALUES(hadir),
                sakit = VALUES(sakit),
                izin = VALUES(izin),
                alpa = VALUES(alpa),
                terlambat = VALUES(terlambat),
                pekerjaan_terjadwal = VALUES(pekerjaan_terjadwal),
                sesuai_jadwal = VALUES(sesuai_jadwal)',
            [
                $data['id_periode'],
                $data['id_teknisi'],
                $data['bulan'],
                $data['total_hari_kerja'] ?? 0,
                $data['hadir'] ?? 0,
                $data['sakit'] ?? 0,
                $data['izin'] ?? 0,
                $data['alpa'] ?? 0,
                $data['terlambat'] ?? 0,
                $data['pekerjaan_terjadwal'] ?? 0,
                $data['sesuai_jadwal'] ?? 0,
            ]
        );
    }

    /**
     * Delete a discipline record.
     */
    public static function delete(int $id): void
    {
        Database::query('DELETE FROM tb_kedisiplinan WHERE id_kedisiplinan = ?', [$id]);
    }
}
