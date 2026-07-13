<?php
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['Admin','Manager','Finance']);

$pageTitle = 'Inventory';
$items = $pdo->query(
    "SELECT i.*, s.SupplierName FROM InventoryItems i LEFT JOIN Suppliers s ON s.SupplierID = i.SupplierID ORDER BY i.ItemName"
)->fetchAll();

require __DIR__ . '/../../includes/header.php';
?>

<div class="section-card">
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <h2 class="mb-0"><i class="bi bi-box-seam"></i> Inventory Items</h2>
    <div class="d-flex gap-2">
      <a href="suppliers.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-truck"></i> Suppliers &amp; POs</a>
      <a href="add.php" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg"></i> Add Item</a>
    </div>
  </div>
  <p class="text-muted small">Models the Supply Chain Management concept from the textbook: stock consumed by Housekeeping/Minibar (via Billing at checkout) is replenished by raising Purchase Orders to Suppliers when it falls below the reorder level.</p>

  <div class="table-responsive">
    <table class="table erp-table align-middle">
      <thead><tr><th>Item</th><th>Category</th><th>UoM</th><th>Qty On Hand</th><th>Reorder Level</th><th>Unit Cost</th><th>Supplier</th><th class="text-end">Actions</th></tr></thead>
      <tbody>
        <?php foreach ($items as $it): $low = $it['QuantityOnHand'] < $it['ReorderLevel']; ?>
        <tr class="<?= $low ? 'table-danger' : '' ?>">
          <td><?= e($it['ItemName']) ?></td>
          <td><?= e($it['Category']) ?></td>
          <td><?= e($it['UnitOfMeasure']) ?></td>
          <td><?= (int) $it['QuantityOnHand'] ?> <?= $low ? '<i class="bi bi-exclamation-triangle-fill text-danger" title="Below reorder level"></i>' : '' ?></td>
          <td><?= (int) $it['ReorderLevel'] ?></td>
          <td><?= formatMoney($it['UnitCost']) ?></td>
          <td><?= e($it['SupplierName']) ?: '-' ?></td>
          <td class="text-end"><a href="edit.php?id=<?= (int) $it['ItemID'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
