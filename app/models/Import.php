<?php
// ASENTRA SPK — Import model

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class Import
{
    /**
     * Get all import logs, optionally filtered by period ID.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function all(int $periodeId = 0): array
    {
        $sql = 'SELECT i.*, p.kode_periode, p.nama_periode, u.nama AS user_nama
                FROM tb_import i
                JOIN tb_periode_penilaian p ON p.id_periode = i.id_periode
                LEFT JOIN tb_user u ON u.id = i.id_user
                WHERE 1=1';
        $params = [];

        if ($periodeId > 0) {
            $sql .= ' AND i.id_periode = ?';
            $params[] = $periodeId;
        }

        $sql .= ' ORDER BY i.id_import DESC';

        return Database::query($sql, $params)->fetchAll();
    }

    /**
     * Alias for all().
     *
     * @return array<int, array<string, mixed>>
     */
    public static function getAll(int $periodeId = 0): array
    {
        return self::all($periodeId);
    }

    /**
     * Find import record by ID.
     *
     * @return array<string, mixed>|null
     */
    public static function findById(int $id): ?array
    {
        $sql = 'SELECT i.*, p.kode_periode, p.nama_periode, u.nama AS user_nama
                FROM tb_import i
                JOIN tb_periode_penilaian p ON p.id_periode = i.id_periode
                LEFT JOIN tb_user u ON u.id = i.id_user
                WHERE i.id_import = ? LIMIT 1';
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
     * Get imports by period ID.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function findByPeriode(int $periodeId): array
    {
        return self::all($periodeId);
    }

    /**
     * Create an import log record.
     */
    public static function create(array $data): int
    {
        Database::query(
            'INSERT INTO tb_import
                (id_periode, nama_file, nama_file_asli, total_data, data_berhasil, data_gagal, status, pesan_error, id_user)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $data['id_periode'],
                $data['nama_file'],
                $data['nama_file_asli'],
                $data['total_data'] ?? 0,
                $data['data_berhasil'] ?? 0,
                $data['data_gagal'] ?? 0,
                $data['status'] ?? 'pending',
                $data['pesan_error'] ?? null,
                $data['id_user'] ?? null,
            ]
        );
        return (int) Database::getConnection()->lastInsertId();
    }

    /**
     * Update an import log record.
     */
    public static function update(int $id, array $data): void
    {
        Database::query(
            'UPDATE tb_import
             SET total_data = ?, data_berhasil = ?, data_gagal = ?, status = ?, pesan_error = ?
             WHERE id_import = ?',
            [
                $data['total_data'] ?? 0,
                $data['data_berhasil'] ?? 0,
                $data['data_gagal'] ?? 0,
                $data['status'],
                $data['pesan_error'] ?? null,
                $id,
            ]
        );
    }

    /**
     * Update import status and optional error message.
     */
    public static function updateStatus(int $id, string $status, ?string $pesanError = null): void
    {
        Database::query(
            'UPDATE tb_import SET status = ?, pesan_error = ? WHERE id_import = ?',
            [$status, $pesanError, $id]
        );
    }

    /**
     * Delete an import log.
     */
    public static function delete(int $id): void
    {
        Database::query('DELETE FROM tb_import WHERE id_import = ?', [$id]);
    }
}
