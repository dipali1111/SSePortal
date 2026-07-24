<?php
require_once __DIR__ . '/includes/functions.php';

$assignedWorks = [
    ['title' => 'School Building Renovation', 'location' => 'Harohalli', 'status' => 'In Progress'],
    ['title' => 'Water Supply Installation', 'location' => 'Kengeri', 'status' => 'Pending Materials'],
    ['title' => 'Sanitation Facility Upgrade', 'location' => 'Mysuru Road', 'status' => 'Ready for Review']
];

$notifications = [
    'New inspection request received from the district office.',
    'Funding approval for the next phase is pending.',
    'Two photos for the current site visit are awaiting review.'
];

$progressEntries = array_slice(read_json_data('data/progress_updates.json'), -3);
$blockers = array_slice(read_json_data('data/blockers.json'), -3);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HM Dashboard</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background: #f5f7fb; color: #233; }
        .topbar { background: #0f6cbd; color: #fff; padding: 16px 24px; }
        .container { max-width: 1100px; margin: 0 auto; padding: 20px; }
        .cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin: 20px 0; }
        .card { background: #fff; border-radius: 10px; padding: 16px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
        .card h3 { margin-top: 0; }
        .nav { display: flex; gap: 12px; margin-top: 12px; flex-wrap: wrap; }
        .nav a { background: #eaf4ff; padding: 8px 12px; border-radius: 6px; text-decoration: none; color: #0f6cbd; font-weight: bold; }
        .panel { background: #fff; border-radius: 10px; padding: 16px; margin-bottom: 16px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px; border-bottom: 1px solid #eee; text-align: left; }
        .pill { display: inline-block; padding: 4px 8px; border-radius: 999px; background: #e7f7ec; color: #1f7d45; font-size: 12px; }
    </style>
</head>
<body>
    <div class="topbar">
        <h1>HM Dashboard</h1>
        <p>Monitor project work, updates, blockers and fund activity in one place.</p>
    </div>
    <div class="container">
        <div class="nav">
            <a href="hm_dashboard.php">Dashboard</a>
            <a href="progress_update.php">Progress Update</a>
            <a href="blocker_management.php">Blocker Report</a>
            <a href="login.php">Back to login</a>
        </div>

        <div class="cards">
            <div class="card">
                <h3>Assigned Works</h3>
                <p><strong>3</strong> active assignments</p>
            </div>
            <div class="card">
                <h3>Notifications</h3>
                <p><strong>3</strong> new updates</p>
            </div>
            <div class="card">
                <h3>Progress Status</h3>
                <p><strong>2</strong> projects in progress</p>
            </div>
            <div class="card">
                <h3>Fund Utilization</h3>
                <p><strong>72%</strong> utilized</p>
            </div>
        </div>

        <div class="panel">
            <h2>Assigned Works</h2>
            <table>
                <thead>
                    <tr><th>Project</th><th>Location</th><th>Status</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($assignedWorks as $work) { ?>
                        <tr>
                            <td><?= e($work['title']) ?></td>
                            <td><?= e($work['location']) ?></td>
                            <td><span class="pill"><?= e($work['status']) ?></span></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>

        <div class="panel">
            <h2>Notifications</h2>
            <ul>
                <?php foreach ($notifications as $note) { ?>
                    <li><?= e($note) ?></li>
                <?php } ?>
            </ul>
        </div>

        <div class="panel">
            <h2>Recent Progress Updates</h2>
            <?php if (empty($progressEntries)) { ?>
                <p>No updates submitted yet.</p>
            <?php } else { ?>
                <ul>
                    <?php foreach ($progressEntries as $entry) { ?>
                        <li><strong><?= e($entry['stage'] ?? 'Unknown stage') ?></strong> — <?= e($entry['remarks'] ?? '') ?></li>
                    <?php } ?>
                </ul>
            <?php } ?>
        </div>

        <div class="panel">
            <h2>Latest Blockers</h2>
            <?php if (empty($blockers)) { ?>
                <p>No blockers reported.</p>
            <?php } else { ?>
                <ul>
                    <?php foreach ($blockers as $blocker) { ?>
                        <li><strong><?= e($blocker['project'] ?? 'Project') ?></strong> — <?= e($blocker['reason'] ?? '') ?> <span class="pill"><?= e($blocker['severity'] ?? 'Medium') ?></span></li>
                    <?php } ?>
                </ul>
            <?php } ?>
        </div>
    </div>
</body>
</html>
