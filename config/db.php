<?php
// Database connection for XAMPP (MySQL/MariaDB)
// Adjust credentials if your XAMPP setup differs.
$DB_HOST = 'localhost';
$DB_USER = 'root';
$DB_PASS = '';
$DB_NAME = 'kolhapur_school_dashboard';

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}
$conn->set_charset('utf8mb4');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Language handling (English / Marathi)
if (isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'mr'])) {
    $_SESSION['lang'] = $_GET['lang'];
}
$LANG = $_SESSION['lang'] ?? 'en';
$T = require __DIR__ . '/../lang/' . $LANG . '.php';

function t($key) {
    global $T;
    return $T[$key] ?? $key;
}
