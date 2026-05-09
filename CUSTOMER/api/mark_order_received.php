<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc.php';

header('Content-Type: application/json');

function mark_order_received_response(int $status, array $payload): void
{
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    mark_order_received_response(405, ['ok' => false, 'error' => 'Method not allowed.']);
}

if (!current_user_id()) {
    mark_order_received_response(401, ['ok' => false, 'error' => 'Please sign in first.']);
}

try {
    csrf_validate();
} catch (Throwable $e) {
    mark_order_received_response(403, ['ok' => false, 'error' => 'Security validation failed. Please refresh and try again.']);
}

$userId = (int) current_user_id();
$orderId = trim((string) ($_POST['order_id'] ?? ''));
if ($orderId === '') {
    mark_order_received_response(422, ['ok' => false, 'error' => 'Order ID is required.']);
}

$orderStmt = db()->prepare('SELECT id, status, created_at FROM orders WHERE id = ? AND user_id = ? LIMIT 1');
$orderStmt->execute([$orderId, $userId]);
$order = $orderStmt->fetch(PDO::FETCH_ASSOC);
if (!$order) {
    mark_order_received_response(404, ['ok' => false, 'error' => 'Order not found.']);
}

if (($order['status'] ?? '') !== 'delivered') {
    mark_order_received_response(409, ['ok' => false, 'error' => 'Only delivered orders can be marked as received.']);
}

try {
    db()->exec('CREATE TABLE IF NOT EXISTS order_receipts (
      id INT AUTO_INCREMENT PRIMARY KEY,
      order_id VARCHAR(64) NOT NULL,
      user_id INT NOT NULL,
      received_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
      UNIQUE KEY uniq_order_user (order_id, user_id),
      KEY idx_user_received (user_id, received_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
} catch (Throwable $e) {
    mark_order_received_response(500, ['ok' => false, 'error' => 'Unable to prepare receipt tracking.']);
}

$insertReceipt = db()->prepare('INSERT INTO order_receipts (order_id, user_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE received_at = received_at');
$insertReceipt->execute([$orderId, $userId]);

$receivedAt = '';
$receiptStmt = db()->prepare('SELECT received_at FROM order_receipts WHERE order_id = ? AND user_id = ? LIMIT 1');
$receiptStmt->execute([$orderId, $userId]);
$receivedAt = (string) ($receiptStmt->fetchColumn() ?: '');

$reviewStmt = db()->prepare('SELECT product_id FROM product_reviews WHERE user_id = ? AND order_id = ?');
$reviewStmt->execute([$userId, $orderId]);
$reviewedProductIds = array_map('intval', $reviewStmt->fetchAll(PDO::FETCH_COLUMN) ?: []);
$reviewedMap = array_fill_keys($reviewedProductIds, true);

$itemStmt = db()->prepare('SELECT product_id, name, cat, size, image, qty FROM order_items WHERE order_id = ? ORDER BY id ASC');
$itemStmt->execute([$orderId]);
$orderItems = $itemStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

$dateLabel = '';
try {
    $dt = new DateTime((string) ($order['created_at'] ?? ''));
    $dateLabel = $dt->format('M j, Y');
} catch (Throwable $e) {
    $dateLabel = '';
}

$pendingItems = [];
foreach ($orderItems as $item) {
    $productId = (int) ($item['product_id'] ?? 0);
    if ($productId <= 0 || isset($reviewedMap[$productId])) {
        continue;
    }

    $pendingItems[] = [
        'order_id' => (string) $orderId,
        'order_date' => $dateLabel,
        'product_id' => $productId,
        'product_name' => (string) ($item['name'] ?? ''),
        'product_cat' => (string) ($item['cat'] ?? ''),
        'product_size' => (string) ($item['size'] ?? ''),
        'product_image' => (string) ($item['image'] ?? ''),
        'qty' => (int) ($item['qty'] ?? 1),
    ];
}

mark_order_received_response(200, [
    'ok' => true,
    'message' => 'Order received confirmed.',
    'received_at' => $receivedAt,
    'pending_count' => count($pendingItems),
    'pending_items' => $pendingItems,
]);
