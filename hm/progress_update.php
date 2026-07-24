<?php
require_once dirname(__DIR__) . '/config/db.php';
requireHmAccess();

$pdo = getDbConnection();
$hmId = (int) ($_SESSION['user_id'] ?? 1);
$projects = [];
$message = '';
$error = '';

if ($pdo) {
    $stmt = $pdo->prepare('SELECT id, project_name, stage, progress_percentage FROM projects WHERE hm_id = :hm_id ORDER BY project_name');
    $stmt->execute([':hm_id' => $hmId]);
    $projects = $stmt->fetchAll();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $projectId = (int) ($_POST['project_id'] ?? 0);
    $projectStage = sanitizeText($_POST['project_stage'] ?? '');
    $progressPercentage = (int) ($_POST['progress_percentage'] ?? 0);
    $remarks = sanitizeText($_POST['remarks'] ?? '');

    if (!$projectId || !$projectStage || $progressPercentage < 0 || $progressPercentage > 100 || $remarks === '') {
        $error = 'Please provide a valid project, stage, progress percentage, and remarks.';
    } else {
        $uploadedFiles = [];
        if (!empty($_FILES['photos']['name'])) {
            $targetDir = dirname(__DIR__) . '/uploads/progress/';
            $uploadedFiles = uploadFiles($_FILES['photos'], $targetDir);
        }

        if ($pdo) {
            $stmt = $pdo->prepare('INSERT INTO progress_updates (project_id, hm_id, project_stage, progress_percentage, remarks, photo, submitted_at) VALUES (:project_id, :hm_id, :project_stage, :progress_percentage, :remarks, :photo, NOW())');
            $stmt->execute([
                ':project_id' => $projectId,
                ':hm_id' => $hmId,
                ':project_stage' => $projectStage,
                ':progress_percentage' => $progressPercentage,
                ':remarks' => $remarks,
                ':photo' => implode(',', array_map(fn($item) => basename($item), $uploadedFiles)),
            ]);

            $updateProject = $pdo->prepare('UPDATE projects SET stage = :stage, progress_percentage = :progress_percentage, last_update_date = CURDATE(), last_remark = :remarks, status = CASE WHEN :progress_percentage = 100 THEN "Completed" WHEN :progress_percentage > 0 THEN "In Progress" ELSE "Pending" END WHERE id = :project_id AND hm_id = :hm_id');
            $updateProject->execute([
                ':stage' => $projectStage,
                ':progress_percentage' => $progressPercentage,
                ':remarks' => $remarks,
                ':project_id' => $projectId,
                ':hm_id' => $hmId,
            ]);

            $message = 'Progress update submitted successfully.';
        } else {
            $message = 'Progress update recorded in demo mode.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submit Progress Update | HM Dashboard</title>
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
            <a class="active" href="progress_update.php"><i class="fa-solid fa-file-lines"></i> Progress Updates</a>
            <a href="fund_utilization.php"><i class="fa-solid fa-wallet"></i> Fund Utilization</a>
            <a href="dashboard.php#blockers"><i class="fa-solid fa-triangle-exclamation"></i> Blockers / Delays</a>
            <a href="notifications.php"><i class="fa-solid fa-bell"></i> Notifications</a>
            <a href="profile.php"><i class="fa-solid fa-user"></i> Profile</a>
            <a href="../login.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
        </nav>
    </aside>
    <div class="content-area">
        <header class="topbar">
            <div><h1>Submit Progress Update</h1></div>
            <div class="topbar-actions"><div class="notice-badge"><i class="fa-solid fa-bell"></i><span class="badge">3</span></div></div>
        </header>
        <main class="main-content">
            <?php if ($message) : ?><div class="notice success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
            <?php if ($error) : ?><div class="notice error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            <section class="section-card">
                <form method="post" enctype="multipart/form-data" onsubmit="return validateProgressForm();">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="project_id">Select Assigned Project</label>
                            <select name="project_id" id="project_id" required>
                                <option value="">Select Project</option>
                                <?php foreach ($projects as $project) : ?>
                                    <option value="<?= (int) $project['id'] ?>"><?= htmlspecialchars($project['project_name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="project_stage">Project Stage</label>
                            <select name="project_stage" id="project_stage" required>
                                <option value="">Select Stage</option>
                                <option>Work Not Started</option><option>Foundation</option><option>Construction</option><option>Roofing</option><option>Finishing</option><option>Completed</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="progress_percentage">Progress Percentage</label>
                            <input type="number" min="0" max="100" name="progress_percentage" id="progress_percentage" required>
                        </div>
                        <div class="form-group">
                            <label for="photos">Upload Project Photos</label>
                            <input type="file" name="photos[]" id="photos" multiple accept=".jpg,.jpeg,.png,.webp,image/jpg,image/jpeg,image/png,image/webp" data-photo-preview>
                            <div class="preview-box"></div>
                        </div>
                        <div class="form-group full">
                            <label for="remarks">Remarks</label>
                            <textarea name="remarks" id="remarks" required placeholder="Add progress remarks, site observations, or next milestone updates."></textarea>
                        </div>
                    </div>
                    <div class="action-row">
                        <button type="submit" class="btn btn-primary">Submit Progress Update</button>
                        <a href="dashboard.php" class="btn btn-outline">Back to Dashboard</a>
                    </div>
                </form>
            </section>
        </main>
    </div>
</div>
<footer class="footer"><div class="footer-grid"><div><div>© 2026 Samruddh Shala E-Portal</div><div>School Infrastructure Monitoring System</div></div><div><a href="#">Privacy Policy</a> · <a href="#">Help & Support</a> · <a href="#">Contact</a></div><div><strong>System Status:</strong> Online</div></div></footer>
<script src="../assets/js/hm-dashboard.js"></script>
<script>
function validateProgressForm() {
    const projectId = document.getElementById('project_id').value;
    const stage = document.getElementById('project_stage').value;
    const percent = parseInt(document.getElementById('progress_percentage').value, 10);
    const remarks = document.getElementById('remarks').value.trim();
    if (!projectId || !stage || Number.isNaN(percent) || percent < 0 || percent > 100 || remarks === '') {
        alert('Please enter a valid project, stage, progress percentage, and remarks.');
        return false;
    }
    return true;
}
</script>
</body>
</html>
