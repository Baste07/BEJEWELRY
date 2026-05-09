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
        echo json_encode(['ok' => false, 'error' => 'Not authorized']);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    if (!is_array($data)) $data = [];
    $id = isset($data['id']) ? (int)$data['id'] : 0;

    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Missing id']);
        exit;
    }

    if ($id === (int)$_SESSION['user_id']) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Cannot remove current admin']);
        exit;
    }

    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role IN ('super_admin','manager','inventory')");
    $stmt->execute([$id]);

    echo json_encode(['ok' => true]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Server error']);
}

