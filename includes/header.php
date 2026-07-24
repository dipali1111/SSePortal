<?php require_once __DIR__ . '/../config/db.php'; ?>
<!doctype html>
<html lang="<?= $LANG ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= t('app_name') ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="app-body">
<div class="school-bg"></div>
<nav class="navbar navbar-expand-lg custom-nav px-4">
  <a class="navbar-brand fw-bold text-white" href="index.php">
    <i class="bi bi-mortarboard-fill me-2"></i><?= t('app_name') ?>
  </a>
  <div class="ms-auto d-flex align-items-center gap-2">
    <?php if (!empty($_SESSION['user'])): ?>
      <span class="text-white small me-2"><i class="bi bi-person-circle"></i> <?= htmlspecialchars($_SESSION['user']['full_name']) ?> (<?= t($_SESSION['user']['role']) ?>)</span>
      <a class="btn btn-sm btn-glow" href="dashboard.php"><?= t('dashboard') ?></a>
      <?php if ($_SESSION['user']['role'] === 'ceo' || $_SESSION['user']['role'] === 'admin'): ?>
        <a class="btn btn-sm btn-glow" href="ceo.php"><?= t('ceo_dashboard') ?></a>
      <?php endif; ?>
      <a class="btn btn-sm btn-outline-light" href="logout.php"><?= t('logout') ?></a>
    <?php endif; ?>
    <div class="btn-group btn-group-sm ms-2">
      <a class="btn btn-outline-light <?= $LANG==='en'?'active':'' ?>" href="?lang=en">EN</a>
      <a class="btn btn-outline-light <?= $LANG==='mr'?'active':'' ?>" href="?lang=mr">मराठी</a>
    </div>
  </div>
</nav>
<main class="container-fluid py-4 position-relative">
