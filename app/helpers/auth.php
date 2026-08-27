<?php
// ASENTRA SPK — Authentication & authorization helpers

declare(strict_types=1);

/**
 * Get currently authenticated user from session, or null.
 *
 * @return array<string, mixed>|null
 */
function currentUser(): ?array
{
    if (empty($_SESSION['user'])) {
        return null;
    }
    return is_array($_SESSION['user']) ? $_SESSION['user'] : null;
}

/**
 * Check if user is authenticated.
 */
function isAuthenticated(): bool
{
    return currentUser() !== null;
}

/**
 * Check if current user has the given role.
 */
function hasRole(string $role): bool
{
    $user = currentUser();
    return $user !== null && ($user['role'] ?? '') === $role;
}

/**
 * Require authentication. Redirect to login if not logged in.
 */
function requireAuth(): void
{
    if (!isAuthenticated()) {
        redirect('/login');
    }
}

/**
 * Require a specific role. Redirect to login if not authenticated,
 * or abort with 403 if wrong role.
 */
function requireRole(string $role): void
{
    if (!isAuthenticated()) {
        $_SESSION['intended_url'] = currentRoute();
        redirect('/login');
    }

    if (!hasRole($role)) {
        abortForbidden();
    }
}

/**
 * Require admin role.
 */
function requireAdmin(): void
{
    requireRole('admin');
}

/**
 * Require owner role.
 */
function requireOwner(): void
{
    requireRole('owner');
}

/**
 * Abort with 403 Forbidden.
 */
function abortForbidden(): never
{
    http_response_code(403);
    render('errors/403', ['title' => 'Akses Ditolak']);
    exit;
}

/**
 * Set the authenticated user in session.
 *
 * @param array<string, mixed> $user
 */
function setUserSession(array $user): void
{
    $_SESSION['user'] = $user;
}

/**
 * Clear the authenticated user from session.
 */
function clearUserSession(): void
{
    unset($_SESSION['user']);
}

/**
 * Regenerate session ID to prevent session fixation.
 */
function regenerateSession(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
}

/**
 * Generate or return a CSRF token.
 */
function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Render hidden CSRF input field.
 */
function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Validate CSRF token from request.
 */
function csrfVerify(): bool
{
    $token = $_POST['csrf_token'] ?? '';
    $expected = $_SESSION['csrf_token'] ?? '';
    return $token !== '' && hash_equals($expected, $token);
}

/**
 * Verify CSRF token or abort with 403.
 */
function csrfCheck(): void
{
    if (!isPost()) {
        return;
    }

    if (!csrfVerify()) {
        http_response_code(403);
        exit('Permintaan tidak valid (CSRF).');
    }
}
