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

$currentId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
$currentRole = null;
if ($currentId) {
    $meStmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $meStmt->execute([$currentId]);
    $currentRole = $meStmt->fetchColumn() ?: null;
}

if ($currentRole !== 'super_admin' && $currentRole !== 'admin') {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Only super admin can change roles']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data)) $data = [];

$id = isset($data['id']) ? (int)$data['id'] : 0;
$role = isset($data['role']) ? (string)$data['role'] : '';

if ($id <= 0 || $role === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Missing fields']);
    exit;
}

if ($id === $currentId) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Cannot change your own role']);
    exit;
}

$allowed = ['super_admin', 'manager', 'inventory'];
if (!in_array($role, $allowed, true)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid role']);
    exit;
}

$stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ? AND role IN ('admin','super_admin','manager','inventory')");
$stmt->execute([$role, $id]);

echo json_encode(['ok' => true]);
exit;

