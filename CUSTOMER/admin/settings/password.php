<?php
require_once __DIR__ . '/_settings_db.php';
header('Content-Type: application/json');

try {
    $pdo = settingsPdo();
    $defaults = [
        'enabled' => true,
        'min_length' => 12,
        'require_upper' => true,
        'require_lower' => true,
        'require_number' => true,
        'require_special' => true,
        'expiration_days' => 0,
    ];
    echo json_encode(settingsGetJson($pdo, 'security', $defaults));
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([]);
}

