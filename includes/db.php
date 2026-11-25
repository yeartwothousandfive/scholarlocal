<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/config/config.php';

/**
 * Returns a singleton PDO database connection supporting MySQL and SQLite.
 */
function get_db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    if (DB_DRIVER === 'sqlite') {
        $dir = dirname(SQLITE_PATH);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $needsInit = !file_exists(SQLITE_PATH) || filesize(SQLITE_PATH) === 0;
        $pdo = new PDO('sqlite:' . SQLITE_PATH, null, null, $options);
        $pdo->exec('PRAGMA foreign_keys = ON;');
        if ($needsInit) {
            $schemaFile = dirname(__DIR__) . '/database/schema.sqlite.sql';
            if (is_file($schemaFile)) {
                $sql = file_get_contents($schemaFile);
                if ($sql !== false) {
                    $pdo->exec($sql);
                }
            }
        }
        return $pdo;
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        DB_HOST,
        DB_PORT,
        DB_NAME,
        DB_CHARSET
    );
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    return $pdo;
}
