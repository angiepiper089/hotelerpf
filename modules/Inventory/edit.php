<?php
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['Admin','Manager','Finance']);

$pageTitle = 'Edit Inventory Item';
$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM InventoryItems WHERE ItemID = ?');
$stmt->execute([$id]);
$item = $stmt->fetch();
if (!$item) { flash('error', 'Item not found.'); redirectTo('list.php'); }

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
        $pdo->prepare('UPDATE InventoryItems SET ItemName=?, Category=?, UnitOfMeasure=?, QuantityOnHand=?, ReorderLevel=?, UnitCost=?, SupplierID=? WHERE ItemID=?')
            ->execute([$name, $category, $uom, $qty, $reorder, $cost, $supplierId, $id]);
        logAudit($pdo, 'UPDATE', 'InventoryItems', $id, "Item '$name' updated");
        flash('success', 'Item updated.');
        redirectTo('list.php');
    }
    $item = array_merge($item, ['ItemName'=>$name,'Category'=>$category,'UnitOfMeasure'=>$uom,'QuantityOnHand'=>$qty,'ReorderLevel'=>$reorder,'UnitCost'=>$cost,'SupplierID'=>$supplierId]);
}

require __DIR__ . '/../../includes/header.php';
?>
<div class="section-card" style="max-width:640px;">
  <h2><i class="bi bi-pencil-square"></i> Edit Inventory Item</h2>
  <?php foreach ($errors as $err): ?><div class="alert alert-danger py-2"><?= e($err) ?></div><?php endforeach; ?>
  <form method="post">
    <input type="hidden" name="id" value="<?= $id ?>">
    <div class="row g-3">
      <div class="col-md-8"><label class="form-label">Item Name</label><input type="text" name="item_name" class="form-control" required value="<?= e($item['ItemName']) ?>"></div>
      <div class="col-md-4"><label class="form-label">Category</label><input type="text" name="category" class="form-control" value="<?= e($item['Category']) ?>"></div>
      <div class="col-md-4"><label class="form-label">Unit of Measure</label><input type="text" name="uom" class="form-control" value="<?= e($item['UnitOfMeasure']) ?>"></div>
      <div class="col-md-4"><label class="form-label">Quantity On Hand</label><input type="number" name="quantity" class="form-control" value="<?= (int) $item['QuantityOnHand'] ?>" min="0"></div>
      <div class="col-md-4"><label class="form-label">Reorder Level</label><input type="number" name="reorder_level" class="form-control" value="<?= (int) $item['ReorderLevel'] ?>" min="0"></div>
      <div class="col-md-6"><label class="form-label">Unit Cost</label><input type="number" step="0.01" name="unit_cost" class="form-control" value="<?= e($item['UnitCost']) ?>" min="0"></div>
      <div class="col-md-6">
        <label class="form-label">Supplier</label>
        <select name="supplier_id" class="form-select">
          <option value="">-- None --</option>
          <?php foreach ($suppliers as $s): ?><option value="<?= (int) $s['SupplierID'] ?>" <?= $item['SupplierID']==$s['SupplierID']?'selected':'' ?>><?= e($s['SupplierName']) ?></option><?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="mt-4 d-flex gap-2">
      <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Save Changes</button>
      <a href="list.php" class="btn btn-outline-secondary">Cancel</a>
    </div>
  </form>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
