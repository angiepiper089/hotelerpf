<?php
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['Admin','Manager','FrontDesk']);

const TAX_RATE = 0.10;

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$stmt = $pdo->prepare(
    "SELECT r.*, g.FullName AS GuestName, rm.RoomNumber, rt.TypeName, rt.BaseRate
     FROM Reservations r
     JOIN Guests g ON g.GuestID = r.GuestID
     JOIN Rooms rm ON rm.RoomID = r.RoomID
     JOIN RoomTypes rt ON rt.RoomTypeID = rm.RoomTypeID
     WHERE r.ReservationID = ?"
);
$stmt->execute([$id]);
$res = $stmt->fetch();

if (!$res || $res['Status'] !== 'CheckedIn') {
    flash('error', 'Only checked-in reservations can be checked out and billed.');
    redirectTo('list.php');
}

$nights = max(1, (int) ((strtotime($res['CheckOutDate']) - strtotime($res['CheckInDate'])) / 86400));
$roomCharges = $nights * (float) $res['BaseRate'];

$items = $pdo->query('SELECT ItemID, ItemName, UnitCost, QuantityOnHand FROM InventoryItems ORDER BY ItemName')->fetchAll();

$pageTitle = 'Check-out & Billing';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $qtyByItem = $_POST['qty'] ?? [];
    $serviceCharges = 0.0;
    $lineItems = [];

    foreach ($qtyByItem as $itemId => $qty) {
        $qty = (int) $qty;
        if ($qty <= 0) continue;
        foreach ($items as $it) {
            if ((int) $it['ItemID'] === (int) $itemId) {
                if ($qty > (int) $it['QuantityOnHand']) {
                    $errors[] = "Not enough stock of {$it['ItemName']} (available: {$it['QuantityOnHand']}).";
                    break;
                }
                $amount = $qty * (float) $it['UnitCost'];
                $serviceCharges += $amount;
                $lineItems[] = ['ItemID' => $it['ItemID'], 'Description' => $it['ItemName'], 'Quantity' => $qty, 'Amount' => $amount];
                break;
            }
        }
    }

    if (!$errors) {
        $taxAmount = round(($roomCharges + $serviceCharges) * TAX_RATE, 2);
        $totalAmount = round($roomCharges + $serviceCharges + $taxAmount, 2);

        $pdo->beginTransaction();
        try {
            // 1. Reservations module: close out the stay
            $pdo->prepare("UPDATE Reservations SET Status='CheckedOut', ActualCheckOut=? WHERE ReservationID=?")
                ->execute([date('Y-m-d H:i:s'), $id]);

            // 2. Room Management module: room now needs housekeeping before resale
            $pdo->prepare("UPDATE Rooms SET Status='Cleaning' WHERE RoomID=?")->execute([$res['RoomID']]);

            // 3. Billing module: generate the invoice
            $pdo->prepare(
                'INSERT INTO Invoices (ReservationID, GuestID, RoomCharges, ServiceCharges, TaxAmount, TotalAmount, Status)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            )->execute([$id, $res['GuestID'], $roomCharges, $serviceCharges, $taxAmount, $totalAmount, 'Unpaid']);
            $invoiceId = (int) $pdo->lastInsertId();

            $roomLine = $pdo->prepare('INSERT INTO InvoiceItems (InvoiceID, ItemID, Description, Quantity, Amount) VALUES (?, NULL, ?, ?, ?)');
            $roomLine->execute([$invoiceId, "Room charges ($nights night(s) x {$res['TypeName']} rate)", $nights, $roomCharges]);

            $lineStmt = $pdo->prepare('INSERT INTO InvoiceItems (InvoiceID, ItemID, Description, Quantity, Amount) VALUES (?, ?, ?, ?, ?)');
            $stockStmt = $pdo->prepare('UPDATE InventoryItems SET QuantityOnHand = QuantityOnHand - ? WHERE ItemID = ?');
            foreach ($lineItems as $li) {
                $lineStmt->execute([$invoiceId, $li['ItemID'], $li['Description'], $li['Quantity'], $li['Amount']]);
                // 4. Inventory / Supply Chain module: minibar & amenity usage draws down stock
                $stockStmt->execute([$li['Quantity'], $li['ItemID']]);
            }

            logAudit($pdo, 'CHECK_OUT', 'Reservations', $id, "Checked out, generated Invoice #$invoiceId, room set to Cleaning");
            $pdo->commit();

            flash('success', "Guest checked out. Invoice #$invoiceId generated.");
            redirectTo('../billing/invoice.php?id=' . $invoiceId);
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Checkout failed: ' . $e->getMessage();
        }
    }
}

require __DIR__ . '/../../includes/header.php';
?>

<div class="section-card" style="max-width:760px;">
  <h2><i class="bi bi-box-arrow-right"></i> Check-out &amp; Generate Invoice</h2>
  <p class="text-muted">Reservation #<?= $id ?> &middot; <?= e($res['GuestName']) ?> &middot; Room <?= e($res['RoomNumber']) ?> (<?= e($res['TypeName']) ?>)</p>

  <?php foreach ($errors as $err): ?>
    <div class="alert alert-danger py-2"><?= e($err) ?></div>
  <?php endforeach; ?>

  <div class="alert alert-secondary py-2">
    <?= $nights ?> night(s) &times; <?= formatMoney($res['BaseRate']) ?> = <strong><?= formatMoney($roomCharges) ?></strong> room charges
  </div>

  <form method="post">
    <input type="hidden" name="id" value="<?= $id ?>">
    <h6 class="mt-3">Extra charges (minibar / amenities used, pulled from Inventory)</h6>
    <div class="table-responsive">
      <table class="table table-sm align-middle">
        <thead><tr><th>Item</th><th>Unit Cost</th><th>In Stock</th><th style="width:120px;">Qty Used</th></tr></thead>
        <tbody>
          <?php foreach ($items as $it): ?>
          <tr>
            <td><?= e($it['ItemName']) ?></td>
            <td><?= formatMoney($it['UnitCost']) ?></td>
            <td><?= (int) $it['QuantityOnHand'] ?></td>
            <td><input type="number" min="0" max="<?= (int) $it['QuantityOnHand'] ?>" name="qty[<?= (int) $it['ItemID'] ?>]" class="form-control form-control-sm" value="0"></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <p class="text-muted small">A <?= TAX_RATE*100 ?>% service tax is applied automatically to the invoice total.</p>
    <div class="d-flex gap-2">
      <button type="submit" class="btn btn-primary"><i class="bi bi-receipt"></i> Check-out &amp; Generate Invoice</button>
      <a href="list.php" class="btn btn-outline-secondary">Cancel</a>
    </div>
  </form>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
