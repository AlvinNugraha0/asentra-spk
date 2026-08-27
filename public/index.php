<?php
// ASENTRA SPK — Front controller

declare(strict_types=1);

// Path constants
if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}
define('ROOT_PATH', realpath(__DIR__ . DS . '..') ?: __DIR__ . DS . '..');
define('APP_PATH', ROOT_PATH . DS . 'app');
define('CONFIG_PATH', ROOT_PATH . DS . 'config');
define('PUBLIC_PATH', __DIR__);
define('DATABASE_PATH', ROOT_PATH . DS . 'database');

// Built-in server: serve real static files (CSS/JS/images) directly so the
// router does not swallow them and return the correct MIME type.
if (PHP_SAPI === 'cli-server') {
    $staticUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    if (is_string($staticUri) && $staticUri !== '/' && $staticUri !== '') {
        $staticFile = realpath(PUBLIC_PATH . $staticUri);
        if ($staticFile !== false && str_starts_with($staticFile, realpath(PUBLIC_PATH) . DS) && is_file($staticFile)) {
            return false;
        }
    }
}

// Autoloader for App namespace
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $path = APP_PATH . DS . str_replace('\\', DS, $relative) . '.php';

    if (is_file($path)) {
        require_once $path;
    }
});

// Application constants and environment
require_once CONFIG_PATH . DS . 'constants.php';

// Start session with httponly + samesite
if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    $cookieParams = session_get_cookie_params();
    session_set_cookie_params([
        'lifetime'  => $cookieParams['lifetime'],
        'path'      => '/',
        'domain'    => $cookieParams['domain'],
        'secure'    => false, // XAMPP local usually HTTP; set true in production with HTTPS
        'httponly'  => true,
        'samesite'  => 'Lax',
    ]);
    session_start();
}

// Helpers
require_once APP_PATH . DS . 'helpers' . DS . 'url.php';
require_once APP_PATH . DS . 'helpers' . DS . 'format.php';
// Load view helpers after session so partials can access session data
require_once APP_PATH . DS . 'helpers' . DS . 'view.php';

// Router (defines render/captureView used by auth helpers)
$router = require APP_PATH . DS . 'core' . DS . 'Router.php';

// Helpers that depend on render()
require_once APP_PATH . DS . 'helpers' . DS . 'auth.php';

// Error display (local only)
if (APP_ENV === 'local') {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(0);
}

// Dispatch
$router();
