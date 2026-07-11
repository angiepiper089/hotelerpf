<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$pageTitle = 'Dashboard';

// --- KPIs (each query pulls from a different module's tables, then the
//     dashboard recombines them — this is the "process integration" view
//     called for in the assignment's presentation slide list). ---
$totalRooms = (int) $pdo->query('SELECT COUNT(*) c FROM Rooms')->fetch()['c'];
$occupiedRooms = (int) $pdo->query("SELECT COUNT(*) c FROM Rooms WHERE Status = 'Occupied'")->fetch()['c'];
$occupancyRate = $totalRooms > 0 ? round($occupiedRooms / $totalRooms * 100) : 0;

$inHouseGuests = (int) $pdo->query("SELECT COUNT(*) c FROM Reservations WHERE Status = 'CheckedIn'")->fetch()['c'];

// SQL Server and MySQL use different date-part functions, so branch on driver.
$driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
if ($driver === 'sqlsrv') {
    $monthRevenue = (float) $pdo->query(
        "SELECT COALESCE(SUM(Amount),0) s FROM Payments WHERE DATEPART(month, PaymentDate) = DATEPART(month, GETDATE()) AND DATEPART(year, PaymentDate) = DATEPART(year, GETDATE())"
    )->fetch()['s'];
} else {
    $monthRevenue = (float) $pdo->query(
        "SELECT COALESCE(SUM(Amount),0) s FROM Payments WHERE MONTH(PaymentDate) = MONTH(NOW()) AND YEAR(PaymentDate) = YEAR(NOW())"
    )->fetch()['s'];
}

$pendingPOs = (int) $pdo->query("SELECT COUNT(*) c FROM PurchaseOrders WHERE Status = 'Pending'")->fetch()['c'];
$lowStockItems = (int) $pdo->query('SELECT COUNT(*) c FROM InventoryItems WHERE QuantityOnHand < ReorderLevel')->fetch()['c'];
$unpaidInvoices = (int) $pdo->query("SELECT COUNT(*) c FROM Invoices WHERE Status IN ('Unpaid','Partial')")->fetch()['c'];

$arrivals = $pdo->query(
    "SELECT r.ReservationID, g.FullName, rm.RoomNumber, r.CheckInDate, r.Status
     FROM Reservations r
     JOIN Guests g ON g.GuestID = r.GuestID
     JOIN Rooms rm ON rm.RoomID = r.RoomID
     WHERE r.Status IN ('Booked','CheckedIn')
     ORDER BY r.CheckInDate ASC"
)->fetchAll();

require __DIR__ . '/includes/header.php';
?>

<div class="row g-3 mb-4">
  <div class="col-6 col-lg-4 col-xl-2">
    <div class="kpi-card">
      <div class="kpi-icon bg-icon-navy"><i class="bi bi-door-open"></i></div>
      <div>
        <div class="kpi-value"><?= $occupancyRate ?>%</div>
        <div class="kpi-label">Occupancy</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-4 col-xl-2">
    <div class="kpi-card">
      <div class="kpi-icon bg-icon-blue"><i class="bi bi-people"></i></div>
      <div>
        <div class="kpi-value"><?= $inHouseGuests ?></div>
        <div class="kpi-label">In-House Guests</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-4 col-xl-2">
    <div class="kpi-card">
      <div class="kpi-icon bg-icon-green"><i class="bi bi-cash-coin"></i></div>
      <div>
        <div class="kpi-value" style="font-size:1.05rem;"><?= formatMoney($monthRevenue) ?></div>
        <div class="kpi-label">Revenue (This Month)</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-4 col-xl-2">
    <div class="kpi-card">
      <div class="kpi-icon bg-icon-gold"><i class="bi bi-truck"></i></div>
      <div>
        <div class="kpi-value"><?= $pendingPOs ?></div>
        <div class="kpi-label">Pending POs</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-4 col-xl-2">
    <div class="kpi-card">
      <div class="kpi-icon bg-icon-red"><i class="bi bi-box-seam"></i></div>
      <div>
        <div class="kpi-value"><?= $lowStockItems ?></div>
        <div class="kpi-label">Low Stock Items</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-lg-4 col-xl-2">
    <div class="kpi-card">
      <div class="kpi-icon bg-icon-red"><i class="bi bi-receipt"></i></div>
      <div>
        <div class="kpi-value"><?= $unpaidInvoices ?></div>
        <div class="kpi-label">Unpaid Invoices</div>
      </div>
    </div>
  </div>
</div>

<div class="section-card">
  <h2><i class="bi bi-diagram-3"></i> How the Modules Integrate</h2>
  <p class="text-muted small">One shared database replaces the departmental "information silos" described in the ERP textbook — a booking made in Reservations automatically updates Room status, feeds Billing, and (via minibar/housekeeping items) draws down Inventory that is replenished through Suppliers.</p>
  <div class="workflow-diagram">
    <span class="workflow-step"><i class="bi bi-calendar-check"></i> Reservation Created</span>
    <span class="workflow-arrow">&rarr;</span>
    <span class="workflow-step"><i class="bi bi-door-open"></i> Room Status Updated</span>
    <span class="workflow-arrow">&rarr;</span>
    <span class="workflow-step"><i class="bi bi-people"></i> Guest Profile (CRM)</span>
    <span class="workflow-arrow">&rarr;</span>
    <span class="workflow-step"><i class="bi bi-box-seam"></i> Inventory Consumed</span>
    <span class="workflow-arrow">&rarr;</span>
    <span class="workflow-step"><i class="bi bi-receipt"></i> Invoice Generated</span>
    <span class="workflow-arrow">&rarr;</span>
    <span class="workflow-step"><i class="bi bi-cash-coin"></i> Payment Recorded</span>
  </div>
  <div class="workflow-diagram mt-2">
    <span class="workflow-step" style="background:var(--brand-gold-dark)"><i class="bi bi-truck"></i> Low Stock Detected</span>
    <span class="workflow-arrow">&rarr;</span>
    <span class="workflow-step" style="background:var(--brand-gold-dark)"><i class="bi bi-file-earmark-text"></i> Purchase Order</span>
    <span class="workflow-arrow">&rarr;</span>
    <span class="workflow-step" style="background:var(--brand-gold-dark)"><i class="bi bi-building"></i> Supplier</span>
    <span class="workflow-arrow">&rarr;</span>
    <span class="workflow-step" style="background:var(--brand-gold-dark)"><i class="bi bi-box-seam"></i> Stock Received</span>
  </div>
</div>

<div class="section-card">
  <h2><i class="bi bi-calendar3"></i> Upcoming &amp; Current Reservations</h2>
  <div class="table-responsive">
    <table class="table erp-table align-middle">
      <thead><tr><th>#</th><th>Guest</th><th>Room</th><th>Check-in</th><th>Status</th></tr></thead>
      <tbody>
        <?php foreach ($arrivals as $a): ?>
          <tr>
            <td>#<?= (int) $a['ReservationID'] ?></td>
            <td><?= e($a['FullName']) ?></td>
            <td><?= e($a['RoomNumber']) ?></td>
            <td><?= formatDate($a['CheckInDate']) ?></td>
            <td><span class="status-pill status-<?= e($a['Status']) ?>"><?= e($a['Status']) ?></span></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$arrivals): ?>
          <tr><td colspan="5" class="text-center text-muted py-3">No upcoming reservations.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>