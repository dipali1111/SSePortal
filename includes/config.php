<?php
/**
 * Database configuration.
 *
 * DB_DRIVER = 'sqlite' -> zero-config, self-initializing file database.
 *             Good for local testing / small deployments. No setup needed,
 *             the database file and schema are created automatically on
 *             first request (see includes/db.php).
 *
 * DB_DRIVER = 'mysql'  -> use for a real production LAMP/MySQL host.
 *             Create the database and import database/schema.mysql.sql
 *             first, then fill in the credentials below and flip the
 *             driver. Also run database/seed_mysql.php once to load the
 *             starting Kolhapur district data.
 */

define('DB_DRIVER', 'sqlite');

// --- SQLite settings (used when DB_DRIVER = 'sqlite') ---
define('SQLITE_PATH', __DIR__ . '/../data/app.sqlite');

// --- MySQL settings (used when DB_DRIVER = 'mysql') ---
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'sumruddha_sala');
define('DB_USER', 'root');
define('DB_PASS', '');
