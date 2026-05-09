<?php
declare(strict_types=1);

require_once __DIR__ . '/inc.php';
require_once __DIR__ . '/api/registration_helpers.php';

if (current_user_id()) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

csrf_validate();

$first = trim($_POST['first_name'] ?? '');
$last  = trim($_POST['last_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$pass  = $_POST['password'] ?? '';
$confirm = $_POST['password_confirm'] ?? '';
$captcha = $_POST['g-recaptcha-response'] ?? '';

if (!$first || !$last || !$email || !$pass) {
    header('Location: login.php?reg_error=' . urlencode('Please fill in all fields.'));
    exit;
}
if (!verify_recaptcha_v2($captcha)) {
    header('Location: login.php?reg_error=' . urlencode('Please complete the CAPTCHA verification.'));
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: login.php?reg_error=' . urlencode('Invalid email address.'));
    exit;
}

$pwdCheck = bejewelry_validate_password($pass);
if (empty($pwdCheck['ok'])) {
    $pwdErr = implode(' ', $pwdCheck['errors'] ?? ['Password does not meet the configured policy.']);
    header('Location: login.php?reg_error=' . urlencode($pwdErr));
    exit;
}
if ($pass !== $confirm) {
    header('Location: login.php?reg_error=' . urlencode('Passwords do not match.'));
    exit;
}

$chk = db()->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
$chk->execute([$email]);
if ($chk->fetch()) {
    header('Location: login.php?reg_error=' . urlencode('Email already registered.'));
    exit;
}

$hash = password_hash($pass, PASSWORD_BCRYPT);
$token = bin2hex(random_bytes(32));
$expires = (new DateTimeImmutable('+' . ACTIVATION_LINK_HOURS . ' hours'))->format('Y-m-d H:i:s');

$ins = db()->prepare(
    'INSERT INTO users (first_name, last_name, email, password_hash, role, email_verified_at, activation_token, activation_expires)
     VALUES (?,?,?,?,?,?,?,?)'
);
$ins->execute([$first, $last, $email, $hash, 'customer', null, $token, $expires]);
$userId = (int) db()->lastInsertId();

$base = customer_public_base_url();
$link = $base . '/activate.php?token=' . urlencode($token);

$sent = send_activation_email($email, $first, $link);

if (!$sent) {
    $del = db()->prepare('DELETE FROM users WHERE id = ?');
    $del->execute([$userId]);
    header('Location: login.php?reg_error=' . urlencode(
        'Could not send activation email. Configure SMTP (SMTP_USER / SMTP_PASS) in api/config.php and try again.'
    ));
    exit;
}

header('Location: login.php?reg_pending=1');
exit;
