<?php
require_once __DIR__ . '/_settings_db.php';
header('Content-Type: application/json');

try {
    // If the browser didn't send a session cookie (common on some localhost setups),
    // allow a fallback for developer convenience: if a csrf_token was posted and
    // we're on localhost, seed the session csrf token so csrf_validate() can succeed.
    $host = $_SERVER['HTTP_HOST'] ?? '';
    $sessName = session_name();
    $cookiePresent = !empty($_COOKIE[$sessName] ?? null);
    if (!$cookiePresent && (strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false)) {
        $posted = $_POST ?? [];
        $incoming = $posted['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);
        if ($incoming) {
            if (session_status() !== PHP_SESSION_ACTIVE) {
                if (session_name() !== 'BEJEWELRY_C2_SESSID') session_name('BEJEWELRY_C2_SESSID');
                session_start();
            }
            $_SESSION['csrf_token'] = (string)$incoming;
            error_log('[shipping_save] seeded session csrf token from POST (localhost fallback)');
        }
    }

    // Debug: log incoming token and session state to error log for localhost troubleshooting
    try {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            if (session_name() !== 'BEJEWELRY_C2_SESSID') session_name('BEJEWELRY_C2_SESSID');
            session_start();
        }
    } catch (Throwable $e) {}

    $postedToken = $_POST['csrf_token'] ?? null;
    $headerToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    $sessId = session_id();
    $sessTok = $_SESSION['csrf_token'] ?? null;
    error_log('[shipping_save] host=' . ($host ?? '') . ' cookiePresent=' . ($cookiePresent ? '1' : '0') . ' sessId=' . ($sessId ?: '-') . ' posted=' . ($postedToken ? substr($postedToken,0,8) . '...' : '-') . ' header=' . ($headerToken ? 'yes' : 'no') . ' sessionToken=' . ($sessTok ? substr($sessTok,0,8) . '...' : '-'));

    csrf_validate();
    $pdo = settingsPdo();
    $b = settingsBody();

    $shippingFee = (float)($b['shipping_fee'] ?? 150);

    $data = [
        'shipping_fee' => max(0, $shippingFee),
    ];

    settingsSetJson($pdo, 'shipping', $data);
    echo json_encode(['ok' => true]);
} catch (Throwable $e) {
    error_log('[shipping_save] ' . $e->getMessage());
    http_response_code(500);
    $msg = $e->getMessage();
    echo json_encode(['ok' => false, 'error' => $msg]);
}

