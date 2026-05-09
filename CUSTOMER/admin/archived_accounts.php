<?php
declare(strict_types=1);

require_once __DIR__ . '/admin_auth.php';
require_once __DIR__ . '/db.php';
admin_require_page('archived_accounts');
$pdo = adminDb();

$search = trim((string) ($_GET['search'] ?? ''));
$okMsg = trim((string) ($_GET['ok'] ?? ''));
$errMsg = trim((string) ($_GET['err'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'restore_account') {
    csrf_validate();
    $targetId = (int) ($_POST['target_id'] ?? 0);
    if ($targetId > 0) {
        bejewelry_set_account_archive($targetId, false, (int) (($GLOBALS['ADMIN_USER']['id'] ?? 0)), null);
        $qs = 'ok=' . rawurlencode('Archived account restored successfully.');
        if ($search !== '') {
            $qs .= '&search=' . rawurlencode($search);
        }
        header('Location: archived_accounts.php?' . $qs);
        exit;
    }
}

$where = ["u.role = 'customer'", "u.archived_at IS NOT NULL"];
$params = [];
if ($search !== '') {
    $where[] = '(u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?)';
    $like = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}
$whereSql = implode(' AND ', $where);

$stmt = $pdo->prepare(
    "SELECT u.id, u.first_name, u.last_name, u.email, u.phone, u.created_at, u.archived_at, u.archived_by, u.archive_reason,
            arch.first_name AS arch_first_name, arch.last_name AS arch_last_name, arch.email AS arch_email,
            COALESCE(o.order_count, 0) AS order_count,
            COALESCE(o.total_spent, 0) AS total_spent
     FROM users u
     LEFT JOIN users arch ON arch.id = u.archived_by
     LEFT JOIN (
        SELECT o.user_id, COUNT(*) AS order_count, SUM(o.total) AS total_spent
        FROM orders o
        GROUP BY o.user_id
     ) o ON o.user_id = u.id
     WHERE $whereSql
     ORDER BY u.archived_at DESC, u.id DESC"
);
$stmt->execute($params);
$archivedAccounts = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

$totalArchived = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'customer' AND archived_at IS NOT NULL")->fetchColumn();
$adminUser = $GLOBALS['ADMIN_USER'] ?? [];
$adminName = trim((string) (($adminUser['first_name'] ?? '') . ' ' . ($adminUser['last_name'] ?? '')));
$adminName = $adminName !== '' ? $adminName : (string) ($adminUser['email'] ?? 'Admin');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <?= csrf_meta_tag() ?>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Bejewelry Admin — Archive accounts</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,500;0,600;0,700;1,400&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600;9..40,700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="dashboard.css">
  <style>
    .panel{background:var(--white);border:1px solid var(--border);border-radius:var(--r-lg);box-shadow:var(--sh-xs);overflow:hidden}
    .panel-head{display:flex;align-items:center;justify-content:space-between;gap:10px;padding:14px 16px;border-bottom:1px solid var(--border)}
    .panel-title{font-family:var(--fd);font-size:1.02rem;color:var(--dark)}
    .panel-sub{font-size:.74rem;color:var(--muted-light)}
    .table-wrap{overflow-x:auto}
    .tbl{width:100%;border-collapse:collapse}
    .tbl th{font-size:.58rem;font-weight:700;letter-spacing:.14em;text-transform:uppercase;color:var(--muted-light);background:var(--blush);padding:11px 14px;text-align:left;border-bottom:1px solid var(--border);white-space:nowrap}
    .tbl td{padding:12px 14px;border-bottom:1px solid var(--border);font-size:.8rem;color:var(--dark);vertical-align:middle}
    .tbl tr:last-child td{border-bottom:none}
    .cell-user{display:flex;flex-direction:column;gap:2px}
    .name{font-weight:700;color:var(--dark)}
    .mail{font-size:.72rem;color:var(--muted-light)}
    .muted{color:var(--muted-light)}
    .search-box{min-width:240px;padding:9px 12px;border:1px solid var(--border);border-radius:10px;background:var(--white);font-size:.9rem;color:var(--dark);outline:none}
  </style>
</head>
<body>

<div class="loading-bar" id="loadingBar"></div>
<div class="site-wrapper">
  <?php $GLOBALS['NAV_ACTIVE'] = 'archived_accounts'; require __DIR__ . '/includes/nav_sidebar.php'; ?>
  <div class="site-content">
    <header class="topbar">
      <div class="topbar-left">
        <span class="topbar-title">Archive accounts</span>
        <span class="topbar-bc">Bejewelry Admin › Archive accounts</span>
      </div>
      <div class="topbar-right">
        <button class="icon-btn" title="Refresh" onclick="window.location.reload()">↺</button>
      </div>
    </header>

    <div class="content">
      <div class="page-hdr">
        <div>
          <h2>Archived customer accounts</h2>
          <p><?= (int) $totalArchived ?> archived customer account<?= $totalArchived === 1 ? '' : 's' ?></p>
        </div>
        <form method="get" class="toolbar">
          <input class="search-box" type="text" name="search" value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>" placeholder="Search archived accounts…">
          <button type="submit" class="btn btn-ghost btn-sm">Search</button>
        </form>
      </div>

      <?php if ($okMsg !== ''): ?>
      <div class="alert" style="margin-bottom:12px;background:#eef9f1;border-color:#b8e1c5;color:#1d6b3d;align-items:flex-start">
        <span class="alert-icon">✓</span>
        <span><?= htmlspecialchars($okMsg, ENT_QUOTES, 'UTF-8') ?></span>
      </div>
      <?php endif; ?>

      <?php if ($errMsg !== ''): ?>
      <div class="alert" style="margin-bottom:12px;background:#fff0f2;border-color:#f2b6c2;color:#9c2f45;align-items:flex-start">
        <span class="alert-icon">⛔</span>
        <span><?= htmlspecialchars($errMsg, ENT_QUOTES, 'UTF-8') ?></span>
      </div>
      <?php endif; ?>

      <div class="panel">
        <div class="panel-head">
          <div>
            <div class="panel-title">Archived Customers</div>
            <div class="panel-sub">Accounts removed from the main customer list.</div>
          </div>
        </div>
        <div class="panel-body table-wrap">
          <table class="tbl">
            <thead>
              <tr>
                <th>Account</th>
                <th>Email</th>
                <th>Archived At</th>
                <th>Reason</th>
                <th>Archived By</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($archivedAccounts)): ?>
                <tr>
                  <td colspan="6" style="text-align:center;padding:28px;color:var(--muted-light)">No archived accounts found.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($archivedAccounts as $row): ?>
                  <?php
                    $name = trim((string) ($row['first_name'] ?? '') . ' ' . (string) ($row['last_name'] ?? ''));
                    if ($name === '') {
                        $name = 'User #' . (int) $row['id'];
                    }
                    $archivedBy = trim((string) ($row['arch_first_name'] ?? '') . ' ' . (string) ($row['arch_last_name'] ?? ''));
                    if ($archivedBy === '') {
                        $archivedBy = trim((string) ($row['arch_email'] ?? ''));
                    }
                    if ($archivedBy === '') {
                        $archivedBy = 'System';
                    }
                  ?>
                  <tr>
                    <td>
                      <div class="cell-user">
                        <span class="name"><?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="mail">Orders: <?= (int) ($row['order_count'] ?? 0) ?> · Spent: ₱<?= number_format((float) ($row['total_spent'] ?? 0), 2) ?></span>
                      </div>
                    </td>
                    <td class="muted"><?= htmlspecialchars((string) ($row['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string) ($row['archived_at'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="muted" style="max-width:360px"><?= htmlspecialchars((string) ($row['archive_reason'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="muted"><?= htmlspecialchars($archivedBy, ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                      <form method="post" style="display:inline">
                        <?php echo csrf_token_field(); ?>
                        <input type="hidden" name="action" value="restore_account">
                        <input type="hidden" name="target_id" value="<?= (int) $row['id'] ?>">
                        <button type="button" class="btn btn-primary btn-sm restore-btn" style="padding:6px 12px">Restore</button>
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

<script src="confirm_modal.js?v=1"></script>
<script src="whoami.js?v=1"></script>
<script>
document.addEventListener('click', function (e) {
  var btn = e.target.closest && e.target.closest('.restore-btn');
  if (!btn) return;
  e.preventDefault();
  var form = btn.closest('form');
  if (typeof window.adminConfirm === 'function') {
    window.adminConfirm('Restore this archived customer account?', function () { form.submit(); }, { okText: 'Restore', cancelText: 'Cancel' });
    return;
  }
  if (confirm('Restore this archived customer account?')) {
    form.submit();
  }
});
</script>
</body>
</html>
