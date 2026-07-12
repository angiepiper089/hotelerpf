<?php
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['Admin']);

$pageTitle = 'User Accounts';
$roles = $pdo->query('SELECT * FROM Roles ORDER BY RoleName')->fetchAll();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_user') {
    $fullName = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $roleId = (int) $_POST['role_id'];

    if ($fullName === '' || $email === '' || $username === '' || $password === '' || !$roleId) {
        $errors[] = 'All fields are required.';
    } elseif (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }

    if (!$errors) {
        try {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare('INSERT INTO Users (FullName, Email, Username, PasswordHash, RoleID, IsActive) VALUES (?,?,?,?,?,1)');
            $stmt->execute([$fullName, $email, $username, $hash, $roleId]);
            logAudit($pdo, 'CREATE', 'Users', (int) $pdo->lastInsertId(), "User '$username' created");
            flash('success', "User '$username' created.");
            redirectTo('list.php');
        } catch (PDOException $e) {
            $errors[] = 'Could not create user (username/email may already be taken).';
        }
    }
}

$users = $pdo->query(
    "SELECT u.*, r.RoleName FROM Users u JOIN Roles r ON r.RoleID = u.RoleID ORDER BY u.FullName"
)->fetchAll();

require __DIR__ . '/../../includes/header.php';
?>

<div class="section-card">
  <h2><i class="bi bi-person-gear"></i> User Accounts</h2>
  <p class="text-muted small">Role-based access control: each account is assigned one role that determines which ERP modules it can see, matching the security principles covered in the textbook.</p>

  <div class="table-responsive mb-4">
    <table class="table erp-table align-middle">
      <thead><tr><th>Name</th><th>Username</th><th>Email</th><th>Role</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
      <tbody>
        <?php foreach ($users as $u): ?>
        <tr>
          <td><?= e($u['FullName']) ?></td>
          <td><?= e($u['Username']) ?></td>
          <td><?= e($u['Email']) ?></td>
          <td><span class="badge text-bg-light border"><?= e($u['RoleName']) ?></span></td>
          <td><?= $u['IsActive'] ? '<span class="status-pill status-Available">Active</span>' : '<span class="status-pill status-Cancelled">Inactive</span>' ?></td>
          <td class="text-end">
            <?php if ((int) $u['UserID'] !== (int) $_SESSION['user_id']): ?>
              <a href="toggle-status.php?id=<?= (int) $u['UserID'] ?>" class="btn btn-sm btn-outline-secondary" data-confirm="<?= $u['IsActive'] ? 'Deactivate' : 'Activate' ?> this account?">
                <i class="bi bi-power"></i> <?= $u['IsActive'] ? 'Deactivate' : 'Activate' ?>
              </a>
            <?php else: ?>
              <span class="text-muted small">Current user</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <h6>Create New User</h6>
  <?php foreach ($errors as $err): ?><div class="alert alert-danger py-2"><?= e($err) ?></div><?php endforeach; ?>
  <form method="post" class="row g-2">
    <input type="hidden" name="action" value="add_user">
    <div class="col-md-3"><input type="text" name="full_name" class="form-control" placeholder="Full name" required></div>
    <div class="col-md-2"><input type="text" name="username" class="form-control" placeholder="Username" required></div>
    <div class="col-md-3"><input type="email" name="email" class="form-control" placeholder="Email" required></div>
    <div class="col-md-2"><input type="password" name="password" class="form-control" placeholder="Password" required></div>
    <div class="col-md-1">
      <select name="role_id" class="form-select" required>
        <?php foreach ($roles as $r): ?><option value="<?= (int) $r['RoleID'] ?>"><?= e($r['RoleName']) ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-1"><button type="submit" class="btn btn-primary w-100"><i class="bi bi-plus-lg"></i></button></div>
  </form>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
