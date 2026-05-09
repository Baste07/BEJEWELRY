<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../../api/csrf_helper.php';
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

$id = isset($data['id']) ? (int)$data['id'] : 0;
if ($id <= 0) {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
}
csrf_validate();
if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Missing id']);
    exit;
}

$currentId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
$currentRole = null;
if ($currentId) {
    $meStmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $meStmt->execute([$currentId]);
    $currentRole = $meStmt->fetchColumn() ?: null;
}

if ($currentRole !== 'super_admin' && $currentRole !== 'admin') {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Only super admin can remove accounts']);
    exit;
}

// Super admin cannot delete themselves
if ($id === $currentId) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Cannot remove current admin']);
    exit;
}

$stmt = $pdo->prepare("UPDATE users SET role = 'customer' WHERE id = ? AND role IN ('admin','super_admin','manager','inventory')");
$stmt->execute([$id]);

if ($stmt->rowCount() < 1) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => 'Admin not found or cannot be removed']);
    exit;
}

echo json_encode(['ok' => true]);
exit;

