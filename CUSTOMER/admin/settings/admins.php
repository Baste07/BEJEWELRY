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

$stmt = $pdo->query("SELECT id, first_name, last_name, email, role, created_at, failed_login_attempts, locked_at, locked_by, lock_reason FROM users WHERE role IN ('admin','super_admin','manager','inventory','courier') ORDER BY created_at DESC");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

$currentId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
$currentRole = null;
if ($currentId) {
    $meStmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $meStmt->execute([$currentId]);
    $currentRole = $meStmt->fetchColumn() ?: null;
}

$out = [];
foreach ($rows as $r) {
    $roleRaw = $r['role'];
    $role = $roleRaw === 'admin' ? 'super_admin' : $roleRaw;
    $isCurrent = ((int)$r['id'] === $currentId);
    $canRemove = ($currentRole === 'super_admin' && !$isCurrent);
    $canEditRole = ($currentRole === 'super_admin' && !$isCurrent && $role !== 'courier');
    $isLocked = !empty($r['locked_at']);
    $out[] = [
        'id'         => (int)$r['id'],
        'name'       => trim($r['first_name'] . ' ' . $r['last_name']),
        'email'      => $r['email'],
        'role'       => $role,
        'created_at' => $r['created_at'],
        'is_current' => $isCurrent,
        'can_remove' => $canRemove,
        'can_edit_role' => $canEditRole,
        'failed_login_attempts' => (int) ($r['failed_login_attempts'] ?? 0),
        'locked_at' => $r['locked_at'],
        'locked_by' => isset($r['locked_by']) ? (int) $r['locked_by'] : null,
        'lock_reason' => $r['lock_reason'],
        'is_locked' => $isLocked,
        'can_toggle_lock' => ($currentRole === 'super_admin' && !$isCurrent),
    ];
}

echo json_encode($out);
exit;

