<?php
require_once dirname(__DIR__) . '/config/db.php';
requireHmAccess();

$pdo = getDbConnection();
$hmId = (int) ($_SESSION['user_id'] ?? 1);
$user = ['full_name' => 'Head Master', 'email' => 'hm@samruddhshala.gov', 'role' => 'HM'];

if ($pdo) {
    $stmt = $pdo->prepare('SELECT full_name, email, role FROM users WHERE id = :id');
    $stmt->execute([':id' => $hmId]);
    $user = $stmt->fetch() ?: $user;
}
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Profile | HM Dashboard</title><link rel="stylesheet" href="../assets/css/hm-dashboard.css"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"></head>
<body>
<div class="app-shell">
    <aside class="sidebar open"><div class="sidebar-header"> <div class="brand"><div class="brand-badge"><i class="fa-solid fa-school"></i></div><div><div class="brand-name">Samruddh Shala</div><div class="brand-subtitle">E-Portal</div></div></div></div><nav class="sidebar-nav"><a href="dashboard.php"><i class="fa-solid fa-chart-pie"></i> Dashboard</a><a href="dashboard.php#assigned-works"><i class="fa-solid fa-briefcase"></i> Assigned Works</a><a href="progress_update.php"><i class="fa-solid fa-file-lines"></i> Progress Updates</a><a href="fund_utilization.php"><i class="fa-solid fa-wallet"></i> Fund Utilization</a><a href="report_blocker.php"><i class="fa-solid fa-triangle-exclamation"></i> Blockers / Delays</a><a href="notifications.php"><i class="fa-solid fa-bell"></i> Notifications</a><a class="active" href="profile.php"><i class="fa-solid fa-user"></i> Profile</a><a href="../login.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></nav></aside>
    <div class="content-area"><header class="topbar"><div><h1>HM Profile</h1></div><div class="topbar-actions"><div class="notice-badge"><i class="fa-solid fa-bell"></i><span class="badge">0</span></div></div></header>
        <main class="main-content">
            <section class="section-card">
                <div class="profile-chip" style="margin-bottom:18px;">
                    <div class="avatar">HM</div>
                    <div class="profile-meta"><strong><?= htmlspecialchars($user['full_name']) ?></strong><small><?= htmlspecialchars($user['role']) ?></small></div>
                </div>
                <div class="form-grid">
                    <div class="form-group"><label>Name</label><input type="text" value="<?= htmlspecialchars($user['full_name']) ?>" readonly></div>
                    <div class="form-group"><label>Email</label><input type="text" value="<?= htmlspecialchars($user['email']) ?>" readonly></div>
                    <div class="form-group"><label>Role</label><input type="text" value="<?= htmlspecialchars($user['role']) ?>" readonly></div>
                    <div class="form-group"><label>Portal Access</label><input type="text" value="Head Master Dashboard" readonly></div>
                </div>
            </section>
        </main>
        <footer class="footer"><div class="footer-grid"><div><div>© 2026 Samruddh Shala E-Portal</div><div>School Infrastructure Monitoring System</div></div><div><a href="#">Privacy Policy</a> · <a href="#">Help & Support</a> · <a href="#">Contact</a></div><div><strong>System Status:</strong> Online</div></div></footer>
    </div>
</div>
<script src="../assets/js/hm-dashboard.js"></script>
</body>
</html>
