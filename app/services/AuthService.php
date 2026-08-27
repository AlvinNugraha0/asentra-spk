<?php
// ASENTRA SPK — Authentication service

declare(strict_types=1);

namespace App\Services;

use App\Models\User;

class AuthService
{
    /**
     * Attempt to authenticate a user.
     *
     * @return array{success: bool, user?: array<string, mixed>, error?: string}
     */
    public function login(string $username, string $password): array
    {
        $username = trim($username);

        if ($username === '' || $password === '') {
            return ['success' => false, 'error' => 'Username dan password wajib diisi.'];
        }

        $user = User::findByUsername($username);

        if ($user === null) {
            return ['success' => false, 'error' => 'Username atau password salah.'];
        }

        if (!password_verify($password, $user['password'])) {
            return ['success' => false, 'error' => 'Username atau password salah.'];
        }

        // Re-hash if necessary
        if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            // Optional: update password hash in DB. Skipped to avoid extra model method in foundation.
        }

        return ['success' => true, 'user' => $user];
    }
}
