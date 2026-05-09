<?php
declare(strict_types=1);

require_once __DIR__ . '/inc.php';
require_once __DIR__ . '/api/registration_helpers.php';
require_once __DIR__ . '/api/paymongo.php';
require_once __DIR__ . '/api/paymongo_pending.php';
require_once __DIR__ . '/promotion_helpers.php';
require_once __DIR__ . '/admin/settings/_settings_db.php';

if (!current_user_id()) {
    header('Location: login.php?redirect=' . urlencode('checkout.php'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: checkout.php');
    exit;
}

csrf_validate();

$paymentMethod = trim((string) ($_POST['payment_method'] ?? ''));
if ($paymentMethod !== 'paymongo') {
    header('Location: checkout.php?err=' . urlencode('Invalid payment flow.'));
    exit;
}

if (!paymongo_is_configured()) {
    header('Location: checkout.php?err=' . urlencode('Online payment is not configured. Add PAYMONGO_SECRET_KEY in api/config.php.'));
    exit;
}

$cart = get_customer_cart();
if (empty($cart)) {
    header('Location: cart.php');
    exit;
}

$shipName = trim((string) ($_POST['ship_name'] ?? ''));
$shipStreet = trim((string) ($_POST['ship_street'] ?? ''));
$shipCity = trim((string) ($_POST['ship_city'] ?? ''));
$shipProvince = trim((string) ($_POST['ship_province'] ?? ''));
$shipZip = trim((string) ($_POST['ship_zip'] ?? ''));
$shipPhone = trim((string) ($_POST['ship_phone'] ?? ''));

if ($shipName === '' || $shipStreet === '' || $shipCity === '' || $shipPhone === '') {
    header('Location: checkout.php?err=' . urlencode('Please complete your delivery information.'));
    exit;
}

$pdo = db();
$sPdo = settingsPdo();
$dbShipping = settingsGetJson($sPdo, 'shipping', ['shipping_fee' => SHIPPING_FEE, 'free_ship_threshold' => FREE_SHIP_THRESHOLD]);
$freeShipThreshold = (float) ($dbShipping['free_ship_threshold'] ?? FREE_SHIP_THRESHOLD);
$subtotal = 0.0;

foreach ($cart as $item) {
    $pid = (int) ($item['product_id'] ?? $item['id'] ?? 0);
    $qty = max(1, (int) ($item['qty'] ?? 1));
    $pStmt = $pdo->prepare('SELECT price FROM products WHERE id=? AND is_active=1');
    $pStmt->execute([$pid]);
    $price = $pStmt->fetchColumn();
    if ($price === false) {
        header('Location: cart.php?err=' . urlencode('A product in your cart is no longer available.'));
        exit;
    }
    $subtotal += (float) $price * $qty;
}

$promotionId = (int) ($_POST['promotion_id'] ?? 0);
$promotion = null;
$discount = 0.0;
if ($promotionId > 0) {
    $promotion = bejewelry_find_promotion_by_id($pdo, $promotionId);
    if (!$promotion) {
        header('Location: checkout.php?err=' . urlencode('Selected promotion is no longer available.'));
        exit;
    }
    $discount = bejewelry_calculate_promotion_discount($promotion, $subtotal, ($subtotal >= $freeShipThreshold) ? 0.0 : (float) ($dbShipping['shipping_fee'] ?? SHIPPING_FEE));
    if ($discount <= 0.0) {
        header('Location: checkout.php?err=' . urlencode('Selected promotion is not valid for this order total.'));
        exit;
    }
}

$shippingFee = ($subtotal >= $freeShipThreshold) ? 0.0 : (float) ($dbShipping['shipping_fee'] ?? SHIPPING_FEE);
$total = max(0.0, $subtotal - $discount + $shippingFee);
$totalCents = (int) round($total * 100);

if ($totalCents < 100) {
    header('Location: checkout.php?err=' . urlencode('Order total is too small for online payment.'));
    exit;
}

$pl = strtolower($shipProvince);
$daysNote = '5–7 business days';
foreach (['metro manila', 'ncr', 'manila', 'cebu'] as $m) {
    if ($pl !== '' && strpos($pl, $m) !== false) {
        $daysNote = '3–5 business days';
        break;
    }
}
if ($daysNote === '5–7 business days' && $pl !== '' && strpos($pl, 'metro') === false && strpos($pl, 'cebu') === false) {
    $isNear = false;
    foreach (['laguna', 'cavite', 'bulacan', 'rizal', 'batangas', 'pampanga', 'quezon', 'davao', 'iloilo'] as $n) {
        if (strpos($pl, $n) !== false) {
            $isNear = true;
            break;
        }
    }
    if (!$isNear) {
        $daysNote = '7–10 business days';
    }
}

$notes = 'Standard delivery · est. ' . $daysNote;

$lineItemsPaymongo = [[
    'amount' => $totalCents,
    'currency' => 'PHP',
    'name' => 'Bejewelry order',
    'quantity' => 1,
]];

$base = customer_public_base_url();
// Opaque token so return URL can be matched even when PayMongo omits checkout_session_id (Checkout v2 often sends payment_intent_id only).
$pmState = bin2hex(random_bytes(16));
$successUrl = $base . '/paymongo_return.php?' . http_build_query(['pm_state' => $pmState]);
$cancelUrl = $base . '/checkout.php?cancelled=1';

$cu = current_user();
$billEmail = trim((string) ($cu['email'] ?? ''));
$billing = [
    'name' => $shipName,
    'phone' => $shipPhone,
    'address' => [
        'line1' => $shipStreet,
        'city' => $shipCity,
        'state' => $shipProvince,
        'postal_code' => $shipZip !== '' ? $shipZip : '0000',
        'country' => 'PH',
    ],
];
if ($billEmail !== '') {
    $billing['email'] = $billEmail;
}

try {
    $result = paymongo_create_checkout_session(
        $lineItemsPaymongo,
        $successUrl,
        $cancelUrl,
        ['user_id' => (string) current_user_id()],
        $billing
    );
} catch (Throwable $e) {
    header('Location: checkout.php?err=' . urlencode($e->getMessage()));
    exit;
}

$_SESSION['paymongo_pending'] = [
    'pm_state' => $pmState,
    'checkout_session_id' => $result['checkout_session_id'],
    'expected_total_cents' => $totalCents,
    'post' => [
        'ship_name' => $shipName,
        'ship_street' => $shipStreet,
        'ship_city' => $shipCity,
        'ship_province' => $shipProvince,
        'ship_zip' => $shipZip,
        'ship_phone' => $shipPhone,
        'payment_method' => 'paymongo',
        'shipping_fee' => $shippingFee,
        'promotion_id' => $promotion ? (int) $promotion['id'] : null,
        'promotion_code' => $promotion ? (string) $promotion['code'] : null,
        'promotion_discount' => $discount,
        'notes' => $notes,
    ],
];

paymongo_pending_save(
    $pdo,
    (int) current_user_id(),
    $result['checkout_session_id'],
    $pmState,
    $totalCents,
    $_SESSION['paymongo_pending']['post']
);

header('Location: ' . $result['checkout_url']);
exit;
