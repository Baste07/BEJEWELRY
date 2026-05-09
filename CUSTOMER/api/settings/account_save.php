<?php
declare(strict_types=1);

require_once __DIR__ . '/../../inc.php';
header('Content-Type: application/json');

try {
    csrf_validate();

    $userId = current_user_id();
    if (!$userId) {
        http_response_code(401);
        echo json_encode(['ok' => false, 'error' => 'Not authenticated']);
        exit;
    }

    $current = current_user() ?: [];
    $role = (string) ($current['role'] ?? 'customer');
    if ($role === 'admin') {
        $role = 'super_admin';
    }
    if (!in_array($role, ['super_admin', 'manager', 'inventory'], true)) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'error' => 'Access denied']);
        exit;
    }

    $data = json_decode((string) file_get_contents('php://input'), true);
    if (!is_array($data)) {
        $data = [];
    }

    $displayName = trim((string) ($data['name'] ?? ''));
    $email = trim((string) ($data['email'] ?? ''));
    $password = (string) ($data['password'] ?? '');

    if ($displayName === '' || $email === '') {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Name and email are required']);
        exit;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Invalid email address']);
        exit;
    }
    if ($password !== '' && strlen($password) < 8) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Password must be at least 8 characters']);
        exit;
    }

    $parts = preg_split('/\s+/', $displayName, 2) ?: [];
    $firstName = trim((string) ($parts[0] ?? ''));
    $lastName = trim((string) ($parts[1] ?? ''));

    if ($firstName === '') {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Please enter a valid display name']);
        exit;
    }

    $pdo = db();
    $dup = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id <> ? LIMIT 1');
    $dup->execute([$email, $userId]);
    if ($dup->fetchColumn()) {
        http_response_code(409);
        echo json_encode(['ok' => false, 'error' => 'Email is already in use']);
        exit;
    }

    if ($password !== '') {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('UPDATE users SET first_name = ?, last_name = ?, email = ?, password_hash = ?, updated_at = NOW() WHERE id = ?');
        $stmt->execute([$firstName, $lastName, $email, $hash, $userId]);
    } else {
        $stmt = $pdo->prepare('UPDATE users SET first_name = ?, last_name = ?, email = ?, updated_at = NOW() WHERE id = ?');
        $stmt->execute([$firstName, $lastName, $email, $userId]);
    }

    // Refresh the cached user row for the current session so the admin panel reflects the update immediately.
    unset($_SESSION['user_row']);
    current_user();

    echo json_encode([
        'ok' => true,
        'name' => $displayName,
        'email' => $email,
    ]);
} catch (Throwable $e) {
    error_log('[account_save] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
