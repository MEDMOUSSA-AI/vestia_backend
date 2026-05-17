<?php
// ============================================================
// VESTIA API — Auth Middleware
// ============================================================
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/response.php';

function getAuthUser(): array {
    // ✅ قراءة التوكن بكل الطرق الممكنة (Apache أحياناً يحذف Authorization)
    $headers    = getallheaders();
    $authHeader = $headers['Authorization']
               ?? $headers['authorization']
               ?? $_SERVER['HTTP_AUTHORIZATION']
               ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
               ?? '';

    error_log('🔑 Auth header: [' . $authHeader . ']');
    error_log('🔑 Headers keys: ' . json_encode(array_keys($headers)));

    if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
        error_log('❌ Missing or invalid Authorization header');
        jsonError('Unauthorized — Missing token', 401);
    }

    $token = trim(substr($authHeader, 7));
    error_log('🔑 Token: ' . substr($token, 0, 10) . '...');

    $db   = getDB();
    $stmt = $db->prepare(
        'SELECT u.id, u.name, u.phone, u.avatar, u.is_active
         FROM auth_tokens t
         JOIN users u ON u.id = t.user_id
         WHERE t.token = ? AND t.expires_at > NOW()'
    );
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if (!$user) {
        error_log('❌ Token not found or expired');
        jsonError('Unauthorized — Invalid or expired token', 401);
    }

    if (!$user['is_active']) {
        error_log('❌ Account suspended: user_id=' . $user['id']);
        jsonError('Account is suspended', 403);
    }

    error_log('✅ Auth OK: user_id=' . $user['id']);
    return $user;
}

function generateToken(): string {
    return bin2hex(random_bytes(32));
}
