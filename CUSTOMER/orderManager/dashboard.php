<?php
require_once __DIR__ . '/admin_auth.php';
require_once __DIR__ . '/db.php';
admin_require_page('dashboard');
$pdo = adminDb();

$startOfMonth = date('Y-m-01 00:00:00');
$endOfMonth   = date('Y-m-t 23:59:59');
$revStmt = $pdo->prepare('SELECT COALESCE(SUM(total), 0) AS total, COUNT(*) AS cnt FROM orders WHERE created_at BETWEEN ? AND ?');
$revStmt->execute([$startOfMonth, $endOfMonth]);
$revRow = $revStmt->fetch();

$refundMonthStmt = $pdo->prepare('SELECT COALESCE(SUM(refund_amount), 0) AS total FROM refund_logs WHERE created_at BETWEEN ? AND ?');
$refundMonthStmt->execute([$startOfMonth, $endOfMonth]);
$refundMonthTotal = (float) ($refundMonthStmt->fetchColumn() ?: 0);

$grossMonthRevenue = (float) ($revRow['total'] ?? 0);
$netMonthRevenue = $grossMonthRevenue - $refundMonthTotal;

$custTotal = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn();
$custNewStmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'customer' AND created_at BETWEEN ? AND ?");
$custNewStmt->execute([$startOfMonth, $endOfMonth]);
$custNew = (int) $custNewStmt->fetchColumn();

$u = $GLOBALS['ADMIN_USER'] ?? [];
$dispName = trim((string) (($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? '')));
$rk = $GLOBALS['ADMIN_ROLE'] ?? 'super_admin';
$roleLabel = $rk === 'manager' ? 'Order Manager' : ($rk === 'inventory' ? 'Inventory Manager' : 'Super Admin');
$restrictSuperDash = ($rk !== 'manager');
$deniedPageKey = trim((string) ($_GET['denied'] ?? ''));
$deniedPageLabelMap = [
  'customers' => 'Customer Accounts',
  'settings' => 'Settings and Staff',
  'audit_log' => 'Audit Log',
  'inventory' => 'Inventory',
  'products' => 'Products',
  'orders' => 'Orders',
  'tickets' => 'Tickets',
  'reviews' => 'Review Ratings',
  'promotions' => 'Promotions',
  'reports' => 'Reports',
  'notifications' => 'Notifications',
];
$deniedPageLabel = $deniedPageLabelMap[$deniedPageKey] ?? '';

$dashboardData = [
  'user' => ['name' => $dispName !== '' ? $dispName : 'Admin', 'role' => $roleLabel],
  'restrict_super' => $restrictSuperDash,
  'top_products_link' => $restrictSuperDash ? 'inventory.php' : 'products.php',
  'stats' => [
    'revenue'   => ['value' => $netMonthRevenue, 'change' => 0, 'gross' => $grossMonthRevenue, 'refunds' => $refundMonthTotal],
    'refunds'   => ['value' => $refundMonthTotal, 'change' => 0],
    'orders'    => ['value' => (int) $revRow['cnt'], 'change' => 0],
    'customers' => ['value' => $custTotal, 'new_this_month' => $custNew],
    'rating'    => ['value' => 5, 'change' => 0, 'previous' => 5],
  ],
  'badges' => [
    'pending_orders'   => (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'processing'")->fetchColumn(),
    'new_products'    => (int) $pdo->query("SELECT COUNT(*) FROM products WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn(),
    'low_stock'       => (int) $pdo->query("SELECT COUNT(*) FROM products WHERE stock > 0 AND stock <= 5")->fetchColumn(),
    'pending_reviews' => (int) $pdo->query("SELECT COUNT(*) FROM product_reviews WHERE status = 'pending'")->fetchColumn(),
  ],
  'revenue' => ['labels' => ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'], 'values' => array_fill(0, 12, 0)],
  'top_products' => [],
  'recent_orders' => [],
  'refund_logs' => [],
  'activity' => [],
];

$year = (int) date('Y');
$revByMonth = $pdo->prepare("SELECT MONTH(created_at) AS m, COALESCE(SUM(total), 0) AS total FROM orders WHERE YEAR(created_at) = ? GROUP BY MONTH(created_at)");
$revByMonth->execute([$year]);
$grossByMonth = array_fill(1, 12, 0.0);
while ($r = $revByMonth->fetch()) {
  $month = (int) ($r['m'] ?? 0);
  if ($month >= 1 && $month <= 12) {
    $grossByMonth[$month] = (float) ($r['total'] ?? 0);
  }
}

$refundByMonthStmt = $pdo->prepare("SELECT MONTH(created_at) AS m, COALESCE(SUM(refund_amount), 0) AS total FROM refund_logs WHERE YEAR(created_at) = ? GROUP BY MONTH(created_at)");
$refundByMonthStmt->execute([$year]);
$refundByMonth = array_fill(1, 12, 0.0);
while ($r = $refundByMonthStmt->fetch()) {
  $month = (int) ($r['m'] ?? 0);
  if ($month >= 1 && $month <= 12) {
    $refundByMonth[$month] = (float) ($r['total'] ?? 0);
  }
}

for ($m = 1; $m <= 12; $m++) {
  $dashboardData['revenue']['values'][$m - 1] = $grossByMonth[$m] - $refundByMonth[$m];
}

$refundLogsStmt = $pdo->query("SELECT id, ticket_id, customer_name, order_id, refund_amount, ticket_category, approved_by_name, created_at
  FROM refund_logs
  ORDER BY created_at DESC
  LIMIT 8");
while ($r = $refundLogsStmt->fetch()) {
  $dashboardData['refund_logs'][] = [
    'id' => (int) ($r['id'] ?? 0),
    'ticket_id' => (int) ($r['ticket_id'] ?? 0),
    'customer_name' => (string) ($r['customer_name'] ?? ''),
    'order_id' => (string) ($r['order_id'] ?? ''),
    'refund_amount' => (float) ($r['refund_amount'] ?? 0),
    'ticket_category' => (string) ($r['ticket_category'] ?? ''),
    'approved_by_name' => (string) ($r['approved_by_name'] ?? ''),
    'created_at' => (string) ($r['created_at'] ?? ''),
  ];
}

$topStmt = $pdo->query(" 
  SELECT p.id AS product_id, p.name, p.image, SUM(oi.qty) AS sold, SUM(oi.qty * oi.price) AS revenue, MAX(oi.cat) AS category
  FROM order_items oi
  INNER JOIN products p ON p.id = oi.product_id
  WHERE LOWER(TRIM(oi.name)) NOT IN ('sss', 'bogart batumbakal', 'sample')
  GROUP BY p.id, p.name, p.image
  ORDER BY revenue DESC
  LIMIT 5
");
while ($r = $topStmt->fetch()) {
  $img = trim((string) ($r['image'] ?? ''));
  $dashboardData['top_products'][] = [
    'name' => $r['name'],
    'category' => $r['category'],
    'sold' => (int) $r['sold'],
    'revenue' => (float) $r['revenue'],
    'image_url' => $img !== '' ? '../uploads/products/' . $img : null,
  ];
}

if (!$restrictSuperDash) {
  $recentStmt = $pdo->query("
    SELECT o.id, o.ship_name AS customer_name,
      (SELECT oi.name FROM order_items oi WHERE oi.order_id = o.id LIMIT 1) AS item_name,
      o.total, o.status
    FROM orders o
    ORDER BY o.created_at DESC
    LIMIT 5
  ");
  while ($r = $recentStmt->fetch()) {
    $dashboardData['recent_orders'][] = [
      'id' => $r['id'], 'customer_name' => $r['customer_name'], 'item_name' => $r['item_name'],
      'total' => (float)$r['total'], 'status' => $r['status'],
    ];
  }

  $actStmt = $pdo->query("
    SELECT CONCAT('Order #', o.id, ' placed by ', COALESCE(o.ship_name, 'Customer')) AS message, o.created_at
    FROM orders o
    ORDER BY o.created_at DESC
    LIMIT 10
  ");
  while ($r = $actStmt->fetch()) {
    $dashboardData['activity'][] = ['message' => $r['message'], 'created_at' => $r['created_at']];
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Bejewelry — Order Manager Dashboard</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,500;0,600;0,700;1,400&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600;9..40,700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../admin/dashboard.css">
<script>
  window.__SCROLL_RESTORE_BOOTSTRAP__ = (function () {
    var key = 'scroll_restore::' + location.pathname;
    try { history.scrollRestoration = 'manual'; } catch (e) {}
    try {
      if (sessionStorage.getItem(key) !== null) {
        document.documentElement.style.opacity = '0';
      }
    } catch (e) {}
    return key;
  })();
</script>
</head>
<body>

<div class="loading-bar" id="loadingBar"></div>

<div class="site-wrapper">

  <?php $GLOBALS['NAV_ACTIVE'] = 'dashboard'; require __DIR__ . '/includes/nav_sidebar.php'; ?>

  <!-- ── MAIN CONTENT ── -->
  <div class="site-content">

    <!-- Topbar -->
    <header class="topbar">
      <div class="topbar-left">
        <span class="topbar-title">Dashboard</span>
        <span class="topbar-bc">Bejewelry Admin › Dashboard</span>
      </div>
      <div class="topbar-right">
        <button class="icon-btn" title="Notifications" onclick="handleNotifications()">
          🔔<span class="dot" id="notifDot" style="display:none"></span>
        </button>
        <button class="icon-btn" title="Refresh" onclick="loadDashboardData()">↺</button>
      </div>
    </header>

    <!-- Content -->
    <div class="content">

      <!-- Page Header -->
      <div class="page-hdr">
        <div>
          <h2>Dashboard</h2>
          <p id="lastUpdated">Loading data…</p>
        </div>
        <?php if (!$restrictSuperDash): ?>
        <div class="page-hdr-actions">
          <button class="btn btn-ghost btn-sm" onclick="handleExport()">⬇ Export</button>
          <button class="btn btn-primary btn-sm" onclick="handleNewOrder()">＋ New Order</button>
        </div>
        <?php endif; ?>
      </div>

      <?php if ($deniedPageLabel !== ''): ?>
      <div class="alert" style="margin-bottom:12px;background:#fff0f2;border-color:#f2b6c2;color:#9c2f45;">
        <span class="alert-icon">⛔</span>
        <span>
          Access denied to <?= htmlspecialchars($deniedPageLabel, ENT_QUOTES, 'UTF-8') ?> for your current role.
        </span>
      </div>
      <?php endif; ?>

      <!-- Low stock alert (shown dynamically) -->
      <div class="alert" id="lowStockAlert" style="display:none">
        <span class="alert-icon">⚠</span>
        <span>
          Low stock alert: <strong id="lowStockCount">—</strong> product(s) are running low.
          <a href="inventory.php"> Manage Inventory →</a>
        </span>
        <button class="alert-close" onclick="document.getElementById('lowStockAlert').style.display='none'">✕</button>
      </div>

      <!-- Stats Row -->
      <div class="stats-row">
        <div class="stat-card">
          <span class="stat-icon">💰</span>
          <span class="stat-label">Revenue this month</span>
          <div class="stat-value skel skel-val" id="valRevenue"> </div>
          <div class="stat-trend skel skel-text" id="trendRevenue"> </div>
        </div>
        <div class="stat-card">
          <span class="stat-icon">↩</span>
          <span class="stat-label">Refunds this month</span>
          <div class="stat-value skel skel-val" id="valRefunds"> </div>
          <div class="stat-trend skel skel-text" id="trendRefunds"> </div>
        </div>
        <?php if (!$restrictSuperDash): ?>
        <div class="stat-card">
          <span class="stat-icon">📦</span>
          <span class="stat-label">Orders this month</span>
          <div class="stat-value skel skel-val" id="valOrders"> </div>
          <div class="stat-trend skel skel-text" id="trendOrders"> </div>
        </div>
        <?php endif; ?>
        <div class="stat-card">
          <span class="stat-icon">👤</span>
          <span class="stat-label">Total customers</span>
          <div class="stat-value skel skel-val" id="valCustomers"> </div>
          <div class="stat-trend skel skel-text" id="trendCustomers"> </div>
        </div>
        <div class="stat-card">
          <span class="stat-icon">⭐</span>
          <span class="stat-label">Avg. Rating</span>
          <div class="stat-value skel skel-val" id="valRating"> </div>
          <div class="stat-trend skel skel-text" id="trendRating"> </div>
        </div>
      </div>

      <!-- Charts + Top Products -->
      <div class="two-col">
        <div class="card">
          <div class="card-hd">
            <h3>Revenue Overview</h3>
            <select id="chartPeriod" onchange="loadChart()">
              <option value="year">This Year</option>
              <option value="last_year">Last Year</option>
            </select>
          </div>
          <div class="chart-wrap">
            <div class="chart-area" id="revenueChart">
              <div class="empty-state" style="width:100%">
                <span class="empty-icon">📊</span>
                Loading chart…
              </div>
            </div>
          </div>
        </div>

        <div class="card">
          <div class="card-hd">
            <h3>Top Products</h3>
          </div>
          <div class="prod-list" id="topProductsList">
            <div class="prod-row">
              <div class="prod-thumb skel"></div>
              <div class="prod-info">
                <div class="skel skel-text"></div>
                <div class="skel skel-text" style="width:60%"></div>
              </div>
            </div>
            <div class="prod-row">
              <div class="prod-thumb skel"></div>
              <div class="prod-info">
                <div class="skel skel-text"></div>
                <div class="skel skel-text" style="width:60%"></div>
              </div>
            </div>
            <div class="prod-row">
              <div class="prod-thumb skel"></div>
              <div class="prod-info">
                <div class="skel skel-text"></div>
                <div class="skel skel-text" style="width:60%"></div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="card">
        <div class="card-hd">
          <h3>Recent Refund Logs</h3>
          <span class="card-hd-link">Latest approved refund requests</span>
        </div>
        <div style="overflow-x:auto">
          <table class="tbl">
            <thead>
              <tr>
                <th>Log</th>
                <th>Ticket</th>
                <th>Order</th>
                <th>Customer</th>
                <th>Amount</th>
                <th>Approved by</th>
                <th>Date</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($dashboardData['refund_logs'])): ?>
              <tr>
                <td colspan="7">No refund logs yet.</td>
              </tr>
              <?php else: ?>
              <?php foreach ($dashboardData['refund_logs'] as $log): ?>
              <tr>
                <td>#<?= (int) ($log['id'] ?? 0) ?></td>
                <td>#<?= (int) ($log['ticket_id'] ?? 0) ?></td>
                <td><?= htmlspecialchars((string) ($log['order_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars((string) ($log['customer_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                <td>₱<?= number_format((float) ($log['refund_amount'] ?? 0), 2) ?></td>
                <td><?= htmlspecialchars((string) ($log['approved_by_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars((string) ($log['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
              </tr>
              <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <?php if (!$restrictSuperDash): ?>
      <!-- Recent Orders + Activity Feed -->
      <div class="two-col">
        <div class="card">
          <div class="card-hd">
            <h3>Recent Orders</h3>
            <a href="orders.php" class="card-hd-link">View all →</a>
          </div>
          <div style="overflow-x:auto">
            <table class="tbl">
              <thead>
                <tr>
                  <th>#Order</th>
                  <th>Customer</th>
                  <th>Item</th>
                  <th>Total</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody id="recentOrdersBody">
                <tr><td colspan="5"><div class="skel skel-text" style="margin:10px 16px"></div></td></tr>
                <tr><td colspan="5"><div class="skel skel-text" style="margin:10px 16px"></div></td></tr>
                <tr><td colspan="5"><div class="skel skel-text" style="margin:10px 16px"></div></td></tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="card">
          <div class="card-hd">
            <h3>Activity Feed</h3>
          </div>
          <div class="feed-body" id="activityFeed">
            <div class="feed-item">
              <div class="feed-dot"></div>
              <div style="flex:1"><div class="skel skel-text"></div><div class="skel skel-text" style="width:40%"></div></div>
            </div>
            <div class="feed-item">
              <div class="feed-dot"></div>
              <div style="flex:1"><div class="skel skel-text"></div><div class="skel skel-text" style="width:40%"></div></div>
            </div>
            <div class="feed-item">
              <div class="feed-dot"></div>
              <div style="flex:1"><div class="skel skel-text"></div><div class="skel skel-text" style="width:40%"></div></div>
            </div>
          </div>
        </div>
      </div>
      <?php endif; ?>

    </div><!-- /content -->
  </div><!-- /site-content -->
</div><!-- /site-wrapper -->

<div class="toast" id="toast"></div>

<div id="productImageModal" class="img-modal" aria-hidden="true">
  <div class="img-modal-panel">
    <div class="img-modal-hd">
      <strong id="productImageModalTitle">Product image</strong>
      <button type="button" class="img-modal-close" onclick="closeProductImageModal()" aria-label="Close image preview">×</button>
    </div>
    <div class="img-modal-body">
      <img id="productImageModalImg" alt="Product image preview">
    </div>
  </div>
</div>

<script>window.__DASHBOARD__ = <?= json_encode($dashboardData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;</script>
<script src="whoami.js?v=1"></script>
<script src="../admin/notif_dot.js?v=1"></script>
<script src="../admin/confirm_modal.js?v=1"></script>
<script src="../admin/dashboard.js?v=20260416-3"></script>
<script>
  (function () {
    var key = window.__SCROLL_RESTORE_BOOTSTRAP__ || ('scroll_restore::' + location.pathname);

    function saveScrollPosition() {
      try {
        sessionStorage.setItem(key, String(window.scrollY || 0));
      } catch (e) {}
    }

    function restoreScrollPositionOnce() {
      var raw = null;
      try {
        raw = sessionStorage.getItem(key);
      } catch (e) {}
      if (raw === null) {
        document.documentElement.style.opacity = '1';
        return;
      }

      var savedY = Number(raw);
      try {
        sessionStorage.removeItem(key);
      } catch (e) {}

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

    window.addEventListener('beforeunload', saveScrollPosition);
    window.addEventListener('pagehide', saveScrollPosition);
    window.addEventListener('load', restoreScrollPositionOnce);
  })();
</script>
</body>
</html>
