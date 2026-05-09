<?php
require_once __DIR__ . '/_settings_db.php';
header('Content-Type: application/json');

try {
    $pdo = settingsPdo();
    $defaults = [
        'gcash' => true,
        'maya' => true,
        'card' => true,
        'bank_transfer' => false,
    ];
    echo json_encode(settingsGetJson($pdo, 'payment', $defaults));
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([]);
}

