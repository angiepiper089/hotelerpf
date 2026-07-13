<?php
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    logAudit($pdo, 'LOGOUT', 'Users', (int) $_SESSION['user_id'], $_SESSION['full_name'] . ' logged out');
}

$_SESSION = [];
session_destroy();

header('Location: ' . basePath() . '/index.php');
exit;
