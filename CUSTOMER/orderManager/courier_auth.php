<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc.php';

function courier_require_login(): array
{
    if (!current_user_id()) {
        header('Location: ../login.php');
        exit;
    }

    $user = current_user();
    if (!$user) {
        header('Location: ../login.php');
        exit;
    }

    if (($user['role'] ?? '') !== 'courier') {
        header('Location: ../' . bejewelry_staff_post_login_path((string) ($user['role'] ?? 'customer')));
        exit;
    }

    return $user;
}
