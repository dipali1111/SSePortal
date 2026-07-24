<?php
require_once dirname(__DIR__) . '/config/db.php';
requireHmAccess();

$pdo = getDbConnection();
$hmId = (int) ($_SESSION['user_id'] ?? 1);
$projects = [];
$message = '';
$error = '';

if ($pdo) {
    $stmt = $pdo->prepare('SELECT id, project_name FROM projects WHERE hm_id = :hm_id ORDER BY project_name');
    $stmt->execute([':hm_id' => $hmId]);
    $projects = $stmt->fetchAll();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $projectId = (int) ($_POST['project_id'] ?? 0);
    $blockerType = sanitizeText($_POST['blocker_type'] ?? '');
    $title = sanitizeText($_POST['title'] ?? '');
    $reason = sanitizeText($_POST['reason'] ?? '');
    $expectedImpact = sanitizeText($_POST['expected_impact'] ?? '');

    if (!$projectId || !$blockerType || !$title || !$reason || !$expectedImpact) {
        $error = 'All blocker fields are required.';
    } else {
        $attachment = '';
        if (!empty($_FILES['attachment']['name'])) {
            $targetDir = dirname(__DIR__) . '/uploads/blockers/';
            $uploaded = uploadFiles($_FILES['attachment'], $targetDir);
            $attachment = basename($uploaded[0] ?? '');
        }

        if ($pdo) {
            $stmt = $pdo->prepare('INSERT INTO blockers (project_id, hm_id, blocker_type, title, reason, expected_impact, attachment, status, created_at) VALUES (:project_id, :hm_id, :blocker_type, :title, :reason, :expected_impact, :attachment, "Reported", NOW())');
            $stmt->execute([
                ':project_id' => $projectId,
                ':hm_id' => $hmId,
                ':blocker_type' => $blockerType,
                ':title' => $title,
                ':reason' => $reason,
                ':expected_impact' => $expectedImpact,
                ':attachment' => $attachment,
            ]);
            $message = 'Blocker has been reported successfully.';
        } else {
            $message = 'Blocker recorded in demo mode.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Blocker | HM Dashboard</title>
    <link rel="stylesheet" href="../assets/css/hm-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>
<div class="app-shell">
    <aside class="sidebar open">
        <div class="sidebar-header"> <div class="brand"><div class="brand-badge"><i class="fa-solid fa-school"></i></div><div><div class="brand-name">Samruddh Shala</div><div class="brand-subtitle">E-Portal</div></div></div></div>
        <nav class="sidebar-nav">
            <a href="dashboard.php"><i class="fa-solid fa-chart-pie"></i> Dashboard</a>
            <a href="dashboard.php#assigned-works"><i class="fa-solid fa-briefcase"></i> Assigned Works</a>
            <a href="progress_update.php"><i class="fa-solid fa-file-lines"></i> Progress Updates</a>
            <a href="fund_utilization.php"><i class="fa-solid fa-wallet"></i> Fund Utilization</a>
            <a class="active" href="report_blocker.php"><i class="fa-solid fa-triangle-exclamation"></i> Blockers / Delays</a>
            <a href="notifications.php"><i class="fa-solid fa-bell"></i> Notifications</a>
            <a href="profile.php"><i class="fa-solid fa-user"></i> Profile</a>
            <a href="../login.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
        </nav>
    </aside>
    <div class="content-area">
        <header class="topbar"><div><h1>Report Project Blocker</h1></div><div class="topbar-actions"><div class="notice-badge"><i class="fa-solid fa-bell"></i><span class="badge">2</span></div></div></header>
        <main class="main-content">
            <?php if ($message) : ?><div class="notice success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
            <?php if ($error) : ?><div class="notice error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            <section class="section-card">
                <form method="post" enctype="multipart/form-data" onsubmit="return validateBlockerForm();">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="project_id">Select Project</label>
                            <select name="project_id" id="project_id" required>
                                <option value="">Select Project</option>
                                <?php foreach ($projects as $project) : ?>
                                    <option value="<?= (int) $project['id'] ?>"><?= htmlspecialchars($project['project_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="blocker_type">Blocker Type</label>
                            <select name="blocker_type" id="blocker_type" required>
                                <option value="">Select Type</option>
                                <option>Fund Delay</option><option>Material Shortage</option><option>Labour Shortage</option><option>Weather Problem</option><option>Approval Delay</option><option>Contractor Issue</option><option>Technical Problem</option><option>Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="title">Blocker Title</label>
                            <input type="text" name="title" id="title" required>
                        </div>
                        <div class="form-group">
                            <label for="expected_impact">Expected Impact</label>
                            <input type="text" name="expected_impact" id="expected_impact" required>
                        </div>
                        <div class="form-group full">
                            <label for="reason">Detailed Reason for Delay</label>
                            <textarea name="reason" id="reason" required></textarea>
                        </div>
                        <div class="form-group full">
                            <label for="attachment">Optional Photo / Document Upload</label>
                            <input type="file" name="attachment" id="attachment" accept=".jpg,.jpeg,.png,.webp,.pdf,image/jpg,image/jpeg,image/png,image/webp,application/pdf">
                        </div>
                    </div>
                    <div class="action-row">
                        <button type="submit" class="btn btn-primary">Submit Blocker</button>
                        <a href="dashboard.php" class="btn btn-outline">Back to Dashboard</a>
                    </div>
                </form>
            </section>
        </main>
        <footer class="footer"><div class="footer-grid"><div><div>© 2026 Samruddh Shala E-Portal</div><div>School Infrastructure Monitoring System</div></div><div><a href="#">Privacy Policy</a> · <a href="#">Help & Support</a> · <a href="#">Contact</a></div><div><strong>System Status:</strong> Online</div></div></footer>
    </div>
</div>
<script src="../assets/js/hm-dashboard.js"></script>
<script>
function validateBlockerForm() {
    const project = document.getElementById('project_id').value;
    const type = document.getElementById('blocker_type').value;
    const title = document.getElementById('title').value.trim();
    const reason = document.getElementById('reason').value.trim();
    const impact = document.getElementById('expected_impact').value.trim();
    if (!project || !type || !title || !reason || !impact) {
        alert('Please complete all required blocker details.');
        return false;
    }
    return true;
}
</script>
</body>
</html>
