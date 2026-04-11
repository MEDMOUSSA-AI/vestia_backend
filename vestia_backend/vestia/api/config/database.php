<?php
// ============================================================
// VESTIA API — Database Configuration
// ============================================================
error_reporting(0);      // ← أضف هذا
ini_set('display_errors', 0);  //
define('DB_HOST', 'localhost');
define('DB_NAME', 'vestia_db');
define('DB_USER', 'root');       // ← Change to your DB user
define('DB_PASS', '');           // ← Change to your DB password
define('DB_CHARSET', 'utf8mb4');

// Token expiry in seconds (30 days)
define('TOKEN_EXPIRY', 30 * 24 * 60 * 60);

// Shipping fee
define('SHIPPING_FEE', 80.00);

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            http_response_code(500);
            die(json_encode(['success' => false, 'message' => 'Database connection failed.']));
        }
    }
    return $pdo;
}
