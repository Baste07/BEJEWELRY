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
    echo json_encode(settingsGetJson($pdo, 'notifications', $defaults));
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([]);
}

