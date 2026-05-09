<?php
require_once __DIR__ . '/../../inc.php';

header('Content-Type: application/json');

if (!current_user_id()) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Unauthorized']);
    exit;
}

$current = current_user();
if (($current['role'] ?? '') !== 'super_admin') {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Only super admin can lock or unlock accounts']);
    exit;
}

csrf_validate();
$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data)) {
    $data = [];
}

$id = isset($data['id']) ? (int) $data['id'] : 0;
$action = strtolower(trim((string) ($data['action'] ?? '')));

if ($id <= 0 || !in_array($action, ['lock', 'unlock'], true)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Missing or invalid fields']);
    exit;
}

if ($id === (int) ($current['id'] ?? 0)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Cannot change lock state for your own account']);
    exit;
}

$pdo = db();
$stmt = $pdo->prepare('SELECT id, role FROM users WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$target = $stmt->fetch();

if (!$target) {
    http_response_code(404);
    echo json_encode(['ok' => false, 'error' => 'Account not found']);
    exit;
}

bejewelry_set_account_lock($id, $action === 'lock', (int) ($current['id'] ?? 0), $action === 'lock' ? 'Locked by super admin.' : null);

echo json_encode(['ok' => true]);
exit;
