<?php
// ASENTRA SPK — Application constants
// Loaded by public/index.php before request handling.

declare(strict_types=1);

if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}

// Base paths
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', realpath(__DIR__ . DS . '..'));
}
if (!defined('APP_PATH')) {
    define('APP_PATH', ROOT_PATH . DS . 'app');
}
if (!defined('CONFIG_PATH')) {
    define('CONFIG_PATH', ROOT_PATH . DS . 'config');
}
if (!defined('PUBLIC_PATH')) {
    define('PUBLIC_PATH', ROOT_PATH . DS . 'public');
}
if (!defined('DATABASE_PATH')) {
    define('DATABASE_PATH', ROOT_PATH . DS . 'database');
}

// Load environment variables from .env if present.
$envPath = ROOT_PATH . DS . '.env';
if (is_file($envPath) && is_readable($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        [$key, $value] = array_pad(explode('=', $line, 2), 2, '');
        $key = trim($key);
        $value = trim($value);
        if ($value !== '' && str_starts_with($value, '"') && str_ends_with($value, '"')) {
            $value = substr($value, 1, -1);
        }
        if ($key !== '' && !array_key_exists($key, $_ENV)) {
            $_ENV[$key] = $value;
        }
    }
}

function env(string $key, mixed $default = null): mixed
{
    return $_ENV[$key] ?? $default;
}

// Application
if (!defined('APP_NAME')) {
    define('APP_NAME', (string) env('APP_NAME', 'ASENTRA SPK'));
}
if (!defined('APP_ENV')) {
    define('APP_ENV', (string) env('APP_ENV', 'local'));
}
if (!defined('APP_URL')) {
    define('APP_URL', (string) env('APP_URL', 'http://localhost/asentra-spk/public'));
}
if (!defined('SESSION_NAME')) {
    define('SESSION_NAME', (string) env('SESSION_NAME', 'ASENTRA_SPK_SESSION'));
}

// Database
if (!defined('DB_HOST')) {
    define('DB_HOST', (string) env('DB_HOST', 'localhost'));
}
if (!defined('DB_PORT')) {
    define('DB_PORT', (int) env('DB_PORT', 3306));
}
if (!defined('DB_DATABASE')) {
    define('DB_DATABASE', (string) env('DB_DATABASE', 'asentra_spk'));
}
if (!defined('DB_USERNAME')) {
    define('DB_USERNAME', (string) env('DB_USERNAME', 'root'));
}
if (!defined('DB_PASSWORD')) {
    define('DB_PASSWORD', (string) env('DB_PASSWORD', ''));
}
if (!defined('DB_CHARSET')) {
    define('DB_CHARSET', (string) env('DB_CHARSET', 'utf8mb4'));
}
