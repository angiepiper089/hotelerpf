<?php
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['Admin','Manager','FrontDesk','Housekeeping']);

$pageTitle = 'Room Management';
$role = $_SESSION['role_name'];
$canManage = in_array($role, ['Admin','Manager'], true);

$rooms = $pdo->query(
    "SELECT rm.RoomID, rm.RoomNumber, rm.Floor, rm.Status, rm.Notes, rt.TypeName, rt.BaseRate, rt.MaxOccupancy
     FROM Rooms rm JOIN RoomTypes rt ON rt.RoomTypeID = rm.RoomTypeID
     ORDER BY rm.Floor, rm.RoomNumber"
)->fetchAll();

$statusCounts = ['Available'=>0,'Occupied'=>0,'Cleaning'=>0,'Maintenance'=>0];
foreach ($rooms as $r) { $statusCounts[$r['Status']] = ($statusCounts[$r['Status']] ?? 0) + 1; }

require __DIR__ . '/../../includes/header.php';
?>

<div class="row g-3 mb-4">
  <div class="col-6 col-lg-3"><div class="kpi-card"><div class="kpi-icon bg-icon-green"><i class="bi bi-door-open"></i></div><div><div class="kpi-value"><?= $statusCounts['Available'] ?></div><div class="kpi-label">Available</div></div></div></div>
  <div class="col-6 col-lg-3"><div class="kpi-card"><div class="kpi-icon bg-icon-gold"><i class="bi bi-person-fill"></i></div><div><div class="kpi-value"><?= $statusCounts['Occupied'] ?></div><div class="kpi-label">Occupied</div></div></div></div>
  <div class="col-6 col-lg-3"><div class="kpi-card"><div class="kpi-icon bg-icon-blue"><i class="bi bi-brush"></i></div><div><div class="kpi-value"><?= $statusCounts['Cleaning'] ?></div><div class="kpi-label">Needs Cleaning</div></div></div></div>
  <div class="col-6 col-lg-3"><div class="kpi-card"><div class="kpi-icon bg-icon-red"><i class="bi bi-tools"></i></div><div><div class="kpi-value"><?= $statusCounts['Maintenance'] ?></div><div class="kpi-label">Maintenance</div></div></div></div>
</div>

<div class="section-card">
  <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <h2 class="mb-0"><i class="bi bi-door-open"></i> Rooms</h2>
    <?php if ($canManage): ?>
      <div class="d-flex gap-2">
        <a href="types.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-tags"></i> Room Types</a>
        <a href="add.php" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg"></i> Add Room</a>
      </div>
    <?php endif; ?>
  </div>

  <div class="table-responsive">
    <table class="table erp-table align-middle">
      <thead>
        <tr><th>Room</th><th>Floor</th><th>Type</th><th>Rate/Night</th><th>Max Occ.</th><th>Status</th><th class="text-end">Actions</th></tr>
      </thead>
      <tbody>
        <?php foreach ($rooms as $r): ?>
        <tr>
          <td><strong><?= e($r['RoomNumber']) ?></strong></td>
          <td><?= (int) $r['Floor'] ?></td>
          <td><?= e($r['TypeName']) ?></td>
          <td><?= formatMoney($r['BaseRate']) ?></td>
          <td><?= (int) $r['MaxOccupancy'] ?></td>
          <td><span class="status-pill status-<?= e($r['Status']) ?>"><?= e($r['Status']) ?></span></td>
          <td class="text-end">
            <form method="post" action="update-status.php" class="d-inline-flex gap-1">
              <input type="hidden" name="id" value="<?= (int) $r['RoomID'] ?>">
              <select name="status" class="form-select form-select-sm" style="width:auto;display:inline-block;">
                <?php foreach (['Available','Occupied','Cleaning','Maintenance'] as $s): ?>
                  <option value="<?= $s ?>" <?= $r['Status']===$s?'selected':'' ?>><?= $s ?></option>
                <?php endforeach; ?>
              </select>
              <button class="btn btn-sm btn-outline-secondary" type="submit"><i class="bi bi-arrow-repeat"></i></button>
            </form>
            <?php if ($canManage): ?>
              <a href="edit.php?id=<?= (int) $r['RoomID'] ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>