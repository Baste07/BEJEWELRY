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
    'revenue'   => ['value' => (float) $revRow['total'], 'change' => 0],
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
  'activity' => [],
];

$year = (int) date('Y');
$revByMonth = $pdo->prepare("SELECT MONTH(created_at) AS m, COALESCE(SUM(total), 0) AS total FROM orders WHERE YEAR(created_at) = ? GROUP BY MONTH(created_at)");
$revByMonth->execute([$year]);
while ($r = $revByMonth->fetch()) {
  $dashboardData['revenue']['values'][(int)$r['m'] - 1] = (float) $r['total'];
}

$topStmt = $pdo->query("
  SELECT p.id AS product_id, p.name, p.image, SUM(oi.qty) AS sold, SUM(oi.qty * oi.price) AS revenue, MAX(oi.cat) AS category
  FROM order_items oi
  INNER JOIN products p ON p.id = oi.product_id
  GROUP BY p.id, p.name, p.image
  ORDER BY revenue DESC
  LIMIT 5
");
while ($r = $topStmt->fetch()) {
  $img = trim((string)($r['image'] ?? ''));
  $dashboardData['top_products'][] = [
    'name' => $r['name'],
    'category' => $r['category'],
    'sold' => (int)$r['sold'],
    'revenue' => (float)$r['revenue'],
    'image_url' => $img !== '' ? '../uploads/products/' . $img : null,
  ];
}

// Inventory-focused data (products, units, most-bought, recent restocks)
$productsCount = (int) $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$totalUnits = (int) $pdo->query("SELECT COALESCE(SUM(stock), 0) FROM products")->fetchColumn();
$lowStockCount = (int) $pdo->query("SELECT COUNT(*) FROM products WHERE stock > 0 AND stock <= 5")->fetchColumn();

$mostBought = [];
$mbStmt = $pdo->query("SELECT p.id AS product_id, p.name, p.image, SUM(oi.qty) AS sold
  FROM order_items oi
  INNER JOIN products p ON p.id = oi.product_id
  GROUP BY p.id, p.name, p.image
  ORDER BY sold DESC
  LIMIT 5");
while ($m = $mbStmt->fetch()) {
  $img = trim((string)($m['image'] ?? ''));
  $mostBought[] = [
    'product_id' => (int)$m['product_id'],
    'name' => $m['name'],
    'sold' => (int)$m['sold'],
    'image_url' => $img !== '' ? '../uploads/products/' . $img : null,
  ];
}

$recentRestocks = [];
// only show positive qty_added as restock records
$rsStmt = $pdo->prepare("SELECT sh.id, sh.product_id, sh.qty_added, sh.stock_after, sh.note, sh.updated_by, sh.created_at, p.name FROM stock_history sh JOIN products p ON p.id = sh.product_id WHERE sh.qty_added > 0 ORDER BY sh.created_at DESC LIMIT 6");
$rsStmt->execute();
while ($s = $rsStmt->fetch(PDO::FETCH_ASSOC)) {
  $recentRestocks[] = $s;
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
  <title>Bejewelry — Admin Dashboard</title>
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

      <!-- Inventory Stats -->
      <div class="stats-row">
        <div class="stat-card">
          <span class="stat-icon">📦</span>
          <span class="stat-label">Total Products</span>
          <div class="stat-value" id="valProducts"><?php echo htmlspecialchars((string)$productsCount, ENT_QUOTES, 'UTF-8'); ?></div>
          <div class="stat-trend">Across all categories</div>
        </div>
        <div class="stat-card">
          <span class="stat-icon">🔢</span>
          <span class="stat-label">Total Units</span>
          <div class="stat-value" id="valUnits"><?php echo htmlspecialchars((string)$totalUnits, ENT_QUOTES, 'UTF-8'); ?></div>
          <div class="stat-trend">Units on hand</div>
        </div>
        <div class="stat-card warn-card">
          <span class="stat-icon">⚠</span>
          <span class="stat-label">Low Stock</span>
          <div class="stat-value" id="valLow"><?php echo htmlspecialchars((string)$lowStockCount, ENT_QUOTES, 'UTF-8'); ?></div>
          <div class="stat-trend">Below 5 units</div>
        </div>
        <div class="stat-card">
          <span class="stat-icon">🕘</span>
          <span class="stat-label">Recent Restocks</span>
          <div class="stat-value" id="valRestocks"><?php echo htmlspecialchars((string)count($recentRestocks), ENT_QUOTES, 'UTF-8'); ?></div>
          <div class="stat-trend">Last <?php echo count($recentRestocks) > 0 ? count($recentRestocks) : 0; ?> records</div>
        </div>
      </div>

      <!-- Stock Overview + Top Products -->
      <div class="two-col">
        <div class="card">
          <div class="card-hd"><h3>Low Stock Items</h3></div>
          <div class="card-body">
            <div style="max-height:220px;overflow:auto">
            <?php
              $lowStmt = $pdo->prepare("SELECT id, name, stock FROM products WHERE stock > 0 AND stock <= 5 ORDER BY stock ASC LIMIT 10");
              $lowStmt->execute();
              $lowRows = $lowStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
              if (count($lowRows) === 0) {
                echo '<div class="empty-state"><span class="empty-icon">✅</span>All products above low threshold</div>';
              } else {
                echo '<ul class="simple-list">';
                foreach ($lowRows as $lr) {
                  echo '<li>' . htmlspecialchars($lr['name'], ENT_QUOTES, 'UTF-8') . ' — <strong>' . (int)$lr['stock'] . ' units</strong></li>';
                }
                echo '</ul>';
              }
            ?>
            </div>
          </div>
        </div>

        <div class="card">
          <div class="card-hd"><h3>Most Bought Products</h3><a href="products.php" class="card-hd-link">View all →</a></div>
          <div class="prod-list" id="topProductsList">
            <?php if (count($mostBought) === 0): ?>
              <div class="prod-row"><div class="prod-thumb skel"></div><div class="prod-info"><div class="skel skel-text"></div></div></div>
            <?php else: ?>
              <?php foreach ($mostBought as $mb): ?>
                <div class="prod-row">
                  <div class="prod-thumb">
                    <?php if (!empty($mb['image_url'])): ?>
                      <img src="<?php echo htmlspecialchars($mb['image_url'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($mb['name'], ENT_QUOTES, 'UTF-8'); ?>">
                    <?php else: ?>
                      💎
                    <?php endif; ?>
                  </div>
                  <div class="prod-info">
                    <div class="prod-name"><?php echo htmlspecialchars($mb['name'], ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="prod-meta"><?php echo (int)$mb['sold']; ?> sold</div>
                  </div>
                  <div style="margin-left:auto;padding-left:12px;font-weight:700">&nbsp;</div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Recent restocks and recent stock transactions -->
      <div class="two-col">
        <div class="card">
          <div class="card-hd"><h3>Recent Restocks</h3><a href="inventory.php" class="card-hd-link">View transactions →</a></div>
          <div class="card-body">
            <?php if (count($recentRestocks) === 0): ?>
              <div class="empty-state"><span class="empty-icon">📦</span>No restock records found</div>
            <?php else: ?>
              <table class="tbl" style="width:100%">
                <thead><tr><th>Date</th><th>Product</th><th>Qty</th><th>Stock After</th><th>By</th></tr></thead>
                <tbody>
                <?php foreach ($recentRestocks as $rs): ?>
                  <tr>
                    <td style="white-space:nowrap"><?php echo htmlspecialchars($rs['created_at'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($rs['name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo (int)$rs['qty_added']; ?></td>
                    <td><?php echo (int)$rs['stock_after']; ?></td>
                    <td><?php echo htmlspecialchars($rs['updated_by'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                  </tr>
                <?php endforeach; ?>
                </tbody>
              </table>
            <?php endif; ?>
          </div>
        </div>

        <div class="card">
          <div class="card-hd"><h3>Recent Stock Activity</h3></div>
          <div class="card-body">
            <?php
              $actStmt = $pdo->query("SELECT sh.created_at, p.name, sh.qty_added, sh.stock_after, sh.updated_by FROM stock_history sh JOIN products p ON p.id = sh.product_id ORDER BY sh.created_at DESC LIMIT 8");
              $acts = $actStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
              if (count($acts) === 0) {
                echo '<div class="empty-state"><span class="empty-icon">📊</span>No recent stock activity</div>';
              } else {
                echo '<table class="tbl" style="width:100%"><thead><tr><th>Date</th><th>Product</th><th>Change</th><th>Stock After</th><th>By</th></tr></thead><tbody>';
                foreach ($acts as $a) {
                  $qty = (int)$a['qty_added'];
                  $sign = $qty > 0 ? '+' : '';
                  $cls = $qty > 0 ? 'qty-positive' : 'qty-negative';
                  echo '<tr>' .
                       '<td style="white-space:nowrap">' . htmlspecialchars($a['created_at'], ENT_QUOTES, 'UTF-8') . '</td>' .
                       '<td>' . htmlspecialchars($a['name'], ENT_QUOTES, 'UTF-8') . '</td>' .
                       '<td class="' . $cls . '" style="font-weight:600">' . $sign . $qty . '</td>' .
                       '<td>' . (int)$a['stock_after'] . '</td>' .
                       '<td style="color:var(--muted);font-size:.9rem">' . htmlspecialchars($a['updated_by'], ENT_QUOTES, 'UTF-8') . '</td>' .
                       '</tr>';
                }
                echo '</tbody></table>';
              }
            ?>
          </div>
        </div>
      </div>

    </div><!-- /content -->
  </div><!-- /site-content -->
</div><!-- /site-wrapper -->

<div class="toast" id="toast"></div>

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