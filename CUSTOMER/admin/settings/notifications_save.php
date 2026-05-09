<?php
require_once __DIR__ . '/_settings_db.php';
header('Content-Type: application/json');

try {
    csrf_validate();
    $pdo = settingsPdo();
    $b = settingsBody();

    $email = trim((string)($b['admin_email'] ?? ''));
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Invalid email']);
        exit;
    }

    $data = [
        'admin_email' => $email,
        'new_order' => !empty($b['new_order']),
        'low_stock' => !empty($b['low_stock']),
        'new_review' => !empty($b['new_review']),
        'customer_reg' => !empty($b['customer_reg']),
        'daily_summary' => !empty($b['daily_summary']),
    ];

    settingsSetJson($pdo, 'notifications', $data);
    echo json_encode(['ok' => true]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false]);
}

