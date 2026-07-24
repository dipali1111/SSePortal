<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

require_method('POST');
$pdo = get_db();
$body = read_json_body();

if (!empty($body['all'])) {
    $pdo->exec('UPDATE notifications SET is_read = 1 WHERE is_read = 0');
    $pdo->prepare('INSERT INTO activity_log (message) VALUES (?)')
        ->execute(['सर्व सूचना वाचल्या म्हणून चिन्हांकित करण्यात आल्या']);
    json_out(['ok' => true, 'message' => 'सर्व सूचना वाचल्या म्हणून चिन्हांकित करण्यात आल्या']);
}

$id = (int) ($body['id'] ?? 0);
if ($id <= 0) {
    json_out(['ok' => false, 'error' => 'आयडी गहाळ आहे'], 400);
}
$stmt = $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE id = ?');
$stmt->execute([$id]);

json_out(['ok' => true, 'message' => 'सूचना वाचली म्हणून चिन्हांकित करण्यात आली']);