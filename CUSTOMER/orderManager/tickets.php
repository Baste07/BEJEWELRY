<?php
declare(strict_types=1);

require_once __DIR__ . '/admin_auth.php';
require_once __DIR__ . '/db.php';
admin_require_page('tickets');
$pdo = adminDb();

$flash = trim((string) ($_GET['flash'] ?? ''));
$flashMessage = '';
if ($flash === 'approved') {
  $flashMessage = 'Ticket approved successfully. Refund log was updated when applicable.';
} elseif ($flash === 'rejected') {
  $flashMessage = 'Ticket rejected successfully.';
} elseif ($flash === 'not_found') {
  $flashMessage = 'Ticket not found or not allowed for this page.';
} elseif ($flash === 'error') {
  $flashMessage = 'Could not update ticket. Please try again.';
}

$filterStatus = strtolower(trim((string) ($_GET['status'] ?? 'all')));
$filterOrder = trim((string) ($_GET['order'] ?? ''));
$filterName = trim((string) ($_GET['name'] ?? ''));
$filterCategory = strtolower(trim((string) ($_GET['category'] ?? 'all')));
$filterStatusOptions = [
  'all' => 'All statuses',
  'open' => 'Pending',
  'resolved' => 'Approved',
  'closed' => 'Rejected',
];
$filterCategoryOptions = [
  'all' => 'All categories',
  'wrong_item' => 'Wrong item received',
  'damaged' => 'Item arrived damaged',
  'not_delivered' => 'Order not delivered',
  'missing_item' => 'Missing item in order',
  'other' => 'Other',
  'delayed' => 'Shipment delayed',
];

function om_ticket_filter_href(array $filters): string
{
  $params = [];
  if (($filters['status'] ?? 'all') !== 'all') {
    $params['status'] = (string) $filters['status'];
  }
  if (($filters['order'] ?? '') !== '') {
    $params['order'] = (string) $filters['order'];
  }
  if (($filters['name'] ?? '') !== '') {
    $params['name'] = (string) $filters['name'];
  }
  if (($filters['category'] ?? 'all') !== 'all') {
    $params['category'] = (string) $filters['category'];
  }

  return 'tickets.php' . ($params ? '?' . http_build_query($params) : '');
}

function om_ticket_type_label(string $type): string
{
  return [
    'wrong_item' => 'Wrong item received',
    'damaged' => 'Item arrived damaged',
    'not_delivered' => 'Order not delivered',
    'missing_item' => 'Missing item in order',
    'other' => 'Other',
    'delayed' => 'Shipment delayed',
  ][$type] ?? $type;
}

function om_ticket_status_label(string $status): string
{
  return [
    'open' => 'Pending',
    'resolved' => 'Approved',
    'closed' => 'Rejected',
  ][$status] ?? ucfirst($status);
}

function om_ticket_resolution_label(?string $type, ?string $resolution): string
{
  $type = (string) $type;
  $resolution = (string) $resolution;
  if ($resolution === '') {
    return '—';
  }
  if ($type === 'not_delivered') {
    return $resolution === 'refund' ? 'Refund' : 'Re-deliver';
  }
  return $resolution === 'refund' ? 'Refund' : 'Reorder';
}

function om_ticket_photo_url(?string $path): ?string
{
  return bejewelry_support_ticket_photo_url($path);
}

function om_ticket_customer_name(array $ticket): string
{
  return trim((string) (($ticket['first_name'] ?? '') . ' ' . ($ticket['last_name'] ?? '')));
}

function om_ticket_review(array $ticket, string $decision, string $reason = ''): void
{
  $manager = current_user();
  $managerName = trim((string) (($manager['first_name'] ?? '') . ' ' . ($manager['last_name'] ?? '')));
  if ($managerName === '') {
    $managerName = (string) ($manager['email'] ?? 'Order Manager');
  }

  $pdo = adminDb();
  $pdo->beginTransaction();

  try {
    if ($decision === 'approve') {
      $pdo->prepare('UPDATE support_tickets SET status = ?, reviewed_by = ?, reviewed_at = NOW(), rejection_reason = NULL WHERE id = ?')->execute([
        'resolved',
        (int) ($manager['id'] ?? 0),
        (int) $ticket['id'],
      ]);

      if (($ticket['resolution'] ?? '') === 'refund') {
        $orderStmt = $pdo->prepare('SELECT total, ship_name, user_id FROM orders WHERE id = ? LIMIT 1');
        $orderStmt->execute([(string) $ticket['order_id']]);
        $order = $orderStmt->fetch(PDO::FETCH_ASSOC) ?: [];
        $refundAmount = (float) ($order['total'] ?? 0);
        $customerName = trim((string) ($order['ship_name'] ?? om_ticket_customer_name($ticket)));
        $pdo->prepare('INSERT IGNORE INTO refund_logs (ticket_id, user_id, customer_name, order_id, refund_amount, ticket_category, approved_by, approved_by_name) VALUES (?, ?, ?, ?, ?, ?, ?, ?)')
          ->execute([
            (int) $ticket['id'],
            (int) ($ticket['user_id'] ?? 0),
            $customerName !== '' ? $customerName : 'Customer',
            (string) $ticket['order_id'],
            $refundAmount,
            (string) ($ticket['type'] ?? ''),
            (int) ($manager['id'] ?? 0),
            $managerName,
          ]);
      } elseif (in_array((string) ($ticket['resolution'] ?? ''), ['reorder', 'redeliver'], true)) {
        $pdo->prepare("UPDATE orders SET status = 'processing' WHERE id = ?")->execute([(string) $ticket['order_id']]);
      }
    } else {
      $pdo->prepare('UPDATE support_tickets SET status = ?, reviewed_by = ?, reviewed_at = NOW(), rejection_reason = ? WHERE id = ?')->execute([
        'closed',
        (int) ($manager['id'] ?? 0),
        $reason !== '' ? $reason : null,
        (int) $ticket['id'],
      ]);
    }

    $pdo->commit();
  } catch (Throwable $e) {
    $pdo->rollBack();
    throw $e;
  }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_ticket') {
  $redirectParams = [];
  if ($filterStatus !== '' && $filterStatus !== 'all') $redirectParams['status'] = $filterStatus;
  if ($filterOrder !== '') $redirectParams['order'] = $filterOrder;
  if ($filterName !== '') $redirectParams['name'] = $filterName;
  if ($filterCategory !== '' && $filterCategory !== 'all') $redirectParams['category'] = $filterCategory;

  try {
    csrf_validate();
    $id = (int) ($_POST['id'] ?? 0);
    $decision = trim((string) ($_POST['decision'] ?? ''));
    $reason = trim((string) ($_POST['rejection_reason'] ?? ''));
    if ($id > 0 && in_array($decision, ['approve', 'reject'], true)) {
      $stmt = $pdo->prepare("SELECT t.*, u.first_name, u.last_name, u.email
        FROM support_tickets t
        JOIN users u ON u.id = t.user_id
        WHERE t.id = ?
          AND t.scope = 'manager'
          AND t.order_id NOT LIKE 'ACCDEL-%'
          AND t.order_id NOT LIKE 'UNLOCK-%'
        LIMIT 1");
      $stmt->execute([$id]);
      $ticket = $stmt->fetch(PDO::FETCH_ASSOC);
      if (!$ticket) {
        $redirectParams['flash'] = 'not_found';
      } else {
        om_ticket_review($ticket, $decision, $reason);
        $redirectParams['flash'] = $decision === 'approve' ? 'approved' : 'rejected';
      }
    } else {
      $redirectParams['flash'] = 'error';
    }
  } catch (Throwable $e) {
    $redirectParams['flash'] = 'error';
  }

  header('Location: tickets.php' . ($redirectParams ? '?' . http_build_query($redirectParams) : ''));
  exit;
}

$openCount = (int) $pdo->query("SELECT COUNT(*)
  FROM support_tickets
  WHERE scope = 'manager'
    AND order_id NOT LIKE 'ACCDEL-%'
    AND order_id NOT LIKE 'UNLOCK-%'
    AND status = 'open'")->fetchColumn();
$countsRow = $pdo->query("SELECT
    COUNT(*) AS total_count,
    SUM(status = 'open') AS pending_count,
    SUM(status = 'resolved') AS approved_count,
    SUM(status = 'closed') AS rejected_count
  FROM support_tickets
  WHERE scope = 'manager'
    AND order_id NOT LIKE 'ACCDEL-%'
    AND order_id NOT LIKE 'UNLOCK-%'")->fetch(PDO::FETCH_ASSOC) ?: [];
$totalCount = (int) ($countsRow['total_count'] ?? 0);
$pendingCount = (int) ($countsRow['pending_count'] ?? 0);
$approvedCount = (int) ($countsRow['approved_count'] ?? 0);
$rejectedCount = (int) ($countsRow['rejected_count'] ?? 0);

$where = ["t.scope = 'manager'", "t.order_id NOT LIKE 'ACCDEL-%'", "t.order_id NOT LIKE 'UNLOCK-%'"];
$params = [];
if (in_array($filterStatus, ['open', 'resolved', 'closed'], true)) {
  $where[] = 't.status = ?';
  $params[] = $filterStatus;
}
if ($filterOrder !== '') {
  $where[] = 't.order_id LIKE ?';
  $params[] = '%' . $filterOrder . '%';
}
if ($filterName !== '') {
  $where[] = "(u.first_name LIKE ? OR u.last_name LIKE ? OR CONCAT(u.first_name, ' ', u.last_name) LIKE ? OR u.email LIKE ?)";
  $nameLike = '%' . $filterName . '%';
  $params[] = $nameLike;
  $params[] = $nameLike;
  $params[] = $nameLike;
  $params[] = $nameLike;
}
if (in_array($filterCategory, array_keys($filterCategoryOptions), true) && $filterCategory !== 'all') {
  $where[] = 't.type = ?';
  $params[] = $filterCategory;
}

$stmt = $pdo->prepare(
  'SELECT t.id, t.user_id, t.order_id, t.type, t.category, t.photo_path, t.resolution, t.description, t.status, t.admin_note, t.rejection_reason, t.reviewed_by, t.reviewed_at, t.created_at,
      u.first_name, u.last_name, u.email,
      reviewer.first_name AS reviewer_first_name, reviewer.last_name AS reviewer_last_name
     FROM support_tickets t
     JOIN users u ON u.id = t.user_id
   LEFT JOIN users reviewer ON reviewer.id = t.reviewed_by
     WHERE ' . implode(' AND ', $where) . '
     ORDER BY t.created_at DESC'
);
$stmt->execute($params);
$tickets = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
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
        <button class="icon-btn" title="Notifications" onclick="location.href='notifications.php'">🔔</button>
      </div>
    </header>

    <div class="content">
      <?php if ($flashMessage !== ''): ?>
      <div class="alert" style="margin-bottom:12px;background:#eef9f1;border-color:#b8e1c5;color:#1d6b3d;">
        <span class="alert-icon">✓</span>
        <span><?= htmlspecialchars($flashMessage, ENT_QUOTES, 'UTF-8') ?></span>
      </div>
      <?php endif; ?>

      <div class="page-hdr">
        <div>
          <h2>Tickets &amp; customer issues</h2>
          <p id="ticketsSub"><?= (int) count($tickets) ?> showing · <?= (int) $totalCount ?> total</p>
        </div>
      </div>

      <div class="stats-row ticket-stats-row" style="margin-bottom:16px">
        <a class="stat-card ticket-stat-card<?= $filterStatus === 'open' ? ' is-active' : '' ?>" href="<?= htmlspecialchars(om_ticket_filter_href(['status' => 'open', 'order' => $filterOrder, 'name' => $filterName, 'category' => $filterCategory]), ENT_QUOTES, 'UTF-8') ?>">
          <span class="stat-icon">🎫</span>
          <span class="stat-label">Pending</span>
          <div class="stat-value"><?= (int) $pendingCount ?></div>
          <span class="stat-trend">Open tickets waiting for review</span>
        </a>
        <a class="stat-card ticket-stat-card<?= $filterStatus === 'resolved' ? ' is-active' : '' ?>" href="<?= htmlspecialchars(om_ticket_filter_href(['status' => 'resolved', 'order' => $filterOrder, 'name' => $filterName, 'category' => $filterCategory]), ENT_QUOTES, 'UTF-8') ?>">
          <span class="stat-icon">✅</span>
          <span class="stat-label">Approved</span>
          <div class="stat-value"><?= (int) $approvedCount ?></div>
          <span class="stat-trend">Resolved requests</span>
        </a>
        <a class="stat-card ticket-stat-card<?= $filterStatus === 'closed' ? ' is-active' : '' ?>" href="<?= htmlspecialchars(om_ticket_filter_href(['status' => 'closed', 'order' => $filterOrder, 'name' => $filterName, 'category' => $filterCategory]), ENT_QUOTES, 'UTF-8') ?>">
          <span class="stat-icon">⛔</span>
          <span class="stat-label">Rejected</span>
          <div class="stat-value"><?= (int) $rejectedCount ?></div>
          <span class="stat-trend">Declined tickets</span>
        </a>
        <a class="stat-card ticket-stat-card<?= $filterStatus === 'all' ? ' is-active' : '' ?>" href="<?= htmlspecialchars(om_ticket_filter_href(['status' => 'all', 'order' => $filterOrder, 'name' => $filterName, 'category' => $filterCategory]), ENT_QUOTES, 'UTF-8') ?>">
          <span class="stat-icon">📋</span>
          <span class="stat-label">All</span>
          <div class="stat-value"><?= (int) $totalCount ?></div>
          <span class="stat-trend">Total support tickets</span>
        </a>
      </div>

      <form id="ticketFilterForm" method="get" class="ticket-filter-card">
        <div class="ticket-filter-head">
          <div>
            <div class="ticket-filter-kicker">Filter tickets</div>
            <h3 class="ticket-filter-title">Live filters for status, order, customer, and category</h3>
          </div>
          <a href="tickets.php" class="ticket-filter-clear">Clear filters</a>
        </div>
        <div class="ticket-filter-grid">
          <label class="ticket-field">
            <span>Status</span>
            <select name="status" class="fselect" data-auto-filter="true">
            <?php foreach ($filterStatusOptions as $value => $label): ?>
              <option value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>" <?= $filterStatus === $value ? 'selected' : '' ?>><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
          </select>
          </label>
          <label class="ticket-field">
            <span>Order ID</span>
            <input type="text" name="order" value="<?= htmlspecialchars($filterOrder, ENT_QUOTES, 'UTF-8') ?>" placeholder="Search order…" class="finput" data-auto-filter="true">
          </label>
          <label class="ticket-field">
            <span>Customer</span>
            <input type="text" name="name" value="<?= htmlspecialchars($filterName, ENT_QUOTES, 'UTF-8') ?>" placeholder="Search customer…" class="finput" data-auto-filter="true">
          </label>
          <label class="ticket-field">
            <span>Category</span>
            <select name="category" class="fselect" data-auto-filter="true">
            <?php foreach ($filterCategoryOptions as $value => $label): ?>
              <option value="<?= htmlspecialchars($value, ENT_QUOTES, 'UTF-8') ?>" <?= $filterCategory === $value ? 'selected' : '' ?>><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
          </select>
          </label>
        </div>
      </form>

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
                <th style="padding:12px 14px">Category</th>
                <th style="padding:12px 14px">Photo</th>
                <th style="padding:12px 14px">Resolution</th>
                <th style="padding:12px 14px">Issue</th>
                <th style="padding:12px 14px">Status</th>
                <th style="padding:12px 14px">Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($tickets as $t): ?>
                <?php
                $tid = (int) $t['id'];
                $cn = om_ticket_customer_name($t);
                $tl = om_ticket_type_label((string) ($t['type'] ?? ''));
                $statusLabel = om_ticket_status_label((string) ($t['status'] ?? ''));
                $resolutionLabel = om_ticket_resolution_label($t['type'] ?? null, $t['resolution'] ?? null);
                $descRaw = (string) ($t['description'] ?? '');
                $descShow = strlen($descRaw) > 200 ? substr($descRaw, 0, 197) . '...' : $descRaw;
                $photoUrl = om_ticket_photo_url($t['photo_path'] ?? null);
                $ticketPreview = [
                  'id' => $tid,
                  'order_id' => (string) ($t['order_id'] ?? ''),
                  'customer_name' => $cn !== '' ? $cn : (string) ($t['email'] ?? ''),
                  'category' => $tl,
                  'issue' => $descRaw,
                  'photo_url' => $photoUrl,
                  'resolution' => $resolutionLabel,
                  'status' => $statusLabel,
                  'created_at' => (string) ($t['created_at'] ?? ''),
                  'reviewed_at' => (string) ($t['reviewed_at'] ?? ''),
                  'admin_note' => (string) ($t['admin_note'] ?? ''),
                  'rejection_reason' => (string) ($t['rejection_reason'] ?? ''),
                ];
                ?>
                <tr style="border-bottom:1px solid var(--border)">
                  <td style="padding:12px 14px">#<?= $tid ?></td>
                  <td style="padding:12px 14px"><?= htmlspecialchars((string) $t['order_id'], ENT_QUOTES, 'UTF-8') ?></td>
                  <td style="padding:12px 14px"><?= htmlspecialchars($cn !== '' ? $cn : ($t['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                  <td style="padding:12px 14px"><?= htmlspecialchars($tl, ENT_QUOTES, 'UTF-8') ?></td>
                  <td style="padding:12px 14px">
                    <?php if ($photoUrl): ?>
                      <button type="button" data-photo-url="<?= htmlspecialchars($photoUrl, ENT_QUOTES, 'UTF-8') ?>" onclick="openTicketPhoto(this.getAttribute('data-photo-url'))" style="display:inline-flex;align-items:center;gap:6px;padding:5px 10px;border:1px solid var(--border);border-radius:999px;background:var(--white);color:var(--rose-deep);cursor:pointer;font-size:.7rem;font-weight:700">
                        View photo
                      </button>
                    <?php else: ?>
                      <span style="color:var(--muted-light)">—</span>
                    <?php endif; ?>
                  </td>
                  <td style="padding:12px 14px"><?= htmlspecialchars($resolutionLabel, ENT_QUOTES, 'UTF-8') ?></td>
                  <td style="padding:12px 14px;max-width:280px"><?= nl2br(htmlspecialchars($descShow, ENT_QUOTES, 'UTF-8')) ?></td>
                  <td style="padding:12px 14px">
                    <span class="status-chip"><?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?></span>
                  </td>
                  <td style="padding:12px 14px">
                    <div style="display:flex;flex-direction:column;gap:6px;align-items:flex-start">
                      <button type="button" class="btn btn-ghost btn-sm" style="padding:4px 10px;font-size:.65rem" data-ticket='<?= htmlspecialchars(json_encode($ticketPreview, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, "UTF-8") ?>' onclick='openTicketDetails(JSON.parse(this.dataset.ticket))'>View</button>
                      <?php if (($t['status'] ?? '') === 'open'): ?>
                      <form method="post" style="display:flex;flex-direction:column;gap:6px;align-items:flex-start">
                                                <?php echo csrf_token_field(); ?>
                        <input type="hidden" name="action" value="update_ticket"/>
                        <input type="hidden" name="id" value="<?= (int) $tid ?>"/>
                        <div style="display:flex;gap:6px;flex-wrap:wrap">
                          <button type="submit" name="decision" value="approve" class="btn btn-primary btn-sm" style="padding:4px 10px;font-size:.65rem">Approve</button>
                          <button type="button" class="btn btn-ghost btn-sm" style="padding:4px 10px;font-size:.65rem" data-ticket-id="<?= (int) $tid ?>" data-ticket-order="<?= htmlspecialchars((string) $t['order_id'], ENT_QUOTES, 'UTF-8') ?>" data-ticket-customer="<?= htmlspecialchars($cn !== '' ? $cn : ($t['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" onclick="openRejectModal(this)">Reject</button>
                        </div>
                      </form>
                      <?php else: ?>
                      <span style="font-size:.68rem;color:var(--muted-light)">Already reviewed</span>
                      <?php endif; ?>
                    </div>
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

<div id="rejectTicketModal" style="display:none;position:fixed;inset:0;background:rgba(36,20,24,.74);z-index:1001;align-items:center;justify-content:center;padding:24px">
  <div style="width:min(520px,96vw);background:var(--white);border-radius:18px;box-shadow:0 24px 64px rgba(36,20,24,.35);overflow:hidden">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:16px;padding:14px 16px;border-bottom:1px solid var(--border)">
      <div>
        <strong style="display:block;font-size:1rem;color:var(--dark)">Reject ticket</strong>
        <span id="rejectTicketMeta" style="font-size:.78rem;color:var(--muted)">Optional reason</span>
      </div>
      <button type="button" onclick="closeRejectModal()" style="border:none;background:transparent;color:var(--muted);font-size:1.5rem;cursor:pointer;line-height:1">×</button>
    </div>
    <form id="rejectTicketForm" method="post" style="padding:16px">
        <?php echo csrf_token_field(); ?>
      <input type="hidden" name="action" value="update_ticket"/>
      <input type="hidden" name="decision" value="reject"/>
      <input type="hidden" name="id" id="rejectTicketId" value=""/>
      <label style="display:block;font-size:.72rem;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:var(--muted-light);margin-bottom:8px">Reject reason</label>
      <textarea id="rejectTicketReason" name="rejection_reason" rows="5" placeholder="Optional reject reason" style="width:100%;resize:vertical;min-height:120px;padding:12px 14px;border:1px solid var(--border);border-radius:12px;font:inherit;color:var(--dark);background:#fff"></textarea>
      <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:16px">
        <button type="button" class="btn btn-ghost btn-sm" style="padding:8px 14px;font-size:.72rem" onclick="closeRejectModal()">Cancel</button>
        <button type="submit" class="btn btn-primary btn-sm" style="padding:8px 14px;font-size:.72rem">Reject ticket</button>
      </div>
    </form>
  </div>
</div>

<div id="ticketDetailsModal" style="display:none;position:fixed;inset:0;background:rgba(36,20,24,.74);z-index:1000;align-items:center;justify-content:center;padding:24px;overflow:auto">
  <div style="width:min(980px,96vw);background:var(--white);border-radius:20px;box-shadow:0 24px 64px rgba(36,20,24,.35);overflow:hidden">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:16px;padding:16px 18px;border-bottom:1px solid var(--border)">
      <div>
        <strong style="display:block;font-size:1rem;color:var(--dark)">Ticket details</strong>
        <span id="ticketDetailsSub" style="font-size:.78rem;color:var(--muted)">Preview</span>
      </div>
      <button type="button" onclick="closeTicketDetails()" style="border:none;background:transparent;color:var(--muted);font-size:1.5rem;cursor:pointer;line-height:1">×</button>
    </div>
    <div style="display:grid;grid-template-columns:1.1fr .9fr;gap:0;max-height:80vh;overflow:auto">
      <div style="padding:18px;border-right:1px solid var(--border)">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px">
          <div><div style="font-size:.68rem;letter-spacing:.12em;text-transform:uppercase;color:var(--muted-light)">Order</div><div id="ticketDetailsOrder" style="font-weight:600;color:var(--dark)">—</div></div>
          <div><div style="font-size:.68rem;letter-spacing:.12em;text-transform:uppercase;color:var(--muted-light)">Customer</div><div id="ticketDetailsCustomer" style="font-weight:600;color:var(--dark)">—</div></div>
          <div><div style="font-size:.68rem;letter-spacing:.12em;text-transform:uppercase;color:var(--muted-light)">Category</div><div id="ticketDetailsCategory" style="font-weight:600;color:var(--dark)">—</div></div>
          <div><div style="font-size:.68rem;letter-spacing:.12em;text-transform:uppercase;color:var(--muted-light)">Resolution</div><div id="ticketDetailsResolution" style="font-weight:600;color:var(--dark)">—</div></div>
          <div><div style="font-size:.68rem;letter-spacing:.12em;text-transform:uppercase;color:var(--muted-light)">Status</div><div id="ticketDetailsStatus" style="font-weight:600;color:var(--dark)">—</div></div>
          <div><div style="font-size:.68rem;letter-spacing:.12em;text-transform:uppercase;color:var(--muted-light)">Submitted</div><div id="ticketDetailsCreatedAt" style="font-weight:600;color:var(--dark)">—</div></div>
        </div>
        <div style="margin-bottom:14px">
          <div style="font-size:.68rem;letter-spacing:.12em;text-transform:uppercase;color:var(--muted-light);margin-bottom:6px">Issue details</div>
          <div id="ticketDetailsIssue" style="white-space:pre-wrap;line-height:1.7;color:var(--dark);font-size:.9rem"></div>
        </div>
        <div id="ticketDetailsNoteWrap" style="display:none;margin-bottom:14px">
          <div style="font-size:.68rem;letter-spacing:.12em;text-transform:uppercase;color:var(--muted-light);margin-bottom:6px">Admin note</div>
          <div id="ticketDetailsAdminNote" style="white-space:pre-wrap;line-height:1.7;color:var(--dark);font-size:.9rem"></div>
        </div>
        <div id="ticketDetailsRejectWrap" style="display:none">
          <div style="font-size:.68rem;letter-spacing:.12em;text-transform:uppercase;color:var(--muted-light);margin-bottom:6px">Rejection reason</div>
          <div id="ticketDetailsRejectReason" style="white-space:pre-wrap;line-height:1.7;color:var(--dark);font-size:.9rem"></div>
        </div>
      </div>
      <div style="padding:18px;background:#fff9fa">
        <div style="font-size:.68rem;letter-spacing:.12em;text-transform:uppercase;color:var(--muted-light);margin-bottom:8px">Photo evidence</div>
        <div id="ticketDetailsPhotoEmpty" style="display:none;padding:18px;border:1px dashed var(--border-mid);border-radius:14px;background:var(--white);color:var(--muted);text-align:center">No photo uploaded</div>
        <button type="button" id="ticketDetailsPhotoBtn" onclick="openTicketPhoto(document.getElementById('ticketDetailsPhotoBtn').getAttribute('data-photo-url'))" style="display:none;border:none;background:transparent;padding:0;width:100%;cursor:pointer">
          <img id="ticketDetailsPhotoImg" alt="Ticket photo" style="width:100%;max-height:420px;object-fit:contain;border-radius:14px;border:1px solid var(--border);background:#111">
        </button>
      </div>
    </div>
  </div>
</div>

<div id="ticketPhotoModal" style="display:none;position:fixed;inset:0;background:rgba(36,20,24,.74);z-index:999;align-items:center;justify-content:center;padding:24px">
  <div style="background:var(--white);border-radius:18px;overflow:hidden;max-width:min(92vw,1100px);max-height:92vh;display:flex;flex-direction:column;box-shadow:0 24px 64px rgba(36,20,24,.35)">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:16px;padding:12px 16px;border-bottom:1px solid var(--border)">
      <strong style="font-size:.86rem;color:var(--dark)">Ticket photo</strong>
      <button type="button" onclick="closeTicketPhoto()" style="border:none;background:transparent;color:var(--muted);font-size:1.4rem;cursor:pointer;line-height:1">×</button>
    </div>
    <img id="ticketPhotoModalImg" alt="Ticket photo" style="max-width:100%;max-height:82vh;object-fit:contain;background:#111">
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

  window.openTicketPhoto = function (photoUrl) {
    if (!photoUrl) return;
    var modal = document.getElementById('ticketPhotoModal');
    var img = document.getElementById('ticketPhotoModalImg');
    if (!modal || !img) {
      window.open(photoUrl, '_blank', 'noopener,noreferrer');
      return;
    }
    img.src = photoUrl;
    modal.style.display = 'flex';
  };

  window.closeTicketPhoto = function () {
    var modal = document.getElementById('ticketPhotoModal');
    var img = document.getElementById('ticketPhotoModalImg');
    if (img) img.src = '';
    if (modal) modal.style.display = 'none';
  };

  window.openTicketDetails = function (ticket) {
    if (!ticket) return;
    var modal = document.getElementById('ticketDetailsModal');
    if (!modal) return;

    document.getElementById('ticketDetailsSub').textContent = 'Ticket #' + (ticket.id || '—');
    document.getElementById('ticketDetailsOrder').textContent = ticket.order_id ? ('#' + ticket.order_id) : '—';
    document.getElementById('ticketDetailsCustomer').textContent = ticket.customer_name || '—';
    document.getElementById('ticketDetailsCategory').textContent = ticket.category || '—';
    document.getElementById('ticketDetailsResolution').textContent = ticket.resolution || '—';
    document.getElementById('ticketDetailsStatus').textContent = ticket.status || '—';
    document.getElementById('ticketDetailsCreatedAt').textContent = ticket.created_at || '—';
    document.getElementById('ticketDetailsIssue').textContent = ticket.issue || '—';

    var noteWrap = document.getElementById('ticketDetailsNoteWrap');
    var note = document.getElementById('ticketDetailsAdminNote');
    if (ticket.admin_note) {
      note.textContent = ticket.admin_note;
      noteWrap.style.display = 'block';
    } else {
      noteWrap.style.display = 'none';
      note.textContent = '';
    }

    var rejectWrap = document.getElementById('ticketDetailsRejectWrap');
    var rejectReason = document.getElementById('ticketDetailsRejectReason');
    if (ticket.rejection_reason) {
      rejectReason.textContent = ticket.rejection_reason;
      rejectWrap.style.display = 'block';
    } else {
      rejectWrap.style.display = 'none';
      rejectReason.textContent = '';
    }

    var photoBtn = document.getElementById('ticketDetailsPhotoBtn');
    var photoImg = document.getElementById('ticketDetailsPhotoImg');
    var photoEmpty = document.getElementById('ticketDetailsPhotoEmpty');
    if (ticket.photo_url) {
      photoBtn.setAttribute('data-photo-url', ticket.photo_url);
      photoImg.src = ticket.photo_url;
      photoBtn.style.display = 'block';
      photoEmpty.style.display = 'none';
    } else {
      photoBtn.removeAttribute('data-photo-url');
      photoImg.src = '';
      photoBtn.style.display = 'none';
      photoEmpty.style.display = 'block';
    }

    modal.style.display = 'flex';
  };

  window.closeTicketDetails = function () {
    var modal = document.getElementById('ticketDetailsModal');
    if (modal) modal.style.display = 'none';
  };

  window.openRejectModal = function (button) {
    if (!button) return;
    var modal = document.getElementById('rejectTicketModal');
    var ticketId = button.getAttribute('data-ticket-id') || '';
    var orderId = button.getAttribute('data-ticket-order') || '—';
    var customer = button.getAttribute('data-ticket-customer') || '—';
    var meta = document.getElementById('rejectTicketMeta');
    var idInput = document.getElementById('rejectTicketId');
    var reasonInput = document.getElementById('rejectTicketReason');
    if (!modal || !meta || !idInput || !reasonInput) return;

    idInput.value = ticketId;
    reasonInput.value = '';
    meta.textContent = 'Order #' + orderId + ' · ' + customer;
    modal.style.display = 'flex';
    setTimeout(function () { reasonInput.focus(); }, 0);
  };

  window.closeRejectModal = function () {
    var modal = document.getElementById('rejectTicketModal');
    var idInput = document.getElementById('rejectTicketId');
    var reasonInput = document.getElementById('rejectTicketReason');
    if (idInput) idInput.value = '';
    if (reasonInput) reasonInput.value = '';
    if (modal) modal.style.display = 'none';
  };

  document.getElementById('ticketPhotoModal')?.addEventListener('click', function (ev) {
    if (ev.target === this) {
      window.closeTicketPhoto();
    }
  });

  document.getElementById('ticketDetailsModal')?.addEventListener('click', function (ev) {
    if (ev.target === this) {
      window.closeTicketDetails();
    }
  });

  document.getElementById('rejectTicketModal')?.addEventListener('click', function (ev) {
    if (ev.target === this) {
      window.closeRejectModal();
    }
  });

  (function() {
    var el = document.getElementById('badgeTickets');
    if (el) el.textContent = '<?= (int) $openCount ?>';
  })();

  document.querySelectorAll('form').forEach(function (form) {
    form.addEventListener('submit', function () {
      saveScrollPosition();
    });
  });

  (function () {
    var form = document.getElementById('ticketFilterForm');
    if (!form) return;

    var submitTimer = null;
    var submitForm = function () {
      if (typeof form.requestSubmit === 'function') {
        form.requestSubmit();
        return;
      }
      form.submit();
    };

    var queueSubmit = function () {
      if (submitTimer) {
        window.clearTimeout(submitTimer);
      }
      submitTimer = window.setTimeout(function () {
        submitTimer = null;
        submitForm();
      }, 280);
    };

    form.querySelectorAll('select[data-auto-filter="true"]').forEach(function (field) {
      field.addEventListener('change', submitForm);
    });

    form.querySelectorAll('input[data-auto-filter="true"]').forEach(function (field) {
      field.addEventListener('input', queueSubmit);
      field.addEventListener('change', submitForm);
    });
  })();

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
