<?php
if (isset($_GET['v'])) { echo json_encode(['v' => '3.0', 'file' => __FILE__]); exit; }
// ============================================================
// VESTIA API — Main Router
// ============================================================
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ── CORS ──
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ── Bootstrap ──
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/helpers/response.php';
require_once __DIR__ . '/helpers/auth.php';

// ── Controllers ──
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/ProductController.php';
require_once __DIR__ . '/controllers/CategoryController.php';
require_once __DIR__ . '/controllers/CartController.php';
require_once __DIR__ . '/controllers/SavedController.php';
require_once __DIR__ . '/controllers/OrderController.php';
require_once __DIR__ . '/controllers/ReviewController.php';
require_once __DIR__ . '/controllers/ProfileController.php';
require_once __DIR__ . '/controllers/TryOnController.php';

set_exception_handler(function(\Throwable $e) {
    error_log('💥 Exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'file'    => $e->getFile(),
        'line'    => $e->getLine(),
    ]);
    exit;
});

// ── Route Parsing ──
$method    = $_SERVER['REQUEST_METHOD'];
$uri       = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$scriptDir = dirname($_SERVER['SCRIPT_NAME']);
$path      = '/' . trim(substr($uri, strlen($scriptDir)), '/');
$segments  = array_values(array_filter(explode('/', trim($path, '/'))));
$resource  = $segments[0] ?? '';
$id        = $segments[1] ?? null;
$sub       = $segments[2] ?? null;

// ── DEBUG ENDPOINT ──
if ($resource === 'debug-orders' && $method === 'GET') {
    $results = [];
    try {
        $db = getDB();
        $results['db_connected'] = true;
        $results['php_version']  = PHP_VERSION;
        $results['SHIPPING_FEE'] = defined('SHIPPING_FEE') ? SHIPPING_FEE : 'NOT DEFINED';

        // أعمدة orders
        $s = $db->query("SELECT column_name, data_type FROM information_schema.columns WHERE table_name='orders' ORDER BY ordinal_position");
        $results['orders_columns'] = $s->fetchAll(PDO::FETCH_ASSOC);

        // أعمدة order_items
        $s = $db->query("SELECT column_name, data_type FROM information_schema.columns WHERE table_name='order_items' ORDER BY ordinal_position");
        $results['order_items_columns'] = $s->fetchAll(PDO::FETCH_ASSOC);

        // عينة من cart_items
        $s = $db->query("SELECT c.*, p.name, p.price FROM cart_items c JOIN products p ON p.id = c.product_id LIMIT 5");
        $results['cart_sample'] = $s->fetchAll(PDO::FETCH_ASSOC);

        // اختبار INSERT في orders
        $db->beginTransaction();
        try {
            $s = $db->prepare('INSERT INTO orders (user_id, status, subtotal, shipping_fee, vat, total) VALUES (?, CAST(? AS order_status), ?, ?, ?, ?) RETURNING id');
            $s->execute([1, 'Packing', 100.0, 80.0, 0, 180.0]);
            $oid = $s->fetchColumn();
            $results['test_orders_insert'] = 'SUCCESS id=' . $oid;

            // اختبار INSERT في order_items
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
    exit;
}

// ── Route Table ──
match(true) {
    // AUTH
    $resource === 'register' && $method === 'POST' => AuthController::register(),
    $resource === 'login'    && $method === 'POST' => AuthController::login(),
    $resource === 'logout'   && $method === 'POST' => AuthController::logout(),

    // CATEGORIES
    $resource === 'categories' && $method === 'GET' => CategoryController::index(),

    // PRODUCTS
    $resource === 'products' && $method === 'GET' && $id === null                  => ProductController::index(),
    $resource === 'products' && $method === 'GET' && $id !== null && $sub === null => ProductController::show($id),

    // REVIEWS
    $resource === 'products' && $id !== null && $sub === 'reviews' && $method === 'GET'  => ReviewController::index($id),
    $resource === 'products' && $id !== null && $sub === 'reviews' && $method === 'POST' => ReviewController::store($id),

    // SAVED
    $resource === 'saved' && $method === 'GET'  => SavedController::index(),
    $resource === 'saved' && $method === 'POST' => SavedController::toggle(),

    // CART
    $resource === 'cart' && $method === 'GET'    => CartController::index(),
    $resource === 'cart' && $method === 'POST'   => CartController::add(),
    $resource === 'cart' && $method === 'PUT'    => CartController::update($id),
    $resource === 'cart' && $method === 'DELETE' => CartController::remove($id),

    // ORDERS
    $resource === 'orders' && $method === 'GET'  && $id === null => OrderController::index(),
    $resource === 'orders' && $method === 'GET'  && $id !== null => OrderController::show($id),
    $resource === 'orders' && $method === 'POST'                 => OrderController::store(),

    // PROFILE
    $resource === 'profile' && $method === 'GET' => ProfileController::show(),
    $resource === 'profile' && $method === 'PUT' => ProfileController::update(),

    // VIRTUAL TRY-ON
    $resource === 'virtual-tryon' && $method === 'POST' => TryOnController::generate(),

    // 404
    default => jsonError('Endpoint not found', 404),
};
