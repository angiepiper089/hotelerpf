<?php
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['Admin','Manager','Finance']);

$pageTitle = 'New Purchase Order';
$suppliers = $pdo->query('SELECT SupplierID, SupplierName FROM Suppliers ORDER BY SupplierName')->fetchAll();
$items = $pdo->query('SELECT ItemID, ItemName, UnitCost, UnitOfMeasure FROM InventoryItems ORDER BY ItemName')->fetchAll();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $supplierId = (int) $_POST['supplier_id'];
    $qtyByItem = $_POST['qty'] ?? [];
    $lineItems = [];
    $total = 0.0;

    if (!$supplierId) $errors[] = 'Please select a supplier.';

    foreach ($qtyByItem as $itemId => $qty) {
        $qty = (int) $qty;
        if ($qty <= 0) continue;
        foreach ($items as $it) {
            if ((int) $it['ItemID'] === (int) $itemId) {
                $amount = $qty * (float) $it['UnitCost'];
                $total += $amount;
                $lineItems[] = ['ItemID' => $it['ItemID'], 'Quantity' => $qty, 'UnitCost' => $it['UnitCost']];
                break;
            }
        }
    }
    if (!$lineItems) $errors[] = 'Add a quantity for at least one item.';

    if (!$errors) {
        $pdo->beginTransaction();
        try {
            $pdo->prepare('INSERT INTO PurchaseOrders (SupplierID, Status, TotalAmount, CreatedBy) VALUES (?, ?, ?, ?)')
                ->execute([$supplierId, 'Pending', $total, $_SESSION['user_id']]);
            $poId = (int) $pdo->lastInsertId();
            $lineStmt = $pdo->prepare('INSERT INTO PurchaseOrderItems (PurchaseOrderID, ItemID, Quantity, UnitCost) VALUES (?,?,?,?)');
            foreach ($lineItems as $li) {
                $lineStmt->execute([$poId, $li['ItemID'], $li['Quantity'], $li['UnitCost']]);
            }
            logAudit($pdo, 'CREATE', 'PurchaseOrders', $poId, "PO #$poId created for supplier #$supplierId");
            $pdo->commit();
            flash('success', "Purchase Order #$poId created.");
            redirectTo('po-view.php?id=' . $poId);
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Failed to create purchase order: ' . $e->getMessage();
        }
    }
}

require __DIR__ . '/../../includes/header.php';
?>
<div class="section-card" style="max-width:760px;">
  <h2><i class="bi bi-file-earmark-plus"></i> New Purchase Order</h2>
  <p class="text-muted small">Supply Chain Management flow: raise a PO with a supplier for items running low, approve it, then mark it Received to restock Inventory.</p>
  <?php foreach ($errors as $err): ?><div class="alert alert-danger py-2"><?= e($err) ?></div><?php endforeach; ?>
  <form method="post">
    <div class="mb-3">
      <label class="form-label">Supplier</label>
      <select name="supplier_id" class="form-select" required>
        <option value="">-- Select supplier --</option>
        <?php foreach ($suppliers as $s): ?><option value="<?= (int) $s['SupplierID'] ?>"><?= e($s['SupplierName']) ?></option><?php endforeach; ?>
      </select>
    </div>
    <h6>Items to Order</h6>
    <div class="table-responsive">
      <table class="table table-sm align-middle">
        <thead><tr><th>Item</th><th>Unit Cost</th><th style="width:120px;">Quantity</th></tr></thead>
        <tbody>
          <?php foreach ($items as $it): ?>
          <tr>
            <td><?= e($it['ItemName']) ?> <span class="text-muted small">(<?= e($it['UnitOfMeasure']) ?>)</span></td>
            <td><?= formatMoney($it['UnitCost']) ?></td>
            <td><input type="number" min="0" name="qty[<?= (int) $it['ItemID'] ?>]" class="form-control form-control-sm" value="0"></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <div class="mt-3 d-flex gap-2">
      <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Create Purchase Order</button>
      <a href="suppliers.php" class="btn btn-outline-secondary">Cancel</a>
    </div>
  </form>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
