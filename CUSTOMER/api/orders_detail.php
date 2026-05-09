<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/../inc.php';

setHeaders();

$id = $_GET['id'] ?? '';
if (!$id) {
    respondError('order id required', 400);
}

$pdo = db();

$stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ?');
$stmt->execute([$id]);
$order = $stmt->fetch();

if (!$order) {
    respondError('Order not found', 404);
}

$order = bejewelry_decrypt_order_shipping_fields($order);

$itemsStmt = $pdo->prepare('SELECT name, qty, price AS unit_price FROM order_items WHERE order_id = ?');
$itemsStmt->execute([$id]);
$items = $itemsStmt->fetchAll();

foreach ($items as &$i) {
    $i['qty']        = (int)$i['qty'];
    $i['unit_price'] = (float)$i['unit_price'];
}

respond([
    'id'               => $order['id'],
    'customer_name'    => $order['ship_name'],
    'customer_contact' => $order['ship_phone'],
    'created_at'       => $order['created_at'],
    'payment_method'   => $order['payment_method'],
    'shipping_address' => trim($order['ship_street'] . ', ' . $order['ship_city'] . ', ' . $order['ship_province'] . ' ' . $order['ship_zip']),
    'status'           => $order['status'],
    'items'            => $items,
    'shipping_fee'     => (float)$order['shipping_fee'],
]);

