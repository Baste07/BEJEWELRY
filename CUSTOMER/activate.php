<?php
declare(strict_types=1);

require_once __DIR__ . '/inc.php';

$token = trim($_GET['token'] ?? '');
if ($token === '' || strlen($token) !== 64 || !ctype_xdigit($token)) {
    header('Location: login.php?activation_error=' . urlencode('Invalid activation link.'));
    exit;
}

$stmt = db()->prepare('SELECT id, activation_expires FROM users WHERE activation_token = ? LIMIT 1');
$stmt->execute([$token]);
$row = $stmt->fetch();

if (!$row) {
    header('Location: login.php?activation_error=' . urlencode('Invalid or already used activation link.'));
    exit;
}

$expires = $row['activation_expires'] ?? null;
if ($expires && strtotime((string) $expires) < time()) {
    header('Location: login.php?activation_error=' . urlencode('This activation link has expired. Please register again or contact support.'));
    exit;
}

$upd = db()->prepare('UPDATE users SET email_verified_at = NOW(), activation_token = NULL, activation_expires = NULL WHERE id = ?');
$upd->execute([(int) $row['id']]);

header('Location: login.php?activated=1');
exit;
