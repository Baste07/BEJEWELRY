<?php
declare(strict_types=1);

$active = $GLOBALS['NAV_ACTIVE'] ?? 'orders';
$na = static function (string $p) use ($active): string {
    return $p === $active ? 'active' : '';
};
?>
  <aside class="sidebar">
    <div class="sb-brand">
      <div class="sb-logo">Bejewelry</div>
      <div class="sb-sub">Fine Jewellery · Philippines</div>
    </div>
    <div class="sb-user">
      <div class="sb-av" id="sbAvatar">OM</div>
      <div>
        <div class="sb-uname" id="sbUsername">Loading…</div>
        <div class="sb-urole" id="sbUserRole">Order Manager</div>
      </div>
    </div>
    <nav>
      <div class="sb-group">Overview</div>
      <a class="sb-item <?= $na('dashboard') ?>" href="dashboard.php"><span class="sb-icon">◈</span> Dashboard</a>

      <div class="sb-group">Order Manager app</div>
      <a class="sb-item <?= $na('orders') ?>" href="orders.php"><span class="sb-icon">📦</span> Orders &amp; shipping</a>
      <a class="sb-item <?= $na('courier_accounts') ?>" href="courier_accounts.php"><span class="sb-icon">🚚</span> Courier Accounts</a>

      <div class="sb-group">Support &amp; marketing</div>
      <a class="sb-item <?= $na('tickets') ?>" href="tickets.php"><span class="sb-icon">🎫</span> Tickets</a>
      <a class="sb-item <?= $na('reviews') ?>" href="reviews.php"><span class="sb-icon">⭐</span> Review ratings</a>
      <a class="sb-item <?= $na('promotions') ?>" href="promotions.php"><span class="sb-icon">🏷️</span> Promotions</a>
      <a class="sb-item <?= $na('settings') ?>" href="settings.php"><span class="sb-icon">⚙</span> Settings</a>
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
