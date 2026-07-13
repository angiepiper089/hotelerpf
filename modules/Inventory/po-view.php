<?php
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['Admin','Manager','Finance']);

$pageTitle = 'Purchase Order';
$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT po.*, s.SupplierName FROM PurchaseOrders po JOIN Suppliers s ON s.SupplierID = po.SupplierID WHERE po.PurchaseOrderID = ?");
$stmt->execute([$id]);
$po = $stmt->fetch();
if (!$po) { flash('error', 'Purchase order not found.'); redirectTo('suppliers.php'); }

$lineItems = $pdo->prepare(
    "SELECT poi.*, i.ItemName, i.UnitOfMeasure FROM PurchaseOrderItems poi JOIN InventoryItems i ON i.ItemID = poi.ItemID WHERE poi.PurchaseOrderID = ?"
);
$lineItems->execute([$id]);
$lineItems = $lineItems->fetchAll();

require __DIR__ . '/../../includes/header.php';
?>
<div class="section-card" style="max-width:760px;">
  <div class="d-flex justify-content-between align-items-start">
    <div>
      <h2><i class="bi bi-file-earmark-text"></i> Purchase Order #<?= (int) $po['PurchaseOrderID'] ?></h2>
      <p class="text-muted mb-1">Supplier: <strong><?= e($po['SupplierName']) ?></strong></p>
      <p class="text-muted">Order Date: <?= formatDate($po['OrderDate']) ?></p>
    </div>
    <span class="status-pill status-<?= e($po['Status']) ?> fs-6"><?= e($po['Status']) ?></span>
  </div>

  <div class="table-responsive mb-3">
    <table class="table erp-table align-middle">
      <thead><tr><th>Item</th><th>Quantity</th><th>Unit Cost</th><th>Line Total</th></tr></thead>
      <tbody>
        <?php foreach ($lineItems as $li): ?>
        <tr>
          <td><?= e($li['ItemName']) ?> (<?= e($li['UnitOfMeasure']) ?>)</td>
          <td><?= (int) $li['Quantity'] ?></td>
          <td><?= formatMoney($li['UnitCost']) ?></td>
          <td><?= formatMoney($li['Quantity'] * $li['UnitCost']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot><tr><th colspan="3" class="text-end">Total</th><th><?= formatMoney($po['TotalAmount']) ?></th></tr></tfoot>
    </table>
  </div>

  <div class="d-flex gap-2">
    <?php if ($po['Status'] === 'Pending'): ?>
      <a href="po-action.php?id=<?= $id ?>&action=approve" class="btn btn-primary btn-sm" data-confirm="Approve this purchase order?"><i class="bi bi-check-circle"></i> Approve</a>
      <a href="po-action.php?id=<?= $id ?>&action=cancel" class="btn btn-outline-danger btn-sm" data-confirm="Cancel this purchase order?"><i class="bi bi-x-circle"></i> Cancel</a>
    <?php elseif ($po['Status'] === 'Approved'): ?>
      <a href="po-action.php?id=<?= $id ?>&action=receive" class="btn btn-success btn-sm" data-confirm="Mark as received? This will add the ordered quantities to Inventory stock."><i class="bi bi-box-arrow-in-down"></i> Mark as Received</a>
    <?php else: ?>
      <span class="text-muted small">No further actions available.</span>
    <?php endif; ?>
    <a href="suppliers.php" class="btn btn-outline-secondary btn-sm">Back</a>
  </div>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
