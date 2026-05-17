<?php
// ============================================================
// VESTIA API — Cart Controller  ✅ النسخة المُعدَّلة
// ============================================================
class CartController {

    /**
     * جلب بيانات السلّة مع تصحيح روابط الصور والإجماليات
     */
    private static function getCartData($userId): array {
        $db = getDB();

        $stmt = $db->prepare(
            "SELECT c.id, c.quantity, c.size,
                    p.id AS product_id, p.name, p.price, p.old_price, p.image_url
             FROM cart_items c
             JOIN products p ON p.id = c.product_id
             WHERE c.user_id = ? AND p.is_active = 1
             ORDER BY c.created_at DESC"
        );
        $stmt->execute([$userId]);
        $items = $stmt->fetchAll();

        // ✅ تصحيح روابط الصور المحلية إلى روابط كاملة
        $items = array_map(function ($item) {
            $item['image_url'] = fixImageUrl($item['image_url']);
            return $item;
        }, $items);

        $subtotal    = array_sum(array_map(fn($i) => $i['price'] * $i['quantity'], $items));
        $shippingFee = count($items) > 0 ? (defined('SHIPPING_FEE') ? (float)SHIPPING_FEE : 80.0) : 0;

        return [
            'items'        => $items,
            'subtotal'     => round($subtotal, 2),
            'shipping_fee' => $shippingFee,
            'vat'          => 0,
            'total'        => round($subtotal + $shippingFee, 2),
            'item_count'   => array_sum(array_column($items, 'quantity')),
        ];
    }

    // ─────────────────────────────────────────────────────────
    public static function index(): void {
        $user = getAuthUser();
        jsonSuccess(self::getCartData($user['id']));
    }

    // ─────────────────────────────────────────────────────────
    public static function add(): void {
        $user = getAuthUser();
        $body = getRequestBody();

        $productId = (int)($body['product_id'] ?? 0);
        $quantity  = max(1, (int)($body['quantity'] ?? 1));
        $size      = strtoupper(sanitize($body['size'] ?? 'M'));

        if (!$productId) jsonError('product_id is required', 422);

        $db = getDB();

        // ✅ إصلاح: is_active = TRUE أوضح في PostgreSQL
        $check = $db->prepare('SELECT id FROM products WHERE id = ? AND is_active = TRUE');
        $check->execute([$productId]);
        if (!$check->fetch()) jsonError('Product not found', 404);

        // ✅ ON CONFLICT — صحيح مع PostgreSQL + UNIQUE(user_id, product_id, size)
        $db->prepare(
            "INSERT INTO cart_items (user_id, product_id, quantity, size)
             VALUES (?, ?, ?, ?)
             ON CONFLICT (user_id, product_id, size)
             DO UPDATE SET quantity = cart_items.quantity + EXCLUDED.quantity"
        )->execute([$user['id'], $productId, $quantity, $size]);

        jsonSuccess(self::getCartData($user['id']), 'Added to cart', 201);
    }

    // ─────────────────────────────────────────────────────────
    public static function update(?string $id): void {
        $user = getAuthUser();
        if (!$id) jsonError('Cart item ID required', 422);

        $body     = getRequestBody();
        $quantity = (int)($body['quantity'] ?? 0);
        $db       = getDB();

        if ($quantity <= 0) {
            $db->prepare('DELETE FROM cart_items WHERE id = ? AND user_id = ?')
               ->execute([$id, $user['id']]);
        } else {
            $db->prepare('UPDATE cart_items SET quantity = ? WHERE id = ? AND user_id = ?')
               ->execute([$quantity, $id, $user['id']]);
        }

        jsonSuccess(self::getCartData($user['id']), $quantity <= 0 ? 'Item removed' : 'Cart updated');
    }

    // ─────────────────────────────────────────────────────────
    public static function remove(?string $id): void {
        $user = getAuthUser();
        if (!$id) jsonError('Cart item ID required', 422);

        $db = getDB();
        $db->prepare('DELETE FROM cart_items WHERE id = ? AND user_id = ?')
           ->execute([$id, $user['id']]);

        jsonSuccess(self::getCartData($user['id']), 'Item removed from cart');
    }
}
