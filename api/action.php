<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

require_method('POST');
$pdo = get_db();
$body = read_json_body();

$id = (int) ($body['id'] ?? 0);
$action = $body['action'] ?? '';
$allowed = ['approve', 'request_update', 'resolve', 'download'];

if ($id <= 0 || !in_array($action, $allowed, true)) {
    json_out(['ok' => false, 'error' => 'अवैध आयडी किंवा क्रिया'], 400);
}

$stmt = $pdo->prepare('SELECT n.id, n.project_id, p.name AS project, s.name AS school
    FROM notifications n
    JOIN projects p ON p.id = n.project_id
    JOIN schools s ON s.id = p.school_id
    WHERE n.id = ?');
$stmt->execute([$id]);
$row = $stmt->fetch();
if (!$row) {
    json_out(['ok' => false, 'error' => 'सूचना सापडली नाही'], 404);
}

$label = $row['project'] . ' — ' . $row['school'];
$messages = [
    'approve'        => "$label साठी अद्यतन मंजूर करण्यात आले",
    'request_update'  => "$label साठी मुख्याध्यापकांकडून अद्यतनाची विनंती करण्यात आली",
    'resolve'         => "$label साठी सूचना निराकरण झाली म्हणून चिन्हांकित करण्यात आली",
    'download'        => "$label साठी अहवाल डाउनलोड करण्यात आला",
];
$toast = [
    'approve'        => 'अद्यतन मंजूर करण्यात आले',
    'request_update'  => 'मुख्याध्यापकांकडून अद्यतनाची विनंती करण्यात आली',
    'resolve'         => 'सूचना निराकरण झाली म्हणून चिन्हांकित करण्यात आली',
    'download'        => 'अहवाल डाउनलोड सुरू झाला',
];

$pdo->prepare('UPDATE notifications SET is_read = 1 WHERE id = ?')->execute([$id]);

if ($action === 'resolve') {
    $pdo->prepare("UPDATE projects SET status = 'completed' WHERE id = ?")->execute([$row['project_id']]);
}

$pdo->prepare('INSERT INTO activity_log (message) VALUES (?)')->execute([$messages[$action]]);

json_out(['ok' => true, 'message' => $toast[$action]]);