<?php
declare(strict_types=1);

require_once __DIR__ . '/admin_auth.php';
require_once __DIR__ . '/db.php';
admin_require_page('inventory_action');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: inventory.php');
    exit;
}
csrf_validate();
$pdo = adminDb();
$action = trim((string) ($_POST['action'] ?? ''));
$redirect = 'inventory.php';
if (!empty($_GET['filter'])) {
    $redirect .= '?filter=' . rawurlencode($_GET['filter']);
}
if (!empty($_GET['search'])) {
    $redirect .= (strpos($redirect, '?') !== false ? '&' : '?') . 'search=' . rawurlencode($_GET['search']);
}

if ($action === 'update_price') {
    $id = (int) ($_POST['product_id'] ?? 0);
    $price = isset($_POST['price']) ? (float) $_POST['price'] : 0;
    if ($id > 0 && $price >= 0 && $price <= 99999999.99) {
        $pdo->prepare('UPDATE products SET price = ? WHERE id = ?')->execute([$price, $id]);
        $actor = current_user();
        bejewelry_audit_log(
            (int) ($actor['id'] ?? 0) ?: null,
            (string) ($actor['email'] ?? ''),
            'edit_product_price'
        );
    }
    header('Location: ' . $redirect);
    exit;
}

if ($action === 'restock') {
    $id = (int) ($_POST['product_id'] ?? 0);
    $add = (int) ($_POST['add_qty'] ?? 0);
    if ($id > 0 && $add > 0) {
        $pdo->prepare('UPDATE products SET stock = stock + ? WHERE id = ?')->execute([$add, $id]);
        $actor = current_user();
        bejewelry_audit_log(
            (int) ($actor['id'] ?? 0) ?: null,
            (string) ($actor['email'] ?? ''),
            'restock_product'
        );
    }
    header('Location: ' . $redirect);
    exit;
}
if ($action === 'bulk_restock') {
    header('Location: ' . $redirect);
    exit;
}
header('Location: ' . $redirect);
exit;
