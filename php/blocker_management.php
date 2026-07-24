<?php
require_once __DIR__ . '/includes/functions.php';

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $project = trim($_POST['project'] ?? '');
    $reason = trim($_POST['reason'] ?? '');
    $severity = trim($_POST['severity'] ?? 'Medium');

    if ($project === '' || $reason === '') {
        $message = 'Please provide the project name and blocker reason.';
    } else {
        $entry = [
            'project' => $project,
            'reason' => $reason,
            'severity' => $severity,
            'reported_at' => date('Y-m-d H:i:s')
        ];
        save_blocker($entry);
        $message = 'Blocker reported successfully.';
    }
}

$blockers = read_json_data('data/blockers.json');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blocker Management</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background: #f5f7fb; color: #233; }
        .topbar { background: #0f6cbd; color: #fff; padding: 16px 24px; }
        .container { max-width: 900px; margin: 0 auto; padding: 20px; }
        .panel { background: #fff; border-radius: 10px; padding: 16px; margin-bottom: 16px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
        form { display: grid; gap: 12px; }
        input, select, textarea, button { padding: 10px; border-radius: 6px; border: 1px solid #dbe4ee; font-size: 14px; }
        button { background: #0f6cbd; color: #fff; border: none; cursor: pointer; }
        .nav { display: flex; gap: 12px; margin-top: 12px; flex-wrap: wrap; }
        .nav a { background: #eaf4ff; padding: 8px 12px; border-radius: 6px; text-decoration: none; color: #0f6cbd; font-weight: bold; }
        .success { color: #1f7d45; font-weight: bold; }
        .meta { color: #666; font-size: 12px; }
    </style>
</head>
<body>
    <div class="topbar">
        <h1>Blocker Management</h1>
        <p>HM can report blockers with proper reasons for project delay.</p>
    </div>
    <div class="container">
        <div class="nav">
            <a href="hm_dashboard.php">Dashboard</a>
            <a href="progress_update.php">Progress Update</a>
            <a href="blocker_management.php">Blocker Report</a>
        </div>

        <div class="panel">
            <?php if ($message !== '') { ?><p class="success"><?= e($message) ?></p><?php } ?>
            <form method="post">
                <label>Project Name</label>
                <input type="text" name="project" placeholder="Enter project name" required>

                <label>Reason for Delay</label>
                <textarea name="reason" rows="4" placeholder="Explain the blocker clearly" required></textarea>

                <label>Severity</label>
                <select name="severity">
                    <option value="Low">Low</option>
                    <option value="Medium" selected>Medium</option>
                    <option value="High">High</option>
                </select>

                <button type="submit">Report Blocker</button>
            </form>
        </div>

        <div class="panel">
            <h2>Reported Blockers</h2>
            <?php if (empty($blockers)) { ?>
                <p>No blockers reported yet.</p>
            <?php } else { ?>
                <ul>
                    <?php foreach (array_reverse($blockers) as $blocker) { ?>
                        <li>
                            <strong><?= e($blocker['project'] ?? 'Project') ?></strong>
                            <div><?= e($blocker['reason'] ?? '') ?></div>
                            <div class="meta">Severity: <?= e($blocker['severity'] ?? 'Medium') ?> | Reported at <?= e($blocker['reported_at'] ?? '') ?></div>
                        </li>
                    <?php } ?>
                </ul>
            <?php } ?>
        </div>
    </div>
</body>
</html>
