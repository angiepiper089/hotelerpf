<?php
/**
 * Shared page shell. Every protected page does:
 *   require_once __DIR__ . '/../../includes/auth.php';
 *   requireRole([...]);
 *   $pageTitle = 'Reservations';
 *   require __DIR__ . '/../../includes/header.php';
 */
$user = currentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle ?? 'Dashboard') ?> · Grand Horizon Hotel ERP</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="<?= asset('css/style.css') ?>" rel="stylesheet">
</head>
<body>
<div class="app-shell">
  <?php require __DIR__ . '/sidebar.php'; ?>
  <div class="app-main">
    <header class="app-topbar">
      <button class="btn btn-sm btn-outline-secondary d-lg-none" id="sidebarToggle" type="button">
        <i class="bi bi-list"></i>
      </button>
      <h1 class="app-topbar-title"><?= e($pageTitle ?? 'Dashboard') ?></h1>
      <div class="app-topbar-user">
        <span class="badge text-bg-secondary"><?= e($user['role']) ?></span>
        <span class="app-topbar-name"><?= e($user['name']) ?></span>
        <a href="<?= basePath() ?>/logout.php" class="btn btn-sm btn-outline-danger ms-2">
          <i class="bi bi-box-arrow-right"></i> Logout
        </a>
      </div>
    </header>
    <main class="app-content">
      <?php if ($msg = flash('success')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
          <?= e($msg) ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>
      <?php if ($msg = flash('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
          <?= e($msg) ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>
      