<?php
/**
 * Database connection (Azure SQL Database via PDO sqlsrv driver).
 * Credentials are read from environment variables so they never live in source
 * control. Locally, create a .env file (see .env.example) and it will be loaded
 * by loadEnv() below. On Azure App Service, set these as Application Settings.
 */

function loadEnv(string $path): void
{
    if (!file_exists($path)) {
        return;
    }
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        if ($key !== '' && getenv($key) === false) {
            putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }
}

loadEnv(__DIR__ . '/../.env');

function env(string $key, ?string $default = null): ?string
{
    $value = getenv($key);
    return $value === false ? $default : $value;
}

$dbHost = env('DB_HOST', 'localhost');
$dbName = env('DB_NAME', 'hotel_erp');
$dbUser = env('DB_USER', 'root');
$dbPass = env('DB_PASS', '');
$dbPort = env('DB_PORT', '3306');

try {
    if (extension_loaded('pdo_sqlsrv')) {
        // Azure SQL Database / SQL Server
        $dsn = "sqlsrv:Server=tcp:$dbHost,$dbPort;Database=$dbName;Encrypt=1;TrustServerCertificate=0;";
        $pdo = new PDO($dsn, $dbUser, $dbPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    } else {
        // Fallback to MySQL for local development without the sqlsrv extension
        $dsn = "mysql:host=$dbHost;port=" . env('DB_MYSQL_PORT', '3306') . ";dbname=$dbName;charset=utf8mb4";
        $pdo = new PDO($dsn, $dbUser, $dbPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    die('Database connection failed: ' . htmlspecialchars($e->getMessage()));
}
