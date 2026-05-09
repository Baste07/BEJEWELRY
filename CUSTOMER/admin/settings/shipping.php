<?php
require_once __DIR__ . '/_settings_db.php';
header('Content-Type: application/json');

try {
    $pdo = settingsPdo();
    $defaults = [
        'shipping_fee' => 150,
        'free_ship_threshold' => 2000,
        'tax_rate' => 0,
        'carrier' => 'LBC Express',
        'cod_enabled' => true,
        'same_day_enabled' => false,
    ];
    echo json_encode(settingsGetJson($pdo, 'shipping', $defaults));
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([]);
}

