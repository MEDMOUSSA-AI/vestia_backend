<?php
// ============================================================
// VESTIA API — Saved (Wishlist) Controller  ✅ النسخة المُعدَّلة
// ============================================================
class SavedController {

    public static function index(): void {
        $user = getAuthUser();
        $db   = getDB();

        // ✅ COALESCE بدلاً من IFNULL — صحيح لـ PostgreSQL
        $stmt = $db->prepare(
            "SELECT p.id, p.name, p.price, p.old_price, p.image_url,
                    COALESCE(AVG(r.rating), 0) AS avg_rating
             FROM saved_items s
             JOIN products p ON p.id = s.product_id
             LEFT JOIN reviews r ON r.product_id = p.id
             WHERE s.user_id = ? AND p.is_active = TRUE
             GROUP BY p.id, p.name, p.price, p.old_price, p.image_url
             ORDER BY s.created_at DESC"
        );
        $stmt->execute([$user['id']]);
        $items = $stmt->fetchAll();

        // ✅ إصلاح: استخدام fixImageUrl() المركزية بدلاً من تكرار المنطق
        $items = array_map(function ($item) {
            $item['image_url'] = fixImageUrl($item['image_url']);
            return $item;
        }, $items);

        jsonSuccess(['saved' => $items]);
    }

    // ─────────────────────────────────────────────────────────
    public static function toggle(): void {
        $user      = getAuthUser();
        $body      = getRequestBody();
        $productId = (int)($body['product_id'] ?? 0);

        if (!$productId) jsonError('product_id is required', 422);

        $db = getDB();

        // ✅ is_active = TRUE — أوضح في PostgreSQL
        $check = $db->prepare('SELECT id FROM products WHERE id = ? AND is_active = TRUE');
        $check->execute([$productId]);
        if (!$check->fetch()) jsonError('Product not found', 404);

        $existing = $db->prepare('SELECT id FROM saved_items WHERE user_id = ? AND product_id = ?');
        $existing->execute([$user['id'], $productId]);

        if ($existing->fetch()) {
            $db->prepare('DELETE FROM saved_items WHERE user_id = ? AND product_id = ?')
               ->execute([$user['id'], $productId]);
            jsonSuccess(['saved' => false], 'Removed from saved');
        }

        $db->prepare('INSERT INTO saved_items (user_id, product_id) VALUES (?, ?)')
           ->execute([$user['id'], $productId]);
        jsonSuccess(['saved' => true], 'Added to saved');
    }
}
