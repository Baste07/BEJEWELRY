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
$productId = (int) ($_POST['product_id'] ?? $_GET['product_id'] ?? 0);
$redirect = $_POST['redirect'] ?? $_GET['redirect'] ?? 'index.php';
if ($productId) {
  db()->prepare('DELETE FROM wishlist WHERE user_id = ? AND product_id = ?')->execute([$uid, $productId]);
}
header('Location: ' . $redirect);
exit;
