<?php
require_once __DIR__ . '/../../includes/auth.php';
requireRole(['Admin']);

$id = (int) ($_GET['id'] ?? 0);
if ($id === (int) $_SESSION['user_id']) {
    flash('error', 'You cannot deactivate your own account.');
    redirectTo('list.php');
}

$stmt = $pdo->prepare('SELECT * FROM Users WHERE UserID = ?');
$stmt->execute([$id]);
$user = $stmt->fetch();
if (!$user) { flash('error', 'User not found.'); redirectTo('list.php'); }

$newStatus = $user['IsActive'] ? 0 : 1;
$pdo->prepare('UPDATE Users SET IsActive = ? WHERE UserID = ?')->execute([$newStatus, $id]);
logAudit($pdo, 'UPDATE', 'Users', $id, "User {$user['Username']} " . ($newStatus ? 'activated' : 'deactivated'));
flash('success', 'User account updated.');
redirectTo('list.php');
