<?php
require_once __DIR__ . '/inc.php';
$uid = current_user_id();
if (!$uid) {
  header('Location: login.php');
  exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_validate();
}
$id = (int) ($_POST['id'] ?? $_GET['id'] ?? 0);
$qty = max(1, (int) ($_POST['qty'] ?? $_GET['qty'] ?? 1));
$redirect = $_POST['redirect'] ?? $_GET['redirect'] ?? 'cart.php';
if (!$id) {
  header('Location: ' . $redirect);
  exit;
}
db()->prepare('UPDATE cart_items SET qty = ? WHERE id = ? AND user_id = ?')->execute([$qty, $id, $uid]);
header('Location: ' . $redirect);
exit;
