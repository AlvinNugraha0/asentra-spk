<?php
// ASENTRA SPK — Hasil (SAW result) model

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

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

    public static function deleteByPeriode(string $periode): void
    {
        Database::query('DELETE FROM tb_hasil WHERE periode = ?', [$periode]);
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
                    penilaian_id, teknisi_id, periode,
                    nilai_c1_normalisasi, nilai_c2_normalisasi, nilai_c3_normalisasi,
                    kontribusi_c1, kontribusi_c2, kontribusi_c3,
                    nilai_preferensi, ranking
                ) VALUES ';

        $placeholders = [];
        $params = [];
        foreach ($rows as $row) {
            $placeholders[] = '(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';
            $params[] = $row['penilaian_id'];
            $params[] = $row['teknisi_id'];
            $params[] = $row['periode'];
            $params[] = $row['normal_c1'];
            $params[] = $row['normal_c2'];
            $params[] = $row['normal_c3'];
            $params[] = $row['kontribusi_c1'];
            $params[] = $row['kontribusi_c2'];
            $params[] = $row['kontribusi_c3'];
            $params[] = $row['nilai_preferensi'];
            $params[] = $row['ranking'];
        }

        Database::query($sql . implode(', ', $placeholders), $params);
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
}
