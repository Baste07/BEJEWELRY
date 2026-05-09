<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc.php';

if (!current_user_id()) {
    header('Location: ../login.php?redirect=' . rawurlencode('orderManager/order_manager.php?page=orders'));
    exit;
}

$u = current_user();
$r = $u['role'] ?? 'customer';
if ($r === 'admin') {
    $r = 'super_admin';
}

if ($r !== 'manager') {
    header('Location: ../' . bejewelry_staff_post_login_path($u['role'] ?? 'customer'));
    exit;
}

$omSection = trim((string) ($_GET['page'] ?? 'orders'));
$orderManagerPageMap = [
    'dashboard' => 'dashboard.php',
    'orders' => 'orders.php',
    'tickets' => 'tickets.php',
    'reviews' => 'reviews.php',
    'promotions' => 'promotions.php',
];

if (isset($orderManagerPageMap[$omSection])) {
    $query = $_GET;
    unset($query['page']);

    $target = $orderManagerPageMap[$omSection];
    $qs = http_build_query($query);
    if ($qs !== '') {
        $target .= '?' . $qs;
    }

    header('Location: ' . $target);
    exit;
}

header('Location: orders.php');
exit;