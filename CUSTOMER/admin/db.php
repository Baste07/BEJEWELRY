<?php
/**
 * Bejewelry Admin — MySQL connection only (no API).
 * Edit these to match your MySQL server. Same database as schema.sql (bejewelry).
 */
define('ADMIN_DB_HOST', 'localhost');
define('ADMIN_DB_NAME', 'bejewelry');
define('ADMIN_DB_USER', 'root');
define('ADMIN_DB_PASS', '');
define('ADMIN_DB_CHARSET', 'utf8mb4');

function adminDb(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;
    $dsn = 'mysql:host=' . ADMIN_DB_HOST . ';dbname=' . ADMIN_DB_NAME . ';charset=' . ADMIN_DB_CHARSET;
    $pdo = new PDO($dsn, ADMIN_DB_USER, ADMIN_DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    return $pdo;
}
