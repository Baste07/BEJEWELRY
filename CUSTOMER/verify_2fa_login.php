<?php
declare(strict_types=1);

require_once __DIR__ . '/inc.php';
require_once __DIR__ . '/auth_totp.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login_2fa.php');
    exit;
}

csrf_validate();

if (empty($_SESSION['pending_totp_uid'])) {
    header('Location: login.php');
    exit;
}

$uid = (int) $_SESSION['pending_totp_uid'];
$code = trim($_POST['totp'] ?? '');

$_SESSION['pending_totp_attempts'] = (int) ($_SESSION['pending_totp_attempts'] ?? 0) + 1;
if ($_SESSION['pending_totp_attempts'] > TOTP_MAX_ATTEMPTS) {
    bejewelry_clear_pending_totp();
    header('Location: login.php?error=' . urlencode('Too many attempts. Please sign in again.'));
    exit;
}

$stmt = db()->prepare('SELECT totp_secret FROM users WHERE id = ? LIMIT 1');
$stmt->execute([$uid]);
$row = $stmt->fetch();
$secret = $row['totp_secret'] ?? null;

if (!$secret || !TotpHelper::verify($secret, $code)) {
    $_SESSION['login_2fa_error'] = 'Invalid code. Try again.';
    header('Location: login_2fa.php');
    exit;
}

bejewelry_finalize_login_session($uid);
