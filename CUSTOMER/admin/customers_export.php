<?php
require_once __DIR__ . '/admin_auth.php';
require_once __DIR__ . '/db.php';
admin_require_page('customers_export');
$pdo = adminDb();
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$search = trim((string)($_GET['search'] ?? ''));
$startOfMonth = date('Y-m-01 00:00:00');
$endOfMonth = date('Y-m-t 23:59:59');

$where = ["u.role = 'customer'"];
$params = [];
if ($filter === 'vip') {
  $where[] = "COALESCE(o.order_count, 0) >= 3 OR COALESCE(o.total_spent, 0) >= 5000";
}
if ($filter === 'new') {
  $where[] = "u.created_at BETWEEN ? AND ?";
  $params[] = $startOfMonth;
  $params[] = $endOfMonth;
}
if ($search !== '') {
  $where[] = "(u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?)";
  $s = '%' . $search . '%';
  $params[] = $s; $params[] = $s; $params[] = $s;
}
$whereSql = implode(' AND ', $where);
$sql = "SELECT u.id, u.first_name, u.last_name, u.email, u.phone, u.created_at,
        o.order_count, o.total_spent
        FROM users u
        LEFT JOIN (SELECT user_id, COUNT(*) AS order_count, SUM(total) AS total_spent FROM orders GROUP BY user_id) o ON u.id = o.user_id
        WHERE $whereSql ORDER BY u.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="customers_' . date('Y-m-d_His') . '.csv"');
$out = fopen('php://output', 'w');
fputcsv($out, ['ID', 'First Name', 'Last Name', 'Email', 'Phone', 'Orders', 'Total Spent', 'Created']);
foreach ($rows as $r) {
  fputcsv($out, [$r['id'], $r['first_name'], $r['last_name'], $r['email'], $r['phone'], $r['order_count'] ?? 0, $r['total_spent'] ?? 0, $r['created_at']]);
}
fclose($out);
exit;
