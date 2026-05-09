<?php
require_once __DIR__ . '/admin_auth.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/settings/_settings_db.php';
admin_require_page('notifications');

$pdo = adminDb();
$sPdo = settingsPdo();

$defaults = [
  'admin_email' => '',
  'new_order' => true,
  'low_stock' => true,
  'new_review' => true,
  'customer_reg' => false,
  'daily_summary' => false,
];
$prefs = settingsGetJson($sPdo, 'notifications', $defaults);

$alerts = [];

function addAlert(array &$alerts, string $type, string $title, string $message, string $href, ?string $createdAt = null): void {
  $alerts[] = [
    'type' => $type,
    'title' => $title,
    'message' => $message,
    'href' => $href,
    'created_at' => $createdAt,
  ];
}

if (!empty($prefs['new_order'])) {
  $processing = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'processing'")->fetchColumn();
  if ($processing > 0) {
    addAlert($alerts, 'order', 'New Orders', $processing . ' order(s) waiting for courier assignment.', 'orders.php?status=processing');
  }
  $recent = $pdo->query("SELECT id, total, created_at FROM orders ORDER BY created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC) ?: [];
  foreach ($recent as $o) {
    addAlert($alerts, 'order', 'Order placed', 'Order #' . $o['id'] . ' · ₱' . number_format((float)$o['total'], 0), 'orders.php?id=' . urlencode($o['id']), $o['created_at'] ?? null);
  }
}

if (!empty($prefs['low_stock'])) {
  $lowStock = $pdo->query("SELECT id, name, stock FROM products WHERE stock > 0 AND stock <= 5 ORDER BY stock ASC, id DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC) ?: [];
  if (count($lowStock) > 0) {
    addAlert($alerts, 'inventory', 'Low stock', count($lowStock) . ' product(s) are low on stock.', 'inventory.php?filter=low');
    foreach ($lowStock as $p) {
      addAlert($alerts, 'inventory', 'Low stock item', $p['name'] . ' — ' . (int)$p['stock'] . ' left', 'inventory.php?search=' . urlencode($p['name']));
    }
  }
}

if (!empty($prefs['new_review'])) {
  try {
    $pendingReviews = (int)$pdo->query("SELECT COUNT(*) FROM product_reviews WHERE status = 'pending'")->fetchColumn();
    if ($pendingReviews > 0) {
      addAlert($alerts, 'review', 'Reviews pending', $pendingReviews . ' review(s) awaiting moderation.', 'reviews.php?filter=pending');
    }
  } catch (Throwable $e) {}
}

if (!empty($prefs['customer_reg'])) {
  $newCust = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'customer' AND DATE(created_at) = CURDATE()")->fetchColumn();
  if ($newCust > 0) {
    addAlert($alerts, 'customer', 'New customers', $newCust . ' new registration(s) today.', 'customers.php?filter=new');
  }
}

usort($alerts, function($a, $b) {
  $ta = !empty($a['created_at']) ? strtotime($a['created_at']) : 0;
  $tb = !empty($b['created_at']) ? strtotime($b['created_at']) : 0;
  return $tb <=> $ta;
});

function esc($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function fmtDate(?string $dt): string {
  if (!$dt) return '';
  $t = strtotime($dt);
  if (!$t) return '';
  return date('M j, Y g:i A', $t);
}

$count = count($alerts);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Bejewelry Admin — Notifications</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600;9..40,700&display=swap" rel="stylesheet">
  <style>
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
    body{font-family:'DM Sans',system-ui,sans-serif;background:#FEF1F3;color:#241418;min-height:100vh}
    :root{
      --fd:'Playfair Display',Georgia,serif;
      --blush:#FEF1F3;--blush-mid:#FAE3E8;--blush-deep:#F4C8D2;
      --rose:#D96070;--rose-deep:#B03050;--rose-muted:#CC8898;
      --white:#fff;--dark:#241418;--muted:#7A5E68;--muted-light:#AC8898;
      --border:#ECDCE0;--border-mid:#DEC8D0;
      --r-md:10px;--r-lg:16px;--r-pill:999px;
      --sh-xs:0 1px 4px rgba(160,40,60,.07);
      --tr:.18s ease;
    }
    a{text-decoration:none;color:inherit}
    .wrap{max-width:1080px;margin:0 auto;padding:28px 18px}
    .top{display:flex;align-items:flex-end;justify-content:space-between;gap:12px;margin-bottom:16px}
    .h{font-family:var(--fd);font-size:1.6rem}
    .sub{font-size:.78rem;color:var(--muted-light);margin-top:4px}
    .btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;padding:9px 16px;border-radius:var(--r-pill);font-size:.72rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase;border:1.5px solid var(--border-mid);background:transparent;color:var(--muted);cursor:pointer;transition:all var(--tr)}
    .btn:hover{background:var(--blush-mid);color:var(--rose-deep);border-color:var(--rose-muted)}
    .card{background:var(--white);border:1px solid var(--border);border-radius:var(--r-lg);box-shadow:var(--sh-xs);overflow:hidden}
    .item{display:flex;gap:14px;align-items:flex-start;padding:14px 16px;border-bottom:1px solid var(--border);transition:background var(--tr)}
    .item:hover{background:var(--blush)}
    .item:last-child{border-bottom:none}
    .ic{width:38px;height:38px;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;color:var(--white);font-weight:800}
    .ic.order{background:linear-gradient(135deg,var(--rose),var(--rose-deep))}
    .ic.inventory{background:linear-gradient(135deg,#B88830,#8C6800)}
    .ic.review{background:linear-gradient(135deg,#1455A0,#2035A0)}
    .ic.customer{background:linear-gradient(135deg,#228855,#156038)}
    .ttl{font-weight:700;font-size:.9rem;color:var(--dark)}
    .msg{font-size:.82rem;color:var(--muted);margin-top:2px;line-height:1.55}
    .meta{font-size:.7rem;color:var(--muted-light);margin-top:6px}
    .empty{padding:26px;text-align:center;color:var(--muted-light)}
    .pill{display:inline-flex;align-items:center;gap:6px;padding:4px 10px;border-radius:var(--r-pill);font-size:.62rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase;background:var(--blush-mid);color:var(--rose-deep);border:1px solid var(--border)}
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
  <div class="wrap">
    <div class="top">
      <div>
        <div class="pill">🔔 Alerts</div>
        <div class="h">Notifications</div>
        <div class="sub"><?= $count ? esc($count) . ' alert(s) right now' : 'No alerts right now' ?></div>
      </div>
      <div style="display:flex;gap:8px">
        <a class="btn" href="/BEJEWELRY/CUSTOMER/InventoryManager/dashboard.php">← Back</a>
        <a class="btn" href="settings.php#notifications">Notification Settings</a>
      </div>
    </div>

    <div class="card">
      <?php if (!$alerts): ?>
        <div class="empty">You’re all caught up.</div>
      <?php else: ?>
        <?php foreach ($alerts as $a): ?>
          <a class="item" href="<?= esc($a['href']) ?>">
            <div class="ic <?= esc($a['type']) ?>">
              <?= $a['type'] === 'order' ? '📦' : ($a['type'] === 'inventory' ? '📋' : ($a['type'] === 'review' ? '⭐' : '👤')) ?>
            </div>
            <div style="flex:1">
              <div class="ttl"><?= esc($a['title']) ?></div>
              <div class="msg"><?= esc($a['message']) ?></div>
              <?php if (!empty($a['created_at'])): ?>
                <div class="meta"><?= esc(fmtDate($a['created_at'])) ?></div>
              <?php endif; ?>
            </div>
          </a>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
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
