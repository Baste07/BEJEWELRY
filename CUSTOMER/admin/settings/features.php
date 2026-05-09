<?php
require_once __DIR__ . '/_settings_db.php';
header('Content-Type: application/json');

try {
    $pdo = settingsPdo();
    $defaults = [
        'wishlist' => true,
        'reviews' => true,
        'review_moderation' => false,
        'engraving' => false,
        'maintenance_mode' => false,
    ];
    echo json_encode(settingsGetJson($pdo, 'features', $defaults));
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([]);
}

