<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

$pdo = get_db();

$filter = $_GET['filter'] ?? 'all';
$search = trim($_GET['search'] ?? '');
$allowedTypes = ['critical', 'pending', 'info', 'success'];

$sql = "SELECT n.title, s.name AS school, p.name AS project, n.priority_label AS priority,
               n.created_at, n.description
        FROM notifications n
        JOIN projects p ON p.id = n.project_id
        JOIN schools  s ON s.id = p.school_id
        WHERE 1=1";
$params = [];
if (in_array($filter, $allowedTypes, true)) {
    $sql .= ' AND n.type = :type';
    $params[':type'] = $filter;
}
if ($search !== '') {
    $sql .= ' AND (n.title LIKE :s OR s.name LIKE :s OR p.name LIKE :s OR n.description LIKE :s)';
    $params[':s'] = "%$search%";
}
$sql .= ' ORDER BY n.created_at DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="alerts_export.csv"');

$out = fopen('php://output', 'w');
fputcsv($out, ['शीर्षक', 'शाळा', 'प्रकल्प', 'प्राधान्य', 'वेळ', 'वर्णन']);
foreach ($rows as $r) {
    fputcsv($out, [
        $r['title'], $r['school'], $r['project'], $r['priority'],
        format_relative_time($r['created_at']), $r['description'],
    ]);
}
fclose($out);
exit;