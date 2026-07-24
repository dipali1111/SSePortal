<?php
session_start();

if (empty($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
}

if (empty($_SESSION['role'])) {
    $_SESSION['role'] = 'HM';
}

if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

function getDbConnection(): ?PDO
{
    $host = getenv('DB_HOST') ?: 'localhost';
    $dbName = getenv('DB_NAME') ?: 'samruddh_shala';
    $user = getenv('DB_USER') ?: 'root';
    $pass = getenv('DB_PASS') ?: '';

    try {
        $pdo = new PDO(
            "mysql:host={$host};dbname={$dbName};charset=utf8mb4",
            $user,
            $pass,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
        return $pdo;
    } catch (PDOException $e) {
        return null;
    }
}

function requireHmAccess(): void
{
    if ((($_SESSION['role'] ?? '') !== 'HM')) {
        header('Location: ../login.php');
        exit;
    }
}

function sanitizeText(string $value): string
{
    return trim(htmlspecialchars($value, ENT_QUOTES, 'UTF-8'));
}

function uploadFiles(array $files, string $targetDir, array $allowed = ['jpg', 'jpeg', 'png', 'webp']): array
{
    $uploaded = [];
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    foreach ($files['name'] as $index => $name) {
        if ($files['error'][$index] !== UPLOAD_ERR_OK) {
            continue;
        }

        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed, true)) {
            continue;
        }

        if ($files['size'][$index] > 2 * 1024 * 1024) {
            continue;
        }

        $uniqueName = uniqid('upload_', true) . '.' . $ext;
        $destination = rtrim($targetDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $uniqueName;

        if (move_uploaded_file($files['tmp_name'][$index], $destination)) {
            $uploaded[] = $destination;
        }
    }

    return $uploaded;
}

function formatCurrency($value): string
{
    return '₹' . number_format((float) $value, 2);
}

function statusPillClass(string $status): string
{
    $status = strtolower($status);
    $map = [
        'completed' => 'status-completed',
        'in progress' => 'status-progress',
        'pending' => 'status-pending',
        'delayed' => 'status-delayed',
        'reported' => 'status-reported',
        'under review' => 'status-review',
        'resolved' => 'status-resolved',
    ];

    return $map[$status] ?? 'status-pending';
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
