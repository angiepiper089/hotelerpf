<?php
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['Admin','Manager']);

$pageTitle = 'Employees';
$employees = $pdo->query(
    "SELECT e.*, u.Username, r.RoleName FROM Employees e
     LEFT JOIN Users u ON u.UserID = e.UserID
     LEFT JOIN Roles r ON r.RoleID = u.RoleID
     ORDER BY e.Department, e.FullName"
)->fetchAll();

require __DIR__ . '/../../includes/header.php';
?>

<div class="section-card">
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <h2 class="mb-0"><i class="bi bi-person-badge"></i> Employees</h2>
    <a href="add.php" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg"></i> Add Employee</a>
  </div>
  <div class="table-responsive">
    <table class="table erp-table align-middle">
      <thead><tr><th>Name</th><th>Position</th><th>Department</th><th>Hire Date</th><th>Contact</th><th>Linked System Account</th></tr></thead>
      <tbody>
        <?php foreach ($employees as $emp): ?>
        <tr>
          <td><?= e($emp['FullName']) ?></td>
          <td><?= e($emp['Position']) ?></td>
          <td><?= e($emp['Department']) ?></td>
          <td><?= formatDate($emp['HireDate']) ?></td>
          <td class="small text-muted"><?= e($emp['Phone']) ?><br><?= e($emp['Email']) ?></td>
          <td><?= $emp['Username'] ? e($emp['Username']) . ' (' . e($emp['RoleName']) . ')' : '<span class="text-muted">No login account</span>' ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$employees): ?><tr><td colspan="6" class="text-center text-muted py-4">No employees yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
