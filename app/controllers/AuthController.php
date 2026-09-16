+<?php
// ASENTRA SPK — Authentication controller

declare(strict_types=1);

namespace App\Controllers;

use App\Services\AuthService;

class AuthController
{
    private AuthService $authService;

    public function __construct()
    {
        $this->authService = new AuthService();
    }

    public function showLogin(): void
    {
        if (isAuthenticated()) {
            redirect(currentUser()['role'] === 'admin' ? '/admin/dashboard' : '/owner/dashboard');
        }

        render('auth/login', [
            'title' => 'Login - ' . APP_NAME,
            'error' => $_SESSION['login_error'] ?? null,
        ]);

        unset($_SESSION['login_error']);
    }

    public function login(): void
    {
        csrfCheck();

        $username = input('username', '');
        $password = input('password', '');

        $result = $this->authService->login((string) $username, (string) $password);

        if (!$result['success']) {
            $_SESSION['login_error'] = $result['error'];
            redirect('/login');
        }

        $user = $result['user'];
        unset($user['password']);

        regenerateSession();
        setUserSession($user);

        setFlash('Login berhasil.', 'success');
        redirect($user['role'] === 'admin' ? '/admin/dashboard' : '/owner/dashboard');
    }

    public function logout(): void
    {
        csrfCheck();
        clearUserSession();
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(),
                    '',
                    [
                        'expires'  => time() - 42000,
                        'path'     => $params['path'],
                        'domain'   => $params['domain'],
                        'secure'   => $params['secure'],
                        'httponly' => $params['httponly'],
                        'samesite' => $params['samesite'] ?? 'Lax',
                    ]
                );
            }
            session_destroy();
        }
        redirect('/login');
    }
}
