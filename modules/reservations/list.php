<?php
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['Admin','Manager','FrontDesk']);

$pageTitle = 'Reservations';

$statusFilter = $_GET['status'] ?? '';
$sql = "SELECT r.ReservationID, g.FullName, g.GuestID, rm.RoomNumber, rt.TypeName, r.CheckInDate, r.CheckOutDate,
               r.ActualCheckIn, r.ActualCheckOut, r.NumGuests, r.Status
        FROM Reservations r
        JOIN Guests g ON g.GuestID = r.GuestID
        JOIN Rooms rm ON rm.RoomID = r.RoomID
        JOIN RoomTypes rt ON rt.RoomTypeID = rm.RoomTypeID";
$params = [];
if ($statusFilter !== '') {
    $sql .= ' WHERE r.Status = ?';
    $params[] = $statusFilter;
}
$sql .= ' ORDER BY r.CheckInDate DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reservations = $stmt->fetchAll();

require __DIR__ . '/../../includes/header.php';
?>

<div class="section-card">
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div class="btn-group" role="group">
      <a href="?status=" class="btn btn-sm btn-outline-secondary <?= $statusFilter==='' ?'active':'' ?>">All</a>
      <a href="?status=Booked" class="btn btn-sm btn-outline-secondary <?= $statusFilter==='Booked' ?'active':'' ?>">Booked</a>
      <a href="?status=CheckedIn" class="btn btn-sm btn-outline-secondary <?= $statusFilter==='CheckedIn' ?'active':'' ?>">Checked-In</a>
      <a href="?status=CheckedOut" class="btn btn-sm btn-outline-secondary <?= $statusFilter==='CheckedOut' ?'active':'' ?>">Checked-Out</a>
      <a href="?status=Cancelled" class="btn btn-sm btn-outline-secondary <?= $statusFilter==='Cancelled' ?'active':'' ?>">Cancelled</a>
    </div>
    <a href="add.php" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> New Reservation</a>
  </div>

  <div class="table-responsive">
    <table class="table erp-table align-middle">
      <thead>
        <tr>
          <th>#</th><th>Guest</th><th>Room</th><th>Check-in</th><th>Check-out</th>
          <th>Guests</th><th>Status</th><th class="text-end">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($reservations as $r): ?>
        <tr>
          <td>#<?= (int) $r['ReservationID'] ?></td>
          <td>
            <a href="../guests/profile.php?id=<?= (int) $r['GuestID'] ?>"><?= e($r['FullName']) ?></a>
          </td>
          <td><?= e($r['RoomNumber']) ?> <span class="text-muted small">(<?= e($r['TypeName']) ?>)</span></td>
          <td><?= formatDate($r['CheckInDate']) ?></td>
          <td><?= formatDate($r['CheckOutDate']) ?></td>
          <td><?= (int) $r['NumGuests'] ?></td>
          <td><span class="status-pill status-<?= e($r['Status']) ?>"><?= e($r['Status']) ?></span></td>
          <td class="text-end">
            <?php if ($r['Status'] === 'Booked'): ?>
              <a href="edit.php?id=<?= (int) $r['ReservationID'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
              <a href="checkin.php?id=<?= (int) $r['ReservationID'] ?>" class="btn btn-sm btn-outline-success" data-confirm="Check in this guest now?"><i class="bi bi-box-arrow-in-right"></i> Check-in</a>
              <a href="cancel.php?id=<?= (int) $r['ReservationID'] ?>" class="btn btn-sm btn-outline-danger" data-confirm="Cancel this reservation?"><i class="bi bi-x-lg"></i></a>
            <?php elseif ($r['Status'] === 'CheckedIn'): ?>
              <a href="checkout.php?id=<?= (int) $r['ReservationID'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-box-arrow-right"></i> Check-out &amp; Bill</a>
            <?php else: ?>
              <span class="text-muted small">&mdash;</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$reservations): ?>
          <tr><td colspan="8" class="text-center text-muted py-4">No reservations found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
