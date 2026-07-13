<?php
require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectTo(basePath() . '/index.php');
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if ($username === '' || $password === '') {
    flash('error', 'Please enter both username and password.');
    redirectTo(basePath() . '/index.php');
}

$stmt = $pdo->prepare(
    'SELECT u.UserID, u.FullName, u.Username, u.PasswordHash, u.IsActive, r.RoleName
     FROM Users u JOIN Roles r ON r.RoleID = u.RoleID
     WHERE u.Username = ?'
);
$stmt->execute([$username]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['PasswordHash'])) {
    flash('error', 'Invalid username or password.');
    redirectTo(basePath() . '/index.php');
}

if (!$user['IsActive']) {
    flash('error', 'This account has been deactivated. Contact an administrator.');
    redirectTo(basePath() . '/index.php');
}

session_regenerate_id(true);
$_SESSION['user_id']   = $user['UserID'];
$_SESSION['full_name'] = $user['FullName'];
$_SESSION['role_name'] = $user['RoleName'];

logAudit($pdo, 'LOGIN', 'Users', (int) $user['UserID'], $user['Username'] . ' logged in');

redirectTo(basePath() . '/dashboard.php');
