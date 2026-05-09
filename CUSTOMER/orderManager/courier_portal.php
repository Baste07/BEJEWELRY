<?php
declare(strict_types=1);

require_once __DIR__ . '/courier_auth.php';
require_once __DIR__ . '/../api/carrier_delivery_helpers.php';

$courierUser = courier_require_login();
$courierName = trim((string) (($courierUser['first_name'] ?? '') . ' ' . ($courierUser['last_name'] ?? '')));
if ($courierName === '') {
  $courierName = (string) ($courierUser['email'] ?? 'Courier');
}

$error = '';
$success = '';
$lastResult = null;

// Basic AJAX detection used for multiple handlers below
$isAjaxRequest = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
  || (strpos((string) ($_SERVER['HTTP_ACCEPT'] ?? ''), 'application/json') !== false)
  || (isset($_REQUEST['ajax']) && (string) $_REQUEST['ajax'] === '1');

// Allow couriers to pick unassigned orders themselves. Uses an atomic UPDATE
// so once an order is picked other couriers cannot claim it.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'pick_order') {
  csrf_validate();
  $orderId = trim((string) ($_POST['pick_order_id'] ?? ''));
  $isAjax = $isAjaxRequest;

  if ($orderId === '' || !preg_match('/^[A-Za-z0-9-]+$/', $orderId)) {
    if ($isAjax) {
      header('Content-Type: application/json');
      echo json_encode(['ok' => false, 'message' => 'Invalid order id.']);
      exit;
    }
    $error = 'Invalid order id.';
  } else {
    try {
      $pdo = db();
      // Use tracking based on year+order to avoid sequence race conditions.
      $year = date('Y');
      $tracking = 'TRACK-' . $year . '-' . $orderId;
      $stmt = $pdo->prepare("UPDATE orders SET courier_user_id = ?, courier_name = ?, courier_assigned_at = NOW(), tracking_number = ?, status = 'shipped' WHERE id = ? AND courier_user_id IS NULL AND status = 'processing'");
      $stmt->execute([(int) ($courierUser['id'] ?? 0), $courierName, $tracking, $orderId]);
      if ($stmt->rowCount() > 0) {
        if ($isAjax) {
          header('Content-Type: application/json');
          echo json_encode(['ok' => true, 'message' => 'Order picked successfully.', 'orderId' => $orderId, 'tracking' => $tracking]);
          exit;
        }
        $success = 'Order #' . htmlspecialchars($orderId, ENT_QUOTES, 'UTF-8') . ' picked successfully.';
      } else {
        if ($isAjax) {
          header('Content-Type: application/json');
          echo json_encode(['ok' => false, 'message' => 'Could not pick order. It may have been picked by someone else or is no longer available.']);
          exit;
        }
        $error = 'Could not pick order. It may have been picked by someone else or is no longer available.';
      }
    } catch (Throwable $e) {
      if ($isAjax) {
        header('Content-Type: application/json');
        echo json_encode(['ok' => false, 'message' => 'Could not pick order.']);
        exit;
      }
      $error = 'Could not pick order.';
    }
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'confirm_delivery') {
    csrf_validate();
    try {
    $_POST['carrier_name'] = $courierName;
    $_POST['courier_user_id'] = (string) ((int) ($courierUser['id'] ?? 0));
        $lastResult = carrier_confirm_delivery($_POST, $_FILES['proof_photo'] ?? []);
        $success = 'Delivery confirmed. The order status is now Delivered.';
        if ($isAjaxRequest) {
          header('Content-Type: application/json');
          echo json_encode(['ok' => true, 'message' => $success]);
          exit;
        }
    } catch (Throwable $e) {
        $error = $e->getMessage() ?: 'Could not confirm delivery.';
        if ($isAjaxRequest) {
          header('Content-Type: application/json');
          echo json_encode(['ok' => false, 'message' => $error]);
          exit;
        }
    }
}

$recent = [];
{
    $stmt = db()->prepare(
    'SELECT p.order_id, p.carrier_name, p.carrier_reference, p.proof_photo, p.note, p.delivered_at, p.created_at, o.status
         FROM order_delivery_proofs p
         JOIN orders o ON o.id = p.order_id
     WHERE o.courier_user_id = ?
         ORDER BY COALESCE(p.delivered_at, p.created_at) DESC, p.id DESC
         LIMIT 8'
    );
  $stmt->execute([(int) ($courierUser['id'] ?? 0)]);
    $recent = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$assignedStmt = db()->prepare(
  "SELECT id, status, ship_name, ship_street, ship_city, ship_province, ship_zip, ship_phone, created_at, courier_assigned_at, tracking_number
   FROM orders
   WHERE courier_user_id = ?
     AND status IN ('shipped', 'processing')
   ORDER BY COALESCE(courier_assigned_at, created_at) DESC
   LIMIT 100"
);
$assignedStmt->execute([(int) ($courierUser['id'] ?? 0)]);
$assignedOrders = $assignedStmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($assignedOrders as &$orderRow) {
  $orderRow = bejewelry_decrypt_order_shipping_fields($orderRow);
}
unset($orderRow);

// Delivered orders (those with status = 'delivered') for this courier
$deliveredStmt = db()->prepare(
  "SELECT o.id, o.tracking_number, o.ship_name, o.ship_phone, o.ship_city, o.total, o.created_at, COALESCE(p.delivered_at, p.created_at) AS delivered_at,
          (SELECT SUM(qty) FROM order_items WHERE order_id = o.id) AS item_count, p.proof_photo
   FROM orders o
   LEFT JOIN order_delivery_proofs p ON p.order_id = o.id
   WHERE o.courier_user_id = ? AND o.status = 'delivered'
   ORDER BY COALESCE(p.delivered_at, p.created_at, o.created_at) DESC
   LIMIT 200"
);
$deliveredStmt->execute([(int) ($courierUser['id'] ?? 0)]);
$deliveredOrders = $deliveredStmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($deliveredOrders as &$orderRow) {
  $orderRow = bejewelry_decrypt_order_shipping_fields($orderRow);
}
unset($orderRow);

// Counts and item quantities
$countsStmt = db()->prepare("SELECT
  (SELECT COUNT(*) FROM orders WHERE courier_user_id = ? AND COALESCE(status,'') != 'delivered') AS to_deliver_count,
  (SELECT COALESCE(SUM(oi.qty),0) FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE o.courier_user_id = ? AND COALESCE(o.status,'') != 'delivered') AS to_deliver_items,
  (SELECT COUNT(*) FROM orders WHERE courier_user_id = ? AND COALESCE(status,'') = 'delivered') AS delivered_count,
  (SELECT COALESCE(SUM(oi.qty),0) FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE o.courier_user_id = ? AND COALESCE(o.status,'') = 'delivered') AS delivered_items
");
$countsStmt->execute([(int) ($courierUser['id'] ?? 0),(int) ($courierUser['id'] ?? 0),(int) ($courierUser['id'] ?? 0),(int) ($courierUser['id'] ?? 0)]);
$counts = $countsStmt->fetch(PDO::FETCH_ASSOC);

// Fetch recently created unassigned processing orders for couriers to pick
$availableStmt = db()->prepare(
  "SELECT o.id, o.ship_name, o.ship_phone, o.ship_city, o.total, o.created_at, (SELECT oi.name FROM order_items oi WHERE oi.order_id = o.id LIMIT 1) AS item_name
   FROM orders o
   WHERE o.status = 'processing' AND o.courier_user_id IS NULL
   ORDER BY o.created_at ASC
   LIMIT 30"
);
$availableStmt->execute();
$availableOrders = $availableStmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($availableOrders as &$orderRow) {
  $orderRow = bejewelry_decrypt_order_shipping_fields($orderRow);
}
unset($orderRow);

// If this is an AJAX refresh request, return JSON with both lists
if ((isset($_GET['ajax']) && (string) $_GET['ajax'] === '1') && (isset($_GET['action']) && $_GET['action'] === 'refresh')) {
  header('Content-Type: application/json');
  echo json_encode([
    'assignedOrders' => $assignedOrders,
    'availableOrders' => $availableOrders,
    'deliveredOrders' => $deliveredOrders,
    'counts' => $counts,
    'recent' => $recent
  ]);
  exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <?= csrf_meta_tag() ?>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Courier Portal — Bejewelry</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>
    *{box-sizing:border-box} body{margin:0;font-family:'DM Sans',system-ui,sans-serif;background:linear-gradient(180deg,#fff7f9 0%,#fdf1f4 100%);color:#241418;min-height:100vh}
    .wrap{max-width:1180px;margin:0 auto;padding:24px}
    .hero{display:flex;align-items:center;justify-content:space-between;gap:20px;padding:22px 24px;border:1px solid #ead8df;border-radius:24px;background:rgba(255,255,255,.82);backdrop-filter:blur(6px);box-shadow:0 8px 30px rgba(160,40,60,.08)}
    .brand{font-family:'Playfair Display',serif;font-size:1.8rem;font-weight:700}
    .sub{font-size:.72rem;letter-spacing:.22em;text-transform:uppercase;color:#d96070;margin-top:4px}
    .grid{display:grid;grid-template-columns:1.1fr .9fr;gap:20px;margin-top:20px}
    .card{background:#fff;border:1px solid #ead8df;border-radius:22px;padding:22px;box-shadow:0 8px 30px rgba(160,40,60,.08)}
    h1,h2{font-family:'Playfair Display',serif;margin:0 0 12px}
    h1{font-size:2rem} h2{font-size:1.15rem}
    .muted{color:#8f707c;font-size:.9rem;line-height:1.7}
    .msg{padding:12px 14px;border-radius:14px;margin-bottom:16px;font-size:.92rem}
    .err{background:#ffe8e8;color:#7b1d1d;border:1px solid #e1b0b0}.ok{background:#e7fff2;color:#145a34;border:1px solid #b4e2c4}
    .row{display:grid;grid-template-columns:1fr 1fr;gap:14px}
    .fg{margin-bottom:14px}.fl{display:block;font-size:.63rem;font-weight:700;letter-spacing:.15em;text-transform:uppercase;color:#8f707c;margin-bottom:7px}
    .fi, .sel, .ta{width:100%;padding:12px 14px;border:1.5px solid #e0cbd3;border-radius:14px;font:inherit;background:#fff;outline:none}
    .fi:focus,.sel:focus,.ta:focus{border-color:#d96070}
    .ta{min-height:92px;resize:vertical}
    .btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;width:100%;padding:12px 16px;border:none;border-radius:999px;background:linear-gradient(135deg,#d96070,#b03050);color:#fff;font-weight:700;letter-spacing:.06em;text-transform:uppercase;cursor:pointer;box-shadow:0 6px 20px rgba(176,48,80,.22)}
    .btn-ghost{width:auto;background:#fff;color:#8f707c;border:1.5px solid #e0cbd3;box-shadow:none}
    .btn.loading{opacity:.9;pointer-events:none}
    .btn .spinner{display:inline-block;width:16px;height:16px;border:2px solid rgba(255,255,255,.35);border-top-color:#fff;border-radius:50%;animation:spin .8s linear infinite;margin-right:8px;vertical-align:middle}
    @keyframes spin{to{transform:rotate(360deg)}}
    /* Delivered modal */
    #deliveredModal{position:fixed;left:50%;top:18%;transform:translateX(-50%);background:#fff;border-radius:12px;padding:18px 20px;box-shadow:0 18px 50px rgba(0,0,0,.18);z-index:1200;display:none;font-weight:700;color:#145a34}
    #deliveredModal.show{display:block}
    .pill{display:inline-flex;padding:5px 10px;border-radius:999px;font-size:.62rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;background:#fff7d6;color:#8c6800;border:1px solid #edd050}
    .recent{display:grid;gap:12px}.recent-item{padding:14px;border:1px solid #ead8df;border-radius:16px;background:#fff}
    .recent-head{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:6px}.recent-id{font-family:'Playfair Display',serif;font-size:1rem;font-weight:600}
    .recent-meta{font-size:.82rem;color:#8f707c;line-height:1.6}
    .preview{margin-top:12px;display:none}.preview img{width:100%;max-height:240px;object-fit:cover;border-radius:14px;border:1px solid #ead8df}
    .top-actions{display:flex;align-items:center;gap:10px;flex-wrap:wrap}
    .order-list-item{background:#f9f5f7;border:1.5px solid #e0cbd3;border-radius:14px;padding:14px;cursor:pointer;transition:all .2s;text-align:left;font-family:inherit;font-size:inherit;width:100%;text-align:left;border:none}
    .order-list-item:hover{background:#f0e8ec;border-color:#d96070}
    .orders-modal{display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(25,15,20,.52);z-index:999;align-items:center;justify-content:center;padding:20px}
    .orders-modal-card{background:#fff;border-radius:22px;padding:26px;max-width:760px;width:100%;max-height:88vh;overflow-y:auto;box-shadow:0 26px 70px rgba(0,0,0,.30);border:1px solid #ead8df}
    .orders-tabs{display:flex;gap:10px;margin:2px 0 14px}
    .orders-list-sticky{position:sticky;top:0;z-index:4;background:#fff;padding:2px 0 10px;border-bottom:1px solid #f0e0e6;margin-bottom:10px}
    .orders-list-heading{font-family:'Playfair Display',serif;font-size:1rem;font-weight:700;margin-bottom:10px}
    .orders-search{width:100%;padding:10px 12px;border:1.5px solid #e0cbd3;border-radius:12px;font:inherit;color:#533542;background:#fff;margin:0 0 10px}
    .orders-search:focus{outline:none;border-color:#d96070;box-shadow:0 0 0 3px rgba(217,96,112,.12)}
    .modal-tab{appearance:none;border:1px solid #e0cbd3;background:#fff7fa;color:#8f707c;border-radius:999px;padding:8px 14px;font-size:.84rem;font-weight:700;cursor:pointer;transition:all .2s}
    .modal-tab:hover{border-color:#d96070;color:#6f2b43;background:#fff}
    .modal-tab.active{background:linear-gradient(135deg,#d96070,#b03050);border-color:#b03050;color:#fff;box-shadow:0 6px 18px rgba(176,48,80,.22)}
    .view-detail-btn{margin-top:10px;display:inline-flex;align-items:center;justify-content:center;padding:7px 12px;border:1px solid #d9b8c3;border-radius:999px;background:#fff;color:#7d4758;font-size:.78rem;font-weight:700;letter-spacing:.04em;text-transform:uppercase;cursor:pointer}
    .view-detail-btn:hover{border-color:#b03050;color:#b03050}
    .detail-modal{display:none;position:fixed;inset:0;background:rgba(20,12,16,.58);z-index:1050;align-items:center;justify-content:center;padding:18px}
    .detail-modal-card{background:#fff;border:1px solid #ead8df;border-radius:20px;padding:20px;max-width:620px;width:100%;max-height:88vh;overflow:auto;box-shadow:0 24px 64px rgba(0,0,0,.28)}
    .detail-kv{font-size:.92rem;color:#6f5460;line-height:1.7}
    .detail-proof{margin-top:12px;border:1px solid #ead8df;border-radius:14px;overflow:hidden;background:#fff7fa}
    .detail-proof img{display:block;width:100%;max-height:360px;object-fit:contain;background:#fff}
    .detail-proof-empty{padding:14px;color:#8f707c;font-size:.9rem}
    @media (max-width: 900px){.grid{grid-template-columns:1fr}.hero{flex-direction:column;align-items:flex-start}.row{grid-template-columns:1fr}}
  </style>
  <script>
    (function () {
      try {
        history.scrollRestoration = 'manual';
      } catch (e) {}
      try {
        if (sessionStorage.getItem('courier_portal_scroll_y') !== null) {
          document.documentElement.style.opacity = '0';
        }
      } catch (e) {}
    })();
  </script>
</head>
<body>
  <div class="wrap">
    <div class="hero">
      <div>
        <div class="brand">Bejewelry Courier Portal</div>
        <div class="sub">Delivery proof upload · <?php echo htmlspecialchars($courierName, ENT_QUOTES, 'UTF-8'); ?></div>
      </div>
      <div class="top-actions">
        <span class="pill">Photo proof required</span>
        <a class="btn btn-ghost" href="../logout.php" style="text-decoration:none;display:inline-flex;width:auto;padding:10px 16px">Logout</a>
      </div>
    </div>

    <?php if ($error !== ''): ?><div class="msg err"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
    <?php if ($success !== ''): ?><div class="msg ok"><?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>

      <div class="grid">
        <div class="card">
          <h1>Confirm Delivery</h1>
          <p class="muted">Submit proof for your assigned deliveries. Order manager assignment automatically appears here.</p>
          <form id="confirmDeliveryForm" method="post" enctype="multipart/form-data" style="margin-top:18px">
                        <?php echo csrf_token_field(); ?>
            <input type="hidden" name="action" value="confirm_delivery">
            <div class="row">
              <div class="fg">
                <label class="fl">Order ID</label>
                <div style="display:grid;grid-template-columns:1fr auto;gap:8px">
                  <input class="fi" type="text" id="orderIdInput" name="order_id" placeholder="BJ-2026-0007" required>
                  <button class="btn" type="button" id="openOrdersModal" style="width:auto;min-width:140px;padding:12px 14px;font-size:.82rem">Quick Select</button>
                </div>
              </div>
              <div class="fg">
                <label class="fl">Carrier Name</label>
                <input class="fi" type="text" name="carrier_name" value="<?= htmlspecialchars($courierName, ENT_QUOTES, 'UTF-8') ?>" readonly>
              </div>
            </div>
            <div class="row">
              <div class="fg">
                <label class="fl">Tracking Number (Auto)</label>
                <input class="fi" type="text" id="trackingNumberInput" name="tracking_number_display" readonly placeholder="Fills when order selected">
              </div>
            </div>
            <div class="row">
              <div class="fg">
                <label class="fl">Delivered At</label>
                <input class="fi" type="datetime-local" name="delivered_at">
              </div>
            </div>
            <div class="fg">
              <label class="fl">Proof Photo</label>
              <input class="fi" type="file" name="proof_photo" accept="image/png,image/jpeg,image/webp" required>
            </div>
            <div class="fg">
              <label class="fl">Note</label>
              <textarea class="ta" name="note" placeholder="Optional delivery note..."></textarea>
            </div>
            <button id="confirmDeliveryBtn" class="btn" type="submit"><span class="spinner" aria-hidden="true" style="display:none"></span><span class="btn-text">Confirm Delivery</span></button>
          </form>

          <h2 style="margin-top:24px">Orders to Deliver <span id="toDeliverBadge" class="pill"><?php $deliverOrders = (int) ($counts['to_deliver_count'] ?? count($assignedOrders)); echo $deliverOrders . ' ' . ($deliverOrders === 1 ? 'order' : 'orders') . ' to deliver'; ?></span></h2>
          <?php if (empty($assignedOrders)): ?>
            <p class="muted">No assigned orders yet.</p>
          <?php else: ?>
            <div class="recent">
              <?php foreach ($assignedOrders as $ao): ?>
                <div class="recent-item">
                  <div class="recent-head">
                    <div class="recent-id">#<?= htmlspecialchars((string) $ao['id'], ENT_QUOTES, 'UTF-8') ?></div>
                    <span class="pill"><?= htmlspecialchars((string) $ao['status'], ENT_QUOTES, 'UTF-8') ?></span>
                  </div>
                  <div class="recent-meta">
                    Customer: <?= htmlspecialchars((string) ($ao['ship_name'] ?? '—'), ENT_QUOTES, 'UTF-8') ?><br>
                    Contact: <?= htmlspecialchars((string) ($ao['ship_phone'] ?? '—'), ENT_QUOTES, 'UTF-8') ?><br>
                    City: <?= htmlspecialchars((string) ($ao['ship_city'] ?? '—'), ENT_QUOTES, 'UTF-8') ?><br>
                    Assigned: <?= htmlspecialchars((string) ($ao['courier_assigned_at'] ?: $ao['created_at']), ENT_QUOTES, 'UTF-8') ?>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>

        <div class="card">
          <h2>Available Orders — Pick to claim</h2>
          <?php if (empty($availableOrders)): ?>
            <p class="muted">No available orders to pick right now.</p>
          <?php else: ?>
            <div class="recent">
              <?php foreach ($availableOrders as $ao): ?>
                <div class="recent-item" data-order-id="<?= htmlspecialchars((string) $ao['id'], ENT_QUOTES, 'UTF-8') ?>" data-ship-name="<?= htmlspecialchars((string) ($ao['ship_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" data-ship-phone="<?= htmlspecialchars((string) ($ao['ship_phone'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" data-item-name="<?= htmlspecialchars((string) ($ao['item_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" data-total="<?= htmlspecialchars((string) ($ao['total'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" data-created-at="<?= htmlspecialchars((string) ($ao['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" style="display:flex;flex-direction:column;gap:8px">
                  <div style="display:flex;align-items:center;justify-content:space-between">
                    <div style="font-family:'Playfair Display',serif;font-size:1rem;font-weight:600">#<?= htmlspecialchars((string) $ao['id'], ENT_QUOTES, 'UTF-8') ?></div>
                    <form method="post" style="margin:0">
                                              <?php echo csrf_token_field(); ?>
                      <input type="hidden" name="action" value="pick_order">
                      <input type="hidden" name="pick_order_id" value="<?= htmlspecialchars((string) $ao['id'], ENT_QUOTES, 'UTF-8') ?>">
                      <button type="submit" class="btn" style="padding:8px 12px;font-size:.86rem">Pick</button>
                    </form>
                  </div>
                  <div style="font-size:.82rem;color:#8f707c">
                    <strong><?= htmlspecialchars((string) ($ao['ship_name'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></strong>
                    · <?= htmlspecialchars((string) ($ao['ship_phone'] ?? '—'), ENT_QUOTES, 'UTF-8') ?>
                    <div style="color:var(--muted);margin-top:6px"><?= htmlspecialchars((string) ($ao['item_name'] ?? '—'), ENT_QUOTES, 'UTF-8') ?> · ₱<?= htmlspecialchars((string) number_format((float) ($ao['total'] ?? 0), 2), ENT_QUOTES, 'UTF-8') ?></div>
                    <div style="color:var(--muted);font-size:.78rem;margin-top:6px">Created: <?= htmlspecialchars((string) ($ao['created_at'] ?? '')) ?></div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
  </div>

  <!-- Orders Modal -->
  <div id="ordersModal" class="orders-modal">
    <div class="orders-modal-card">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
        <h2 style="margin:0;font-size:1.3rem;font-family:'Playfair Display',serif">Your Orders to Deliver</h2>
        <button type="button" id="closeOrdersModal" style="background:none;border:none;font-size:1.6rem;cursor:pointer;color:#8f707c;padding:0;width:32px;height:32px;display:flex;align-items:center;justify-content:center">×</button>
      </div>
      <div id="ordersListContainer" style="display:grid;gap:10px">
        <!-- Orders will be populated here by JavaScript -->
      </div>
    </div>
  </div>

  <!-- Delivered Order Details Modal -->
  <div id="deliveredDetailModal" class="detail-modal" aria-hidden="true">
    <div class="detail-modal-card">
      <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:10px">
        <h2 style="margin:0;font-family:'Playfair Display',serif;font-size:1.35rem">Delivered Order Details</h2>
        <button type="button" id="closeDeliveredDetailModal" style="background:none;border:none;font-size:1.6rem;cursor:pointer;color:#8f707c;padding:0;width:32px;height:32px;display:flex;align-items:center;justify-content:center">×</button>
      </div>
      <div id="deliveredDetailBody" class="detail-kv"></div>
      <div id="deliveredDetailProof" class="detail-proof"></div>
    </div>
  </div>

  <script>
    const scrollKey = 'courier_portal_scroll_y';
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

    let assignedOrders = <?php echo json_encode($assignedOrders, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    let deliveredOrders = <?php echo json_encode($deliveredOrders, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    let counts = <?php echo json_encode($counts, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
    const orderIdInput = document.getElementById('orderIdInput');
    const trackingNumberInput = document.getElementById('trackingNumberInput');
    const openBtn = document.getElementById('openOrdersModal');
    const closeBtn = document.getElementById('closeOrdersModal');
    const modal = document.getElementById('ordersModal');
    const container = document.getElementById('ordersListContainer');
    const deliveredDetailModal = document.getElementById('deliveredDetailModal');
    const deliveredDetailBody = document.getElementById('deliveredDetailBody');
    const deliveredDetailProof = document.getElementById('deliveredDetailProof');
    const closeDeliveredDetailModal = document.getElementById('closeDeliveredDetailModal');
    const proofBaseUrl = <?php echo json_encode(defined('DELIVERY_PROOF_URL') ? DELIVERY_PROOF_URL : '../uploads/delivery_proofs/', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;

    function openDeliveredOrderDetails(order) {
      if (!deliveredDetailModal || !deliveredDetailBody || !deliveredDetailProof) {
        return;
      }
      deliveredDetailBody.innerHTML = `
        <div><strong>Order:</strong> #${escapeHtml(order.id || '')}</div>
        <div><strong>Customer:</strong> ${escapeHtml(order.ship_name || '—')}</div>
        <div><strong>Contact:</strong> ${escapeHtml(order.ship_phone || '—')}</div>
        <div><strong>City:</strong> ${escapeHtml(order.ship_city || '—')}</div>
        <div><strong>Tracking:</strong> ${escapeHtml(order.tracking_number || '—')}</div>
        <div><strong>Delivered At:</strong> ${escapeHtml(order.delivered_at || '—')}</div>
      `;

      if (order.proof_photo) {
        const imgSrc = String(proofBaseUrl || '') + encodeURIComponent(String(order.proof_photo));
        deliveredDetailProof.innerHTML = `<img src="${imgSrc}" alt="Delivery proof photo">`;
      } else {
        deliveredDetailProof.innerHTML = '<div class="detail-proof-empty">No proof photo available for this delivery.</div>';
      }

      deliveredDetailModal.style.display = 'flex';
      deliveredDetailModal.setAttribute('aria-hidden', 'false');
    }

    function populateOrdersList() {
      container.innerHTML = '';
      // Header with sticky tabs
      const stickyWrap = document.createElement('div');
      stickyWrap.className = 'orders-list-sticky';

      const header = document.createElement('div');
      header.className = 'orders-list-heading';
      header.textContent = 'Choose an order';
      stickyWrap.appendChild(header);

      // Tabs
      const tabs = document.createElement('div');
      tabs.className = 'orders-tabs';
      const toDeliverTab = document.createElement('button');
      toDeliverTab.type = 'button';
      toDeliverTab.className = 'modal-tab';
      toDeliverTab.textContent = `To Deliver (${assignedOrders.length})`;
      const deliveredTab = document.createElement('button');
      deliveredTab.type = 'button';
      deliveredTab.className = 'modal-tab';
      deliveredTab.textContent = `Delivered (${deliveredOrders.length})`;
      tabs.appendChild(toDeliverTab);
      tabs.appendChild(deliveredTab);
      stickyWrap.appendChild(tabs);

      const searchInput = document.createElement('input');
      searchInput.type = 'search';
      searchInput.className = 'orders-search';
      searchInput.placeholder = 'Search tracking number or customer name...';
      stickyWrap.appendChild(searchInput);

      container.appendChild(stickyWrap);

      // Content areas
      const toDeliverArea = document.createElement('div');
      const deliveredArea = document.createElement('div');
      container.appendChild(toDeliverArea);
      container.appendChild(deliveredArea);

      function showToDeliver() {
        toDeliverArea.style.display = 'block';
        deliveredArea.style.display = 'none';
        toDeliverTab.classList.add('active');
        deliveredTab.classList.remove('active');
      }
      function showDelivered() {
        toDeliverArea.style.display = 'none';
        deliveredArea.style.display = 'block';
        deliveredTab.classList.add('active');
        toDeliverTab.classList.remove('active');
      }
      toDeliverTab.addEventListener('click', showToDeliver);
      deliveredTab.addEventListener('click', showDelivered);

      // Populate To Deliver
      if (!assignedOrders || assignedOrders.length === 0) {
        const p = document.createElement('p'); p.style.color='#8f707c'; p.style.textAlign='center'; p.style.padding='8px'; p.textContent = 'No assigned orders yet.'; toDeliverArea.appendChild(p);
      } else {
        assignedOrders.forEach(order => {
          const orderBtn = document.createElement('button');
          orderBtn.type = 'button';
          orderBtn.className = 'order-list-item';
          const addressLine = [order.ship_street, order.ship_city, order.ship_province, order.ship_zip].filter(v => v).join(', ') || '—';
          orderBtn.innerHTML = `
            <div style="font-family:'Playfair Display',serif;font-size:1rem;font-weight:600;margin-bottom:6px">#${escapeHtml(order.id)}</div>
            <div style="font-size:.85rem;color:#8f707c;line-height:1.6">
              <strong>${escapeHtml(order.ship_name || '—')}</strong><br>
              ${escapeHtml(addressLine)}<br>
              ${escapeHtml(order.ship_phone || '—')}
              ${order.tracking_number ? `<br><strong>Tracking:</strong> ${escapeHtml(order.tracking_number)}` : ''}
            </div>
          `;
          orderBtn.onclick = (e) => {
            e.preventDefault();
            orderIdInput.value = order.id;
            trackingNumberInput.value = order.tracking_number || '';
            modal.style.display = 'none';
            orderIdInput.focus();
          };
          orderBtn.setAttribute('data-search', ((order.ship_name || '') + ' ' + (order.tracking_number || '') + ' ' + (order.id || '')).toLowerCase());
          toDeliverArea.appendChild(orderBtn);
        });
      }

      // Populate Delivered
      if (!deliveredOrders || deliveredOrders.length === 0) {
        const p = document.createElement('p'); p.style.color='#8f707c'; p.style.textAlign='center'; p.style.padding='8px'; p.textContent = 'No delivered orders yet.'; deliveredArea.appendChild(p);
      } else {
        deliveredOrders.forEach(order => {
          const b = document.createElement('div');
          b.className = 'order-list-item';
          b.style.cursor = 'default';
          b.innerHTML = `
            <div style="font-family:'Playfair Display',serif;font-size:1rem;font-weight:600;margin-bottom:6px">#${escapeHtml(order.id)}</div>
            <div style="font-size:.85rem;color:#8f707c;line-height:1.6">
              <strong>${escapeHtml(order.ship_name || '—')}</strong><br>
              ${escapeHtml(order.ship_city || '—')}<br>
              ${escapeHtml(order.ship_phone || '—')}<br>
              ${order.item_count ? ('Items: ' + escapeHtml(order.item_count)) : ''}
              ${order.tracking_number ? `<br><strong>Tracking:</strong> ${escapeHtml(order.tracking_number)}` : ''}
              ${order.delivered_at ? `<br><small>Delivered: ${escapeHtml(order.delivered_at)}</small>` : ''}
            </div>
            <button type="button" class="view-detail-btn">View Details</button>
          `;
          const detailBtn = b.querySelector('.view-detail-btn');
          if (detailBtn) {
            detailBtn.addEventListener('click', function (e) {
              e.preventDefault();
              e.stopPropagation();
              openDeliveredOrderDetails(order);
            });
          }
          b.setAttribute('data-search', ((order.ship_name || '') + ' ' + (order.tracking_number || '') + ' ' + (order.id || '')).toLowerCase());
          deliveredArea.appendChild(b);
        });
      }

      function clearSearchEmpty(area) {
        const old = area.querySelector('.search-empty');
        if (old) {
          old.remove();
        }
      }

      function addSearchEmpty(area) {
        const p = document.createElement('p');
        p.className = 'muted search-empty';
        p.style.textAlign = 'center';
        p.style.padding = '8px';
        p.textContent = 'No matching orders found.';
        area.appendChild(p);
      }

      function applyModalFilter() {
        const q = (searchInput.value || '').trim().toLowerCase();
        [toDeliverArea, deliveredArea].forEach(function (area) {
          clearSearchEmpty(area);
          const cards = area.querySelectorAll('.order-list-item');
          if (!cards.length) {
            return;
          }
          let visible = 0;
          cards.forEach(function (card) {
            const hay = (card.getAttribute('data-search') || card.textContent || '').toLowerCase();
            const isMatch = q === '' || hay.includes(q);
            card.style.display = isMatch ? '' : 'none';
            if (isMatch) {
              visible += 1;
            }
          });
          if (q !== '' && visible === 0) {
            addSearchEmpty(area);
          }
        });
      }

      searchInput.addEventListener('input', applyModalFilter);

      // Default to To Deliver tab
      showToDeliver();
      applyModalFilter();
    }

    openBtn.onclick = (e) => {
      e.preventDefault();
      populateOrdersList();
      modal.style.display = 'flex';
    };

    closeBtn.onclick = () => modal.style.display = 'none';

    if (closeDeliveredDetailModal) {
      closeDeliveredDetailModal.onclick = () => {
        deliveredDetailModal.style.display = 'none';
        deliveredDetailModal.setAttribute('aria-hidden', 'true');
      };
    }

    modal.onclick = (e) => {
      if (e.target === modal) modal.style.display = 'none';
    };

    if (deliveredDetailModal) {
      deliveredDetailModal.onclick = (e) => {
        if (e.target === deliveredDetailModal) {
          deliveredDetailModal.style.display = 'none';
          deliveredDetailModal.setAttribute('aria-hidden', 'true');
        }
      };
    }

    // Also support manual order ID entry with auto-lookup
    orderIdInput.addEventListener('change', () => {
      const orderId = orderIdInput.value.trim();
      if (orderId) {
        const order = assignedOrders.find(o => o.id === orderId);
        if (order) {
          trackingNumberInput.value = order.tracking_number || '';
        }
      }
    });

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

    // --- AJAX utilities and dynamic refresh ---
    function escapeHtml(str) {
      return String(str === undefined || str === null ? '' : str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
    }

    function showMessage(type, text) {
      const msg = document.createElement('div');
      msg.className = 'msg ' + (type === 'ok' ? 'ok' : 'err');
      msg.textContent = text;
      const grid = document.querySelector('.wrap');
      if (grid && grid.parentNode) {
        grid.insertBefore(msg, grid.firstChild);
        setTimeout(() => { try { msg.remove(); } catch (e) {} }, 4500);
      }
    }

    function renderAssignedOrders(list) {
      const cards = document.querySelectorAll('.grid .card');
      if (!cards || cards.length === 0) return;
      const assignedCard = cards[0];
      const container = assignedCard.querySelector('.recent');
      if (!container) return;
      container.innerHTML = '';
      if (!list || list.length === 0) {
        container.innerHTML = '<p class="muted">No assigned orders yet.</p>';
        return;
      }
      list.forEach(ao => {
        const el = document.createElement('div');
        el.className = 'recent-item';
        el.innerHTML = `
          <div class="recent-head">
            <div class="recent-id">#${escapeHtml(ao.id)}</div>
            <span class="pill">${escapeHtml(ao.status || 'shipped')}</span>
          </div>
          <div class="recent-meta">
            Customer: ${escapeHtml(ao.ship_name || '—')}<br>
            Contact: ${escapeHtml(ao.ship_phone || '—')}<br>
            City: ${escapeHtml(ao.ship_city || '—')}<br>
            Assigned: ${escapeHtml(ao.courier_assigned_at || ao.created_at || '')}
          </div>
        `;
        container.appendChild(el);
      });
    }

    function renderDeliveredOrders(list) {
      const cards = document.querySelectorAll('.grid .card');
      if (!cards || cards.length === 0) return;
      const assignedCard = cards[0];
      const deliveredContainer = assignedCard.querySelector('#deliveredList');
      if (!deliveredContainer) return;
      deliveredContainer.innerHTML = '';
      if (!list || list.length === 0) {
        deliveredContainer.innerHTML = '<p class="muted">No delivered orders yet.</p>';
        return;
      }
      list.forEach(ao => {
        const el = document.createElement('div');
        el.className = 'recent-item';
        el.innerHTML = `
          <div class="recent-head">
            <div class="recent-id">#${escapeHtml(ao.id)}</div>
            <span class="pill">delivered</span>
          </div>
          <div class="recent-meta">
            Customer: ${escapeHtml(ao.ship_name || '—')}<br>
            Contact: ${escapeHtml(ao.ship_phone || '—')}<br>
            City: ${escapeHtml(ao.ship_city || '—')}<br>
            Delivered: ${escapeHtml(ao.delivered_at || ao.created_at || '')}
          </div>
        `;
        deliveredContainer.appendChild(el);
      });
    }

    function renderAvailableOrders(list) {
      const cards = document.querySelectorAll('.grid .card');
      if (!cards || cards.length < 2) return;
      const availCard = cards[1];
      const container = availCard.querySelector('.recent');
      if (!container) return;
      container.innerHTML = '';
      if (!list || list.length === 0) {
        container.innerHTML = '<p class="muted">No available orders to pick right now.</p>';
        return;
      }
      list.forEach(ao => {
        const item = document.createElement('div');
        item.className = 'recent-item';
        item.setAttribute('data-order-id', ao.id);
        item.setAttribute('data-ship-name', ao.ship_name || '');
        item.setAttribute('data-ship-phone', ao.ship_phone || '');
        item.setAttribute('data-item-name', ao.item_name || '');
        item.setAttribute('data-total', ao.total || '');
        item.setAttribute('data-created-at', ao.created_at || '');
        item.style.display = 'flex';
        item.style.flexDirection = 'column';
        item.style.gap = '8px';
        item.innerHTML = `
          <div style="display:flex;align-items:center;justify-content:space-between">
            <div style="font-family:'Playfair Display',serif;font-size:1rem;font-weight:600">#${escapeHtml(ao.id)}</div>
            <form method="post" style="margin:0">
              <input type="hidden" name="action" value="pick_order">
              <input type="hidden" name="pick_order_id" value="${escapeHtml(ao.id)}">
              <button type="submit" class="btn" style="padding:8px 12px;font-size:.86rem">Pick</button>
            </form>
          </div>
          <div style="font-size:.82rem;color:#8f707c">
            <strong>${escapeHtml(ao.ship_name || '—')}</strong>
            · ${escapeHtml(ao.ship_phone || '—')}
            <div style="color:var(--muted);margin-top:6px">${escapeHtml(ao.item_name || '—')} · ₱${escapeHtml(Number(ao.total || 0).toFixed(2))}</div>
            <div style="color:var(--muted);font-size:.78rem;margin-top:6px">Created: ${escapeHtml(ao.created_at || '')}</div>
          </div>
        `;
        container.appendChild(item);
      });
      wirePickHandlers();
    }

    function wirePickHandlers() {
      document.querySelectorAll('form').forEach(function (f) {
        // only interested in pick forms
        const action = f.querySelector('input[name="action"]');
        if (!action || action.value !== 'pick_order') return;
        // avoid double-binding
        if (f.__hasAjaxPick) return;
        f.__hasAjaxPick = true;
        f.addEventListener('submit', async function (ev) {
          ev.preventDefault();
          const recentItem = f.closest('.recent-item');
          if (!recentItem) return;
          const payload = new FormData(f);
                    payload.append('csrf_token', document.querySelector('meta[name="csrf-token"]')?.content || '');
          payload.append('ajax', '1');
          const meta = {
            orderId: recentItem.dataset.orderId || '',
            shipName: recentItem.dataset.shipName || '',
            shipPhone: recentItem.dataset.shipPhone || '',
            itemName: recentItem.dataset.itemName || '',
            total: recentItem.dataset.total || '',
            createdAt: recentItem.dataset.createdAt || ''
          };
          try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
            const resp = await fetch(window.location.href, { method: 'POST', body: payload, headers: { 'X-Requested-With': 'XMLHttpRequest', ...(csrfToken ? { 'X-CSRF-Token': csrfToken } : {}) } });
            const data = await resp.json();
            if (data && data.ok) {
              try { recentItem.remove(); } catch (e) {}
              showMessage('ok', 'Picked order #' + String(data.orderId));
              // optimistic insert into assigned list
              const assignedList = (document.querySelectorAll('.grid .card')[0] || {}).querySelector('.recent');
              if (assignedList) {
                const el = document.createElement('div');
                el.className = 'recent-item';
                el.innerHTML = `
                  <div class="recent-head">
                    <div class="recent-id">#${escapeHtml(meta.orderId)}</div>
                    <span class="pill">shipped</span>
                  </div>
                  <div class="recent-meta">
                    Customer: ${escapeHtml(meta.shipName)}<br>
                    Contact: ${escapeHtml(meta.shipPhone)}<br>
                    ${meta.itemName ? escapeHtml(meta.itemName) + '<br>' : ''}
                    Assigned: ${escapeHtml(new Date().toLocaleString())}<br>
                    Tracking: ${escapeHtml(data.tracking || '')}
                  </div>
                `;
                assignedList.insertBefore(el, assignedList.firstChild);
                // keep client-side assignedOrders in sync for Quick Select modal
                try {
                  assignedOrders.unshift({ id: meta.orderId, ship_name: meta.shipName, ship_phone: meta.shipPhone, ship_city: '', courier_assigned_at: new Date().toISOString(), tracking_number: data.tracking || '', status: 'shipped' });
                } catch (e) {}
              }
            } else {
              showMessage('err', (data && data.message) ? data.message : 'Could not pick order.');
              // refresh authoritative state
              refreshLists();
            }
          } catch (err) {
            showMessage('err', 'Network error while picking order.');
          }
        });
      });
    }

    async function refreshLists() {
      try {
        const url = new URL(window.location.href);
        url.searchParams.set('ajax', '1');
        url.searchParams.set('action', 'refresh');
        const resp = await fetch(url.toString(), { cache: 'no-store' });
        if (!resp.ok) return;
        const data = await resp.json();
        assignedOrders = data.assignedOrders || [];
        deliveredOrders = data.deliveredOrders || [];
        counts = data.counts || counts;
        renderAssignedOrders(assignedOrders);
        renderAvailableOrders(data.availableOrders || []);
        renderDeliveredOrders(deliveredOrders);
        // update badge on main page
        try {
          const badge = document.getElementById('toDeliverBadge');
          if (badge && counts) {
            const ordersToDeliver = Number(counts.to_deliver_count || 0);
            badge.textContent = ordersToDeliver + ' ' + (ordersToDeliver === 1 ? 'order' : 'orders') + ' to deliver';
          }
        } catch (e) {}
      } catch (e) {
        // silent
      }
    }

    // Confirm Delivery form -> AJAX submit (handles file upload)
    (function attachConfirmAjax() {
      const confirmForm = document.getElementById('confirmDeliveryForm');
      if (!confirmForm) return;
      const confirmBtn = document.getElementById('confirmDeliveryBtn');
      const spinner = confirmBtn ? confirmBtn.querySelector('.spinner') : null;
      const btnText = confirmBtn ? confirmBtn.querySelector('.btn-text') : null;
      confirmForm.addEventListener('submit', async function (ev) {
        ev.preventDefault();
        const fd = new FormData(confirmForm);
        fd.append('ajax', '1');
        try {
          if (confirmBtn) { confirmBtn.disabled = true; confirmBtn.classList.add('loading'); if (spinner) spinner.style.display = 'inline-block'; if (btnText) btnText.textContent = 'Uploading...'; }
          const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
          const resp = await fetch(window.location.href, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest', ...(csrfToken ? { 'X-CSRF-Token': csrfToken } : {}) } });
          const data = await resp.json();
          if (data && data.ok) {
            showMessage('ok', data.message || 'Delivery confirmed.');
            try { confirmForm.reset(); } catch (e) {}
            try { trackingNumberInput.value = ''; } catch (e) {}
            refreshLists();
            try { showDeliveredModal('Order delivered'); } catch (e) {}
          } else {
            showMessage('err', (data && data.message) ? data.message : 'Could not confirm delivery.');
          }
        } catch (err) {
          showMessage('err', 'Network error while confirming delivery.');
        } finally {
          if (confirmBtn) { confirmBtn.disabled = false; confirmBtn.classList.remove('loading'); if (spinner) spinner.style.display = 'none'; if (btnText) btnText.textContent = 'Confirm Delivery'; }
        }
      });
    })();

    // Small delivered modal helper
    function showDeliveredModal(text) {
      let m = document.getElementById('deliveredModal');
      if (!m) {
        m = document.createElement('div');
        m.id = 'deliveredModal';
        document.body.appendChild(m);
      }
      m.textContent = text || 'Order delivered';
      m.classList.add('show');
      setTimeout(function () { try { m.classList.remove('show'); } catch (e) {} }, 2000);
    }

    // initial wiring + periodic poll
    wirePickHandlers();
    refreshLists();
    setInterval(refreshLists, 8000);
  </script>
</body>
</html>