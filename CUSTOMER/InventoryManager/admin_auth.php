<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc.php';

if (!current_user_id()) {
    $path = 'InventoryManager/' . basename($_SERVER['PHP_SELF'] ?? 'inventory.php');
    $qs = $_SERVER['QUERY_STRING'] ?? '';
    if ($qs !== '') {
        $path .= '?' . $qs;
    }
    header('Location: ../login.php?redirect=' . urlencode($path));
    exit;
}

$u = current_user();
$r = $u['role'] ?? 'customer';
if ($r === 'admin') {
    $r = 'super_admin';
}

$GLOBALS['ADMIN_ROLE'] = $r;
$GLOBALS['ADMIN_USER'] = $u;

if ($r !== 'inventory') {
    header('Location: ../' . bejewelry_staff_post_login_path($u['role'] ?? 'customer'));
    exit;
}

function admin_page_allowed_roles(): array
{
    return [
        'dashboard' => ['inventory'],
        'inventory' => ['inventory'],
        'inventory_action' => ['inventory'],
        'inventory_export' => ['inventory'],
        'products' => ['inventory'],
        'reports' => ['inventory'],
        'notifications' => ['inventory'],
    ];
}

function admin_require_page(string $pageKey): void
{
    $r = $GLOBALS['ADMIN_ROLE'] ?? 'inventory';
    $map = admin_page_allowed_roles();
    $allowed = $map[$pageKey] ?? [];

    if ($allowed === [] || !in_array($r, $allowed, true)) {
        header('Location: inventory.php?denied=' . urlencode($pageKey));
        exit;
    }
}
