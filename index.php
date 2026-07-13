<?php
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    redirectTo(basePath() . '/dashboard.php');
}
$error = flash('error');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login · Grand Horizon Hotel ERP</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="<?= asset('css/style.css') ?>" rel="stylesheet">
</head>
<body>
<div class="login-page">
  <div class="login-card">
    <div class="brand">
      <i class="bi bi-building"></i>
      <h1>Grand Horizon Hotel ERP</h1>
      <small class="text-muted">Business Process &amp; ERP Systems &middot; Group Prototype</small>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-danger py-2"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" action="<?= basePath() ?>/login.php">
      <div class="mb-3">
        <label class="form-label">Username</label>
        <input type="text" name="username" class="form-control" required autofocus>
      </div>
      <div class="mb-3">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control" required>
      </div>
      <button type="submit" class="btn btn-primary w-100">
        <i class="bi bi-box-arrow-in-right"></i> Sign In
      </button>
    </form>

    <div class="demo-accounts">
      <strong>Demo accounts</strong> (password: <code>Password123</code>)<br>
      admin &middot; manager &middot; frontdesk1 &middot; housekeep1 &middot; finance1
    </div>
  </div>
</div>
</body>
</html>
