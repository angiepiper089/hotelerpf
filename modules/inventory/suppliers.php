<?php
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['Admin','Manager','Finance']);

$pageTitle = 'Suppliers & Purchase Orders';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_supplier') {
    $name = trim($_POST['supplier_name']);
    $contact = trim($_POST['contact_person']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $address = trim($_POST['address']);
    if ($name === '') {
        $errors[] = 'Supplier name is required.';
    } else {
        $pdo->prepare('INSERT INTO Suppliers (SupplierName, ContactPerson, Phone, Email, Address) VALUES (?,?,?,?,?)')
            ->execute([$name, $contact ?: null, $phone ?: null, $email ?: null, $address ?: null]);
        logAudit($pdo, 'CREATE', 'Suppliers', (int) $pdo->lastInsertId(), "Supplier '$name' added");
        flash('success', "Supplier '$name' added.");
        redirectTo('suppliers.php');
    }
}

$suppliers = $pdo->query('SELECT * FROM Suppliers ORDER BY SupplierName')->fetchAll();
$purchaseOrders = $pdo->query(
    "SELECT po.*, s.SupplierName FROM PurchaseOrders po JOIN Suppliers s ON s.SupplierID = po.SupplierID ORDER BY po.OrderDate DESC"
)->fetchAll();

require __DIR__ . '/../../includes/header.php';
?>

<div class="section-card">
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <h2 class="mb-0"><i class="bi bi-truck"></i> Suppliers</h2>
    <a href="list.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Inventory</a>
  </div>
  <div class="table-responsive mb-4">
    <table class="table erp-table align-middle">
      <thead><tr><th>Supplier</th><th>Contact</th><th>Phone</th><th>Email</th></tr></thead>
      <tbody>
        <?php foreach ($suppliers as $s): ?>
        <tr>
          <td><?= e($s['SupplierName']) ?></td>
          <td><?= e($s['ContactPerson']) ?></td>
          <td><?= e($s['Phone']) ?></td>
          <td><?= e($s['Email']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <h6>Add Supplier</h6>
  <?php foreach ($errors as $err): ?><div class="alert alert-danger py-2"><?= e($err) ?></div><?php endforeach; ?>
  <form method="post" class="row g-2">
    <input type="hidden" name="action" value="add_supplier">
    <div class="col-md-3"><input type="text" name="supplier_name" class="form-control" placeholder="Supplier name" required></div>
    <div class="col-md-2"><input type="text" name="contact_person" class="form-control" placeholder="Contact person"></div>
    <div class="col-md-2"><input type="text" name="phone" class="form-control" placeholder="Phone"></div>
    <div class="col-md-3"><input type="email" name="email" class="form-control" placeholder="Email"></div>
    <div class="col-md-2"><button type="submit" class="btn btn-primary w-100"><i class="bi bi-plus-lg"></i> Add</button></div>
  </form>
</div>

<div class="section-card">
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <h2 class="mb-0"><i class="bi bi-file-earmark-text"></i> Purchase Orders</h2>
    <a href="po-create.php" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg"></i> New Purchase Order</a>
  </div>
  <div class="table-responsive">
    <table class="table erp-table align-middle">
      <thead><tr><th>PO #</th><th>Supplier</th><th>Order Date</th><th>Total</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
      <tbody>
        <?php foreach ($purchaseOrders as $po): ?>
        <tr>
          <td>#<?= (int) $po['PurchaseOrderID'] ?></td>
          <td><?= e($po['SupplierName']) ?></td>
          <td><?= formatDate($po['OrderDate']) ?></td>
          <td><?= formatMoney($po['TotalAmount']) ?></td>
          <td><span class="status-pill status-<?= e($po['Status']) ?>"><?= e($po['Status']) ?></span></td>
          <td class="text-end"><a href="po-view.php?id=<?= (int) $po['PurchaseOrderID'] ?>" class="btn btn-sm btn-outline-secondary">View</a></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$purchaseOrders): ?><tr><td colspan="6" class="text-center text-muted py-3">No purchase orders yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
