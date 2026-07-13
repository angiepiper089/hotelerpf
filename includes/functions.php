<?php
/** Small shared helpers used across every module. */

function basePath(): string
{
    // Allows the app to run whether it's deployed at the domain root
    // (Azure App Service) or in a subfolder (local XAMPP/IIS testing).
    static $base = null;
    if ($base === null) {
        $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
        // Strip one level when called from inside /modules/<name>/
        if (preg_match('#/modules/[^/]+$#', $base)) {
            $base = preg_replace('#/modules/[^/]+$#', '', $base);
        }
    }
    return $base;
}

function asset(string $path): string
{
    return basePath() . '/assets/' . ltrim($path, '/');
}

function moduleUrl(string $path): string
{
    return basePath() . '/modules/' . ltrim($path, '/');
}

/** SQL Server uses GETDATE(); the MySQL fallback uses NOW(). Chosen once per request. */
function nowFn(): string
{
    global $pdo;
    static $driver = null;
    if ($driver === null) {
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    }
    return $driver === 'sqlsrv' ? 'GETDATE()' : 'NOW()';
}

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function formatMoney(?float $amount): string
{
    return 'LKR ' . number_format((float) $amount, 2);
}

function formatDate(?string $date): string
{
    if (!$date) {
        return '-';
    }
    return date('d M Y', strtotime($date));
}

function flash(string $key, ?string $message = null)
{
    if ($message !== null) {
        $_SESSION['flash'][$key] = $message;
        return null;
    }
    $msg = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $msg;
}

function redirectTo(string $url): void
{
    header('Location: ' . $url);
    exit;
}
