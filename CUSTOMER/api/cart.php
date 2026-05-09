<?php
/* ═══════════════════════════════════════════════════════════
   BEJEWELRY — Cart Endpoints
   GET    /api/cart             → get user's cart
   POST   /api/cart             → add item
   PATCH  /api/cart?id=N        → update qty
   DELETE /api/cart?id=N        → remove item
   DELETE /api/cart?clear=1     → clear cart
═══════════════════════════════════════════════════════════ */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/csrf_helper.php';

setHeaders();

$auth   = requireAuth();
$userId = $auth['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

// ── Format cart row ───────────────────────────────────────
function formatCartItem(array $row): array {
    return [
        'id'        => (int)$row['id'],
        'key'       => $row['product_id'] . '-' . $row['size'],
        'product_id'=> (int)$row['product_id'],
        'name'      => $row['name'],
        'cat'       => $row['cat'],
        'price'     => (float)$row['price'],
        'image'     => $row['image'],
        'image_url' => productImageUrl($row['image']),
        'size'      => $row['size'],
        'qty'       => (int)$row['qty'],
    ];
}

function getCartItems(int $userId): array {
    $stmt = db()->prepare('
        SELECT ci.id, ci.product_id, ci.size, ci.qty,
               p.name, p.price, p.image, c.name AS cat
        FROM cart_items ci
        JOIN products p ON p.id = ci.product_id
        LEFT JOIN categories c ON c.id = p.category_id
        WHERE ci.user_id = ?
        ORDER BY ci.added_at ASC
    ');
    $stmt->execute([$userId]);
    return array_map('formatCartItem', $stmt->fetchAll());
}

// ── GET /cart ─────────────────────────────────────────────
if ($method === 'GET') {
    respond(['items' => getCartItems($userId)]);
}

// ── POST /cart (add item) ─────────────────────────────────
if ($method === 'POST') {
    $b         = body();
    $productId = (int)($b['productId'] ?? 0);
    $qty       = max(1, (int)($b['qty'] ?? 1));
    $size      = trim($b['size'] ?? 'One Size');

    if (!$productId) respondError('productId is required.');

    // Verify product exists
    $p = db()->prepare('SELECT id, size_default FROM products WHERE id = ? AND is_active = 1');
    $p->execute([$productId]);
    $product = $p->fetch();
    if (!$product) respondError('Product not found.', 404);

    if (!$size) $size = $product['size_default'];

    // Upsert: increment if exists
    $stmt = db()->prepare('
        INSERT INTO cart_items (user_id, product_id, size, qty)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE qty = qty + VALUES(qty)
    ');
    $stmt->execute([$userId, $productId, $size, $qty]);

    respond(['items' => getCartItems($userId)], 201);
}

// ── PATCH /cart?id=N (update qty) ────────────────────────
if ($method === 'PATCH') {
    $itemId = (int)($_GET['id'] ?? 0);
    if (!$itemId) respondError('Cart item ID required.');
    $b   = body();
    $qty = (int)($b['qty'] ?? 1);
    if ($qty < 1) {
        db()->prepare('DELETE FROM cart_items WHERE id = ? AND user_id = ?')->execute([$itemId, $userId]);
    } else {
        db()->prepare('UPDATE cart_items SET qty = ? WHERE id = ? AND user_id = ?')->execute([$qty, $itemId, $userId]);
    }
    respond(['items' => getCartItems($userId)]);
}

// ── DELETE /cart ──────────────────────────────────────────
if ($method === 'DELETE') {
    if (!empty($_GET['clear'])) {
        db()->prepare('DELETE FROM cart_items WHERE user_id = ?')->execute([$userId]);
        http_response_code(204); exit;
    }
    $itemId = (int)($_GET['id'] ?? 0);
    if (!$itemId) respondError('Cart item ID required.');
    db()->prepare('DELETE FROM cart_items WHERE id = ? AND user_id = ?')->execute([$itemId, $userId]);
    http_response_code(204); exit;
}

respondError('Not found.', 404);
