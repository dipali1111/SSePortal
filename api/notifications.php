<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

$pdo = get_db();
$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    json_out(['ok' => false, 'error' => 'आयडी गहाळ किंवा अवैध आहे'], 400);
}

$stmt = $pdo->prepare("SELECT n.*, p.name AS project, p.funding_source, p.stage, p.completion_pct,
        p.delay_days, p.geotag_status, p.officer, p.sanctioned_amount, p.utilized_amount,
        s.name AS school
    FROM notifications n
    JOIN projects p ON p.id = n.project_id
    JOIN schools  s ON s.id = p.school_id
    WHERE n.id = :id");
$stmt->execute([':id' => $id]);
$r = $stmt->fetch();

if (!$r) {
    json_out(['ok' => false, 'error' => 'सूचना सापडली नाही'], 404);
}

function money($n) {
    $n = (float) $n;
    return '₹' . rtrim(rtrim(number_format($n / 100000, 1), '0'), '.') . 'L';
}

$data = [
    'id'       => (int) $r['id'],
    'type'     => $r['type'],
    'title'    => $r['title'],
    'school'   => $r['school'],
    'project'  => $r['project'],
    'desc'     => $r['description'],
    'reason'   => $r['reason'],
    'action'   => $r['action_label'],
    'priority' => $r['priority_label'],
    'detail'   => [
        'funding'     => $r['funding_source'],
        'stage'       => $r['stage'],
        'completion'  => (int) $r['completion_pct'],
        'delay'       => $r['delay_days'] . ' days',
        'geotag'      => $r['geotag_status'],
        'officer'     => $r['officer'],
        'remarks'     => $r['remarks'],
        'utilization' => money($r['utilized_amount']) . ' / ' . money($r['sanctioned_amount']),
    ],
];

json_out(['ok' => true, 'data' => $data]);