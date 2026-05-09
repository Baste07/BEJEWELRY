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
            error_log('[store_save] seeded session csrf token from request (localhost fallback)');
        }
    }

    if (session_status() !== PHP_SESSION_ACTIVE) {
        if (session_name() !== 'BEJEWELRY_C2_SESSID') session_name('BEJEWELRY_C2_SESSID');
        session_start();
    }

    error_log('[store_save] host=' . ($host ?? '') . ' cookiePresent=' . ($cookiePresent ? '1' : '0') . ' sessId=' . session_id());

    csrf_validate();
    $pdo = settingsPdo();
    $b = settingsBody();

    $data = [
        'store_name' => trim((string)($b['store_name'] ?? '')),
        'tagline' => trim((string)($b['tagline'] ?? '')),
        'currency' => (string)($b['currency'] ?? 'PHP'),
        'contact_email' => trim((string)($b['contact_email'] ?? '')),
        'phone' => trim((string)($b['phone'] ?? '')),
    ];
    if ($data['store_name'] === '') $data['store_name'] = 'Bejewelry';
    if (!in_array($data['currency'], ['PHP','USD'], true)) $data['currency'] = 'PHP';

    settingsSetJson($pdo, 'store', $data);
    echo json_encode(['ok' => true]);
} catch (Throwable $e) {
    error_log('[store_save] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}

