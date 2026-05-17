<?php
// ============================================================
// VESTIA API — Database Configuration
// ============================================================
error_reporting(0);
ini_set('display_errors', 0);

// ✅ Render يوفر هذه المتغيرات تلقائياً عند إنشاء قاعدة بيانات PostgreSQL
define('DB_HOST', getenv('DB_HOST') ?: 'dpg-d84grr3tqb8s73fcadn0-a.frankfurt-postgres.render.com');
define('DB_NAME', getenv('DB_NAME') ?: 'my_fashion_db_dpp9');
define('DB_USER', getenv('DB_USER') ?: 'my_fashion_db_dpp9_user');
define('DB_PASS', getenv('DB_PASS') ?: 'BI7j11oBO6Mvw5vFXxzFOtTnBReLcNgn');

// Token expiry in seconds (30 days)
define('TOKEN_EXPIRY', 30 * 24 * 60 * 60);
// Shipping fee
define('SHIPPING_FEE', 80.00);

define('REPLICATE_API_TOKEN', 'r8_xxxxxxxxxxxxxxxx');
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        // ✅ pgsql بدلاً من mysql
        $dsn = 'pgsql:host=' . DB_HOST . ';port=5432;dbname=' . DB_NAME;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            // ✅ تعيين encoding بدلاً من charset في DSN
            $pdo->exec("SET client_encoding TO 'UTF8'");
        } catch (PDOException $e) {
            http_response_code(500);
         die(json_encode(['success' => false, 'message' => $e->getMessage()]));
        }
    }
    return $pdo;
}
