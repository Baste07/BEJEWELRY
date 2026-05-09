<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/../inc.php';

$pdo      = db();
$segment1 = $_GET['__segment1'] ?? '';

switch ($segment1) {
    case 'stats':
        $total    = (int)$pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn();
        $processing  = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'processing'")->fetchColumn();
        $shipped  = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'shipped'")->fetchColumn();
        $flagged  = $processing;
        respond([
            'total'   => $total,
            'processing' => $processing,
            'shipped' => $shipped,
            'flagged' => $flagged,
            'month_label' => date('F Y'),
        ]);

    case 'list':
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

        $counts = [
            'all'       => (int)$pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn(),
            'processing'=> (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status='processing'")->fetchColumn(),
            'shipped'   => (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status='shipped'")->fetchColumn(),
            'delivered' => (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status='delivered'")->fetchColumn(),
            'cancelled' => (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status='cancelled'")->fetchColumn(),
        ];

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
            $o = bejewelry_decrypt_order_shipping_fields($o);
            $o['total'] = (float)$o['total'];
            if (isset($o['customer_name'])) {
                $o['customer_name'] = (string) ($o['customer_name'] ?? '');
            }
        }

        respond([
            'orders'      => $orders,
            'total_pages' => $totalPages,
            'total_count' => $totalCount,
            'counts'      => $counts,
        ]);

    case 'detail':
        $id = $_GET['id'] ?? '';
        if (!$id) respondError('order id required', 400);

        $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ?');
        $stmt->execute([$id]);
        $order = $stmt->fetch();
        if (!$order) respondError('Order not found', 404);
        $order = bejewelry_decrypt_order_shipping_fields($order);

        $itemsStmt = $pdo->prepare('SELECT name, qty, price AS unit_price FROM order_items WHERE order_id = ?');
        $itemsStmt->execute([$id]);
        $items = $itemsStmt->fetchAll();
        foreach ($items as &$i) {
            $i['qty']        = (int)$i['qty'];
            $i['unit_price'] = (float)$i['unit_price'];
        }

        $proofStmt = $pdo->prepare(
            'SELECT carrier_name, carrier_reference, proof_photo, note, delivered_at, created_at
             FROM order_delivery_proofs
             WHERE order_id = ?
             ORDER BY COALESCE(delivered_at, created_at) DESC, id DESC
             LIMIT 1'
        );
        $proofStmt->execute([$id]);
        $proof = $proofStmt->fetch();

        $deliveryProof = null;
        if ($proof) {
            $photo = (string) ($proof['proof_photo'] ?? '');
            $deliveryProof = [
                'carrier_name' => $proof['carrier_name'] ?? '',
                'carrier_reference' => $proof['carrier_reference'] ?? '',
                'note' => $proof['note'] ?? '',
                'delivered_at' => $proof['delivered_at'] ?? '',
                'created_at' => $proof['created_at'] ?? '',
                'proof_photo' => $photo,
                'proof_url' => $photo !== '' ? DELIVERY_PROOF_URL . rawurlencode($photo) : '',
            ];
        }

        respond([
            'id'               => $order['id'],
            'customer_name'    => $order['ship_name'],
            'customer_contact' => $order['ship_phone'],
            'created_at'       => $order['created_at'],
            'payment_method'   => $order['payment_method'],
            'shipping_address' => trim($order['ship_street'] . ', ' . $order['ship_city'] . ', ' . $order['ship_province'] . ' ' . $order['ship_zip']),
            'status'           => $order['status'],
            'courier_user_id'  => (int) ($order['courier_user_id'] ?? 0),
            'courier_name'     => $order['courier_name'] ?? null,
            'courier_assigned_at' => $order['courier_assigned_at'] ?? null,
            'items'            => $items,
            'shipping_fee'     => (float)$order['shipping_fee'],
            'delivery_proof'   => $deliveryProof,
        ]);

    case 'update-status':
        $auth = requireAuth();
        if (($auth['role'] ?? '') !== 'admin') {
            respondError('Forbidden', 403);
        }
        $b = body();
        $orderId = $b['order_id'] ?? '';
        $status  = strtolower(trim($b['status'] ?? ''));
        if ($status === 'pending') {
            $status = 'processing';
        }
        if (!$orderId || !$status) respondError('order_id and status are required.');
        $allowed = ['processing','shipped','delivered','cancelled'];
        if (!in_array($status, $allowed, true)) respondError('Invalid status value.');
        $stmt = $pdo->prepare('UPDATE orders SET status = ? WHERE id = ?');
        $stmt->execute([$status, $orderId]);
        respond(['ok' => true]);

    case 'flagged':
        // Placeholder: no dedicated flagged table yet.
        // Return empty array so UI shows "No flagged orders".
        respond([]);

    case 'flagged-detail':
        // Placeholder detail response for flagged orders.
        $id = $_GET['id'] ?? '';
        if (!$id) respondError('id required', 400);
        respond([
            'id'              => $id,
            'customer_name'   => null,
            'issue_type'      => null,
            'issue_detail'    => null,
            'flagged_by_name' => null,
            'flagged_by_role' => null,
            'date_flagged'    => null,
        ]);

    case 'resolve-flag':
        // Accept payload but do nothing for now.
        body(); // consume
        respond(['ok' => true]);

    case 'export':
        // Basic CSV export of all orders matching optional status/search.
        $status = $_GET['status'] ?? 'all';
        $search = trim($_GET['search'] ?? '');
        $where  = ['1=1'];
        $params = [];
        if ($status !== 'all') {
            $where[]  = 'status = ?';
            $params[] = $status;
        }
        if ($search !== '') {
            $where[] = '(id LIKE ? OR ship_name LIKE ?)';
            $s = '%' . $search . '%';
            $params[] = $s;
            $params[] = $s;
        }
        $sql = 'SELECT id, ship_name, total, status, payment_method, created_at FROM orders WHERE ' . implode(' AND ', $where) . ' ORDER BY created_at DESC';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();
        foreach ($rows as &$r) {
            $r = bejewelry_decrypt_order_shipping_fields($r);
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="orders.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Order ID','Customer','Total','Status','Payment','Created At']);
        foreach ($rows as $r) {
            fputcsv($out, [$r['id'], $r['ship_name'], $r['total'], $r['status'], $r['payment_method'], $r['created_at']]);
        }
        fclose($out);
        exit;

    case 'export-flagged':
        // No flagged table yet: export empty CSV with headers.
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="flagged-orders.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Order ID','Customer','Issue','Flagged By','Date Flagged','Status']);
        fclose($out);
        exit;

    default:
        respondError('Not found', 404);
}

