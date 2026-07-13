<?php
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['Admin','Manager']);

$pageTitle = 'Room Types';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['type_name']);
    $rate = (float) $_POST['base_rate'];
    $maxOcc = max(1, (int) $_POST['max_occupancy']);
    $desc = trim($_POST['description'] ?? '');

    if ($name === '' || $rate <= 0) {
        $errors[] = 'Type name and a positive base rate are required.';
    } else {
        $pdo->prepare('INSERT INTO RoomTypes (TypeName, BaseRate, MaxOccupancy, Description) VALUES (?, ?, ?, ?)')
            ->execute([$name, $rate, $maxOcc, $desc ?: null]);
        logAudit($pdo, 'CREATE', 'RoomTypes', (int) $pdo->lastInsertId(), "Room type '$name' added");
        flash('success', "Room type '$name' added.");
        redirectTo('types.php');
    }
}

$roomTypes = $pdo->query('SELECT * FROM RoomTypes ORDER BY BaseRate')->fetchAll();
require __DIR__ . '/../../includes/header.php';
?>

<div class="section-card">
  <h2><i class="bi bi-tags"></i> Room Types</h2>
  <?php foreach ($errors as $err): ?><div class="alert alert-danger py-2"><?= e($err) ?></div><?php endforeach; ?>
  <div class="table-responsive mb-4">
    <table class="table erp-table align-middle">
      <thead><tr><th>Type</th><th>Base Rate</th><th>Max Occupancy</th><th>Description</th></tr></thead>
      <tbody>
        <?php foreach ($roomTypes as $t): ?>
        <tr>
          <td><?= e($t['TypeName']) ?></td>
          <td><?= formatMoney($t['BaseRate']) ?></td>
          <td><?= (int) $t['MaxOccupancy'] ?></td>
          <td class="text-muted"><?= e($t['Description']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <h6>Add Room Type</h6>
  <form method="post" class="row g-2">
    <div class="col-md-3"><input type="text" name="type_name" class="form-control" placeholder="Type name" required></div>
    <div class="col-md-2"><input type="number" step="0.01" name="base_rate" class="form-control" placeholder="Base rate" required></div>
    <div class="col-md-2"><input type="number" name="max_occupancy" class="form-control" placeholder="Max occ." value="2"></div>
    <div class="col-md-3"><input type="text" name="description" class="form-control" placeholder="Description"></div>
    <div class="col-md-2"><button type="submit" class="btn btn-primary w-100"><i class="bi bi-plus-lg"></i> Add</button></div>
  </form>
</div>

<div>
  <a href="list.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back to Rooms</a>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>