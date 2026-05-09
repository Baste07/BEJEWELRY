<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc.php';
require_once __DIR__ . '/order_email_helpers.php';
header('Content-Type: application/json');

function resp(int $status, array $payload): void {
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    resp(405, ['ok' => false, 'error' => 'Method not allowed.']);
}

if (!current_user_id()) {
    resp(401, ['ok' => false, 'error' => 'Please sign in first.']);
}

    try {
        csrf_validate();
    } catch (Throwable $e) {
        resp(403, ['ok' => false, 'error' => 'Security validation failed: ' . $e->getMessage() . '. Please refresh and try again.']);
    }

$userId = (int) current_user_id();
$orderId = trim((string) ($_POST['order_id'] ?? ''));
$rating = (int) ($_POST['rating'] ?? 0);
$body = trim((string) ($_POST['body'] ?? ''));

if ($orderId === '' || $rating < 1 || $rating > 5) {
    resp(422, ['ok' => false, 'error' => 'Please choose a rating from 1 to 5 stars.']);
}
if (strlen($body) > 1000) {
    resp(422, ['ok' => false, 'error' => 'Comment too long.']);
}

$pdo = db();
$orderStmt = $pdo->prepare('SELECT id, status, courier_user_id, courier_name, user_id FROM orders WHERE id = ? LIMIT 1');
$orderStmt->execute([$orderId]);
$order = $orderStmt->fetch(PDO::FETCH_ASSOC);
if (!$order) resp(404, ['ok' => false, 'error' => 'Order not found.']);
if ((string)($order['user_id'] ?? '') !== (string)$userId) resp(403, ['ok' => false, 'error' => 'Not allowed.']);
if (($order['status'] ?? '') !== 'delivered') resp(409, ['ok' => false, 'error' => 'Can only rate courier for delivered orders.']);

$existing = $pdo->prepare('SELECT id FROM order_courier_ratings WHERE order_id = ? AND user_id = ? LIMIT 1');
$existing->execute([$orderId, $userId]);
$existingId = (int)($existing->fetchColumn() ?: 0);

if ($existingId > 0) {
    $up = $pdo->prepare('UPDATE order_courier_ratings SET rating = ?, body = ?, courier_user_id = ?, courier_name = ?, created_at = NOW() WHERE id = ?');
    $up->execute([$rating, $body !== '' ? $body : null, $order['courier_user_id'] ?? null, $order['courier_name'] ?? null, $existingId]);
} else {
    $ins = $pdo->prepare('INSERT INTO order_courier_ratings (order_id, user_id, courier_user_id, courier_name, rating, body) VALUES (?, ?, ?, ?, ?, ?)');
    $ins->execute([$orderId, $userId, $order['courier_user_id'] ?? null, $order['courier_name'] ?? null, $rating, $body !== '' ? $body : null]);
}

bejewelry_send_order_completed_email($pdo, $orderId, $userId);

resp(200, ['ok' => true, 'message' => 'Thanks — your courier rating has been saved.']);
