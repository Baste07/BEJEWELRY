<?php
declare(strict_types=1);

require_once __DIR__ . '/admin_auth.php';
require_once __DIR__ . '/db.php';
admin_require_page('courier_accounts');
$pdo = adminDb();

function courier_active_delivery_count(PDO $pdo, int $courierId): int
{
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE courier_user_id = ? AND status NOT IN ('delivered','cancelled')");
    $stmt->execute([$courierId]);
    return (int) $stmt->fetchColumn();
}

$query = trim((string) ($_GET['q'] ?? ($_POST['q'] ?? '')));
$courierStatusFilter = strtolower(trim((string) ($_GET['courier_status'] ?? ($_POST['courier_status'] ?? 'all'))));
if (!in_array($courierStatusFilter, ['all', 'available', 'busy'], true)) {
  $courierStatusFilter = 'all';
}
$orderSearch = trim((string) ($_GET['order_search'] ?? ($_POST['order_search'] ?? '')));
$logStatusFilter = strtolower(trim((string) ($_GET['log_status'] ?? ($_POST['log_status'] ?? 'all'))));
if (!in_array($logStatusFilter, ['all', 'shipped', 'delivered', 'cancelled'], true)) {
  $logStatusFilter = 'all';
}
$logSearch = trim((string) ($_GET['log_search'] ?? ($_POST['log_search'] ?? '')));
$editId = (int) ($_GET['edit'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate();
    $action = (string) ($_POST['action'] ?? '');
    $redirect = 'courier_accounts.php';
    if ($query !== '') {
        $redirect .= '?q=' . rawurlencode($query);
    }
    if ($courierStatusFilter !== 'all' || $orderSearch !== '') {
      $redirect .= ($query !== '' ? '&' : '?');
      $redirect .= 'courier_status=' . rawurlencode($courierStatusFilter);
      if ($orderSearch !== '') {
        $redirect .= '&order_search=' . rawurlencode($orderSearch);
      }
    }
    if ($logStatusFilter !== 'all' || $logSearch !== '') {
      $redirect .= ($query !== '' || $courierStatusFilter !== 'all' || $orderSearch !== '') ? '&' : '?';
      $redirect .= 'log_status=' . rawurlencode($logStatusFilter);
      if ($logSearch !== '') {
        $redirect .= '&log_search=' . rawurlencode($logSearch);
      }
    }

    if ($action === 'update_courier') {
        $id = (int) ($_POST['id'] ?? 0);
        $first = trim((string) ($_POST['first_name'] ?? ''));
        $last = trim((string) ($_POST['last_name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $phone = trim((string) ($_POST['phone'] ?? ''));
        if ($id > 0 && $first !== '' && $last !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $encUser = bejewelry_encrypt_user_private_fields([
          'phone' => $phone !== '' ? $phone : null,
        ]);
            $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ? WHERE id = ? AND role = 'courier'");
        $stmt->execute([$first, $last, $email, $encUser['phone'], $id]);
        }
        header('Location: ' . $redirect);
        exit;
    }

    if ($action === 'delete_courier') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0 && courier_active_delivery_count($pdo, $id) === 0) {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'courier'");
            $stmt->execute([$id]);
        }
        header('Location: ' . $redirect);
        exit;
    }

    header('Location: ' . $redirect);
    exit;
}

$stmt = $pdo->query(
    "SELECT u.id, u.first_name, u.last_name, u.email, u.phone, u.created_at, u.totp_secret,
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
     ORDER BY is_available DESC, u.created_at DESC, u.id DESC"
);
$couriers = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
foreach ($couriers as &$courierRow) {
  $courierRow = bejewelry_decrypt_user_private_fields($courierRow);
}
unset($courierRow);

if ($query !== '') {
    $needle = mb_strtolower($query);
    $couriers = array_values(array_filter($couriers, static function (array $c) use ($needle): bool {
        $haystack = mb_strtolower(trim(((string) ($c['first_name'] ?? '')) . ' ' . ((string) ($c['last_name'] ?? '')) . ' ' . (string) ($c['email'] ?? '') . ' ' . (string) ($c['phone'] ?? '')));
        return $haystack !== '' && str_contains($haystack, $needle);
    }));
}
if ($courierStatusFilter !== 'all') {
  $wantAvailable = $courierStatusFilter === 'available';
  $couriers = array_values(array_filter($couriers, static function (array $c) use ($wantAvailable): bool {
    return ((int) ($c['is_available'] ?? 0) === 1) === $wantAvailable;
  }));
}

$total = count($couriers);
$linked = 0;
$availableCount = 0;
$busyCount = 0;
$editCourier = null;

foreach ($couriers as $c) {
    if (!empty($c['totp_secret'])) {
        $linked++;
    }
    if ((int) ($c['is_available'] ?? 0) === 1) {
        $availableCount++;
    } else {
        $busyCount++;
    }
    if ($editId > 0 && (int) ($c['id'] ?? 0) === $editId) {
        $editCourier = $c;
    }
}

  $activeOrdersStmt = $pdo->query(
    "SELECT o.id, o.ship_name, o.status, o.courier_user_id, o.courier_name, o.courier_assigned_at, o.created_at,
        u.first_name, u.last_name, u.email
     FROM orders o
     LEFT JOIN users u ON u.id = o.courier_user_id AND u.role = 'courier'
     WHERE o.courier_user_id IS NOT NULL AND o.status NOT IN ('delivered','cancelled')
     ORDER BY o.courier_assigned_at DESC, o.created_at DESC, o.id DESC"
  );
  $activeOrders = $activeOrdersStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
  if ($orderSearch !== '') {
    $orderNeedle = mb_strtolower($orderSearch);
    $activeOrders = array_values(array_filter($activeOrders, static function (array $order) use ($orderNeedle): bool {
      $fields = [
        (string) ($order['id'] ?? ''),
        (string) ($order['ship_name'] ?? ''),
        (string) ($order['courier_name'] ?? ''),
      ];
      foreach ($fields as $field) {
        if ($field !== '' && str_contains(mb_strtolower($field), $orderNeedle)) {
          return true;
        }
      }
      return false;
    }));
  }
  $courierOrders = [];
  foreach ($activeOrders as $order) {
    $cid = (int) ($order['courier_user_id'] ?? 0);
    if ($cid <= 0) {
      continue;
    }
    if (!isset($courierOrders[$cid])) {
      $courierOrders[$cid] = [];
    }
    $courierOrders[$cid][] = $order;
  }
  if ($orderSearch !== '') {
    $couriers = array_values(array_filter($couriers, static function (array $c) use ($courierOrders): bool {
      $cid = (int) ($c['id'] ?? 0);
      return $cid > 0 && !empty($courierOrders[$cid]);
    }));
  }
  $activeOrderCount = count($activeOrders);

$logWhere = ["o.courier_user_id IS NOT NULL", "o.status IN ('shipped','delivered','cancelled')"];
$logParams = [];
if ($logStatusFilter !== 'all') {
  $logWhere[] = 'o.status = ?';
  $logParams[] = $logStatusFilter;
}
if ($logSearch !== '') {
  $logWhere[] = '(o.id LIKE ? OR o.ship_name LIKE ? OR o.courier_name LIKE ? OR CONCAT(COALESCE(u.first_name, ""), " ", COALESCE(u.last_name, "")) LIKE ? OR u.email LIKE ?)';
  $logLike = '%' . $logSearch . '%';
  $logParams[] = $logLike;
  $logParams[] = $logLike;
  $logParams[] = $logLike;
  $logParams[] = $logLike;
  $logParams[] = $logLike;
}
$logWhereSql = implode(' AND ', $logWhere);
$courierLogStmt = $pdo->prepare(
  "SELECT o.id, o.ship_name, o.total, o.payment_method, o.status, o.courier_name, o.courier_assigned_at, o.created_at,
          u.first_name, u.last_name, u.email
   FROM orders o
   LEFT JOIN users u ON u.id = o.courier_user_id AND u.role = 'courier'
   WHERE $logWhereSql
   ORDER BY o.courier_assigned_at DESC, o.created_at DESC, o.id DESC
   LIMIT 300"
);
$courierLogStmt->execute($logParams);
$courierLogRows = $courierLogStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

$courierAverageRows = [];
try {
  $courierAverageStmt = $pdo->query(
    "SELECT u.id,
            u.first_name,
            u.last_name,
            u.email,
            COALESCE(ROUND(AVG(cr.rating), 1), 0) AS avg_rating,
            COUNT(cr.id) AS rating_count
     FROM users u
     LEFT JOIN order_courier_ratings cr ON cr.courier_user_id = u.id
     WHERE u.role = 'courier'
     GROUP BY u.id, u.first_name, u.last_name, u.email
     ORDER BY u.first_name, u.last_name, u.id"
  );
  $courierAverageRows = $courierAverageStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {
  $courierAverageRows = [];
}

$courierRatingsRows = [];
$courierRatingsError = '';
try {
  $courierRatingsStmt = $pdo->query(
    "SELECT cr.id, cr.order_id, cr.rating, cr.body, cr.created_at, cr.courier_name,
            cr.courier_user_id,
            o.ship_name AS customer_name,
            u.first_name, u.last_name, u.email
     FROM order_courier_ratings cr
     LEFT JOIN orders o ON o.id = cr.order_id
     LEFT JOIN users u ON u.id = cr.courier_user_id AND u.role = 'courier'
     ORDER BY cr.created_at DESC, cr.id DESC
     LIMIT 300"
  );
  $courierRatingsRows = $courierRatingsStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {
  $courierRatingsRows = [];
  $courierRatingsError = 'Courier ratings table is not available yet.';
}

$u = $GLOBALS['ADMIN_USER'] ?? [];
$dispName = trim((string) (($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? '')));
$roleLabel = 'Order Manager';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Bejewelry Admin — Courier Accounts</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;1,400&family=DM+Sans:opsz,wght@9..40,400;9..40,600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../admin/reviews.css">
  <script>
    (function () {
      try {
        history.scrollRestoration = 'manual';
      } catch (e) {}
      // If a saved scroll position exists, hide the page immediately so the user
      // never sees the flash at scroll-top before we restore their position.
      try {
        if (sessionStorage.getItem('courier_accounts_scroll_y') !== null) {
          document.documentElement.style.opacity = '0';
        }
      } catch (e) {}
    })();
  </script>
</head>
<body>

<div class="loading-bar" id="loadingBar"></div>

<div class="site-wrapper">

  <?php $GLOBALS['NAV_ACTIVE'] = 'courier_accounts'; require __DIR__ . '/includes/nav_sidebar.php'; ?>

  <div class="site-content">
    <header class="topbar">
      <div class="topbar-left">
        <span class="topbar-title">Courier accounts</span>
        <span class="topbar-bc">Bejewelry Admin › Courier Accounts</span>
      </div>
    </header>

    <div class="content">
      <div class="page-hdr" style="display:flex;align-items:flex-end;justify-content:space-between;gap:16px;flex-wrap:wrap">
        <div>
          <h2>Manage courier accounts</h2>
          <p>Registered couriers can upload delivery proof and confirm deliveries.</p>
        </div>
      </div>

      <div class="stats-row" style="margin-bottom:16px">
        <div class="stat-card"><span class="stat-icon">🚚</span><span class="stat-label">Total couriers</span><div class="stat-value"><?= (int) $total ?></div></div>
        <div class="stat-card"><span class="stat-icon">🟢</span><span class="stat-label">Available</span><div class="stat-value"><?= (int) $availableCount ?></div></div>
        <div class="stat-card"><span class="stat-icon">⏳</span><span class="stat-label">Busy</span><div class="stat-value"><?= (int) $busyCount ?></div></div>
        <div class="stat-card"><span class="stat-icon">📦</span><span class="stat-label">Active orders</span><div class="stat-value"><?= (int) $activeOrderCount ?></div></div>
        <div class="stat-card"><span class="stat-icon">🔐</span><span class="stat-label">2FA ready</span><div class="stat-value"><?= (int) $linked ?></div></div>
      </div>

      <div style="background:var(--white);border:1px solid var(--border);border-radius:var(--r-lg);padding:18px 18px 14px;margin-bottom:16px">
        <div class="courier-filter-head">
          <div>
            <h3 style="margin:0 0 4px">Courier table</h3>
            <div style="color:var(--muted);font-size:.82rem">Use the status filter and order search to see each courier's current workload in one view.</div>
          </div>
          <form method="get" class="courier-filter-form">
            <input type="hidden" name="q" value="<?= htmlspecialchars($query, ENT_QUOTES, 'UTF-8') ?>">

            <select name="courier_status" class="finput" style="padding:8px 12px" onchange="this.form.submit()">
              <option value="all" <?= $courierStatusFilter === 'all' ? 'selected' : '' ?>>All couriers</option>
              <option value="available" <?= $courierStatusFilter === 'available' ? 'selected' : '' ?>>Available only</option>
              <option value="busy" <?= $courierStatusFilter === 'busy' ? 'selected' : '' ?>>Busy only</option>
            </select>

            <input type="text" name="q" value="<?= htmlspecialchars($query, ENT_QUOTES, 'UTF-8') ?>" placeholder="Search courier..." class="finput" style="padding:8px 12px">

            <input type="text" name="order_search" value="<?= htmlspecialchars($orderSearch, ENT_QUOTES, 'UTF-8') ?>" placeholder="Search order ID..." class="finput" style="padding:8px 12px">

            <button class="btn btn-ghost btn-sm" type="submit" style="display:inline-flex;align-items:center;justify-content:center">Search</button>

            <a href="courier_accounts.php" class="btn btn-ghost btn-sm" style="display:inline-flex;align-items:center;justify-content:center;text-decoration:none">Reset</a>
          </form>
        </div>
        <div class="table-wrap" style="overflow:auto">
          <table class="data-table" style="width:100%;border-collapse:collapse;font-size:.82rem">
            <thead>
              <tr style="text-align:left;border-bottom:1px solid var(--border)">
                <th style="padding:12px 14px">ID</th>
                <th style="padding:12px 14px">Courier</th>
                <th style="padding:12px 14px">Email</th>
                <th style="padding:12px 14px">Phone</th>
                <th style="padding:12px 14px">2FA</th>
                <th style="padding:12px 14px">Status</th>
                <th style="padding:12px 14px">Active orders</th>
                <th style="padding:12px 14px">Order IDs</th>
                <th style="padding:12px 14px">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($couriers as $c): ?>
                <?php
                  $name = trim((string) (($c['first_name'] ?? '') . ' ' . ($c['last_name'] ?? '')));
                  $cid = (int) ($c['id'] ?? 0);
                  $activeDeliveries = (int) ($c['active_deliveries'] ?? 0);
                  $available = (int) ($c['is_available'] ?? 0) === 1;
                  $ordersForCourier = $courierOrders[$cid] ?? [];
                  $activeCount = count($ordersForCourier);
                  $ordersModalData = array_map(static function (array $order): array {
                    return [
                      'id' => (string) ($order['id'] ?? ''),
                      'customer_name' => (string) ($order['ship_name'] ?? ''),
                      'status' => (string) ($order['status'] ?? ''),
                      'assigned_at' => (string) ($order['courier_assigned_at'] ?? ''),
                      'created_at' => (string) ($order['created_at'] ?? ''),
                    ];
                  }, $ordersForCourier);
                  $ordersModalJson = htmlspecialchars((string) json_encode($ordersModalData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
                  $orderLabels = [];
                  foreach (array_slice($ordersForCourier, 0, 4) as $order) {
                    $orderLabels[] = '#' . (string) ($order['id'] ?? '');
                  }
                  if ($activeCount > 4) {
                    $orderLabels[] = '+' . ($activeCount - 4) . ' more';
                  }
                ?>
                <tr style="border-bottom:1px solid var(--border)">
                  <td style="padding:12px 14px">#<?= $cid ?></td>
                  <td style="padding:12px 14px;font-weight:600"><?= htmlspecialchars($name !== '' ? $name : 'Courier', ENT_QUOTES, 'UTF-8') ?></td>
                  <td style="padding:12px 14px"><?= htmlspecialchars((string) ($c['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                  <td style="padding:12px 14px"><?= htmlspecialchars((string) ($c['phone'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                  <td style="padding:12px 14px"><?= !empty($c['totp_secret']) ? 'Enabled' : 'Not set' ?></td>
                  <td style="padding:12px 14px">
                    <?php if ($available): ?>
                      <span class="badge" style="background:#e9f8ef;color:#16803d;border:1px solid #bde7ca">Available</span>
                    <?php else: ?>
                      <span class="badge" style="background:#fff1e8;color:#b45309;border:1px solid #f7c59e">Busy</span>
                    <?php endif; ?>
                  </td>
                  <td style="padding:12px 14px"><?= (int) $activeCount ?></td>
                  <td style="padding:12px 14px;color:var(--muted)"><?= htmlspecialchars(!empty($orderLabels) ? implode(', ', $orderLabels) : 'None', ENT_QUOTES, 'UTF-8') ?></td>
                  <td style="padding:12px 14px">
                    <div style="display:flex;gap:8px;flex-wrap:wrap">
                      <button
                        type="button"
                        class="btn btn-ghost btn-sm"
                        style="padding:5px 10px;font-size:.62rem;display:inline-flex;align-items:center;justify-content:center"
                        data-courier-name="<?= htmlspecialchars($name !== '' ? $name : ('Courier #' . $cid), ENT_QUOTES, 'UTF-8') ?>"
                        data-orders="<?= $ordersModalJson ?>"
                        onclick="openCourierOrdersModal(this)">
                        View Orders
                      </button>
                      <a href="courier_accounts.php?edit=<?= $cid ?><?= $query !== '' ? '&q=' . rawurlencode($query) : '' ?><?= $courierStatusFilter !== 'all' ? '&courier_status=' . rawurlencode($courierStatusFilter) : '' ?><?= $orderSearch !== '' ? '&order_search=' . rawurlencode($orderSearch) : '' ?><?= $logStatusFilter !== 'all' ? '&log_status=' . rawurlencode($logStatusFilter) : '' ?><?= $logSearch !== '' ? '&log_search=' . rawurlencode($logSearch) : '' ?>" class="btn btn-ghost btn-sm" style="padding:5px 10px;font-size:.62rem;display:inline-flex;align-items:center;justify-content:center;text-decoration:none">Edit</a>
                      <form method="post" onsubmit="return confirm('Delete this courier account?');" style="display:inline">
                                                <?php echo csrf_token_field(); ?>
                        <input type="hidden" name="action" value="delete_courier">
                        <input type="hidden" name="id" value="<?= (int) $cid ?>">
                        <input type="hidden" name="q" value="<?= htmlspecialchars($query, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="courier_status" value="<?= htmlspecialchars($courierStatusFilter, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="order_search" value="<?= htmlspecialchars($orderSearch, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="log_status" value="<?= htmlspecialchars($logStatusFilter, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="log_search" value="<?= htmlspecialchars($logSearch, ENT_QUOTES, 'UTF-8') ?>">
                        <button type="submit" class="btn btn-ghost btn-sm" style="padding:5px 10px;font-size:.62rem;display:inline-flex;align-items:center;justify-content:center" <?= $activeDeliveries > 0 ? 'disabled title="Courier has active deliveries"' : '' ?>>Delete</button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
              <?php if (!$couriers): ?>
                <tr><td colspan="9" style="padding:24px;text-align:center;color:var(--muted)">No courier accounts found.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div style="background:var(--white);border:1px solid var(--border);border-radius:var(--r-lg);padding:18px 18px 14px;margin-bottom:16px">
        <div class="courier-filter-head">
          <div>
            <h3 style="margin:0 0 4px">Courier order log</h3>
            <div style="color:var(--muted);font-size:.82rem">History of shipped, delivered, and cancelled courier orders for admin monitoring.</div>
          </div>
          <form method="get" class="courier-log-filter-form">
            <input type="hidden" name="q" value="<?= htmlspecialchars($query, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="courier_status" value="<?= htmlspecialchars($courierStatusFilter, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="order_search" value="<?= htmlspecialchars($orderSearch, ENT_QUOTES, 'UTF-8') ?>">

            <select name="log_status" class="finput" style="padding:8px 12px" onchange="this.form.submit()">
              <option value="all" <?= $logStatusFilter === 'all' ? 'selected' : '' ?>>All</option>
              <option value="shipped" <?= $logStatusFilter === 'shipped' ? 'selected' : '' ?>>Shipped</option>
              <option value="delivered" <?= $logStatusFilter === 'delivered' ? 'selected' : '' ?>>Delivered</option>
              <option value="cancelled" <?= $logStatusFilter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
            </select>

            <input type="text" name="log_search" value="<?= htmlspecialchars($logSearch, ENT_QUOTES, 'UTF-8') ?>" placeholder="Search order, customer, courier..." class="finput" style="padding:8px 12px">

            <button class="btn btn-ghost btn-sm" type="submit" style="display:inline-flex;align-items:center;justify-content:center">Search</button>

            <?php if ($logStatusFilter !== 'all' || $logSearch !== ''): ?>
              <a href="courier_accounts.php" class="btn btn-ghost btn-sm" style="display:inline-flex;align-items:center;justify-content:center;text-decoration:none">Clear</a>
            <?php endif; ?>
          </form>
        </div>
        <div class="table-wrap" style="overflow:auto">
          <table class="data-table" style="width:100%;border-collapse:collapse;font-size:.82rem">
            <thead>
              <tr style="text-align:left;border-bottom:1px solid var(--border)">
                <th style="padding:12px 14px">Order ID</th>
                <th style="padding:12px 14px">Courier</th>
                <th style="padding:12px 14px">Customer</th>
                <th style="padding:12px 14px">Payment</th>
                <th style="padding:12px 14px">Status</th>
                <th style="padding:12px 14px">Assigned at</th>
                <th style="padding:12px 14px">Order date</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($courierLogRows as $row): ?>
                <?php
                  $logStatus = strtolower((string) ($row['status'] ?? ''));
                  $logStatusClass = $logStatus === 'delivered'
                    ? 'background:#e9f8ef;color:#16803d;border:1px solid #bde7ca'
                    : ($logStatus === 'shipped'
                      ? 'background:#eef3ff;color:#3559c7;border:1px solid #c4d0ff'
                      : 'background:#fff1f1;color:#b42318;border:1px solid #f2c0c0');
                  $logCourierName = trim((string) ($row['courier_name'] ?? ''));
                  if ($logCourierName === '') {
                    $logCourierName = trim((string) (($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')));
                  }
                  if ($logCourierName === '') {
                    $logCourierName = (string) ($row['email'] ?? 'Courier');
                  }
                ?>
                <tr style="border-bottom:1px solid var(--border)">
                  <td style="padding:12px 14px;font-weight:600;color:#d94e6a">#<?= htmlspecialchars((string) ($row['id'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                  <td style="padding:12px 14px"><?= htmlspecialchars($logCourierName, ENT_QUOTES, 'UTF-8') ?></td>
                  <td style="padding:12px 14px"><?= htmlspecialchars((string) ($row['ship_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                  <td style="padding:12px 14px"><span class="badge" style="background:#f9e7eb;color:#b57483;border:1px solid #f1c9d1"><?= htmlspecialchars((string) ($row['payment_method'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span></td>
                  <td style="padding:12px 14px"><span class="badge" style="<?= $logStatusClass ?>"><?= htmlspecialchars(strtoupper($logStatus), ENT_QUOTES, 'UTF-8') ?></span></td>
                  <td style="padding:12px 14px;color:var(--muted)"><?= htmlspecialchars((string) ($row['courier_assigned_at'] ? date('M d, Y h:i A', strtotime((string) $row['courier_assigned_at'])) : '—'), ENT_QUOTES, 'UTF-8') ?></td>
                  <td style="padding:12px 14px;color:var(--muted)"><?= htmlspecialchars(date('M d, Y', strtotime((string) ($row['created_at'] ?? 'now'))), ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
              <?php endforeach; ?>
              <?php if (!$courierLogRows): ?>
                <tr><td colspan="7" style="padding:24px;text-align:center;color:var(--muted)">No courier log records found.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div style="background:var(--white);border:1px solid var(--border);border-radius:var(--r-lg);padding:18px 18px 14px;margin-bottom:16px">
        <div class="courier-filter-head">
          <div>
            <h3 style="margin:0 0 4px">Courier averages</h3>
            <div style="color:var(--muted);font-size:.82rem">Average customer courier rating and total rating count per courier.</div>
          </div>
        </div>
        <div class="table-wrap" style="overflow:auto">
          <table class="data-table" style="width:100%;border-collapse:collapse;font-size:.82rem">
            <thead>
              <tr style="text-align:left;border-bottom:1px solid var(--border)">
                <th style="padding:12px 14px">Courier</th>
                <th style="padding:12px 14px;text-align:right">Avg Rating</th>
                <th style="padding:12px 14px;text-align:right"># Ratings</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($courierAverageRows as $row): ?>
                <?php
                  $courierName = trim((string) (($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')));
                  if ($courierName === '') {
                    $courierName = (string) ($row['email'] ?? 'Courier');
                  }
                  $avgRating = (float) ($row['avg_rating'] ?? 0);
                  $ratingCount = (int) ($row['rating_count'] ?? 0);
                ?>
                <tr style="border-bottom:1px solid var(--border)">
                  <td style="padding:12px 14px"><strong><?= htmlspecialchars($courierName, ENT_QUOTES, 'UTF-8') ?></strong></td>
                  <td style="padding:12px 14px;text-align:right;font-weight:600"><?= $ratingCount > 0 ? htmlspecialchars(number_format($avgRating, 1) . ' / 5', ENT_QUOTES, 'UTF-8') : '—' ?></td>
                  <td style="padding:12px 14px;text-align:right;color:var(--muted)"><?= (int) $ratingCount ?></td>
                </tr>
              <?php endforeach; ?>
              <?php if (!$courierAverageRows): ?>
                <tr><td colspan="3" style="padding:24px;text-align:center;color:var(--muted)">No courier averages found.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div style="background:var(--white);border:1px solid var(--border);border-radius:var(--r-lg);padding:18px 18px 14px;margin-bottom:16px">
        <div class="courier-filter-head">
          <div>
            <h3 style="margin:0 0 4px">Courier ratings</h3>
            <div style="color:var(--muted);font-size:.82rem">Customer feedback submitted for delivered orders and assigned couriers.</div>
          </div>
        </div>
        <div class="table-wrap" style="overflow:auto">
          <table class="data-table" style="width:100%;border-collapse:collapse;font-size:.82rem">
            <thead>
              <tr style="text-align:left;border-bottom:1px solid var(--border)">
                <th style="padding:12px 14px">Log ID</th>
                <th style="padding:12px 14px">Order ID</th>
                <th style="padding:12px 14px">Courier</th>
                <th style="padding:12px 14px">Customer</th>
                <th style="padding:12px 14px">Rating</th>
                <th style="padding:12px 14px">Comment</th>
                <th style="padding:12px 14px">Submitted at</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($courierRatingsRows as $row): ?>
                <?php
                  $courierName = trim((string) (($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? '')));
                  if ($courierName === '') {
                    $courierName = trim((string) ($row['courier_name'] ?? ''));
                  }
                  if ($courierName === '') {
                    $courierName = (string) ($row['email'] ?? 'Courier');
                  }

                  $rating = max(1, min(5, (int) ($row['rating'] ?? 0)));
                  $stars = str_repeat('★', $rating) . str_repeat('☆', 5 - $rating);

                  $commentRaw = trim((string) ($row['body'] ?? ''));
                  $comment = $commentRaw === '' ? '—' : $commentRaw;
                  if (mb_strlen($comment) > 180) {
                    $comment = mb_substr($comment, 0, 177) . '...';
                  }
                ?>
                <tr style="border-bottom:1px solid var(--border)">
                  <td style="padding:12px 14px">#<?= (int) ($row['id'] ?? 0) ?></td>
                  <td style="padding:12px 14px;font-weight:600;color:#d94e6a">#<?= htmlspecialchars((string) ($row['order_id'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                  <td style="padding:12px 14px"><?= htmlspecialchars($courierName, ENT_QUOTES, 'UTF-8') ?></td>
                  <td style="padding:12px 14px"><?= htmlspecialchars((string) ($row['customer_name'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                  <td style="padding:12px 14px"><span class="badge" style="background:#fff7d6;color:#8c6800;border:1px solid #edd050"><?= htmlspecialchars($stars, ENT_QUOTES, 'UTF-8') ?></span></td>
                  <td style="padding:12px 14px;color:var(--muted)"><?= htmlspecialchars($comment, ENT_QUOTES, 'UTF-8') ?></td>
                  <td style="padding:12px 14px;color:var(--muted)"><?= htmlspecialchars((string) ($row['created_at'] ? date('M d, Y h:i A', strtotime((string) $row['created_at'])) : '—'), ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
              <?php endforeach; ?>
              <?php if ($courierRatingsError !== ''): ?>
                <tr><td colspan="7" style="padding:24px;text-align:center;color:var(--muted)"><?= htmlspecialchars($courierRatingsError, ENT_QUOTES, 'UTF-8') ?></td></tr>
              <?php elseif (!$courierRatingsRows): ?>
                <tr><td colspan="7" style="padding:24px;text-align:center;color:var(--muted)">No courier ratings found.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <?php if ($editCourier): ?>
        <div style="background:var(--white);border:1px solid var(--border);border-radius:var(--r-lg);padding:18px 18px 14px;margin-bottom:16px">
          <div style="display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap;align-items:flex-start;margin-bottom:12px">
            <div>
              <h3 style="margin:0 0 4px">Edit courier #<?= (int) $editCourier['id'] ?></h3>
              <div style="color:var(--muted);font-size:.82rem">Update courier profile details. Availability is derived from active deliveries.</div>
            </div>
            <a href="courier_accounts.php<?= $query !== '' ? '?q=' . rawurlencode($query) : '' ?><?= $courierStatusFilter !== 'all' ? '&courier_status=' . rawurlencode($courierStatusFilter) : '' ?><?= $orderSearch !== '' ? '&order_search=' . rawurlencode($orderSearch) : '' ?><?= $logStatusFilter !== 'all' ? '&log_status=' . rawurlencode($logStatusFilter) : '' ?><?= $logSearch !== '' ? '&log_search=' . rawurlencode($logSearch) : '' ?>" class="btn btn-ghost btn-sm" style="display:inline-flex;align-items:center;justify-content:center;text-decoration:none">Cancel</a>
          </div>
          <form method="post" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;align-items:end">
                        <?php echo csrf_token_field(); ?>
            <input type="hidden" name="action" value="update_courier">
            <input type="hidden" name="id" value="<?= (int) $editCourier['id'] ?>">
            <input type="hidden" name="q" value="<?= htmlspecialchars($query, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="courier_status" value="<?= htmlspecialchars($courierStatusFilter, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="order_search" value="<?= htmlspecialchars($orderSearch, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="log_status" value="<?= htmlspecialchars($logStatusFilter, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="log_search" value="<?= htmlspecialchars($logSearch, ENT_QUOTES, 'UTF-8') ?>">
            <label class="field"><span style="display:block;margin-bottom:6px">First name</span><input class="finput" type="text" name="first_name" value="<?= htmlspecialchars((string) ($editCourier['first_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required style="width:100%;padding:10px 14px"></label>
            <label class="field"><span style="display:block;margin-bottom:6px">Last name</span><input class="finput" type="text" name="last_name" value="<?= htmlspecialchars((string) ($editCourier['last_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required style="width:100%;padding:10px 14px"></label>
            <label class="field"><span style="display:block;margin-bottom:6px">Email</span><input class="finput" type="email" name="email" value="<?= htmlspecialchars((string) ($editCourier['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required style="width:100%;padding:10px 14px"></label>
            <label class="field"><span style="display:block;margin-bottom:6px">Phone</span><input class="finput" type="text" name="phone" value="<?= htmlspecialchars((string) ($editCourier['phone'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" style="width:100%;padding:10px 14px"></label>
            <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
              <button type="submit" class="btn btn-primary btn-sm" style="display:inline-flex;align-items:center;justify-content:center">Save changes</button>
            </div>
          </form>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<div id="courierOrdersModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1000;align-items:center;justify-content:center;padding:16px">
  <div style="background:#fff;border:1px solid var(--border);border-radius:14px;max-width:820px;width:100%;max-height:85vh;overflow:auto;box-shadow:0 18px 48px rgba(0,0,0,.25)">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:14px 16px;border-bottom:1px solid var(--border)">
      <h3 id="courierOrdersTitle" style="margin:0;font-size:1.02rem">Courier Orders</h3>
      <button type="button" class="btn btn-ghost btn-sm" style="padding:5px 10px;font-size:.62rem" onclick="closeCourierOrdersModal()">Close</button>
    </div>
    <div style="padding:14px 16px">
      <table style="width:100%;border-collapse:collapse;font-size:.82rem">
        <thead>
          <tr style="text-align:left;border-bottom:1px solid var(--border)">
            <th style="padding:10px 10px">Order ID</th>
            <th style="padding:10px 10px">Customer</th>
            <th style="padding:10px 10px">Status</th>
            <th style="padding:10px 10px">Assigned</th>
            <th style="padding:10px 10px">Created</th>
          </tr>
        </thead>
        <tbody id="courierOrdersBody"></tbody>
      </table>
    </div>
  </div>
</div>

<script src="../admin/confirm_modal.js?v=1"></script>
<script src="whoami.js?v=1"></script>
<script>
  function escHtml(val) {
    var d = document.createElement('div');
    d.textContent = val == null ? '' : String(val);
    return d.innerHTML;
  }

  function formatShortDate(value) {
    if (!value) return '—';
    var dt = new Date(value);
    if (Number.isNaN(dt.getTime())) return '—';
    return dt.toLocaleString('en-PH', {
      year: 'numeric',
      month: 'short',
      day: '2-digit',
      hour: '2-digit',
      minute: '2-digit'
    });
  }

  function openCourierOrdersModal(triggerEl) {
    var modal = document.getElementById('courierOrdersModal');
    var title = document.getElementById('courierOrdersTitle');
    var body = document.getElementById('courierOrdersBody');
    if (!modal || !title || !body || !triggerEl) return;

    var courierName = triggerEl.getAttribute('data-courier-name') || 'Courier';
    var rawOrders = triggerEl.getAttribute('data-orders') || '[]';
    var orders = [];
    try {
      orders = JSON.parse(rawOrders);
    } catch (e) {
      orders = [];
    }

    title.textContent = courierName + ' — Active Orders';

    if (!Array.isArray(orders) || orders.length === 0) {
      body.innerHTML = '<tr><td colspan="5" style="padding:16px 10px;color:var(--muted);text-align:center">No active orders assigned to this courier.</td></tr>';
    } else {
      body.innerHTML = orders.map(function (o) {
        var status = String(o.status || '').toLowerCase();
        var badgeStyle = status === 'shipped'
          ? 'background:#eef3ff;color:#3559c7;border:1px solid #c4d0ff'
          : (status === 'processing'
            ? 'background:#eef5ff;color:#2460b0;border:1px solid #cfe0ff'
            : 'background:#f5f5f5;color:#4b5563;border:1px solid #e5e7eb');
        return '<tr style="border-bottom:1px solid var(--border)">' +
          '<td style="padding:10px 10px;font-weight:600;color:#d94e6a">#' + escHtml(o.id || '') + '</td>' +
          '<td style="padding:10px 10px">' + escHtml(o.customer_name || '—') + '</td>' +
          '<td style="padding:10px 10px"><span class="badge" style="' + badgeStyle + '">' + escHtml((status || 'unknown').toUpperCase()) + '</span></td>' +
          '<td style="padding:10px 10px;color:var(--muted)">' + escHtml(formatShortDate(o.assigned_at)) + '</td>' +
          '<td style="padding:10px 10px;color:var(--muted)">' + escHtml(formatShortDate(o.created_at)) + '</td>' +
        '</tr>';
      }).join('');
    }

    modal.style.display = 'flex';
  }

  function closeCourierOrdersModal() {
    var modal = document.getElementById('courierOrdersModal');
    if (modal) modal.style.display = 'none';
  }

  const scrollKey = 'courier_accounts_scroll_y';

  function saveScrollPosition() {
    sessionStorage.setItem(scrollKey, String(window.scrollY || 0));
  }

  function restoreScrollPositionOnce() {
    const raw = sessionStorage.getItem(scrollKey);
    if (raw === null) {
      // No saved position — make sure page is visible (in case opacity was hidden).
      document.documentElement.style.opacity = '1';
      return;
    }
    const savedY = Number(raw);
    sessionStorage.removeItem(scrollKey);
    // Double rAF: first = layout done, second = paint done.
    // Only then scroll to the saved position and fade the page in.
    requestAnimationFrame(function () {
      requestAnimationFrame(function () {
        if (!Number.isNaN(savedY) && savedY > 0) {
          window.scrollTo({ top: savedY, behavior: 'instant' });
        }
        // Fade in after scroll is set — hides the top-of-page flash entirely.
        document.documentElement.style.transition = 'opacity 0.15s ease';
        document.documentElement.style.opacity = '1';
      });
    });
  }

  document.addEventListener('DOMContentLoaded', function() {
    const name = <?= json_encode($dispName !== '' ? $dispName : 'Order Manager', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const role = <?= json_encode($roleLabel, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const av = name.split(/\s+/).map(function(w){ return w[0]; }).join('').slice(0, 2).toUpperCase();
    const a = document.getElementById('sbAvatar');
    const u = document.getElementById('sbUsername');
    const r = document.getElementById('sbUserRole');
    if (a) a.textContent = av;
    if (u) u.textContent = name;
    if (r) r.textContent = role;
  });

  // Restore scroll only after the page is fully loaded and painted.
  window.addEventListener('load', function () {
    restoreScrollPositionOnce();
  });

  function handleLogout() {
    if (typeof window.adminConfirm === 'function') {
      window.adminConfirm('Log out of Bejewelry Admin?', function () { window.location.href = '../logout.php'; }, { okText: 'Log out' });
      return;
    }
    window.location.href = '../logout.php';
  }

  // Save scroll on every form submit (Search, Delete, Save changes, etc.)
  document.querySelectorAll('form').forEach(function (form) {
    form.addEventListener('submit', function () {
      saveScrollPosition();
    });
  });

  // Save scroll on every same-page link click (Edit, Reset filters, status tabs,
  // Clear, Cancel — anything that reloads this same PHP page with new query params).
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
      } catch (e) {
        // Ignore malformed href values.
      }
    });
  });

  // Save scroll when the courier status dropdown changes (it auto-submits its form).
  // The form submit listener above already covers this, but this is an explicit
  // safety net in case the onchange fires before the submit event registers.
  var courierStatusSelect = document.querySelector('select[name="courier_status"]');
  if (courierStatusSelect) {
    courierStatusSelect.addEventListener('change', function () {
      saveScrollPosition();
    });
  }

  var courierOrdersModal = document.getElementById('courierOrdersModal');
  if (courierOrdersModal) {
    courierOrdersModal.addEventListener('click', function (e) {
      if (e.target === courierOrdersModal) {
        closeCourierOrdersModal();
      }
    });
  }

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      closeCourierOrdersModal();
    }
  });
</script>
</body>
</html>