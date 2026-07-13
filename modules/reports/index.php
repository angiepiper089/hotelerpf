<?php
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['Admin','Manager','Finance']);

$pageTitle = 'Reports';

$revenueByType = $pdo->query(
    "SELECT rt.TypeName, COUNT(inv.InvoiceID) AS InvoiceCount, COALESCE(SUM(inv.TotalAmount),0) AS Revenue
     FROM Invoices inv
     JOIN Reservations r ON r.ReservationID = inv.ReservationID
     JOIN Rooms rm ON rm.RoomID = r.RoomID
     JOIN RoomTypes rt ON rt.RoomTypeID = rm.RoomTypeID
     GROUP BY rt.TypeName
     ORDER BY Revenue DESC"
)->fetchAll();

$topGuests = $pdo->query(
    "SELECT g.FullName, g.LoyaltyTier, COUNT(inv.InvoiceID) AS Stays, COALESCE(SUM(inv.TotalAmount),0) AS Spend
     FROM Guests g JOIN Invoices inv ON inv.GuestID = g.GuestID
     GROUP BY g.FullName, g.LoyaltyTier
     ORDER BY Spend DESC"
)->fetchAll();

$roomStatusBreakdown = $pdo->query('SELECT Status, COUNT(*) AS Cnt FROM Rooms GROUP BY Status')->fetchAll();

$lowStock = $pdo->query('SELECT ItemName, QuantityOnHand, ReorderLevel FROM InventoryItems WHERE QuantityOnHand < ReorderLevel ORDER BY (ReorderLevel - QuantityOnHand) DESC')->fetchAll();

$maxRevenue = max(array_column($revenueByType, 'Revenue') ?: [1]);

require __DIR__ . '/../../includes/header.php';
?>

<div class="section-card">
  <h2><i class="bi bi-graph-up"></i> Revenue by Room Type</h2>
  <?php foreach ($revenueByType as $row): $pct = $maxRevenue > 0 ? round($row['Revenue'] / $maxRevenue * 100) : 0; ?>
    <div class="mb-2">
      <div class="d-flex justify-content-between small mb-1">
        <span><?= e($row['TypeName']) ?> (<?= (int) $row['InvoiceCount'] ?> invoices)</span>
        <span><?= formatMoney($row['Revenue']) ?></span>
      </div>
      <div class="progress" style="height:10px;">
        <div class="progress-bar" style="width: <?= $pct ?>%; background:var(--brand-navy);"></div>
      </div>
    </div>
  <?php endforeach; ?>
  <?php if (!$revenueByType): ?><p class="text-muted">No billing data yet.</p><?php endif; ?>
</div>

<div class="row g-3">
  <div class="col-lg-6">
    <div class="section-card h-100">
      <h2><i class="bi bi-door-open"></i> Room Status Breakdown</h2>
      <table class="table table-sm">
        <?php foreach ($roomStatusBreakdown as $row): ?>
          <tr><td><span class="status-pill status-<?= e($row['Status']) ?>"><?= e($row['Status']) ?></span></td><td class="text-end"><?= (int) $row['Cnt'] ?> rooms</td></tr>
        <?php endforeach; ?>
      </table>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="section-card h-100">
      <h2><i class="bi bi-exclamation-triangle"></i> Items Below Reorder Level</h2>
      <?php if ($lowStock): ?>
      <table class="table table-sm">
        <thead><tr><th>Item</th><th>On Hand</th><th>Reorder Level</th></tr></thead>
        <tbody>
        <?php foreach ($lowStock as $row): ?>
          <tr><td><?= e($row['ItemName']) ?></td><td><?= (int) $row['QuantityOnHand'] ?></td><td><?= (int) $row['ReorderLevel'] ?></td></tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <a href="../inventory/po-create.php" class="btn btn-sm btn-outline-primary"><i class="bi bi-file-earmark-plus"></i> Raise Purchase Order</a>
      <?php else: ?>
        <p class="text-muted">All items are above their reorder level.</p>
      <?php endif; ?>
    </div>
  </div>
</div>

<div class="section-card">
  <h2><i class="bi bi-people"></i> Top Guests by Spend (CRM Analysis)</h2>
  <div class="table-responsive">
    <table class="table erp-table align-middle">
      <thead><tr><th>Guest</th><th>Loyalty Tier</th><th>Stays</th><th>Total Spend</th></tr></thead>
      <tbody>
        <?php foreach ($topGuests as $row): ?>
          <tr>
            <td><?= e($row['FullName']) ?></td>
            <td><span class="badge text-bg-light border"><?= e($row['LoyaltyTier']) ?></span></td>
            <td><?= (int) $row['Stays'] ?></td>
            <td><?= formatMoney($row['Spend']) ?></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$topGuests): ?><tr><td colspan="4" class="text-center text-muted py-3">No data yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
