<?php
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['Admin','Manager']);

$pageTitle = 'Add Employee';
$users = $pdo->query(
    "SELECT u.UserID, u.Username, u.FullName FROM Users u
     LEFT JOIN Employees e ON e.UserID = u.UserID
     WHERE e.EmployeeID IS NULL
     ORDER BY u.FullName"
)->fetchAll();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name']);
    $position = trim($_POST['position']);
    $department = trim($_POST['department']);
    $hireDate = $_POST['hire_date'];
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $userId = $_POST['user_id'] !== '' ? (int) $_POST['user_id'] : null;

    if ($fullName === '' || $position === '' || $department === '') {
        $errors[] = 'Full name, position and department are required.';
    }
    if (!$errors) {
        $stmt = $pdo->prepare('INSERT INTO Employees (UserID, FullName, Position, Department, HireDate, Phone, Email) VALUES (?,?,?,?,?,?,?)');
        $stmt->execute([$userId, $fullName, $position, $department, $hireDate ?: date('Y-m-d'), $phone ?: null, $email ?: null]);
        logAudit($pdo, 'CREATE', 'Employees', (int) $pdo->lastInsertId(), "Employee '$fullName' added");
        flash('success', "Employee '$fullName' added.");
        redirectTo('list.php');
    }
}

require __DIR__ . '/../../includes/header.php';
?>
<div class="section-card" style="max-width:640px;">
  <h2><i class="bi bi-person-plus"></i> Add Employee</h2>
  <?php foreach ($errors as $err): ?><div class="alert alert-danger py-2"><?= e($err) ?></div><?php endforeach; ?>
  <form method="post">
    <div class="row g-3">
      <div class="col-md-8"><label class="form-label">Full Name</label><input type="text" name="full_name" class="form-control" required></div>
      <div class="col-md-4"><label class="form-label">Hire Date</label><input type="date" name="hire_date" class="form-control" value="<?= date('Y-m-d') ?>"></div>
      <div class="col-md-6"><label class="form-label">Position</label><input type="text" name="position" class="form-control" required></div>
      <div class="col-md-6"><label class="form-label">Department</label><input type="text" name="department" class="form-control" required></div>
      <div class="col-md-6"><label class="form-label">Phone</label><input type="text" name="phone" class="form-control"></div>
      <div class="col-md-6"><label class="form-label">Email</label><input type="email" name="email" class="form-control"></div>
      <div class="col-md-12">
        <label class="form-label">Linked System Account (optional)</label>
        <select name="user_id" class="form-select">
          <option value="">-- No login account --</option>
          <?php foreach ($users as $u): ?><option value="<?= (int) $u['UserID'] ?>"><?= e($u['FullName']) ?> (<?= e($u['Username']) ?>)</option><?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="mt-4 d-flex gap-2">
      <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Save Employee</button>
      <a href="list.php" class="btn btn-outline-secondary">Cancel</a>
    </div>
  </form>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
