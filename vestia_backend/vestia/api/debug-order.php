<?php
// ملف مؤقت للتشخيص — ضعه في مجلد api/ ثم احذفه بعد حل المشكلة
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/helpers/response.php';
require_once __DIR__ . '/helpers/auth.php';

try {
    $db      = getDB();
    $results = [];

    $results['db_connected'] = true;
    $results['SHIPPING_FEE_defined'] = defined('SHIPPING_FEE');
    $results['SHIPPING_FEE_value']   = defined('SHIPPING_FEE') ? SHIPPING_FEE : 'NOT DEFINED';

    $stmt = $db->query("SELECT column_name, data_type FROM information_schema.columns WHERE table_name = 'orders' ORDER BY ordinal_position");
    $results['orders_columns'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $db->query("SELECT column_name, data_type FROM information_schema.columns WHERE table_name = 'order_items' ORDER BY ordinal_position");
    $results['order_items_columns'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $db->beginTransaction();
    try {
        $testStmt = $db->prepare('INSERT INTO orders (user_id, status, subtotal, shipping_fee, vat, total) VALUES (?, ?, ?, ?, ?, ?) RETURNING id');
        $testStmt->execute([1, 'Packing', 100.0, 80.0, 0, 180.0]);
        $testId = $testStmt->fetchColumn();
        $results['test_insert_success']  = true;
        $results['test_insert_order_id'] = $testId;
        $db->rollBack();
    } catch (\Throwable $e) {
        $db->rollBack();
        $results['test_insert_success'] = false;
        $results['test_insert_error']   = $e->getMessage();
    }

    echo json_encode(['status' => 'ok', 'results' => $results], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (\Throwable $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine()], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
