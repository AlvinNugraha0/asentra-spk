<?php
// ASENTRA SPK — AuthService unit test

declare(strict_types=1);

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/models/User.php';
require_once __DIR__ . '/../app/services/AuthService.php';

$service = new \App\Services\AuthService();
$ok = true;

// Valid admin
$r = $service->login('admin', 'admin');
if (!$r['success'] || ($r['user']['role'] ?? '') !== 'admin') {
    echo "FAIL: valid admin login\n";
    $ok = false;
} else {
    echo "OK: valid admin login\n";
}

// Valid owner
$r = $service->login('owner', 'owner');
if (!$r['success'] || ($r['user']['role'] ?? '') !== 'owner') {
    echo "FAIL: valid owner login\n";
    $ok = false;
} else {
    echo "OK: valid owner login\n";
}

// Invalid password
$r = $service->login('admin', 'wrong');
if ($r['success']) {
    echo "FAIL: invalid password should be rejected\n";
    $ok = false;
} else {
    echo "OK: invalid password rejected\n";
}

// Empty credentials
$r = $service->login('', '');
if ($r['success']) {
    echo "FAIL: empty credentials should be rejected\n";
    $ok = false;
} else {
    echo "OK: empty credentials rejected\n";
}

exit($ok ? 0 : 1);
