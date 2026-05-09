<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc.php';

if (!current_user_id()) {
    header('Location: ../login.php?redirect=' . rawurlencode('InventoryManager/index.php?page=inventory'));
    exit;
}

$u = current_user();
$r = $u['role'] ?? 'customer';
if ($r === 'admin') {
    $r = 'super_admin';
}

if ($r !== 'inventory') {
    header('Location: ../' . bejewelry_staff_post_login_path($u['role'] ?? 'customer'));
    exit;
}

$section = trim((string) ($_GET['page'] ?? 'inventory'));
$pageMap = [
    'dashboard' => 'dashboard.php',
    'inventory' => 'inventory.php',
    'products' => 'products.php',
    // 'reports' removed - using consolidated dashboard instead
];

if (isset($pageMap[$section])) {
    $query = $_GET;
    unset($query['page']);
    $target = $pageMap[$section];
    $qs = http_build_query($query);
    if ($qs !== '') {
        $target .= '?' . $qs;
    }
    header('Location: ' . $target);
    exit;
}

header('Location: inventory.php');
exit;