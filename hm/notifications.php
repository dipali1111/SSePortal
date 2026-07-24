<?php
require_once dirname(__DIR__) . '/config/db.php';
requireHmAccess();

$pdo = getDbConnection();
$hmId = (int) ($_SESSION['user_id'] ?? 1);
$notifications = [];

if ($pdo) {
    $stmt = $pdo->prepare('SELECT * FROM notifications WHERE hm_id = :hm_id ORDER BY created_at DESC');
    $stmt->execute([':hm_id' => $hmId]);
    $notifications = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Notifications | HM Dashboard</title><link rel="stylesheet" href="../assets/css/hm-dashboard.css"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"></head>
<body>
<div class="app-shell">
    <aside class="sidebar open"><div class="sidebar-header"> <div class="brand"><div class="brand-badge"><i class="fa-solid fa-school"></i></div><div><div class="brand-name">Samruddh Shala</div><div class="brand-subtitle">E-Portal</div></div></div></div><nav class="sidebar-nav"><a href="dashboard.php"><i class="fa-solid fa-chart-pie"></i> Dashboard</a><a href="dashboard.php#assigned-works"><i class="fa-solid fa-briefcase"></i> Assigned Works</a><a href="progress_update.php"><i class="fa-solid fa-file-lines"></i> Progress Updates</a><a href="fund_utilization.php"><i class="fa-solid fa-wallet"></i> Fund Utilization</a><a href="report_blocker.php"><i class="fa-solid fa-triangle-exclamation"></i> Blockers / Delays</a><a class="active" href="notifications.php"><i class="fa-solid fa-bell"></i> Notifications</a><a href="profile.php"><i class="fa-solid fa-user"></i> Profile</a><a href="../login.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></nav></aside>
    <div class="content-area"><header class="topbar"><div><h1>Notifications</h1></div><div class="topbar-actions"><div class="notice-badge"><i class="fa-solid fa-bell"></i><span class="badge"><?= count(array_filter($notifications, fn($n) => !$n['is_read'])) ?></span></div></div></header>
        <main class="main-content">
            <section class="section-card">
                <div class="table-responsive"><table><thead><tr><th>Title</th><th>Message</th><th>Date</th><th>Status</th></tr></thead><tbody>
                    <?php foreach ($notifications as $note) : ?>
                        <tr><td><?= htmlspecialchars($note['title']) ?></td><td><?= htmlspecialchars($note['message']) ?></td><td><?= htmlspecialchars($note['created_at']) ?></td><td><span class="status-pill <?= $note['is_read'] ? 'status-resolved' : 'status-progress' ?>"><?= $note['is_read'] ? 'Read' : 'Unread' ?></span></td></tr>
                    <?php endforeach; ?>
                </tbody></table></div>
            </section>
        </main>
        <footer class="footer"><div class="footer-grid"><div><div>© 2026 Samruddh Shala E-Portal</div><div>School Infrastructure Monitoring System</div></div><div><a href="#">Privacy Policy</a> · <a href="#">Help & Support</a> · <a href="#">Contact</a></div><div><strong>System Status:</strong> Online</div></div></footer>
    </div>
</div>
<script src="../assets/js/hm-dashboard.js"></script>
</body>
</html>
