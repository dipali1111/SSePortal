<?php
require_once __DIR__ . '/includes/auth.php';
require_role(['ceo','admin']);
$namecol = $LANG==='mr'?'name_mr':'name_en';

$taluka_id = isset($_GET['taluka']) && $_GET['taluka']!==''? (int)$_GET['taluka'] : null;

// Mark a blocker resolved (CEO/admin action after confirming the suggested_solution worked)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resolve_blocker_id'])) {
  $bid = (int)$_POST['resolve_blocker_id'];
  $stmt = $conn->prepare("UPDATE blockers SET resolved=1 WHERE id=?");
  $stmt->bind_param('i', $bid);
  $stmt->execute();
  header("Location: ceo.php" . ($taluka_id ? "?taluka=$taluka_id" : ""));
  exit;
}

generate_alerts($conn);
$twhere = $taluka_id ? "AND s.taluka_id = $taluka_id" : '';

// District-wide stats
$total_schools = $conn->query("SELECT COUNT(*) c FROM schools s WHERE 1=1 $twhere")->fetch_assoc()['c'];
$total_works = $conn->query("SELECT COUNT(*) c FROM works w JOIN schools s ON s.id=w.school_id WHERE 1=1 $twhere")->fetch_assoc()['c'];
$overdue = $conn->query("SELECT COUNT(*) c FROM works w JOIN schools s ON s.id=w.school_id WHERE w.deadline<CURDATE() AND w.status<>'completed' $twhere")->fetch_assoc()['c'];
$active_blockers = $conn->query("SELECT COUNT(*) c FROM blockers b JOIN works w ON w.id=b.work_id JOIN schools s ON s.id=w.school_id WHERE b.resolved=0 $twhere")->fetch_assoc()['c'];

// Overall status breakdown (district or filtered taluka) — for the pie chart
$statusRes = $conn->query("
  SELECT CASE WHEN w.deadline<CURDATE() AND w.status<>'completed' THEN 'overdue' ELSE w.status END st, COUNT(*) c
  FROM works w JOIN schools s ON s.id=w.school_id
  WHERE 1=1 $twhere
  GROUP BY st");
$statusMap = ['pending'=>0,'in_progress'=>0,'completed'=>0,'overdue'=>0];
while ($r = $statusRes->fetch_assoc()) { $statusMap[$r['st']] = (int)$r['c']; }

// Taluka summary — also doubles as the bar chart source
$talukaStats = $conn->query("
  SELECT t.id, t.$namecol AS name,
    COUNT(DISTINCT s.id) AS schools,
    COUNT(w.id) AS works,
    SUM(CASE WHEN w.deadline<CURDATE() AND w.status<>'completed' THEN 1 ELSE 0 END) AS overdue,
    (SELECT COUNT(*) FROM blockers b JOIN works w2 ON w2.id=b.work_id JOIN schools s2 ON s2.id=w2.school_id WHERE b.resolved=0 AND s2.taluka_id=t.id) AS blockers
  FROM talukas t
  LEFT JOIN schools s ON s.taluka_id=t.id
  LEFT JOIN works w ON w.school_id=s.id
  GROUP BY t.id ORDER BY t.$namecol");
$talukaRows = [];
while ($r = $talukaStats->fetch_assoc()) { $talukaRows[] = $r; }

$blockers = $conn->query("SELECT b.*, w.title, s.$namecol AS school_name, t.$namecol AS taluka_name, u.full_name
  FROM blockers b
  JOIN works w ON w.id=b.work_id
  JOIN schools s ON s.id=w.school_id
  JOIN talukas t ON t.id=s.taluka_id
  JOIN users u ON u.id=b.reported_by
  WHERE b.resolved=0 $twhere
  ORDER BY b.created_at DESC LIMIT 50");

$alerts = $conn->query("SELECT a.*, s.$namecol AS school_name, t.$namecol AS taluka_name
  FROM alerts a
  JOIN schools s ON s.id=a.school_id
  JOIN talukas t ON t.id=s.taluka_id
  WHERE 1=1 $twhere
  ORDER BY a.created_at DESC LIMIT 30");

$talukas = $conn->query("SELECT id, $namecol AS name FROM talukas ORDER BY $namecol");
include __DIR__ . '/includes/header.php';
?>
<style>
/* ===== Futuristic orange theme overrides — same treatment as dashboard.php ===== */
:root{
  --fx-bg:#0a0d14;
  --fx-panel:rgba(20,16,12,0.55);
  --fx-border:rgba(255,138,61,0.25);
  --fx-orange:#ff8a3d;
  --fx-orange2:#ff5e00;
  --fx-amber:#f59e0b;
  --fx-red:#ef4444;
  --fx-glow:0 0 24px rgba(255,94,0,0.35);
}
body{ background:
  radial-gradient(circle at 15% 10%, rgba(255,94,0,0.10), transparent 40%),
  radial-gradient(circle at 90% 0%, rgba(245,158,11,0.08), transparent 45%),
  var(--fx-bg) !important;
}
.hero{
  background: linear-gradient(135deg, rgba(255,94,0,0.18), rgba(255,138,61,0.05));
  border:1px solid var(--fx-border);
  border-radius:16px; padding:20px 24px;
  box-shadow: var(--fx-glow);
  position:relative; overflow:hidden;
}
.hero::before{
  content:""; position:absolute; inset:0;
  background:linear-gradient(90deg, transparent, rgba(255,138,61,0.5), transparent);
  height:2px; top:0; animation: fx-scan 3.5s linear infinite;
}
@keyframes fx-scan{ 0%{transform:translateX(-100%);} 100%{transform:translateX(100%);} }
.hero h2{
  background:linear-gradient(90deg,#fff,var(--fx-orange));
  -webkit-background-clip:text; background-clip:text; color:transparent;
  letter-spacing:.5px;
}
.stat-card{
  background: linear-gradient(135deg, rgba(255,94,0,0.9), rgba(255,138,61,0.7));
  border-radius:14px; padding:18px 20px; color:#fff;
  border:1px solid var(--fx-border);
  box-shadow: 0 4px 18px rgba(255,94,0,0.25);
  transition: transform .2s ease, box-shadow .2s ease;
}
.stat-card:hover{ transform: translateY(-3px); box-shadow: var(--fx-glow); }
.stat-card .stat-value{ font-size:2.1rem; font-weight:700; text-shadow:0 0 12px rgba(255,255,255,0.35); }
.stat-card.pulse{ animation: fx-pulse 1.8s ease-in-out infinite; }
@keyframes fx-pulse{
  0%,100%{ box-shadow:0 0 0 rgba(239,68,68,0.0); }
  50%{ box-shadow:0 0 26px rgba(239,68,68,0.55); }
}
.card-fx{
  background: var(--fx-panel);
  backdrop-filter: blur(10px);
  border:1px solid var(--fx-border);
  border-radius:14px; overflow:hidden;
  box-shadow: 0 4px 20px rgba(0,0,0,0.35);
}
.card-fx .card-header{
  background: linear-gradient(90deg, rgba(255,94,0,0.18), transparent);
  border-bottom:1px solid var(--fx-border);
  color:#ffd8b8; font-weight:600; letter-spacing:.3px;
}
.table-fx{ color:#f2e9e4; }
.table-fx thead th{ color:var(--fx-orange); border-bottom:1px solid var(--fx-border); font-weight:600; }
.table-fx td, .table-fx th{ border-color:rgba(255,138,61,0.12); }
.table-fx tbody tr:hover{ background:rgba(255,138,61,0.06); }
.badge-overdue{ background:linear-gradient(135deg,#ef4444,#ff5e00); color:#fff; animation: fx-pulse 1.8s ease-in-out infinite; }
.badge-pending{ background:rgba(255,255,255,0.12); color:#f2e9e4; border:1px solid rgba(255,255,255,0.2); }
.btn-glow{
  background:linear-gradient(135deg,var(--fx-orange2),var(--fx-orange));
  border:none; color:#fff; box-shadow:0 0 10px rgba(255,94,0,0.4);
  transition: box-shadow .2s ease, transform .2s ease;
}
.btn-glow:hover{ box-shadow:0 0 18px rgba(255,94,0,0.75); transform:translateY(-1px); color:#fff; }
.btn-resolve{
  background:transparent; border:1px solid rgba(74,222,128,0.5); color:#8ef2ae;
  transition: background .2s ease;
}
.btn-resolve:hover{ background:rgba(74,222,128,0.15); color:#8ef2ae; }
.alert-fx{ background:rgba(255,94,0,0.08); border:1px solid var(--fx-border); border-radius:10px; color:#f2e9e4; }
.blocker-card{ background:rgba(255,94,0,0.06); border:1px solid var(--fx-border); border-radius:10px; padding:12px; margin-bottom:10px; color:#f2e9e4; }
.blocker-card .solution{ color:#ffd8b8; }
.chart-box{ background:rgba(0,0,0,0.15); border-radius:10px; padding:10px; }
</style>

<div class="hero d-flex justify-content-between align-items-center flex-wrap gap-3">
  <div>
    <h2 class="mb-1"><i class="bi bi-buildings"></i> <?= t('ceo_dashboard') ?></h2>
    <div class="text-white-50"><?= t('kolhapur_schools') ?></div>
  </div>
  <form method="get">
    <select name="taluka" class="form-select" onchange="this.form.submit()">
      <option value=""><?= t('all_talukas') ?></option>
      <?php while($t=$talukas->fetch_assoc()): ?>
        <option value="<?= $t['id'] ?>" <?= $taluka_id==$t['id']?'selected':'' ?>><?= htmlspecialchars($t['name']) ?></option>
      <?php endwhile; ?>
    </select>
  </form>
</div>

<div class="row g-3 mb-4 mt-1">
  <div class="col-md-3"><div class="stat-card"><div class="small"><?= t('school') ?>s</div><div class="stat-value"><?= $total_schools ?></div></div></div>
  <div class="col-md-3"><div class="stat-card" style="background:linear-gradient(135deg,#f59e0b,#ff5e00)"><div class="small"><?= t('total_works') ?></div><div class="stat-value"><?= $total_works ?></div></div></div>
  <div class="col-md-3"><div class="stat-card <?= $overdue>0?'pulse':'' ?>" style="background:linear-gradient(135deg,#ef4444,#ff8a3d)"><div class="small"><?= t('overdue_works') ?></div><div class="stat-value"><?= $overdue ?></div></div></div>
  <div class="col-md-3"><div class="stat-card <?= $active_blockers>0?'pulse':'' ?>" style="background:linear-gradient(135deg,#b91c1c,#ff5e00)"><div class="small"><?= t('active_blockers') ?></div><div class="stat-value"><?= $active_blockers ?></div></div></div>
</div>

<div class="row g-3 mb-4">
  <div class="col-md-7">
    <div class="card-fx h-100">
      <div class="card-header"><i class="bi bi-bar-chart-line"></i> <?= t('taluka') ?> <?= t('works') ?> / <?= t('overdue_works') ?> / <?= t('blockers') ?></div>
      <div class="p-3 chart-box"><canvas id="talukaBarChart" height="160"></canvas></div>
    </div>
  </div>
  <div class="col-md-5">
    <div class="card-fx h-100">
      <div class="card-header"><i class="bi bi-pie-chart"></i> <?= t('status') ?> <?= t('progress') ?></div>
      <div class="p-3 chart-box"><canvas id="statusPieChart" height="180"></canvas></div>
    </div>
  </div>
</div>

<div class="row g-3">
  <div class="col-md-6">
    <div class="card-fx mb-3">
      <div class="card-header"><i class="bi bi-geo-alt"></i> <?= t('taluka') ?> summary</div>
      <div class="table-responsive">
        <table class="table table-fx mb-0">
          <thead><tr><th><?= t('taluka') ?></th><th><?= t('school') ?>s</th><th><?= t('works') ?></th><th><?= t('overdue_works') ?></th><th><?= t('blockers') ?></th></tr></thead>
          <tbody>
          <?php foreach($talukaRows as $r): ?>
            <tr>
              <td><a href="?taluka=<?= $r['id'] ?>" class="text-warning"><?= htmlspecialchars($r['name']) ?></a></td>
              <td><?= (int)$r['schools'] ?></td>
              <td><?= (int)$r['works'] ?></td>
              <td><?= (int)$r['overdue']>0?'<span class="badge badge-overdue">'.$r['overdue'].'</span>':'0' ?></td>
              <td><?= (int)$r['blockers']>0?'<span class="badge badge-pending">'.$r['blockers'].'</span>':'0' ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="col-md-6">
    <div class="card-fx mb-3">
      <div class="card-header"><i class="bi bi-bell"></i> <?= t('alerts') ?></div>
      <div class="p-3" style="max-height:360px;overflow:auto">
        <?php if (!$alerts->num_rows): ?><div class="text-white-50"><?= t('no_alerts') ?></div><?php endif; ?>
        <?php while($a=$alerts->fetch_assoc()): ?>
          <div class="alert-fx p-2 mb-2">
            <div class="small text-white-50"><?= $a['created_at'] ?> · <?= htmlspecialchars($a['taluka_name']) ?> · <?= htmlspecialchars($a['school_name']) ?></div>
            <div><span class="badge badge-overdue me-1"><?= strtoupper($a['type']) ?></span><?= htmlspecialchars($a['message']) ?>
              <a class="ms-2" href="work.php?id=<?= $a['work_id'] ?>"><?= t('view') ?></a>
            </div>
          </div>
        <?php endwhile; ?>
      </div>
    </div>
  </div>
</div>

<div class="card-fx">
  <div class="card-header"><i class="bi bi-shield-exclamation"></i> <?= t('blockers') ?> — <?= t('suggested_solution') ?></div>
  <div class="p-3">
    <?php if(!$blockers->num_rows): ?><div class="text-white-50">—</div><?php endif; ?>
    <?php while($b=$blockers->fetch_assoc()): ?>
      <div class="blocker-card">
        <div class="d-flex justify-content-between flex-wrap">
          <div><b><?= htmlspecialchars($b['title']) ?></b> · <?= htmlspecialchars($b['school_name']) ?> <span class="text-white-50">(<?= htmlspecialchars($b['taluka_name']) ?>)</span></div>
          <span class="small text-white-50"><?= $b['created_at'] ?> · <?= htmlspecialchars($b['full_name']) ?></span>
        </div>
        <div><?= htmlspecialchars($b['issue']) ?></div>
        <?php if (!empty($b['suggested_solution'])): ?>
          <div class="solution mt-1"><i class="bi bi-lightbulb"></i> <b><?= t('suggested_solution') ?>:</b> <?= htmlspecialchars($b['suggested_solution']) ?></div>
        <?php else: ?>
          <div class="small text-white-50 mt-1"><i class="bi bi-hourglass-split"></i> no solution submitted yet</div>
        <?php endif; ?>
        <div class="d-flex gap-2 mt-2">
          <a class="btn btn-sm btn-glow" href="work.php?id=<?= $b['work_id'] ?>"><?= t('view') ?></a>
          <?php if (!empty($b['suggested_solution'])): ?>
          <form method="post" onsubmit="return confirm('Mark this blocker resolved?');">
            <input type="hidden" name="resolve_blocker_id" value="<?= $b['id'] ?>">
            <button type="submit" class="btn btn-sm btn-resolve"><i class="bi bi-check2-circle"></i> Mark resolved</button>
          </form>
          <?php endif; ?>
        </div>
      </div>
    <?php endwhile; ?>
  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.4/chart.umd.min.js"></script>
<script>
const talukaLabels = <?= json_encode(array_column($talukaRows,'name')) ?>;
const talukaWorks = <?= json_encode(array_map('intval', array_column($talukaRows,'works'))) ?>;
const talukaOverdue = <?= json_encode(array_map('intval', array_column($talukaRows,'overdue'))) ?>;
const talukaBlockers = <?= json_encode(array_map('intval', array_column($talukaRows,'blockers'))) ?>;

new Chart(document.getElementById('talukaBarChart'), {
  type: 'bar',
  data: {
    labels: talukaLabels,
    datasets: [
      { label: 'Works', data: talukaWorks, backgroundColor: '#ffb84d', borderRadius: 5 },
      { label: 'Overdue', data: talukaOverdue, backgroundColor: '#ef4444', borderRadius: 5 },
      { label: 'Blockers', data: talukaBlockers, backgroundColor: '#f59e0b', borderRadius: 5 }
    ]
  },
  options: {
    plugins: { legend: { labels: { color: '#f2e9e4' } } },
    scales: {
      x: { ticks: { color: '#f2e9e4' }, grid: { color: 'rgba(255,255,255,0.05)' } },
      y: { ticks: { color: '#f2e9e4', precision:0 }, grid: { color: 'rgba(255,255,255,0.05)' }, beginAtZero:true }
    }
  }
});

const statusData = <?= json_encode($statusMap) ?>;
new Chart(document.getElementById('statusPieChart'), {
  type: 'pie',
  data: {
    labels: Object.keys(statusData).map(k => k.replace('_',' ')),
    datasets: [{ data: Object.values(statusData), backgroundColor: ['#ffb84d','#f59e0b','#4ade80','#ef4444'], borderColor:'#0a0d14', borderWidth:2 }]
  },
  options: { plugins: { legend: { position: 'bottom', labels: { color: '#f2e9e4' } } } }
});
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>