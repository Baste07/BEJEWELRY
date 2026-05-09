<?php
require_once __DIR__ . '/admin_auth.php';
require_once __DIR__ . '/db.php';
admin_require_page('promotions');
$pdo = adminDb();

$now = date('Y-m-d H:i:s');
$startOfMonth = date('Y-m-01 00:00:00');

$countStmt = function (string $sql, array $params = []) use ($pdo): int {
  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  return (int) $stmt->fetchColumn();
};

$activeCount = $countStmt(
  'SELECT COUNT(*) FROM promotions WHERE is_active = 1 AND (start_at IS NULL OR start_at <= ?) AND (end_at IS NULL OR end_at >= ?)',
  [$now, $now]
);
$expiringSoon = $countStmt(
  'SELECT COUNT(*) FROM promotions WHERE is_active = 1 AND end_at IS NOT NULL AND end_at >= ? AND end_at <= DATE_ADD(?, INTERVAL 7 DAY)',
  [$now, $now]
);
$totalRedemptions = $countStmt('SELECT COUNT(*) FROM promotion_redemptions');
$redemptionsThisMonth = $countStmt('SELECT COUNT(*) FROM promotion_redemptions WHERE created_at >= ?', [$startOfMonth]);
$totalDiscounts = (float) $pdo->query("SELECT COALESCE(SUM(discount_amt), 0) FROM promotion_redemptions")->fetchColumn();
$totalOrderValue = (float) $pdo->query("SELECT COALESCE(SUM(o.total), 0) FROM promotion_redemptions r JOIN orders o ON o.id = r.order_id")->fetchColumn();
$avgDiscountRate = $totalOrderValue > 0 ? round(($totalDiscounts / $totalOrderValue) * 100, 1) : 0;

$promosStmt = $pdo->query("SELECT * FROM promotions ORDER BY created_at DESC");
$promos = [];
while ($row = $promosStmt->fetch(PDO::FETCH_ASSOC)) {
  $value = (float) $row['value'];
  $minOrder = (float) $row['min_order'];
  $usedCount = (int) $row['used_count'];
  $maxUses = $row['max_uses'] !== null ? (int) $row['max_uses'] : null;
  $startAt = $row['start_at'];
  $endAt = $row['end_at'];
  $isActive = (bool) $row['is_active'];
  $savedStmt = $pdo->prepare("SELECT COALESCE(SUM(discount_amt), 0) FROM promotion_redemptions WHERE promotion_id = ?");
  $savedStmt->execute([$row['id']]);
  $totalSaved = (float) $savedStmt->fetchColumn();
  if ($endAt && $endAt < $now) $status = 'expired';
  elseif ($startAt && $startAt > $now) $status = 'scheduled';
  elseif (!$isActive) $status = 'paused';
  else $status = 'active';
  $promos[] = [
    'id' => (int) $row['id'],
    'code' => $row['code'],
    'name' => $row['name'],
    'description' => $row['name'],
    'status' => $status,
    'discount_type' => $row['type'] === 'percent' ? 'percentage' : 'fixed',
    'discount_value' => $value,
    'min_order' => $minOrder,
    'usage_limit' => $maxUses ?? 0,
    'times_used' => $usedCount,
    'total_saved' => $totalSaved,
    'start_date' => $startAt ? date('M j, Y', strtotime($startAt)) : '',
    'end_date' => $endAt ? date('M j, Y', strtotime($endAt)) : '',
    'start_date_input' => $startAt ? date('Y-m-d', strtotime($startAt)) : '',
    'end_date_input' => $endAt ? date('Y-m-d', strtotime($endAt)) : '',
    'apply_to' => 'All Products',
  ];
}

$promosData = [
  'user' => ['name' => 'Admin', 'role' => 'admin'],
  'badges' => [
    'pending_orders' => (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'processing'")->fetchColumn(),
    'new_products' => (int) $pdo->query("SELECT COUNT(*) FROM products WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn(),
    'low_stock' => (int) $pdo->query("SELECT COUNT(*) FROM products WHERE stock > 0 AND stock <= 5")->fetchColumn(),
    'pending_reviews' => (int) $pdo->query("SELECT COUNT(*) FROM product_reviews WHERE status = 'pending'")->fetchColumn(),
  ],
  'stats' => [
    'active_count' => $activeCount,
    'codes_expiring_soon' => $expiringSoon,
    'total_redemptions' => $totalRedemptions,
    'redemptions_this_month' => $redemptionsThisMonth,
    'total_discounts_given' => $totalDiscounts,
    'avg_discount_rate' => $avgDiscountRate,
  ],
  'promos' => $promos,
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <?= csrf_meta_tag() ?>
  <title>Bejewelry — Promotions</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,500;0,600;0,700;1,400&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600;9..40,700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="promotions.css">
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

  <?php $GLOBALS['NAV_ACTIVE'] = 'promotions'; require __DIR__ . '/includes/nav_sidebar.php'; ?>

  <!-- ── MAIN CONTENT ── -->
  <div class="site-content">

    <!-- Topbar -->
    <header class="topbar">
      <div class="topbar-left">
        <span class="topbar-title">Promotions</span>
        <span class="topbar-bc">Bejewelry Admin › Promotions</span>
      </div>
      <div class="topbar-right">
        <div class="topbar-search">
          <span style="color:var(--muted-light);font-size:.9rem">⌕</span>
          <input type="text" placeholder="Search orders, products…" id="globalSearch">
        </div>
        <button class="icon-btn" title="Notifications" onclick="window.location.href='notifications.php'">
          🔔<span class="dot" id="notifDot" style="display:none"></span>
        </button>
        <button class="icon-btn" title="Refresh" onclick="loadPageData()">↺</button>
      </div>
    </header>

    <!-- Content -->
    <div class="content">

      <!-- Page Header -->
      <div class="page-hdr">
        <div>
          <h2>Promotions</h2>
          <p id="promoSubtitle">Manage discount codes and campaigns</p>
        </div>
        <div class="page-hdr-actions">
          <button class="btn btn-ghost btn-sm" onclick="exportPromos()">⬇ Export</button>
          <button class="btn btn-primary btn-sm" onclick="openCreateModal()">＋ Create Promo</button>
        </div>
      </div>

      <!-- Stats Row -->
      <div class="stats-row">
        <div class="stat-card">
          <span class="stat-icon">🏷️</span>
          <span class="stat-label">Active Codes</span>
          <div class="stat-value skel skel-val" id="valActive"> </div>
          <div class="stat-sub skel skel-text" id="subActive"> </div>
        </div>
        <div class="stat-card">
          <span class="stat-icon">🔁</span>
          <span class="stat-label">Redemptions</span>
          <div class="stat-value skel skel-val" id="valRedemptions"> </div>
          <div class="stat-sub skel skel-text" id="subRedemptions"> </div>
        </div>
        <div class="stat-card">
          <span class="stat-icon">💸</span>
          <span class="stat-label">Discounts Given</span>
          <div class="stat-value skel skel-val" id="valDiscounts"> </div>
          <div class="stat-sub skel skel-text" id="subDiscounts"> </div>
        </div>
        <div class="stat-card">
          <span class="stat-icon">📉</span>
          <span class="stat-label">Avg. Discount Rate</span>
          <div class="stat-value skel skel-val" id="valAvgRate"> </div>
          <div class="stat-sub skel skel-text" id="subAvgRate"> </div>
        </div>
      </div>

      <!-- Filters -->
      <div class="filters-bar">
        <button class="filter-chip active" onclick="setFilter(this,'all')">All</button>
        <button class="filter-chip" onclick="setFilter(this,'active')">Active</button>
        <button class="filter-chip" onclick="setFilter(this,'scheduled')">Scheduled</button>
        <button class="filter-chip" onclick="setFilter(this,'expired')">Expired</button>
        <button class="filter-chip" onclick="setFilter(this,'paused')">Paused</button>
        <div class="filter-search">
          <span style="color:var(--muted-light);font-size:.85rem">⌕</span>
          <input type="text" id="promoSearch" placeholder="Search codes…" oninput="filterPromos()">
        </div>
      </div>

      <!-- Promo Grid (populated by JS) -->
      <div class="promo-grid" id="promoGrid">
        <!-- Skeleton placeholders while loading -->
        <div class="promo-card">
          <div class="promo-card-stripe"></div>
          <div class="promo-card-body">
            <div class="skel skel-val" style="width:55%;margin-bottom:12px"></div>
            <div class="skel skel-text"></div>
            <div class="skel skel-text" style="width:70%"></div>
          </div>
        </div>
        <div class="promo-card">
          <div class="promo-card-stripe"></div>
          <div class="promo-card-body">
            <div class="skel skel-val" style="width:55%;margin-bottom:12px"></div>
            <div class="skel skel-text"></div>
            <div class="skel skel-text" style="width:70%"></div>
          </div>
        </div>
        <div class="promo-card">
          <div class="promo-card-stripe"></div>
          <div class="promo-card-body">
            <div class="skel skel-val" style="width:55%;margin-bottom:12px"></div>
            <div class="skel skel-text"></div>
            <div class="skel skel-text" style="width:70%"></div>
          </div>
        </div>
      </div>

    </div><!-- /content -->
  </div><!-- /site-content -->
</div><!-- /site-wrapper -->


<!-- ══════════════════════════════════════
     CREATE / EDIT PROMO MODAL
══════════════════════════════════════ -->
<div class="modal-overlay" id="promoModal">
  <div class="modal">
    <div class="modal-hd">
      <div>
        <h3 id="modalTitle">Create Promotion</h3>
        <p id="modalSubtitle">Fill in the details for your new promo code</p>
      </div>
      <button class="modal-close" onclick="closeModal('promoModal')">✕</button>
    </div>

    <div class="modal-body">
      <input type="hidden" id="editPromoId">

      <div class="form-section-title">Code & Discount</div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Promo Code</label>
          <input class="form-input" type="text" id="fCode" placeholder="e.g. SUMMER20">
          <div class="form-hint">Customers enter this at checkout</div>
        </div>
        <div class="form-group">
          <label class="form-label">Discount Type</label>
          <select class="form-select" id="fType">
            <option value="percentage">Percentage (%)</option>
            <option value="fixed">Fixed Amount (₱)</option>
            <option value="free_shipping">Free Shipping</option>
          </select>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Discount Value</label>
          <input class="form-input" type="number" id="fValue" placeholder="e.g. 20" min="0">
        </div>
        <div class="form-group">
          <label class="form-label">Min. Order Amount (₱)</label>
          <input class="form-input" type="number" id="fMinOrder" placeholder="0 = no minimum" min="0">
        </div>
      </div>

      <div class="form-section-title">Validity</div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Start Date</label>
          <input class="form-input" type="date" id="fStartDate">
        </div>
        <div class="form-group">
          <label class="form-label">End Date</label>
          <input class="form-input" type="date" id="fEndDate">
          <div class="form-hint">Leave blank for no expiry</div>
        </div>
      </div>

      <div class="form-section-title">Usage & Scope</div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Usage Limit</label>
          <input class="form-input" type="number" id="fUsageLimit" placeholder="Leave blank = unlimited" min="1">
        </div>
        <div class="form-group">
          <label class="form-label">Per-Customer Limit</label>
          <input class="form-input" type="number" id="fPerCustomer" placeholder="Leave blank = unlimited" min="1">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Apply To</label>
        <select class="form-select" id="fApplyTo">
          <option value="all">All Products</option>
          <option value="rings">Rings</option>
          <option value="necklaces">Necklaces</option>
          <option value="earrings">Earrings</option>
          <option value="bracelets">Bracelets</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Description (internal)</label>
        <input class="form-input" type="text" id="fDescription" placeholder="e.g. Valentine's Day campaign 2026">
        <div class="form-hint">Not shown to customers</div>
      </div>
    </div>

    <div class="modal-ft">
      <button class="btn btn-ghost btn-sm" onclick="closeModal('promoModal')">Cancel</button>
      <button class="btn btn-primary btn-sm" id="modalSaveBtn" onclick="savePromo()">Create Promotion</button>
    </div>
  </div>
</div>


<!-- ══════════════════════════════════════
     CONFIRM MODAL (deactivate / delete)
══════════════════════════════════════ -->
<div class="modal-overlay" id="confirmModal">
  <div class="modal confirm-modal">
    <div class="modal-body" style="padding:var(--s8)">
      <span class="confirm-icon" id="confirmIcon">⚠️</span>
      <h3 id="confirmTitle">Are you sure?</h3>
      <p id="confirmMsg">This action cannot be undone.</p>
    </div>
    <div class="confirm-ft">
      <button class="btn btn-ghost btn-sm" onclick="closeModal('confirmModal')">Cancel</button>
      <button class="btn btn-danger btn-sm" id="confirmActionBtn">Confirm</button>
    </div>
  </div>
</div>


<div class="toast" id="toast"></div>

<script>window.__PROMOTIONS__ = <?= json_encode($promosData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;</script>
<script src="whoami.js?v=1"></script>
<script src="notif_dot.js?v=1"></script>
<script src="confirm_modal.js?v=1"></script>
<script src="promotions.js?v=2"></script>
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