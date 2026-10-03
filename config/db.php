<?php
// ============================================================
// TechSense — Database Configuration
// ============================================================
define('DB_HOST', 'localhost');
define('DB_NAME', 'techsense');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHAR', 'utf8mb4');

define('BASE_URL', 'http://localhost/techsense');
define('SITE_NAME', 'TechSense');
define('SITE_VERSION', '1.0.0');

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHAR;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    http_response_code(500);
    die(json_encode(['error' => 'Database connection failed. Please check your configuration.']));
}
