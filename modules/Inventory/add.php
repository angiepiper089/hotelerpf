<?php
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['Admin','Manager','Finance']);

$pageTitle = 'Add Inventory Item';
$suppliers = $pdo->query('SELECT SupplierID, SupplierName FROM Suppliers ORDER BY SupplierName')->fetchAll();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['item_name']);
    $category = trim($_POST['category']) ?: 'General';
    $uom = trim($_POST['uom']) ?: 'unit';
    $qty = max(0, (int) $_POST['quantity']);
    $reorder = max(0, (int) $_POST['reorder_level']);
    $cost = max(0, (float) $_POST['unit_cost']);
    $supplierId = $_POST['supplier_id'] !== '' ? (int) $_POST['supplier_id'] : null;

    if ($name === '') $errors[] = 'Item name is required.';

    if (!$errors) {
        $stmt = $pdo->prepare('INSERT INTO InventoryItems (ItemName, Category, UnitOfMeasure, QuantityOnHand, ReorderLevel, UnitCost, SupplierID) VALUES (?,?,?,?,?,?,?)');
        $stmt->execute([$name, $category, $uom, $qty, $reorder, $cost, $supplierId]);
        logAudit($pdo, 'CREATE', 'InventoryItems', (int) $pdo->lastInsertId(), "Item '$name' added");
        flash('success', "Item '$name' added.");
        redirectTo('list.php');
    }
}

require __DIR__ . '/../../includes/header.php';
?>
<div class="section-card" style="max-width:640px;">
  <h2><i class="bi bi-box-seam"></i> Add Inventory Item</h2>
  <?php foreach ($errors as $err): ?><div class="alert alert-danger py-2"><?= e($err) ?></div><?php endforeach; ?>
  <form method="post">
    <div class="row g-3">
      <div class="col-md-8"><label class="form-label">Item Name</label><input type="text" name="item_name" class="form-control" required></div>
      <div class="col-md-4"><label class="form-label">Category</label><input type="text" name="category" class="form-control" value="General"></div>
      <div class="col-md-4"><label class="form-label">Unit of Measure</label><input type="text" name="uom" class="form-control" value="unit"></div>
      <div class="col-md-4"><label class="form-label">Quantity On Hand</label><input type="number" name="quantity" class="form-control" value="0" min="0"></div>
      <div class="col-md-4"><label class="form-label">Reorder Level</label><input type="number" name="reorder_level" class="form-control" value="10" min="0"></div>
      <div class="col-md-6"><label class="form-label">Unit Cost</label><input type="number" step="0.01" name="unit_cost" class="form-control" value="0" min="0"></div>
      <div class="col-md-6">
        <label class="form-label">Supplier</label>
        <select name="supplier_id" class="form-select">
          <option value="">-- None --</option>
          <?php foreach ($suppliers as $s): ?><option value="<?= (int) $s['SupplierID'] ?>"><?= e($s['SupplierName']) ?></option><?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="mt-4 d-flex gap-2">
      <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Save Item</button>
      <a href="list.php" class="btn btn-outline-secondary">Cancel</a>
    </div>
  </form>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
