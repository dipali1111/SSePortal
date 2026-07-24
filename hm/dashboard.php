<?php
require_once dirname(__DIR__) . '/config/db.php';
requireHmAccess();

$pdo = getDbConnection();
$hmId = (int) ($_SESSION['user_id'] ?? 1);

$summary = [
    'assigned_works' => 0,
    'completed' => 0,
    'in_progress' => 0,
    'pending' => 0,
    'fund_utilized' => 0,
    'blockers' => 0,
];

$works = [];
$notifications = [];
$fundRows = [];
$selectedProject = null;

if ($pdo) {
    $summaryStmt = $pdo->prepare('SELECT
        COUNT(*) AS assigned_works,
        SUM(CASE WHEN status = "Completed" THEN 1 ELSE 0 END) AS completed,
        SUM(CASE WHEN status = "In Progress" THEN 1 ELSE 0 END) AS in_progress,
        SUM(CASE WHEN status = "Pending" THEN 1 ELSE 0 END) AS pending
    FROM projects WHERE hm_id = :hm_id');
    $summaryStmt->execute([':hm_id' => $hmId]);
    $summaryData = $summaryStmt->fetch();

    $summary = [
        'assigned_works' => (int) ($summaryData['assigned_works'] ?? 0),
        'completed' => (int) ($summaryData['completed'] ?? 0),
        'in_progress' => (int) ($summaryData['in_progress'] ?? 0),
        'pending' => (int) ($summaryData['pending'] ?? 0),
    ];

    $fundStmt = $pdo->prepare('SELECT COALESCE(SUM(allocated_amount),0) AS total_allocated, COALESCE(SUM(utilized_amount),0) AS total_utilized FROM fund_utilization WHERE project_id IN (SELECT id FROM projects WHERE hm_id = :hm_id)');
    $fundStmt->execute([':hm_id' => $hmId]);
    $fundData = $fundStmt->fetch();
    $summary['fund_utilized'] = $fundData['total_utilized'] ?? 0;

    $blockerStmt = $pdo->prepare('SELECT COUNT(*) AS blockers FROM blockers WHERE hm_id = :hm_id');
    $blockerStmt->execute([':hm_id' => $hmId]);
    $summary['blockers'] = (int) ($blockerStmt->fetch()['blockers'] ?? 0);

    $worksStmt = $pdo->prepare('SELECT * FROM projects WHERE hm_id = :hm_id ORDER BY start_date DESC');
    $worksStmt->execute([':hm_id' => $hmId]);
    $works = $worksStmt->fetchAll();

    $notifyStmt = $pdo->prepare('SELECT * FROM notifications WHERE hm_id = :hm_id ORDER BY created_at DESC LIMIT 5');
    $notifyStmt->execute([':hm_id' => $hmId]);
    $notifications = $notifyStmt->fetchAll();

    $fundStmt = $pdo->prepare('SELECT p.project_name, fu.allocated_amount, fu.utilized_amount, fu.remaining_amount, fu.utilization_percentage FROM fund_utilization fu JOIN projects p ON p.id = fu.project_id WHERE p.hm_id = :hm_id ORDER BY p.project_name');
    $fundStmt->execute([':hm_id' => $hmId]);
    $fundRows = $fundStmt->fetchAll();

    $projectStmt = $pdo->prepare('SELECT * FROM projects WHERE hm_id = :hm_id ORDER BY id ASC LIMIT 1');
    $projectStmt->execute([':hm_id' => $hmId]);
    $selectedProject = $projectStmt->fetch() ?: null;
} else {
    $summary = [
        'assigned_works' => 4,
        'completed' => 1,
        'in_progress' => 1,
        'pending' => 1,
        'fund_utilized' => 1930000,
        'blockers' => 2,
    ];

    $works = [
        ['id' => 1, 'project_name' => 'Primary School Class Room Extension', 'work_type' => 'Classroom Construction', 'location' => 'Bengaluru North', 'status' => 'In Progress', 'progress_percentage' => 65, 'stage' => 'Construction', 'start_date' => '2026-01-12', 'expected_completion_date' => '2026-08-30', 'total_budget' => 1200000, 'utilized_amount' => 780000, 'last_update_date' => '2026-07-18', 'last_remark' => 'Brickwork completed and plastering in progress.'],
        ['id' => 2, 'project_name' => 'Toilet Block Upgrade', 'work_type' => 'Toilet Construction', 'location' => 'Kolar District', 'status' => 'Pending', 'progress_percentage' => 15, 'stage' => 'Foundation', 'start_date' => '2026-02-09', 'expected_completion_date' => '2026-09-15', 'total_budget' => 650000, 'utilized_amount' => 98000, 'last_update_date' => '2026-07-10', 'last_remark' => 'Pending final site clearance.'],
        ['id' => 3, 'project_name' => 'School Compound Repair Works', 'work_type' => 'Repair Work', 'location' => 'Mysuru', 'status' => 'Completed', 'progress_percentage' => 100, 'stage' => 'Completed', 'start_date' => '2025-11-11', 'expected_completion_date' => '2026-03-18', 'total_budget' => 450000, 'utilized_amount' => 450000, 'last_update_date' => '2026-03-18', 'last_remark' => 'Project handed over successfully.'],
        ['id' => 4, 'project_name' => 'Science Lab Renovation', 'work_type' => 'Renovation', 'location' => 'Tumakuru', 'status' => 'Delayed', 'progress_percentage' => 48, 'stage' => 'Roofing', 'start_date' => '2026-03-05', 'expected_completion_date' => '2026-10-22', 'total_budget' => 900000, 'utilized_amount' => 430000, 'last_update_date' => '2026-07-16', 'last_remark' => 'Material supply issue affecting roof work.']
    ];

    $notifications = [
        ['title' => 'New Work Assigned', 'message' => 'Primary School Class Room Extension has been assigned to you.', 'created_at' => '2026-07-18 08:00:00', 'is_read' => 0],
        ['title' => 'Progress Update Approved', 'message' => 'Your update for Primary School Class Room Extension was approved.', 'created_at' => '2026-07-18 11:00:00', 'is_read' => 0],
        ['title' => 'Blocker Status Update', 'message' => 'The cement supply delay is now under review.', 'created_at' => '2026-07-16 10:00:00', 'is_read' => 1],
    ];

    $fundRows = [
        ['project_name' => 'Primary School Class Room Extension', 'allocated_amount' => 1200000, 'utilized_amount' => 780000, 'remaining_amount' => 420000, 'utilization_percentage' => 65],
        ['project_name' => 'Toilet Block Upgrade', 'allocated_amount' => 650000, 'utilized_amount' => 98000, 'remaining_amount' => 552000, 'utilization_percentage' => 15],
        ['project_name' => 'School Compound Repair Works', 'allocated_amount' => 450000, 'utilized_amount' => 450000, 'remaining_amount' => 0, 'utilization_percentage' => 100],
        ['project_name' => 'Science Lab Renovation', 'allocated_amount' => 900000, 'utilized_amount' => 430000, 'remaining_amount' => 470000, 'utilization_percentage' => 48],
    ];

    $selectedProject = $works[0];
}

$dashboardStages = ['Work Not Started', 'Foundation', 'Construction', 'Roofing', 'Finishing', 'Completed'];
$selectedStageIndex = $selectedProject ? array_search($selectedProject['stage'] ?? 'Construction', $dashboardStages, true) : 0;
$selectedStageIndex = $selectedStageIndex === false ? 0 : $selectedStageIndex;
$totalAllocated = array_sum(array_column($fundRows, 'allocated_amount'));
$totalUtilized = array_sum(array_column($fundRows, 'utilized_amount'));
$remainingFund = $totalAllocated - $totalUtilized;
$utilizationPercent = $totalAllocated > 0 ? round(($totalUtilized / $totalAllocated) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HM Dashboard | Samruddh Shala E-Portal</title>
    <link rel="stylesheet" href="../assets/css/hm-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<div class="app-shell">
    <aside class="sidebar open">
        <div class="sidebar-header">
            <div class="brand">
                <div class="brand-badge"><i class="fa-solid fa-school"></i></div>
                <div>
                    <div class="brand-name">Samruddh Shala</div>
                    <div class="brand-subtitle">E-Portal</div>
                </div>
            </div>
            <button class="btn btn-outline mobile-toggle" data-sidebar-toggle><i class="fa-solid fa-bars"></i></button>
        </div>
        <nav class="sidebar-nav">
            <a class="active" href="dashboard.php"><i class="fa-solid fa-chart-pie"></i> Dashboard</a>
            <a href="dashboard.php#assigned-works"><i class="fa-solid fa-briefcase"></i> Assigned Works</a>
            <a href="dashboard.php#progress-tracker"><i class="fa-solid fa-file-lines"></i> Progress Updates</a>
            <a href="fund_utilization.php"><i class="fa-solid fa-wallet"></i> Fund Utilization</a>
            <a href="dashboard.php#blockers"><i class="fa-solid fa-triangle-exclamation"></i> Blockers / Delays</a>
            <a href="notifications.php"><i class="fa-solid fa-bell"></i> Notifications</a>
            <a href="profile.php"><i class="fa-solid fa-user"></i> Profile</a>
            <a href="../login.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
        </nav>
    </aside>

    <div class="content-area">
        <header class="topbar">
            <div>
                <h1>HM Dashboard</h1>
            </div>
            <div class="topbar-actions">
                <div class="notice-badge">
                    <i class="fa-solid fa-bell"></i>
                    <span class="badge"><?= count(array_filter($notifications, fn($n) => !$n['is_read'])) ?></span>
                </div>
                <div class="profile-chip dropdown">
                    <div class="avatar">HM</div>
                    <div class="profile-meta">
                        <strong>Head Master</strong>
                        <small>School Infrastructure</small>
                    </div>
                    <i class="fa-solid fa-caret-down"></i>
                    <div class="dropdown-menu">
                        <a href="profile.php">My Profile</a>
                        <a href="dashboard.php">Settings</a>
                        <a href="../login.php">Logout</a>
                    </div>
                </div>
            </div>
        </header>

        <main class="main-content">
            <section class="summary-grid">
                <article class="summary-card">
                    <div class="summary-icon"><i class="fa-solid fa-clipboard-list"></i></div>
                    <div class="summary-value"><?= $summary['assigned_works'] ?></div>
                    <div class="summary-text">Total Assigned Works</div>
                </article>
                <article class="summary-card">
                    <div class="summary-icon"><i class="fa-solid fa-circle-check"></i></div>
                    <div class="summary-value"><?= $summary['completed'] ?></div>
                    <div class="summary-text">Completed Works</div>
                </article>
                <article class="summary-card">
                    <div class="summary-icon"><i class="fa-solid fa-spinner"></i></div>
                    <div class="summary-value"><?= $summary['in_progress'] ?></div>
                    <div class="summary-text">Works In Progress</div>
                </article>
                <article class="summary-card">
                    <div class="summary-icon"><i class="fa-solid fa-clock"></i></div>
                    <div class="summary-value"><?= $summary['pending'] ?></div>
                    <div class="summary-text">Pending Works</div>
                </article>
                <article class="summary-card">
                    <div class="summary-icon"><i class="fa-solid fa-indian-rupee-sign"></i></div>
                    <div class="summary-value"><?= formatCurrency($summary['fund_utilized']) ?></div>
                    <div class="summary-text">Total Fund Utilized</div>
                </article>
                <article class="summary-card">
                    <div class="summary-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
                    <div class="summary-value"><?= $summary['blockers'] ?></div>
                    <div class="summary-text">Reported Blockers</div>
                </article>
            </section>

            <section class="section-card" id="assigned-works">
                <div class="section-head">
                    <h2>Assigned Works</h2>
                    <a class="btn btn-primary" href="progress_update.php">Submit Progress Update</a>
                </div>
                <div class="work-grid">
                    <?php foreach ($works as $work) : ?>
                        <article class="work-card">
                            <h3><?= htmlspecialchars($work['project_name'] ?? 'Project') ?></h3>
                            <div class="status-pill <?= statusPillClass($work['status'] ?? 'Pending') ?>"><?= htmlspecialchars($work['status'] ?? 'Pending') ?></div>
                            <div class="meta-list">
                                <div class="meta-item"><span>Work Type</span><strong><?= htmlspecialchars($work['work_type'] ?? '-') ?></strong></div>
                                <div class="meta-item"><span>Project Location</span><strong><?= htmlspecialchars($work['location'] ?? '-') ?></strong></div>
                                <div class="meta-item"><span>Start Date</span><strong><?= htmlspecialchars($work['start_date'] ?? '-') ?></strong></div>
                                <div class="meta-item"><span>Expected Completion</span><strong><?= htmlspecialchars($work['expected_completion_date'] ?? '-') ?></strong></div>
                                <div class="meta-item"><span>Total Budget</span><strong><?= formatCurrency($work['total_budget'] ?? 0) ?></strong></div>
                                <div class="meta-item"><span>Amount Utilized</span><strong><?= formatCurrency($work['utilized_amount'] ?? 0) ?></strong></div>
                            </div>
                            <div class="progress-wrap">
                                <div class="progress-track"><div class="progress-bar" data-progress="<?= (int) ($work['progress_percentage'] ?? 0) ?>"></div></div>
                                <small><?= (int) ($work['progress_percentage'] ?? 0) ?>% progress</small>
                            </div>
                            <div class="action-row">
                                <a class="btn btn-secondary" href="dashboard.php#progress-tracker">View Details</a>
                                <a class="btn btn-primary" href="progress_update.php">Update Progress</a>
                                <a class="btn btn-outline" href="report_blocker.php">Report Blocker</a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="section-card" id="progress-tracker">
                <div class="section-head">
                    <h2>Work Progress Status</h2>
                    <div class="status-pill status-progress">Overall <?= (int) ($selectedProject['progress_percentage'] ?? 0) ?>%</div>
                </div>
                <div class="timeline">
                    <?php foreach ($dashboardStages as $index => $stage) : ?>
                        <div class="timeline-step <?= $index <= $selectedStageIndex ? 'active' : '' ?>">
                            <div class="step-number">Stage <?= $index + 1 ?></div>
                            <div class="step-title"><?= htmlspecialchars($stage) ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="meta-list" style="margin-top:18px;">
                    <div class="meta-item"><span>Project Stage</span><strong><?= htmlspecialchars($selectedProject['stage'] ?? 'Construction') ?></strong></div>
                    <div class="meta-item"><span>Current Stage</span><strong><?= htmlspecialchars($selectedProject['stage'] ?? 'Construction') ?></strong></div>
                    <div class="meta-item"><span>Last Update Date</span><strong><?= htmlspecialchars($selectedProject['last_update_date'] ?? '-') ?></strong></div>
                    <div class="meta-item"><span>Last Submitted Remark</span><strong><?= htmlspecialchars($selectedProject['last_remark'] ?? '-') ?></strong></div>
                </div>
            </section>

            <section class="section-card" id="blockers">
                <div class="section-head">
                    <h2>Blockers / Delays</h2>
                    <a class="btn btn-primary" href="report_blocker.php">Report Project Blocker</a>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Project</th>
                                <th>Blocker Type</th>
                                <th>Title</th>
                                <th>Status</th>
                                <th>Created At</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($blockers ?? [])) : ?>
                                <tr><td colspan="5">No blocker records available.</td></tr>
                            <?php else : ?>
                                <?php foreach ($blockers ?? [] as $item) : ?>
                                    <tr>
                                        <td><?= htmlspecialchars($item['project_name'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($item['blocker_type'] ?? '-') ?></td>
                                        <td><?= htmlspecialchars($item['title'] ?? '-') ?></td>
                                        <td><span class="status-pill <?= statusPillClass($item['status'] ?? 'Reported') ?>"><?= htmlspecialchars($item['status'] ?? 'Reported') ?></span></td>
                                        <td><?= htmlspecialchars($item['created_at'] ?? '-') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="section-card">
                <div class="section-head">
                    <h2>Notifications</h2>
                    <a class="btn btn-outline" href="notifications.php">View All</a>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr><th>Title</th><th>Message</th><th>Date & Time</th><th>Status</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($notifications as $note) : ?>
                                <tr>
                                    <td><?= htmlspecialchars($note['title'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($note['message'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($note['created_at'] ?? '-') ?></td>
                                    <td><span class="status-pill <?= $note['is_read'] ? 'status-resolved' : 'status-progress' ?>"><?= $note['is_read'] ? 'Read' : 'Unread' ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="section-card">
                <div class="section-head">
                    <h2>Fund Utilization</h2>
                    <a class="btn btn-primary" href="fund_utilization.php">Open Details</a>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr><th>Project Name</th><th>Allocated Amount</th><th>Utilized Amount</th><th>Remaining Amount</th><th>Utilization %</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($fundRows as $row) : ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['project_name'] ?? '-') ?></td>
                                    <td><?= formatCurrency($row['allocated_amount'] ?? 0) ?></td>
                                    <td><?= formatCurrency($row['utilized_amount'] ?? 0) ?></td>
                                    <td><?= formatCurrency($row['remaining_amount'] ?? 0) ?></td>
                                    <td><?= (int) ($row['utilization_percentage'] ?? 0) ?>%</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div style="margin-top:18px; display:grid; grid-template-columns:1.2fr 1fr; gap:12px;">
                    <div class="panel-card" style="padding:16px;">
                        <div class="progress-wrap">
                            <strong style="display:block; margin-bottom:10px;">Overall Fund Utilization</strong>
                            <div class="progress-track"><div class="progress-bar" data-progress="<?= $utilizationPercent ?>"></div></div>
                            <small><?= $utilizationPercent ?>% utilization</small>
                        </div>
                    </div>
                    <div class="panel-card" style="padding:16px;">
                        <canvas id="fundChart" height="160"></canvas>
                    </div>
                </div>
            </section>
        </main>
    </div>
</div>

<footer class="footer">
    <div class="footer-grid">
        <div>
            <div>© 2026 Samruddh Shala E-Portal</div>
            <div>School Infrastructure Monitoring System</div>
        </div>
        <div>
            <a href="#">Privacy Policy</a> ·
            <a href="#">Help & Support</a> ·
            <a href="#">Contact</a>
        </div>
        <div><strong>System Status:</strong> Online</div>
    </div>
</footer>

<script src="../assets/js/hm-dashboard.js"></script>
<script>
const totalAllocated = <?= (float) $totalAllocated ?>;
const totalUtilized = <?= (float) $totalUtilized ?>;
const remainingFund = <?= (float) $remainingFund ?>;

new Chart(document.getElementById('fundChart'), {
    type: 'doughnut',
    data: {
        labels: ['Utilized', 'Remaining'],
        datasets: [{
            data: [totalUtilized, remainingFund],
            backgroundColor: ['#eb8825', '#06B6D4'],
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'bottom' } }
    }
});
</script>
</body>
</html>
