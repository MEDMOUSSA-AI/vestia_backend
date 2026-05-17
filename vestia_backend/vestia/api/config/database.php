<?php
// ============================================================
// VESTIA API — Database Configuration
// ============================================================
error_reporting(0);
ini_set('display_errors', 0);

define('DB_HOST', getenv('DB_HOST') ?: 'dpg-d84grr3tqb8s73fcadn0-a.frankfurt-postgres.render.com');
define('DB_NAME', getenv('DB_NAME') ?: 'my_fashion_db_dpp9');
define('DB_USER', getenv('DB_USER') ?: 'my_fashion_db_dpp9_user');
define('DB_PASS', getenv('DB_PASS') ?: 'BI7j11oBO6Mvw5vFXxzFOtTnBReLcNgn');

define('TOKEN_EXPIRY', 30 * 24 * 60 * 60);
define('SHIPPING_FEE', 80.00);
define('REPLICATE_API_TOKEN', 'r8_xxxxxxxxxxxxxxxx');

function getDB(): PDO {
    static $pdo = null;

    // ✅ إذا كان الاتصال موجوداً، تحقق أنه لا يزال حياً
    if ($pdo !== null) {
        try {
            $pdo->query('SELECT 1');
        } catch (\Exception $e) {
            error_log('🔄 DB connection lost, reconnecting: ' . $e->getMessage());
            $pdo = null;
        }
    }

    // ✅ أنشئ اتصالاً جديداً إذا لزم الأمر
    if ($pdo === null) {
        $dsn = 'pgsql:host=' . DB_HOST
             . ';port=5432'
             . ';dbname=' . DB_NAME
             . ';connect_timeout=10';

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_PERSISTENT         => false, // ✅ مهم جداً مع Render
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            $pdo->exec("SET client_encoding TO 'UTF8'");
            $pdo->exec("SET statement_timeout = '25000'"); // 25 ثانية
            error_log('✅ DB connected successfully');
        } catch (PDOException $e) {
            error_log('❌ DB connection failed: ' . $e->getMessage());
            http_response_code(500);
            die(json_encode([
                'success' => false,
                'message' => 'Database connection failed'
            ]));
        }
    }

    return $pdo;
}
