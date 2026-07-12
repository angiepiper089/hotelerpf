<?php
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['Admin']);

$pageTitle = 'Audit Log';

// SQL Server uses TOP, MySQL uses LIMIT — pick the right syntax for the active driver.
if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlsrv') {
    $logs = $pdo->query(
        "SELECT TOP 200 al.*, u.Username FROM AuditLog al LEFT JOIN Users u ON u.UserID = al.UserID ORDER BY al.LogTime DESC"
    );
} else {
    $logs = $pdo->query(
        "SELECT al.*, u.Username FROM AuditLog al LEFT JOIN Users u ON u.UserID = al.UserID ORDER BY al.LogTime DESC LIMIT 200"
    );
}
$logs = $logs->fetchAll();

require __DIR__ . '/../../includes/header.php';
?>

<div class="section-card">
  <h2><i class="bi bi-shield-check"></i> Audit Log</h2>
  <p class="text-muted small">Every create/update/check-in/check-out/payment/purchase action across every module is recorded here with the user, timestamp and affected record — supporting the accountability and change-management practices covered in the ERP security chapter.</p>
  <div class="table-responsive">
    <table class="table erp-table align-middle">
      <thead><tr><th>Time</th><th>User</th><th>Action</th><th>Table</th><th>Record</th><th>Details</th></tr></thead>
      <tbody>
        <?php foreach ($logs as $log): ?>
        <tr>
          <td class="small text-muted"><?= e($log['LogTime']) ?></td>
          <td><?= e($log['Username']) ?: 'System' ?></td>
          <td><span class="badge text-bg-light border"><?= e($log['Action']) ?></span></td>
          <td><?= e($log['TableName']) ?></td>
          <td><?= $log['RecordID'] ? '#' . (int) $log['RecordID'] : '-' ?></td>
          <td class="small"><?= e($log['Details']) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$logs): ?><tr><td colspan="6" class="text-center text-muted py-4">No activity recorded yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
