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
$redirect = $_POST['redirect'] ?? $_GET['redirect'] ?? 'cart.php';
if ($id) {
  db()->prepare('DELETE FROM cart_items WHERE id = ? AND user_id = ?')->execute([$id, $uid]);
}
header('Location: ' . $redirect);
exit;
