<?php
// Fetch database configuration from Render environment variables
$host = getenv('DB_HOST');
$port = getenv('DB_PORT') ?: '19320';
$db   = getenv('DB_NAME');
$user = getenv('DB_USER');
$pass = getenv('DB_PASS');

$dsn = "mysql:host={$host};port={$port};dbname={$db};charset=utf8mb4";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
    PDO::MYSQL_ATTR_SSL_CA       => true,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    $conn = $pdo;
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

if (!function_exists('db')) {
    function db() {
        global $pdo;
        return $pdo;
    }
}
