<?php
require_once __DIR__ . '/inc.php';

header('Content-Type: application/json');

if (!current_user_id()) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Not logged in']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['photo'])) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'No file uploaded']);
    exit;
}

csrf_validate();

$file = $_FILES['photo'];
if ($file['error'] === UPLOAD_ERR_NO_FILE) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'No file']);
    exit;
}
if ($file['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Upload error']);
    exit;
}
if ($file['size'] <= 0 || $file['size'] > 5 * 1024 * 1024) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid file size']);
    exit;
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime  = $finfo->file($file['tmp_name']);
$allowed = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
];
if (!isset($allowed[$mime])) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid image type']);
    exit;
}

$ext = $allowed[$mime];
$uid = current_user_id();
$dir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'profile';
if (!is_dir($dir)) {
    @mkdir($dir, 0777, true);
}
$filename = 'user_' . $uid . '.' . $ext;
$path = $dir . DIRECTORY_SEPARATOR . $filename;

// Remove old files for this user (any extension)
foreach (glob($dir . DIRECTORY_SEPARATOR . 'user_' . $uid . '.*') as $old) {
    @unlink($old);
}

if (!move_uploaded_file($file['tmp_name'], $path)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Could not save file']);
    exit;
}

$url = 'uploads/profile/' . $filename;
echo json_encode(['ok' => true, 'url' => $url]);
exit;

