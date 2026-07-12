<?php
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['Admin','Manager','FrontDesk']);

$pageTitle = 'Edit Reservation';
$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM Reservations WHERE ReservationID = ?');
$stmt->execute([$id]);
$reservation = $stmt->fetch();
if (!$reservation) {
    flash('error', 'Reservation not found.');
    redirectTo('list.php');
}
if ($reservation['Status'] !== 'Booked') {
    flash('error', 'Only reservations still in "Booked" status can be edited.');
    redirectTo('list.php');
}

$guests = $pdo->query('SELECT GuestID, FullName FROM Guests ORDER BY FullName')->fetchAll();
$rooms = $pdo->query(
    "SELECT rm.RoomID, rm.RoomNumber, rt.TypeName, rt.BaseRate
     FROM Rooms rm JOIN RoomTypes rt ON rt.RoomTypeID = rm.RoomTypeID
     ORDER BY rm.RoomNumber"
)->fetchAll();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $guestId = (int) $_POST['guest_id'];
    $roomId = (int) $_POST['room_id'];
    $checkIn = $_POST['check_in'];
    $checkOut = $_POST['check_out'];
    $numGuests = max(1, (int) $_POST['num_guests']);

    if (strtotime($checkOut) <= strtotime($checkIn)) {
        $errors[] = 'Check-out date must be after check-in date.';
    }
    if (!$errors) {
        $overlap = $pdo->prepare(
            "SELECT COUNT(*) c FROM Reservations
             WHERE RoomID = ? AND ReservationID <> ? AND Status IN ('Booked','CheckedIn')
             AND CheckInDate < ? AND CheckOutDate > ?"
        );
        $overlap->execute([$roomId, $id, $checkOut, $checkIn]);
        if ((int) $overlap->fetch()['c'] > 0) {
            $errors[] = 'This room is already booked for an overlapping date range.';
        }
    }
    if (!$errors) {
        $upd = $pdo->prepare(
            'UPDATE Reservations SET GuestID=?, RoomID=?, CheckInDate=?, CheckOutDate=?, NumGuests=? WHERE ReservationID=?'
        );
        $upd->execute([$guestId, $roomId, $checkIn, $checkOut, $numGuests, $id]);
        logAudit($pdo, 'UPDATE', 'Reservations', $id, 'Reservation details updated');
        flash('success', "Reservation #$id updated.");
        redirectTo('list.php');
    }
    $reservation = array_merge($reservation, $_POST);
}

require __DIR__ . '/../../includes/header.php';
?>

<div class="section-card" style="max-width:720px;">
  <h2><i class="bi bi-pencil-square"></i> Edit Reservation #<?= $id ?></h2>

  <?php foreach ($errors as $err): ?>
    <div class="alert alert-danger py-2"><?= e($err) ?></div>
  <?php endforeach; ?>

  <form method="post">
    <input type="hidden" name="id" value="<?= $id ?>">
    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label">Guest</label>
        <select name="guest_id" class="form-select" required>
          <?php foreach ($guests as $g): ?>
            <option value="<?= (int) $g['GuestID'] ?>" <?= $reservation['GuestID'] == $g['GuestID'] ? 'selected' : '' ?>><?= e($g['FullName']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-6">
        <label class="form-label">Room</label>
        <select name="room_id" class="form-select" required>
          <?php foreach ($rooms as $r): ?>
            <option value="<?= (int) $r['RoomID'] ?>" <?= $reservation['RoomID'] == $r['RoomID'] ? 'selected' : '' ?>>
              <?= e($r['RoomNumber']) ?> &middot; <?= e($r['TypeName']) ?> &middot; <?= formatMoney($r['BaseRate']) ?>/night
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">Check-in Date</label>
        <input type="date" name="check_in" class="form-control" required value="<?= e(substr($reservation['CheckInDate'],0,10)) ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label">Check-out Date</label>
        <input type="date" name="check_out" class="form-control" required value="<?= e(substr($reservation['CheckOutDate'],0,10)) ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label">Number of Guests</label>
        <input type="number" name="num_guests" class="form-control" min="1" value="<?= (int) $reservation['NumGuests'] ?>">
      </div>
    </div>
    <div class="mt-4 d-flex gap-2">
      <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Save Changes</button>
      <a href="list.php" class="btn btn-outline-secondary">Cancel</a>
    </div>
  </form>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
