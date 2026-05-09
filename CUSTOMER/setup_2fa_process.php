<?php
declare(strict_types=1);

require_once __DIR__ . '/inc.php';
require_once __DIR__ . '/auth_totp.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: setup_2fa.php');
    exit;
}

csrf_validate();

if (empty($_SESSION['pending_totp_setup_uid']) || empty($_SESSION['totp_setup_secret'])) {
    header('Location: login.php');
    exit;
}

$uid = (int) $_SESSION['pending_totp_setup_uid'];
$secret = (string) $_SESSION['totp_setup_secret'];
$code = trim($_POST['totp'] ?? '');

$_SESSION['totp_setup_attempts'] = (int) ($_SESSION['totp_setup_attempts'] ?? 0) + 1;
if ($_SESSION['totp_setup_attempts'] > TOTP_MAX_ATTEMPTS) {
    unset($_SESSION['totp_setup_secret'], $_SESSION['pending_totp_setup_uid'], $_SESSION['totp_setup_attempts']);
    header('Location: login.php?error=' . urlencode('Too many attempts. Please sign in again.'));
    exit;
}

if (!TotpHelper::verify($secret, $code)) {
    $_SESSION['setup_2fa_error'] = 'Invalid code. Check that your phone time is correct.';
    header('Location: setup_2fa.php');
    exit;
}

$upd = db()->prepare('UPDATE users SET totp_secret = ? WHERE id = ?');
$upd->execute([$secret, $uid]);

unset($_SESSION['totp_setup_secret'], $_SESSION['totp_setup_attempts']);

bejewelry_finalize_login_session($uid);
