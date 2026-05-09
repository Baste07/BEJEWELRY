<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc.php';

if (current_user_id()) {
    $u = current_user();
    $role = (string) ($u['role'] ?? 'customer');
    if ($role === 'courier') {
        header('Location: courier_portal.php');
        exit;
    }
    header('Location: ../' . bejewelry_staff_post_login_path($role));
    exit;
}

header('Location: ../login.php');
exit;
