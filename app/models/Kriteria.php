<?php
// ASENTRA SPK — Kriteria model

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class Kriteria
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function all(): array
    {
        return Database::query('SELECT * FROM tb_kriteria ORDER BY kode ASC')->fetchAll();
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function findById(int $id): ?array
    {
        $row = Database::query('SELECT * FROM tb_kriteria WHERE id = ? LIMIT 1', [$id])->fetch();
        return $row ?: null;
    }

    public static function update(int $id, array $data): void
    {
        Database::query(
            'UPDATE tb_kriteria SET nama_kriteria = ?, atribut = ?, bobot = ?, deskripsi = ? WHERE id = ?',
            [$data['nama_kriteria'], $data['atribut'], $data['bobot'], $data['deskripsi'] ?? null, $id]
        );
    }

    public static function totalBobot(): float
    {
        $row = Database::query('SELECT SUM(bobot) AS total FROM tb_kriteria')->fetch();
        return (float) ($row['total'] ?? 0);
    }
}
