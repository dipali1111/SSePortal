<?php
require_once dirname(__DIR__) . '/config/db.php';
requireHmAccess();

$pdo = getDbConnection();
$hmId = (int) ($_SESSION['user_id'] ?? 1);
$fundRows = [];
$totalAllocated = 0;
$totalUtilized = 0;

if ($pdo) {
    $stmt = $pdo->prepare('SELECT p.project_name, fu.allocated_amount, fu.utilized_amount, fu.remaining_amount, fu.utilization_percentage FROM fund_utilization fu JOIN projects p ON p.id = fu.project_id WHERE p.hm_id = :hm_id ORDER BY p.project_name');
    $stmt->execute([':hm_id' => $hmId]);
    $fundRows = $stmt->fetchAll();
    $totalAllocated = array_sum(array_column($fundRows, 'allocated_amount'));
    $totalUtilized = array_sum(array_column($fundRows, 'utilized_amount'));
}
$remainingFund = $totalAllocated - $totalUtilized;
$utilizationPercent = $totalAllocated > 0 ? round(($totalUtilized / $totalAllocated) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Fund Utilization | HM Dashboard</title><link rel="stylesheet" href="../assets/css/hm-dashboard.css"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"><script src="https://cdn.jsdelivr.net/npm/chart.js"></script></head>
<body>
<div class="app-shell">
    <aside class="sidebar open"><div class="sidebar-header"> <div class="brand"><div class="brand-badge"><i class="fa-solid fa-school"></i></div><div><div class="brand-name">Samruddh Shala</div><div class="brand-subtitle">E-Portal</div></div></div></div><nav class="sidebar-nav"><a href="dashboard.php"><i class="fa-solid fa-chart-pie"></i> Dashboard</a><a href="dashboard.php#assigned-works"><i class="fa-solid fa-briefcase"></i> Assigned Works</a><a href="progress_update.php"><i class="fa-solid fa-file-lines"></i> Progress Updates</a><a class="active" href="fund_utilization.php"><i class="fa-solid fa-wallet"></i> Fund Utilization</a><a href="dashboard.php#blockers"><i class="fa-solid fa-triangle-exclamation"></i> Blockers / Delays</a><a href="notifications.php"><i class="fa-solid fa-bell"></i> Notifications</a><a href="profile.php"><i class="fa-solid fa-user"></i> Profile</a><a href="../login.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></nav></aside>
    <div class="content-area"><header class="topbar"><div><h1>Fund Utilization</h1></div><div class="topbar-actions"><div class="notice-badge"><i class="fa-solid fa-bell"></i><span class="badge">1</span></div></div></header>
        <main class="main-content">
            <section class="section-card">
                <div class="summary-grid">
                    <article class="summary-card"><div class="summary-icon"><i class="fa-solid fa-wallet"></i></div><div class="summary-value"><?= formatCurrency($totalAllocated) ?></div><div class="summary-text">Total Allocated Fund</div></article>
                    <article class="summary-card"><div class="summary-icon"><i class="fa-solid fa-indian-rupee-sign"></i></div><div class="summary-value"><?= formatCurrency($totalUtilized) ?></div><div class="summary-text">Total Utilized Fund</div></article>
                    <article class="summary-card"><div class="summary-icon"><i class="fa-solid fa-piggy-bank"></i></div><div class="summary-value"><?= formatCurrency($remainingFund) ?></div><div class="summary-text">Remaining Fund</div></article>
                    <article class="summary-card"><div class="summary-icon"><i class="fa-solid fa-chart-column"></i></div><div class="summary-value"><?= $utilizationPercent ?>%</div><div class="summary-text">Utilization Percentage</div></article>
                </div>
                <div class="table-responsive"><table><thead><tr><th>Project Name</th><th>Allocated Amount</th><th>Utilized Amount</th><th>Remaining Amount</th><th>Utilization %</th></tr></thead><tbody>
                    <?php foreach ($fundRows as $row) : ?><tr><td><?= htmlspecialchars($row['project_name']) ?></td><td><?= formatCurrency($row['allocated_amount']) ?></td><td><?= formatCurrency($row['utilized_amount']) ?></td><td><?= formatCurrency($row['remaining_amount']) ?></td><td><?= (int) $row['utilization_percentage'] ?>%</td></tr><?php endforeach; ?>
                </tbody></table></div>
                <div style="margin-top:18px; display:grid; grid-template-columns:1.1fr 1fr; gap:12px;">
                    <div class="panel-card" style="padding:16px;"><strong>Overall Fund Utilization</strong><div class="progress-wrap"><div class="progress-track"><div class="progress-bar" data-progress="<?= $utilizationPercent ?>"></div></div><small><?= $utilizationPercent ?>%</small></div></div>
                    <div class="panel-card" style="padding:16px;"><canvas id="fundChart" height="160"></canvas></div>
                </div>
            </section>
        </main>
    </div>
</div>
<footer class="footer"><div class="footer-grid"><div><div>© 2026 Samruddh Shala E-Portal</div><div>School Infrastructure Monitoring System</div></div><div><a href="#">Privacy Policy</a> · <a href="#">Help & Support</a> · <a href="#">Contact</a></div><div><strong>System Status:</strong> Online</div></div></footer>
<script src="../assets/js/hm-dashboard.js"></script>
<script>
new Chart(document.getElementById('fundChart'), {
    type: 'doughnut',
    data: { labels: ['Utilized', 'Remaining'], datasets: [{ data: [<?= (float) $totalUtilized ?>, <?= (float) $remainingFund ?>], backgroundColor: ['#2563EB', '#06B6D4'], borderWidth: 0 }] },
    options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
});
</script>
</body>
</html>
