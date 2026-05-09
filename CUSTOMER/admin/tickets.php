<?php
declare(strict_types=1);

require_once __DIR__ . '/admin_auth.php';
require_once __DIR__ . '/db.php';
admin_require_page('tickets');
$pdo = adminDb();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_ticket') {
  csrf_validate();
  $id = (int) ($_POST['id'] ?? 0);
  $status = trim((string) ($_POST['status'] ?? ''));
  $note = trim((string) ($_POST['admin_note'] ?? ''));
  if ($id > 0 && in_array($status, ['open', 'resolved', 'closed'], true)) {
    // enforce role-based update permissions
    $role = $GLOBALS['ADMIN_ROLE'] ?? 'super_admin';
    $check = $pdo->prepare('SELECT scope FROM support_tickets WHERE id = ? LIMIT 1');
    $check->execute([$id]);
    $row = $check->fetch(PDO::FETCH_ASSOC) ?: null;
    $allowed = false;
    if ($row) {
      $allowed = (($row['scope'] ?? '') === 'manager');
    }
    if ($allowed) {
      $pdo->prepare('UPDATE support_tickets SET status = ?, admin_note = ? WHERE id = ?')->execute([
        $status,
        $note !== '' ? $note : null,
        $id,
      ]);
    }
  }
  header('Location: tickets.php');
  exit;
}

// Managers see manager-scoped tickets
$openCount = (int) $pdo->query("SELECT COUNT(*) FROM support_tickets WHERE status = 'open' AND scope = 'manager'")->fetchColumn();
$totalCount = (int) $pdo->query("SELECT COUNT(*) FROM support_tickets WHERE scope = 'manager'")->fetchColumn();

$stmt = $pdo->prepare(
  'SELECT t.id, t.user_id, t.order_id, t.type, t.description, t.status, t.admin_note, t.created_at,
      u.first_name, u.last_name, u.email
   FROM support_tickets t
   JOIN users u ON u.id = t.user_id
   WHERE t.scope = ?
   ORDER BY t.created_at DESC'
);
$stmt->execute(['manager']);
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

$typeLabels = [
    'wrong_item' => 'Wrong item',
    'damaged' => 'Damaged',
    'not_delivered' => 'Not delivered',
    'delayed' => 'Delayed',
    'missing_item' => 'Missing item',
  'other' => 'Other',
];

$typeActions = [
  'wrong_item' => 'Re-deliver the correct item and schedule return pickup for the incorrect item.',
  'damaged' => 'Validate damage proof, then process refund or replacement based on customer preference.',
  'not_delivered' => 'Coordinate with the carrier immediately. If not recoverable, re-ship or issue full refund.',
  'delayed' => 'Check carrier status, provide updated ETA, and prioritize follow-up until delivered.',
  'missing_item' => 'Ship the missing item immediately or issue a partial refund for the missing item.',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Bejewelry Admin — Support Tickets</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,600;1,400&family=DM+Sans:opsz,wght@9..40,400;9..40,600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../admin/reviews.css">
  <script>
    (function () {
      try {
        history.scrollRestoration = 'manual';
      } catch (e) {}
      try {
        if (sessionStorage.getItem('tickets_scroll_y') !== null) {
          document.documentElement.style.opacity = '0';
        }
      } catch (e) {}
    })();
  </script>
</head>
<body>

<div class="loading-bar" id="loadingBar"></div>

<div class="site-wrapper">

  <?php $GLOBALS['NAV_ACTIVE'] = 'tickets'; require __DIR__ . '/includes/nav_sidebar.php'; ?>

  <div class="site-content">
    <header class="topbar">
      <div class="topbar-left">
        <span class="topbar-title">Support tickets</span>
        <span class="topbar-bc">Bejewelry Admin › Tickets</span>
      </div>
      <div class="topbar-right">
      </div>
    </header>

    <div class="content">
      <div class="page-hdr">
        <div>
          <h2>Tickets &amp; customer issues</h2>
          <p id="ticketsSub"><?= (int) $totalCount ?> total · <?= (int) $openCount ?> open</p>
        </div>
      </div>

      <div class="stats-row" style="margin-bottom:16px">
        <div class="stat-card">
          <span class="stat-icon">🎫</span>
          <span class="stat-label">Open</span>
          <div class="stat-value"><?= (int) $openCount ?></div>
        </div>
        <div class="stat-card">
          <span class="stat-icon">📋</span>
          <span class="stat-label">All</span>
          <div class="stat-value"><?= (int) $totalCount ?></div>
        </div>
      </div>

      <!-- eslint-disable-next-line -->
      <?php if (empty($tickets)): ?>
        <p style="padding:24px;color:var(--muted-light);font-size:.9rem">No support tickets yet.</p>
      <?php else: ?>
        <div class="table-wrap" style="background:var(--white);border-radius:var(--r-lg);border:1px solid var(--border);overflow:auto">
          <table class="data-table" style="width:100%;border-collapse:collapse;font-size:.82rem">
            <thead>
              <tr style="text-align:left;border-bottom:1px solid var(--border)">
                <th style="padding:12px 14px">ID</th>
                <th style="padding:12px 14px">Order</th>
                <th style="padding:12px 14px">Customer</th>
                <th style="padding:12px 14px">Type</th>
                <th style="padding:12px 14px">Issue</th>
                <th style="padding:12px 14px">Recommended Handling</th>
                <th style="padding:12px 14px">Status</th>
                <th style="padding:12px 14px">Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($tickets as $t): ?>
                <?php
                $tid = (int) $t['id'];
                $cn = trim(($t['first_name'] ?? '') . ' ' . ($t['last_name'] ?? ''));
                $orderId = (string) ($t['order_id'] ?? '');
                $tl = $typeLabels[$t['type']] ?? $t['type'];
                $ta = $typeActions[$t['type']] ?? 'Review this case manually and decide the best resolution for the customer.';
                $descRaw = (string) ($t['description'] ?? '');
                $descShow = strlen($descRaw) > 200 ? substr($descRaw, 0, 197) . '...' : $descRaw;
                ?>
                <tr style="border-bottom:1px solid var(--border)">
                  <td style="padding:12px 14px">#<?= $tid ?></td>
                  <td style="padding:12px 14px"><?= htmlspecialchars($orderId, ENT_QUOTES, 'UTF-8') ?></td>
                  <td style="padding:12px 14px"><?= htmlspecialchars($cn !== '' ? $cn : ($t['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                  <td style="padding:12px 14px">
                    <?= htmlspecialchars($tl, ENT_QUOTES, 'UTF-8') ?>
                  </td>
                  <td style="padding:12px 14px;max-width:280px"><?= nl2br(htmlspecialchars($descShow, ENT_QUOTES, 'UTF-8')) ?></td>
                  <td style="padding:12px 14px;max-width:360px;line-height:1.45"><?= htmlspecialchars($ta, ENT_QUOTES, 'UTF-8') ?></td>
                  <td style="padding:12px 14px">
                    <span class="status-chip"><?= htmlspecialchars($t['status'], ENT_QUOTES, 'UTF-8') ?></span>
                  </td>
                  <td style="padding:12px 14px">
                    <form method="post" style="display:flex;flex-direction:column;gap:6px;align-items:flex-start">
                                            <?php echo csrf_token_field(); ?>
                      <input type="hidden" name="action" value="update_ticket"/>
                      <input type="hidden" name="id" value="<?= (int) $tid ?>"/>
                      <select name="status" class="fselect" style="font-size:.72rem;padding:4px 8px">
                        <option value="open" <?= $t['status'] === 'open' ? 'selected' : '' ?>>Open</option>
                        <option value="resolved" <?= $t['status'] === 'resolved' ? 'selected' : '' ?>>Resolved</option>
                        <option value="closed" <?= $t['status'] === 'closed' ? 'selected' : '' ?>>Closed</option>
                      </select>
                      <input type="text" name="admin_note" placeholder="Admin note" value="<?= htmlspecialchars((string) ($t['admin_note'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" style="width:100%;max-width:200px;font-size:.72rem;padding:4px 8px;border:1px solid var(--border);border-radius:8px"/>
                      <button type="submit" class="btn btn-primary btn-sm" style="padding:4px 10px;font-size:.65rem">Save</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<script src="../admin/confirm_modal.js?v=1"></script>
<script src="whoami.js?v=1"></script>
<script>
  const scrollKey = 'tickets_scroll_y';

  function saveScrollPosition() {
    sessionStorage.setItem(scrollKey, String(window.scrollY || 0));
  }

  function restoreScrollPositionOnce() {
    const raw = sessionStorage.getItem(scrollKey);
    if (raw === null) {
      document.documentElement.style.opacity = '1';
      return;
    }
    const savedY = Number(raw);
    sessionStorage.removeItem(scrollKey);
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

  function handleLogout() {
    if (typeof window.adminConfirm === 'function') {
      window.adminConfirm('Log out of Bejewelry Admin?', function () { window.location.href = '../logout.php'; }, { okText: 'Log out' });
      return;
    }
    window.location.href = '../logout.php';
  }

  (function() {
    var el = document.getElementById('badgeTickets');
    if (el) el.textContent = '<?= (int) $openCount ?>';
  })();

  document.querySelectorAll('form').forEach(function (form) {
    form.addEventListener('submit', function () {
      saveScrollPosition();
    });
  });

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
      } catch (e) {}
    });
  });

  window.addEventListener('load', function () {
    restoreScrollPositionOnce();
  });
</script>
</body>
</html>
