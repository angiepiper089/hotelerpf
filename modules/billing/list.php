<?php
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['Admin','Manager','FrontDesk','Finance']);

$pageTitle = 'Billing';
$statusFilter = $_GET['status'] ?? '';

$sql = "SELECT inv.*, g.FullName FROM Invoices inv JOIN Guests g ON g.GuestID = inv.GuestID";
$params = [];
if ($statusFilter !== '') {
    $sql .= ' WHERE inv.Status = ?';
    $params[] = $statusFilter;
}
$sql .= ' ORDER BY inv.IssueDate DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$invoices = $stmt->fetchAll();

require __DIR__ . '/../../includes/header.php';
?>

<div class="section-card">
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div class="btn-group">
      <a href="?status=" class="btn btn-sm btn-outline-secondary <?= $statusFilter===''?'active':'' ?>">All</a>
      <a href="?status=Unpaid" class="btn btn-sm btn-outline-secondary <?= $statusFilter==='Unpaid'?'active':'' ?>">Unpaid</a>
      <a href="?status=Partial" class="btn btn-sm btn-outline-secondary <?= $statusFilter==='Partial'?'active':'' ?>">Partial</a>
      <a href="?status=Paid" class="btn btn-sm btn-outline-secondary <?= $statusFilter==='Paid'?'active':'' ?>">Paid</a>
    </div>
    <span class="text-muted small">Invoices are generated automatically from the Reservations check-out workflow.</span>
  </div>

  <div class="table-responsive">
    <table class="table erp-table align-middle">
      <thead><tr><th>Invoice #</th><th>Guest</th><th>Date</th><th>Room</th><th>Service</th><th>Tax</th><th>Total</th><th>Status</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($invoices as $inv): ?>
        <tr>
          <td>#<?= (int) $inv['InvoiceID'] ?></td>
          <td><?= e($inv['FullName']) ?></td>
          <td><?= formatDate($inv['IssueDate']) ?></td>
          <td><?= formatMoney($inv['RoomCharges']) ?></td>
          <td><?= formatMoney($inv['ServiceCharges']) ?></td>
          <td><?= formatMoney($inv['TaxAmount']) ?></td>
          <td><strong><?= formatMoney($inv['TotalAmount']) ?></strong></td>
          <td><span class="status-pill status-<?= e($inv['Status']) ?>"><?= e($inv['Status']) ?></span></td>
          <td><a href="invoice.php?id=<?= (int) $inv['InvoiceID'] ?>" class="btn btn-sm btn-outline-secondary">View</a></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$invoices): ?><tr><td colspan="9" class="text-center text-muted py-4">No invoices yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
