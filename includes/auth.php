<?php
/**
 * Session bootstrap + role-based access control helpers.
 *
 * The book frames ERP security around role-based access so each functional
 * silo (front desk, housekeeping, finance, management) only sees the modules
 * relevant to its job while still sharing one integrated database.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/functions.php';

function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']);
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        header('Location: ' . basePath() . '/index.php');
        exit;
    }
}

/** Restrict a page to a set of role names, e.g. requireRole(['Admin','Manager']) */
function requireRole(array $allowedRoles): void
{
    requireLogin();
    if (!in_array($_SESSION['role_name'], $allowedRoles, true)) {
        http_response_code(403);
        require __DIR__ . '/../modules/access-denied.php';
        exit;
    }
}

function currentUser(): array
{
    return [
        'id' => $_SESSION['user_id'] ?? null,
        'name' => $_SESSION['full_name'] ?? null,
        'role' => $_SESSION['role_name'] ?? null,
    ];
}

/** Writes a row to AuditLog so every cross-module action is traceable. */
function logAudit(PDO $pdo, string $action, string $tableName, ?int $recordId = null, string $details = ''): void
{
    $stmt = $pdo->prepare('INSERT INTO AuditLog (UserID, Action, TableName, RecordID, Details, LogTime) VALUES (?, ?, ?, ?, ?, ' . nowFn() . ')');
    $stmt->execute([$_SESSION['user_id'] ?? null, $action, $tableName, $recordId, $details]);
}
