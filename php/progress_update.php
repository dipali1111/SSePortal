<?php
require_once __DIR__ . '/includes/functions.php';

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stage = trim($_POST['stage'] ?? '');
    $remarks = trim($_POST['remarks'] ?? '');

    if ($stage === '' || $remarks === '') {
        $message = 'Please select a project stage and add remarks before submitting.';
    } else {
        $photoPath = upload_photo('photo');
        $entry = [
            'stage' => $stage,
            'remarks' => $remarks,
            'photo' => $photoPath,
            'submitted_at' => date('Y-m-d H:i:s')
        ];
        save_progress_update($entry);
        $message = 'Progress update submitted successfully.';
    }
}

$updates = read_json_data('data/progress_updates.json');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Progress Update</title>
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
        <h1>Progress Update</h1>
        <p>HM can select project stage, attach a photo and submit a progress note.</p>
    </div>
    <div class="container">
        <div class="nav">
            <a href="hm_dashboard.php">Dashboard</a>
            <a href="progress_update.php">Progress Update</a>
            <a href="blocker_management.php">Blocker Report</a>
        </div>

        <div class="panel">
            <?php if ($message !== '') { ?><p class="success"><?= e($message) ?></p><?php } ?>
            <form method="post" enctype="multipart/form-data">
                <label>Project Stage</label>
                <select name="stage" required>
                    <option value="">Select stage</option>
                    <option value="Planning">Planning</option>
                    <option value="Design">Design</option>
                    <option value="Execution">Execution</option>
                    <option value="Monitoring">Monitoring</option>
                    <option value="Completion">Completion</option>
                </select>

                <label>Upload Photo</label>
                <input type="file" name="photo" accept="image/*">

                <label>Remarks</label>
                <textarea name="remarks" rows="4" placeholder="Add remarks about the current progress..." required></textarea>

                <button type="submit">Submit Progress Update</button>
            </form>
        </div>

        <div class="panel">
            <h2>Submitted Progress Updates</h2>
            <?php if (empty($updates)) { ?>
                <p>No updates submitted yet.</p>
            <?php } else { ?>
                <ul>
                    <?php foreach (array_reverse($updates) as $update) { ?>
                        <li>
                            <strong><?= e($update['stage'] ?? 'Unknown stage') ?></strong>
                            <div><?= e($update['remarks'] ?? '') ?></div>
                            <div class="meta">Submitted at <?= e($update['submitted_at'] ?? '') ?></div>
                            <?php if (!empty($update['photo'])) { ?><div><a href="<?= e($update['photo']) ?>" target="_blank">View attached photo</a></div><?php } ?>
                        </li>
                    <?php } ?>
                </ul>
            <?php } ?>
        </div>
    </div>
</body>
</html>
