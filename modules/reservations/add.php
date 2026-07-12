<?php
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['Admin','Manager','FrontDesk']);

$pageTitle = 'New Reservation';
$errors = [];

$guests = $pdo->query('SELECT GuestID, FullName, LoyaltyTier FROM Guests ORDER BY FullName')->fetchAll();
$rooms = $pdo->query(
    "SELECT rm.RoomID, rm.RoomNumber, rm.Status, rt.TypeName, rt.BaseRate
     FROM Rooms rm JOIN RoomTypes rt ON rt.RoomTypeID = rm.RoomTypeID
     ORDER BY rm.RoomNumber"
)->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $guestId = (int) $_POST['guest_id'];
    $roomId = (int) $_POST['room_id'];
    $checkIn = $_POST['check_in'];
    $checkOut = $_POST['check_out'];
    $numGuests = max(1, (int) $_POST['num_guests']);

    if (!$guestId || !$roomId) $errors[] = 'Please select a guest and a room.';
    if (!$checkIn || !$checkOut) $errors[] = 'Please provide check-in and check-out dates.';
    if ($checkIn && $checkOut && strtotime($checkOut) <= strtotime($checkIn)) {
        $errors[] = 'Check-out date must be after check-in date.';
    }

    if (!$errors) {
        // Prevent double-booking: reject if the room has an overlapping active reservation.
        $overlap = $pdo->prepare(
            "SELECT COUNT(*) c FROM Reservations
             WHERE RoomID = ? AND Status IN ('Booked','CheckedIn')
             AND CheckInDate < ? AND CheckOutDate > ?"
        );
        $overlap->execute([$roomId, $checkOut, $checkIn]);
        if ((int) $overlap->fetch()['c'] > 0) {
            $errors[] = 'This room is already booked for an overlapping date range. Please choose another room or dates.';
        }
    }

    if (!$errors) {
        $stmt = $pdo->prepare(
            'INSERT INTO Reservations (GuestID, RoomID, CheckInDate, CheckOutDate, NumGuests, Status, CreatedBy)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$guestId, $roomId, $checkIn, $checkOut, $numGuests, 'Booked', $_SESSION['user_id']]);
        $newId = (int) $pdo->lastInsertId();
        logAudit($pdo, 'CREATE', 'Reservations', $newId, "Reservation created for guest #$guestId, room #$roomId");
        flash('success', "Reservation #$newId created successfully.");
        redirectTo('list.php');
    }
}

require __DIR__ . '/../../includes/header.php';
?>

<div class="section-card" style="max-width:720px;">
  <h2><i class="bi bi-calendar-plus"></i> New Reservation</h2>

  <?php foreach ($errors as $err): ?>
    <div class="alert alert-danger py-2"><?= e($err) ?></div>
  <?php endforeach; ?>

  <form method="post">
    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label">Guest</label>
        <select name="guest_id" class="form-select" required>
          <option value="">-- Select guest --</option>
          <?php foreach ($guests as $g): ?>
            <option value="<?= (int) $g['GuestID'] ?>" <?= (($_POST['guest_id'] ?? '') == $g['GuestID']) ? 'selected' : '' ?>>
              <?= e($g['FullName']) ?> (<?= e($g['LoyaltyTier']) ?>)
            </option>
          <?php endforeach; ?>
        </select>
        <div class="form-text">Guest not listed? <a href="../guests/add.php">Add a new guest</a> first.</div>
      </div>
      <div class="col-md-6">
        <label class="form-label">Room</label>
        <select name="room_id" class="form-select" required>
          <option value="">-- Select room --</option>
          <?php foreach ($rooms as $r): ?>
            <option value="<?= (int) $r['RoomID'] ?>" <?= (($_POST['room_id'] ?? '') == $r['RoomID']) ? 'selected' : '' ?>>
              <?= e($r['RoomNumber']) ?> &middot; <?= e($r['TypeName']) ?> &middot; <?= formatMoney($r['BaseRate']) ?>/night
              <?= $r['Status'] !== 'Available' ? '(' . e($r['Status']) . ')' : '' ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label">Check-in Date</label>
        <input type="date" name="check_in" class="form-control" required value="<?= e($_POST['check_in'] ?? '') ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label">Check-out Date</label>
        <input type="date" name="check_out" class="form-control" required value="<?= e($_POST['check_out'] ?? '') ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label">Number of Guests</label>
        <input type="number" name="num_guests" class="form-control" min="1" value="<?= e($_POST['num_guests'] ?? '1') ?>">
      </div>
    </div>
    <div class="mt-4 d-flex gap-2">
      <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Create Reservation</button>
      <a href="list.php" class="btn btn-outline-secondary">Cancel</a>
    </div>
  </form>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
