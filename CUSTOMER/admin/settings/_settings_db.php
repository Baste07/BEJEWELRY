<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../../api/csrf_helper.php';

function settingsPdo(): PDO {
    $pdo = adminDb();
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS app_settings (
            `key` VARCHAR(80) PRIMARY KEY,
            `value` TEXT NOT NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");
    return $pdo;
}

function settingsGetJson(PDO $pdo, string $key, array $default): array {
    $stmt = $pdo->prepare("SELECT value FROM app_settings WHERE `key` = ? LIMIT 1");
    $stmt->execute([$key]);
    $val = $stmt->fetchColumn();
    if (!$val) return $default;
    $decoded = json_decode((string)$val, true);
    return is_array($decoded) ? array_merge($default, $decoded) : $default;
}

function settingsSetJson(PDO $pdo, string $key, array $data): void {
    $json = json_encode($data, JSON_UNESCAPED_SLASHES);
    $stmt = $pdo->prepare("INSERT INTO app_settings (`key`,`value`) VALUES (?,?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)");
    $stmt->execute([$key, $json]);
}

function settingsBody(): array {
    $raw = file_get_contents('php://input');
    if ($raw) {
        $d = json_decode($raw, true);
        if (is_array($d)) return $d;
    }
    // Fallback to traditional form-encoded POST data
    if (!empty($_POST)) {
        return $_POST;
    }
    return [];
}

