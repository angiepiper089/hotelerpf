<?php
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['Admin','Manager','FrontDesk']);

$pageTitle = 'Edit Guest';
$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM Guests WHERE GuestID = ?');
$stmt->execute([$id]);
$guest = $stmt->fetch();
if (!$guest) { flash('error', 'Guest not found.'); redirectTo('list.php'); }

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $idNumber = trim($_POST['id_number']);
    $tier = $_POST['loyalty_tier'];

    if ($fullName === '') $errors[] = 'Full name is required.';

    if (!$errors) {
        $pdo->prepare('UPDATE Guests SET FullName=?, Email=?, Phone=?, Address=?, IDNumber=?, LoyaltyTier=? WHERE GuestID=?')
            ->execute([$fullName, $email ?: null, $phone ?: null, $address ?: null, $idNumber ?: null, $tier, $id]);
        logAudit($pdo, 'UPDATE', 'Guests', $id, "Guest '$fullName' updated");
        flash('success', 'Guest updated.');
        redirectTo('profile.php?id=' . $id);
    }
    $guest = array_merge($guest, $_POST);
}

require __DIR__ . '/../../includes/header.php';
?>
<div class="section-card" style="max-width:640px;">
  <h2><i class="bi bi-pencil-square"></i> Edit Guest</h2>
  <?php foreach ($errors as $err): ?><div class="alert alert-danger py-2"><?= e($err) ?></div><?php endforeach; ?>
  <form method="post">
    <input type="hidden" name="id" value="<?= $id ?>">
    <div class="row g-3">
      <div class="col-md-8">
        <label class="form-label">Full Name</label>
        <input type="text" name="full_name" class="form-control" required value="<?= e($guest['FullName']) ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label">Loyalty Tier</label>
        <select name="loyalty_tier" class="form-select">
          <?php foreach (['Standard','Silver','Gold','Platinum'] as $t): ?>
            <option value="<?= $t ?>" <?= $guest['LoyaltyTier']===$t?'selected':'' ?>><?= $t ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-6">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" value="<?= e($guest['Email']) ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label">Phone</label>
        <input type="text" name="phone" class="form-control" value="<?= e($guest['Phone']) ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label">ID / Passport Number</label>
        <input type="text" name="id_number" class="form-control" value="<?= e($guest['IDNumber']) ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label">Address</label>
        <input type="text" name="address" class="form-control" value="<?= e($guest['Address']) ?>">
      </div>
    </div>
    <div class="mt-4 d-flex gap-2">
      <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Save Changes</button>
      <a href="profile.php?id=<?= $id ?>" class="btn btn-outline-secondary">Cancel</a>
    </div>
  </form>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
