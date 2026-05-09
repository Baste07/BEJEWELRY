<?php
require_once __DIR__ . '/admin_auth.php';
require_once __DIR__ . '/db.php';
admin_require_page('inventory_export');
$pdo = adminDb();
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$search = trim((string)($_GET['search'] ?? ''));
$lowThreshold = 5;
$where = ['1=1'];
$params = [];
if ($filter === 'instock') { $where[] = 'p.stock > ?'; $params[] = $lowThreshold; }
elseif ($filter === 'low') { $where[] = 'p.stock > 0 AND p.stock <= ?'; $params[] = $lowThreshold; }
elseif ($filter === 'outofstock') { $where[] = 'p.stock <= 0'; }
if ($search !== '') {
  $where[] = '(p.name LIKE ? OR p.description LIKE ?)';
  $s = '%' . $search . '%';
  $params[] = $s; $params[] = $s;
}
$whereSql = implode(' AND ', $where);
$sql = "SELECT p.id, p.name, p.stock, p.price, c.name AS category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE $whereSql ORDER BY p.name";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="inventory_' . date('Y-m-d_His') . '.csv"');
$out = fopen('php://output', 'w');
fputcsv($out, ['ID', 'Name', 'SKU', 'Category', 'Price', 'Stock']);
foreach ($rows as $r) {
  fputcsv($out, [$r['id'], $r['name'], 'BJ-' . $r['id'], $r['category_name'], $r['price'], $r['stock']]);
}
fclose($out);
exit;
