<?php
require_once __DIR__ . '/admin_auth.php';
require_once __DIR__ . '/db.php';
admin_require_page('orders');
$pdo = adminDb();

/**
 * Generate a unique tracking number in format: TRACK-YYYY-XXXX
 * where XXXX is a sequential counter for the year
 */
function generate_tracking_number($pdo): string {
  $year = date('Y');
  $count = $pdo->query("SELECT COUNT(*) FROM orders WHERE tracking_number LIKE 'TRACK-$year-%'")->fetchColumn();
  $sequence = str_pad((int)$count + 1, 4, '0', STR_PAD_LEFT);
  return "TRACK-$year-$sequence";
}

// Handle order actions (no API)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
  csrf_validate();
  $action = (string) $_POST['action'];
  $id = trim((string) ($_POST['order_id'] ?? ''));

  if ($action === 'update_status') {
    $status = strtolower(trim((string) ($_POST['status'] ?? '')));
    if ($status === 'pending') {
      $status = 'processing';
    }
    $allowed = ['processing', 'shipped', 'delivered', 'cancelled'];
    if ($id !== '' && in_array($status, $allowed, true)) {
      $stmt = $pdo->prepare('UPDATE orders SET status = ? WHERE id = ?');
      $stmt->execute([$status, $id]);
    }
  }

  if ($action === 'assign_courier') {
    $courierId = (int) ($_POST['courier_id'] ?? 0);
    $trackingNumber = trim((string) ($_POST['tracking_number'] ?? ''));
    if ($id !== '' && $courierId > 0) {
      $courierStmt = $pdo->prepare("SELECT id, first_name, last_name FROM users WHERE id = ? AND role = 'courier' LIMIT 1");
      $courierStmt->execute([$courierId]);
      $courier = $courierStmt->fetch(PDO::FETCH_ASSOC);
      if ($courier) {
        $courierName = trim(((string) ($courier['first_name'] ?? '')) . ' ' . ((string) ($courier['last_name'] ?? '')));
        if ($courierName === '') {
          $courierName = 'Courier #' . (int) $courier['id'];
        }
        // Auto-generate tracking number if not provided
        if ($trackingNumber === '') {
          $trackingNumber = generate_tracking_number($pdo);
        }
        $stmt = $pdo->prepare("UPDATE orders SET courier_user_id = ?, courier_name = ?, courier_assigned_at = NOW(), tracking_number = ?, status = 'shipped' WHERE id = ? AND status NOT IN ('delivered','cancelled')");
        $stmt->execute([(int) $courier['id'], $courierName, $trackingNumber, $id]);
      }
    }
  }

  if ($action === 'assign_courier_bulk') {
    $courierId = (int) ($_POST['courier_id'] ?? 0);
    $csv = trim((string) ($_POST['order_ids_csv'] ?? ''));
    $trackingNumber = trim((string) ($_POST['tracking_number'] ?? ''));
    if ($courierId > 0 && $csv !== '') {
      $courierStmt = $pdo->prepare("SELECT id, first_name, last_name FROM users WHERE id = ? AND role = 'courier' LIMIT 1");
      $courierStmt->execute([$courierId]);
      $courier = $courierStmt->fetch(PDO::FETCH_ASSOC);
      if ($courier) {
        $rawIds = array_filter(array_map('trim', explode(',', $csv)), static fn($v) => $v !== '');
        $orderIds = [];
        foreach ($rawIds as $oid) {
          if (preg_match('/^[A-Za-z0-9-]+$/', $oid)) {
            $orderIds[$oid] = $oid;
          }
        }
        if (!empty($orderIds)) {
          $ids = array_values($orderIds);
          $placeholders = implode(',', array_fill(0, count($ids), '?'));
          $courierName = trim(((string) ($courier['first_name'] ?? '')) . ' ' . ((string) ($courier['last_name'] ?? '')));
          if ($courierName === '') {
            $courierName = 'Courier #' . (int) $courier['id'];
          }
          // For bulk: generate individual tracking numbers for each order if not provided
          if ($trackingNumber === '') {
            // Generate separate tracking numbers for each order
            foreach ($ids as $oid) {
              $tNum = generate_tracking_number($pdo);
              $stmt = $pdo->prepare("UPDATE orders SET courier_user_id = ?, courier_name = ?, courier_assigned_at = NOW(), tracking_number = ?, status = 'shipped' WHERE id = ? AND status NOT IN ('delivered','cancelled')");
              $stmt->execute([(int) $courier['id'], $courierName, $tNum, $oid]);
            }
          } else {
            // Use same tracking number for all (if admin provides one)
            $params = array_merge([(int) $courier['id'], $courierName, $trackingNumber], $ids);
            $stmt = $pdo->prepare("UPDATE orders SET courier_user_id = ?, courier_name = ?, courier_assigned_at = NOW(), tracking_number = ?, status = 'shipped' WHERE id IN ($placeholders) AND status NOT IN ('delivered','cancelled')");
            $stmt->execute($params);
          }
        }
      }
    }
  }

  $redirect = 'orders.php?';
  if (!empty($_GET['page'])) $redirect .= 'page=' . (int)$_GET['page'] . '&';
  if (!empty($_GET['status'])) $redirect .= 'status=' . rawurlencode($_GET['status']) . '&';
  if (!empty($_GET['search'])) $redirect .= 'search=' . rawurlencode($_GET['search']);
  header('Location: ' . rtrim($redirect, '&'));
  exit;
}

$page   = max(1, (int)($_GET['page'] ?? 1));
$status = isset($_GET['status']) ? $_GET['status'] : 'all';
if ($status === 'pending') {
  $status = 'processing';
}
$search = trim((string)($_GET['search'] ?? ''));
$perPage = 10;

$where = ['1=1'];
$params = [];
if ($status !== 'all') {
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

$counts = [
  'all' => (int)$pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn(),
  'processing' => (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status='processing'")->fetchColumn(),
  'shipped' => (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status='shipped'")->fetchColumn(),
  'delivered' => (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status='delivered'")->fetchColumn(),
  'cancelled' => (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status='cancelled'")->fetchColumn(),
];

$couriers = $pdo->query(
  "SELECT u.id, u.first_name, u.last_name, u.email,
          COALESCE(a.active_deliveries, 0) AS active_deliveries,
          CASE WHEN COALESCE(a.active_deliveries, 0) > 0 THEN 0 ELSE 1 END AS is_available
   FROM users u
   LEFT JOIN (
     SELECT courier_user_id, COUNT(*) AS active_deliveries
     FROM orders
     WHERE courier_user_id IS NOT NULL AND status NOT IN ('delivered','cancelled')
     GROUP BY courier_user_id
   ) a ON a.courier_user_id = u.id
   WHERE u.role = 'courier'
   ORDER BY is_available DESC, u.first_name ASC, u.last_name ASC, u.email ASC"
)->fetchAll(PDO::FETCH_ASSOC);

$cntStmt = $pdo->prepare("SELECT COUNT(*) FROM orders o WHERE $whereSql");
$cntStmt->execute($params);
$totalCount = (int)$cntStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalCount / $perPage));
$offset = ($page - 1) * $perPage;

$sql = "SELECT o.id, o.ship_name AS customer_name, o.total, o.status, o.payment_method, o.created_at,
        (SELECT oi.name FROM order_items oi WHERE oi.order_id = o.id LIMIT 1) AS item_name
        FROM orders o WHERE $whereSql ORDER BY o.created_at DESC LIMIT $perPage OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$ordersList = $stmt->fetchAll(PDO::FETCH_ASSOC);

$orderDetails = [];
foreach ($ordersList as $o) {
  $id = $o['id'];
  $ordStmt = $pdo->prepare('SELECT ship_name, ship_street, ship_city, ship_province, ship_zip, ship_phone, payment_method, created_at, status, shipping_fee, courier_user_id, courier_name, courier_assigned_at FROM orders WHERE id = ?');
  $ordStmt->execute([$id]);
  $ord = $ordStmt->fetch(PDO::FETCH_ASSOC);
  if (is_array($ord)) {
    $ord = bejewelry_decrypt_order_shipping_fields($ord);
  }
  $itemStmt = $pdo->prepare('SELECT name, qty, price AS unit_price FROM order_items WHERE order_id = ?');
  $itemStmt->execute([$id]);
  $items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);
  $proofStmt = $pdo->prepare(
    'SELECT carrier_name, carrier_reference, proof_photo, note, delivered_at, created_at
     FROM order_delivery_proofs
     WHERE order_id = ?
     ORDER BY COALESCE(delivered_at, created_at) DESC, id DESC
     LIMIT 1'
  );
  $proofStmt->execute([$id]);
  $proof = $proofStmt->fetch(PDO::FETCH_ASSOC);
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
  $orderDetails[$id] = [
    'customer_name' => $ord['ship_name'],
    'customer_contact' => $ord['ship_phone'],
    'created_at' => $ord['created_at'],
    'payment_method' => $ord['payment_method'],
    'shipping_address' => trim(implode(', ', array_filter([$ord['ship_street'], $ord['ship_city'], $ord['ship_province'], $ord['ship_zip']]))),
    'status' => $ord['status'],
    'courier_user_id' => (int) ($ord['courier_user_id'] ?? 0),
    'courier_name' => $ord['courier_name'],
    'courier_assigned_at' => $ord['courier_assigned_at'],
    'items' => array_map(function ($i) {
      return ['name' => $i['name'], 'qty' => (int)$i['qty'], 'unit_price' => (float)$i['unit_price']];
    }, $items),
    'shipping_fee' => (float)$ord['shipping_fee'],
    'delivery_proof' => $deliveryProof,
  ];
}

// Get current user's name
$adminUser = $GLOBALS['ADMIN_USER'] ?? [];
$userName = trim(($adminUser['first_name'] ?? '') . ' ' . ($adminUser['last_name'] ?? '')) ?: ($adminUser['email'] ?? 'Admin');

$ordersData = [
  'user' => ['name' => $userName, 'role' => $GLOBALS['ADMIN_ROLE'] ?? 'manager'],
  'badges' => [
    'processing_orders' => $counts['processing'],
    'new_products' => (int)$pdo->query("SELECT COUNT(*) FROM products WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn(),
    'low_stock' => (int)$pdo->query("SELECT COUNT(*) FROM products WHERE stock > 0 AND stock <= 5")->fetchColumn(),
    'pending_reviews' => (int) $pdo->query("SELECT COUNT(*) FROM product_reviews WHERE status = 'pending'")->fetchColumn(),
  ],
  'stats' => [
    'total' => (int)$pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn(),
    'processing' => $counts['processing'],
    'delivered' => $counts['delivered'],
    'month_label' => date('F Y'),
  ],
  'orders' => $ordersList,
  'couriers' => array_map(static function (array $c): array {
    $full = trim(((string) ($c['first_name'] ?? '')) . ' ' . ((string) ($c['last_name'] ?? '')));
    return [
      'id' => (int) ($c['id'] ?? 0),
      'name' => $full !== '' ? $full : (string) ($c['email'] ?? ('Courier #' . (string) ($c['id'] ?? ''))),
      'email' => (string) ($c['email'] ?? ''),
      'active_deliveries' => (int) ($c['active_deliveries'] ?? 0),
      'is_available' => (int) ($c['is_available'] ?? 0),
    ];
  }, $couriers),
  'order_details' => $orderDetails,
  'total_pages' => $totalPages,
  'total_count' => $totalCount,
  'counts' => $counts,
  'page' => $page,
  'status' => $status,
  'search' => $search,
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Bejewelry Admin — Orders</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,500;0,600;0,700;1,400&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600;9..40,700&display=swap" rel="stylesheet">
  <style>
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
    html{font-size:16px;scroll-behavior:smooth}
    img{display:block;max-width:100%}
    input,button,select,textarea{font-family:inherit}
    a{text-decoration:none;color:inherit}
    ul,ol{list-style:none}

    :root {
      --fd: 'Playfair Display', Georgia, serif;
      --fb: 'DM Sans', system-ui, sans-serif;
      --blush:#FEF1F3; --blush-mid:#FAE3E8; --blush-deep:#F4C8D2;
      --rose:#D96070; --rose-deep:#B03050; --rose-muted:#CC8898;
      --white:#FFFFFF; --dark:#241418; --dark-soft:#3A2028;
      --muted:#7A5E68; --muted-light:#AC8898;
      --border:#ECDCE0; --border-mid:#DEC8D0;
      --gold:#B88830; --gold-light:#FFF7D6; --gold-border:#EDD050;
      --success:#228855; --danger:#BB3333;
      --flag-bg:#FFFBF0; --flag-border:#E8C97A;
      --flag-accent:#8C6800; --flag-badge-bg:#FFF7D6; --flag-badge-text:#7A4F00;
      --s1:4px;--s2:8px;--s3:12px;--s4:16px;--s5:20px;
      --s6:24px;--s8:32px;--s10:40px;--s12:48px;--s16:64px;
      --sidebar-w:220px; --hh:64px;
      --r-sm:8px;--r-md:12px;--r-lg:18px;--r-xl:26px;--r-pill:999px;
      --sh-xs:0 1px 3px rgba(160,40,60,.06);
      --sh-sm:0 2px 8px rgba(160,40,60,.09);
      --sh-md:0 4px 18px rgba(160,40,60,.13);
      --sh-lg:0 8px 36px rgba(160,40,60,.17);
      --tr:.2s ease;
    }

    body { font-family: var(--fb); background: var(--blush); color: var(--dark); line-height: 1.6; min-height: 100vh; -webkit-font-smoothing: antialiased; }
    h1,h2,h3,h4 { font-family: var(--fd); color: var(--dark); line-height: 1.2; }

    .site-wrapper { display: flex; min-height: 100vh; }
    .site-content { flex: 1; min-width: 0; display: flex; flex-direction: column; }

    /* ── SIDEBAR ── */
    .sidebar { width: var(--sidebar-w); min-width: var(--sidebar-w); flex-shrink: 0; background: var(--white); border-right: 1px solid var(--border); display: flex; flex-direction: column; position: sticky; top: 0; height: 100vh; overflow-y: auto; z-index: 90; }
    .sb-brand { padding: var(--s5) var(--s4) var(--s4); border-bottom: 1px solid var(--border); }
    .sb-logo { font-family: var(--fd); font-size: 1.3rem; font-weight: 700; color: var(--dark); }
    .sb-sub { font-size: .58rem; font-weight: 600; letter-spacing: .2em; text-transform: uppercase; color: var(--rose); margin-top: 3px; }
    .sb-user { display: flex; align-items: center; gap: var(--s3); padding: var(--s3) var(--s4); border-bottom: 1px solid var(--border); }
    .sb-av { width: 34px; height: 34px; border-radius: 50%; background: linear-gradient(135deg, var(--blush-deep), var(--rose-muted)); display: flex; align-items: center; justify-content: center; font-size: .78rem; font-weight: 700; color: var(--white); flex-shrink: 0; border: 2px solid var(--white); box-shadow: var(--sh-xs); }
    .sb-uname { font-size: .81rem; font-weight: 600; color: var(--dark); }
    .sb-urole { font-size: .59rem; color: var(--muted-light); text-transform: uppercase; letter-spacing: .08em; }
    .sb-group { font-size: .57rem; font-weight: 700; text-transform: uppercase; letter-spacing: .2em; color: var(--muted-light); padding: var(--s4) var(--s4) var(--s2); }
    .sb-item { display: flex; align-items: center; gap: var(--s3); padding: 9px var(--s4); font-size: .81rem; color: var(--muted); border-left: 2.5px solid transparent; cursor: pointer; transition: all var(--tr); }
    .sb-item:hover { background: var(--blush); color: var(--dark); border-left-color: var(--rose-muted); }
    .sb-item.active { background: linear-gradient(90deg, var(--blush-mid), var(--blush)); color: var(--rose-deep); border-left-color: var(--rose); font-weight: 600; }
    .sb-icon { font-size: .9rem; width: 18px; text-align: center; flex-shrink: 0; }
    .sb-badge { margin-left: auto; background: var(--rose); color: var(--white); font-size: .56rem; font-weight: 700; min-width: 17px; height: 17px; border-radius: var(--r-pill); display: flex; align-items: center; justify-content: center; padding: 0 4px; }
    .sb-badge.gold { background: var(--gold); }
    .sb-div { border: none; border-top: 1px solid var(--border); margin: var(--s2) 0; }
    .sb-foot { margin: var(--s4); padding: 10px 22px; font-size: .75rem; font-weight: 600; color: var(--white); background: linear-gradient(135deg, var(--rose), var(--rose-deep)); border-radius: var(--r-pill); cursor: pointer; display: flex; align-items: center; justify-content: center; gap: var(--s2); transition: all var(--tr); border: none; }
    .sb-foot:hover { transform: translateY(-2px); box-shadow: 0 4px 14px rgba(176,48,80,.3); }

    /* ── TOPBAR ── */
    .topbar { height: var(--hh); background: var(--white); border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; padding: 0 var(--s8); position: sticky; top: 0; z-index: 80; box-shadow: var(--sh-xs); }
    .topbar-left { display: flex; flex-direction: column; }
    .topbar-title { font-family: var(--fd); font-size: 1.1rem; font-weight: 500; color: var(--dark); }
    .topbar-bc { font-size: .65rem; color: var(--muted-light); letter-spacing: .04em; }
    .topbar-right { display: flex; align-items: center; gap: var(--s3); }
    .topbar-search { display: flex; align-items: center; gap: var(--s2); background: var(--blush); border: 1.5px solid var(--border); border-radius: var(--r-pill); padding: 7px 14px; width: 220px; transition: border-color var(--tr); }
    .topbar-search:focus-within { border-color: var(--rose-muted); }
    .topbar-search input { border: none; outline: none; background: transparent; font-size: .8rem; color: var(--dark); width: 100%; }
    .topbar-search input::placeholder { color: var(--muted-light); }
    .icon-btn { width: 36px; height: 36px; border-radius: var(--r-md); background: var(--blush); border: 1px solid var(--border); display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all var(--tr); color: var(--muted); position: relative; }
    .icon-btn:hover { background: var(--blush-mid); border-color: var(--rose-muted); color: var(--rose-deep); }
    .icon-btn .dot{ position:absolute; top:6px; right:6px; width:9px; height:9px; border-radius:50%; background:var(--rose); border:2px solid var(--white); display:none; box-shadow:0 2px 8px rgba(176,48,80,.22); }

    /* ── CONTENT ── */
    .content { padding: var(--s8); flex: 1; }

    .page-hdr { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: var(--s8); padding-bottom: var(--s5); border-bottom: 1px solid var(--border); }
    .page-hdr h2 { font-size: 1.6rem; margin-bottom: 4px; }
    .page-hdr p { font-size: .78rem; color: var(--muted-light); }
    .page-hdr-actions { display: flex; gap: var(--s2); align-items: center; }

    /* ── BUTTONS ── */
    .btn { display: inline-flex; align-items: center; justify-content: center; gap: 6px; padding: 10px 22px; border-radius: var(--r-pill); font-family: var(--fb); font-size: .75rem; font-weight: 600; letter-spacing: .07em; text-transform: uppercase; border: none; cursor: pointer; transition: all var(--tr); white-space: nowrap; }
    .btn-primary { background: linear-gradient(135deg, var(--rose), var(--rose-deep)); color: var(--white); box-shadow: 0 4px 14px rgba(176,48,80,.3); }
    .btn-primary:hover { transform: translateY(-1px); box-shadow: 0 6px 22px rgba(176,48,80,.4); }
    .btn-ghost { background: transparent; color: var(--muted); border: 1.5px solid var(--border-mid); }
    .btn-ghost:hover { background: var(--blush); color: var(--dark); border-color: var(--rose-muted); }
    .btn-sm { padding: 7px 15px; font-size: .7rem; }
    .btn-warn { display: inline-flex; align-items: center; justify-content: center; background: var(--gold-light); color: var(--flag-accent); border: 1px solid var(--gold-border); font-size: .65rem; font-weight: 700; letter-spacing: .07em; text-transform: uppercase; padding: 6px 13px; cursor: pointer; border-radius: var(--r-pill); transition: all var(--tr); }
    .btn-warn:hover { background: #fde9a0; transform: translateY(-1px); }
    .btn-view { display: inline-flex; align-items: center; justify-content: center; background: var(--blush); color: var(--muted); border: 1px solid var(--border-mid); font-size: .65rem; font-weight: 700; letter-spacing: .07em; text-transform: uppercase; padding: 6px 13px; cursor: pointer; border-radius: var(--r-pill); transition: all var(--tr); }
    .btn-view:hover { background: var(--blush-mid); color: var(--dark); }

    /* ── SKELETON ── */
    .skel { background: linear-gradient(90deg, var(--blush) 25%, var(--blush-mid) 50%, var(--blush) 75%); background-size: 200% 100%; animation: skel-shine 1.5s infinite; border-radius: var(--r-sm); display: inline-block; }
    @keyframes skel-shine { 0%{background-position:200% 0} 100%{background-position:-200% 0} }
    .skel-text { height: 13px; width: 80%; margin: 3px 0; }
    .skel-val  { height: 36px; width: 60%; margin: 6px 0; }
    .skel-row  { height: 13px; width: 100%; }

    /* ── STATS ── */
    .stats-row { display: grid; grid-template-columns: repeat(3,1fr); gap: var(--s5); margin-bottom: var(--s8); }
    .stat-card { background: var(--white); border-radius: var(--r-lg); border: 1px solid var(--border); box-shadow: var(--sh-xs); padding: var(--s5) var(--s6); position: relative; overflow: hidden; transition: box-shadow var(--tr); }
    .stat-card:hover { box-shadow: var(--sh-md); }
    .stat-card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px; background: linear-gradient(90deg, var(--rose), var(--rose-deep)); }
    .stat-label { font-size: .62rem; font-weight: 700; letter-spacing: .16em; text-transform: uppercase; color: var(--muted-light); margin-bottom: var(--s2); display: block; }
    .stat-value { font-family: var(--fd); font-size: 1.9rem; font-weight: 600; color: var(--dark); line-height: 1.1; margin-bottom: var(--s2); }
    .stat-trend { font-size: .72rem; color: var(--muted-light); }
    .stat-icon { position: absolute; top: var(--s5); right: var(--s5); font-size: 1.6rem; opacity: .15; }

    /* ── SECTION LABEL ── */
    .section-label { font-size: .65rem; font-weight: 700; text-transform: uppercase; letter-spacing: .18em; color: var(--muted-light); margin-bottom: var(--s4); margin-top: var(--s8); display: flex; align-items: center; gap: var(--s3); }
    .section-label::after { content: ''; flex: 1; height: 1px; background: var(--border); }

    /* ── TABLES ── */
    .table-wrap { background: var(--white); border: 1px solid var(--border); border-radius: var(--r-lg); overflow: hidden; box-shadow: var(--sh-xs); margin-bottom: var(--s5); }
    .tbl { width: 100%; border-collapse: collapse; }
    .tbl th, .tbl td { padding: 13px var(--s5); text-align: left; border-bottom: 1px solid var(--border); font-size: .82rem; }
    .tbl th { font-size: .6rem; font-weight: 700; letter-spacing: .15em; text-transform: uppercase; color: var(--muted-light); background: var(--blush); white-space: nowrap; }
    .tbl tbody tr { transition: background var(--tr); }
    .tbl tbody tr:hover td { background: var(--blush); }
    .tbl tr:last-child td { border-bottom: none; }

    .order-id { font-family: var(--fd); font-weight: 600; color: var(--rose-deep); font-size: .9rem; }
    .tbl td.amount { font-family: var(--fd); font-weight: 600; color: var(--dark); }
    .issue-detail { font-size: .68rem; color: var(--muted-light); margin-top: 4px; max-width: 210px; line-height: 1.4; }

    /* ── BADGES ── */
    .issue-badge { display: inline-flex; align-items: center; gap: 4px; padding: 3px 10px; font-size: .6rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; border-radius: var(--r-pill); }
    .issue-payment { background: #fde8e8; color: #8b1a1a; border: 1px solid #e8b4b4; }
    .issue-address { background: #e8edfb; color: #1a2e8b; border: 1px solid #b4c0e8; }
    .issue-fraud   { background: var(--dark); color: var(--white); border: 1px solid var(--dark); }
    .issue-stock   { background: #fdf3e8; color: #7a3d00; border: 1px solid #e8c494; }
    .issue-return  { background: var(--blush-mid); color: var(--muted); border: 1px solid var(--border-mid); }

    .flag-status { display: inline-flex; align-items: center; padding: 3px 10px; font-size: .58rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; border-radius: var(--r-pill); }
    .flag-status-open     { background: var(--gold-light); color: var(--flag-badge-text); border: 1px solid var(--gold-border); }
    .flag-status-review   { background: #e8edfb; color: #1a2e8b; border: 1px solid #b4c0e8; }
    .flag-status-resolved { background: var(--blush); color: var(--muted-light); border: 1px solid var(--border); }

    .badge { display: inline-flex; align-items: center; padding: 3px 10px; border-radius: var(--r-pill); font-size: .6rem; font-weight: 700; letter-spacing: .07em; text-transform: uppercase; }
    .b-pending   { background:#FFF7D6; color:#8C6800; border:1px solid #EDD050; }
    .b-processing{ background:#E6F3FF; color:#1455A0; border:1px solid #88C0F0; }
    .b-shipped   { background:#ECF0FF; color:#2035A0; border:1px solid #9AA8F0; }
    .b-delivered { background:#E4FFEE; color:#156038; border:1px solid #68CC88; }
    .b-cancelled { background:#FFEEEE; color:#982828; border:1px solid #EEAAAA; }

    .pay-chip { display: inline-flex; align-items: center; padding: 2px 9px; border-radius: var(--r-pill); font-size: .58rem; font-weight: 600; background: var(--blush-mid); color: var(--muted); border: 1px solid var(--border); }

    /* ── FLAGGED BY ── */
    .flagged-by { display: flex; align-items: center; gap: var(--s2); }
    .flagged-by-av { width: 26px; height: 26px; background: linear-gradient(135deg, var(--blush-deep), var(--rose-muted)); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: .65rem; font-weight: 700; color: var(--white); flex-shrink: 0; }
    .flagged-by-av.resolved { background: var(--blush-deep); color: var(--muted); }
    .flagged-by-name { font-size: .78rem; color: var(--dark-soft); font-weight: 500; }
    .flag-actions { display: flex; gap: var(--s2); }

    /* ── FILTERS ── */
    .filters-bar { display: flex; gap: var(--s2); align-items: center; flex-wrap: wrap; margin-bottom: var(--s4); }
    .fb { padding: 7px 16px; border: 1.5px solid var(--border-mid); background: var(--white); font-size: .68rem; font-weight: 600; text-transform: uppercase; letter-spacing: .08em; color: var(--muted); cursor: pointer; border-radius: var(--r-pill); transition: all var(--tr); }
    .fb:hover { background: var(--blush); color: var(--dark); border-color: var(--rose-muted); }
    .fb.on { background: linear-gradient(135deg, var(--rose), var(--rose-deep)); color: var(--white); border-color: transparent; box-shadow: 0 3px 10px rgba(176,48,80,.25); }
    .filter-wrap { position: relative; margin-left: auto; }
    .filter-wrap::before { content: '⌕'; position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: var(--muted-light); font-size: 1rem; pointer-events: none; }
    .filter-input { height: 36px; border: 1.5px solid var(--border-mid); background: var(--white); padding: 0 14px 0 36px; font-size: .8rem; color: var(--dark); outline: none; border-radius: var(--r-pill); min-width: 200px; transition: border-color var(--tr); }
    .filter-input:focus { border-color: var(--rose-muted); }

    /* ── PAGINATION ── */
    .pagination { display: flex; align-items: center; justify-content: center; gap: var(--s2); margin-top: var(--s8); }
    .page-btn { width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; border: 1px solid var(--border); background: var(--white); font-size: .8rem; color: var(--muted); cursor: pointer; border-radius: var(--r-md); transition: all var(--tr); }
    .page-btn.active { background: linear-gradient(135deg, var(--rose), var(--rose-deep)); color: var(--white); border-color: transparent; box-shadow: 0 3px 10px rgba(176,48,80,.25); }
    .page-btn:hover:not(.active) { background: var(--blush); color: var(--dark); border-color: var(--rose-muted); }
    .page-btn:disabled { opacity: .4; cursor: not-allowed; }

    /* ── MODAL ── */
    .modal-bg { position: fixed; inset: 0; background: rgba(36,20,24,.5); backdrop-filter: blur(4px); z-index: 9000; display: none; align-items: center; justify-content: center; }
    .modal-bg.on { display: flex; }
    .modal { background: var(--white); border: 1px solid var(--border); border-radius: var(--r-xl); padding: var(--s8); width: 580px; max-width: 95vw; max-height: 90vh; overflow-y: auto; box-shadow: var(--sh-lg); animation: modalIn .22s ease; }
    @keyframes modalIn { from{opacity:0;transform:scale(.97) translateY(8px)} to{opacity:1;transform:scale(1) translateY(0)} }
    .modal-hdr { display: flex; align-items: center; justify-content: space-between; margin-bottom: var(--s6); padding-bottom: var(--s4); border-bottom: 1px solid var(--border); }
    .modal-hdr h3 { font-family: var(--fd); font-size: 1.1rem; font-weight: 600; color: var(--dark); }
    .modal-close { width: 30px; height: 30px; background: var(--blush); border: 1px solid var(--border); border-radius: var(--r-md); display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: .85rem; color: var(--muted); transition: all var(--tr); }
    .modal-close:hover { background: var(--blush-mid); color: var(--dark); }
    .modal-footer { display: flex; gap: var(--s2); justify-content: flex-end; margin-top: var(--s6); padding-top: var(--s4); border-top: 1px solid var(--border); }

    .modal-info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: var(--s4); margin-bottom: var(--s5); }
    .modal-info-item label { font-size: .58rem; font-weight: 700; text-transform: uppercase; letter-spacing: .14em; color: var(--muted-light); display: block; margin-bottom: 3px; }
    .modal-info-item p { font-size: .84rem; color: var(--dark); font-weight: 500; }

    .flag-context { background: var(--flag-bg); border: 1px solid var(--flag-border); border-radius: var(--r-md); padding: var(--s4) var(--s5); margin-bottom: var(--s5); display: flex; align-items: flex-start; gap: var(--s3); }
    .flag-context-icon { font-size: 1rem; flex-shrink: 0; margin-top: 1px; }
    .flag-context-title { font-size: .6rem; font-weight: 700; text-transform: uppercase; letter-spacing: .1em; color: var(--flag-accent); margin-bottom: 4px; }
    .flag-context-desc { font-size: .8rem; color: var(--dark-soft); }

    .resolve-info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: var(--s3); margin-bottom: var(--s5); }
    .resolve-info-box { background: var(--flag-bg); border: 1px solid #e8d98a; border-radius: var(--r-md); padding: var(--s3) var(--s4); }
    .resolve-info-box label { font-size: .57rem; font-weight: 700; text-transform: uppercase; letter-spacing: .12em; color: var(--flag-accent); display: block; margin-bottom: 3px; }
    .resolve-info-box p { font-size: .82rem; color: var(--dark-soft); font-weight: 500; }

    .proof-panel { border: 1px solid var(--border); border-radius: var(--r-md); padding: var(--s4); background: linear-gradient(180deg, #fff, var(--blush)); margin-bottom: var(--s5); }
    .proof-panel.hidden { display: none; }
    .proof-panel-head { display: flex; align-items: center; justify-content: space-between; gap: var(--s3); margin-bottom: var(--s3); }
    .proof-panel-title { font-size: .62rem; font-weight: 700; letter-spacing: .14em; text-transform: uppercase; color: var(--rose-deep); }
    .proof-panel-meta { font-size: .78rem; color: var(--muted); margin-top: 2px; }
    .proof-thumb { display: block; width: 100%; max-height: 240px; object-fit: cover; border-radius: var(--r-md); border: 1px solid var(--border); background: var(--white); margin-bottom: var(--s3); }
    .proof-note { font-size: .82rem; color: var(--dark-soft); line-height: 1.55; white-space: pre-line; }

    .form-group { margin-bottom: var(--s4); }
    .form-label { display: block; font-size: .6rem; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; color: var(--muted-light); margin-bottom: 6px; }
    .form-input, .form-select, .form-textarea { width: 100%; padding: 10px 14px; font-size: .84rem; color: var(--dark); background: var(--white); border: 1.5px solid var(--border-mid); outline: none; font-family: var(--fb); border-radius: var(--r-md); transition: border-color var(--tr); }
    .form-input:focus, .form-select:focus, .form-textarea:focus { border-color: var(--rose-muted); }
    .form-textarea { resize: vertical; min-height: 90px; }

    .modal-tbl { width: 100%; border-collapse: collapse; font-size: .82rem; margin-bottom: var(--s4); border-radius: var(--r-md); overflow: hidden; border: 1px solid var(--border); }
    .modal-tbl th { background: var(--blush); font-size: .58rem; text-transform: uppercase; letter-spacing: .12em; color: var(--muted-light); font-weight: 700; padding: 10px var(--s4); text-align: left; border-bottom: 1px solid var(--border); }
    .modal-tbl td { padding: 10px var(--s4); border-bottom: 1px solid var(--border); color: var(--dark-soft); }
    .modal-tbl tr:last-child td { border-bottom: none; }

    .order-total-row { display: flex; justify-content: space-between; font-size: .82rem; margin-bottom: 5px; color: var(--muted); }
    .order-total-row.grand { font-family: var(--fd); font-size: 1rem; font-weight: 600; color: var(--dark); padding-top: 10px; border-top: 1px solid var(--border); margin-top: 5px; }

    /* ── EMPTY STATE ── */
    .empty-state { text-align: center; padding: var(--s10) var(--s8); color: var(--muted-light); font-size: .85rem; }
    .empty-icon { font-size: 2rem; display: block; margin-bottom: var(--s3); opacity: .4; }

    /* ── LOADING BAR ── */
    .loading-bar { position: fixed; top: 0; left: 0; right: 0; height: 3px; background: linear-gradient(90deg, var(--rose), var(--rose-deep), var(--gold)); background-size: 200% 100%; animation: loading 1.2s linear infinite; z-index: 9999; display: none; }
    .loading-bar.active { display: block; }
    @keyframes loading { 0%{background-position:200% 0} 100%{background-position:-200% 0} }

    /* ── TOAST ── */
    .toast { position: fixed; bottom: 24px; right: 24px; background: var(--dark); color: var(--white); padding: 12px 20px; border-radius: var(--r-md); font-size: .8rem; z-index: 9999; opacity: 0; transform: translateY(8px); transition: all .25s; pointer-events: none; max-width: 320px; box-shadow: var(--sh-lg); }
    .toast.on { opacity: 1; transform: translateY(0); }
  </style>
  <script>
    (function () {
      try {
        history.scrollRestoration = 'manual';
      } catch (e) {}
      try {
        if (sessionStorage.getItem('orders_scroll_y') !== null) {
          document.documentElement.style.opacity = '0';
        }
      } catch (e) {}
    })();
  </script>
</head>
<body>

<div class="loading-bar" id="loadingBar"></div>

<div class="site-wrapper">

  <?php $GLOBALS['NAV_ACTIVE'] = 'orders'; require __DIR__ . '/includes/nav_sidebar.php'; ?>

  <!-- ── MAIN CONTENT ── -->
  <div class="site-content">

    <header class="topbar">
      <div class="topbar-left">
        <span class="topbar-title">Orders</span>
        <span class="topbar-bc">Bejewelry Admin › Orders</span>
      </div>
      <div class="topbar-right">
        <button class="icon-btn" title="Notifications" onclick="handleNotifications()">🔔<span class="dot" id="notifDot"></span></button>
        <button class="icon-btn" title="Refresh" onclick="loadPageData()">↺</button>
      </div>
    </header>

    <div class="content">

      <!-- PAGE HEADER -->
      <div class="page-hdr">
        <div>
          <h2>Orders</h2>
          <p id="ordersSubtitle">Loading…</p>
        </div>
        <div class="page-hdr-actions">
          <button class="btn btn-ghost btn-sm" onclick="handleExport()">⬇ Export CSV</button>
        </div>
      </div>

      <!-- STAT CARDS -->
      <div class="stats-row">
        <div class="stat-card">
          <span class="stat-icon">📦</span>
          <span class="stat-label">Total Orders</span>
          <div class="stat-value skel skel-val" id="valTotal">&nbsp;</div>
          <div class="stat-trend skel skel-text" id="trendTotal">&nbsp;</div>
        </div>
        <div class="stat-card">
          <span class="stat-icon">🕐</span>
          <span class="stat-label">Processing</span>
          <div class="stat-value skel skel-val" id="valPending">&nbsp;</div>
          <div class="stat-trend skel skel-text" id="trendPending">&nbsp;</div>
        </div>
        <div class="stat-card">
          <span class="stat-icon">✅</span>
          <span class="stat-label">Delivered</span>
          <div class="stat-value skel skel-val" id="valDelivered">&nbsp;</div>
          <div class="stat-trend skel skel-text" id="trendDelivered">&nbsp;</div>
        </div>
      </div>

      <!-- ALL ORDERS -->
      <div class="section-label">All Orders</div>

      <div class="filters-bar" id="filtersBar">
        <button class="fb on" data-status="all">All</button>
        <button class="fb" data-status="processing">Processing</button>
        <button class="fb" data-status="delivered">Delivered</button>
        <button class="fb" data-status="cancelled">Cancelled</button>
        <div class="filter-wrap">
          <input class="filter-input" type="text" id="orderSearch" placeholder="Search by order or customer…"/>
        </div>
      </div>

      <div class="table-wrap">
        <table class="tbl">
          <thead>
            <tr>
              <th><input type="checkbox" id="checkAllOrders" title="Select all visible"></th>
              <th>#Order</th>
              <th>Customer</th>
              <th>Item</th>
              <th>Total</th>
              <th>Payment</th>
              <th>Date</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody id="allOrdersBody">
            <tr><td colspan="9"><div class="skel skel-row" style="margin:14px var(--s5)"></div></td></tr>
            <tr><td colspan="9"><div class="skel skel-row" style="margin:14px var(--s5)"></div></td></tr>
            <tr><td colspan="9"><div class="skel skel-row" style="margin:14px var(--s5)"></div></td></tr>
            <tr><td colspan="9"><div class="skel skel-row" style="margin:14px var(--s5)"></div></td></tr>
            <tr><td colspan="9"><div class="skel skel-row" style="margin:14px var(--s5)"></div></td></tr>
          </tbody>
        </table>
      </div>

      <div class="pagination" id="pagination"></div>

    </div><!-- /content -->
  </div><!-- /site-content -->
</div><!-- /site-wrapper -->

<!-- ═══════ ORDER DETAIL MODAL ═══════ -->
<div class="modal-bg" id="orderModal">
  <div class="modal">
    <div class="modal-hdr">
      <h3 id="orderModalTitle">Order Details</h3>
      <button class="modal-close" onclick="closeModal('orderModal')">✕</button>
    </div>
    <div class="modal-info-grid">
      <div class="modal-info-item"><label>Customer</label><p id="modalCustomer">—</p></div>
      <div class="modal-info-item"><label>Order Date</label><p id="modalDate">—</p></div>
      <div class="modal-info-item"><label>Payment Method</label><p id="modalPayment">—</p></div>
      <div class="modal-info-item"><label>Delivery Address</label><p id="modalAddress">—</p></div>
      <div class="modal-info-item"><label>Status</label><p id="modalStatus">—</p></div>
      <div class="modal-info-item"><label>Contact</label><p id="modalContact">—</p></div>
      <div class="modal-info-item"><label>Courier</label><p id="modalCourier">—</p></div>
      <div class="modal-info-item"><label>Courier Assigned</label><p id="modalCourierAt">—</p></div>
    </div>
    <div class="proof-panel hidden" id="modalProofPanel">
      <div class="proof-panel-head">
        <div>
          <div class="proof-panel-title">Delivery Proof</div>
          <div class="proof-panel-meta" id="modalProofMeta">—</div>
        </div>
        <a class="btn btn-ghost btn-sm" id="modalProofLink" href="#" target="_blank" rel="noopener">Open proof</a>
      </div>
      <img class="proof-thumb" id="modalProofImg" alt="Delivery proof photo">
      <div class="proof-note" id="modalProofNote"></div>
    </div>
    <table class="modal-tbl">
      <thead><tr><th>Item</th><th>Qty</th><th>Unit Price</th><th>Subtotal</th></tr></thead>
      <tbody id="modalItemsBody">
        <tr><td colspan="4" style="text-align:center;color:var(--muted-light);padding:16px">Loading items…</td></tr>
      </tbody>
    </table>
    <div id="modalTotals"></div>
    <div class="modal-footer">
      <div style="margin-right:auto;display:flex;flex-direction:column;gap:10px;align-items:flex-start">
        <form id="statusForm" method="post" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
                    <?php echo csrf_token_field(); ?>
          <input type="hidden" name="action" value="update_status">
          <input type="hidden" name="order_id" id="statusOrderId" value="">
          <label class="form-label" for="modalStatusSelect" style="margin:0">Update status</label>
          <select class="form-select" id="modalStatusSelect" name="status" style="width:auto;min-width:190px">
            <option value="processing">Processing</option>
            <option value="shipped">Shipped</option>
            <option value="delivered">Delivered</option>
            <option value="cancelled">Cancelled</option>
          </select>
          <button type="submit" class="btn btn-primary btn-sm" id="modalApplyBtn" style="padding:7px 14px;font-size:.65rem">Apply</button>
        </form>
      </div>
      <button class="btn btn-ghost btn-sm" onclick="closeModal('orderModal')">Close</button>
    </div>
  </div>
</div>

<!-- ═══════ RESOLVE FLAGGED ORDER MODAL ═══════ -->
<div class="modal-bg" id="resolveModal">
  <div class="modal">
    <div class="modal-hdr">
      <h3 id="resolveModalTitle">Resolve Flagged Order</h3>
      <button class="modal-close" onclick="closeModal('resolveModal')">✕</button>
    </div>
    <div class="flag-context">
      <span class="flag-context-icon">⚠️</span>
      <div>
        <div class="flag-context-title">Flagged Issue</div>
        <div class="flag-context-desc" id="resolveIssueDesc">—</div>
      </div>
    </div>
    <div class="resolve-info-grid">
      <div class="resolve-info-box"><label>Flagged By</label><p id="resolveBy">—</p></div>
      <div class="resolve-info-box"><label>Date Flagged</label><p id="resolveDate">—</p></div>
      <div class="resolve-info-box"><label>Customer</label><p id="resolveCustomer">—</p></div>
      <div class="resolve-info-box"><label>Issue Type</label><p id="resolveIssueType">—</p></div>
    </div>
    <div class="form-group">
      <label class="form-label">Resolution Action</label>
      <select class="form-select" id="resolveAction">
        <option value="">Select resolution…</option>
        <option value="contact_retry">Contact customer to retry payment</option>
        <option value="alt_payment">Switch to alternative payment method</option>
        <option value="cancel_refund">Cancel and refund order</option>
        <option value="manual_verify">Mark as manually verified &amp; proceed</option>
        <option value="escalate">Escalate to finance team</option>
        <option value="update_address">Update delivery address</option>
        <option value="substitute">Offer substitute item</option>
        <option value="process_return">Process return &amp; refund</option>
      </select>
    </div>
    <div class="form-group">
      <label class="form-label">Resolution Notes</label>
      <textarea class="form-textarea" id="resolveNotes" placeholder="Add notes about how this issue was resolved or the steps taken…"></textarea>
    </div>
    <div class="form-group">
      <label class="form-label">Assign To</label>
      <select class="form-select" id="resolveAssignee">
        <!-- Populated from API: auth/staff_list.php -->
      </select>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost btn-sm" onclick="closeModal('resolveModal')">Cancel</button>
      <button class="btn btn-primary btn-sm" id="resolveSubmitBtn">Mark as Resolved</button>
    </div>
  </div>
</div>

<div class="toast" id="toastEl"></div>

<script>window.__ORDERS__ = <?= json_encode($ordersData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;</script>
<script src="whoami.js?v=1"></script>
<script src="../admin/notif_dot.js?v=1"></script>
<script src="../admin/confirm_modal.js?v=1"></script>
<script>
  /* BEJEWELRY ADMIN — Orders (MySQL only, no API). Data from window.__ORDERS__ */

  const D = window.__ORDERS__ || {};
  let currentPage = D.page || 1;
  let currentStatus = D.status || 'all';
  let currentSearch = D.search || '';
  let currentOrderId = '';
  const scrollKey = 'orders_scroll_y';

  function saveScrollPosition() {
    sessionStorage.setItem(scrollKey, String(window.scrollY || 0));
  }

  function restoreScrollPositionOnce() {
    const raw = sessionStorage.getItem(scrollKey);
    if (raw === null) {
      document.documentElement.style.opacity = '1';
      return;
    }
    const savedY = Number(raw);
    sessionStorage.removeItem(scrollKey);
    requestAnimationFrame(function () {
      requestAnimationFrame(function () {
        if (!Number.isNaN(savedY) && savedY > 0) {
          window.scrollTo({ top: savedY, behavior: 'instant' });
        }
        document.documentElement.style.transition = 'opacity 0.15s ease';
        document.documentElement.style.opacity = '1';
      });
    });
  }

  function ordersUrl() {
    const p = new URLSearchParams();
    if (currentPage > 1) p.set('page', currentPage);
    if (currentStatus !== 'all') p.set('status', currentStatus);
    if (currentSearch) p.set('search', currentSearch);
    return 'orders.php?' + p.toString();
  }

  function navigateWithinOrders(url) {
    saveScrollPosition();
    location.href = url;
  }

  function toast(msg, duration = 2800) {
    const el = document.getElementById('toastEl');
    el.textContent = msg;
    el.classList.add('on');
    setTimeout(() => el.classList.remove('on'), duration);
  }

  function setLoading(on) {
    document.getElementById('loadingBar').classList.toggle('active', on);
  }

  function esc(str) {
    const d = document.createElement('div');
    d.textContent = str ?? '';
    return d.innerHTML;
  }

  function formatCurrency(amount) {
    return '₱' + Number(amount || 0).toLocaleString('en-PH');
  }

  function formatDate(dateStr) {
    if (!dateStr) return '—';
    return new Date(dateStr).toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' });
  }

  function clearSkel(el) {
    el.classList.remove('skel', 'skel-val', 'skel-text', 'skel-row');
  }

  function capitalize(s) {
    return s ? s.charAt(0).toUpperCase() + s.slice(1) : '';
  }

  function initials(name) {
    return (name || '?').split(' ').map(w => w[0]).join('').slice(0, 2).toUpperCase();
  }

  function badgeClass(status) {
    const map = { pending: 'b-pending', processing: 'b-processing', shipped: 'b-shipped', delivered: 'b-delivered', cancelled: 'b-cancelled' };
    return map[(status || '').toLowerCase()] || 'b-pending';
  }

  function loadUser() {
    const data = D.user;
    document.getElementById('sbAvatar').textContent = initials(data && data.name);
    document.getElementById('sbUsername').textContent = (data && data.name) || 'Admin';
    document.getElementById('sbUserRole').textContent = (data && data.role) || '—';
  }

  function loadBadges() {
    const b = D.badges || {};
    const setBadge = (id, val) => {
      const el = document.getElementById(id);
      if (!el || val === undefined || val === null || val === 0) return;
      el.textContent = String(val);
    };
    setBadge('badgeOrders', b.processing_orders);
    setBadge('badgeProducts', b.new_products);
    setBadge('badgeInventory', b.low_stock);
    setBadge('badgeReviews', b.pending_reviews);
  }

  function loadStats() {
    const s = D.stats || {};
    const setVal = (id, val) => { const el = document.getElementById(id); clearSkel(el); el.textContent = val ?? '—'; };
    const setTrend = (id, text) => { const el = document.getElementById(id); clearSkel(el); el.textContent = text ?? ''; };
    setVal('valTotal', s.total);
    setVal('valPending', s.processing);
    setVal('valDelivered', s.delivered);
    setTrend('trendTotal', s.month_label || 'This month');
    setTrend('trendPending', 'Awaiting courier assignment');
    setTrend('trendDelivered', 'Completed orders');
    document.getElementById('ordersSubtitle').textContent = 'Manage customer orders' + (s.total ? ' — ' + s.total + ' this month' : '');
  }


  function loadAllOrders() {
    const tbody = document.getElementById('allOrdersBody');
    const orders = D.orders || [];
    const counts = D.counts || {};

    document.querySelectorAll('#filtersBar .fb').forEach(btn => {
      const s = btn.dataset.status;
      const count = s === 'all' ? (counts.all) : counts[s];
      btn.textContent = count !== undefined ? capitalize(s) + ' (' + count + ')' : capitalize(s);
      btn.classList.toggle('on', (s || 'all') === currentStatus);
    });

    if (orders.length === 0) {
      tbody.innerHTML = '<tr><td colspan="9"><div class="empty-state"><span class="empty-icon">📭</span>No orders found</div></td></tr>';
      renderPagination(0, 0);
      return;
    }

    tbody.innerHTML = orders.map(o => {
      const id = esc(o.id);
      const idAttr = esc(String(o.id)).replace(/"/g, '&quot;');
      return '<tr><td><input type="checkbox" class="order-check" value="' + idAttr + '"></td><td><span class="order-id">#' + id + '</span></td><td style="font-weight:500">' + esc(o.customer_name) + '</td><td style="color:var(--muted)">' + esc(o.item_name) + '</td><td class="amount">' + formatCurrency(o.total) + '</td><td><span class="pay-chip">' + esc(o.payment_method) + '</span></td><td style="font-size:.75rem;color:var(--muted-light)">' + formatDate(o.created_at) + '</td><td><span class="badge ' + badgeClass(o.status) + '">' + esc(o.status) + '</span></td><td><button class="btn btn-ghost btn-sm" onclick="openOrderModal(this.getAttribute(\'data-id\'))" data-id="' + idAttr + '" style="padding:6px 14px;font-size:.65rem">View</button></td></tr>';
    }).join('');

    const checkAll = document.getElementById('checkAllOrders');
    if (checkAll) checkAll.checked = false;

    renderPagination(D.total_pages || 1, D.page || 1);
  }

  function renderPagination(totalPages, page) {
    const wrap = document.getElementById('pagination');
    if (totalPages <= 1) { wrap.innerHTML = ''; return; }
    let html = '<button class="page-btn" onclick="navigateWithinOrders(\'orders.php?page=' + (page - 1) + '&status=' + encodeURIComponent(currentStatus) + '&search=' + encodeURIComponent(currentSearch) + '\')"' + (page === 1 ? ' disabled' : '') + '>‹</button>';
    for (let i = 1; i <= totalPages; i++) {
      if (i === 1 || i === totalPages || (i >= page - 1 && i <= page + 1)) {
        html += '<button class="page-btn' + (i === page ? ' active' : '') + '" onclick="navigateWithinOrders(\'orders.php?page=' + i + '&status=' + encodeURIComponent(currentStatus) + '&search=' + encodeURIComponent(currentSearch) + '\')">' + i + '</button>';
      } else if (i === page - 2 || i === page + 2) {
        html += '<span style="color:var(--muted-light);padding:0 4px">…</span>';
      }
    }
    html += '<button class="page-btn" onclick="navigateWithinOrders(\'orders.php?page=' + (page + 1) + '&status=' + encodeURIComponent(currentStatus) + '&search=' + encodeURIComponent(currentSearch) + '\')"' + (page === totalPages ? ' disabled' : '') + '>›</button>';
    wrap.innerHTML = html;
  }

  function openOrderModal(orderId) {
    currentOrderId = orderId;
    const data = (D.order_details || {})[orderId];
    document.getElementById('orderModalTitle').textContent = 'Order #' + orderId + ' — Details';
    if (!data) {
      document.getElementById('modalCustomer').textContent = '—';
      document.getElementById('modalDate').textContent = '—';
      document.getElementById('modalPayment').textContent = '—';
      document.getElementById('modalAddress').textContent = '—';
      document.getElementById('modalContact').textContent = '—';
      document.getElementById('modalCourier').textContent = '—';
      document.getElementById('modalCourierAt').textContent = '—';
      document.getElementById('modalStatus').innerHTML = '—';
      document.getElementById('statusOrderId').value = '';
      document.getElementById('assignOrderId').value = '';
      document.getElementById('modalCourierSelect').value = '';
      document.getElementById('modalProofPanel').classList.add('hidden');
      document.getElementById('modalItemsBody').innerHTML = '<tr><td colspan="4" style="text-align:center;color:var(--muted-light);padding:12px">No details</td></tr>';
      document.getElementById('modalTotals').innerHTML = '';
      openModal('orderModal');
      return;
    }
    document.getElementById('modalCustomer').textContent = data.customer_name || '—';
    document.getElementById('modalDate').textContent = formatDate(data.created_at);
    document.getElementById('modalPayment').textContent = data.payment_method || '—';
    document.getElementById('modalAddress').textContent = data.shipping_address || '—';
    document.getElementById('modalContact').textContent = data.customer_contact || '—';
    document.getElementById('modalCourier').textContent = data.courier_name || '—';
    document.getElementById('modalCourierAt').textContent = data.courier_assigned_at ? formatDate(data.courier_assigned_at) : '—';
    document.getElementById('modalStatus').innerHTML = '<span class="badge ' + badgeClass(data.status) + '">' + esc(data.status) + '</span>';
    const proof = data.delivery_proof || null;
    const proofPanel = document.getElementById('modalProofPanel');
    if (proof && proof.proof_url) {
      proofPanel.classList.remove('hidden');
      document.getElementById('modalProofMeta').textContent = [proof.carrier_name || 'Carrier', proof.delivered_at ? formatDate(proof.delivered_at) : '', proof.carrier_reference ? 'Ref: ' + proof.carrier_reference : ''].filter(Boolean).join(' · ');
      document.getElementById('modalProofImg').src = proof.proof_url;
      document.getElementById('modalProofLink').href = proof.proof_url;
      document.getElementById('modalProofNote').textContent = proof.note || 'Carrier delivery proof submitted.';
    } else {
      proofPanel.classList.add('hidden');
      document.getElementById('modalProofMeta').textContent = '—';
      document.getElementById('modalProofImg').removeAttribute('src');
      document.getElementById('modalProofLink').href = '#';
      document.getElementById('modalProofNote').textContent = '';
    }
    const so = document.getElementById('statusOrderId');
    if (so) so.value = orderId;
    const ao = document.getElementById('assignOrderId');
    if (ao) ao.value = orderId;
    const courierSel = document.getElementById('modalCourierSelect');
    if (courierSel) courierSel.value = String(data.courier_user_id || '');
    const sel = document.getElementById('modalStatusSelect');
    if (sel) {
      sel.value = (data.status || 'processing').toLowerCase();
      if (sel.value === 'pending') sel.value = 'processing';
    }
    const items = data.items || [];
    let subtotal = 0;
    document.getElementById('modalItemsBody').innerHTML = items.map(item => {
      const sub = (item.qty || 1) * (item.unit_price || 0);
      subtotal += sub;
      return '<tr><td>' + esc(item.name) + '</td><td>' + esc(item.qty) + '</td><td>' + formatCurrency(item.unit_price) + '</td><td>' + formatCurrency(sub) + '</td></tr>';
    }).join('') || '<tr><td colspan="4" style="text-align:center;color:var(--muted-light);padding:12px">No items</td></tr>';
    const shipping = data.shipping_fee || 0;
    document.getElementById('modalTotals').innerHTML = '<div class="order-total-row"><span>Subtotal</span><span>' + formatCurrency(subtotal) + '</span></div><div class="order-total-row"><span>Shipping</span><span>' + formatCurrency(shipping) + '</span></div><div class="order-total-row grand"><span>Total</span><span>' + formatCurrency(subtotal + shipping) + '</span></div>';
    openModal('orderModal');
  }

  function openResolveModal(flaggedOrderId) {
    toast('Flagged orders not available in MySQL-only mode.');
  }

  function wireStatusForm() {
    const statusForm = document.getElementById('statusForm');
    if (!statusForm) return;
    statusForm.action = ordersUrl();
    statusForm.addEventListener('submit', (e) => {
      const sel = document.getElementById('modalStatusSelect');
      const s = (sel && sel.value) ? String(sel.value).toLowerCase() : '';
      if (!['processing','shipped','delivered','cancelled'].includes(s)) {
        e.preventDefault();
        toast('Invalid status.');
      }
      const idEl = document.getElementById('statusOrderId');
      if (!idEl || !idEl.value) {
        e.preventDefault();
        toast('Order not selected.');
      }
    });
  }

  function wireCourierForm() {
    const courierForm = document.getElementById('assignCourierForm');
    if (!courierForm) return;
    courierForm.action = ordersUrl();
    courierForm.addEventListener('submit', (e) => {
      const idEl = document.getElementById('assignOrderId');
      const courierSel = document.getElementById('modalCourierSelect');
      const courierId = courierSel ? String(courierSel.value || '').trim() : '';
      if (!idEl || !idEl.value) {
        e.preventDefault();
        toast('Order not selected.');
        return;
      }
      if (!courierId) {
        e.preventDefault();
        toast('Please select a courier account.');
      }
    });
  }

  function wireBulkAssignForm() {
    const bulkForm = document.getElementById('bulkAssignForm');
    if (!bulkForm) return;
    bulkForm.action = ordersUrl();
    bulkForm.addEventListener('submit', (e) => {
      const courierSel = document.getElementById('bulkCourierId');
      const courierId = courierSel ? String(courierSel.value || '').trim() : '';
      const orderIds = Array.from(document.querySelectorAll('.order-check:checked')).map(el => String(el.value || '').trim()).filter(Boolean);
      if (!courierId) {
        e.preventDefault();
        toast('Select a courier account first.');
        return;
      }
      if (orderIds.length === 0) {
        e.preventDefault();
        toast('Select at least one order.');
        return;
      }
      const hidden = document.getElementById('bulkOrderIds');
      if (hidden) hidden.value = orderIds.join(',');
    });
  }

  document.querySelectorAll('#filtersBar .fb').forEach(btn => {
    btn.addEventListener('click', function () {
      const st = this.dataset.status || 'all';
      navigateWithinOrders('orders.php?page=1&status=' + encodeURIComponent(st) + '&search=' + encodeURIComponent(currentSearch));
    });
  });

  document.getElementById('orderSearch').value = currentSearch;
  document.getElementById('orderSearch').addEventListener('change', function () {
    navigateWithinOrders('orders.php?page=1&status=' + encodeURIComponent(currentStatus) + '&search=' + encodeURIComponent(this.value.trim()));
  });
  document.getElementById('orderSearch').addEventListener('keydown', function (e) {
    if (e.key === 'Enter') {
      navigateWithinOrders('orders.php?page=1&status=' + encodeURIComponent(currentStatus) + '&search=' + encodeURIComponent(this.value.trim()));
    }
  });

  const checkAllEl = document.getElementById('checkAllOrders');
  if (checkAllEl) {
    checkAllEl.addEventListener('change', function () {
      document.querySelectorAll('.order-check').forEach(cb => {
        cb.checked = !!checkAllEl.checked;
      });
    });
  }

  function openModal(id) { document.getElementById(id).classList.add('on'); }
  function closeModal(id) { document.getElementById(id).classList.remove('on'); }
  document.querySelectorAll('.modal-bg').forEach(m => m.addEventListener('click', e => { if (e.target === m) m.classList.remove('on'); }));

  function handleExport() {
    location.href = 'orders_export.php?status=' + encodeURIComponent(currentStatus) + '&search=' + encodeURIComponent(currentSearch);
  }
  function handleExportFlagged() {
    location.href = 'orders_export.php?flagged=1';
  }
  function handleNotifications() { location.href = 'notifications.php'; }
  function handleLogout() {
    if (typeof window.adminConfirm === 'function') {
      window.adminConfirm('Log out of Bejewelry Admin?', function () { location.href = '../logout.php'; }, { okText: 'Log out' });
      return;
    }
    location.href = '../logout.php';
  }

  function loadPageData() {
    loadUser();
    loadBadges();
    loadStats();
    loadAllOrders();
    wireStatusForm();
    wireCourierForm();
    wireBulkAssignForm();
  }

  document.querySelectorAll('form').forEach(function (form) {
    form.addEventListener('submit', function () {
      saveScrollPosition();
    });
  });

  document.querySelectorAll('a[href]').forEach(function (link) {
    link.addEventListener('click', function () {
      const href = (link.getAttribute('href') || '').trim();
      if (href === '' || href.startsWith('#') || href.startsWith('javascript:') || href.startsWith('mailto:') || href.startsWith('tel:')) {
        return;
      }
      try {
        const target = new URL(link.href, window.location.href);
        if (target.origin === window.location.origin && target.pathname === window.location.pathname) {
          saveScrollPosition();
        }
      } catch (e) {}
    });
  });

  window.addEventListener('load', function () {
    restoreScrollPositionOnce();
  });

  document.addEventListener('DOMContentLoaded', loadPageData);
</script>
</body>
</html>