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
        try {
            $pdo->beginTransaction();

            // update product stock
            $upd = $pdo->prepare('UPDATE products SET stock = stock + ? WHERE id = ?');
            $upd->execute([$add, $id]);

            // fetch new stock and current price
            $sel = $pdo->prepare('SELECT stock, price FROM products WHERE id = ? LIMIT 1');
            $sel->execute([$id]);
            $row = $sel->fetch(PDO::FETCH_ASSOC) ?: [];
            $stockAfter = isset($row['stock']) ? (int) $row['stock'] : null;
            $price = isset($row['price']) ? $row['price'] : null;

            // build note and updated_by from POST and current user
            $reason = trim((string) ($_POST['reason'] ?? ''));
            $notes = trim((string) ($_POST['notes'] ?? ''));
            $note = $reason !== '' ? $reason : null;
            if ($notes !== '') {
                $note = ($note ? $note . ' — ' : '') . $notes;
            }

            $actor = current_user();
            $updatedBy = 'Inventory Manager';
            if ($actor) {
                $name = trim((string) (($actor['first_name'] ?? '') . ' ' . ($actor['last_name'] ?? '')));
                $updatedBy = $name !== '' ? $name : ($actor['email'] ?? $updatedBy);
            }

            // insert into stock_history
            $ins = $pdo->prepare('INSERT INTO stock_history (product_id, qty_added, stock_after, price, note, updated_by) VALUES (?, ?, ?, ?, ?, ?)');
            $ins->execute([$id, $add, $stockAfter, $price, $note, $updatedBy]);

            // audit log
            bejewelry_audit_log((int) ($actor['id'] ?? 0) ?: null, (string) ($actor['email'] ?? ''), 'restock_product');

            $pdo->commit();
        } catch (Exception $e) {
            try { $pdo->rollBack(); } catch (Exception $_) {}
        }
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
