<?php
require_once __DIR__ . '/inc.php';
require_once __DIR__ . '/order_commit.php';

if (!current_user_id()) {
  header('Location: login.php?redirect=' . urlencode('checkout.php'));
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: checkout.php');
  exit;
}

csrf_validate();

$userId = current_user_id();
$cart = get_customer_cart();
if (empty($cart)) {
  header('Location: cart.php');
  exit;
}

$shipName = trim((string)($_POST['ship_name'] ?? ''));
$shipStreet = trim((string)($_POST['ship_street'] ?? ''));
$shipCity = trim((string)($_POST['ship_city'] ?? ''));
$shipProvince = trim((string)($_POST['ship_province'] ?? ''));
$shipZip = trim((string)($_POST['ship_zip'] ?? ''));
$shipPhone = trim((string)($_POST['ship_phone'] ?? ''));
$paymentMethod = trim((string)($_POST['payment_method'] ?? 'ewallet'));
$shippingFee = isset($_POST['shipping_fee']) ? (float)$_POST['shipping_fee'] : 0.0;
$notes = trim((string)($_POST['notes'] ?? ''));

if ($shipName === '' || $shipStreet === '' || $shipCity === '' || $shipPhone === '') {
  header('Location: checkout.php?err=' . urlencode('Please complete your delivery information.'));
  exit;
}

if ($paymentMethod === 'paymongo') {
  header('Location: checkout.php?err=' . urlencode('Use Pay online to complete PayMongo checkout.'));
  exit;
}

if (!in_array($paymentMethod, ['ewallet'], true)) {
  $paymentMethod = 'ewallet';
}

$pdo = db();

try {
  $orderId = bejewelry_commit_order($pdo, $userId, $_POST);
} catch (Exception $e) {
  header('Location: checkout.php?err=' . urlencode($e->getMessage() ?: 'Could not place order. Please try again.'));
  exit;
}

header('Location: order_history.php?placed=' . urlencode($orderId));
exit;

