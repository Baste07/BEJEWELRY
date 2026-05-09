<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc.php';
require_once __DIR__ . '/../api/registration_helpers.php';

if (current_user_id()) {
    $u = current_user();
    $role = (string) ($u['role'] ?? 'customer');
    if ($role === 'courier') {
        header('Location: courier_portal.php');
        exit;
    }
    header('Location: ../' . bejewelry_staff_post_login_path($role));
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validate();
    $first = trim((string) ($_POST['first_name'] ?? ''));
    $last = trim((string) ($_POST['last_name'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $phone = trim((string) ($_POST['phone'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    $confirm = (string) ($_POST['password_confirm'] ?? '');

    if ($first === '' || $last === '' || $email === '' || $phone === '' || $password === '' || $confirm === '') {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (!preg_match('/^\+?[0-9]{10,15}$/', preg_replace('/\s+/', '', $phone))) {
      $error = 'Please enter a valid phone number (10-15 digits).';
    } else {
        $pwdErr = validate_password_complexity($password);
        if ($pwdErr !== null) {
            $error = $pwdErr;
        } elseif ($password !== $confirm) {
            $error = 'Passwords do not match.';
        } else {
            $chk = db()->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
            $chk->execute([$email]);
            if ($chk->fetch()) {
                $error = 'Email is already used.';
            }
        }
    }

    if ($error === '') {
      $encUser = bejewelry_encrypt_user_private_fields([
        'phone' => $phone !== '' ? $phone : null,
      ]);
        $ins = db()->prepare(
            'INSERT INTO users (first_name, last_name, email, phone, password_hash, role, email_verified_at) VALUES (?,?,?,?,?,?,NOW())'
        );
        $ins->execute([
            $first,
            $last,
            $email,
        $encUser['phone'],
            password_hash($password, PASSWORD_BCRYPT),
            'courier',
        ]);

        $uid = (int) db()->lastInsertId();
        $_SESSION['pending_totp_redirect'] = 'orderManager/courier_portal.php';
        $_SESSION['pending_totp_setup_uid'] = $uid;
        $_SESSION['totp_setup_attempts'] = 0;
        header('Location: ../setup_2fa.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Courier Sign Up — Bejewelry</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;700&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
  <style>
    *{box-sizing:border-box} body{margin:0;font-family:'DM Sans',system-ui,sans-serif;background:linear-gradient(135deg,#fef6f3,#fdeff5);color:#231418;min-height:100vh;display:grid;place-items:center;padding:24px}
    .card{width:min(680px,100%);background:#fff;border:1px solid #ead7dc;border-radius:22px;padding:28px;box-shadow:0 10px 36px rgba(120,30,50,.12)}
    h1{font-family:'Playfair Display',serif;margin:0 0 6px;font-size:1.8rem}
    .sub{margin:0 0 18px;color:#8f707c;font-size:.92rem}
    .row{display:grid;grid-template-columns:1fr 1fr;gap:12px}
    .fg{margin-bottom:14px}.fl{display:block;font-size:.62rem;font-weight:700;text-transform:uppercase;letter-spacing:.12em;color:#8f707c;margin-bottom:7px}
    .fi{width:100%;padding:12px 14px;border:1.5px solid #dfc8d1;border-radius:13px;font:inherit;outline:none}
    .fi:focus{border-color:#d96070}
    .btn{width:100%;padding:12px 16px;border:none;border-radius:999px;background:linear-gradient(135deg,#d96070,#b03050);color:#fff;font-weight:700;letter-spacing:.06em;text-transform:uppercase;cursor:pointer}
    .err{margin:0 0 14px;padding:10px 12px;border-radius:12px;background:#ffe9e9;border:1px solid #e8b5b5;color:#7b2121;font-size:.9rem}
    .links{margin-top:14px;font-size:.86rem;color:#7a5d67;display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap}
    a{color:#b03050;text-decoration:none}
    a:hover{text-decoration:underline}
    @media (max-width:700px){.row{grid-template-columns:1fr}}
  </style>
  <script>
    (function () {
      try {
        history.scrollRestoration = 'manual';
      } catch (e) {}
      try {
        if (sessionStorage.getItem('courier_register_scroll_y') !== null) {
          document.documentElement.style.opacity = '0';
        }
      } catch (e) {}
    })();
  </script>
</head>
<body>
  <div class="card">
    <h1>Courier Sign Up</h1>
    <p class="sub">Create your courier account and start confirming deliveries.</p>
    <?php if ($error !== ''): ?>
      <p class="err"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
    <?php endif; ?>
    <form method="post">
        <?php echo csrf_token_field(); ?>
      <div class="row">
        <div class="fg">
          <label class="fl">First name</label>
          <input class="fi" type="text" name="first_name" required value="<?php echo htmlspecialchars((string) ($_POST['first_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
        </div>
        <div class="fg">
          <label class="fl">Last name</label>
          <input class="fi" type="text" name="last_name" required value="<?php echo htmlspecialchars((string) ($_POST['last_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
        </div>
      </div>
      <div class="row">
        <div class="fg">
          <label class="fl">Email</label>
          <input class="fi" type="email" name="email" required value="<?php echo htmlspecialchars((string) ($_POST['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
        </div>
        <div class="fg">
          <label class="fl">Phone</label>
          <input class="fi" type="text" name="phone" required pattern="\+?[0-9]{10,15}" title="Enter 10-15 digits, optional leading +." value="<?php echo htmlspecialchars((string) ($_POST['phone'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
        </div>
      </div>
      <div class="row">
        <div class="fg">
          <label class="fl">Password</label>
          <input class="fi" type="password" id="regPwd" name="password" required minlength="8" autocomplete="new-password" pattern="(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])(?=.*[^A-Za-z0-9_]).{8,}" title="Use at least 8 characters with uppercase, lowercase, number, and a symbol like ! @ # $ %." oninput="checkPwdStrength(this.value)">
        </div>
        <div class="fg">
          <label class="fl">Confirm password</label>
          <input class="fi" type="password" name="password_confirm" required autocomplete="new-password">
        </div>
      </div>
      <p class="sub" style="margin-top:-6px;margin-bottom:10px;font-size:.82rem">Use 8+ characters with uppercase, lowercase, number, and a symbol.</p>
      <div id="pwdStrengthBar" style="height:4px;border-radius:2px;background:#ead7dc;margin-top:-8px;margin-bottom:16px;overflow:hidden">
        <div id="pwdBar" style="height:100%;width:0;transition:width .3s,background .3s;border-radius:2px"></div>
      </div>
      <button class="btn" type="submit">Create Courier Account</button>
    </form>
    <div class="links">
      <a href="courier_login.php">Already have a courier account?</a>
      <a href="../login.php">Back to main login</a>
    </div>
  </div>
  <script>
  function checkPwdStrength(val) {
    const bar = document.getElementById('pwdBar');
    if (!bar) return;
    let score = 0;
    if (val.length >= 8) score++;
    if (/[A-Z]/.test(val)) score++;
    if (/[a-z]/.test(val)) score++;
    if (/[0-9]/.test(val)) score++;
    if (/[^a-zA-Z0-9_]/.test(val)) score++;
    const widths = ['20%','40%','60%','80%','100%'];
    const colors = ['#EE8888','#EEB850','#D4A84B','#88C858','#28A060'];
    bar.style.width = val ? (widths[score - 1] || '12%') : '0';
    bar.style.background = val ? (colors[score - 1] || '#EE8888') : '';
  }

  const scrollKey = 'courier_register_scroll_y';

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
