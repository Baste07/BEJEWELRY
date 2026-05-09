<?php
require_once __DIR__ . '/admin_auth.php';
require_once __DIR__ . '/db.php';
admin_require_page('inventory');
$pdo = adminDb();

$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$search = trim((string)($_GET['search'] ?? ''));
$lowThreshold = 5;

$totalProducts = (int) $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$inStockStmt = $pdo->prepare('SELECT COUNT(*) FROM products WHERE stock > ?');
$inStockStmt->execute([$lowThreshold]);
$inStock = (int) $inStockStmt->fetchColumn();
$lowStockStmt = $pdo->prepare('SELECT COUNT(*) FROM products WHERE stock > 0 AND stock <= ?');
$lowStockStmt->execute([$lowThreshold]);
$lowStock = (int) $lowStockStmt->fetchColumn();
$outOfStock = (int) $pdo->query("SELECT COUNT(*) FROM products WHERE stock <= 0")->fetchColumn();
$totalUnits = (int) $pdo->query("SELECT COALESCE(SUM(stock), 0) FROM products")->fetchColumn();

$where = ['1=1'];
$params = [];
if ($filter === 'instock') {
  $where[] = 'p.stock > ?';
  $params[] = $lowThreshold;
} elseif ($filter === 'low') {
  $where[] = 'p.stock > 0 AND p.stock <= ?';
  $params[] = $lowThreshold;
} elseif ($filter === 'outofstock') {
  $where[] = 'p.stock <= 0';
}
if ($search !== '') {
  $where[] = '(p.name LIKE ? OR p.description LIKE ?)';
  $s = '%' . $search . '%';
  $params[] = $s;
  $params[] = $s;
}
$whereSql = implode(' AND ', $where);

$sql = "SELECT p.id, p.name, p.stock, p.price, p.image, c.name AS category_name
        FROM products p LEFT JOIN categories c ON p.category_id = c.id
        WHERE $whereSql ORDER BY p.name";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$itemsList = $stmt->fetchAll(PDO::FETCH_ASSOC);

$items = [];
foreach ($itemsList as $r) {
  $qty = (int) $r['stock'];
  $status = $qty <= 0 ? 'outofstock' : ($qty <= 2 ? 'critical' : ($qty <= $lowThreshold ? 'low' : 'instock'));
  $items[] = [
    'id' => $r['id'],
    'name' => $r['name'],
    'sku' => 'BJ-' . $r['id'],
    'category' => $r['category_name'],
    'price' => (float) $r['price'],
    'stock_qty' => $qty,
    'max_stock' => max($qty, 30),
    'low_threshold' => $lowThreshold,
    'status' => $status,
    'image' => $r['image'] ?? '',
    'image_url' => !empty($r['image']) ? '../uploads/products/' . $r['image'] : null,
  ];
}

$u = $GLOBALS['ADMIN_USER'] ?? [];
$dispName = trim((string) (($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? '')));
$roleKey = $GLOBALS['ADMIN_ROLE'] ?? 'super_admin';
$roleLabel = $roleKey === 'inventory' ? 'Inventory Manager' : ($roleKey === 'super_admin' ? 'Super Admin' : ucfirst(str_replace('_', ' ', $roleKey)));

// Fetch stock transactions
$transactionsStmt = $pdo->prepare('
  SELECT sh.id, sh.product_id, sh.qty_added, sh.stock_after, sh.price, sh.note, sh.updated_by, sh.created_at, p.name
  FROM stock_history sh
  JOIN products p ON p.id = sh.product_id
  ORDER BY sh.created_at DESC
  LIMIT 100
');
$transactionsStmt->execute();
$transactionsList = $transactionsStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

$transactions = [];
foreach ($transactionsList as $t) {
  $qtyAdded = (int) $t['qty_added'];
  $stockAfter = (int) $t['stock_after'];
  $stockBefore = $stockAfter - $qtyAdded;
  $transactions[] = [
    'id' => (int) $t['id'],
    'product_id' => (int) $t['product_id'],
    'product_name' => $t['name'] ?? '',
    'qty_added' => $qtyAdded,
    'stock_before' => $stockBefore,
    'stock_after' => $stockAfter,
    'price' => (float) ($t['price'] ?? 0.0),
    'note' => $t['note'] ?? '',
    'updated_by' => $t['updated_by'] ?? 'System',
    'created_at' => $t['created_at'] ?? '',
  ];
}

$inventoryData = [
  'user' => ['name' => $dispName !== '' ? $dispName : 'Admin', 'role' => $roleLabel],
  'badges' => [
    'pending_orders' => (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'processing'")->fetchColumn(),
    'new_products' => (int) $pdo->query("SELECT COUNT(*) FROM products WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn(),
    'low_stock' => $lowStock,
    'pending_reviews' => (int) $pdo->query("SELECT COUNT(*) FROM product_reviews WHERE status = 'pending'")->fetchColumn(),
  ],
  'stats' => [
    'total' => $totalProducts,
    'in_stock' => $inStock,
    'low_stock' => $lowStock,
    'out_of_stock' => $outOfStock,
    'total_units' => $totalUnits,
    'low_threshold' => $lowThreshold,
  ],
  'items' => $items,
  'transactions' => $transactions,
  'counts' => ['all' => $totalProducts, 'instock' => $inStock, 'low' => $lowStock, 'outofstock' => $outOfStock],
  'filter' => $filter,
  'search' => $search,
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Bejewelry Admin — Inventory</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,500;0,600;0,700;1,400&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600;9..40,700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../admin/inventory.css">
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
  <?= csrf_meta_tag() ?>
</head>
<body>

<div class="loading-bar" id="loadingBar"></div>

<div class="site-wrapper">

  <?php $GLOBALS['NAV_ACTIVE'] = 'inventory'; require __DIR__ . '/includes/nav_sidebar.php'; ?>

  <!-- ── MAIN CONTENT ── -->
  <div class="site-content">

    <header class="topbar">
      <div class="topbar-left">
        <span class="topbar-title">Inventory</span>
        <span class="topbar-bc">Bejewelry Admin › Inventory</span>
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
          <h2>Inventory</h2>
          <p id="invSubtitle">Loading…</p>
        </div>
        <div class="page-hdr-actions">
          <button class="btn btn-ghost btn-sm" onclick="handleExport()">⬇ Export</button>
          <button class="btn btn-primary btn-sm" onclick="handleBulkRestock()">↺ Bulk Restock</button>
        </div>
      </div>

      <!-- LOW STOCK ALERT -->
      <div class="alert-bar hidden" id="alertBar">
        <span class="alert-bar-icon">⚠️</span>
        <span id="alertText">—</span>
        <a onclick="filterToLowStock()">View low stock items →</a>
      </div>

      <!-- STAT CARDS -->
      <div class="stats-row">
        <div class="stat-card">
          <span class="stat-icon-bg">📦</span>
          <span class="stat-label">Total Products</span>
          <div class="stat-value skel skel-val" id="valTotal">&nbsp;</div>
          <div class="stat-trend skel skel-text" id="trendTotal">&nbsp;</div>
        </div>
        <div class="stat-card">
          <span class="stat-icon-bg">✅</span>
          <span class="stat-label">In Stock</span>
          <div class="stat-value skel skel-val" id="valInStock">&nbsp;</div>
          <div class="stat-trend skel skel-text" id="trendInStock">&nbsp;</div>
        </div>
        <div class="stat-card warn-card">
          <span class="stat-icon-bg">⚠</span>
          <span class="stat-label">Low Stock</span>
          <div class="stat-value skel skel-val" id="valLow">&nbsp;</div>
          <div class="stat-trend skel skel-text" id="trendLow">&nbsp;</div>
        </div>
        <div class="stat-card">
          <span class="stat-icon-bg">🔢</span>
          <span class="stat-label">Total Units</span>
          <div class="stat-value skel skel-val" id="valUnits">&nbsp;</div>
          <div class="stat-trend skel skel-text" id="trendUnits">&nbsp;</div>
        </div>
      </div>

      <!-- FILTERS -->
      <div class="filters-bar" id="filtersBar">
        <div style="display:flex;gap:8px;align-items:center">
          <button class="tab-btn on" data-tab="products">📦 Products</button>
          <button class="tab-btn" data-tab="transactions">📊 Transactions</button>
        </div>
        <div style="display:flex;gap:8px;align-items:center;flex:1">
          <button class="fb on" data-filter="all">All</button>
          <button class="fb" data-filter="instock">In Stock</button>
          <button class="fb" data-filter="low">Low Stock</button>
          <button class="fb" data-filter="outofstock">Out of Stock</button>
          <div class="filter-wrap">
            <input class="filter-input" type="text" id="invSearch" placeholder="Search inventory…"/>
          </div>
        </div>
      </div>

      <!-- INVENTORY TABLE / TRANSACTIONS TABLE -->
      <div class="table-wrap" id="productsTab">
        <table class="tbl">
          <thead>
            <tr>
              <th>Product</th>
              <th>SKU</th>
              <th>Category</th>
              <th>Price</th>
              <th>Stock Qty</th>
              <th>Stock Level</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody id="inventoryBody">
            <tr><td colspan="8"><div class="skel skel-row" style="margin:14px var(--s5)"></div></td></tr>
            <tr><td colspan="8"><div class="skel skel-row" style="margin:14px var(--s5)"></div></td></tr>
            <tr><td colspan="8"><div class="skel skel-row" style="margin:14px var(--s5)"></div></td></tr>
            <tr><td colspan="8"><div class="skel skel-row" style="margin:14px var(--s5)"></div></td></tr>
            <tr><td colspan="8"><div class="skel skel-row" style="margin:14px var(--s5)"></div></td></tr>
          </tbody>
        </table>
      </div>

      <div class="table-wrap hidden" id="transactionsTab">
        <table class="tbl">
          <thead>
            <tr>
              <th>Date & Time</th>
              <th>Product</th>
              <th>Qty Changed</th>
              <th>Price</th>
              <th>Stock Before</th>
              <th>Stock After</th>
              <th>Purchased By</th>
            </tr>
          </thead>
          <tbody id="transactionsBody">
            <tr><td colspan="7"><div class="skel skel-row" style="margin:14px var(--s5)"></div></td></tr>
            <tr><td colspan="7"><div class="skel skel-row" style="margin:14px var(--s5)"></div></td></tr>
            <tr><td colspan="7"><div class="skel skel-row" style="margin:14px var(--s5)"></div></td></tr>
            <tr><td colspan="7"><div class="skel skel-row" style="margin:14px var(--s5)"></div></td></tr>
            <tr><td colspan="7"><div class="skel skel-row" style="margin:14px var(--s5)"></div></td></tr>
          </tbody>
        </table>
      </div>

    </div><!-- /content -->
  </div><!-- /site-content -->
</div><!-- /site-wrapper -->

<!-- ═══════ RESTOCK MODAL ═══════ -->
<div class="modal-bg" id="restockModal">
  <div class="modal">
    <div class="modal-hdr">
      <h3 id="restockModalTitle">Restock Product</h3>
      <button class="modal-close" onclick="closeModal('restockModal')">✕</button>
    </div>

    <div class="modal-product-info">
      <div class="modal-product-icon" id="restockProductIcon">💎</div>
      <div>
        <div class="modal-product-name" id="restockProductName">—</div>
        <div class="modal-product-meta" id="restockProductMeta">—</div>
      </div>
    </div>

    <div class="current-stock-display">
      <div>
        <div class="csd-label">Current Stock</div>
        <div class="csd-value" id="restockCurrentQty">—</div>
      </div>
      <div id="restockCurrentBadge"></div>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label class="form-label">Add Units</label>
        <input class="form-input" type="number" id="restockAddQty" min="1" placeholder="e.g. 20"/>
      </div>
      <div class="form-group">
        <label class="form-label">Restock Reason</label>
        <select class="form-select" id="restockReason">
          <option value="">Select reason…</option>
          <option value="regular">Regular restock</option>
          <option value="supplier">New supplier delivery</option>
          <option value="return">Customer return</option>
          <option value="adjustment">Manual adjustment</option>
        </select>
      </div>
    </div>

    <div class="form-group">
      <label class="form-label">Notes (optional)</label>
      <input class="form-input" type="text" id="restockNotes" placeholder="e.g. Supplier: Golden Gems Co."/>
    </div>

    <div class="modal-footer">
      <button class="btn btn-ghost btn-sm" onclick="closeModal('restockModal')">Cancel</button>
      <button class="btn btn-primary btn-sm" id="restockSubmitBtn">Confirm Restock</button>
    </div>
  </div>
</div>

<!-- ═══════ UNIT PRICE MODAL ═══════ -->
<div class="modal-bg" id="priceModal">
  <div class="modal">
    <div class="modal-hdr">
      <h3 id="priceModalTitle">Unit price</h3>
      <button class="modal-close" onclick="closeModal('priceModal')">✕</button>
    </div>
    <input type="hidden" id="priceProductId" value=""/>
    <div class="form-group">
      <label class="form-label">Price (PHP)</label>
      <input class="form-input" type="number" id="priceInput" min="0" step="0.01" placeholder="0.00"/>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost btn-sm" onclick="closeModal('priceModal')">Cancel</button>
      <button class="btn btn-primary btn-sm" type="button" onclick="submitPriceModal()">Save price</button>
    </div>
  </div>
</div>

<!-- ═══════ IMAGE ZOOM MODAL ═══════ -->
<div class="modal-bg" id="imageZoomModal">
  <div class="modal" style="max-width: 600px;">
    <div class="modal-hdr">
      <h3 id="zoomImageTitle">Product Image</h3>
      <button class="modal-close" onclick="closeModal('imageZoomModal')">✕</button>
    </div>
    <div style="display: flex; justify-content: center; padding: var(--s5); background: var(--blush-light);">
      <img id="zoomImage" src="" alt="Product" style="max-width: 100%; max-height: 500px; object-fit: contain; border-radius: var(--r-md);"/>
    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost btn-sm" onclick="closeModal('imageZoomModal')">Close</button>
    </div>
  </div>
</div>

<div class="toast" id="toastEl"></div>

<script>window.__INVENTORY__ = <?= json_encode($inventoryData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;</script>
<script src="whoami.js?v=1"></script>
<script src="../admin/notif_dot.js?v=1"></script>
<script src="../admin/confirm_modal.js?v=1"></script>
<script src="../admin/inventory.js?v=2"></script>
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