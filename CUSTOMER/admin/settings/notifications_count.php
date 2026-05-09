<?php
require_once __DIR__ . '/_settings_db.php';
header('Content-Type: application/json');

try {
    $pdo = settingsPdo();

    $defaults = [
        'admin_email' => '',
        'new_order' => true,
        'low_stock' => true,
        'new_review' => true,
        'customer_reg' => false,
        'daily_summary' => false,
    ];
    $prefs = settingsGetJson($pdo, 'notifications', $defaults);

    $count = 0;

    if (!empty($prefs['new_order'])) {
        $count += (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'processing'")->fetchColumn();
    }
    if (!empty($prefs['low_stock'])) {
        $count += (int)$pdo->query("SELECT COUNT(*) FROM products WHERE stock > 0 AND stock <= 5")->fetchColumn();
    }
    if (!empty($prefs['new_review'])) {
        // pending reviews table exists in schema.sql as product_reviews
        $count += (int)$pdo->query("SELECT COUNT(*) FROM product_reviews WHERE status = 'pending'")->fetchColumn();
    }
    if (!empty($prefs['customer_reg'])) {
        // customers created today
        $count += (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'customer' AND DATE(created_at) = CURDATE()")->fetchColumn();
    }

    echo json_encode(['ok' => true, 'count' => $count]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'count' => 0]);
}

