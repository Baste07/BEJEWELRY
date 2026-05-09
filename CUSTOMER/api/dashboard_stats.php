<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

setHeaders();

// Simple aggregate stats for admin dashboard.

$pdo = db();

// Revenue and orders this month
$startOfMonth = date('Y-m-01 00:00:00');
$endOfMonth   = date('Y-m-t 23:59:59');

$revenueStmt = $pdo->prepare('SELECT COALESCE(SUM(total),0) AS total, COUNT(*) AS orders FROM orders WHERE created_at BETWEEN ? AND ?');
$revenueStmt->execute([$startOfMonth, $endOfMonth]);
$revRow = $revenueStmt->fetch() ?: ['total' => 0, 'orders' => 0];

// Total customers
$custStmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'customer'");
$totalCustomers = (int)$custStmt->fetchColumn();

// New customers this month
$custNewStmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'customer' AND created_at BETWEEN ? AND ?");
$custNewStmt->execute([$startOfMonth, $endOfMonth]);
$newCustomers = (int)$custNewStmt->fetchColumn();

// Very simple rating stub (no reviews table yet)
$avgRating = 5.0;

respond([
    'revenue' => [
        'value'  => (float)$revRow['total'],
        'change' => 0,
    ],
    'orders' => [
        'value'  => (int)$revRow['orders'],
        'change' => 0,
    ],
    'customers' => [
        'value'          => $totalCustomers,
        'new_this_month' => $newCustomers,
    ],
    'rating' => [
        'value'    => $avgRating,
        'change'   => 0,
        'previous' => $avgRating,
    ],
]);

