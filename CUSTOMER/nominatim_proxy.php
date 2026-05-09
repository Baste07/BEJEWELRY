<?php
require_once __DIR__ . '/inc.php';

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

if (!current_user_id()) {
    http_response_code(401);
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

$action = (string)($_GET['action'] ?? '');
$baseUrl = '';
$query = [];

if ($action === 'search') {
    $baseUrl = 'https://nominatim.openstreetmap.org/search';
    $query = [
        'q' => trim((string)($_GET['q'] ?? '')),
        'countrycodes' => 'ph',
        'format' => 'json',
        'limit' => (int)($_GET['limit'] ?? 10),
        'addressdetails' => 1,
    ];
} elseif ($action === 'reverse') {
    $baseUrl = 'https://nominatim.openstreetmap.org/reverse';
    $query = [
        'lat' => (string)($_GET['lat'] ?? ''),
        'lon' => (string)($_GET['lon'] ?? ''),
        'format' => 'json',
        'addressdetails' => 1,
        'zoom' => 18,
    ];
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid action']);
    exit;
}

$email = trim((string)SMTP_FROM_EMAIL);
if ($email !== '') {
    $query['email'] = $email;
}

if ($action === 'search' && $query['q'] === '') {
    echo json_encode([]);
    exit;
}

if ($action === 'reverse' && ($query['lat'] === '' || $query['lon'] === '')) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing coordinates']);
    exit;
}

$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => [
            'User-Agent: Bejewelry/1.0 (' . ($email !== '' ? $email : 'local') . ')',
            'Accept: application/json',
        ],
        'timeout' => 8,
    ],
    'ssl' => [
        'verify_peer' => true,
        'verify_peer_name' => true,
    ],
]);

$url = $baseUrl . '?' . http_build_query($query);
$response = @file_get_contents($url, false, $context);

if ($response === false) {
    http_response_code(502);
    echo json_encode(['error' => 'Upstream geocoding request failed']);
    exit;
}

$decoded = json_decode($response, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(502);
    echo json_encode(['error' => 'Invalid geocoding response']);
    exit;
}

echo json_encode($decoded);
