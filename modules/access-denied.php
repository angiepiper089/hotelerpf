<?php
$pageTitle = 'Access Denied';
require __DIR__ . '/../includes/header.php';
?>
<div class="section-card text-center">
  <i class="bi bi-shield-lock" style="font-size:2.5rem;color:#c0392b;"></i>
  <h2 class="mt-3">Access Denied</h2>
  <p class="text-muted">Your role (<strong><?= e($_SESSION['role_name'] ?? '') ?></strong>) does not have permission to view this module.</p>
  <a href="<?= basePath() ?>/dashboard.php" class="btn btn-primary">Back to Dashboard</a>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
