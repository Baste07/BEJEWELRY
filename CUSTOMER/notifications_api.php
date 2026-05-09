<?php
require_once __DIR__ . '/inc.php';
require_once __DIR__ . '/notification_helpers.php';

if (function_exists('bejewelry_send_security_headers')) {
    bejewelry_send_security_headers();
}
header('Content-Type: application/json; charset=utf-8');

if (!current_user_id()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$uid = (int) current_user_id();
$pdo = db();
$action = strtolower(trim((string) ($_GET['action'] ?? 'list')));
$method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

function notifications_json_body(): array
{
    $raw = file_get_contents('php://input');
    if (!is_string($raw) || $raw === '') {
        return [];
    }
    $parsed = json_decode($raw, true);
    return is_array($parsed) ? $parsed : [];
}

function notifications_respond(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

try {
    if ($method === 'GET' && $action === 'list') {
        bejewelry_sync_customer_notifications($pdo, $uid);
        $items = bejewelry_list_customer_notifications($pdo, $uid, 25);
        $prefs = bejewelry_notification_prefs($pdo, $uid);
        $unread = bejewelry_unread_customer_notification_count($pdo, $uid);
        notifications_respond([
            'ok' => true,
            'items' => $items,
            'prefs' => $prefs,
            'unread_count' => $unread,
        ]);
    }

    if ($method === 'GET' && $action === 'prefs') {
        $prefs = bejewelry_notification_prefs($pdo, $uid);
        notifications_respond(['ok' => true, 'prefs' => $prefs]);
    }

    if ($method === 'POST' && $action === 'prefs') {
        csrf_validate();
        $body = notifications_json_body();
        $prefs = bejewelry_notification_save_prefs($pdo, $uid, [
            'order_updates' => $body['order_updates'] ?? null,
            'promotions' => $body['promotions'] ?? null,
            'wishlist' => $body['wishlist'] ?? null,
        ]);
        notifications_respond(['ok' => true, 'prefs' => $prefs]);
    }

    if ($method === 'POST' && $action === 'read') {
        csrf_validate();
        $body = notifications_json_body();
        $id = isset($body['id']) ? (int) $body['id'] : 0;
        if ($id > 0) {
            bejewelry_mark_customer_notification_read($pdo, $uid, $id);
        }
        $unread = bejewelry_unread_customer_notification_count($pdo, $uid);
        notifications_respond(['ok' => true, 'unread_count' => $unread]);
    }

    if ($method === 'POST' && $action === 'read-all') {
        csrf_validate();
        bejewelry_mark_all_customer_notifications_read($pdo, $uid);
        notifications_respond(['ok' => true, 'unread_count' => 0]);
    }

    notifications_respond(['error' => 'Not found'], 404);
} catch (Throwable $e) {
    notifications_respond(['error' => 'Notification request failed.'], 500);
}
