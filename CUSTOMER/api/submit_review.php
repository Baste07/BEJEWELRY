<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc.php';

header('Content-Type: application/json');

function review_response(int $status, array $payload): void
{
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    review_response(405, ['ok' => false, 'error' => 'Method not allowed.']);
}

if (!current_user_id()) {
    review_response(401, ['ok' => false, 'error' => 'Please sign in first.']);
}

try {
    csrf_validate();
} catch (Throwable $e) {
    review_response(403, ['ok' => false, 'error' => 'Security validation failed: ' . $e->getMessage() . '. Please refresh and try again.']);
}

$userId = (int) current_user_id();
$orderId = trim((string) ($_POST['order_id'] ?? ''));
$productId = (int) ($_POST['product_id'] ?? 0);
$rating = (int) ($_POST['rating'] ?? 0);
$body = trim((string) ($_POST['body'] ?? ''));

if ($orderId === '' || $productId <= 0 || $rating < 1 || $rating > 5) {
    review_response(422, ['ok' => false, 'error' => 'Please choose a rating from 1 to 5 stars.']);
}

if (strlen($body) > 1000) {
    review_response(422, ['ok' => false, 'error' => 'Your comment is too long. Please keep it under 1000 characters.']);
}

$orderStmt = db()->prepare('SELECT id, status FROM orders WHERE id = ? AND user_id = ? LIMIT 1');
$orderStmt->execute([$orderId, $userId]);
$order = $orderStmt->fetch(PDO::FETCH_ASSOC);
if (!$order) {
    review_response(404, ['ok' => false, 'error' => 'Order not found.']);
}

if (($order['status'] ?? '') !== 'delivered') {
    review_response(409, ['ok' => false, 'error' => 'You can only rate delivered orders.']);
}

$itemStmt = db()->prepare('SELECT id FROM order_items WHERE order_id = ? AND product_id = ? LIMIT 1');
$itemStmt->execute([$orderId, $productId]);
if (!$itemStmt->fetchColumn()) {
    review_response(404, ['ok' => false, 'error' => 'That product is not part of this order.']);
}

$existingStmt = db()->prepare('SELECT id FROM product_reviews WHERE user_id = ? AND order_id = ? AND product_id = ? LIMIT 1');
$existingStmt->execute([$userId, $orderId, $productId]);
$existingId = (int) ($existingStmt->fetchColumn() ?: 0);

if ($existingId > 0) {
    $updateStmt = db()->prepare('UPDATE product_reviews SET rating = ?, body = ?, status = ?, updated_at = NOW() WHERE id = ?');
    $updateStmt->execute([$rating, $body !== '' ? $body : null, 'pending', $existingId]);
} else {
    $insertStmt = db()->prepare('INSERT INTO product_reviews (product_id, user_id, order_id, rating, body, status) VALUES (?, ?, ?, ?, ?, ?)');
    $insertStmt->execute([$productId, $userId, $orderId, $rating, $body !== '' ? $body : null, 'pending']);
}

review_response(200, [
    'ok' => true,
    'message' => 'Thanks. Your rating has been saved.',
]);
