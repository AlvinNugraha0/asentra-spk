<?php
// ASENTRA SPK — URL helpers

declare(strict_types=1);

/**
 * Build a full URL to an application route.
 */
function route(string $path = '', array $query = []): string
{
    $base = rtrim(APP_URL, '/');
    $path = '/' . ltrim($path, '/');
    $url = $base . $path;

    if (!empty($query)) {
        $url .= '?' . http_build_query($query);
    }

    return $url;
}

/**
 * Build an asset URL (CSS/JS/images inside public/).
 */
function asset(string $path): string
{
    $base = rtrim(APP_URL, '/');
    $path = ltrim($path, '/');
    return $base . '/' . $path;
}

/**
 * Get the current request path relative to the app root.
 */
function currentRoute(): string
{
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    $base = parse_url(APP_URL, PHP_URL_PATH) ?? '';

    if ($base !== '' && str_starts_with($uri, $base)) {
        $uri = substr($uri, strlen($base));
    }

    $uri = parse_url($uri, PHP_URL_PATH) ?? '/';
    return '/' . ltrim($uri, '/');
}

/**
 * Redirect and exit.
 */
function redirect(string $path): never
{
    header('Location: ' . route($path));
    exit;
}

/**
 * Return the requested HTTP method.
 */
function requestMethod(): string
{
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
}

/**
 * Check if request method matches.
 */
function isPost(): bool
{
    return requestMethod() === 'POST';
}

/**
 * Safely get an input value from POST.
 */
function input(string $key, mixed $default = null): mixed
{
    return $_POST[$key] ?? $default;
}
