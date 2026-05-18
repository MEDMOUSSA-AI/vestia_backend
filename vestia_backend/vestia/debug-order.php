<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/helpers/response.php';
require_once __DIR__ . '/helpers/auth.php';

$results = [];

try {
    $db = getDB();
    $results['db_connected']     = true;
    $results['php_version']      = PHP_VERSION;
    $results['SHIPPING_FEE']     = defined('SHIPPING_FEE') ? SHIPPING_FEE : 'NOT DEFINED';

    $stmt = $db->query("SELECT column_name, data_type FROM information_schema.columns WHERE table_name = 'orders' ORDER BY ordinal_position");
    $results['orders_columns'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $db->query("SELECT column_name, data_type FROM information_schema.columns WHERE table_name = 'order_items' ORDER BY ordinal_position");
    $results['order_items_columns'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $db->query("SELECT c.*, p.name, p.price FROM cart_items c JOIN products p ON p.id = c.product_id LIMIT 5");
    $results['cart_sample'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $db->beginTransaction();
    try {
        $s = $db->prepare('INSERT INTO orders (user_id, status, subtotal, shipping_fee, vat, total) VALUES (?, CAST(? AS order_status), ?, ?, ?, ?) RETURNING id');
        $s->execute([1, 'Packing', 100.0, 80.0, 0, 180.0]);
        $oid = $s->fetchColumn();
        $results['test_orders_insert'] = 'SUCCESS id=' . $oid;

        $s2 = $db->prepare('INSERT INTO order_items (order_id, product_id, name, image_url, price, quantity, size) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $s2->execute([$oid, 1, 'Test', 'http://x.com/x.jpg', 100.0, 1, 'M']);
        $results['test_order_items_insert'] = 'SUCCESS';

        $db->rollBack();
    } catch (\Throwable $e) {
        $db->rollBack();
        $results['test_insert_error'] = $e->getMessage();
    }

} catch (\Throwable $e) {
    $results['fatal_error'] = $e->getMessage();
}

echo json_encode(['status' => 'ok', 'results' => $results], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
