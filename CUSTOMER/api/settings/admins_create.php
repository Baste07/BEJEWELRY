<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../../inc.php';
if (session_status() === PHP_SESSION_NONE) {
    if (session_name() !== 'BEJEWELRY_C2_SESSID') {
        session_name('BEJEWELRY_C2_SESSID');
    }
    session_start();
}

header('Content-Type: application/json');

try {
    $pdo = db();

    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'error' => 'Not authorized']);
        exit;
    }

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

    $parts = preg_split('/\s+/', $name);
    $first = array_shift($parts) ?: $name;
    $last  = implode(' ', $parts) ?: $first;

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

    $actor = current_user();
    bejewelry_audit_log(
        (int) ($actor['id'] ?? 0) ?: null,
        (string) ($actor['email'] ?? ''),
        'create_staff_account'
    );

    echo json_encode(['ok' => true, 'id' => $id]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Server error']);
}

