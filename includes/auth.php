<?php
require_once __DIR__ . '/../config/db.php';
function require_login() {
  if (empty($_SESSION['user'])) { header('Location: index.php'); exit; }
}
function require_role($roles) {
  require_login();
  $roles = (array)$roles;
  if (!in_array($_SESSION['user']['role'], $roles)) {
    http_response_code(403);
    die('Forbidden');
  }
}
function generate_alerts($conn) {
  // Overdue alert generation
  $conn->query("INSERT INTO alerts (work_id, school_id, type, message)
    SELECT w.id, w.school_id, 'overdue', CONCAT('Work \"', w.title, '\" is overdue')
    FROM works w
    WHERE w.deadline < CURDATE() AND w.status <> 'completed'
      AND NOT EXISTS (SELECT 1 FROM alerts a WHERE a.work_id = w.id AND a.type='overdue' AND DATE(a.created_at)=CURDATE())");
  // Low progress alert (deadline within 3 days, progress < 50)
  $conn->query("INSERT INTO alerts (work_id, school_id, type, message)
    SELECT w.id, w.school_id, 'low_progress', CONCAT('Low progress on \"', w.title, '\" - only ', w.progress, '% done')
    FROM works w
    WHERE w.deadline BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 3 DAY)
      AND w.status <> 'completed' AND w.progress < 50
      AND NOT EXISTS (SELECT 1 FROM alerts a WHERE a.work_id = w.id AND a.type='low_progress' AND DATE(a.created_at)=CURDATE())");
}
