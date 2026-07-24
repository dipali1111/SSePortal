<?php
require_once __DIR__ . '/includes/auth.php';
require_role(['hm','admin','ceo']);
$user = $_SESSION['user'];
$namecol = $LANG==='mr'?'name_mr':'name_en';

if ($_SERVER['REQUEST_METHOD']==='POST') {
  $school_id = $user['role']==='hm' ? (int)$user['school_id'] : (int)$_POST['school_id'];
  $title = trim($_POST['title']);
  $desc = trim($_POST['description'] ?? '');
  $deadline = $_POST['deadline'];
  $stmt = $conn->prepare("INSERT INTO works (school_id,assigned_by,title,description,deadline) VALUES (?,?,?,?,?)");
  $stmt->bind_param('iisss',$school_id,$user['id'],$title,$desc,$deadline);
  $stmt->execute();
  header('Location: dashboard.php'); exit;
}
$schools = $conn->query("SELECT s.id, s.$namecol AS name, t.$namecol AS taluka FROM schools s JOIN talukas t ON t.id=s.taluka_id ORDER BY t.$namecol, s.$namecol");
include __DIR__ . '/includes/header.php';
?>
<div class="row justify-content-center">
  <div class="col-md-7">
    <div class="card-fx p-4">
      <h3><i class="bi bi-plus-circle"></i> <?= t('add_work') ?></h3>
      <form method="post" class="mt-3">
        <?php if ($user['role']!=='hm'): ?>
        <div class="mb-3">
          <label><?= t('select_school') ?></label>
          <select class="form-select" name="school_id" required>
            <?php while($s=$schools->fetch_assoc()): ?>
              <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['taluka'].' — '.$s['name']) ?></option>
            <?php endwhile; ?>
          </select>
        </div>
        <?php endif; ?>
        <div class="mb-3"><label><?= t('title') ?></label><input class="form-control" name="title" required></div>
        <div class="mb-3"><label><?= t('description') ?></label><textarea class="form-control" name="description" rows="3"></textarea></div>
        <div class="mb-3"><label><?= t('deadline') ?></label><input type="date" class="form-control" name="deadline" required></div>
        <button class="btn btn-glow"><?= t('submit') ?></button>
        <a class="btn btn-outline-light" href="dashboard.php">Cancel</a>
      </form>
    </div>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
