<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

setHeaders();

$pdo = db();

// Processing orders
$processingStmt = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'processing'");
$processingOrders = (int)$processingStmt->fetchColumn();

// New products (created in last 30 days)
$prodStmt = $pdo->prepare('SELECT COUNT(*) FROM products WHERE created_at >= ?');
$prodStmt->execute([date('Y-m-d H:i:s', time() - 30 * 86400)]);
$newProducts = (int)$prodStmt->fetchColumn();

// Low stock (0 < stock <= 5)
$lowStockStmt = $pdo->query('SELECT COUNT(*) FROM products WHERE stock > 0 AND stock <= 5');
$lowStock = (int)$lowStockStmt->fetchColumn();

// Pending reviews placeholder (no reviews table yet)
$pendingReviews = 0;

respond([
    'pending_orders'  => $processingOrders,
    'new_products'    => $newProducts,
    'low_stock'       => $lowStock,
    'pending_reviews' => $pendingReviews,
]);

