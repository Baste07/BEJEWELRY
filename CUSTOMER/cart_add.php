<?php
require_once __DIR__ . '/inc.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate();
}

$isAjax = (($_POST['ajax'] ?? $_GET['ajax'] ?? '') === '1');

$respond = static function (bool $ok, string $redirect, string $message = '', int $status = 200) use ($isAjax): void {
    if ($isAjax) {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode(['ok' => $ok, 'message' => $message]);
    } else {
        header('Location: ' . $redirect);
    }
    exit;
};

$productId = (int) ($_POST['product_id'] ?? $_GET['product_id'] ?? 0);
$qty = max(1, (int) ($_POST['qty'] ?? $_GET['qty'] ?? 1));
$redirect = $_POST['redirect'] ?? $_GET['redirect'] ?? 'index.php';

if (!$productId) {
    $respond(false, $redirect, 'Invalid product.', 400);
}

$uid = current_user_id();
if (!$uid) {
    $respond(false, 'login.php?redirect=' . urlencode($redirect), 'Please log in first.', 401);
}

$stmt = db()->prepare('SELECT id, size_default, stock FROM products WHERE id = ? AND is_active = 1');
$stmt->execute([$productId]);
$product = $stmt->fetch();
if (!$product) {
    $respond(false, $redirect, 'Product not found.', 404);
}

$stock = (int) ($product['stock'] ?? 0);
if ($stock <= 0) {
    $respond(false, $redirect, 'This product is currently out of stock.', 409);
}

$size = trim($_POST['size'] ?? $_GET['size'] ?? $product['size_default'] ?? 'One Size');
if (!$size) $size = 'One Size';

$exists = db()->prepare('SELECT id, qty FROM cart_items WHERE user_id = ? AND product_id = ? AND size = ?');
$exists->execute([$uid, $productId, $size]);
$row = $exists->fetch();
if ($row) {
    $newQty = (int) ($row['qty'] ?? 0) + $qty;
    if ($newQty > $stock) {
        $respond(false, $redirect, 'Not enough stock available.', 409);
    }
    db()->prepare('UPDATE cart_items SET qty = qty + ? WHERE id = ?')->execute([$qty, $row['id']]);
} else {
    if ($qty > $stock) {
        $respond(false, $redirect, 'Not enough stock available.', 409);
    }
    db()->prepare('INSERT INTO cart_items (user_id, product_id, size, qty) VALUES (?,?,?,?)')
        ->execute([$uid, $productId, $size, $qty]);
}

$respond(true, $redirect, 'Added to cart.', 200);
