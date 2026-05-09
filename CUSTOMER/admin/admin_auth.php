<?php
declare(strict_types=1);

/**
 * Bejewelry admin — staff session + explicit role allowlists (no shared “super admin sees all”).
 */
require_once __DIR__ . '/../inc.php';

if (!current_user_id()) {
    $path = 'admin/' . basename($_SERVER['PHP_SELF'] ?? 'dashboard.php');
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

$staffRoles = ['super_admin', 'manager', 'inventory'];
if (!in_array($r, $staffRoles, true)) {
    header('Location: ../index.php');
    exit;
}

/** Page key => roles allowed (super_admin is explicit, never implicit). */
function admin_page_allowed_roles(): array
{
    return [
        'dashboard' => ['super_admin', 'manager', 'inventory'],
        'customers' => ['super_admin'],
        'archived_accounts' => ['super_admin'],
        'account_unlocks' => ['super_admin'],
        'customers_export' => ['super_admin'],
        'settings' => ['super_admin'],
        'audit_log' => ['super_admin'],
        'inventory' => ['super_admin', 'inventory'],
        'inventory_export' => ['super_admin', 'inventory'],
        'inventory_action' => ['super_admin', 'inventory'],
        'products' => ['inventory'],
        'products_action' => ['inventory'],
        'orders' => ['manager'],
        'orders_export' => ['manager'],
        'tickets' => ['super_admin', 'manager'],
        'reviews' => ['manager'],
        'promotions' => ['manager'],
        'review_action' => ['manager'],
        'notifications' => ['manager', 'inventory'],
        'reports' => ['inventory'],
    ];
}

function admin_redirect_for_role(string $r): void
{
    if ($r === 'manager') {
        header('Location: ../orderManager/order_manager.php?page=orders');
    } else {
        header('Location: ../InventoryManager/index.php');
    }
    exit;
}

function admin_require_page(string $pageKey): void
{
    $r = $GLOBALS['ADMIN_ROLE'] ?? 'super_admin';
    $map = admin_page_allowed_roles();
    $allowed = $map[$pageKey] ?? [];

    if ($allowed === [] || !in_array($r, $allowed, true)) {
        // Keep users inside the admin shell on unauthorized page attempts.
        $fallback = $r === 'super_admin' ? 'customers.php' : 'dashboard.php';
        header('Location: ' . $fallback . '?denied=' . urlencode($pageKey));
        exit;
    }
}
