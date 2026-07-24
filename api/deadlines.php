<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

$pdo = get_db();

$stmt = $pdo->query("SELECT d.id, d.due_date, p.name AS project, p.stage, s.name AS school
    FROM deadlines d
    JOIN projects p ON p.id = d.project_id
    JOIN schools s ON s.id = p.school_id
    ORDER BY d.due_date ASC
    LIMIT 6");
$rows = $stmt->fetchAll();

$data = array_map(function ($r) {
    return [
        'id'      => (int) $r['id'],
        'title'   => $r['project'] . ' — ' . $r['school'],
        'sub'     => $r['stage'],
        'tag'     => deadline_label($r['due_date']),
        'urgency' => deadline_urgency($r['due_date']),
    ];
}, $rows);

json_out(['ok' => true, 'data' => $data]);
