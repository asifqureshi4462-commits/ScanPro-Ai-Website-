<?php
declare(strict_types=1);

/**
 * ScanPro AI - MySQL PDO Database Connection
 * Automatically uses local AWebServer/XAMPP credentials on localhost
 * and InfinityFree production credentials on live hosting.
 */

require_once __DIR__ . '/app.php';

if (isLocalhostRequest()) {
    define('DB_HOST', '127.0.0.1');
    define('DB_PORT', '3306');
    define('DB_NAME', 'scanpro_ai');
    define('DB_USER', 'root');
    define('DB_PASS', '');
} else {
    // Production credentials must be supplied by the hosting environment.
    // Never commit database passwords to a public repository.
    define('DB_HOST', getenv('SCANPRO_DB_HOST') ?: '127.0.0.1');
    define('DB_PORT', getenv('SCANPRO_DB_PORT') ?: '3306');
    define('DB_NAME', getenv('SCANPRO_DB_NAME') ?: 'scanpro_ai');
    define('DB_USER', getenv('SCANPRO_DB_USER') ?: 'root');
    define('DB_PASS', getenv('SCANPRO_DB_PASS') ?: '');
}

define('DB_CHARSET', 'utf8mb4');

/**
 * Returns a shared PDO instance configured for security and strict error reporting.
 *
 * @throws RuntimeException when the database connection cannot be established.
 */
function getDbConnection(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        DB_HOST,
        DB_PORT,
        DB_NAME,
        DB_CHARSET
    );

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::ATTR_STRINGIFY_FETCHES  => false,
        PDO::ATTR_TIMEOUT            => 5,
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
    } catch (PDOException $e) {
        error_log('[ScanPro AI DB Error] ' . $e->getMessage());
        throw new RuntimeException('Unable to connect to the database. Please check your MySQL configuration.');
    }
}