<?php
require_once __DIR__ . '/config.php';

/**
 * Returns a shared PDO connection. For SQLite, transparently creates the
 * database file, applies the schema, and seeds starting data the first
 * time it's called (e.g. on a fresh deploy) — no manual setup step.
 */
function get_db(): PDO {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    if (DB_DRIVER === 'mysql') {
        $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        return $pdo;
    }

    // --- SQLite (default) ---
    $dataDir = dirname(SQLITE_PATH);
    if (!is_dir($dataDir)) {
        mkdir($dataDir, 0775, true);
    }
    $isNew = !file_exists(SQLITE_PATH);

    $pdo = new PDO('sqlite:' . SQLITE_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA foreign_keys = ON');

    if ($isNew) {
        $schema = file_get_contents(__DIR__ . '/../database/schema.sqlite.sql');
        $pdo->exec($schema);
        require __DIR__ . '/../database/seed.php'; // populates using $pdo
    }

    return $pdo;
}
