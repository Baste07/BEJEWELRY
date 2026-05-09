<?php
require_once __DIR__ . '/admin_auth.php';
require_once __DIR__ . '/db.php';
admin_require_page('customers');
$pdo = adminDb();

$page = max(1, (int)($_GET['page'] ?? 1));
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$search = trim((string)($_GET['search'] ?? ''));
$perPage = 12;

$startOfMonth = date('Y-m-01 00:00:00');
$endOfMonth = date('Y-m-t 23:59:59');

$totalCustomers = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'customer' AND archived_at IS NULL")->fetchColumn();
$newStmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'customer' AND archived_at IS NULL AND created_at BETWEEN ? AND ?");
$newStmt->execute([$startOfMonth, $endOfMonth]);
$newThisMonth = (int) $newStmt->fetchColumn();

$orderStats = $pdo->query("
  SELECT o.user_id, COUNT(*) AS order_count, COALESCE(SUM(o.total), 0) AS total_spent
  FROM orders o
  JOIN users u ON u.id = o.user_id
  WHERE u.role = 'customer' AND u.archived_at IS NULL
  GROUP BY o.user_id
")->fetchAll(PDO::FETCH_ASSOC);
$orderMap = [];
foreach ($orderStats as $r) { $orderMap[$r['user_id']] = $r; }

$vipCount = 0;
$repeatCount = 0;
foreach ($orderMap as $uid => $r) {
  if (($r['order_count'] >= 3) || ($r['total_spent'] >= 5000)) $vipCount++;
  if ($r['order_count'] >= 2) $repeatCount++;
}
$repeatRate = $totalCustomers > 0 ? round(($repeatCount / $totalCustomers) * 100) : 0;

$where = ["u.role = 'customer'", "u.archived_at IS NULL"];
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

$cntSql = "SELECT COUNT(*) FROM users u LEFT JOIN (SELECT user_id, COUNT(*) AS order_count, SUM(total) AS total_spent FROM orders GROUP BY user_id) o ON u.id = o.user_id WHERE $whereSql";
$cntStmt = $pdo->prepare($cntSql);
$cntStmt->execute($params);
$totalCount = (int) $cntStmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalCount / $perPage));
$offset = ($page - 1) * $perPage;

$sql = "SELECT u.id, u.first_name, u.last_name, u.email, u.phone, u.created_at,
  u.failed_login_attempts, u.locked_at, u.locked_by, u.lock_reason,
  u.archived_at, u.archived_by, u.archive_reason,
  o.order_count, o.total_spent
        FROM users u
        LEFT JOIN (SELECT user_id, COUNT(*) AS order_count, SUM(total) AS total_spent FROM orders GROUP BY user_id) o ON u.id = o.user_id
        WHERE $whereSql ORDER BY u.created_at DESC LIMIT $perPage OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$customersList = $stmt->fetchAll(PDO::FETCH_ASSOC);

$customerDetails = [];
foreach ($customersList as $c) {
  $cid = $c['id'];
  $name = trim($c['first_name'] . ' ' . $c['last_name']);
  $recentStmt = $pdo->prepare("SELECT o.id, o.total, o.status, o.created_at, (SELECT name FROM order_items WHERE order_id = o.id LIMIT 1) AS item_name FROM orders o WHERE o.user_id = ? ORDER BY o.created_at DESC LIMIT 5");
  $recentStmt->execute([$cid]);
  $recentOrders = $recentStmt->fetchAll(PDO::FETCH_ASSOC);
  $customerDetails[$cid] = [
    'id' => $cid, 'name' => $name, 'email' => $c['email'], 'phone' => $c['phone'],
    'tier' => (($c['order_count'] >= 3) || ($c['total_spent'] >= 5000)) ? 'vip' : ($c['order_count'] >= 1 ? 'regular' : 'new'),
    'order_count' => (int)($c['order_count'] ?? 0), 'total_spent' => (float)($c['total_spent'] ?? 0),
    'avg_rating' => null,
    'failed_login_attempts' => (int) ($c['failed_login_attempts'] ?? 0),
    'locked_at' => $c['locked_at'],
    'locked_by' => isset($c['locked_by']) ? (int) $c['locked_by'] : null,
    'lock_reason' => $c['lock_reason'],
    'is_locked' => !empty($c['locked_at']),
    'archived_at' => $c['archived_at'],
    'archived_by' => isset($c['archived_by']) ? (int) $c['archived_by'] : null,
    'archive_reason' => $c['archive_reason'],
    'is_archived' => !empty($c['archived_at']),
    'avatar_url' => $customersList[$cid - $customersList[0]['id']]['avatar_url'] ?? null,
    'recent_orders' => array_map(function ($o) {
      return ['id' => $o['id'], 'item_name' => $o['item_name'], 'created_at' => $o['created_at'], 'total' => (float)$o['total'], 'status' => $o['status']];
    }, $recentOrders),
  ];
}

foreach ($customersList as &$c) {
  $c['name'] = trim($c['first_name'] . ' ' . $c['last_name']);
  $c['order_count'] = (int)($c['order_count'] ?? 0);
  $c['total_spent'] = (float)($c['total_spent'] ?? 0);
  $c['tier'] = (($c['order_count'] >= 3) || ($c['total_spent'] >= 5000)) ? 'vip' : ($c['order_count'] >= 1 ? 'regular' : 'new');
  $c['avg_rating'] = null;
  $c['is_locked'] = !empty($c['locked_at']);
  $c['is_archived'] = !empty($c['archived_at']);
  // Attach profile avatar URL from customer uploads (if any)
  $avatarUrl = null;
  $uid = (int)$c['id'];
  $baseDir = dirname(__DIR__) . '/uploads/profile';
  foreach (['jpg','jpeg','png','webp'] as $ext) {
    $path = $baseDir . '/user_' . $uid . '.' . $ext;
    if (is_file($path)) {
      $avatarUrl = '../uploads/profile/' . basename($path);
      break;
    }
  }
  $c['avatar_url'] = $avatarUrl;
}
unset($c);

$counts = ['all' => $totalCustomers, 'vip' => $vipCount, 'new' => $newThisMonth];
$adminUser = $GLOBALS['ADMIN_USER'] ?? [];
$adminName = trim((string) (($adminUser['first_name'] ?? '') . ' ' . ($adminUser['last_name'] ?? '')));
$adminName = $adminName !== '' ? $adminName : (string) ($adminUser['email'] ?? 'Admin');
$customersData = [
  'user' => ['name' => $adminName, 'role' => $GLOBALS['ADMIN_ROLE'] ?? 'super_admin'],
  'badges' => [
    'pending_orders' => (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'processing'")->fetchColumn(),
    'new_products' => (int) $pdo->query("SELECT COUNT(*) FROM products WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn(),
    'low_stock' => (int) $pdo->query("SELECT COUNT(*) FROM products WHERE stock > 0 AND stock <= 5")->fetchColumn(),
    'pending_reviews' => (int) $pdo->query("SELECT COUNT(*) FROM product_reviews WHERE status = 'pending'")->fetchColumn(),
  ],
  'stats' => [
    'total' => $totalCustomers, 'vip' => $vipCount, 'new_this_month' => $newThisMonth,
    'new_pct_change' => 0, 'repeat_rate' => $repeatRate, 'repeat_pct_change' => 0,
  ],
  'customers' => $customersList,
  'customer_details' => $customerDetails,
  'counts' => $counts,
  'total_pages' => $totalPages,
  'total_count' => $totalCount,
  'page' => $page,
  'filter' => $filter,
  'search' => $search,
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <?= csrf_meta_tag() ?>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Bejewelry Admin — Customer accounts</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,500;0,600;0,700;1,400&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600;9..40,700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="customers.css">
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

  <?php $GLOBALS['NAV_ACTIVE'] = 'customers'; require __DIR__ . '/includes/nav_sidebar.php'; ?>

  <!-- ── MAIN CONTENT ── -->
  <div class="site-content">

    <header class="topbar">
      <div class="topbar-left">
        <span class="topbar-title">Customer accounts</span>
        <span class="topbar-bc">Bejewelry Admin › Customer accounts</span>
      </div>
      <div class="topbar-right">
        <button class="icon-btn" title="Refresh" onclick="loadPageData()">↺</button>
      </div>
    </header>

    <div class="content">

      <!-- PAGE HEADER -->
      <div class="page-hdr">
        <div>
          <h2>Customer accounts</h2>
          <p id="custSubtitle">Loading…</p>
        </div>
        <div class="page-hdr-actions">
          <div class="view-toggle" role="group" aria-label="Customer list layout">
            <button class="btn btn-ghost btn-sm view-btn on" type="button" data-view="grid" onclick="setCustomerView('grid')">Grid</button>
            <button class="btn btn-ghost btn-sm view-btn" type="button" data-view="table" onclick="setCustomerView('table')">Table</button>
          </div>
          <button class="btn btn-ghost btn-sm" onclick="handleExport()">⬇ Export</button>
        </div>
      </div>

      <!-- STAT CARDS -->
      <div class="stats-row">
        <div class="stat-card">
          <span class="stat-icon">👥</span>
          <span class="stat-label">Total Customers</span>
          <div class="stat-value skel skel-val" id="valTotal">&nbsp;</div>
          <div class="stat-trend skel skel-text" id="trendTotal">&nbsp;</div>
        </div>
        <div class="stat-card">
          <span class="stat-icon">💎</span>
          <span class="stat-label">VIP Customers</span>
          <div class="stat-value skel skel-val" id="valVip">&nbsp;</div>
          <div class="stat-trend skel skel-text" id="trendVip">&nbsp;</div>
        </div>
        <div class="stat-card">
          <span class="stat-icon">✨</span>
          <span class="stat-label">New This Month</span>
          <div class="stat-value skel skel-val" id="valNew">&nbsp;</div>
          <div class="stat-trend skel skel-text" id="trendNew">&nbsp;</div>
        </div>
        <div class="stat-card">
          <span class="stat-icon">🔁</span>
          <span class="stat-label">Repeat Rate</span>
          <div class="stat-value skel skel-val" id="valRepeat">&nbsp;</div>
          <div class="stat-trend skel skel-text" id="trendRepeat">&nbsp;</div>
        </div>
      </div>

      <!-- FILTERS -->
      <div class="filters-bar" id="filtersBar">
        <button class="fb on" data-filter="all">All</button>
        <button class="fb" data-filter="vip">VIP</button>
        <button class="fb" data-filter="new">New</button>
        <div class="filter-wrap">
          <input class="filter-input" type="text" id="custSearch" placeholder="Search customers…"/>
        </div>
      </div>

      <!-- CUSTOMER GRID -->
      <div class="cust-grid" id="custGrid">
        <div class="cust-card" style="pointer-events:none">
          <div class="skel skel-circle" style="width:56px;height:56px;margin:0 auto var(--s3)"></div>
          <div class="skel skel-text" style="width:50%;margin:0 auto var(--s2)"></div>
          <div class="skel skel-text" style="width:70%;margin:0 auto var(--s4)"></div>
          <div style="display:flex;justify-content:center;gap:var(--s5);padding-top:var(--s4);border-top:1px solid var(--border)">
            <div class="skel" style="width:36px;height:32px"></div>
            <div class="skel" style="width:56px;height:32px"></div>
            <div class="skel" style="width:36px;height:32px"></div>
          </div>
        </div>
        <div class="cust-card" style="pointer-events:none">
          <div class="skel skel-circle" style="width:56px;height:56px;margin:0 auto var(--s3)"></div>
          <div class="skel skel-text" style="width:50%;margin:0 auto var(--s2)"></div>
          <div class="skel skel-text" style="width:70%;margin:0 auto var(--s4)"></div>
          <div style="display:flex;justify-content:center;gap:var(--s5);padding-top:var(--s4);border-top:1px solid var(--border)">
            <div class="skel" style="width:36px;height:32px"></div>
            <div class="skel" style="width:56px;height:32px"></div>
            <div class="skel" style="width:36px;height:32px"></div>
          </div>
        </div>
        <div class="cust-card" style="pointer-events:none">
          <div class="skel skel-circle" style="width:56px;height:56px;margin:0 auto var(--s3)"></div>
          <div class="skel skel-text" style="width:50%;margin:0 auto var(--s2)"></div>
          <div class="skel skel-text" style="width:70%;margin:0 auto var(--s4)"></div>
          <div style="display:flex;justify-content:center;gap:var(--s5);padding-top:var(--s4);border-top:1px solid var(--border)">
            <div class="skel" style="width:36px;height:32px"></div>
            <div class="skel" style="width:56px;height:32px"></div>
            <div class="skel" style="width:36px;height:32px"></div>
          </div>
        </div>
      </div>

      <!-- CUSTOMER TABLE -->
      <div class="cust-table-wrap hidden" id="custTableWrap">
        <table class="cust-table" id="custTable">
          <thead>
            <tr>
              <th>Customer</th>
              <th>Email</th>
              <th>Phone</th>
              <th>Orders</th>
              <th>Spent</th>
              <th>Tier</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody id="custTableBody">
            <tr>
              <td colspan="7"><div class="skel skel-row" style="margin:14px var(--s5)"></div></td>
            </tr>
            <tr>
              <td colspan="7"><div class="skel skel-row" style="margin:14px var(--s5)"></div></td>
            </tr>
            <tr>
              <td colspan="7"><div class="skel skel-row" style="margin:14px var(--s5)"></div></td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="pagination" id="pagination"></div>

    </div><!-- /content -->
  </div><!-- /site-content -->
</div><!-- /site-wrapper -->

<!-- ═══════ CUSTOMER PROFILE MODAL ═══════ -->
<div class="modal-bg" id="custModal">
  <div class="modal">
    <div class="modal-hdr">
      <h3 id="modalTitle">Customer Profile</h3>
      <button class="modal-close" onclick="closeModal('custModal')">✕</button>
    </div>
    <div class="modal-profile">
      <div class="modal-av" id="modalAv">—</div>
      <div>
        <div class="modal-profile-name" id="modalName">—</div>
        <div class="modal-profile-sub" id="modalContact">—</div>
        <div id="modalTierBadge"></div>
      </div>
    </div>
    <div class="modal-stats">
      <div class="modal-stat-box">
        <div class="modal-stat-v" id="modalOrders">—</div>
        <div class="modal-stat-l">Total Orders</div>
      </div>
      <div class="modal-stat-box">
        <div class="modal-stat-v" id="modalSpent">—</div>
        <div class="modal-stat-l">Total Spent</div>
      </div>
      <div class="modal-stat-box">
        <div class="modal-stat-v" id="modalRating">—</div>
        <div class="modal-stat-l">Avg Rating</div>
      </div>
    </div>
    <span class="modal-section-label">Recent Orders</span>
    <table class="modal-tbl">
      <thead>
        <tr><th>#Order</th><th>Item</th><th>Date</th><th>Total</th><th>Status</th></tr>
      </thead>
      <tbody id="modalOrdersBody">
        <tr><td colspan="5" style="text-align:center;color:var(--muted-light);padding:16px">Loading…</td></tr>
      </tbody>
    </table>
    <div class="modal-footer">
      <button class="btn btn-ghost btn-sm" onclick="closeModal('custModal')">Close</button>
      <button class="btn btn-ghost btn-sm" id="modalToggleLockBtn" onclick="toggleCustomerLockFromModal()">Lock Account</button>
      <button class="btn btn-ghost btn-sm" id="modalToggleArchiveBtn" onclick="toggleCustomerArchiveFromModal()">Archive Account</button>
      <button class="btn btn-primary btn-sm" id="modalViewOrdersBtn">View All Orders →</button>
    </div>
  </div>
</div>

<div class="toast" id="toastEl"></div>

<script>window.__CUSTOMERS__ = <?= json_encode($customersData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;</script>
<script src="whoami.js?v=1"></script>
<script src="confirm_modal.js?v=1"></script>
<script src="customers.js?v=2"></script>
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