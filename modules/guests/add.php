<?php
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['Admin','Manager','FrontDesk']);

$pageTitle = 'Add Guest';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $idNumber = trim($_POST['id_number']);
    $tier = $_POST['loyalty_tier'];

    if ($fullName === '') $errors[] = 'Full name is required.';
    if (!in_array($tier, ['Standard','Silver','Gold','Platinum'], true)) $errors[] = 'Invalid loyalty tier.';

    if (!$errors) {
        $stmt = $pdo->prepare('INSERT INTO Guests (FullName, Email, Phone, Address, IDNumber, LoyaltyTier) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$fullName, $email ?: null, $phone ?: null, $address ?: null, $idNumber ?: null, $tier]);
        $newId = (int) $pdo->lastInsertId();
        logAudit($pdo, 'CREATE', 'Guests', $newId, "Guest '$fullName' added");
        flash('success', "Guest '$fullName' added.");
        redirectTo('list.php');
    }
}

require __DIR__ . '/../../includes/header.php';
?>
<div class="section-card" style="max-width:640px;">
  <h2><i class="bi bi-person-plus"></i> Add Guest</h2>
  <?php foreach ($errors as $err): ?><div class="alert alert-danger py-2"><?= e($err) ?></div><?php endforeach; ?>
  <form method="post">
    <div class="row g-3">
      <div class="col-md-8">
        <label class="form-label">Full Name</label>
        <input type="text" name="full_name" class="form-control" required value="<?= e($_POST['full_name'] ?? '') ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label">Loyalty Tier</label>
        <select name="loyalty_tier" class="form-select">
          <?php foreach (['Standard','Silver','Gold','Platinum'] as $t): ?>
            <option value="<?= $t ?>"><?= $t ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-6">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" value="<?= e($_POST['email'] ?? '') ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label">Phone</label>
        <input type="text" name="phone" class="form-control" value="<?= e($_POST['phone'] ?? '') ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label">ID / Passport Number</label>
        <input type="text" name="id_number" class="form-control" value="<?= e($_POST['id_number'] ?? '') ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label">Address</label>
        <input type="text" name="address" class="form-control" value="<?= e($_POST['address'] ?? '') ?>">
      </div>
    </div>
    <div class="mt-4 d-flex gap-2">
      <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Save Guest</button>
      <a href="list.php" class="btn btn-outline-secondary">Cancel</a>
    </div>
  </form>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
