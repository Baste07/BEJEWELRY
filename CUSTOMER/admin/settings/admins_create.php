<?php
require_once __DIR__ . '/../db.php';
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    if (session_name() !== 'BEJEWELRY_C2_SESSID') {
        session_name('BEJEWELRY_C2_SESSID');
    }
    session_start();
}
$pdo = adminDb();

$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data)) $data = [];

$name  = trim((string)($data['name'] ?? ''));
$email = trim((string)($data['email'] ?? ''));
$role  = (string)($data['role'] ?? '');
$pw    = (string)($data['password'] ?? '');

if ($name === '' || $email === '' || $role === '' || $pw === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Missing fields']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid email']);
    exit;
}

if (!in_array($role, ['super_admin','manager','inventory'], true)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid role']);
    exit;
}

// Split name into first/last (simple heuristic)
$parts = preg_split('/\s+/', $name);
$first = array_shift($parts) ?: $name;
$last  = implode(' ', $parts) ?: $first;

// Ensure email not already used
$chk = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
$chk->execute([$email]);
if ($chk->fetch()) {
    http_response_code(409);
    echo json_encode(['ok' => false, 'error' => 'Email already in use']);
    exit;
}

$hash = password_hash($pw, PASSWORD_DEFAULT);

$stmt = $pdo->prepare('INSERT INTO users (first_name, last_name, email, password_hash, role) VALUES (?, ?, ?, ?, ?)');
$stmt->execute([$first, $last, $email, $hash, $role]);
$id = (int)$pdo->lastInsertId();

echo json_encode(['ok' => true, 'id' => $id]);
exit;

