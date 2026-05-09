<?php
declare(strict_types=1);

require_once __DIR__ . '/inc.php';
require_once __DIR__ . '/order_commit.php';
require_once __DIR__ . '/api/paymongo.php';
require_once __DIR__ . '/api/paymongo_pending.php';
require_once __DIR__ . '/api/order_email_helpers.php';

if (!current_user_id()) {
    $ret = 'paymongo_return.php';
    $q = $_SERVER['QUERY_STRING'] ?? '';
    if ($q !== '') {
        $ret .= '?' . $q;
    }
    header('Location: login.php?redirect=' . urlencode($ret));
    exit;
}

$pdo = db();
paymongo_pending_restore_from_db($pdo);

$pending = $_SESSION['paymongo_pending'] ?? null;

$sessionId = trim((string) ($_GET['checkout_session_id'] ?? $_GET['session_id'] ?? $_GET['checkout_session'] ?? ''));
$pmStateGet = trim((string) ($_GET['pm_state'] ?? ''));

if ($sessionId === '' && $pmStateGet !== '' && is_array($pending) && isset($pending['pm_state']) && hash_equals((string) $pending['pm_state'], $pmStateGet)) {
    $sessionId = trim((string) ($pending['checkout_session_id'] ?? ''));
}

if ($sessionId === '' && is_array($pending)) {
    $piId = trim((string) ($_GET['payment_intent_id'] ?? ''));
    if ($piId !== '') {
        try {
            $pi = paymongo_get_payment_intent($piId);
            $attrs = $pi['data']['attributes'] ?? [];
            $meta = is_array($attrs['metadata'] ?? null) ? $attrs['metadata'] : [];
            $uid = (string) ($meta['user_id'] ?? '');
            $amt = isset($attrs['amount']) && is_numeric($attrs['amount']) ? (int) $attrs['amount'] : 0;
            $st = (string) ($attrs['status'] ?? '');
            $exp = (int) ($pending['expected_total_cents'] ?? 0);
            $uidOk = $uid === '' || $uid === (string) current_user_id();
            if ($st === 'succeeded' && $exp > 0 && $amt === $exp && $uidOk) {
                $sessionId = trim((string) ($pending['checkout_session_id'] ?? ''));
            }
        } catch (Throwable $e) {
            // fall through to session fallback
        }
    }
}

if ($sessionId === '' && is_array($pending)) {
    $sessionId = trim((string) ($pending['checkout_session_id'] ?? ''));
}

if ($sessionId === '') {
    header('Location: checkout.php?err=' . urlencode('Missing payment session. Return from checkout in the same browser, or start checkout again.'));
    exit;
}

$dup = $pdo->prepare('SELECT id FROM orders WHERE notes LIKE ? LIMIT 1');
$dup->execute(['%paymongo_session:' . $sessionId . '%']);
$existing = $dup->fetchColumn();
if ($existing) {
    unset($_SESSION['paymongo_pending']);
    paymongo_pending_delete($pdo, (int) current_user_id());
    header('Location: order_history.php?placed=' . urlencode((string) $existing));
    exit;
}

$pending = $_SESSION['paymongo_pending'] ?? null;
if (!is_array($pending) || ($pending['checkout_session_id'] ?? '') !== $sessionId) {
    header('Location: checkout.php?err=' . urlencode('Payment session expired. Try again from checkout.'));
    exit;
}

try {
    $sess = paymongo_get_checkout_session($sessionId);
} catch (Throwable $e) {
    header('Location: checkout.php?err=' . urlencode('Could not verify payment with PayMongo.'));
    exit;
}

if (!paymongo_session_is_paid($sess)) {
    header('Location: checkout.php?err=' . urlencode('Payment was not completed.'));
    exit;
}

$paidCents = paymongo_session_total_cents($sess);
$expected = (int) ($pending['expected_total_cents'] ?? 0);
if ($paidCents !== null && $expected > 0 && abs($paidCents - $expected) > 1) {
    error_log("PayMongo amount mismatch: expected {$expected} got {$paidCents}");
    header('Location: checkout.php?err=' . urlencode('Payment amount did not match your order. Contact support if you were charged.'));
    exit;
}

$post = $pending['post'];
$post['notes'] = trim((string) ($post['notes'] ?? ''));
$post['notes'] .= ($post['notes'] !== '' ? ' | ' : '') . 'paymongo_session:' . $sessionId;

try {
    $orderId = bejewelry_commit_order($pdo, current_user_id(), $post);
} catch (Throwable $e) {
    header('Location: checkout.php?err=' . urlencode($e->getMessage()));
    exit;
}

bejewelry_send_order_processing_email($pdo, $orderId, (int) current_user_id());

unset($_SESSION['paymongo_pending']);
paymongo_pending_delete($pdo, (int) current_user_id());
header('Location: order_history.php?placed=' . urlencode($orderId));
exit;
