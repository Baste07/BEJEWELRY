<?php
require_once __DIR__ . '/../config.php';
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
        echo json_encode([]);
        exit;
    }

    $stmt = $pdo->query("SELECT id, first_name, last_name, email, role, created_at FROM users WHERE role IN ('super_admin','manager','inventory') ORDER BY created_at DESC");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    $currentId = (int)($_SESSION['user_id'] ?? 0);

    $out = [];
    foreach ($rows as $r) {
        $out[] = [
            'id'         => (int)$r['id'],
            'name'       => trim($r['first_name'] . ' ' . $r['last_name']),
            'email'      => $r['email'],
            'role'       => $r['role'],
            'created_at' => $r['created_at'],
            'is_current' => ((int)$r['id'] === $currentId),
        ];
    }

    echo json_encode($out);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([]);
}

