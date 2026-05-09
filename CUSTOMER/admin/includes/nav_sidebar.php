<?php
declare(strict_types=1);
/** @var string $GLOBALS['NAV_ACTIVE'] */
$r = $GLOBALS['ADMIN_ROLE'] ?? 'super_admin';
$active = $GLOBALS['NAV_ACTIVE'] ?? 'dashboard';
$na = static function (string $p) use ($active): string {
    return $p === $active ? 'active' : '';
};

// Ticket count not needed since we removed tickets feature
?>
  <style>
    /* Ensure sidebar badge looks consistent across admin pages */
    .sb-badge { margin-left: auto; background: #D96070; color: #fff; font-size: .56rem; font-weight:700; min-width:18px; height:18px; border-radius:999px; display:inline-flex; align-items:center; justify-content:center; padding:0 6px; }
    .sb-badge.alert { background: #BB3333; box-shadow: 0 0 0 2px rgba(187,51,51,.12); }
  </style>
  <aside class="sidebar">
    <div class="sb-brand">
      <div class="sb-logo">Bejewelry</div>
      <div class="sb-sub">Fine Jewellery · Philippines</div>
    </div>
    <div class="sb-user">
      <div class="sb-av" id="sbAvatar">AD</div>
      <div>
        <div class="sb-uname" id="sbUsername">Loading…</div>
        <div class="sb-urole" id="sbUserRole">—</div>
      </div>
    </div>
    <nav>
    <?php if ($r !== 'super_admin'): ?>
      <div class="sb-group">Overview</div>
      <a class="sb-item <?= $na('dashboard') ?>" href="/BEJEWELRY/CUSTOMER/InventoryManager/dashboard.php"><span class="sb-icon">◈</span> Dashboard</a>
    <?php endif; ?>

<?php if ($r === 'super_admin'): ?>
      <div class="sb-group">Accounts</div>
      <a class="sb-item <?= $na('customers') ?>" href="customers.php"><span class="sb-icon">👤</span> Customer accounts</a>
  <a class="sb-item <?= $na('account_unlocks') ?>" href="account_unlocks.php"><span class="sb-icon">🔓</span> Account unlocks</a>
      <div class="sb-group">System</div>
      <a class="sb-item <?= $na('settings') ?>" href="settings.php"><span class="sb-icon">⚙️</span> Settings &amp; staff</a>
  <a class="sb-item <?= $na('audit_log') ?>" href="audit_log.php"><span class="sb-icon">📜</span> Audit log</a>

<?php elseif ($r === 'manager'): ?>
      <div class="sb-group">Order Manager app</div>
  <a class="sb-item <?= $na('orders') ?>" href="orders.php"><span class="sb-icon">📦</span> Orders &amp; shipping</a>
      <div class="sb-group">Support &amp; marketing</div>
      <a class="sb-item <?= $na('reviews') ?>" href="reviews.php"><span class="sb-icon">⭐</span> Review ratings</a>
      <a class="sb-item <?= $na('promotions') ?>" href="promotions.php"><span class="sb-icon">🏷️</span> Promotions</a>

<?php else: /* inventory */ ?>
      <div class="sb-group">Inventory app</div>
  <a class="sb-item <?= $na('inventory') ?>" href="inventory.php"><span class="sb-icon">📊</span> Stock dashboard</a>
      <div class="sb-group">Admin tools</div>
      <a class="sb-item <?= $na('inventory') ?>" href="inventory.php"><span class="sb-icon">📋</span> Inventory <span class="sb-badge gold" id="badgeInventory">—</span></a>
      <a class="sb-item <?= $na('reports') ?>" href="reports.php"><span class="sb-icon">📈</span> Reports</a>
      <a class="sb-item <?= $na('products') ?>" href="products.php"><span class="sb-icon">💎</span> Products <span class="sb-badge" id="badgeProducts">—</span></a>
<?php endif; ?>
      <hr class="sb-div">
    </nav>
    <button class="sb-foot" onclick="handleLogout()">← Log Out</button>
  </aside>
  <script>
    (function () {
      if (window.__bjSessionIdleBound) return;
      window.__bjSessionIdleBound = true;
      var timeoutSeconds = <?= (int) (function_exists('bejewelry_get_session_timeout_seconds') ? bejewelry_get_session_timeout_seconds() : 120) ?>;
      var timeoutMs = Math.max(60000, timeoutSeconds * 1000);
      var warningMs = Math.min(30000, Math.max(5000, timeoutMs - 5000));
      var warningSeconds = Math.floor(warningMs / 1000);
      var warningTimer = null;
      var logoutTimer = null;

      function hideWarning() {
        var node = document.getElementById('bjSessionWarning');
        if (node) node.remove();
      }

      function showWarning() {
        hideWarning();
        var box = document.createElement('div');
        box.id = 'bjSessionWarning';
        box.style.cssText = 'position:fixed;right:16px;bottom:16px;z-index:10000;max-width:340px;background:#241418;color:#fff;border-radius:12px;padding:12px 14px;box-shadow:0 10px 24px rgba(0,0,0,.25);font-size:.82rem;line-height:1.5';
        box.innerHTML = '<div style="font-weight:700;margin-bottom:4px">Session expiring soon</div>' +
          '<div>You will be logged out in ' + warningSeconds + ' seconds due to inactivity.</div>';
        document.body.appendChild(box);
      }

      function resetTimer() {
        hideWarning();
        if (warningTimer) clearTimeout(warningTimer);
        if (logoutTimer) clearTimeout(logoutTimer);

        warningTimer = setTimeout(showWarning, Math.max(0, timeoutMs - warningMs));
        logoutTimer = setTimeout(function () {
          window.location.href = '../logout.php?reason=timeout';
        }, timeoutMs);
      }

      ['mousemove', 'mousedown', 'keydown', 'scroll', 'touchstart', 'click'].forEach(function (evt) {
        window.addEventListener(evt, resetTimer, { passive: true });
      });

      resetTimer();
    })();
  </script>
