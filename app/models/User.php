<?php
// ASENTRA SPK — User model

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

class User
{
    /**
     * Find user by username.
     *
     * @return array<string, mixed>|null
     */
    public static function findByUsername(string $username): ?array
    {
        $stmt = Database::query(
            'SELECT * FROM tb_user WHERE username = ? AND status = "active" LIMIT 1',
            [$username]
        );
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Find user by ID.
     *
     * @return array<string, mixed>|null
     */
    public static function findById(int $id): ?array
    {
        $stmt = Database::query(
            'SELECT * FROM tb_user WHERE id = ? LIMIT 1',
            [$id]
        );
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
