<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

$pdo = get_db();

$stmt = $pdo->query('SELECT message, created_at FROM activity_log ORDER BY created_at DESC LIMIT 6');
$rows = $stmt->fetchAll();

$data = array_map(function ($r) {
    return [
        'text' => htmlspecialchars($r['message'], ENT_QUOTES, 'UTF-8'),
        'time' => relative_short($r['created_at']),
    ];
}, $rows);

json_out(['ok' => true, 'data' => $data]);
