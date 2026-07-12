<?php
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['Admin','Manager','FrontDesk']);

$pageTitle = 'Guests (CRM)';
$search = trim($_GET['q'] ?? '');

$sql = "SELECT g.*,
        (SELECT COUNT(*) FROM Reservations r WHERE r.GuestID = g.GuestID) AS TotalStays,
        (SELECT COALESCE(SUM(i.TotalAmount),0) FROM Invoices i WHERE i.GuestID = g.GuestID) AS TotalSpend
        FROM Guests g";
$params = [];
if ($search !== '') {
    $sql .= " WHERE g.FullName LIKE ? OR g.Email LIKE ? OR g.Phone LIKE ?";
    $like = "%$search%";
    $params = [$like, $like, $like];
}
$sql .= " ORDER BY g.FullName";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$guests = $stmt->fetchAll();

require __DIR__ . '/../../includes/header.php';
?>

<div class="section-card">
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <form method="get" class="d-flex gap-2">
      <input type="text" name="q" class="form-control form-control-sm" placeholder="Search guests..." value="<?= e($search) ?>" style="width:220px;">
      <button class="btn btn-sm btn-outline-secondary" type="submit"><i class="bi bi-search"></i></button>
    </form>
    <a href="add.php" class="btn btn-primary btn-sm"><i class="bi bi-person-plus"></i> Add Guest</a>
  </div>

  <p class="text-muted small">This module implements the CRM concept of a unified guest profile — loyalty tier, stay history and lifetime spend are visible in one place instead of scattered front-desk notes.</p>

  <div class="table-responsive">
    <table class="table erp-table align-middle">
      <thead><tr><th>Name</th><th>Contact</th><th>Loyalty Tier</th><th>Total Stays</th><th>Lifetime Spend</th><th class="text-end">Actions</th></tr></thead>
      <tbody>
        <?php foreach ($guests as $g): ?>
        <tr>
          <td><a href="profile.php?id=<?= (int) $g['GuestID'] ?>"><?= e($g['FullName']) ?></a></td>
          <td class="small text-muted"><?= e($g['Email']) ?><br><?= e($g['Phone']) ?></td>
          <td><span class="badge text-bg-light border"><?= e($g['LoyaltyTier']) ?></span></td>
          <td><?= (int) $g['TotalStays'] ?></td>
          <td><?= formatMoney($g['TotalSpend']) ?></td>
          <td class="text-end">
            <a href="profile.php?id=<?= (int) $g['GuestID'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
            <a href="edit.php?id=<?= (int) $g['GuestID'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$guests): ?><tr><td colspan="6" class="text-center text-muted py-4">No guests found.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
