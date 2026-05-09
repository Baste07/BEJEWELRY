<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';

setHeaders();

$pdo = db();

$page   = max(1, (int)($_GET['page'] ?? 1));
$status = $_GET['status'] ?? 'all';
if ($status === 'pending') {
    $status = 'processing';
}
$search = trim($_GET['search'] ?? '');
$perPage = 10;

$where  = ['1=1'];
$params = [];

if ($status !== 'all') {
    $where[]  = 'o.status = ?';
    $params[] = $status;
}

if ($search !== '') {
    $where[] = '(o.id LIKE ? OR o.ship_name LIKE ?)';
    $s = '%' . $search . '%';
    $params[] = $s;
    $params[] = $s;
}

$whereSql = implode(' AND ', $where);

// Counts per status for filter chips
$counts = [
    'all'       => (int)$pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn(),
    'processing'=> (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status='processing'")->fetchColumn(),
    'shipped'   => (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status='shipped'")->fetchColumn(),
    'delivered' => (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status='delivered'")->fetchColumn(),
    'cancelled' => (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status='cancelled'")->fetchColumn(),
];

// Total for current filter
$cntStmt = $pdo->prepare("SELECT COUNT(*) FROM orders o WHERE $whereSql");
$cntStmt->execute($params);
$totalCount = (int)$cntStmt->fetchColumn();

$totalPages = max(1, (int)ceil($totalCount / $perPage));
$offset     = ($page - 1) * $perPage;

$sql = "
    SELECT o.id,
           o.ship_name      AS customer_name,
           o.total,
           o.status,
           o.payment_method,
           o.created_at,
           (SELECT oi.name FROM order_items oi WHERE oi.order_id = o.id LIMIT 1) AS item_name
    FROM orders o
    WHERE $whereSql
    ORDER BY o.created_at DESC
    LIMIT $perPage OFFSET $offset
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

foreach ($orders as &$o) {
    $o['total'] = (float)$o['total'];
}

respond([
    'orders'      => $orders,
    'total_pages' => $totalPages,
    'total_count' => $totalCount,
    'counts'      => $counts,
]);

