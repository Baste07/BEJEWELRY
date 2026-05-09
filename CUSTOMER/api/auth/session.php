<?php
require_once __DIR__ . '/../../inc.php';

header('Content-Type: application/json');

try {
    $uid = current_user_id();
    if (!$uid) {
        http_response_code(401);
        echo json_encode(['name' => 'Admin', 'role' => 'Admin']);
        exit;
    }

    $stmt = db()->prepare('SELECT id, first_name, last_name, email, role, created_at FROM users WHERE id = ?');
    $stmt->execute([$uid]);
    $u = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$u) {
        http_response_code(404);
        echo json_encode(['name' => 'Admin', 'role' => 'Admin']);
        exit;
    }

    $name = trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? '')) ?: ($u['email'] ?? 'Admin');
    $role = $u['role'] ?? 'admin';
    $since = $u['created_at'] ? date('M Y', strtotime($u['created_at'])) : '—';

    echo json_encode([
        'id'    => (int)$u['id'],
        'name'  => $name,
        'email' => $u['email'],
        'role'  => $role === 'admin' ? 'Super Admin' : ucfirst($role),
        'since' => $since,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['name' => 'Admin', 'role' => 'Admin']);
}

