<?php
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['Admin','Manager','Finance']);

$id = (int) ($_GET['id'] ?? 0);
$action = $_GET['action'] ?? '';

$stmt = $pdo->prepare('SELECT * FROM PurchaseOrders WHERE PurchaseOrderID = ?');
$stmt->execute([$id]);
$po = $stmt->fetch();
if (!$po) { flash('error', 'Purchase order not found.'); redirectTo('suppliers.php'); }

if ($action === 'approve' && $po['Status'] === 'Pending') {
    $pdo->prepare("UPDATE PurchaseOrders SET Status='Approved' WHERE PurchaseOrderID=?")->execute([$id]);
    logAudit($pdo, 'APPROVE', 'PurchaseOrders', $id, "PO #$id approved");
    flash('success', "Purchase Order #$id approved.");

} elseif ($action === 'cancel' && $po['Status'] === 'Pending') {
    $pdo->prepare("UPDATE PurchaseOrders SET Status='Cancelled' WHERE PurchaseOrderID=?")->execute([$id]);
    logAudit($pdo, 'CANCEL', 'PurchaseOrders', $id, "PO #$id cancelled");
    flash('success', "Purchase Order #$id cancelled.");

} elseif ($action === 'receive' && $po['Status'] === 'Approved') {
    $pdo->beginTransaction();
    try {
        $items = $pdo->prepare('SELECT ItemID, Quantity FROM PurchaseOrderItems WHERE PurchaseOrderID = ?');
        $items->execute([$id]);
        $restock = $pdo->prepare('UPDATE InventoryItems SET QuantityOnHand = QuantityOnHand + ? WHERE ItemID = ?');
        foreach ($items->fetchAll() as $li) {
            // Supply Chain -> Inventory integration: receiving a PO restocks the shared inventory table.
            $restock->execute([$li['Quantity'], $li['ItemID']]);
        }
        $pdo->prepare("UPDATE PurchaseOrders SET Status='Received' WHERE PurchaseOrderID=?")->execute([$id]);
        logAudit($pdo, 'RECEIVE', 'PurchaseOrders', $id, "PO #$id received, inventory restocked");
        $pdo->commit();
        flash('success', "Purchase Order #$id received. Inventory stock updated.");
    } catch (Exception $e) {
        $pdo->rollBack();
        flash('error', 'Failed to receive purchase order: ' . $e->getMessage());
    }
} else {
    flash('error', 'Invalid action for the current purchase order status.');
}

redirectTo('po-view.php?id=' . $id);
