<?php
// ASENTRA SPK — Database connectivity test

declare(strict_types=1);

// Load constants and Database without triggering the front controller router.
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../app/core/Database.php';

try {
    $db = \App\Core\Database::getConnection();
    $stmt = $db->query('SELECT 1 AS ok');
    $row = $stmt->fetch();

    if ($row && $row['ok'] == 1) {
        echo "OK: Database connected.\n";
    } else {
        echo "FAIL: Unexpected result.\n";
        exit(1);
    }

    $counts = $db->query('SELECT ' .
        '(SELECT COUNT(*) FROM tb_user) AS users, ' .
        '(SELECT COUNT(*) FROM tb_teknisi) AS teknisi, ' .
        '(SELECT COUNT(*) FROM tb_kriteria) AS kriteria, ' .
        '(SELECT COUNT(*) FROM tb_penilaian) AS penilaian'
    )->fetch();

    echo "Counts: users={$counts['users']} teknisi={$counts['teknisi']} kriteria={$counts['kriteria']} penilaian={$counts['penilaian']}\n";

    if ($counts['users'] == 2 && $counts['teknisi'] == 10 && $counts['kriteria'] == 3 && $counts['penilaian'] == 10) {
        echo "OK: Seed data verified.\n";
    } else {
        echo "FAIL: Seed data counts mismatch.\n";
        exit(1);
    }
} catch (Throwable $e) {
    echo 'FAIL: ' . $e->getMessage() . "\n";
    exit(1);
}
