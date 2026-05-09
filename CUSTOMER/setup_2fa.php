<?php
declare(strict_types=1);

require_once __DIR__ . '/inc.php';
require_once __DIR__ . '/auth_totp.php';

if (current_user_id()) {
    header('Location: index.php');
    exit;
}

if (empty($_SESSION['pending_totp_setup_uid'])) {
    header('Location: login.php');
    exit;
}

$uid = (int) $_SESSION['pending_totp_setup_uid'];

if (empty($_SESSION['totp_setup_secret'])) {
    $_SESSION['totp_setup_secret'] = TotpHelper::generateSecret();
}

$secret = (string) $_SESSION['totp_setup_secret'];

$stmt = db()->prepare('SELECT email FROM users WHERE id = ? LIMIT 1');
$stmt->execute([$uid]);
$email = (string) ($stmt->fetchColumn() ?: 'user');
$qrUrl = TotpHelper::getProvisioningUrl($secret, $email, TOTP_ISSUER);
$qrImageUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . rawurlencode($qrUrl);

$error = $_SESSION['setup_2fa_error'] ?? '';
unset($_SESSION['setup_2fa_error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Set up Authenticator — Bejewelry</title>
<link rel="stylesheet" href="css/fonts.css">
<link rel="stylesheet" href="css/styles.css">
<link rel="stylesheet" href="css/login-page.css">
<style>
.otp-group{display:flex;gap:10px;justify-content:space-between}
.otp-box{width:52px;height:52px;border-radius:12px;border:1.5px solid var(--border-mid);text-align:center;font-size:1.15rem;font-weight:700;color:var(--dark);background:var(--white);outline:none;transition:border-color var(--tr),box-shadow var(--tr)}
.otp-box:focus{border-color:var(--rose-muted);box-shadow:0 0 0 3px rgba(217,96,112,.1)}
@media (max-width:480px){
  .otp-group{gap:8px}
  .otp-box{width:46px;height:46px;font-size:1rem}
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
<div class="login-wrap">
  <div class="login-brand">
    <div>
      <div style="font-family:var(--fd);font-size:2rem;font-weight:700;color:var(--white);margin-bottom:4px">Bejewelry</div>
      <div class="brand-sub" style="font-size:.6rem;font-weight:600;letter-spacing:.22em;text-transform:uppercase;margin-bottom:48px">First-time setup</div>
      <p class="brand-body" style="font-size:.9rem;line-height:1.85">Scan the QR code with Google Authenticator, then enter a code to confirm. Required for all accounts.</p>
    </div>
  </div>
  <div class="login-form-wrap">
    <div class="login-form-inner">
      <h2 style="font-size:1.6rem;margin-bottom:4px">Set up Google Authenticator</h2>
      <p style="margin-bottom:16px;font-size:.85rem;color:var(--muted)">You will use this app every time you sign in.</p>
      <div style="margin:16px 0;text-align:center">
        <img src="<?= htmlspecialchars($qrImageUrl, ENT_QUOTES, 'UTF-8') ?>" alt="QR" width="200" height="200" style="border-radius:8px;border:1px solid var(--border)">
      </div>
      <p class="t-sm muted" style="margin-bottom:16px">Manual key: <code style="background:var(--blush);padding:4px 8px;border-radius:4px;font-size:.85rem"><?= htmlspecialchars($secret, ENT_QUOTES, 'UTF-8') ?></code></p>
      <?php if ($error !== ''): ?>
      <p class="muted" style="margin-bottom:16px;color:var(--danger)"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
      <?php endif; ?>
      <form action="setup_2fa_process.php" method="post" autocomplete="off" id="setupOtpForm">
          <?php echo csrf_token_field(); ?>
        <div class="fg">
          <label class="flabel">6-digit code</label>
          <input type="hidden" name="totp" id="setupTotpHidden" required>
          <div class="otp-group" id="setupOtpGroup" role="group" aria-label="6-digit setup authenticator code">
            <input class="otp-box" type="text" inputmode="numeric" maxlength="1" autocomplete="one-time-code" aria-label="Digit 1">
            <input class="otp-box" type="text" inputmode="numeric" maxlength="1" aria-label="Digit 2">
            <input class="otp-box" type="text" inputmode="numeric" maxlength="1" aria-label="Digit 3">
            <input class="otp-box" type="text" inputmode="numeric" maxlength="1" aria-label="Digit 4">
            <input class="otp-box" type="text" inputmode="numeric" maxlength="1" aria-label="Digit 5">
            <input class="otp-box" type="text" inputmode="numeric" maxlength="1" aria-label="Digit 6">
          </div>
        </div>
        <button type="submit" class="btn btn-primary btn-full btn-lg" style="justify-content:center;margin-top:8px">Confirm & continue</button>
      </form>
      <p class="tc mt5 t-sm muted"><a href="login.php" class="text-rose">← Cancel</a></p>
    </div>
  </div>
</div>
<script>
(function() {
  var form = document.getElementById('setupOtpForm');
  var hidden = document.getElementById('setupTotpHidden');
  var boxes = Array.from(document.querySelectorAll('#setupOtpGroup .otp-box'));
  if (!form || !hidden || boxes.length !== 6) return;

  function normalizeDigit(v) {
    var m = String(v || '').match(/\d/);
    return m ? m[0] : '';
  }

  function syncHidden() {
    hidden.value = boxes.map(function(b){ return b.value; }).join('');
  }

  function fillFrom(start, digits) {
    for (var i = 0; i < digits.length && (start + i) < boxes.length; i++) {
      boxes[start + i].value = digits[i];
    }
    var next = Math.min(start + digits.length, boxes.length - 1);
    boxes[next].focus();
    boxes[next].select();
    syncHidden();
  }

  boxes.forEach(function(box, idx) {
    box.addEventListener('input', function(e) {
      var raw = String(e.target.value || '');
      var digits = raw.replace(/\D/g, '');
      if (digits.length > 1) {
        fillFrom(idx, digits.split(''));
        return;
      }
      e.target.value = normalizeDigit(raw);
      syncHidden();
      if (e.target.value && idx < boxes.length - 1) {
        boxes[idx + 1].focus();
        boxes[idx + 1].select();
      }
    });

    box.addEventListener('keydown', function(e) {
      if (e.key === 'Backspace' && !box.value && idx > 0) {
        boxes[idx - 1].focus();
        boxes[idx - 1].value = '';
        syncHidden();
        e.preventDefault();
      }
      if (e.key === 'ArrowLeft' && idx > 0) {
        boxes[idx - 1].focus();
        e.preventDefault();
      }
      if (e.key === 'ArrowRight' && idx < boxes.length - 1) {
        boxes[idx + 1].focus();
        e.preventDefault();
      }
    });

    box.addEventListener('paste', function(e) {
      e.preventDefault();
      var text = (e.clipboardData || window.clipboardData).getData('text') || '';
      var digits = text.replace(/\D/g, '').split('');
      if (!digits.length) return;
      fillFrom(idx, digits);
    });
  });

  form.addEventListener('submit', function(e) {
    syncHidden();
    if (!/^\d{6}$/.test(hidden.value)) {
      e.preventDefault();
      var missing = boxes.find(function(b){ return !b.value; });
      if (missing) {
        missing.focus();
      }
    }
  });

  boxes[0].focus();
})();
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
