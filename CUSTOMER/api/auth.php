<?php
/* ═══════════════════════════════════════════════════════════
   BEJEWELRY — Auth Endpoints
   POST /api/auth/login
   POST /api/auth/register
   POST /api/auth/logout
   GET  /api/auth/me
═══════════════════════════════════════════════════════════ */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/registration_helpers.php';
require_once __DIR__ . '/../inc.php';
require_once __DIR__ . '/../totp_helper.php';

setHeaders();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// ── POST /auth/login ──────────────────────────────────────
// Step 2: totp_token + totp → JWT. Step 1: email + password → totp_token or setup required.
if ($method === 'POST' && $action === 'login') {
    csrf_validate();
    $b = body();
    $totpToken = trim($b['totp_token'] ?? '');
    $totpCode = preg_replace('/\s+/', '', (string) ($b['totp'] ?? ''));

    if ($totpToken !== '' && $totpCode !== '') {
        $ch = jwtDecodeTotpChallenge($totpToken);
        if (!$ch) {
            respondError('Invalid or expired authenticator step. Sign in again.', 401);
        }
        $uid = (int) $ch['user_id'];
        $stmt = db()->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$uid]);
        $user = $stmt->fetch();
        if (!$user || empty($user['totp_secret'])) {
            respondError('Invalid session.', 401);
        }
        if (bejewelry_is_account_locked($user)) {
            respondError('This account is locked. Contact a super admin to unlock it.', 423);
        }
        if (!TotpHelper::verify($user['totp_secret'], $totpCode)) {
            respondError('Invalid authenticator code.', 401);
        }
        $user = bejewelry_decrypt_user_private_fields($user);
        $token = jwtEncode(['user_id' => $user['id'], 'email' => $user['email'], 'role' => $user['role']]);
        bejewelry_reset_login_failures((int) $user['id']);
        unset($user['password_hash'], $user['totp_secret'], $user['activation_token'], $user['activation_expires']);
        respond(['token' => $token, 'user' => $user]);
    }

    $email = trim($b['email'] ?? '');
    $password = trim($b['password'] ?? '');

    if (!$email || $password === '') {
        respondError('Email and password are required.');
    }

    $stmt = db()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && bejewelry_is_account_locked($user)) {
        respondError('This account is locked. Contact a super admin to unlock it.', 423);
    }

    if (!$user || empty($user['password_hash']) || !password_verify($password, $user['password_hash'])) {
        if ($user && !bejewelry_is_account_locked($user)) {
            $lockState = bejewelry_register_login_failure((int) $user['id']);
            if (!empty($lockState['locked'])) {
                respondError('Account locked after 3 failed attempts. Contact a super admin to unlock it.', 423);
            }
        }
        respondError('Invalid email or password.', 401);
    }

    if (($user['role'] ?? '') === 'customer' && empty($user['email_verified_at'])) {
        respondError('Please verify your email before signing in. Check your inbox for the activation link.', 403);
    }

    if (empty($user['totp_secret'])) {
        bejewelry_reset_login_failures((int) $user['id']);
        respond([
            'requires_totp_setup' => true,
            'message' => 'Open the website once to set up Google Authenticator, then sign in again.',
        ], 403);
    }

    bejewelry_reset_login_failures((int) $user['id']);

    $totpChallenge = jwtEncodeTotpChallenge((int) $user['id']);
    respond([
        'requires_totp' => true,
        'totp_token' => $totpChallenge,
        'message' => 'Enter the 6-digit code from Google Authenticator.',
    ]);
}

// ── POST /auth/register ───────────────────────────────────
if ($method === 'POST' && $action === 'register') {
    csrf_validate();
    $b = body();
    $first  = trim($b['first_name'] ?? '');
    $last   = trim($b['last_name']  ?? '');
    $email  = trim($b['email']      ?? '');
    $pass   = $b['password']        ?? '';
    $captcha = trim($b['g_recaptcha_response'] ?? '');

    if (!$first || !$last || !$email || !$pass) {
        respondError('First name, last name, email and password are required.');
    }
    if (
        defined('RECAPTCHA_STRICT_REAL') && RECAPTCHA_STRICT_REAL &&
        (
            RECAPTCHA_SITE_KEY === '6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI' ||
            RECAPTCHA_SECRET_KEY === '6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe'
        )
    ) {
        respondError('reCAPTCHA is in strict real mode. Replace test keys in api/config.php with your real site and secret keys.', 500);
    }
    if (!verify_recaptcha_v2($captcha)) {
        respondError('Please complete the CAPTCHA verification.');
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        respondError('Invalid email address.');
    }
    $pwdCheck = bejewelry_validate_password($pass);
    if (empty($pwdCheck['ok'])) {
        respondError(implode(' ', $pwdCheck['errors']), 400);
    }

    $chk = db()->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
    $chk->execute([$email]);
    if ($chk->fetch()) {
        respondError('Email already registered.', 409);
    }

    $hash = password_hash($pass, PASSWORD_BCRYPT);
    $token = bin2hex(random_bytes(32));
    $expires = (new DateTimeImmutable('+' . ACTIVATION_LINK_HOURS . ' hours'))->format('Y-m-d H:i:s');
    $encUser = bejewelry_encrypt_user_private_fields([
        'phone' => isset($b['phone']) && trim((string) $b['phone']) !== '' ? trim((string) $b['phone']) : null,
    ]);

    $ins = db()->prepare(
        'INSERT INTO users (first_name, last_name, email, password_hash, phone, username, role, email_verified_at, activation_token, activation_expires)
         VALUES (?,?,?,?,?,?,?,?,?,?)'
    );
    $ins->execute([
        $first,
        $last,
        $email,
        $hash,
        $encUser['phone'],
        $b['username'] ?? null,
        'customer',
        null,
        $token,
        $expires,
    ]);
    $userId = (int) db()->lastInsertId();

    $base = customer_public_base_url();
    $link = $base . '/activate.php?token=' . urlencode($token);

    if (!send_activation_email($email, $first, $link)) {
        $del = db()->prepare('DELETE FROM users WHERE id = ?');
        $del->execute([$userId]);
        respondError('Could not send activation email. Configure SMTP in api/config.php and try again.', 503);
    }

    bejewelry_audit_log($userId, $email, 'register_account');

    respond([
        'message' => 'Check your email to activate your account before signing in.',
        'requires_verification' => true,
    ], 201);
}

// ── POST /auth/logout ─────────────────────────────────────
if ($method === 'POST' && $action === 'logout') {
    http_response_code(204);
    exit;
}

// ── GET /auth/me ──────────────────────────────────────────
if ($method === 'GET' && $action === 'me') {
    $auth = requireAuth();
    $stmt = db()->prepare('SELECT id,first_name,last_name,username,email,phone,gender,birthday,city,role,created_at,email_verified_at FROM users WHERE id = ?');
    $stmt->execute([$auth['user_id']]);
    $user = $stmt->fetch();
    if (!$user) {
        respondError('User not found.', 404);
    }
    $user = bejewelry_decrypt_user_private_fields($user);
    respond($user);
}

respondError('Not found.', 404);
