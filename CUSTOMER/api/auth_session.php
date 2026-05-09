<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

setHeaders();

// Lightweight session endpoint for admin dashboards.
// Reads JWT from Authorization header and returns { name, role }.

$auth = optionalAuth();
if (!$auth) {
    respondError('Unauthorized', 401);
}

$stmt = db()->prepare('SELECT first_name, last_name, role FROM users WHERE id = ?');
$stmt->execute([$auth['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    respondError('User not found.', 404);
}

$name = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: 'Admin';

respond([
    'name' => $name,
    'role' => $user['role'] ?? 'admin',
]);

