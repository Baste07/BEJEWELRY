<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

setHeaders();

$pdo = db();

$stmt = $pdo->query("SELECT id, first_name, last_name, role FROM users WHERE role = 'admin'");
$rows = $stmt->fetchAll() ?: [];

foreach ($rows as &$r) {
    $r['name'] = trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? ''));
}

respond($rows);

