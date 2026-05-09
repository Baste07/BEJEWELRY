<?php
declare(strict_types=1);

require_once __DIR__ . '/totp_helper.php';

const TOTP_MAX_ATTEMPTS = 5;
const TOTP_ISSUER = 'Bejewelry';

function bejewelry_clear_pending_totp(): void
{
    foreach ([
        'pending_totp_uid',
        'pending_totp_setup_uid',
        'pending_totp_redirect',
        'totp_setup_secret',
        'totp_setup_attempts',
        'pending_totp_attempts',
    ] as $k) {
        unset($_SESSION[$k]);
    }
}

/** Load safe user row for session (no password / secrets). */
function bejewelry_user_row_for_session(PDO $pdo, int $userId): ?array
{
    $stmt = $pdo->prepare(
        'SELECT id, first_name, last_name, username, email, phone, gender, birthday, city, role, created_at, email_verified_at
         FROM users WHERE id = ? LIMIT 1'
    );
    $stmt->execute([$userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $row = bejewelry_decrypt_user_private_fields($row);
    }
    return $row ?: null;
}

function bejewelry_finalize_login_session(int $userId): never
{
    if (!function_exists('db')) {
        require_once __DIR__ . '/inc.php';
    }
    $pdo = db();
    $redirect = $_SESSION['pending_totp_redirect'] ?? 'index.php';
    bejewelry_clear_pending_totp();

    $user = bejewelry_user_row_for_session($pdo, $userId);
    if (!$user) {
        header('Location: login.php?error=' . urlencode('Session error. Please sign in again.'));
        exit;
    }

    if (function_exists('bejewelry_reset_login_failures')) {
        bejewelry_reset_login_failures($userId);
    }

    $_SESSION['user_id'] = $userId;
    $_SESSION['user_row'] = $user;

    if (function_exists('bejewelry_audit_log')) {
        bejewelry_audit_log($userId, $user['email'] ?? null, 'login');
    }

    $role = $user['role'] ?? 'customer';
    $adminRoles = ['admin', 'super_admin', 'manager', 'inventory', 'courier'];
    if (in_array($role, $adminRoles, true)) {
        header('Location: ' . bejewelry_staff_post_login_path($role));
        exit;
    }

    header('Location: ' . $redirect);
    exit;
}
