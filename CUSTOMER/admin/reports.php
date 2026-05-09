<?php
require_once __DIR__ . '/admin_auth.php';
require_once __DIR__ . '/db.php';
admin_require_page('reports');
$pdo = adminDb();

$period = isset($_GET['period']) ? $_GET['period'] : 'this_month';
$periodLabel = 'This month';
$start = date('Y-m-01 00:00:00');
$end = date('Y-m-t 23:59:59');
if ($period === 'last_month') {
  $start = date('Y-m-01 00:00:00', strtotime('first day of last month'));
  $end = date('Y-m-t 23:59:59', strtotime('last day of last month'));
  $periodLabel = date('F Y', strtotime('first day of last month'));
} elseif ($period === 'this_year') {
  $start = date('Y-01-01 00:00:00');
  $end = date('Y-12-31 23:59:59');
  $periodLabel = 'This year';
}

$revStmt = $pdo->prepare("SELECT COALESCE(SUM(total), 0) AS revenue, COUNT(*) AS orders FROM orders WHERE created_at BETWEEN ? AND ?");
$revStmt->execute([$start, $end]);
$revRow = $revStmt->fetch();
$revenue = (float) $revRow['revenue'];
$orderCount = (int) $revRow['orders'];
$aov = $orderCount > 0 ? $revenue / $orderCount : 0;

$newCustStmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'customer' AND created_at BETWEEN ? AND ?");
$newCustStmt->execute([$start, $end]);
$newCustomers = (int) $newCustStmt->fetchColumn();

$catStmt = $pdo->prepare("
  SELECT oi.cat AS name, COALESCE(SUM(oi.qty * oi.price), 0) AS revenue, COUNT(DISTINCT o.id) AS orders
  FROM order_items oi
  JOIN orders o ON o.id = oi.order_id AND o.created_at BETWEEN ? AND ?
  GROUP BY oi.cat
  ORDER BY revenue DESC
");
$catStmt->execute([$start, $end]);
$categories = $catStmt->fetchAll(PDO::FETCH_ASSOC);
$totalRev = $revenue;
foreach ($categories as &$c) {
  $c['revenue'] = (float) $c['revenue'];
  $c['orders'] = (int) $c['orders'];
  $c['share'] = $totalRev > 0 ? round(($c['revenue'] / $totalRev) * 100) : 0;
}
unset($c);

$payStmt = $pdo->prepare("SELECT payment_method AS name, COALESCE(SUM(total), 0) AS amount FROM orders WHERE created_at BETWEEN ? AND ? GROUP BY payment_method ORDER BY amount DESC");
$payStmt->execute([$start, $end]);
$paymentMethods = $payStmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($paymentMethods as &$m) {
  $m['amount'] = (float) $m['amount'];
  $m['pct'] = $totalRev > 0 ? round(($m['amount'] / $totalRev) * 100) : 0;
  $m['key'] = strtolower(preg_replace('/\s+/', '_', $m['name']));
}
unset($m);

$weeks = [];
if ($period === 'this_month' || $period === 'last_month') {
  $wkStmt = $pdo->prepare("SELECT YEARWEEK(created_at) AS w, COALESCE(SUM(total), 0) AS value FROM orders WHERE created_at BETWEEN ? AND ? GROUP BY YEARWEEK(created_at) ORDER BY w");
  $wkStmt->execute([$start, $end]);
  while ($r = $wkStmt->fetch()) {
    $weeks[] = ['label' => 'Wk ' . substr($r['w'], -2), 'value' => (float) $r['value']];
  }
} else {
  for ($m = 1; $m <= 12; $m++) {
    $mStart = date('Y-' . str_pad($m, 2, '0', STR_PAD_LEFT) . '-01 00:00:00');
    $mEnd = date('Y-m-t 23:59:59', strtotime($mStart));
    $ms = $pdo->prepare("SELECT COALESCE(SUM(total), 0) FROM orders WHERE created_at BETWEEN ? AND ?");
    $ms->execute([$mStart, $mEnd]);
    $weeks[] = ['label' => date('M', strtotime($mStart)), 'value' => (float) $ms->fetchColumn()];
  }
}

$insights = [
  ['label' => 'New customers', 'value' => $newCustomers, 'direction' => 'up'],
  ['label' => 'Orders', 'value' => $orderCount, 'direction' => $orderCount > 0 ? 'up' : 'neutral'],
  ['label' => 'Avg. order value', 'value' => '₱' . number_format($aov, 0), 'direction' => 'neutral'],
  ['label' => 'Revenue', 'value' => '₱' . number_format($revenue, 0), 'direction' => $revenue > 0 ? 'up' : 'neutral'],
];

$reportsData = [
  'user' => ['name' => 'Admin', 'role' => 'admin'],
  'badges' => [
    'pending_orders' => (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'processing'")->fetchColumn(),
    'new_products' => (int) $pdo->query("SELECT COUNT(*) FROM products WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn(),
    'low_stock' => (int) $pdo->query("SELECT COUNT(*) FROM products WHERE stock > 0 AND stock <= 5")->fetchColumn(),
    'pending_reviews' => (int) $pdo->query("SELECT COUNT(*) FROM product_reviews WHERE status = 'pending'")->fetchColumn(),
  ],
  'summary' => [
    'revenue' => $revenue,
    'revenue_change' => 0,
    'orders' => $orderCount,
    'orders_change' => 0,
    'avg_order_value' => $aov,
    'aov_change' => 0,
    'new_customers' => $newCustomers,
    'new_customers_change' => 0,
    'period_label' => $periodLabel,
  ],
  'categories' => $categories,
  'payment_methods' => $paymentMethods,
  'weeks' => $weeks,
  'insights' => $insights,
  'period' => $period,
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Bejewelry Admin — Reports</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,500;0,600;0,700;1,400&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600;9..40,700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="reports.css">
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

  <?php $GLOBALS['NAV_ACTIVE'] = 'reports'; require __DIR__ . '/includes/nav_sidebar.php'; ?>

  <!-- ── MAIN CONTENT ── -->
  <div class="site-content">

    <header class="topbar">
      <div class="topbar-left">
        <span class="topbar-title">Reports</span>
        <span class="topbar-bc">Bejewelry Admin › Reports</span>
      </div>
      <div class="topbar-right">
        <div class="topbar-search">
          <span style="color:var(--muted-light);font-size:.9rem">⌕</span>
          <input type="text" id="globalSearch" placeholder="Search…"/>
        </div>
        <button class="icon-btn" title="Notifications" onclick="handleNotifications()">🔔<span class="dot" id="notifDot"></span></button>
        <button class="icon-btn" title="Refresh" onclick="loadPageData()">↺</button>
      </div>
    </header>

    <div class="content">

      <!-- PAGE HEADER -->
      <div class="page-hdr">
        <div>
          <h2>Reports</h2>
          <p id="reportsSubtitle">Loading…</p>
        </div>
        <div class="page-hdr-actions">
          <select class="period-select" id="periodSelect" onchange="location.href='reports.php?period='+this.value">
            <option value="this_month" <?= $period === 'this_month' ? 'selected' : '' ?>>This Month</option>
            <option value="last_month" <?= $period === 'last_month' ? 'selected' : '' ?>>Last Month</option>
            <option value="this_year" <?= $period === 'this_year' ? 'selected' : '' ?>>This Year</option>
          </select>
          <button class="btn btn-ghost btn-sm" onclick="handleExport()">⬇ Export PDF</button>
        </div>
      </div>

      <!-- STAT CARDS -->
      <div class="stats-row">
        <div class="stat-card">
          <span class="stat-icon">₱</span>
          <span class="stat-label">Revenue</span>
          <div class="stat-value skel skel-val" id="valRevenue">&nbsp;</div>
          <div class="stat-trend skel skel-text" id="trendRevenue">&nbsp;</div>
        </div>
        <div class="stat-card">
          <span class="stat-icon">📦</span>
          <span class="stat-label">Orders</span>
          <div class="stat-value skel skel-val" id="valOrders">&nbsp;</div>
          <div class="stat-trend skel skel-text" id="trendOrders">&nbsp;</div>
        </div>
        <div class="stat-card">
          <span class="stat-icon">÷</span>
          <span class="stat-label">Avg. Order Value</span>
          <div class="stat-value skel skel-val" id="valAov">&nbsp;</div>
          <div class="stat-trend skel skel-text" id="trendAov">&nbsp;</div>
        </div>
        <div class="stat-card">
          <span class="stat-icon">✨</span>
          <span class="stat-label">New Customers</span>
          <div class="stat-value skel skel-val" id="valNewCust">&nbsp;</div>
          <div class="stat-trend skel skel-text" id="trendNewCust">&nbsp;</div>
        </div>
      </div>

      <!-- ROW 1: Category Table + Weekly Chart -->
      <div class="two-col">

        <div class="card">
          <div class="card-hdr">
            <span class="card-title">Sales by Category</span>
            <span class="card-sub" id="categoryPeriodLabel">—</span>
          </div>
          <div class="card-body" style="padding:0">
            <table class="tbl">
              <thead>
                <tr>
                  <th>Category</th>
                  <th>Revenue</th>
                  <th>Orders</th>
                  <th>Share</th>
                </tr>
              </thead>
              <tbody id="categoryBody">
                <tr><td colspan="4" style="padding:var(--s5)"><div class="skel skel-row"></div></td></tr>
                <tr><td colspan="4" style="padding:var(--s5)"><div class="skel skel-row"></div></td></tr>
                <tr><td colspan="4" style="padding:var(--s5)"><div class="skel skel-row"></div></td></tr>
                <tr><td colspan="4" style="padding:var(--s5)"><div class="skel skel-row"></div></td></tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="card">
          <div class="card-hdr">
            <span class="card-title">Weekly Revenue</span>
            <span class="card-sub" id="chartPeriodLabel">—</span>
          </div>
          <div class="card-body">
            <div class="chart-wrap" id="weeklyChart">
              <!-- Skeleton bars -->
              <div class="bar-col"><div class="bar-rect skel" style="height:55%"></div><span class="bar-lbl skel" style="width:20px;height:8px"></span></div>
              <div class="bar-col"><div class="bar-rect skel" style="height:70%"></div><span class="bar-lbl skel" style="width:20px;height:8px"></span></div>
              <div class="bar-col"><div class="bar-rect skel" style="height:60%"></div><span class="bar-lbl skel" style="width:20px;height:8px"></span></div>
              <div class="bar-col"><div class="bar-rect skel" style="height:85%"></div><span class="bar-lbl skel" style="width:20px;height:8px"></span></div>
              <div class="bar-col"><div class="bar-rect skel" style="height:75%"></div><span class="bar-lbl skel" style="width:20px;height:8px"></span></div>
              <div class="bar-col"><div class="bar-rect skel" style="height:95%"></div><span class="bar-lbl skel" style="width:20px;height:8px"></span></div>
              <div class="bar-col"><div class="bar-rect skel" style="height:80%"></div><span class="bar-lbl skel" style="width:20px;height:8px"></span></div>
              <div class="bar-col"><div class="bar-rect skel" style="height:100%"></div><span class="bar-lbl skel" style="width:20px;height:8px"></span></div>
            </div>
          </div>
        </div>

      </div>

      <!-- ROW 2: Payment Methods + Customer Insights -->
      <div class="two-col">

        <div class="card">
          <div class="card-hdr">
            <span class="card-title">Payment Methods</span>
            <span class="card-sub">Revenue distribution</span>
          </div>
          <div class="card-body">
            <div class="pay-list" id="paymentList">
              <div class="pay-row">
                <div class="pay-meta">
                  <span class="skel skel-text" style="width:120px"></span>
                  <span class="skel skel-text" style="width:80px"></span>
                </div>
                <div class="pay-bar"><div class="skel" style="height:8px;width:100%"></div></div>
              </div>
              <div class="pay-row">
                <div class="pay-meta">
                  <span class="skel skel-text" style="width:140px"></span>
                  <span class="skel skel-text" style="width:80px"></span>
                </div>
                <div class="pay-bar"><div class="skel" style="height:8px;width:100%"></div></div>
              </div>
              <div class="pay-row">
                <div class="pay-meta">
                  <span class="skel skel-text" style="width:110px"></span>
                  <span class="skel skel-text" style="width:80px"></span>
                </div>
                <div class="pay-bar"><div class="skel" style="height:8px;width:100%"></div></div>
              </div>
            </div>
          </div>
        </div>

        <div class="card">
          <div class="card-hdr">
            <span class="card-title">Customer Insights</span>
            <span class="card-sub">Behavioural metrics</span>
          </div>
          <div class="card-body">
            <div class="insight-tbl" id="insightsList">
              <div class="insight-row">
                <span class="skel skel-text" style="width:160px"></span>
                <span class="skel skel-text" style="width:60px"></span>
              </div>
              <div class="insight-row">
                <span class="skel skel-text" style="width:140px"></span>
                <span class="skel skel-text" style="width:60px"></span>
              </div>
              <div class="insight-row">
                <span class="skel skel-text" style="width:170px"></span>
                <span class="skel skel-text" style="width:60px"></span>
              </div>
              <div class="insight-row">
                <span class="skel skel-text" style="width:100px"></span>
                <span class="skel skel-text" style="width:60px"></span>
              </div>
              <div class="insight-row">
                <span class="skel skel-text" style="width:110px"></span>
                <span class="skel skel-text" style="width:60px"></span>
              </div>
            </div>
          </div>
        </div>

      </div>

    </div><!-- /content -->
  </div><!-- /site-content -->
</div><!-- /site-wrapper -->

<div class="toast" id="toastEl"></div>

<script>window.__REPORTS__ = <?= json_encode($reportsData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;</script>
<script src="whoami.js?v=1"></script>
<script src="notif_dot.js?v=1"></script>
<script src="confirm_modal.js?v=1"></script>
<script src="reports.js?v=2"></script>
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