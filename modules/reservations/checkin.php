<?php
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['Admin','Manager','FrontDesk']);

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM Reservations WHERE ReservationID = ?');
$stmt->execute([$id]);
$res = $stmt->fetch();

if (!$res || $res['Status'] !== 'Booked') {
    flash('error', 'Reservation cannot be checked in.');
    redirectTo('list.php');
}

$pdo->beginTransaction();
try {
    $now = ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlsrv') ? date('Y-m-d H:i:s') : date('Y-m-d H:i:s');
    $pdo->prepare("UPDATE Reservations SET Status='CheckedIn', ActualCheckIn=? WHERE ReservationID=?")
        ->execute([$now, $id]);
    // Cross-module effect: check-in flips the Room Management module's status.
    $pdo->prepare("UPDATE Rooms SET Status='Occupied' WHERE RoomID=?")->execute([$res['RoomID']]);
    logAudit($pdo, 'CHECK_IN', 'Reservations', $id, "Guest checked in, room #{$res['RoomID']} set to Occupied");
    $pdo->commit();
    flash('success', "Reservation #$id checked in. Room status updated to Occupied.");
} catch (Exception $e) {
    $pdo->rollBack();
    flash('error', 'Check-in failed: ' . $e->getMessage());
}

redirectTo('list.php');
