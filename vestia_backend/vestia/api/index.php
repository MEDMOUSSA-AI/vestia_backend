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

// ── DEBUG ENDPOINT مؤقت ──
if ($resource === 'debug-orders' && $method === 'GET') {
    $headers    = getallheaders();
    $authHeader = $headers['Authorization']
               ?? $headers['authorization']
               ?? $_SERVER['HTTP_AUTHORIZATION']
               ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
               ?? 'MISSING';

    $dbStatus  = 'unknown';
    $cartCount = 0;
    $userId    = null;

    try {
        $db = getDB();
        $db->query('SELECT 1');
        $dbStatus = 'connected';

        if ($authHeader !== 'MISSING' && str_starts_with($authHeader, 'Bearer ')) {
            $token = trim(substr($authHeader, 7));
            $stmt  = $db->prepare(
                'SELECT u.id FROM auth_tokens t
                 JOIN users u ON u.id = t.user_id
                 WHERE t.token = ? AND t.expires_at > NOW()'
            );
            $stmt->execute([$token]);
            $user = $stmt->fetch();
            if ($user) {
                $userId    = $user['id'];
                $cartStmt  = $db->prepare('SELECT COUNT(*) FROM cart_items WHERE user_id = ?');
                $cartStmt->execute([$userId]);
                $cartCount = (int)$cartStmt->fetchColumn();
            }
        }
    } catch (\Throwable $e) {
        $dbStatus = 'ERROR: ' . $e->getMessage();
    }

    echo json_encode([
        'index_version' => '3.0',
        'auth_header'   => substr($authHeader, 0, 40) . '...',
        'db_status'     => $dbStatus,
        'user_id'       => $userId,
        'cart_items'    => $cartCount,
        'php_version'   => PHP_VERSION,
        'order_exists'  => file_exists(__DIR__ . '/controllers/OrderController.php'),
    ]);
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
