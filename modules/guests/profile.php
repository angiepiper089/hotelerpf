<?php
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['Admin','Manager','FrontDesk']);

$pageTitle = 'Guest Profile';
$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM Guests WHERE GuestID = ?');
$stmt->execute([$id]);
$guest = $stmt->fetch();
if (!$guest) { flash('error', 'Guest not found.'); redirectTo('list.php'); }

$reservations = $pdo->prepare(
    "SELECT r.*, rm.RoomNumber, rt.TypeName
     FROM Reservations r JOIN Rooms rm ON rm.RoomID = r.RoomID JOIN RoomTypes rt ON rt.RoomTypeID = rm.RoomTypeID
     WHERE r.GuestID = ? ORDER BY r.CheckInDate DESC"
);
$reservations->execute([$id]);
$reservations = $reservations->fetchAll();

$invoices = $pdo->prepare('SELECT * FROM Invoices WHERE GuestID = ? ORDER BY IssueDate DESC');
$invoices->execute([$id]);
$invoices = $invoices->fetchAll();

$totalSpend = array_sum(array_column($invoices, 'TotalAmount'));

require __DIR__ . '/../../includes/header.php';
?>

<div class="section-card">
  <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
    <div>
      <h2 class="mb-1"><i class="bi bi-person-circle"></i> <?= e($guest['FullName']) ?></h2>
      <span class="badge text-bg-light border"><?= e($guest['LoyaltyTier']) ?> Tier</span>
      <div class="text-muted small mt-2">
        <?= e($guest['Email']) ?: 'No email' ?> &middot; <?= e($guest['Phone']) ?: 'No phone' ?><br>
        <?= e($guest['Address']) ?><br>
        ID: <?= e($guest['IDNumber']) ?: '-' ?>
      </div>
    </div>
    <div class="text-end">
      <div class="kpi-value"><?= formatMoney($totalSpend) ?></div>
      <div class="kpi-label">Lifetime Spend</div>
      <a href="edit.php?id=<?= $id ?>" class="btn btn-sm btn-outline-secondary mt-2"><i class="bi bi-pencil"></i> Edit</a>
    </div>
  </div>
</div>

<div class="section-card">
  <h2><i class="bi bi-calendar3"></i> Stay History</h2>
  <div class="table-responsive">
    <table class="table erp-table align-middle">
      <thead><tr><th>#</th><th>Room</th><th>Check-in</th><th>Check-out</th><th>Status</th></tr></thead>
      <tbody>
        <?php foreach ($reservations as $r): ?>
        <tr>
          <td>#<?= (int) $r['ReservationID'] ?></td>
          <td><?= e($r['RoomNumber']) ?> (<?= e($r['TypeName']) ?>)</td>
          <td><?= formatDate($r['CheckInDate']) ?></td>
          <td><?= formatDate($r['CheckOutDate']) ?></td>
          <td><span class="status-pill status-<?= e($r['Status']) ?>"><?= e($r['Status']) ?></span></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$reservations): ?><tr><td colspan="5" class="text-center text-muted py-3">No reservations yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="section-card">
  <h2><i class="bi bi-receipt"></i> Billing History</h2>
  <div class="table-responsive">
    <table class="table erp-table align-middle">
      <thead><tr><th>Invoice #</th><th>Date</th><th>Total</th><th>Status</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($invoices as $inv): ?>
        <tr>
          <td>#<?= (int) $inv['InvoiceID'] ?></td>
          <td><?= formatDate($inv['IssueDate']) ?></td>
          <td><?= formatMoney($inv['TotalAmount']) ?></td>
          <td><span class="status-pill status-<?= e($inv['Status']) ?>"><?= e($inv['Status']) ?></span></td>
          <td><a href="../billing/invoice.php?id=<?= (int) $inv['InvoiceID'] ?>" class="btn btn-sm btn-outline-secondary">View</a></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$invoices): ?><tr><td colspan="5" class="text-center text-muted py-3">No invoices yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<a href="list.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back to Guests</a>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
