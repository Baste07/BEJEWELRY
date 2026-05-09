<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

setHeaders();

$auth = requireAuth();
if (($auth['role'] ?? '') !== 'admin') {
    respondError('Forbidden', 403);
}

$b = body();
$orderId = $b['order_id'] ?? '';
$status  = strtolower(trim($b['status'] ?? ''));

if ($status === 'pending') {
    $status = 'processing';
}

if (!$orderId || !$status) {
    respondError('order_id and status are required.');
}

$allowed = ['processing','shipped','delivered','cancelled'];
if (!in_array($status, $allowed, true)) {
    respondError('Invalid status value.');
}

$stmt = db()->prepare('UPDATE orders SET status = ? WHERE id = ?');
$stmt->execute([$status, $orderId]);

respond(['ok' => true]);

