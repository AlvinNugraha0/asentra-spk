<?php
// ASENTRA SPK — PDO database configuration

declare(strict_types=1);

require_once __DIR__ . '/constants.php';

/**
 * Return a PDO connection to the configured MySQL database.
 *
 * @throws PDOException
 */
function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=%s',
            DB_HOST,
            DB_PORT,
            DB_DATABASE,
            DB_CHARSET
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_PERSISTENT         => false,
        ];

        $pdo = new PDO($dsn, DB_USERNAME, DB_PASSWORD, $options);
    }

    return $pdo;
}
