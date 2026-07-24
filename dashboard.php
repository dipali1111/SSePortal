<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
generate_alerts($conn);
$user = $_SESSION['user'];
$namecol = $LANG === 'mr' ? 'name_mr' : 'name_en';

// HM sees only their school. CEO/admin can pick taluka/school.
$school_id = null;
$taluka_id = null;
if ($user['role'] === 'hm') {
  $school_id = (int)$user['school_id'];
} else {
  $taluka_id = isset($_GET['taluka']) && $_GET['taluka'] !== '' ? (int)$_GET['taluka'] : null;
  $school_id = isset($_GET['school']) && $_GET['school'] !== '' ? (int)$_GET['school'] : null;
}

// Filter builder (works/blockers use alias w + s)
$where = "1=1";
$params = [];
$types = '';
if ($school_id) { $where .= " AND w.school_id = ?"; $params[] = $school_id; $types .= 'i'; }
elseif ($taluka_id) { $where .= " AND s.taluka_id = ?"; $params[] = $taluka_id; $types .= 'i'; }

// Separate filter for alerts (alias a + s, no w table)
$awhere = "1=1";
$aparams = [];
$atypes = '';
if ($school_id) { $awhere .= " AND a.school_id = ?"; $aparams[] = $school_id; $atypes .= 'i'; }
elseif ($taluka_id) { $awhere .= " AND s.taluka_id = ?"; $aparams[] = $taluka_id; $atypes .= 'i'; }

// Stats
function q($conn,$sql,$types='',$params=[]) {
  $stmt=$conn->prepare($sql);
  if ($types) $stmt->bind_param($types,...$params);
  $stmt->execute();
  return $stmt->get_result();
}
$total = q($conn,"SELECT COUNT(*) c FROM works w JOIN schools s ON s.id=w.school_id WHERE $where",$types,$params)->fetch_assoc()['c'];
$overdue = q($conn,"SELECT COUNT(*) c FROM works w JOIN schools s ON s.id=w.school_id WHERE $where AND w.deadline < CURDATE() AND w.status<>'completed'",$types,$params)->fetch_assoc()['c'];
$blockersCount = q($conn,"SELECT COUNT(*) c FROM blockers b JOIN works w ON w.id=b.work_id JOIN schools s ON s.id=w.school_id WHERE $where AND b.resolved=0",$types,$params)->fetch_assoc()['c'];

// Status breakdown (for bar + pie charts) — overdue counted as its own bucket
$statusRes = q($conn,"
  SELECT CASE WHEN w.deadline < CURDATE() AND w.status<>'completed' THEN 'overdue' ELSE w.status END AS st,
         COUNT(*) c
  FROM works w JOIN schools s ON s.id=w.school_id
  WHERE $where
  GROUP BY st", $types, $params);
$statusMap = ['pending'=>0,'in_progress'=>0,'completed'=>0,'overdue'=>0];
while ($r = $statusRes->fetch_assoc()) {
  if (isset($statusMap[$r['st']])) $statusMap[$r['st']] = (int)$r['c'];
  else $statusMap[$r['st']] = (int)$r['c'];
}
$completedPct = $total > 0 ? round(($statusMap['completed'] / $total) * 100) : 0;

// Taluka-wise breakdown (only meaningful for admin/ceo who can see multiple talukas)
$talukaChart = ['labels' => [], 'total' => [], 'completed' => [], 'overdue' => []];
if ($user['role'] !== 'hm' && !$school_id) {
  $tsql = "SELECT t.$namecol AS taluka_name,
                  COUNT(*) AS total_c,
                  SUM(CASE WHEN w.status='completed' THEN 1 ELSE 0 END) AS completed_c,
                  SUM(CASE WHEN w.deadline < CURDATE() AND w.status<>'completed' THEN 1 ELSE 0 END) AS overdue_c
           FROM works w
           JOIN schools s ON s.id=w.school_id
           JOIN talukas t ON t.id=s.taluka_id
           WHERE $where";
  if ($taluka_id) $tsql .= " AND s.taluka_id = ?";
  $tsql .= " GROUP BY t.id ORDER BY t.$namecol";
  $tParams = $params; $tTypes = $types;
  if ($taluka_id) { $tParams[] = $taluka_id; $tTypes .= 'i'; }
  $tRes = q($conn, $tsql, $tTypes, $tParams);
  while ($row = $tRes->fetch_assoc()) {
    $talukaChart['labels'][] = $row['taluka_name'];
    $talukaChart['total'][] = (int)$row['total_c'];
    $talukaChart['completed'][] = (int)$row['completed_c'];
    $talukaChart['overdue'][] = (int)$row['overdue_c'];
  }
}

// Open blockers for current filter (reuses $where/$params/$types since it joins w + s the same way)
$openBlockers = q($conn,"
  SELECT b.id, b.issue, b.suggested_solution, b.created_at, b.resolved,
         w.id AS work_id, w.title, s.$namecol AS school_name
  FROM blockers b
  JOIN works w ON w.id=b.work_id
  JOIN schools s ON s.id=w.school_id
  WHERE $where AND b.resolved=0
  ORDER BY b.created_at DESC LIMIT 15", $types, $params);

// Recent alerts for current filter
$alertsRes = q($conn,"
  SELECT a.*, s.$namecol AS school_name
  FROM alerts a
  JOIN schools s ON s.id=a.school_id
  WHERE $awhere
  ORDER BY a.created_at DESC LIMIT 15", $atypes, $aparams);

// Talukas + schools for filters
$talukas = $conn->query("SELECT id, $namecol AS name FROM talukas ORDER BY $namecol");
$schools_res = $conn->query("SELECT s.id, s.$namecol AS name, s.taluka_id FROM schools s ORDER BY s.$namecol");

// Works list
$sql = "SELECT w.*, s.$namecol AS school_name, t.$namecol AS taluka_name
        FROM works w
        JOIN schools s ON s.id=w.school_id
        JOIN talukas t ON t.id=s.taluka_id
        WHERE $where ORDER BY w.deadline ASC";
$works = q($conn,$sql,$types,$params);

include __DIR__ . '/includes/header.php';
?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
/* ===== School dashboard — professional orange theme ===== */
:root{
  --fx-primary:#FF7A1A;
  --fx-primary-dark:#E85D00;
  --fx-primary-light:#FFA55C;
  --fx-amber:#F5A623;
  --fx-red:#E8503A;
  --fx-green:#2FAE66;
  --fx-blue:#3B82C4;
  --fx-bg:#FBF6F0;
  --fx-card:#FFFFFF;
  --fx-border:#F1DFC8;
  --fx-text:#3A2C1B;
  --fx-text-soft:#8A7660;
  --fx-shadow:0 4px 18px rgba(232,93,0,0.08);
  --fx-shadow-lg:0 10px 30px rgba(232,93,0,0.12);
}
body{
  font-family:'Inter',-apple-system,BlinkMacSystemFont,sans-serif;
  color:var(--fx-text);
  background-color:var(--fx-bg) !important;
  background-image:
    radial-gradient(circle at 8% 12%, rgba(255,122,26,0.07), transparent 38%),
    radial-gradient(circle at 92% 88%, rgba(245,166,35,0.08), transparent 42%),
    url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='180' height='180' viewBox='0 0 180 180'%3E%3Cg fill='none' stroke='%23FFB673' stroke-width='1.4' opacity='0.16'%3E%3Cpath d='M20 40 L20 20 L45 20 L45 40 M20 25 L45 25' /%3E%3Cpath d='M30 30 L30 55' /%3E%3Ccircle cx='110' cy='30' r='9'/%3E%3Cpath d='M104 38 q6 10 12 0'/%3E%3Cpath d='M140 100 l14 -14 4 4 -14 14 -6 2 2 -6z'/%3E%3Cpath d='M70 100 q0 -18 18 -18 q18 0 18 18 q0 12 -18 22 q-18 -10 -18 -22z'/%3E%3Cpath d='M88 82 l0 -14 M80 70 l16 0'/%3E%3Cpath d='M20 130 L50 130 L50 155 L20 155 Z M20 138 L50 138'/%3E%3Cpath d='M150 150 L165 150 L165 165 L150 165 Z' /%3E%3C/g%3E%3C/svg%3E");
  background-attachment:fixed;
}
h1,h2,h3,h4,.hero h2{ font-family:'Poppins',sans-serif; }

.hero{
  background:linear-gradient(120deg,var(--fx-primary-dark),var(--fx-primary) 60%,var(--fx-primary-light));
  border-radius:18px; padding:26px 30px;
  box-shadow:var(--fx-shadow-lg);
  position:relative; overflow:hidden;
}
.hero::after{
  content:"";
  position:absolute; right:-30px; top:-30px; width:220px; height:220px;
  background:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='220' height='220'%3E%3Cg fill='none' stroke='white' stroke-width='2' opacity='0.15'%3E%3Ccircle cx='150' cy='60' r='16'/%3E%3Cpath d='M138 72 q12 18 24 0'/%3E%3Cpath d='M60 140 q0 -26 26 -26 q26 0 26 26 q0 18 -26 32 q-26 -14 -26 -32z'/%3E%3Cpath d='M86 114 l0 -20 M74 96 l24 0'/%3E%3Cpath d='M140 150 l20 -20 6 6 -20 20 -9 3 3 -9z'/%3E%3C/g%3E%3C/svg%3E") no-repeat;
  background-size:contain; pointer-events:none;
}
.hero h2{ color:#fff; font-weight:800; letter-spacing:.3px; margin-bottom:2px; }
.hero .text-white-50{ color:rgba(11, 11, 11, 0.85) !important; font-weight:500; }
.hero select.form-select{
  border:none; border-radius:10px; font-weight:500; color:var(--fx-text);
  box-shadow:var(--fx-shadow); min-width:170px;
}

.stat-card{
  background:var(--fx-card);
  border-radius:16px; padding:20px 22px;
  border:1px solid var(--fx-border);
  box-shadow:var(--fx-shadow);
  position:relative; overflow:hidden;
  transition:transform .18s ease, box-shadow .18s ease;
}
.stat-card:hover{ transform:translateY(-4px); box-shadow:var(--fx-shadow-lg); }
.stat-card::before{
  content:""; position:absolute; left:0; top:0; bottom:0; width:5px;
  background:var(--stat-accent, var(--fx-primary));
}
.stat-card .stat-icon{
  width:46px; height:46px; border-radius:12px; display:flex; align-items:center; justify-content:center;
  background:var(--stat-accent, var(--fx-primary)); color:#fff; font-size:1.25rem;
  box-shadow:0 6px 14px -4px var(--stat-accent, var(--fx-primary));
  margin-bottom:12px;
}
.stat-card .stat-label{ color:var(--fx-text-soft); font-size:.82rem; font-weight:600; text-transform:uppercase; letter-spacing:.4px; }
.stat-card .stat-value{ font-family:'Poppins',sans-serif; font-size:2.1rem; font-weight:700; color:var(--fx-text); line-height:1.1; }
.stat-card.is-warning{ --stat-accent: var(--fx-red); }
.stat-card.is-amber{ --stat-accent: var(--fx-amber); }
.stat-card.is-primary{ --stat-accent: var(--fx-primary); }

.card-fx{
  background:var(--fx-card);
  border:1px solid var(--fx-border);
  border-radius:16px; overflow:hidden;
  box-shadow:var(--fx-shadow);
}
.card-fx .card-header{
  background:linear-gradient(90deg, rgba(255,122,26,0.10), transparent);
  border-bottom:1px solid var(--fx-border);
  color:var(--fx-primary-dark); font-weight:700; font-family:'Poppins',sans-serif;
  font-size:.95rem; padding:14px 18px;
}

.table-fx{ color:var(--fx-text); margin-bottom:0; }
.table-fx thead th{
  color:var(--fx-text-soft); border-bottom:2px solid var(--fx-border); font-weight:700;
  font-size:.78rem; text-transform:uppercase; letter-spacing:.4px; background:#FFFBF6;
}
.table-fx td, .table-fx th{ border-color:var(--fx-border); vertical-align:middle; }
.table-fx tbody tr:hover{ background:rgba(255,122,26,0.05); }

.badge-overdue{ background:linear-gradient(135deg,var(--fx-red),#ff8a5c); color:#fff; }
.badge-completed{ background:linear-gradient(135deg,#1f9e57,#4ade80); color:#fff; }
.badge-inprogress{ background:linear-gradient(135deg,var(--fx-amber),#ffcf7a); color:#4a2e00; }
.badge-pending{ background:#F1E6D8; color:var(--fx-text-soft); border:1px solid var(--fx-border); }
.badge{ font-weight:600; padding:.4em .75em; border-radius:8px; }

.progress{ background:#F1E6D8; height:8px; border-radius:6px; }
.progress-bar{ background:linear-gradient(90deg,var(--fx-amber),var(--fx-primary)); border-radius:6px; }

.btn-glow{
  background:linear-gradient(135deg,var(--fx-primary-dark),var(--fx-primary));
  border:none; color:#fff; font-weight:600; box-shadow:0 4px 12px rgba(232,93,0,0.3);
  transition:box-shadow .2s ease, transform .2s ease;
}
.btn-glow:hover{ box-shadow:0 6px 18px rgba(232,93,0,0.4); transform:translateY(-1px); color:#fff; }

.alert-fx{ background:#FFF6EC; border:1px solid var(--fx-border); border-radius:12px; color:var(--fx-text); }
.blocker-card{
  background:#FFF9F3; border:1px solid var(--fx-border); border-left:4px solid var(--fx-red);
  border-radius:10px; padding:14px; margin-bottom:12px; color:var(--fx-text);
}
.blocker-card .solution{ color:var(--fx-primary-dark); }
.chart-box{ background:#FFFCF8; border-radius:12px; padding:14px; }

.days-chip{ font-size:.72rem; padding:2px 9px; border-radius:999px; font-weight:600; }
.days-chip.late{ background:rgba(232,80,58,0.12); color:var(--fx-red); border:1px solid rgba(232,80,58,0.3); }
.days-chip.soon{ background:rgba(245,166,35,0.15); color:#b3720b; border:1px solid rgba(245,166,35,0.35); }
.days-chip.ok{ background:rgba(47,174,102,0.12); color:var(--fx-green); border:1px solid rgba(47,174,102,0.3); }
</style>

<div class="hero d-flex justify-content-between align-items-center flex-wrap gap-3">
  <div>
    <h2 class="mb-1"><i class="bi bi-mortarboard-fill"></i> <?= t('dashboard') ?></h2>
    <div class="text-white-50"><?= t('welcome') ?>, <?= htmlspecialchars($user['full_name']) ?></div>
  </div>
  <?php if ($user['role'] !== 'hm'): ?>
  <form class="d-flex gap-2 flex-wrap" method="get">
    <select class="form-select" name="taluka" onchange="this.form.submit()">
      <option value=""><?= t('all_talukas') ?></option>
      <?php while($t=$talukas->fetch_assoc()): ?>
        <option value="<?= $t['id'] ?>" <?= $taluka_id==$t['id']?'selected':'' ?>><?= htmlspecialchars($t['name']) ?></option>
      <?php endwhile; ?>
    </select>
    <select class="form-select" name="school" onchange="this.form.submit()">
      <option value=""><?= t('all_schools') ?></option>
      <?php while($s=$schools_res->fetch_assoc()):
        if ($taluka_id && $s['taluka_id']!=$taluka_id) continue; ?>
        <option value="<?= $s['id'] ?>" <?= $school_id==$s['id']?'selected':'' ?>><?= htmlspecialchars($s['name']) ?></option>
      <?php endwhile; ?>
    </select>
  </form>
  <?php endif; ?>
</div>

<div class="row g-3 mb-4 mt-1">
  <div class="col-md-4">
    <div class="stat-card is-primary">
      <div class="stat-icon"><i class="bi bi-journal-check"></i></div>
      <div class="stat-label"><?= t('total_works') ?></div>
      <div class="stat-value"><?= $total ?></div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="stat-card is-warning">
      <div class="stat-icon"><i class="bi bi-exclamation-triangle-fill"></i></div>
      <div class="stat-label"><?= t('overdue_works') ?></div>
      <div class="stat-value"><?= $overdue ?></div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="stat-card is-amber">
      <div class="stat-icon"><i class="bi bi-cone-striped"></i></div>
      <div class="stat-label"><?= t('active_blockers') ?></div>
      <div class="stat-value"><?= $blockersCount ?></div>
    </div>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-lg-5">
    <div class="card-fx h-100">
      <div class="card-header"><i class="bi bi-bar-chart-line"></i> <?= t('works') ?> <?= t('status') ?></div>
      <div class="p-3 chart-box"><canvas id="statusBarChart" height="200"></canvas></div>
    </div>
  </div>
  <div class="col-lg-3">
    <div class="card-fx h-100">
      <div class="card-header"><i class="bi bi-pie-chart"></i> <?= t('status') ?> <?= t('progress') ?></div>
      <div class="p-3 chart-box d-flex flex-column align-items-center justify-content-center">
        <canvas id="statusDoughnut" height="200"></canvas>
        <div class="text-center mt-2">
          <div class="fw-bold" style="font-size:1.4rem;color:var(--fx-primary-dark)"><?= $completedPct ?>%</div>
          <div class="small text-secondary"><?= t('completed') ?></div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card-fx h-100">
      <div class="card-header"><i class="bi bi-signpost-split"></i> <?= $user['role']!=='hm' && !$school_id ? t('taluka') : t('status') ?></div>
      <div class="p-3 chart-box">
        <?php if (!empty($talukaChart['labels'])): ?>
          <canvas id="talukaChart" height="200"></canvas>
        <?php else: ?>
          <canvas id="statusPieChart" height="200"></canvas>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-md-6">
    <div class="card-fx h-100">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-shield-exclamation"></i> <?= t('blockers') ?></span>
        <span class="badge badge-overdue"><?= $openBlockers->num_rows ?></span>
      </div>
      <div class="p-3" style="max-height:320px;overflow:auto">
        <?php if (!$openBlockers->num_rows): ?><div class="text-secondary">—</div><?php endif; ?>
        <?php while($b=$openBlockers->fetch_assoc()): ?>
          <div class="blocker-card">
            <div class="d-flex justify-content-between flex-wrap">
              <div><b><?= htmlspecialchars($b['title']) ?></b> · <span class="text-secondary"><?= htmlspecialchars($b['school_name']) ?></span></div>
              <span class="small text-secondary"><?= $b['created_at'] ?></span>
            </div>
            <div><?= htmlspecialchars($b['issue']) ?></div>
            <?php if (!empty($b['suggested_solution'])): ?>
              <div class="solution mt-1"><i class="bi bi-lightbulb"></i> <b><?= t('suggested_solution') ?>:</b> <?= htmlspecialchars($b['suggested_solution']) ?></div>
            <?php else: ?>
              <div class="small text-secondary mt-1"><i class="bi bi-hourglass-split"></i> <?= t('no_alerts') /* awaiting a solution reply */ ?></div>
            <?php endif; ?>
            <a class="btn btn-sm btn-glow mt-2" href="work.php?id=<?= $b['work_id'] ?>"><?= t('view') ?></a>
          </div>
        <?php endwhile; ?>
      </div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card-fx h-100">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-bell"></i> <?= t('alerts') ?></span>
        <span class="badge badge-overdue"><?= $alertsRes->num_rows ?></span>
      </div>
      <div class="p-3" style="max-height:320px;overflow:auto">
        <?php if (!$alertsRes->num_rows): ?><div class="text-secondary"><?= t('no_alerts') ?></div><?php endif; ?>
        <?php while($a=$alertsRes->fetch_assoc()): ?>
          <div class="alert-fx p-2 mb-2">
            <div class="small text-secondary"><?= $a['created_at'] ?> · <?= htmlspecialchars($a['school_name']) ?></div>
            <div><span class="badge badge-overdue me-1"><?= strtoupper($a['type']) ?></span><?= htmlspecialchars($a['message']) ?>
              <a class="ms-2" href="work.php?id=<?= $a['work_id'] ?>"><?= t('view') ?></a>
            </div>
          </div>
        <?php endwhile; ?>
      </div>
    </div>
  </div>
</div>

<div class="card-fx mb-4">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span><i class="bi bi-clipboard-check"></i> <?= t('works') ?></span>
    <?php if (in_array($user['role'], ['hm','admin','ceo'])): ?>
      <a class="btn btn-sm btn-glow" href="add_work.php"><i class="bi bi-plus-circle"></i> <?= t('add_work') ?></a>
    <?php endif; ?>
  </div>
  <div class="table-responsive">
    <table class="table table-fx mb-0 align-middle">
      <thead><tr>
        <th><?= t('title') ?></th><th><?= t('school') ?></th><th><?= t('taluka') ?></th>
        <th><?= t('deadline') ?></th><th><?= t('progress') ?></th><th><?= t('status') ?></th><th></th>
      </tr></thead>
      <tbody>
      <?php while($w=$works->fetch_assoc()):
        $overdueRow = ($w['deadline'] < date('Y-m-d') && $w['status']!=='completed');
        $badge = $overdueRow ? 'badge-overdue' : ($w['status']==='completed'?'badge-completed':($w['status']==='in_progress'?'badge-inprogress':'badge-pending'));
        $label = $overdueRow ? t('overdue') : t($w['status']);
        $daysDiff = (strtotime($w['deadline']) - strtotime(date('Y-m-d'))) / 86400;
        if ($w['status']==='completed') { $chip=''; }
        elseif ($daysDiff < 0) { $chip = '<span class="days-chip late">'.abs((int)$daysDiff).' '.t('overdue').'</span>'; }
        elseif ($daysDiff <= 3) { $chip = '<span class="days-chip soon">'.(int)$daysDiff.'d</span>'; }
        else { $chip = '<span class="days-chip ok">'.(int)$daysDiff.'d</span>'; }
      ?>
        <tr>
          <td><b><?= htmlspecialchars($w['title']) ?></b><div class="small text-secondary"><?= htmlspecialchars(mb_strimwidth($w['description']??'',0,80,'…')) ?></div></td>
          <td><?= htmlspecialchars($w['school_name']) ?></td>
          <td><?= htmlspecialchars($w['taluka_name']) ?></td>
          <td><?= htmlspecialchars($w['deadline']) ?> <?= $chip ?></td>
          <td style="min-width:140px">
            <div class="progress"><div class="progress-bar" style="width:<?= (int)$w['progress'] ?>%"></div></div>
            <small><?= (int)$w['progress'] ?>%</small>
          </td>
          <td><span class="badge <?= $badge ?>"><?= $label ?></span></td>
          <td><a class="btn btn-sm btn-glow" href="work.php?id=<?= $w['id'] ?>"><?= t('view') ?></a></td>
        </tr>
      <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.4/chart.umd.min.js"></script>
<script>
const statusData = <?= json_encode($statusMap) ?>;
const statusLabels = Object.keys(statusData).map(k => k.replace('_',' '));
const statusValues = Object.values(statusData);
const talukaChart = <?= json_encode($talukaChart) ?>;

// Professional warm-orange palette (order: pending, in_progress, completed, overdue)
const palette = {
  pending:'#D8C4A6',
  'in progress':'#F5A623',
  completed:'#2FAE66',
  overdue:'#E8503A'
};
const barColors = statusLabels.map(l => palette[l] || '#FF7A1A');

Chart.defaults.font.family = "'Inter', sans-serif";

new Chart(document.getElementById('statusBarChart'), {
  type: 'bar',
  data: {
    labels: statusLabels,
    datasets: [{
      label: 'Works',
      data: statusValues,
      backgroundColor: barColors,
      borderRadius: 8,
      maxBarThickness: 46
    }]
  },
  options: {
    plugins: { legend: { display: false }, tooltip: { backgroundColor:'#3A2C1B', padding:10, cornerRadius:8 } },
    scales: {
      x: { ticks: { color: '#8A7660', font:{weight:600} }, grid: { display:false } },
      y: { ticks: { color: '#8A7660', precision:0 }, grid: { color: 'rgba(232,93,0,0.06)' }, beginAtZero:true }
    }
  }
});

new Chart(document.getElementById('statusDoughnut'), {
  type: 'doughnut',
  data: {
    labels: statusLabels,
    datasets: [{ data: statusValues, backgroundColor: barColors, borderColor:'#fff', borderWidth:3 }]
  },
  options: {
    cutout: '72%',
    plugins: { legend: { position: 'bottom', labels: { color: '#3A2C1B', boxWidth:10, padding:12, font:{weight:600} } } }
  }
});

<?php if (!empty($talukaChart['labels'])): ?>
new Chart(document.getElementById('talukaChart'), {
  type: 'bar',
  data: {
    labels: talukaChart.labels,
    datasets: [
      { label: 'Completed', data: talukaChart.completed, backgroundColor: '#2FAE66', borderRadius:6 },
      { label: 'Overdue', data: talukaChart.overdue, backgroundColor: '#E8503A', borderRadius:6 },
      { label: 'Total', data: talukaChart.total, backgroundColor: '#FFA55C', borderRadius:6 }
    ]
  },
  options: {
    indexAxis: 'y',
    plugins: { legend: { position: 'bottom', labels:{ color:'#3A2C1B', boxWidth:10, font:{weight:600} } } },
    scales: {
      x: { ticks: { color: '#8A7660', precision:0 }, grid: { color:'rgba(232,93,0,0.06)' }, beginAtZero:true },
      y: { ticks: { color: '#3A2C1B', font:{weight:600} }, grid: { display:false } }
    }
  }
});
<?php else: ?>
new Chart(document.getElementById('statusPieChart'), {
  type: 'pie',
  data: {
    labels: statusLabels,
    datasets: [{ data: statusValues, backgroundColor: barColors, borderColor:'#fff', borderWidth:3 }]
  },
  options: {
    plugins: { legend: { position: 'bottom', labels: { color: '#3A2C1B', boxWidth:10, font:{weight:600} } } }
  }
});
<?php endif; ?>
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>