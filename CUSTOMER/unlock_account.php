<?php
require_once __DIR__ . '/inc.php';

if (current_user_id()) {
    $u = current_user();
    $role = $u['role'] ?? 'customer';
    if (in_array($role, ['admin', 'super_admin', 'manager', 'inventory', 'courier'], true)) {
        header('Location: ' . bejewelry_staff_post_login_path($role));
    } else {
        header('Location: index.php');
    }
    exit;
}

$message = '';
$error = '';
$submittedEmail = '';
$submittedReason = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate();

  $email = trim((string) ($_POST['email'] ?? ''));
  $reason = trim((string) ($_POST['reason'] ?? ''));
  $submittedEmail = $email;
  $submittedReason = $reason;

    if ($email === '' || $reason === '') {
        $error = 'Please fill in all fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $pdo = db();
        $userStmt = $pdo->prepare('SELECT id, first_name, last_name, email, locked_at FROM users WHERE email = ? LIMIT 1');
        $userStmt->execute([$email]);
        $user = $userStmt->fetch(PDO::FETCH_ASSOC) ?: null;

        if (!$user) {
            $error = 'No account found with that email.';
        } elseif (empty($user['locked_at'])) {
            $error = 'This account is not currently locked.';
        } else {
            $requestRef = 'UNLOCK-' . (string) ((int) $user['id']);

            $existingStmt = $pdo->prepare(
                "SELECT id
                 FROM support_tickets
                 WHERE user_id = ?
                   AND order_id = ?
                   AND status = 'open'
                 ORDER BY id DESC
                 LIMIT 1"
            );
            $existingStmt->execute([(int) $user['id'], $requestRef]);

            if ($existingStmt->fetch(PDO::FETCH_ASSOC)) {
                $error = 'You already have an open unlock request.';
            } else {
                $description = 'Account unlock request submitted from the login page. Reason: ' . $reason;
                try {
                    $insert = $pdo->prepare(
                        'INSERT INTO support_tickets (user_id, order_id, type, category, scope, description, status)
                         VALUES (?, ?, ?, ?, ?, ?, ?)'
                    );
                    $insert->execute([(int) $user['id'], $requestRef, 'other', 'other', 'super_admin', $description, 'open']);

                    bejewelry_audit_log((int) $user['id'], (string) $user['email'], 'request_account_unlock');
                    $message = 'Your unlock request has been submitted. A super admin will review it soon.';
                } catch (Throwable $e) {
                    $error = 'Could not submit your unlock request right now.';
                }
            }
        }
    }
}

  $formEmail = $error !== '' ? $submittedEmail : '';
  $formReason = $error !== '' ? $submittedReason : '';

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<meta name="csrf-token" content="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
<title>Unlock Account — Bejewelry</title>
<link rel="stylesheet" href="css/fonts.css">
<link rel="stylesheet" href="css/styles.css">
<link rel="stylesheet" href="css/login-page.css">
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
<div id="toasts"></div>

<div class="login-wrap">
  <div class="login-brand">
    <div>
      <div style="font-family:var(--fd);font-size:2rem;font-weight:700;color:var(--white);margin-bottom:4px">Bejewelry</div>
      <div class="brand-sub" style="font-size:.6rem;font-weight:600;letter-spacing:.22em;text-transform:uppercase;margin-bottom:48px">Fine Jewelry</div>
      <div class="brand-tagline" style="font-family:var(--fd);font-size:2.2rem;font-weight:500;color:var(--white);line-height:1.3;margin-bottom:16px"><em>Need access</em><br>to your account?</div>
      <p class="brand-body" style="font-size:.9rem;line-height:1.85;margin-bottom:40px">If your account is locked after too many sign-in attempts, submit a request and our super admin team will review it.</p>
      <div style="display:flex;flex-direction:column;gap:14px">
        <div class="brand-feature" style="display:flex;align-items:center;gap:12px;font-size:.84rem"><span style="font-size:1.2rem">🔓</span>Request account unlock</div>
        <div class="brand-feature" style="display:flex;align-items:center;gap:12px;font-size:.84rem"><span style="font-size:1.2rem">🛡️</span>Reviewed by super admin only</div>
        <div class="brand-feature" style="display:flex;align-items:center;gap:12px;font-size:.84rem"><span style="font-size:1.2rem">✉️</span>We will keep you posted on the status</div>
      </div>
    </div>
  </div>

  <div class="login-form-wrap">
    <div class="login-form-inner">
      <div style="margin-bottom:32px">
        <div style="font-family:var(--fd);font-size:1.4rem;font-weight:700;color:var(--dark)">Bejewelry</div>
        <div style="font-size:.55rem;font-weight:700;letter-spacing:.2em;text-transform:uppercase;color:var(--rose)">Fine Jewelry</div>
      </div>

      <h2 style="font-size:1.6rem;margin-bottom:4px">Unlock Account Request</h2>
      <p style="margin-bottom:24px;font-size:.85rem">Use this form if your account was locked due to repeated failed sign-ins.</p>

      <?php if ($message !== ''): ?>
      <div class="alert" style="margin-bottom:16px;background:#eef9f1;border-color:#b8e1c5;color:#1d6b3d;align-items:flex-start">
        <span class="alert-icon">✓</span>
        <span><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></span>
      </div>
      <?php endif; ?>

      <?php if ($error !== ''): ?>
      <div class="alert" style="margin-bottom:16px;background:#fff0f2;border-color:#f2b6c2;color:#9c2f45;align-items:flex-start">
        <span class="alert-icon">⛔</span>
        <span><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></span>
      </div>
      <?php endif; ?>

      <form method="post" action="unlock_account.php">
        <?php echo csrf_token_field(); ?>
        <div class="fg">
          <label class="flabel">Email Address</label>
          <input class="finput" type="email" name="email" placeholder="you@email.com" autocomplete="email" required value="<?= htmlspecialchars($formEmail, ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="fg">
          <label class="flabel">Reason for Unlock Request</label>
          <textarea class="finput" name="reason" rows="5" placeholder="Tell us what happened and why you need your account restored" required style="resize:vertical;min-height:120px"><?= htmlspecialchars($formReason, ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>
        <button type="submit" class="btn btn-primary btn-full btn-lg" style="justify-content:center">Submit Unlock Request</button>
      </form>

      <p class="tc mt5 t-sm muted">Remembered your password? <a href="login.php" class="text-rose">Back to sign in</a></p>
    </div>
  </div>
</div>

<script>
  (function () {
    var key = window.__SCROLL_RESTORE_BOOTSTRAP__ || ('scroll_restore::' + location.pathname);

    function saveScrollPosition() {
      try { sessionStorage.setItem(key, String(window.scrollY || 0)); } catch (e) {}
    }

    function restoreScrollPositionOnce() {
      var raw = null;
      try { raw = sessionStorage.getItem(key); } catch (e) {}
      if (raw === null) {
        document.documentElement.style.opacity = '1';
        return;
      }

      var savedY = Number(raw);
      try { sessionStorage.removeItem(key); } catch (e) {}
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
    window.addEventListener('load', function () {
      restoreScrollPositionOnce();
      window.dispatchEvent(new Event('resize'));
    });
  })();
</script>
</body>
</html>