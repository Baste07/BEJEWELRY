<?php
declare(strict_types=1);

require_once __DIR__ . '/admin_auth.php';
require_once __DIR__ . '/db.php';
admin_require_page('reviews');
$pdo = adminDb();

function fetchProductRatings(PDO $pdo): array
{
    $sql = "
      SELECT p.id, p.name, p.image,
             COALESCE(ROUND(AVG(CASE WHEN r.status = 'approved' THEN r.rating END), 1), 0) AS avg_rating,
             COUNT(CASE WHEN r.status = 'approved' THEN 1 END) AS review_count
      FROM products p
      LEFT JOIN product_reviews r ON r.product_id = p.id
      WHERE p.is_active = 1
      GROUP BY p.id, p.name, p.image
      ORDER BY p.name
    ";

    $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as &$row) {
        $row['id'] = (int) $row['id'];
        $row['avg_rating'] = (float) $row['avg_rating'];
        $row['review_count'] = (int) $row['review_count'];
    }
    unset($row);

    return $rows;
}

function fetchCustomerRatings(PDO $pdo): array
{
    $sql = "
      SELECT r.id,
             r.rating,
             r.title,
             r.body,
             r.status,
             r.created_at,
             p.name AS product_name,
             u.first_name,
             u.last_name,
             u.email
      FROM product_reviews r
      INNER JOIN products p ON p.id = r.product_id
      INNER JOIN users u ON u.id = r.user_id
      WHERE r.status = 'approved'
      ORDER BY r.created_at DESC, r.id DESC
    ";

    $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as &$row) {
        $row['id'] = (int) $row['id'];
        $row['rating'] = (int) $row['rating'];
        $row['customer_name'] = trim((string) ($row['first_name'] ?? '') . ' ' . (string) ($row['last_name'] ?? ''));
    }
    unset($row);

    return $rows;
}

/** Average rating per product (approved reviews only). */
$productRatings = fetchProductRatings($pdo);
$customerRatings = fetchCustomerRatings($pdo);

$u = $GLOBALS['ADMIN_USER'] ?? [];
$dispName = trim((string) (($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? '')));
$rk = $GLOBALS['ADMIN_ROLE'] ?? 'super_admin';
$revRoleLabel = $rk === 'manager' ? 'Order Manager' : ($rk === 'super_admin' ? 'Super Admin' : ucfirst($rk));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Bejewelry — Review ratings (by product)</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600&family=DM+Sans:opsz,wght@9..40,400;9..40,600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../admin/reviews.css">
  <style>
  .rating-pill{display:inline-flex;align-items:center;padding:3px 9px;border-radius:999px;background:var(--blush-mid);color:var(--rose-deep);font-size:.72rem;font-weight:700}
  </style>
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

  <?php $GLOBALS['NAV_ACTIVE'] = 'reviews'; require __DIR__ . '/includes/nav_sidebar.php'; ?>

  <div class="site-content">

    <header class="topbar">
      <div class="topbar-left">
        <span class="topbar-title">Review ratings</span>
        <span class="topbar-bc">Average customer rating per item (approved feedback only)</span>
      </div>
      <div class="topbar-right">
        <button class="icon-btn" title="Notifications" onclick="location.href='notifications.php'">🔔</button>
      </div>
    </header>

    <div class="content">
      <div class="page-hdr">
        <div>
          <h2>Product averages</h2>
          <p>Based on all approved customer reviews for each product.</p>
        </div>
      </div>

      <div class="card" style="border:1px solid var(--border);border-radius:12px;overflow:hidden;background:var(--white)">
        <table class="tbl" style="width:100%;border-collapse:collapse">
          <thead>
            <tr style="background:#fdf5f7">
              <th style="text-align:left;padding:12px 16px;font-size:.72rem;text-transform:uppercase;letter-spacing:.06em;color:var(--muted)">Product</th>
              <th style="text-align:right;padding:12px 16px;font-size:.72rem;text-transform:uppercase;letter-spacing:.06em;color:var(--muted)">Avg rating</th>
              <th style="text-align:right;padding:12px 16px;font-size:.72rem;text-transform:uppercase;letter-spacing:.06em;color:var(--muted)"># Reviews</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($productRatings as $pr): ?>
            <tr style="border-top:1px solid var(--border)">
              <td style="padding:12px 16px">
                <strong><?= htmlspecialchars($pr['name'], ENT_QUOTES, 'UTF-8') ?></strong>
              </td>
              <td style="padding:12px 16px;text-align:right;font-weight:600">
                <?= $pr['review_count'] > 0 ? number_format($pr['avg_rating'], 1) . ' / 5' : '—' ?>
              </td>
              <td style="padding:12px 16px;text-align:right;color:var(--muted)"><?= (int) $pr['review_count'] ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($productRatings)): ?>
            <tr><td colspan="3" style="padding:24px;text-align:center;color:var(--muted)">No products found.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <div class="page-hdr" style="margin-top:28px">
        <div>
          <h2>Customer ratings</h2>
          <p>Approved customer reviews with customer name, product, and feedback.</p>
        </div>
      </div>



      <div class="card" style="border:1px solid var(--border);border-radius:12px;overflow:hidden;background:var(--white)">
        <table class="tbl" style="width:100%;border-collapse:collapse">
          <thead>
            <tr style="background:#fdf5f7">
              <th style="text-align:left;padding:12px 16px;font-size:.72rem;text-transform:uppercase;letter-spacing:.06em;color:var(--muted)">Customer</th>
              <th style="text-align:left;padding:12px 16px;font-size:.72rem;text-transform:uppercase;letter-spacing:.06em;color:var(--muted)">Product</th>
              <th style="text-align:right;padding:12px 16px;font-size:.72rem;text-transform:uppercase;letter-spacing:.06em;color:var(--muted)">Rating</th>
              <th style="text-align:left;padding:12px 16px;font-size:.72rem;text-transform:uppercase;letter-spacing:.06em;color:var(--muted)">Title</th>
              <th style="text-align:left;padding:12px 16px;font-size:.72rem;text-transform:uppercase;letter-spacing:.06em;color:var(--muted)">Review</th>
              <th style="text-align:right;padding:12px 16px;font-size:.72rem;text-transform:uppercase;letter-spacing:.06em;color:var(--muted)">Date</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($customerRatings as $cr): ?>
            <tr style="border-top:1px solid var(--border)">
              <td style="padding:12px 16px">
                <strong><?= htmlspecialchars($cr['customer_name'] !== '' ? $cr['customer_name'] : ($cr['email'] ?? 'Customer'), ENT_QUOTES, 'UTF-8') ?></strong>
              </td>
              <td style="padding:12px 16px"><?= htmlspecialchars($cr['product_name'], ENT_QUOTES, 'UTF-8') ?></td>
              <td style="padding:12px 16px;text-align:right;font-weight:600"><span class="rating-pill"><?= number_format((float) $cr['rating'], 0) ?> / 5</span></td>
              <td style="padding:12px 16px"><?= htmlspecialchars((string) ($cr['title'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
              <td style="padding:12px 16px;color:var(--muted)"><?= htmlspecialchars(mb_strimwidth((string) ($cr['body'] ?? ''), 0, 80, '…'), ENT_QUOTES, 'UTF-8') ?></td>
              <td style="padding:12px 16px;text-align:right;color:var(--muted)"><?= htmlspecialchars((string) $cr['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($customerRatings)): ?>
            <tr><td colspan="6" style="padding:24px;text-align:center;color:var(--muted)">No approved customer reviews yet.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script src="../admin/confirm_modal.js?v=1"></script>
<script src="whoami.js?v=1"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  const data = <?= json_encode(['user' => ['name' => $dispName !== '' ? $dispName : 'Admin', 'role' => $revRoleLabel]], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
  const name = data.user.name || 'Admin';
  const av = name.split(/\s+/).map(w => w[0]).join('').slice(0, 2).toUpperCase();
  const a = document.getElementById('sbAvatar');
  const u = document.getElementById('sbUsername');
  const r = document.getElementById('sbUserRole');
  if (a) a.textContent = av;
  if (u) u.textContent = name;
  if (r) r.textContent = data.user.role || '—';
});
function handleLogout() {
  if (typeof window.adminConfirm === 'function') {
    window.adminConfirm('Log out of Bejewelry Admin?', function () { window.location.href = '../logout.php'; }, { okText: 'Log out' });
    return;
  }
  window.location.href = '../logout.php';
}


</script>
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
