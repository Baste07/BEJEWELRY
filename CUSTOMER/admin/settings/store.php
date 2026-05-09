<?php
require_once __DIR__ . '/_settings_db.php';
header('Content-Type: application/json');

try {
    $pdo = settingsPdo();
    $defaults = [
        'store_name' => 'Bejewelry',
        'tagline' => 'Fine Jewelry',
        'currency' => 'PHP',
        'contact_email' => '',
        'phone' => '',
    ];
    echo json_encode(settingsGetJson($pdo, 'store', $defaults));
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([]);
}

