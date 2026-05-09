<?php
require_once __DIR__ . '/inc.php';

if (!current_user_id()) {
    header('Location: login.php?redirect=' . urlencode('profile.php'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: profile.php');
    exit;
}

csrf_validate();

$action = $_POST['action'] ?? '';
$pdo = db();
$uid = current_user_id();

if ($action === 'change_password') {
    $curr = trim($_POST['current_password'] ?? '');
    $new  = trim($_POST['new_password'] ?? '');
    $conf = trim($_POST['confirm_password'] ?? '');

    if ($curr === '' || $new === '' || $conf === '') {
        header('Location: profile.php?pw_err=missing#settings');
        exit;
    }
    if (strlen($new) < 6 || $new !== $conf) {
        header('Location: profile.php?pw_err=invalid_new#settings');
        exit;
    }

    $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = ?');
    $stmt->execute([$uid]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row || !password_verify($curr, $row['password_hash'])) {
        header('Location: profile.php?pw_err=wrong_curr#settings');
        exit;
    }

    $newHash = password_hash($new, PASSWORD_DEFAULT);
    $up = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
    $up->execute([$newHash, $uid]);

    header('Location: profile.php?pw_ok=1#settings');
    exit;
}

if ($action === 'email_prefs') {
    $orderUpdates = isset($_POST['pref_order_updates']) ? 1 : 0;
    $launches     = isset($_POST['pref_launches']) ? 1 : 0;
    $promos       = isset($_POST['pref_promos']) ? 1 : 0;
    $wishlist     = isset($_POST['pref_wishlist']) ? 1 : 0;

    $stmt = $pdo->prepare('INSERT INTO email_prefs (user_id, order_updates, launches, promos, wishlist)
                           VALUES (?, ?, ?, ?, ?)
                           ON DUPLICATE KEY UPDATE
                             order_updates = VALUES(order_updates),
                             launches      = VALUES(launches),
                             promos        = VALUES(promos),
                             wishlist      = VALUES(wishlist)');
    $stmt->execute([$uid, $orderUpdates, $launches, $promos, $wishlist]);

    header('Location: profile.php?prefs_ok=1#settings');
    exit;
}


header('Location: profile.php');
exit;

