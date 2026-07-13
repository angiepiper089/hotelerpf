<?php
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['Admin','Manager']);

$pageTitle = 'Edit Room';
$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM Rooms WHERE RoomID = ?');
$stmt->execute([$id]);
$room = $stmt->fetch();
if (!$room) { flash('error', 'Room not found.'); redirectTo('list.php'); }

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
        $pdo->prepare('UPDATE Rooms SET RoomNumber=?, RoomTypeID=?, Floor=?, Notes=? WHERE RoomID=?')
            ->execute([$roomNumber, $roomTypeId, $floor, $notes ?: null, $id]);
        logAudit($pdo, 'UPDATE', 'Rooms', $id, "Room $roomNumber updated");
        flash('success', "Room $roomNumber updated.");
        redirectTo('list.php');
    }
    $room = array_merge($room, ['RoomNumber'=>$roomNumber,'RoomTypeID'=>$roomTypeId,'Floor'=>$floor,'Notes'=>$notes]);
}

require __DIR__ . '/../../includes/header.php';
?>
<div class="section-card" style="max-width:600px;">
  <h2><i class="bi bi-pencil-square"></i> Edit Room</h2>
  <?php foreach ($errors as $err): ?><div class="alert alert-danger py-2"><?= e($err) ?></div><?php endforeach; ?>
  <form method="post">
    <input type="hidden" name="id" value="<?= $id ?>">
    <div class="mb-3">
      <label class="form-label">Room Number</label>
      <input type="text" name="room_number" class="form-control" required value="<?= e($room['RoomNumber']) ?>">
    </div>
    <div class="mb-3">
      <label class="form-label">Room Type</label>
      <select name="room_type_id" class="form-select" required>
        <?php foreach ($roomTypes as $t): ?>
          <option value="<?= (int) $t['RoomTypeID'] ?>" <?= $room['RoomTypeID']==$t['RoomTypeID']?'selected':'' ?>><?= e($t['TypeName']) ?> (<?= formatMoney($t['BaseRate']) ?>)</option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="mb-3">
      <label class="form-label">Floor</label>
      <input type="number" name="floor" class="form-control" value="<?= (int) $room['Floor'] ?>" min="1">
    </div>
    <div class="mb-3">
      <label class="form-label">Notes</label>
      <textarea name="notes" class="form-control" rows="2"><?= e($room['Notes']) ?></textarea>
    </div>
    <div class="d-flex gap-2">
      <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Save Changes</button>
      <a href="list.php" class="btn btn-outline-secondary">Cancel</a>
    </div>
  </form>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>