<?php
require_once __DIR__ . '/_settings_db.php';
header('Content-Type: application/json');

try {
    csrf_validate();
    $pdo = settingsPdo();
    $b = settingsBody();

    $data = [
        'wishlist' => !empty($b['wishlist']),
        'reviews' => !empty($b['reviews']),
        'review_moderation' => !empty($b['review_moderation']),
        'engraving' => !empty($b['engraving']),
        'maintenance_mode' => !empty($b['maintenance_mode']),
    ];

    settingsSetJson($pdo, 'features', $data);
    echo json_encode(['ok' => true]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false]);
}

