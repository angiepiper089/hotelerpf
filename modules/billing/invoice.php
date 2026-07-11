<?php
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['Admin','Manager','FrontDesk','Finance']);

$pageTitle = 'Invoice';
$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare(
    "SELECT inv.*, g.FullName, g.Email, g.Phone, r.ReservationID, rm.RoomNumber, r.CheckInDate, r.CheckOutDate
     FROM Invoices inv
     JOIN Guests g ON g.GuestID = inv.GuestID
     JOIN Reservations r ON r.ReservationID = inv.ReservationID
     JOIN Rooms rm ON rm.RoomID = r.RoomID
     WHERE inv.InvoiceID = ?"
);
$stmt->execute([$id]);
$invoice = $stmt->fetch();
if (!$invoice) { flash('error', 'Invoice not found.'); redirectTo('list.php'); }

$items = $pdo->prepare('SELECT * FROM InvoiceItems WHERE InvoiceID = ?');
$items->execute([$id]);
$items = $items->fetchAll();

$payments = $pdo->prepare('SELECT * FROM Payments WHERE InvoiceID = ? ORDER BY PaymentDate');
$payments->execute([$id]);
$payments = $payments->fetchAll();

$paidSoFar = array_sum(array_column($payments, 'Amount'));
$balance = round($invoice['TotalAmount'] - $paidSoFar, 2);

require __DIR__ . '/../../includes/header.php';
?>

<div class="section-card">
  <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
    <div>
      <h2><i class="bi bi-receipt"></i> Invoice #<?= (int) $invoice['InvoiceID'] ?></h2>
      <p class="mb-1"><strong><?= e($invoice['FullName']) ?></strong> &middot; <?= e($invoice['Email']) ?> &middot; <?= e($invoice['Phone']) ?></p>
      <p class="text-muted">Reservation #<?= (int) $invoice['ReservationID'] ?> &middot; Room <?= e($invoice['RoomNumber']) ?> &middot; <?= formatDate($invoice['CheckInDate']) ?> &rarr; <?= formatDate($invoice['CheckOutDate']) ?></p>
    </div>
    <div class="text-end">
      <span class="status-pill status-<?= e($invoice['Status']) ?> fs-6"><?= e($invoice['Status']) ?></span>
      <div class="mt-2"><button onclick="window.print()" class="btn btn-sm btn-outline-secondary"><i class="bi bi-printer"></i> Print</button></div>
    </div>
  </div>
</div>

<div class="section-card">
  <h2>Charges</h2>
  <div class="table-responsive">
    <table class="table erp-table align-middle">
      <thead><tr><th>Description</th><th>Qty</th><th class="text-end">Amount</th></tr></thead>
      <tbody>
        <?php foreach ($items as $it): ?>
          <tr><td><?= e($it['Description']) ?></td><td><?= (int) $it['Quantity'] ?></td><td class="text-end"><?= formatMoney($it['Amount']) ?></td></tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr><th colspan="2" class="text-end">Room Charges</th><th class="text-end"><?= formatMoney($invoice['RoomCharges']) ?></th></tr>
        <tr><th colspan="2" class="text-end">Service Charges</th><th class="text-end"><?= formatMoney($invoice['ServiceCharges']) ?></th></tr>
        <tr><th colspan="2" class="text-end">Tax</th><th class="text-end"><?= formatMoney($invoice['TaxAmount']) ?></th></tr>
        <tr class="table-light"><th colspan="2" class="text-end">Total</th><th class="text-end"><?= formatMoney($invoice['TotalAmount']) ?></th></tr>
        <tr><th colspan="2" class="text-end">Paid So Far</th><th class="text-end"><?= formatMoney($paidSoFar) ?></th></tr>
        <tr><th colspan="2" class="text-end">Balance Due</th><th class="text-end"><?= formatMoney($balance) ?></th></tr>
      </tfoot>
    </table>
  </div>
</div>

<div class="section-card">
  <h2>Payments</h2>
  <div class="table-responsive mb-3">
    <table class="table erp-table align-middle">
      <thead><tr><th>Date</th><th>Method</th><th class="text-end">Amount</th></tr></thead>
      <tbody>
        <?php foreach ($payments as $p): ?>
          <tr><td><?= formatDate($p['PaymentDate']) ?></td><td><?= e($p['Method']) ?></td><td class="text-end"><?= formatMoney($p['Amount']) ?></td></tr>
        <?php endforeach; ?>
        <?php if (!$payments): ?><tr><td colspan="3" class="text-center text-muted py-3">No payments recorded yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php if ($balance > 0): ?>
    <h6>Record Payment</h6>
    <form method="post" action="pay.php" class="row g-2 align-items-end">
      <input type="hidden" name="invoice_id" value="<?= $id ?>">
      <div class="col-md-3">
        <label class="form-label">Amount</label>
        <input type="number" step="0.01" name="amount" class="form-control" max="<?= $balance ?>" value="<?= $balance ?>" required>
      </div>
      <div class="col-md-3">
        <label class="form-label">Method</label>
        <select name="method" class="form-select">
          <option value="Cash">Cash</option>
          <option value="Card">Card</option>
          <option value="BankTransfer">Bank Transfer</option>
        </select>
      </div>
      <div class="col-md-3">
        <button type="submit" class="btn btn-primary"><i class="bi bi-cash-coin"></i> Record Payment</button>
      </div>
    </form>
  <?php else: ?>
    <div class="alert alert-success py-2 mb-0">This invoice is fully paid.</div>
  <?php endif; ?>
</div>

<a href="list.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back to Billing</a>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
