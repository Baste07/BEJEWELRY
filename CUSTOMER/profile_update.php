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

$uid = current_user_id();

$first  = trim((string)($_POST['first_name'] ?? ''));
$last   = trim((string)($_POST['last_name'] ?? ''));
$email  = trim((string)($_POST['email'] ?? ''));
$phone  = trim((string)($_POST['phone'] ?? ''));
$gender = trim((string)($_POST['gender'] ?? ''));
$city   = trim((string)($_POST['city'] ?? ''));
$bday   = trim((string)($_POST['birthday'] ?? ''));

if ($first === '' || $last === '' || $email === '') {
    header('Location: profile.php?err=' . urlencode('Please complete required fields.'));
    exit;
}

$pdo = db();
$stmt = $pdo->prepare('UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ?, gender = ?, city = ?, birthday = ? WHERE id = ?');
$encUser = bejewelry_encrypt_user_private_fields([
    'phone' => $phone !== '' ? $phone : null,
    'city' => $city !== '' ? $city : null,
    'birthday' => $bday !== '' ? $bday : null,
]);
$stmt->execute([
    $first,
    $last,
    $email,
    $encUser['phone'],
    $gender !== '' ? $gender : null,
    $encUser['city'],
    $encUser['birthday'],
    $uid,
]);

// Refresh session snapshot used by current_user()
unset($_SESSION['user_row']);
current_user();

header('Location: profile.php?saved=1');
exit;

