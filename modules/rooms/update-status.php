<?php
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['Admin','Manager','FrontDesk','Housekeeping']);

$id = (int) ($_POST['id'] ?? 0);
$status = $_POST['status'] ?? '';
$allowed = ['Available','Occupied','Cleaning','Maintenance'];

if ($id && in_array($status, $allowed, true)) {
    $pdo->prepare('UPDATE Rooms SET Status = ? WHERE RoomID = ?')->execute([$status, $id]);
    logAudit($pdo, 'UPDATE', 'Rooms', $id, "Room status changed to $status");
    flash('success', 'Room status updated.');
} else {
    flash('error', 'Invalid room status update.');
}

redirectTo('list.php');