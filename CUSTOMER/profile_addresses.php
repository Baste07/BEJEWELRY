<?php
require_once __DIR__ . '/inc.php';

header('Content-Type: application/json');

if (!current_user_id()) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Not logged in']);
    exit;
}

$pdo = db();
$uid = current_user_id();

function json_body(): array {
    $raw = file_get_contents('php://input');
    if (!$raw) return [];
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function list_addresses(PDO $pdo, int $uid): array {
    $stmt = $pdo->prepare('SELECT id, label, name, street, city, province, zip, phone, latitude, longitude, is_default
                           FROM addresses WHERE user_id = ?
                           ORDER BY is_default DESC, id DESC');
    $stmt->execute([$uid]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    foreach ($rows as &$r) {
        $r = bejewelry_decrypt_address_private_fields($r);
        // Ensure numeric types for lat/lon
        $r['latitude'] = isset($r['latitude']) && $r['latitude'] !== null ? (float)$r['latitude'] : null;
        $r['longitude'] = isset($r['longitude']) && $r['longitude'] !== null ? (float)$r['longitude'] : null;
        $r['id'] = (int)$r['id'];
        $r['is_default'] = (int)($r['is_default'] ?? 0) === 1;
    }
    return $rows;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo json_encode(['ok' => true, 'data' => list_addresses($pdo, $uid)]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

csrf_validate();

$in = json_body();
$action = (string)($in['action'] ?? '');

try {
    if ($action === 'create') {
        $label  = trim((string)($in['label'] ?? 'Home'));
        $name   = trim((string)($in['name'] ?? ''));
        $street = trim((string)($in['street'] ?? ''));
        $city   = trim((string)($in['city'] ?? ''));
        $province = trim((string)($in['province'] ?? ''));
        $zip    = trim((string)($in['zip'] ?? ''));
        $phone  = trim((string)($in['phone'] ?? ''));
        $isDefault = !empty($in['is_default']) ? 1 : 0;

        if ($name === '' || $street === '' || $city === '') {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Missing required fields']);
            exit;
        }

        $pdo->beginTransaction();
        if ($isDefault) {
            $pdo->prepare('UPDATE addresses SET is_default = 0 WHERE user_id = ?')->execute([$uid]);
        }
        $enc = bejewelry_encrypt_address_private_fields([
            'name' => $name,
            'street' => $street,
            'city' => $city,
            'province' => $province !== '' ? $province : null,
            'zip' => $zip !== '' ? $zip : null,
            'phone' => $phone !== '' ? $phone : null,
        ]);
        // latitude/longitude may be provided from frontend
        $latitude = isset($in['latitude']) && $in['latitude'] !== '' ? (float)$in['latitude'] : null;
        $longitude = isset($in['longitude']) && $in['longitude'] !== '' ? (float)$in['longitude'] : null;

        $stmt = $pdo->prepare('INSERT INTO addresses (user_id, label, name, street, city, province, zip, phone, latitude, longitude, is_default)
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$uid, $label !== '' ? $label : 'Home', $enc['name'], $enc['street'], $enc['city'], $enc['province'], $enc['zip'], $enc['phone'], $latitude, $longitude, $isDefault]);
        $pdo->commit();

        echo json_encode(['ok' => true, 'data' => list_addresses($pdo, $uid)]);
        exit;
    }

    if ($action === 'update') {
        $id = (int)($in['id'] ?? 0);
        if ($id <= 0) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'Missing id']); exit; }

        $label  = trim((string)($in['label'] ?? 'Home'));
        $name   = trim((string)($in['name'] ?? ''));
        $street = trim((string)($in['street'] ?? ''));
        $city   = trim((string)($in['city'] ?? ''));
        $province = trim((string)($in['province'] ?? ''));
        $zip    = trim((string)($in['zip'] ?? ''));
        $phone  = trim((string)($in['phone'] ?? ''));
        $isDefault = !empty($in['is_default']) ? 1 : 0;

        if ($name === '' || $street === '' || $city === '') {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Missing required fields']);
            exit;
        }

        $pdo->beginTransaction();
        if ($isDefault) {
            $pdo->prepare('UPDATE addresses SET is_default = 0 WHERE user_id = ?')->execute([$uid]);
        }
        $enc = bejewelry_encrypt_address_private_fields([
            'name' => $name,
            'street' => $street,
            'city' => $city,
            'province' => $province !== '' ? $province : null,
            'zip' => $zip !== '' ? $zip : null,
            'phone' => $phone !== '' ? $phone : null,
        ]);
        $latitude = isset($in['latitude']) && $in['latitude'] !== '' ? (float)$in['latitude'] : null;
        $longitude = isset($in['longitude']) && $in['longitude'] !== '' ? (float)$in['longitude'] : null;

        $stmt = $pdo->prepare('UPDATE addresses
                               SET label = ?, name = ?, street = ?, city = ?, province = ?, zip = ?, phone = ?, latitude = ?, longitude = ?, is_default = ?
                               WHERE id = ? AND user_id = ?');
        $stmt->execute([
            $label !== '' ? $label : 'Home',
            $enc['name'],
            $enc['street'],
            $enc['city'],
            $enc['province'],
            $enc['zip'],
            $enc['phone'],
            $latitude,
            $longitude,
            $isDefault,
            $id,
            $uid
        ]);
        $pdo->commit();

        echo json_encode(['ok' => true, 'data' => list_addresses($pdo, $uid)]);
        exit;
    }

    if ($action === 'delete') {
        $id = (int)($in['id'] ?? 0);
        if ($id <= 0) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'Missing id']); exit; }
        $pdo->prepare('DELETE FROM addresses WHERE id = ? AND user_id = ?')->execute([$id, $uid]);
        echo json_encode(['ok' => true, 'data' => list_addresses($pdo, $uid)]);
        exit;
    }

    if ($action === 'set_default') {
        $id = (int)($in['id'] ?? 0);
        if ($id <= 0) { http_response_code(400); echo json_encode(['ok'=>false,'error'=>'Missing id']); exit; }
        $pdo->beginTransaction();
        $pdo->prepare('UPDATE addresses SET is_default = 0 WHERE user_id = ?')->execute([$uid]);
        $pdo->prepare('UPDATE addresses SET is_default = 1 WHERE id = ? AND user_id = ?')->execute([$id, $uid]);
        $pdo->commit();
        echo json_encode(['ok' => true, 'data' => list_addresses($pdo, $uid)]);
        exit;
    }

    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Unknown action']);
    exit;
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Server error']);
    exit;
}

