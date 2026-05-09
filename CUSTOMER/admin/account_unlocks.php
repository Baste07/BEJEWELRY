<?php
declare(strict_types=1);

require_once __DIR__ . '/admin_auth.php';
require_once __DIR__ . '/db.php';

admin_require_page('account_unlocks');
$pdo = adminDb();

$currentUser = $GLOBALS['ADMIN_USER'] ?? [];
$currentUserId = (int) ($currentUser['id'] ?? 0);
$search = trim((string) ($_GET['search'] ?? ($_POST['search'] ?? '')));
$lockThreshold = bejewelry_get_login_max_attempts();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate();
    $action = trim((string) ($_POST['action'] ?? ''));
    $targetId = (int) ($_POST['target_id'] ?? 0);

  if ($action === 'update_lock_threshold') {
    $newThreshold = (int) ($_POST['max_attempts'] ?? 0);
    if ($newThreshold < 1 || $newThreshold > 20) {
      $qs = 'err=' . rawurlencode('Please enter a lock threshold between 1 and 20.');
      header('Location: account_unlocks.php?' . $qs);
      exit;
    }
    bejewelry_set_login_max_attempts($newThreshold);
    $qs = 'ok=' . rawurlencode('Lock threshold updated to ' . $newThreshold . ' attempts.');
    header('Location: account_unlocks.php?' . $qs);
    exit;
  }

    if ($action === 'unlock' && $targetId > 0) {
        if ($targetId === $currentUserId) {
            $qs = 'err=' . rawurlencode('You cannot unlock your own account from this page.');
            if ($search !== '') {
                $qs .= '&search=' . rawurlencode($search);
            }
            header('Location: account_unlocks.php?' . $qs);
            exit;
        }

          $requestRef = 'UNLOCK-' . (string) $targetId;
          $reqStmt = $pdo->prepare(
            "SELECT id
             FROM support_tickets
             WHERE user_id = ? AND order_id = ? AND status = 'open'
             ORDER BY id DESC
             LIMIT 1"
          );
          $reqStmt->execute([$targetId, $requestRef]);
          $requestRow = $reqStmt->fetch(PDO::FETCH_ASSOC) ?: null;

        bejewelry_set_account_lock($targetId, false, $currentUserId, null);

          if ($requestRow) {
            $pdo->prepare('UPDATE support_tickets SET status = ?, admin_note = ?, reviewed_by = ?, reviewed_at = NOW() WHERE id = ?')->execute([
              'resolved',
              'Unlocked by super admin.',
              $currentUserId,
              (int) $requestRow['id'],
            ]);
          }

        $qs = 'ok=' . rawurlencode('Account unlocked successfully.');
        if ($search !== '') {
            $qs .= '&search=' . rawurlencode($search);
        }
        header('Location: account_unlocks.php?' . $qs);
        exit;
    }
}

$lockedCountStmt = $pdo->query('SELECT COUNT(*) FROM users WHERE locked_at IS NOT NULL');
$totalLocked = (int) $lockedCountStmt->fetchColumn();

$lockedByRoleStmt = $pdo->query("SELECT role, COUNT(*) AS cnt FROM users WHERE locked_at IS NOT NULL GROUP BY role");
$lockedByRole = [];
foreach ($lockedByRoleStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $lockedByRole[(string) $row['role']] = (int) $row['cnt'];
}

// Build unified unlock table: customer requests + system-locked accounts
$params = [];
$searchCondition = '';
if ($search !== '') {
    $like = '%' . $search . '%';
    $searchCondition = ' AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?)';
    $params = [$like, $like, $like];
}

// Merge unlock requests + system-locked accounts
$unifiedSql = "
  SELECT 
    u.id,
    u.first_name,
    u.last_name,
    u.email,
    u.role,
    u.failed_login_attempts,
    u.locked_at,
    u.lock_reason,
    locker.first_name AS locker_first_name,
    locker.last_name AS locker_last_name,
    locker.email AS locker_email,
    'system' AS unlock_type,
    NULL AS request_id,
    NULL AS request_description,
    NULL AS request_created_at
  FROM users u
  LEFT JOIN users locker ON locker.id = u.locked_by
  WHERE u.locked_at IS NOT NULL
    $searchCondition
  
  UNION ALL
  
  SELECT 
    u.id,
    u.first_name,
    u.last_name,
    u.email,
    u.role,
    NULL AS failed_login_attempts,
    t.created_at AS locked_at,
    NULL AS lock_reason,
    NULL AS locker_first_name,
    NULL AS locker_last_name,
    NULL AS locker_email,
    'request' AS unlock_type,
    t.id AS request_id,
    t.description AS request_description,
    t.created_at AS request_created_at
  FROM support_tickets t
  JOIN users u ON u.id = t.user_id
  WHERE t.order_id LIKE 'UNLOCK-%' AND t.status = 'open'
    $searchCondition
  
  ORDER BY locked_at DESC, id DESC
";
$stmt = $pdo->prepare($unifiedSql);
$stmt->execute($params);
$unifiedUnlocks = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

$roleLabels = [
    'super_admin' => 'Super Admin',
    'manager' => 'Order Manager',
    'inventory' => 'Inventory Manager',
    'courier' => 'Courier',
    'customer' => 'Customer',
    'admin' => 'Super Admin',
];

$roleOrder = ['super_admin', 'manager', 'inventory', 'courier', 'customer'];

$okMsg = trim((string) ($_GET['ok'] ?? ''));
$errMsg = trim((string) ($_GET['err'] ?? ''));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Bejewelry Admin — Account unlocks</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,500;0,600;0,700;1,400&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600;9..40,700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="dashboard.css">
  <style>
    .unlock-grid{display:grid;grid-template-columns:repeat(5,minmax(120px,1fr));gap:14px;margin-bottom:16px}
    .unlock-card{background:var(--white);border:1px solid var(--border);border-radius:var(--r-lg);padding:14px 16px}
    .unlock-card .label{display:block;font-size:.58rem;font-weight:700;letter-spacing:.14em;text-transform:uppercase;color:var(--muted-light);margin-bottom:4px}
    .unlock-card .value{font-family:var(--fd);font-size:1.3rem;color:var(--dark)}
    .panel{background:var(--white);border:1px solid var(--border);border-radius:var(--r-lg);box-shadow:var(--sh-xs);overflow:hidden}
    .panel-head{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:14px 16px;border-bottom:1px solid var(--border)}
    .panel-title{font-family:var(--fd);font-size:1.02rem;color:var(--dark)}
    .panel-sub{font-size:.74rem;color:var(--muted-light)}
    .panel-body{padding:0}
    .table-wrap{overflow-x:auto}
    .tbl{width:100%;border-collapse:collapse}
    .tbl th{font-size:.58rem;font-weight:700;letter-spacing:.14em;text-transform:uppercase;color:var(--muted-light);background:var(--blush);padding:11px 14px;text-align:left;border-bottom:1px solid var(--border);white-space:nowrap}
    .tbl td{padding:12px 14px;border-bottom:1px solid var(--border);font-size:.8rem;color:var(--dark);vertical-align:middle}
    .tbl tr:last-child td{border-bottom:none}
    .chip{display:inline-flex;align-items:center;padding:4px 10px;border-radius:999px;font-size:.62rem;font-weight:700;letter-spacing:.04em;text-transform:uppercase}
    .chip-locked{background:#fff1f1;color:#b42318;border:1px solid #f2c0c0}
    .chip-role{background:#eef3ff;color:#3559c7;border:1px solid #c4d0ff}
    .cell-user{display:flex;flex-direction:column;gap:2px}
    .name{font-weight:700;color:var(--dark)}
    .mail{font-size:.72rem;color:var(--muted-light)}
    .muted{color:var(--muted-light)}
    .toolbar{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
    .toolbar .finput{min-width:220px;padding:8px 12px;border:1px solid var(--border);border-radius:10px;background:var(--white);font-size:.9rem;color:var(--dark);outline:none;transition:box-shadow .12s ease,border-color .12s ease}
    .toolbar .finput:focus{box-shadow:0 6px 18px rgba(29,43,63,0.06);border-color:#d7c0c7}
    .toolbar .finput::placeholder{color:var(--muted-light)}
    .toolbar .search-input{min-width:260px;width:320px}
    .toolbar .finput-number{width:110px;min-width:110px;text-align:center}
    @media (max-width:1100px){.unlock-grid{grid-template-columns:repeat(2,minmax(120px,1fr))}}
    @media (max-width:700px){.unlock-grid{grid-template-columns:1fr}}
  </style>
</head>
<body>

<div class="loading-bar" id="loadingBar"></div>

<div class="site-wrapper">

  <?php $GLOBALS['NAV_ACTIVE'] = 'account_unlocks'; require __DIR__ . '/includes/nav_sidebar.php'; ?>

  <div class="site-content">
    <header class="topbar">
      <div class="topbar-left">
        <span class="topbar-title">Account unlocks</span>
        <span class="topbar-bc">Bejewelry Admin › Account unlocks</span>
      </div>
      <div class="topbar-right">
        <button class="icon-btn" title="Refresh" onclick="window.location.reload()">↺</button>
      </div>
    </header>

    <div class="content">
      <div class="page-hdr">
        <div>
          <h2>Locked Accounts</h2>
          <p>Unlock customer and staff accounts that were locked after failed sign-in attempts.</p>
        </div>
        <form method="post" class="toolbar" style="margin-top:8px">
                    <?php echo csrf_token_field(); ?>
          <input type="hidden" name="action" value="update_lock_threshold">
          <label class="muted" for="max_attempts" style="font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em">Attempts before lock</label>
          <input class="finput finput-number" id="max_attempts" name="max_attempts" type="number" min="1" max="20" value="<?= (int) $lockThreshold ?>">
          <button type="submit" class="btn btn-primary btn-sm">Save</button>
        </form>
      </div>

      <?php if ($okMsg !== ''): ?>
      <div class="alert" style="margin-bottom:12px;background:#eef9f1;border-color:#b8e1c5;color:#1d6b3d;">
        <span class="alert-icon">✓</span>
        <span><?= htmlspecialchars($okMsg, ENT_QUOTES, 'UTF-8') ?></span>
      </div>
      <?php endif; ?>

      <?php if ($errMsg !== ''): ?>
      <div class="alert" style="margin-bottom:12px;background:#fff0f2;border-color:#f2b6c2;color:#9c2f45;">
        <span class="alert-icon">⛔</span>
        <span><?= htmlspecialchars($errMsg, ENT_QUOTES, 'UTF-8') ?></span>
      </div>
      <?php endif; ?>

      <div class="unlock-grid">
        <div class="unlock-card"><span class="label">Total Locked</span><div class="value"><?= (int) $totalLocked ?></div></div>
        <div class="unlock-card"><span class="label">Lock Threshold</span><div class="value"><?= (int) $lockThreshold ?></div></div>
        <?php foreach ($roleOrder as $rk): ?>
          <div class="unlock-card">
            <span class="label"><?= htmlspecialchars($roleLabels[$rk] ?? ucfirst(str_replace('_', ' ', $rk)), ENT_QUOTES, 'UTF-8') ?></span>
            <div class="value"><?= (int) ($lockedByRole[$rk] ?? 0) ?></div>
          </div>
        <?php endforeach; ?>
      </div>

      <div class="panel" style="margin-top:16px">
        <div class="panel-head">
          <div>
            <div class="panel-title">All Accounts Needing Unlock</div>
            <div class="panel-sub">Customer requests and system-locked accounts in one view. Only super admins can unlock.</div>
          </div>
          <form method="get" class="toolbar">
            <input class="finput search-input" type="text" name="search" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>" placeholder="Search name or email">
            <button type="submit" class="btn btn-ghost btn-sm">Search</button>
            <?php if ($search !== ''): ?>
              <a class="btn btn-ghost btn-sm" href="account_unlocks.php" style="text-decoration:none;display:inline-flex;align-items:center;justify-content:center">Reset</a>
            <?php endif; ?>
          </form>
        </div>
        <div class="panel-body table-wrap">
          <table class="tbl">
            <thead>
              <tr>
                <th>Account</th>
                <th>Role</th>
                <th>Type</th>
                <th>Reason / Details</th>
                <th>Date</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($unifiedUnlocks)): ?>
                <tr>
                  <td colspan="6" style="text-align:center;padding:28px;color:var(--muted-light)">No unlock requests or locked accounts found.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($unifiedUnlocks as $row): ?>
                  <?php
                    $name = trim((string) ($row['first_name'] ?? '') . ' ' . (string) ($row['last_name'] ?? ''));
                    if ($name === '') {
                        $name = 'User #' . (int) $row['id'];
                    }
                    $roleKey = (string) ($row['role'] ?? 'customer');
                    if ($roleKey === 'admin') {
                        $roleKey = 'super_admin';
                    }
                    $roleLabel = $roleLabels[$roleKey] ?? ucfirst(str_replace('_', ' ', $roleKey));
                    $isRequest = (string) ($row['unlock_type'] ?? '') === 'request';
                    $typeLabel = $isRequest ? 'Customer Request' : 'System Lock';
                    $typeChipClass = $isRequest ? 'style="background:#fef3c7;color:#b45309;border:1px solid #fcd34d"' : 'style="background:#fee2e2;color:#991b1b;border:1px solid #fca5a5"';
                    $reason = $isRequest ? (string) ($row['request_description'] ?? '—') : (string) ($row['lock_reason'] ?? '—');
                    $date = (string) ($row['locked_at'] ?? '—');
                    $lockerName = '';
                    if (!$isRequest) {
                        $lockerName = trim((string) ($row['locker_first_name'] ?? '') . ' ' . (string) ($row['locker_last_name'] ?? ''));
                        if ($lockerName === '') {
                            $lockerName = trim((string) ($row['locker_email'] ?? ''));
                        }
                        if ($lockerName === '') {
                            $lockerName = 'System';
                        }
                    }
                  ?>
                  <tr>
                    <td>
                      <div class="cell-user">
                        <span class="name"><?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="mail"><?= htmlspecialchars((string) ($row['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                      </div>
                    </td>
                    <td><span class="chip chip-role"><?= htmlspecialchars($roleLabel, ENT_QUOTES, 'UTF-8') ?></span></td>
                    <td><span class="chip" <?= $typeChipClass ?>><?= htmlspecialchars($typeLabel, ENT_QUOTES, 'UTF-8') ?></span></td>
                    <td>
                      <div style="max-width:400px">
                        <div class="muted"><?= htmlspecialchars($reason, ENT_QUOTES, 'UTF-8') ?></div>
                        <?php if (!$isRequest && $lockerName): ?>
                          <div style="font-size:.68rem;margin-top:4px;color:var(--muted-light)">by <?= htmlspecialchars($lockerName, ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
                        <?php if (!$isRequest && $row['failed_login_attempts']): ?>
                          <div style="font-size:.68rem;margin-top:4px;color:var(--muted-light)"><?= (int) $row['failed_login_attempts'] ?> failed attempt<?= (int) $row['failed_login_attempts'] === 1 ? '' : 's' ?></div>
                        <?php endif; ?>
                      </div>
                    </td>
                    <td><?= htmlspecialchars($date, ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                      <form method="post" class="unlock-form" style="display:inline">
                        <?php echo csrf_token_field(); ?>
                        <input type="hidden" name="action" value="unlock">
                        <input type="hidden" name="target_id" value="<?= (int) $row['id'] ?>">
                        <input type="hidden" name="search" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>">
                        <button type="button" class="btn btn-primary btn-sm unlock-btn" style="padding:6px 12px">Unlock</button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="whoami.js?v=1"></script>
<script src="notif_dot.js?v=1"></script>
<script src="confirm_modal.js?v=1"></script>
<script>
function handleLogout() {
  if (typeof window.adminConfirm === 'function') {
    window.adminConfirm('Log out of Bejewelry Admin?', function () { window.location.href = '../logout.php'; }, { okText: 'Log out' });
    return;
  }
  window.location.href = '../logout.php';
}
</script>
<script>
// Unlock button uses branded confirm modal when available
document.addEventListener('click', function (e) {
  var btn = e.target.closest && e.target.closest('.unlock-btn');
  if (!btn) return;
  e.preventDefault();
  var form = btn.closest('form');
  var nameEl = form && form.closest('tr') && form.closest('tr').querySelector('.name');
  var who = nameEl ? nameEl.textContent.trim() : 'this account';
  var msg = 'Unlock ' + who + '?\nThis will reset the failed sign-in attempts.';
  if (typeof window.adminConfirm === 'function') {
    window.adminConfirm(msg, function () { form.submit(); }, { okText: 'Unlock', cancelText: 'Cancel' });
    return;
  }
  if (confirm(msg)) {
    form.submit();
  }
});
</script>
</body>
</html>
