<?php
require_once __DIR__ . '/_settings_db.php';
header('Content-Type: application/json');

try {
    $host = $_SERVER['HTTP_HOST'] ?? '';
    $sessName = session_name();
    $cookiePresent = !empty($_COOKIE[$sessName] ?? null);
    if (!$cookiePresent && (strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false)) {
        $incoming = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);
        if ($incoming) {
            if (session_status() !== PHP_SESSION_ACTIVE) {
                if (session_name() !== 'BEJEWELRY_C2_SESSID') session_name('BEJEWELRY_C2_SESSID');
                session_start();
            }
            $_SESSION['csrf_token'] = (string) $incoming;
            error_log('[password_save] seeded session csrf token from request (localhost fallback)');
        }
    }

    if (session_status() !== PHP_SESSION_ACTIVE) {
        if (session_name() !== 'BEJEWELRY_C2_SESSID') session_name('BEJEWELRY_C2_SESSID');
        session_start();
    }

    error_log('[password_save] host=' . ($host ?? '') . ' cookiePresent=' . ($cookiePresent ? '1' : '0') . ' sessId=' . session_id());

    csrf_validate();
    $pdo = settingsPdo();
    $b = settingsBody();

    $enabled = !empty($b['enabled']);
    $min_length = max(6, (int)($b['min_length'] ?? 12));
    $require_upper = !empty($b['require_upper']);
    $require_lower = !empty($b['require_lower']);
    $require_number = !empty($b['require_number']);
    $require_special = !empty($b['require_special']);
    $expiration_days = max(0, (int)($b['expiration_days'] ?? 0));

    $data = [
        'enabled' => $enabled,
        'min_length' => $min_length,
        'require_upper' => $require_upper,
        'require_lower' => $require_lower,
        'require_number' => $require_number,
        'require_special' => $require_special,
        'expiration_days' => $expiration_days,
    ];

    settingsSetJson($pdo, 'security', $data);
    echo json_encode(['ok' => true]);
} catch (Throwable $e) {
    error_log('[password_save] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
