<?php
require_once __DIR__ . '/inc.php';

// If already logged in, just go to appropriate home
if (current_user_id()) {
  $u = current_user();
  $role = $u['role'] ?? 'customer';
  if (in_array($role, ['admin','super_admin'], true)) {
    header('Location: admin/dashboard.php');
  } else {
    header('Location: index.php');
  }
  exit;
}

$message = '';
$error = '';

$__pwd_policy = bejewelry_get_password_policy();
if (empty($__pwd_policy['enabled'])) {
  $__pwd_hint = 'No specific password requirements.';
} else {
  $__pwd_hint_parts = ['at least ' . (int)($__pwd_policy['min_length'] ?? 8) . ' characters'];
  $__pwd_requirements = [];
  if (!empty($__pwd_policy['require_upper'])) $__pwd_requirements[] = 'uppercase';
  if (!empty($__pwd_policy['require_lower'])) $__pwd_requirements[] = 'lowercase';
  if (!empty($__pwd_policy['require_number'])) $__pwd_requirements[] = 'a number';
  if (!empty($__pwd_policy['require_special'])) $__pwd_requirements[] = 'a special character';
  if ($__pwd_requirements) {
    $__pwd_hint_parts[] = 'include ' . implode(', ', $__pwd_requirements);
  }
  $__pwd_hint = 'Use ' . implode(' and ', $__pwd_hint_parts) . '.';
}

$alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

function newCaptcha(string $alphabet): void {
  $txt = '';
  for ($i = 0; $i < 5; $i++) {
    $txt .= $alphabet[random_int(0, strlen($alphabet) - 1)];
  }
  $_SESSION['captcha_text'] = $txt;
  $_SESSION['captcha_ts'] = time();
}

function verifyCaptcha(string $input): bool {
  $expected = isset($_SESSION['captcha_text']) ? (string)$_SESSION['captcha_text'] : '';
  $ts = isset($_SESSION['captcha_ts']) ? (int)$_SESSION['captcha_ts'] : 0;
  if ($expected === '' || !$ts) return false;
  if (time() - $ts > 10 * 60) return false; // 10 minutes
  $a = strtoupper(trim($input));
  $b = strtoupper(trim($expected));
  return $a !== '' && hash_equals($b, $a);
}

function setOtpForEmail(string $email): string {
  $otp = (string)random_int(100000, 999999);
  $_SESSION['otp_email'] = $email;
  $_SESSION['otp_hash'] = password_hash($otp, PASSWORD_DEFAULT);
  // Store plain OTP in session for local/dev reliability (unset on success).
  $_SESSION['otp_code'] = $otp;
  $_SESSION['otp_expires'] = time() + 10 * 60; // 10 minutes
  $_SESSION['otp_tries'] = 0;
  return $otp;
}

function clearOtp(): void {
  unset($_SESSION['otp_email'], $_SESSION['otp_hash'], $_SESSION['otp_code'], $_SESSION['otp_expires'], $_SESSION['otp_tries']);
}

function sendOtpEmail(string $toEmail, string $otp): bool {
  $toEmail = trim($toEmail);
  if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) return false;

  $autoload = __DIR__ . '/vendor/autoload.php';
  if (!is_file($autoload)) return false;
  require_once $autoload;

  $cfgPath = __DIR__ . '/smtp_config.php';
  if (!is_file($cfgPath)) return false;
  $cfg = require $cfgPath;
  if (!is_array($cfg)) return false;

  $host = (string)($cfg['host'] ?? '');
  $port = (int)($cfg['port'] ?? 587);
  $secure = (string)($cfg['secure'] ?? 'tls');
  $user = (string)($cfg['username'] ?? '');
  $pass = (string)($cfg['password'] ?? '');
  $fromEmail = (string)($cfg['from_email'] ?? $user);
  $fromName  = (string)($cfg['from_name'] ?? 'Bejewelry');
  if ($host === '' || $user === '' || $pass === '' || $fromEmail === '') return false;

  $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
  try {
    $mail->isSMTP();
    $mail->Host = $host;
    $mail->SMTPAuth = true;
    $mail->Username = $user;
    $mail->Password = $pass;
    $mail->SMTPSecure = $secure; // 'tls'
    $mail->Port = $port;         // 587
    $mail->CharSet = 'UTF-8';

    $mail->setFrom($fromEmail, $fromName);
    $mail->addAddress($toEmail);

    $mail->Subject = 'Your Bejewelry OTP Code';
    $mail->Body =
      "Your OTP code is: {$otp}\r\n\r\n" .
      "This code expires in 10 minutes.\r\n" .
      "If you did not request this, you can ignore this email.\r\n";

    $mail->send();
    return true;
  } catch (\Throwable $e) {
    return false;
  }
}

if (!isset($_SESSION['captcha_text'])) {
  newCaptcha($alphabet);
}

$step = isset($_SESSION['otp_hash']) ? 2 : 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Note: CSRF validation skipped because this page uses CAPTCHA for anti-spam
  // and is not a logged-in endpoint
  
  $action = (string)($_POST['action'] ?? '');

  if ($action === 'request_otp') {
    $email = trim($_POST['email'] ?? '');
    $captcha = trim($_POST['captcha'] ?? '');

    if (!$email || !$captcha) {
      $error = 'Please fill in all fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $error = 'Please enter a valid email address.';
    } elseif (!verifyCaptcha($captcha)) {
      $error = 'Incorrect CAPTCHA. Please try again.';
      newCaptcha($alphabet);
    } else {
      $stmt = db()->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
      $stmt->execute([$email]);
      $row = $stmt->fetch(PDO::FETCH_ASSOC);
      if (!$row) {
        $error = 'No account found with that email.';
        newCaptcha($alphabet);
      } else {
        $otp = setOtpForEmail($email);
        $step = 2;
        $sent = sendOtpEmail($email, $otp);
        if (!$sent) {
          $error = 'Could not send OTP email. Please configure SMTP in smtp_config.php and try again.';
          clearOtp();
          $step = 1;
        } else {
          $message = 'OTP sent to your email. Enter the 6-digit code to continue.';
        }
        newCaptcha($alphabet);
      }
    }
  } elseif ($action === 'reset_password') {
    $otp = preg_replace('/\D+/', '', (string)($_POST['otp'] ?? ''));
    $pw  = (string)($_POST['password'] ?? '');
    $pw2 = (string)($_POST['password_confirm'] ?? '');

    $email = isset($_SESSION['otp_email']) ? (string)$_SESSION['otp_email'] : '';
    $hash  = isset($_SESSION['otp_hash']) ? (string)$_SESSION['otp_hash'] : '';
    $plain = isset($_SESSION['otp_code']) ? (string)$_SESSION['otp_code'] : '';
    $exp   = isset($_SESSION['otp_expires']) ? (int)$_SESSION['otp_expires'] : 0;
    $tries = isset($_SESSION['otp_tries']) ? (int)$_SESSION['otp_tries'] : 0;

    if ($email === '' || $hash === '' || !$exp) {
      $error = 'Please request a new OTP.';
      clearOtp();
      $step = 1;
    } elseif (time() > $exp) {
      $error = 'OTP expired. Please request a new one.';
      clearOtp();
      $step = 1;
    } elseif ($tries >= 5) {
      $error = 'Too many attempts. Please request a new OTP.';
      clearOtp();
      $step = 1;
    } elseif (!$otp || !$pw || !$pw2) {
      $error = 'Please fill in all fields.';
      $step = 2;
    } elseif ($otp === '' || (!hash_equals($plain, $otp) && !password_verify($otp, $hash))) {
      $_SESSION['otp_tries'] = $tries + 1;
      $error = 'Invalid OTP. Please try again.';
      $step = 2;
    } else {
      $pwdCheck = bejewelry_validate_password($pw);
      if (empty($pwdCheck['ok'])) {
        $error = implode(' ', $pwdCheck['errors']);
        $step = 2;
      } elseif ($pw !== $pw2) {
        $error = 'Passwords do not match.';
        $step = 2;
      } else {
        $stmt = db()->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
          $error = 'No account found with that email.';
          clearOtp();
          $step = 1;
        } else {
          $hashPw = password_hash($pw, PASSWORD_DEFAULT);
          $up = db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
          $up->execute([$hashPw, $row['id']]);
          clearOtp();
          $step = 1;
          $message = 'Your password has been updated. You can now sign in.';
        }
      }
    }
  } elseif ($action === 'restart') {
    clearOtp();
    newCaptcha($alphabet);
    $step = 1;
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Reset Password — Bejewelry</title>
  <link rel="stylesheet" href="css/fonts.css">
  <link rel="stylesheet" href="css/styles.css">
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

<div class="login-wrap" style="min-height:100vh;display:flex;align-items:center;justify-content:center;padding:40px 16px">
  <div class="login-form-wrap" style="max-width:640px;width:100%;display:block">
    <div class="login-form-inner" style="max-width:520px;margin:0 auto">
      <h1 style="font-family:var(--fd);font-size:1.5rem;margin-bottom:16px;color:var(--dark)">Account Settings</h1>

      <div style="background:var(--white);border-radius:var(--r-lg);border:1px solid var(--border);padding:24px 26px;margin-bottom:20px;box-shadow:var(--sh-xs)">
        <h2 style="font-family:var(--fd);font-size:1rem;margin-bottom:4px;color:var(--dark)">Reset Password</h2>
        <p style="font-size:.8rem;color:var(--muted-light);margin-bottom:18px">Choose a new password for your Bejewelry account.</p>

        <?php if ($error): ?>
          <p class="muted" style="margin-bottom:14px;color:var(--danger);font-size:.83rem"><?= htmlspecialchars($error) ?></p>
        <?php elseif ($message): ?>
          <p class="muted" style="margin-bottom:14px;color:var(--success);font-size:.83rem"><?= htmlspecialchars($message) ?></p>
        <?php endif; ?>

        <?php if ($step === 1): ?>
          <form action="forgot_password.php" method="post">
            <input type="hidden" name="action" value="request_otp">
            <div class="fg">
              <label class="flabel">Email Address</label>
              <input class="finput" type="email" name="email" placeholder="you@email.com" autocomplete="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>

            <div class="fg" style="margin-bottom:12px">
              <label class="flabel">CAPTCHA</label>
              <div style="display:flex;gap:10px;align-items:center">
                <input class="finput" name="captcha" placeholder="Enter code" autocomplete="off" required style="flex:1">
                <img src="captcha_image.php?ts=<?= time() ?>" alt="CAPTCHA" style="height:44px;border-radius:10px;border:1px solid var(--border);background:var(--blush);padding:2px">
              </div>
              <div class="fhint">Can’t read it? Refresh the page to get a new code.</div>
            </div>

            <button type="submit" class="btn btn-primary btn-full btn-lg" style="justify-content:center">Send OTP</button>
          </form>
        <?php else: ?>
          <form action="forgot_password.php" method="post">
            <input type="hidden" name="action" value="reset_password">
            <div class="fg">
              <label class="flabel">Email Address</label>
              <input class="finput" type="email" value="<?= htmlspecialchars((string)($_SESSION['otp_email'] ?? '')) ?>" readonly>
            </div>
            <div class="fg">
              <label class="flabel">OTP Code</label>
              <input class="finput" name="otp" inputmode="numeric" placeholder="6-digit code" autocomplete="one-time-code" required>
              <div class="fhint">Check your inbox (and spam/junk folder) for the OTP email.</div>
            </div>
            <div class="fg">
              <label class="flabel">New Password</label>
              <input class="finput" type="password" name="password" placeholder="New password" required>
              <div class="fhint"><?= htmlspecialchars($__pwd_hint, ENT_QUOTES, 'UTF-8') ?></div>
            </div>
            <div class="fg" style="margin-bottom:18px">
              <label class="flabel">Confirm New Password</label>
              <input class="finput" type="password" name="password_confirm" placeholder="Repeat new password" required>
            </div>
            <button type="submit" class="btn btn-primary btn-full btn-lg" style="justify-content:center">Update Password</button>
          </form>
          <form action="forgot_password.php" method="post" style="margin-top:10px">
            <input type="hidden" name="action" value="restart">
            <button type="submit" class="btn btn-ghost btn-full btn-lg" style="justify-content:center;border:1.5px solid var(--border-mid)">Request new OTP</button>
          </form>
        <?php endif; ?>
      </div>

      <div style="background:var(--white);border-radius:var(--r-lg);border:1px solid var(--border);padding:20px 24px">
        <h3 style="font-family:var(--fd);font-size:.95rem;margin-bottom:6px;color:var(--dark)">Need Help?</h3>
        <p style="font-size:.8rem;color:var(--muted-light);margin-bottom:10px">If you no longer have access to your email, please contact our support team to recover your account.</p>
        <p class="t-sm muted">Remembered your password? <a href="login.php" class="text-rose">Back to sign in</a></p>
      </div>
    </div>
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

