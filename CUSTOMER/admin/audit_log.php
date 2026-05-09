<?php
declare(strict_types=1);

require_once __DIR__ . '/admin_auth.php';
require_once __DIR__ . '/db.php';
admin_require_page('audit_log');

$pdo = adminDb();
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 40;
$offset = ($page - 1) * $perPage;

$total = (int) $pdo->query('SELECT COUNT(*) FROM audit_log')->fetchColumn();
$totalPages = max(1, (int) ceil($total / $perPage));

$stmt = $pdo->prepare(
    'SELECT a.id, a.user_id, a.email, a.action, a.ip, a.user_agent, a.created_at,
            CONCAT(COALESCE(u.first_name,\'\'), \' \', COALESCE(u.last_name,\'\')) AS user_name
     FROM audit_log a
     LEFT JOIN users u ON u.id = a.user_id
     ORDER BY a.created_at DESC
     LIMIT ? OFFSET ?'
);
$stmt->bindValue(1, $perPage, PDO::PARAM_INT);
$stmt->bindValue(2, $offset, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$GLOBALS['NAV_ACTIVE'] = 'audit_log';
$roleRaw = (string) ($GLOBALS['ADMIN_ROLE'] ?? 'super_admin');
$roleLabelMap = [
    'super_admin' => 'Super Admin',
    'manager' => 'Order Manager',
    'inventory' => 'Inventory Manager',
];
$roleLabel = $roleLabelMap[$roleRaw] ?? 'Super Admin';
$dispName = trim((string) (($GLOBALS['ADMIN_USER']['first_name'] ?? '') . ' ' . ($GLOBALS['ADMIN_USER']['last_name'] ?? '')));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Bejewelry — Audit log</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,500;0,600;0,700;1,400&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600;9..40,700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="dashboard.css">
  <style>
    .audit-wrap {
      background: var(--white);
      border: 1px solid var(--border);
      border-radius: var(--r-lg);
      overflow: auto;
    }
    .audit-table {
      width: 100%;
      border-collapse: collapse;
      font-size: .82rem;
    }
    .audit-table th,
    .audit-table td {
      padding: 11px 14px;
      text-align: left;
      border-bottom: 1px solid var(--border);
    }
    .audit-table th {
      font-size: .64rem;
      letter-spacing: .12em;
      text-transform: uppercase;
      color: var(--muted-light);
      background: var(--blush);
    }
    .audit-table tr:hover td {
      background: #fff9fb;
    }
    .audit-pill {
      display: inline-flex;
      align-items: center;
      border-radius: 999px;
      padding: 3px 9px;
      font-size: .62rem;
      letter-spacing: .06em;
      font-weight: 700;
      text-transform: uppercase;
    }
    .audit-pill-in {
      background: #e8f5e9;
      color: #1b5e20;
    }
    .audit-pill-out {
      background: #ffebee;
      color: #b71c1c;
    }
    .audit-pagination {
      margin-top: 12px;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .audit-pagination a {
      color: var(--rose-deep);
      font-size: .8rem;
      text-decoration: none;
      font-weight: 600;
    }
    .audit-pagination a:hover {
      text-decoration: underline;
    }
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

  <?php require __DIR__ . '/includes/nav_sidebar.php'; ?>

  <div class="site-content">
    <header class="topbar">
      <div class="topbar-left">
        <span class="topbar-title">Audit log</span>
        <span class="topbar-bc">Bejewelry Admin › Audit log</span>
      </div>
      <div class="topbar-right">
        <button class="icon-btn" title="Refresh" onclick="location.reload()">↺</button>
      </div>
    </header>

    <div class="content">
      <div class="page-hdr">
        <div>
          <h2>Audit log</h2>
          <p>Login and logout history</p>
        </div>
      </div>

      <div class="audit-wrap">
        <table class="audit-table">
          <thead>
            <tr>
              <th>When</th>
              <th>Action</th>
              <th>Email</th>
              <th>User</th>
              <th>IP</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($rows)): ?>
            <tr>
              <td colspan="5" style="text-align:center;color:var(--muted);padding:28px;">
                No entries yet. Run api/migration_audit_log.sql if the table is missing.
              </td>
            </tr>
            <?php else: ?>
              <?php foreach ($rows as $row): ?>
              <?php $actionKey = (string) ($row['action'] ?? ''); ?>
              <tr>
                <td><?= htmlspecialchars((string) $row['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                <td>
                  <?php if (function_exists('bejewelry_audit_action_label')): ?>
                    <?php $actionLabel = bejewelry_audit_action_label($actionKey); ?>
                  <?php else: ?>
                    <?php $actionLabel = ucwords(str_replace(['_', '-'], ' ', $actionKey)); ?>
                  <?php endif; ?>
                  <?php
                    $pillClass = in_array($actionKey, ['logout', 'delete_product'], true) ? 'audit-pill-out' : 'audit-pill-in';
                  ?>
                  <span class="audit-pill <?= $pillClass ?>"><?= htmlspecialchars($actionLabel ?: $actionKey, ENT_QUOTES, 'UTF-8') ?></span>
                </td>
                <td><?= htmlspecialchars((string) ($row['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars(trim((string) ($row['user_name'] ?? '')) ?: '—', ENT_QUOTES, 'UTF-8') ?></td>
                <td style="font-size:.78rem;color:var(--muted-light);"><?= htmlspecialchars((string) ($row['ip'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
              </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <?php if ($totalPages > 1): ?>
      <div class="audit-pagination">
        <?php if ($page > 1): ?><a href="?page=<?= $page - 1 ?>">← Newer</a><?php endif; ?>
        <span style="color:var(--muted-light);font-size:.8rem;">Page <?= (int) $page ?> / <?= (int) $totalPages ?></span>
        <?php if ($page < $totalPages): ?><a href="?page=<?= $page + 1 ?>">Older →</a><?php endif; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<script src="whoami.js?v=1"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  const name = <?= json_encode($dispName !== '' ? $dispName : 'Admin', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;
  const role = <?= json_encode($roleLabel, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>;
  const av = name.split(/\s+/).map(w => w[0]).join('').slice(0, 2).toUpperCase();

  const a = document.getElementById('sbAvatar');
  const un = document.getElementById('sbUsername');
  const ur = document.getElementById('sbUserRole');
  if (a) a.textContent = av || 'AD';
  if (un) un.textContent = name;
  if (ur) ur.textContent = role;

  var searchInput = document.getElementById('globalSearch');
  if (searchInput) {
    var timer;
    searchInput.addEventListener('input', function(e) {
      clearTimeout(timer);
      var q = (e.target.value || '').trim();
      if (q.length < 2) return;
      timer = setTimeout(function() {
        window.location.href = 'search.php?q=' + encodeURIComponent(q);
      }, 450);
    });
  }
});

function handleLogout() {
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
