<?php
require_once __DIR__ . '/inc.php';

$uid = current_user_id();
$em = null;
if ($uid && function_exists('current_user')) {
    $ur = current_user();
    $em = $ur['email'] ?? null;
}
if (function_exists('bejewelry_logout_now')) {
    bejewelry_logout_now(false);
} else {
    if ($uid && function_exists('bejewelry_audit_log')) {
        bejewelry_audit_log($uid, $em, 'logout');
    }

    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}
header('Location: login.php');
exit;
