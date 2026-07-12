<?php
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['Admin','Manager','FrontDesk']);

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM Reservations WHERE ReservationID = ?');
$stmt->execute([$id]);
$res = $stmt->fetch();

if (!$res || $res['Status'] !== 'Booked') {
    flash('error', 'Only booked reservations can be cancelled.');
    redirectTo('list.php');
}

$pdo->prepare("UPDATE Reservations SET Status='Cancelled' WHERE ReservationID=?")->execute([$id]);
logAudit($pdo, 'CANCEL', 'Reservations', $id, 'Reservation cancelled');
flash('success', "Reservation #$id cancelled.");
redirectTo('list.php');
