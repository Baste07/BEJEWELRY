<?php
require_once __DIR__ . '/admin_auth.php';
require_once __DIR__ . '/db.php';
admin_require_page('orders_export');
$pdo = adminDb();

$status = isset($_GET['status']) ? $_GET['status'] : 'all';
$search = trim((string)($_GET['search'] ?? ''));
$flagged = !empty($_GET['flagged']);

$where = ['1=1'];
$params = [];
if (!$flagged && $status !== 'all') {
    $where[] = 'o.status = ?';
    $params[] = $status;
}
if ($search !== '') {
    $where[] = '(o.id LIKE ? OR o.ship_name LIKE ?)';
    $s = '%' . $search . '%';
    $params[] = $s;
    $params[] = $s;
}
$whereSql = implode(' AND ', $where);

$sql = "SELECT o.id, o.ship_name AS customer_name, o.total, o.status, o.payment_method, o.created_at
        FROM orders o WHERE $whereSql ORDER BY o.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="orders_' . date('Y-m-d_His') . '.csv"');
$out = fopen('php://output', 'w');
fputcsv($out, ['Order ID', 'Customer', 'Total', 'Status', 'Payment', 'Date']);
foreach ($rows as $r) {
    fputcsv($out, [$r['id'], $r['customer_name'], $r['total'], $r['status'], $r['payment_method'], $r['created_at']]);
}
fclose($out);
exit;
