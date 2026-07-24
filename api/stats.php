<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

$pdo = get_db();

$total = (int) $pdo->query('SELECT COUNT(*) FROM notifications')->fetchColumn();

function countType(PDO $pdo, string $type): int {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE type = ?');
    $stmt->execute([$type]);
    return (int) $stmt->fetchColumn();
}
$critical = countType($pdo, 'critical');
$pending  = countType($pdo, 'pending');
$info     = countType($pdo, 'info');
$resolved = countType($pdo, 'success');

$pct = function (int $n) use ($total) {
    return $total > 0 ? round(($n / $total) * 100) : 0;
};

$priority = [
    'completed' => ['count' => $resolved, 'pct' => $pct($resolved)],
    'pending'   => ['count' => $pending,  'pct' => $pct($pending)],
    'delayed'   => ['count' => $critical, 'pct' => $pct($critical)],
    'info'      => ['count' => $info,     'pct' => $pct($info)],
];

$rings = [
    ['label' => 'गंभीर सूचना', 'value' => $critical, 'total' => max($total, 1), 'color' => 'critical'],
    ['label' => 'प्रलंबित',    'value' => $pending,  'total' => max($total, 1), 'color' => 'warning'],
    ['label' => 'पूर्ण झाले',   'value' => $resolved, 'total' => max($total, 1), 'color' => 'success'],
    ['label' => 'निराकरण झाले','value' => $resolved, 'total' => max($total, 1), 'color' => 'blue'],
];

// Weekly comparison: notification count per day for the last 7 days.
$weekly = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE date(created_at) = ?");
    $stmt->execute([$date]);
    $weekly[] = ['day' => date('D', strtotime($date)), 'count' => (int) $stmt->fetchColumn()];
}

json_out(['ok' => true, 'data' => [
    'total'    => $total,
    'critical' => $critical,
    'pending'  => $pending,
    'resolved' => $resolved,
    'priority' => $priority,
    'rings'    => $rings,
    'weekly'   => $weekly,
]]);