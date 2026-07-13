<?php
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['Admin','Manager']);

$pageTitle = 'Add Room';
$roomTypes = $pdo->query('SELECT RoomTypeID, TypeName, BaseRate FROM RoomTypes ORDER BY TypeName')->fetchAll();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $roomNumber = trim($_POST['room_number']);
    $roomTypeId = (int) $_POST['room_type_id'];
    $floor = (int) $_POST['floor'];
    $notes = trim($_POST['notes'] ?? '');

    if ($roomNumber === '' || !$roomTypeId) {
        $errors[] = 'Room number and room type are required.';
    }
    if (!$errors) {
        $stmt = $pdo->prepare('INSERT INTO Rooms (RoomNumber, RoomTypeID, Floor, Status, Notes) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$roomNumber, $roomTypeId, $floor, 'Available', $notes ?: null]);
        $newId = (int) $pdo->lastInsertId();
        logAudit($pdo, 'CREATE', 'Rooms', $newId, "Room $roomNumber added");
        flash('success', "Room $roomNumber added.");
        redirectTo('list.php');
    }
}

require __DIR__ . '/../../includes/header.php';
?>
<div class="section-card" style="max-width:600px;">
  <h2><i class="bi bi-door-open"></i> Add Room</h2>
  <?php foreach ($errors as $err): ?><div class="alert alert-danger py-2"><?= e($err) ?></div><?php endforeach; ?>
  <form method="post">
    <div class="mb-3">
      <label class="form-label">Room Number</label>
      <input type="text" name="room_number" class="form-control" required value="<?= e($_POST['room_number'] ?? '') ?>">
    </div>
    <div class="mb-3">
      <label class="form-label">Room Type</label>
      <select name="room_type_id" class="form-select" required>
        <?php foreach ($roomTypes as $t): ?>
          <option value="<?= (int) $t['RoomTypeID'] ?>"><?= e($t['TypeName']) ?> (<?= formatMoney($t['BaseRate']) ?>)</option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="mb-3">
      <label class="form-label">Floor</label>
      <input type="number" name="floor" class="form-control" value="1" min="1">
    </div>
    <div class="mb-3">
      <label class="form-label">Notes</label>
      <textarea name="notes" class="form-control" rows="2"></textarea>
    </div>
    <div class="d-flex gap-2">
      <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Save</button>
      <a href="list.php" class="btn btn-outline-secondary">Cancel</a>
    </div>
  </form>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>