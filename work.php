<?php
require_once __DIR__ . '/includes/auth.php';
require_login();
$user = $_SESSION['user'];
$namecol = $LANG==='mr'?'name_mr':'name_en';
$id = (int)($_GET['id'] ?? 0);

// Handle progress update
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action'])) {
  if ($_POST['action']==='progress') {
    $progress = max(0,min(100,(int)$_POST['progress']));
    $notes = trim($_POST['notes'] ?? '');
    $photoPath = null;
    if (!empty($_FILES['photo']['name'])) {
      $ext = pathinfo($_FILES['photo']['name'],PATHINFO_EXTENSION);
      $fn = 'uploads/work_'.$id.'_'.time().'.'.preg_replace('/[^a-z0-9]/i','',$ext);
      if (move_uploaded_file($_FILES['photo']['tmp_name'], __DIR__.'/'.$fn)) $photoPath = $fn;
    }
    $stmt=$conn->prepare("INSERT INTO progress_updates (work_id,user_id,progress,notes,photo_path) VALUES (?,?,?,?,?)");
    $stmt->bind_param('iiiss',$id,$user['id'],$progress,$notes,$photoPath);
    $stmt->execute();
    $newStatus = $progress>=100 ? 'completed' : 'in_progress';
    $conn->query("UPDATE works SET progress=$progress, status='$newStatus' WHERE id=$id");
  } elseif ($_POST['action']==='blocker') {
    $issue = trim($_POST['issue']);
    // Auto suggested solution (simple keyword rules)
    $suggest = suggest_solution($issue);
    $stmt=$conn->prepare("INSERT INTO blockers (work_id,reported_by,issue,suggested_solution) VALUES (?,?,?,?)");
    $stmt->bind_param('iiss',$id,$user['id'],$issue,$suggest);
    $stmt->execute();
    $conn->query("INSERT INTO alerts (work_id,school_id,type,message) SELECT id, school_id, 'blocker', CONCAT('New blocker: ',".$conn->real_escape_string("'".$issue."'").") FROM works WHERE id=$id");
  } elseif ($_POST['action']==='resolve' && in_array($user['role'],['ceo','admin','hm'])) {
    $bid = (int)$_POST['blocker_id'];
    $conn->query("UPDATE blockers SET resolved=1 WHERE id=$bid");
  }
  header("Location: work.php?id=$id"); exit;
}

function suggest_solution($issue) {
  $issue_l = mb_strtolower($issue);
  $rules = [
    ['water','Coordinate with Gram Panchayat / Zilla Parishad water dept for a tanker; check school rainwater harvesting tank.'],
    ['vendor','Escalate to district procurement cell; use approved alternate vendor from the empanelled list.'],
    ['fund','Raise indent to BEO; use school SMC contingency fund; apply for RMSA/SSA grant.'],
    ['staff','Request substitute teacher via BEO office; reassign duties temporarily among existing staff.'],
    ['electric','Contact MSEDCL section office; use school inverter/solar backup for critical rooms.'],
    ['internet','Use mobile hotspot temporarily; contact BSNL/JIO school connectivity nodal officer.'],
    ['book','Request supplementary set from district DIET; share with nearby school through cluster head.'],
    ['toilet','Coordinate with Swachh Vidyalaya cell; use MGNREGA/Panchayat funds for urgent repairs.'],
    ['roof','Get quick tarpaulin cover from Panchayat store; raise urgent repair estimate to BEO.'],
    ['transport','Coordinate with parents/SMC for temporary carpool; contact state transport for van route.'],
  ];
  foreach($rules as $r) if (strpos($issue_l,$r[0])!==false) return $r[1];
  return 'Raise the issue with the Block Education Officer, document photo evidence, and set a 48-hour follow-up review.';
}

$w = $conn->query("SELECT w.*, s.$namecol AS school_name, t.$namecol AS taluka_name FROM works w JOIN schools s ON s.id=w.school_id JOIN talukas t ON t.id=s.taluka_id WHERE w.id=$id")->fetch_assoc();
if (!$w) { die('Work not found'); }
$updates = $conn->query("SELECT p.*, u.full_name FROM progress_updates p JOIN users u ON u.id=p.user_id WHERE work_id=$id ORDER BY created_at DESC");
$blockers = $conn->query("SELECT b.*, u.full_name FROM blockers b JOIN users u ON u.id=b.reported_by WHERE work_id=$id ORDER BY resolved ASC, created_at DESC");

include __DIR__ . '/includes/header.php';
?>
<a href="dashboard.php" class="text-white-50"><i class="bi bi-arrow-left"></i> <?= t('dashboard') ?></a>
<div class="hero mt-2">
  <div class="d-flex justify-content-between flex-wrap gap-2">
    <div>
      <h3 class="mb-1"><?= htmlspecialchars($w['title']) ?></h3>
      <div class="text-white-50"><?= htmlspecialchars($w['school_name']) ?> · <?= htmlspecialchars($w['taluka_name']) ?></div>
      <p class="mt-2"><?= nl2br(htmlspecialchars($w['description'])) ?></p>
    </div>
    <div class="text-end">
      <div><?= t('deadline') ?>: <b><?= htmlspecialchars($w['deadline']) ?></b></div>
      <div class="mt-2" style="min-width:200px">
        <div class="progress"><div class="progress-bar" style="width:<?= (int)$w['progress'] ?>%"></div></div>
        <small><?= (int)$w['progress'] ?>% · <?= t($w['status']) ?></small>
      </div>
    </div>
  </div>
</div>

<div class="row g-3">
  <div class="col-md-6">
    <div class="card-fx mb-3">
      <div class="card-header"><i class="bi bi-camera"></i> <?= t('update_progress') ?></div>
      <div class="p-3">
        <form method="post" enctype="multipart/form-data">
          <input type="hidden" name="action" value="progress">
          <div class="mb-2"><label><?= t('progress') ?> (%)</label><input type="number" name="progress" min="0" max="100" class="form-control" value="<?= (int)$w['progress'] ?>" required></div>
          <div class="mb-2"><label><?= t('notes') ?></label><textarea name="notes" rows="2" class="form-control"></textarea></div>
          <div class="mb-2"><label><?= t('upload_photo') ?></label><input type="file" name="photo" accept="image/*" class="form-control"></div>
          <button class="btn btn-glow"><?= t('submit') ?></button>
        </form>
      </div>
    </div>
    <div class="card-fx">
      <div class="card-header"><i class="bi bi-images"></i> <?= t('progress') ?> log</div>
      <div class="p-3">
        <?php if (!$updates->num_rows): ?><div class="text-white-50">—</div><?php endif; ?>
        <?php while($p=$updates->fetch_assoc()): ?>
          <div class="d-flex gap-3 mb-3 pb-3 border-bottom border-warning border-opacity-25">
            <?php if($p['photo_path']): ?><a href="<?= $p['photo_path'] ?>" target="_blank"><img class="thumb" src="<?= $p['photo_path'] ?>"></a><?php endif; ?>
            <div>
              <div><b><?= (int)$p['progress'] ?>%</b> · <?= htmlspecialchars($p['full_name']) ?> · <span class="text-white-50 small"><?= $p['created_at'] ?></span></div>
              <div><?= nl2br(htmlspecialchars($p['notes'])) ?></div>
              <?php if($p['photo_path']): ?><div class="small solution"><i class="bi bi-shield-check"></i> <?= t('progress_verified') ?></div><?php endif; ?>
            </div>
          </div>
        <?php endwhile; ?>
      </div>
    </div>
  </div>

  <div class="col-md-6">
    <div class="card-fx mb-3">
      <div class="card-header"><i class="bi bi-exclamation-triangle"></i> <?= t('report_blocker') ?></div>
      <div class="p-3">
        <form method="post">
          <input type="hidden" name="action" value="blocker">
          <textarea class="form-control mb-2" name="issue" rows="3" placeholder="Describe the blocker..." required></textarea>
          <button class="btn btn-glow"><?= t('submit') ?></button>
        </form>
      </div>
    </div>
    <div class="card-fx">
      <div class="card-header"><i class="bi bi-list-task"></i> <?= t('blockers') ?></div>
      <div class="p-3">
        <?php if(!$blockers->num_rows): ?><div class="text-white-50">—</div><?php endif; ?>
        <?php while($b=$blockers->fetch_assoc()): ?>
          <div class="blocker-card">
            <div class="d-flex justify-content-between">
              <b><?= htmlspecialchars($b['full_name']) ?></b>
              <span class="small text-white-50"><?= $b['created_at'] ?></span>
            </div>
            <div><?= nl2br(htmlspecialchars($b['issue'])) ?></div>
            <div class="solution mt-2"><i class="bi bi-lightbulb"></i> <b><?= t('suggested_solution') ?>:</b> <?= htmlspecialchars($b['suggested_solution']) ?></div>
            <?php if(!$b['resolved']): ?>
              <form method="post" class="mt-2">
                <input type="hidden" name="action" value="resolve">
                <input type="hidden" name="blocker_id" value="<?= $b['id'] ?>">
                <button class="btn btn-sm btn-glow"><i class="bi bi-check2-circle"></i> <?= t('resolve') ?></button>
              </form>
            <?php else: ?>
              <span class="badge badge-completed mt-2"><?= t('resolved') ?></span>
            <?php endif; ?>
          </div>
        <?php endwhile; ?>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
