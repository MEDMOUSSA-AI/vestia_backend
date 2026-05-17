<?php
// ============================================================
// VESTIA API — Order Controller  ✅ النسخة المُعدَّلة
// ============================================================
class OrderController {

    public static function index(): void {
        $user   = getAuthUser();
        $db     = getDB();
        $status = $_GET['status'] ?? null;

        // ── بناء شرط الفلترة ──────────────────────────────────
        $where  = ['o.user_id = ?'];
        $params = [$user['id']];

        if ($status === 'ongoing') {
            // ✅ إصلاح: استخدام = ANY() بدلاً من IN مع string مباشر
            $where[]  = "o.status = ANY(?)";
            $params[] = '{Packing,Picked,In Transit}';
        } elseif ($status === 'completed') {
            $where[]  = "o.status = ?";
            $params[] = 'Completed';
        }

        $whereSQL = implode(' AND ', $where);

        // ✅ إصلاح: استبدال N+1 queries بـ JOIN واحد شامل
        $stmt = $db->prepare(
            "SELECT
                o.id        AS order_id,
                o.status,
                o.subtotal,
                o.shipping_fee,
                o.vat,
                o.total,
                o.created_at,

                oi.id         AS item_id,
                oi.name       AS item_name,
                oi.image_url  AS item_image_url,
                oi.price      AS item_price,
                oi.quantity   AS item_quantity,
                oi.size       AS item_size,
                oi.product_id AS item_product_id,

                r.id     AS review_id,
                r.rating AS review_rating

             FROM orders o
             LEFT JOIN order_items oi ON oi.order_id = o.id
             LEFT JOIN reviews r
                    ON r.product_id = oi.product_id
                   AND r.user_id    = ?
                   AND r.order_id   = o.id
             WHERE {$whereSQL}
             ORDER BY o.created_at DESC, oi.id ASC"
        );

        // ✅ user_id مرّة ثانية لشرط JOIN
        $stmt->execute(array_merge([$user['id']], $params));
        $rows = $stmt->fetchAll();

        // ── تجميع النتائج في هيكل منظّم ──────────────────────
        $ordersMap = [];
        foreach ($rows as $row) {
            $oid = $row['order_id'];

            if (!isset($ordersMap[$oid])) {
                $ordersMap[$oid] = [
                    'id'           => (int)$oid,
                    'status'       => $row['status'],
                    'subtotal'     => (float)$row['subtotal'],
                    'shipping_fee' => (float)$row['shipping_fee'],
                    'vat'          => (float)$row['vat'],
                    'total'        => (float)$row['total'],
                    'created_at'   => $row['created_at'],
                    'items'        => [],
                ];
            }

            if ($row['item_id'] !== null) {
                $ordersMap[$oid]['items'][] = [
                    'id'         => (int)$row['item_id'],
                    'name'       => $row['item_name'],
                    'image_url'  => fixImageUrl($row['item_image_url']),
                    'price'      => (float)$row['item_price'],
                    'quantity'   => (int)$row['item_quantity'],
                    'size'       => $row['item_size'],
                    'product_id' => $row['item_product_id'] ? (int)$row['item_product_id'] : null,
                    'reviewed'   => $row['review_id'] !== null,
                    'rating'     => $row['review_rating'] ? (float)$row['review_rating'] : null,
                ];
            }
        }

        jsonSuccess(['orders' => array_values($ordersMap)]);
    }

    // ─────────────────────────────────────────────────────────
    public static function show(string $id): void {
        $user = getAuthUser();
        $db   = getDB();

        $stmt = $db->prepare('SELECT * FROM orders WHERE id = ? AND user_id = ?');
        $stmt->execute([$id, $user['id']]);
        $order = $stmt->fetch();

        if (!$order) jsonError('Order not found', 404);

        $items = $db->prepare('SELECT * FROM order_items WHERE order_id = ?');
        $items->execute([$id]);
        $rawItems = $items->fetchAll();

        foreach ($rawItems as &$item) {
            $item['image_url'] = fixImageUrl($item['image_url']);
        }
        $order['items'] = $rawItems;

        jsonSuccess(['order' => $order]);
    }

    // ─────────────────────────────────────────────────────────
    public static function store(): void {
        $user = getAuthUser();
        $db   = getDB();

        $stmt = $db->prepare(
            "SELECT c.quantity, c.size, p.id AS product_id, p.name, p.price, p.image_url
             FROM cart_items c
             JOIN products p ON p.id = c.product_id
             WHERE c.user_id = ? AND p.is_active = 1"
        );
        $stmt->execute([$user['id']]);
        $cartItems = $stmt->fetchAll();

        if (empty($cartItems)) {
            jsonError('Cart is empty', 422);
        }

        $shippingFee = defined('SHIPPING_FEE') ? (float)SHIPPING_FEE : 80.0;
        $subtotal    = array_sum(array_map(fn($i) => $i['price'] * $i['quantity'], $cartItems));
        $total       = $subtotal + $shippingFee;

        $db->beginTransaction();
        try {
            // ✅ RETURNING id — صحيح مع PostgreSQL
            $insertStmt = $db->prepare(
                'INSERT INTO orders (user_id, status, subtotal, shipping_fee, vat, total)
                 VALUES (?, ?, ?, ?, ?, ?) RETURNING id'
            );
            $insertStmt->execute([
                $user['id'],
                'Packing',
                $subtotal,
                $shippingFee,
                0,
                $total,
            ]);

            // ✅ إصلاح: التحقق من false قبل الـ cast
            $raw = $insertStmt->fetchColumn();
            if (!$raw) {
                throw new \RuntimeException('Failed to retrieve order ID after insert');
            }
            $orderId = (int)$raw;

            $insertItem = $db->prepare(
                'INSERT INTO order_items (order_id, product_id, name, image_url, price, quantity, size)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            foreach ($cartItems as $item) {
                $insertItem->execute([
                    $orderId,
                    $item['product_id'],
                    $item['name'],
                    $item['image_url'],
                    $item['price'],
                    $item['quantity'],
                    $item['size'],
                ]);
            }

            $db->prepare('DELETE FROM cart_items WHERE user_id = ?')->execute([$user['id']]);

            $db->commit();
            jsonSuccess(['order_id' => $orderId], 'Order placed successfully', 201);

        } catch (\Throwable $e) {
            $db->rollBack();
            jsonError('Failed to place order: ' . $e->getMessage(), 500);
        }
    }
}
