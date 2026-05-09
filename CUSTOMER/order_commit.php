<?php
declare(strict_types=1);

require_once __DIR__ . '/promotion_helpers.php';
require_once __DIR__ . '/admin/settings/_settings_db.php';

/**
 * Serialize order-id allocation (pair with bejewelry_unlock_order_sequence in finally).
 */
function bejewelry_lock_order_sequence(PDO $pdo): void
{
    $lock = $pdo->query("SELECT GET_LOCK('bejewelry_order_seq', 15)");
    $ok = $lock ? (int) $lock->fetchColumn() : 0;
    if ($ok !== 1) {
        throw new RuntimeException('Could not allocate order number. Please try again.');
    }
}

function bejewelry_unlock_order_sequence(PDO $pdo): void
{
    $pdo->query("SELECT RELEASE_LOCK('bejewelry_order_seq')");
}

/**
 * Next id BJ-{YEAR}-{NNNN} — global max for that year (not per user).
 * Call only while bejewelry_lock_order_sequence() is held, before INSERT commits.
 */
function bejewelry_next_order_id(PDO $pdo): string
{
    $year = date('Y');
    $prefix = 'BJ-' . $year . '-';
    $stmt = $pdo->prepare(
        'SELECT COALESCE(MAX(CAST(SUBSTRING(id, 9) AS UNSIGNED)), 0) FROM orders WHERE id LIKE ?'
    );
    $stmt->execute([$prefix . '%']);
    $max = (int) $stmt->fetchColumn();
    $next = $max + 1;
    if ($next > 9999) {
        throw new RuntimeException('Order number limit reached for this year.');
    }

    return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
}

/**
 * Create order from current cart (shared by place_order.php and paymongo_return.php).
 *
 * @param array $post Keys: ship_name, ship_street, ship_city, ship_province, ship_zip, ship_phone,
 *                    payment_method, notes (shipping_fee from POST is ignored — recalculated server-side)
 */
function bejewelry_commit_order(PDO $pdo, int $userId, array $post): string
{
    $cart = get_customer_cart();
    if (empty($cart)) {
        throw new RuntimeException('Cart is empty.');
    }

    $shipName = trim((string) ($post['ship_name'] ?? ''));
    $shipStreet = trim((string) ($post['ship_street'] ?? ''));
    $shipCity = trim((string) ($post['ship_city'] ?? ''));
    $shipProvince = trim((string) ($post['ship_province'] ?? ''));
    $shipZip = trim((string) ($post['ship_zip'] ?? ''));
    $shipPhone = trim((string) ($post['ship_phone'] ?? ''));
    $paymentMethod = trim((string) ($post['payment_method'] ?? 'ewallet'));
    $notes = trim((string) ($post['notes'] ?? ''));
    $promotionId = (int) ($post['promotion_id'] ?? 0);

    if ($shipName === '' || $shipStreet === '' || $shipCity === '' || $shipPhone === '') {
        throw new RuntimeException('Incomplete delivery information.');
    }

    $subtotal = 0.0;
    $lineItems = [];
    foreach ($cart as $item) {
        $pid = (int) ($item['product_id'] ?? $item['id'] ?? 0);
        $qty = max(1, (int) ($item['qty'] ?? 1));
        $size = (string) ($item['size'] ?? 'One Size');

        $pStmt = $pdo->prepare('SELECT p.*, c.name AS cat FROM products p LEFT JOIN categories c ON c.id=p.category_id WHERE p.id=? AND p.is_active=1');
        $pStmt->execute([$pid]);
        $prod = $pStmt->fetch();
        if (!$prod) {
            throw new RuntimeException('A product in your cart is no longer available.');
        }

        $avail = (int) ($prod['stock'] ?? 0);
        if ($avail < $qty) {
            throw new RuntimeException(
                'Not enough stock for "' . ($prod['name'] ?? 'item') . '" (only ' . $avail . ' available).'
            );
        }

        $subtotal += (float) $prod['price'] * $qty;
        $lineItems[] = ['product' => $prod, 'qty' => $qty, 'size' => $size];
    }

    $sPdo = settingsPdo();
    $dbShipping = settingsGetJson($sPdo, 'shipping', ['shipping_fee' => SHIPPING_FEE, 'free_ship_threshold' => FREE_SHIP_THRESHOLD]);
    $freeShipThreshold = (float) ($dbShipping['free_ship_threshold'] ?? FREE_SHIP_THRESHOLD);
    $shippingFee = ($subtotal >= $freeShipThreshold) ? 0.0 : (float) ($dbShipping['shipping_fee'] ?? SHIPPING_FEE);
    if ($shippingFee < 0) {
        $shippingFee = 0.0;
    }

    $promotion = null;
    $discount = 0.0;
    if ($promotionId > 0) {
        $promotion = bejewelry_find_promotion_by_id($pdo, $promotionId);
        if (!$promotion) {
            throw new RuntimeException('Selected promotion is no longer available.');
        }
        $discount = bejewelry_calculate_promotion_discount($promotion, $subtotal, $shippingFee);
        if ($discount <= 0.0) {
            throw new RuntimeException('Selected promotion is not valid for this order total.');
        }
    }

    $total = max(0.0, $subtotal - $discount + $shippingFee);

    $orderId = '';
    $pdo->beginTransaction();
    try {
        bejewelry_lock_order_sequence($pdo);
        try {
            $orderId = bejewelry_next_order_id($pdo);

            $encShip = bejewelry_encrypt_order_shipping_fields([
                'ship_name' => $shipName ?: null,
                'ship_street' => $shipStreet ?: null,
                'ship_city' => $shipCity ?: null,
                'ship_province' => $shipProvince ?: null,
                'ship_zip' => $shipZip ?: null,
                'ship_phone' => $shipPhone ?: null,
            ]);

            $oStmt = $pdo->prepare('INSERT INTO orders (id, user_id, status, subtotal, shipping_fee, total, promotion_id, ship_name, ship_street, ship_city, ship_province, ship_zip, ship_phone, payment_method, notes) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
            $oStmt->execute([
                $orderId, $userId, 'processing',
                $subtotal, $shippingFee, $total,
                $promotion ? (int) $promotion['id'] : null,
                $encShip['ship_name'],
                $encShip['ship_street'],
                $encShip['ship_city'],
                $encShip['ship_province'],
                $encShip['ship_zip'],
                $encShip['ship_phone'],
                $paymentMethod,
                $notes ?: null,
            ]);

            if ($promotion) {
                $redemption = $pdo->prepare('INSERT INTO promotion_redemptions (promotion_id, order_id, user_id, discount_amt) VALUES (?,?,?,?)');
                $redemption->execute([(int) $promotion['id'], $orderId, $userId, $discount]);
                $pdo->prepare('UPDATE promotions SET used_count = used_count + 1 WHERE id = ?')->execute([(int) $promotion['id']]);
            }

            $iStmt = $pdo->prepare('INSERT INTO order_items (order_id, product_id, name, image, cat, size, price, qty) VALUES (?,?,?,?,?,?,?,?)');
            foreach ($lineItems as $li) {
                $p = $li['product'];
                $iStmt->execute([
                    $orderId,
                    (int) $p['id'],
                    $p['name'],
                    $p['image'] ?? null,
                    $p['cat'] ?? null,
                    $li['size'],
                    (float) $p['price'],
                    (int) $li['qty'],
                ]);
            }

            bejewelry_deduct_stock_for_order($pdo, $lineItems, $orderId, $userId);

            $pdo->prepare('DELETE FROM cart_items WHERE user_id = ?')->execute([$userId]);
            $pdo->commit();
        } finally {
            bejewelry_unlock_order_sequence($pdo);
        }
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    return $orderId;
}

/**
 * Decrease products.stock for each line (call inside an open transaction).
 * Uses UPDATE … WHERE stock >= qty to avoid overselling under concurrency.
 *
 * @param list<array{product: array, qty: int, size: string}> $lineItems
 */
function bejewelry_deduct_stock_for_order(PDO $pdo, array $lineItems, ?string $actor = null, ?int $purchaserId = null): void
{
    foreach ($lineItems as $li) {
        $qty = (int) ($li['qty'] ?? 0);
        $pid = (int) ($li['product']['id'] ?? 0);
        $name = (string) ($li['product']['name'] ?? 'Product');
        $price = (float) ($li['product']['price'] ?? 0.0);
        if ($qty < 1 || $pid < 1) {
            throw new RuntimeException('Invalid order line for stock update.');
        }
        $upd = $pdo->prepare(
            'UPDATE products SET stock = stock - ?, updated_at = NOW() WHERE id = ? AND stock >= ? AND is_active = 1'
        );
        $upd->execute([$qty, $pid, $qty]);
        if ($upd->rowCount() === 0) {
            throw new RuntimeException('Insufficient stock for "' . $name . '".');
        }
        // Record stock history entry (qty_added is negative for deductions)
        $stockAfterStmt = $pdo->prepare('SELECT stock FROM products WHERE id = ?');
        $stockAfterStmt->execute([$pid]);
        $stockAfter = (int) $stockAfterStmt->fetchColumn();
        // Determine purchaser name when available
        $updatedBy = 'system';
        if ($purchaserId !== null) {
            $u = $pdo->prepare('SELECT first_name, last_name FROM users WHERE id = ?');
            $u->execute([$purchaserId]);
            $row = $u->fetch();
            if ($row) {
                $fn = trim((string) ($row['first_name'] ?? ''));
                $ln = trim((string) ($row['last_name'] ?? ''));
                $updatedBy = $fn !== '' || $ln !== '' ? trim($fn . ' ' . $ln) : 'Customer';
            }
        } elseif ($actor) {
            $updatedBy = $actor;
        }
        $ins = $pdo->prepare('INSERT INTO stock_history (product_id, qty_added, stock_after, price, note, updated_by) VALUES (?,?,?,?,?,?)');
        $ins->execute([$pid, -$qty, $stockAfter, $price, null, $updatedBy]);
    }
}
