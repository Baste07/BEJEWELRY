<?php
require_once __DIR__ . '/inc.php';
require_once __DIR__ . '/auth_totp.php';
require_once __DIR__ . '/api/registration_helpers.php';

// If already logged in, send super admin to admin system and everyone else to shop
if (current_user_id()) {
    $u = current_user();
    $role = $u['role'] ?? 'customer';
  $adminRoles = ['admin', 'super_admin', 'manager', 'inventory', 'courier'];
  if (in_array($role, $adminRoles, true)) {
        header('Location: ' . bejewelry_staff_post_login_path($role));
    } else {
        header('Location: index.php');
    }
    exit;
}

$error = '';

$registerMode = strtolower(trim((string) ($_POST['register_mode'] ?? $_GET['register'] ?? 'customer')));
if (!in_array($registerMode, ['customer', 'courier'], true)) {
    $registerMode = 'customer';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_validate();
    $action = (string) ($_POST['auth_action'] ?? 'login');

    if ($action === 'courier_register') {
        $first = trim((string) ($_POST['first_name'] ?? ''));
        $last = trim((string) ($_POST['last_name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $phone = trim((string) ($_POST['phone'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $confirm = (string) ($_POST['password_confirm'] ?? '');
      $agreeTerms = !empty($_POST['agree_terms']);
      $captcha = trim((string) ($_POST['g-recaptcha-response'] ?? ''));
        $registerMode = 'courier';

      if ($first === '' || $last === '' || $email === '' || $phone === '' || $password === '' || $confirm === '') {
            $error = 'Please fill in all required fields.';
      } elseif (!$agreeTerms) {
        $error = 'Please agree to the Terms and Privacy Policy.';
      } elseif (!verify_recaptcha_v2($captcha)) {
        $error = 'Please complete the CAPTCHA verification.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            $phoneDigits = preg_replace('/\s+/', '', $phone);
            if (!preg_match('/^\+?[0-9]{10,15}$/', $phoneDigits)) {
                $error = 'Please enter a valid phone number (10-15 digits).';
            } else {
              $pwdCheck = bejewelry_validate_password($password);
              if (empty($pwdCheck['ok'])) {
                $error = implode(' ', $pwdCheck['errors'] ?? ['Password does not meet the configured policy.']);
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
            header('Location: setup_2fa.php');
            exit;
        }
    } elseif ($action === 'login') {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        if (!$email || !$password) {
            $error = 'Please fill in email and password.';
        } else {
            $stmt = db()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            if ($user && bejewelry_is_account_archived($user)) {
              $error = 'This account is archived. Please contact a super admin.';
            } elseif ($user && bejewelry_is_account_locked($user)) {
              $error = 'This account is locked. Please contact a super admin.';
            } elseif (!$user || empty($user['password_hash']) || !password_verify($password, $user['password_hash'])) {
              if ($user && !bejewelry_is_account_locked($user)) {
                $lockState = bejewelry_register_login_failure((int) $user['id']);
                $maxAttempts = bejewelry_get_login_max_attempts();
                if (!empty($lockState['locked'])) {
                  $error = 'Account locked after ' . $maxAttempts . ' failed attempts. Please contact a super admin.';
                } else {
                  $remaining = max(0, $maxAttempts - (int) $lockState['attempts']);
                  $error = 'Invalid email or password.' . ($remaining > 0 ? ' ' . $remaining . ' attempt' . ($remaining === 1 ? '' : 's') . ' left before lock.' : '');
                }
              } else {
                $error = 'Invalid email or password.';
              }
            } elseif (($user['role'] ?? '') === 'customer' && empty($user['email_verified_at'])) {
                $error = 'Please verify your email before signing in. Check your inbox for the activation link.';
            } else {
              bejewelry_reset_login_failures((int) $user['id']);
                $_SESSION['pending_totp_redirect'] = $_GET['redirect'] ?? 'index.php';
                $uid = (int) $user['id'];
                if (empty($user['totp_secret'])) {
                    $_SESSION['pending_totp_setup_uid'] = $uid;
                    $_SESSION['totp_setup_attempts'] = 0;
                    header('Location: setup_2fa.php');
                    exit;
                }
                $_SESSION['pending_totp_uid'] = $uid;
                $_SESSION['pending_totp_attempts'] = 0;
                header('Location: login_2fa.php');
                exit;
            }
        }
    }
}

$redirectParam = isset($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : '';

// Build a human-friendly password hint from current policy
$__pwd_policy = bejewelry_get_password_policy();
$__pwd_hint_parts = [];
if (empty($__pwd_policy['enabled'])) {
  $__pwd_hint = 'No specific password requirements.';
} else {
  $__pwd_hint_parts[] = 'at least ' . (int)$__pwd_policy['min_length'] . ' characters';
  $__req = [];
  if (!empty($__pwd_policy['require_upper'])) $__req[] = 'uppercase';
  if (!empty($__pwd_policy['require_lower'])) $__req[] = 'lowercase';
  if (!empty($__pwd_policy['require_number'])) $__req[] = 'a number';
  if (!empty($__pwd_policy['require_special'])) $__req[] = 'a special character';
  if (!empty($__req)) {
    $__pwd_hint_parts[] = 'include ' . implode(', ', $__req);
  }
  $__pwd_hint = 'Use ' . implode(' and ', $__pwd_hint_parts) . '.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<meta name="csrf-token" content="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
<title>Sign In — Bejewelry</title>
<script>
  window.__recaptchaWidgetId = null;
  window.__courierRecaptchaWidgetId = null;
  window.__recaptchaApiLoaded = false;

  function onRecaptchaApiLoaded() {
    window.__recaptchaApiLoaded = true;
    if (typeof window.ensureRegisterCaptcha === 'function') {
      window.ensureRegisterCaptcha();
    }
      if (typeof window.ensureCourierRegisterCaptcha === 'function') {
        window.ensureCourierRegisterCaptcha();
      }
  }

  (function loadRecaptchaScript() {
    function appendScript(src, onError) {
      var s = document.createElement('script');
      s.src = src;
      s.async = true;
      s.defer = true;
      if (typeof onError === 'function') {
        s.onerror = onError;
      }
      document.head.appendChild(s);
    }

    appendScript(
      'https://www.google.com/recaptcha/api.js?onload=onRecaptchaApiLoaded&render=explicit',
      function () {
        appendScript('https://www.recaptcha.net/recaptcha/api.js?onload=onRecaptchaApiLoaded&render=explicit');
      }
    );
  })();
</script>
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
      <div class="brand-tagline" style="font-family:var(--fd);font-size:2.2rem;font-weight:500;color:var(--white);line-height:1.3;margin-bottom:16px"><em>Every piece</em><br>tells a story.</div>
      <p class="brand-body" style="font-size:.9rem;line-height:1.85;margin-bottom:40px">Handcrafted jewelry that celebrates your most meaningful moments. Sign in to access exclusive collections and track your orders.</p>
      <div style="display:flex;flex-direction:column;gap:14px">
        <div class="brand-feature" style="display:flex;align-items:center;gap:12px;font-size:.84rem"><span style="font-size:1.2rem">💎</span>Access exclusive member collections</div>
        <div class="brand-feature" style="display:flex;align-items:center;gap:12px;font-size:.84rem"><span style="font-size:1.2rem">🚚</span>Free shipping on orders ₱2,000+</div>
        <div class="brand-feature" style="display:flex;align-items:center;gap:12px;font-size:.84rem"><span style="font-size:1.2rem">♡</span>Save your wishlist & order history</div>
        <div class="brand-feature" style="display:flex;align-items:center;gap:12px;font-size:.84rem"><span style="font-size:1.2rem">🎁</span>Birthday & anniversary surprises</div>
      </div>
    </div>
  </div>

  <div class="login-form-wrap">
    <div class="login-form-inner">
      <div style="margin-bottom:32px">
        <div style="font-family:var(--fd);font-size:1.4rem;font-weight:700;color:var(--dark)">Bejewelry</div>
        <div style="font-size:.55rem;font-weight:700;letter-spacing:.2em;text-transform:uppercase;color:var(--rose)">Fine Jewelry</div>
      </div>

      <div style="display:flex;margin-bottom:32px;border-bottom:1.5px solid var(--border);gap:4px">
        <div id="tabLogin" class="tab active" onclick="showTab('login')">Sign In</div>
        <div id="tabReg" class="tab" onclick="showTab('register')">Create Account</div>
      </div>

      <div id="loginForm">
        <h2 style="font-size:1.6rem;margin-bottom:4px">Welcome back</h2>
        <p style="margin-bottom:24px;font-size:.85rem">Sign in to your Bejewelry account</p>
        <?php if (isset($_GET['activated'])): ?>
        <p class="muted" style="margin-bottom:16px;color:var(--success)">Your email is verified. You can sign in now.</p>
        <?php endif; ?>
        <?php if (isset($_GET['activation_error'])): ?>
        <p class="muted" style="margin-bottom:16px;color:var(--danger)"><?= htmlspecialchars((string) ($_GET['activation_error'] ?? '')) ?></p>
        <?php endif; ?>
        <?php if (isset($_GET['reg_pending'])): ?>
        <p class="muted" style="margin-bottom:16px;color:var(--success)">Check your email for a link to activate your account, then sign in here.</p>
        <?php endif; ?>
        <?php if (!empty($_GET['error'])): ?>
        <p class="muted" style="margin-bottom:16px;color:var(--danger)"><?= htmlspecialchars((string) $_GET['error'], ENT_QUOTES, 'UTF-8') ?></p>
        <?php endif; ?>
        <?php if ($error): ?>
        <p class="muted" style="margin-bottom:16px;color:var(--danger)"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>
        <form action="login.php<?= htmlspecialchars($redirectParam) ?>" method="post" id="loginFormPost">
          <input type="hidden" name="auth_action" value="login">
                    <?php echo csrf_token_field(); ?>
          <div class="fg">
            <label class="flabel">Email Address</label>
            <input class="finput" type="email" name="email" id="loginEmail" placeholder="you@email.com" autocomplete="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
          </div>
          <div class="fg" style="position:relative">
            <label class="flabel">Password</label>
            <input class="finput" type="password" id="loginPwd" name="password" placeholder="••••••••" autocomplete="current-password" required>
            <button type="button" onclick="togglePwd('loginPwd',this)" style="position:absolute;right:13px;bottom:12px;background:none;border:none;cursor:pointer;color:var(--muted-light);font-size:.78rem">Show</button>
          </div>
          <div class="flex jb items-center mb5">
            <label class="fp-opt"><input type="checkbox" name="remember" id="rememberMe" style="accent-color:var(--rose)"><span class="t-sm muted"> Remember me</span></label>
            <a href="forgot_password.php" class="t-sm text-rose">Forgot password?</a>
          </div>
          <button type="submit" class="btn btn-primary btn-full btn-lg" style="justify-content:center">Sign In</button>
        </form>
        <div style="margin-top:12px;display:flex;justify-content:center">
          <a href="#" class="btn btn-ghost btn-full btn-lg" style="justify-content:center;max-width:320px;text-decoration:none" onclick="event.preventDefault();showTab('register');showRegisterMode('courier')">Courier Sign Up</a>
        </div>
        <p class="tc mt5 t-sm muted">Locked account? <a href="unlock_account.php" class="text-rose">Click here</a></p>
        <p class="tc mt2 t-sm muted"><a href="#" class="text-rose" onclick="event.preventDefault();bejOpenPrivacyPolicyModal()">Privacy Policy</a></p>
      </div>

      <div id="registerForm" class="hidden">
        <h2 style="font-size:1.6rem;margin-bottom:4px">Create account</h2>
        <p style="margin-bottom:24px;font-size:.85rem">Join thousands of Bejewelry members</p>
        <?php if (isset($_GET['reg_error'])): ?>
        <p class="muted" style="margin-bottom:16px;color:var(--danger)"><?= htmlspecialchars($_GET['reg_error']) ?></p>
        <?php endif; ?>
        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px">
          <button type="button" id="regModeCustomer" class="btn btn-ghost btn-sm" style="width:auto;padding:8px 14px" onclick="showRegisterMode('customer')">Customer</button>
          <button type="button" id="regModeCourier" class="btn btn-ghost btn-sm" style="width:auto;padding:8px 14px" onclick="showRegisterMode('courier')">Courier</button>
        </div>

        <div id="customerRegisterPane">
          <form action="register.php" method="post" id="registerFormPost">
                        <?php echo csrf_token_field(); ?>
            <div class="frow">
              <div class="fg"><label class="flabel">First Name</label><input class="finput" name="first_name" placeholder="First name" autocomplete="given-name" required></div>
              <div class="fg"><label class="flabel">Last Name</label><input class="finput" name="last_name" placeholder="Last name" autocomplete="family-name" required></div>
            </div>
            <div class="fg">
              <label class="flabel">Email Address</label>
              <input class="finput" type="email" name="email" placeholder="you@email.com" autocomplete="email" required>
            </div>
            <div class="fg" style="position:relative">
              <label class="flabel">Password</label>
              <input class="finput" type="password" id="regPwd" name="password" placeholder="Strong password" minlength="8" autocomplete="new-password" required pattern="(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])(?=.*[^A-Za-z0-9_]).{8,}" title="Use at least 8 characters with uppercase, lowercase, number, and a symbol like ! @ # $ %." oninput="checkPwdStrength(this.value)">
              <button type="button" onclick="togglePwd('regPwd',this)" style="position:absolute;right:13px;bottom:12px;background:none;border:none;cursor:pointer;color:var(--muted-light);font-size:.78rem">Show</button>
            </div>
            <p class="t-sm muted" style="margin-top:-8px;margin-bottom:8px"><?= htmlspecialchars($__pwd_hint, ENT_QUOTES, 'UTF-8') ?></p>
            <div id="pwdStrengthBar" style="height:4px;border-radius:2px;background:var(--border);margin-top:-12px;margin-bottom:14px;overflow:hidden">
              <div id="pwdBar" style="height:100%;width:0;transition:width .3s,background .3s;border-radius:2px"></div>
            </div>
            <div class="fg">
              <label class="flabel">Confirm Password</label>
              <input class="finput" type="password" name="password_confirm" placeholder="Repeat password" autocomplete="new-password" required>
            </div>
            <label class="fp-opt mb4">
              <input type="checkbox" name="agree_terms" value="1" required style="accent-color:var(--rose)">
              <span class="t-sm muted"> I agree to the Terms and <a href="#" onclick="event.preventDefault();bejOpenPrivacyPolicyModal()" class="text-rose">Privacy Policy</a></span>
            </label>
            <div id="registerCaptcha" class="mb4" data-sitekey="<?= htmlspecialchars(RECAPTCHA_SITE_KEY, ENT_QUOTES, 'UTF-8') ?>"></div>
            <p id="captchaLoadHint" class="t-sm" style="margin-top:-8px;margin-bottom:12px;color:var(--danger);display:none">
              CAPTCHA could not load. Check internet/extension settings then refresh the page.
            </p>
            <button type="submit" class="btn btn-primary btn-full btn-lg" style="justify-content:center">Create Account</button>
          </form>
        </div>

        <div id="courierRegisterPane" class="hidden">
          <form action="orderManager/courier_register.php" method="post" id="courierRegisterForm">
                        <?php echo csrf_token_field(); ?>
            <div class="frow">
              <div class="fg"><label class="flabel">First Name</label><input class="finput" name="first_name" placeholder="First name" autocomplete="given-name" required value="<?= htmlspecialchars((string) ($_POST['first_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
              <div class="fg"><label class="flabel">Last Name</label><input class="finput" name="last_name" placeholder="Last name" autocomplete="family-name" required value="<?= htmlspecialchars((string) ($_POST['last_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
            </div>
            <div class="frow">
              <div class="fg"><label class="flabel">Email Address</label><input class="finput" type="email" name="email" placeholder="courier@email.com" autocomplete="email" required value="<?= htmlspecialchars((string) ($_POST['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
              <div class="fg"><label class="flabel">Phone</label><input class="finput" type="text" name="phone" placeholder="+639123456789" autocomplete="tel" required value="<?= htmlspecialchars((string) ($_POST['phone'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"></div>
            </div>
            <div class="fg" style="position:relative">
              <label class="flabel">Password</label>
              <input class="finput" type="password" id="courierRegPwd" name="password" placeholder="Strong password" minlength="8" autocomplete="new-password" required pattern="(?=.*[A-Z])(?=.*[a-z])(?=.*[0-9])(?=.*[^A-Za-z0-9_]).{8,}" title="Use at least 12 characters and include uppercase, lowercase, a number, and a special character." oninput="checkCourierPwdStrength(this.value)">
              <button type="button" onclick="togglePwd('courierRegPwd',this)" style="position:absolute;right:13px;bottom:12px;background:none;border:none;cursor:pointer;color:var(--muted-light);font-size:.78rem">Show</button>
            </div>
            <p class="t-sm muted" style="margin:8px 0 8px">Use at least 12 characters and include uppercase, lowercase, a number, a special character.</p>
            <div id="courierPwdStrengthBar" style="height:4px;border-radius:2px;background:var(--border);margin-top:0;margin-bottom:14px;overflow:hidden">
              <div id="courierPwdBar" style="height:100%;width:0;transition:width .3s,background .3s;border-radius:2px"></div>
            </div>
            <div class="fg">
              <label class="flabel">Confirm Password</label>
              <input class="finput" type="password" name="password_confirm" placeholder="Repeat password" autocomplete="new-password" required>
            </div>
            <label class="fp-opt mb4">
              <input type="checkbox" name="agree_terms" value="1" required style="accent-color:var(--rose)">
              <span class="t-sm muted"> I agree to the Terms and <a href="#" onclick="event.preventDefault();bejOpenPrivacyPolicyModal()" class="text-rose">Privacy Policy</a></span>
            </label>
            <div id="courierRegisterCaptcha" class="mb4" data-sitekey="<?= htmlspecialchars(RECAPTCHA_SITE_KEY, ENT_QUOTES, 'UTF-8') ?>"></div>
            <button type="submit" class="btn btn-primary btn-full btn-lg" style="justify-content:center">Create Courier Account</button>
          </form>
        </div>
        <p class="tc mt5 t-sm muted">Already have an account? <a href="#" class="text-rose" onclick="event.preventDefault();showTab('login')">Sign in</a></p>
      </div>
    </div>
  </div>
</div>

<div id="privacyPolicyModal" style="position:fixed;inset:0;display:none;align-items:flex-start;justify-content:center;background:rgba(36,20,24,.62);backdrop-filter:blur(6px);z-index:10050;padding:18px;overflow:auto" aria-hidden="true" role="dialog" aria-label="Privacy Policy">
  <div style="width:min(760px,100%);max-height:calc(100vh - 36px);overflow:hidden;background:#fff;border:1px solid #ead8df;border-radius:22px;box-shadow:0 20px 60px rgba(36,20,24,.18);position:relative;display:flex;flex-direction:column">
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px;padding:22px 24px 16px;border-bottom:1px solid #efdce2;background:linear-gradient(180deg,#fff 0%,#fff7fa 100%);position:sticky;top:0;z-index:2">
      <div>
        <h2 style="margin:0 0 6px;font-family:var(--fd);font-size:1.8rem;color:#241418">Privacy Policy</h2>
        <p style="margin:0;color:#7a5e68;font-size:.88rem">Last updated: May 3, 2026</p>
      </div>
      <button type="button" id="privacyPolicyClose" aria-label="Close privacy policy" style="width:36px;height:36px;flex-shrink:0;border:1px solid #e0cbd3;border-radius:12px;background:#fff;color:#7a5e68;cursor:pointer;font-size:1.1rem;box-shadow:0 2px 8px rgba(36,20,24,.06)">×</button>
    </div>
    <div style="padding:20px 24px 24px;overflow:auto;line-height:1.7">
    <p style="margin:0 0 12px">This Privacy Policy explains how Bejewelry collects, uses, stores, and protects your personal information when you use this website and related services.</p>

    <h3 style="margin:22px 0 8px;font-family:var(--fd);font-size:1.12rem;color:#3b2129">1. Information We Collect</h3>
    <ul style="margin:0 0 14px 18px;padding:0">
      <li>Account details such as name, email address, and phone number.</li>
      <li>Shipping and order details including delivery address and order history.</li>
      <li>Security information such as login activity and authentication events.</li>
      <li>Technical data such as session identifiers and essential cookie preferences.</li>
    </ul>

    <h3 style="margin:22px 0 8px;font-family:var(--fd);font-size:1.12rem;color:#3b2129">2. How We Use Information</h3>
    <ul style="margin:0 0 14px 18px;padding:0">
      <li>To create and manage your account.</li>
      <li>To process orders, payments, and deliveries.</li>
      <li>To provide customer support and improve service reliability.</li>
      <li>To secure accounts, prevent fraud, and enforce platform policies.</li>
    </ul>

    <h3 style="margin:22px 0 8px;font-family:var(--fd);font-size:1.12rem;color:#3b2129">3. Data Security</h3>
    <p style="margin:0 0 12px">We use technical safeguards including encrypted transport (HTTPS/TLS), secure session cookies, and encryption of sensitive fields in the database where applicable.</p>

    <h3 style="margin:22px 0 8px;font-family:var(--fd);font-size:1.12rem;color:#3b2129">4. Cookies</h3>
    <p style="margin:0 0 12px">We use essential cookies required for authentication, session continuity, and security. Where available, you may also choose whether to allow non-essential analytics cookies.</p>

    <h3 style="margin:22px 0 8px;font-family:var(--fd);font-size:1.12rem;color:#3b2129">5. Data Sharing</h3>
    <p style="margin:0 0 12px">We do not sell personal information. Data may be shared only with service providers necessary to operate the platform, such as payment and delivery integrations, and only for legitimate business purposes.</p>

    <h3 style="margin:22px 0 8px;font-family:var(--fd);font-size:1.12rem;color:#3b2129">6. Data Retention</h3>
    <p style="margin:0 0 12px">We retain personal data only as long as needed for account operations, order fulfillment, legal obligations, dispute handling, and security auditing.</p>

    <h3 style="margin:22px 0 8px;font-family:var(--fd);font-size:1.12rem;color:#3b2129">7. Your Rights</h3>
    <p style="margin:0 0 12px">You may request updates or corrections to your account data through your profile settings. You may also contact us to request account deletion, subject to legal and transactional requirements.</p>

    <h3 style="margin:22px 0 8px;font-family:var(--fd);font-size:1.12rem;color:#3b2129">8. Policy Updates</h3>
    <p style="margin:0 0 12px">We may update this policy from time to time. Material changes will be posted on this page with an updated date.</p>

    <h3 style="margin:22px 0 8px;font-family:var(--fd);font-size:1.12rem;color:#3b2129">9. Contact</h3>
    <p style="margin:0 0 6px">For privacy concerns, please contact the Bejewelry support/admin team using available support channels in the platform.</p>
    </div>
  </div>
</div>

<script>
function bejOpenPrivacyPolicyModal() {
  const modal = document.getElementById('privacyPolicyModal');
  if (!modal) return;
  modal.style.display = 'flex';
  modal.setAttribute('aria-hidden', 'false');
  document.body.style.overflow = 'hidden';
}

function bejClosePrivacyPolicyModal() {
  const modal = document.getElementById('privacyPolicyModal');
  if (!modal) return;
  modal.style.display = 'none';
  modal.setAttribute('aria-hidden', 'true');
  document.body.style.overflow = '';
}

window.bejOpenPrivacyPolicyModal = bejOpenPrivacyPolicyModal;

document.addEventListener('DOMContentLoaded', function () {
  const modal = document.getElementById('privacyPolicyModal');
  const closeBtn = document.getElementById('privacyPolicyClose');
  if (closeBtn) {
    closeBtn.addEventListener('click', bejClosePrivacyPolicyModal);
  }
  if (modal) {
    modal.addEventListener('click', function (ev) {
      if (ev.target === modal) {
        bejClosePrivacyPolicyModal();
      }
    });
  }
  document.addEventListener('keydown', function (ev) {
    if (ev.key === 'Escape') {
      bejClosePrivacyPolicyModal();
    }
  });
});

function showTab(tab) {
  document.getElementById('tabLogin').classList.toggle('active', tab === 'login');
  document.getElementById('tabReg').classList.toggle('active', tab === 'register');
  document.getElementById('loginForm').classList.toggle('hidden', tab !== 'login');
  document.getElementById('registerForm').classList.toggle('hidden', tab !== 'register');
  if (tab === 'register') {
    showRegisterMode(window.__registerMode || 'customer');
    ensureRegisterCaptcha();
    ensureCourierRegisterCaptcha();
    setTimeout(showCaptchaLoadHintIfNeeded, 1500);
  }
}

function showRegisterMode(mode) {
  const selected = mode === 'courier' ? 'courier' : 'customer';
  window.__registerMode = selected;
  const customerPane = document.getElementById('customerRegisterPane');
  const courierPane = document.getElementById('courierRegisterPane');
  const customerBtn = document.getElementById('regModeCustomer');
  const courierBtn = document.getElementById('regModeCourier');
  if (customerPane) customerPane.classList.toggle('hidden', selected !== 'customer');
  if (courierPane) courierPane.classList.toggle('hidden', selected !== 'courier');
  if (customerBtn) customerBtn.classList.toggle('active', selected === 'customer');
  if (courierBtn) courierBtn.classList.toggle('active', selected === 'courier');
  if (selected === 'customer') {
    ensureRegisterCaptcha();
    setTimeout(showCaptchaLoadHintIfNeeded, 1500);
  } else {
    ensureCourierRegisterCaptcha();
  }
}

function ensureRegisterCaptcha() {
  const box = document.getElementById('registerCaptcha');
  if (!box) return;
  if (window.__recaptchaWidgetId !== null) return;
  if (typeof grecaptcha === 'undefined' || typeof grecaptcha.render !== 'function') return;
  const siteKey = box.getAttribute('data-sitekey') || '';
  if (!siteKey) return;
  window.__recaptchaWidgetId = grecaptcha.render('registerCaptcha', { sitekey: siteKey });
}

function showCaptchaLoadHintIfNeeded() {
  const hint = document.getElementById('captchaLoadHint');
  const registerHidden = document.getElementById('registerForm')?.classList.contains('hidden');
  if (!hint || registerHidden) return;
  if (window.__recaptchaWidgetId === null) {
    hint.style.display = 'block';
  } else {
    hint.style.display = 'none';
  }
}

function ensureCourierRegisterCaptcha() {
  const box = document.getElementById('courierRegisterCaptcha');
  if (!box) return;
  if (window.__courierRecaptchaWidgetId !== null) return;
  if (typeof grecaptcha === 'undefined' || typeof grecaptcha.render !== 'function') return;
  const siteKey = box.getAttribute('data-sitekey') || '';
  if (!siteKey) return;
  window.__courierRecaptchaWidgetId = grecaptcha.render('courierRegisterCaptcha', { sitekey: siteKey });
}
function togglePwd(id, btn) {
  const inp = document.getElementById(id);
  if (!inp) return;
  const show = inp.type === 'password';
  inp.type = show ? 'text' : 'password';
  btn.textContent = show ? 'Hide' : 'Show';
}
function checkPwdStrength(val) {
  const bar = document.getElementById('pwdBar');
  if (!bar) return;
  let score = 0;
  if (val.length >= 8) score++;
  if (/[A-Z]/.test(val)) score++;
  if (/[a-z]/.test(val)) score++;
  if (/[0-9]/.test(val)) score++;
  if (/[^a-zA-Z0-9_]/.test(val)) score++;
  const colors = ['#EE8888','#EEB850','#D4A84B','#88C858','#28A060'];
  bar.style.width = val ? (['20%','40%','60%','80%','100%'][score - 1] || '12%') : '0';
  bar.style.background = val ? (colors[score - 1] || '#EE8888') : '';
}

function checkCourierPwdStrength(val) {
  const bar = document.getElementById('courierPwdBar');
  if (!bar) return;
  let score = 0;
  if (val.length >= 8) score++;
  if (/[A-Z]/.test(val)) score++;
  if (/[a-z]/.test(val)) score++;
  if (/[0-9]/.test(val)) score++;
  if (/[^a-zA-Z0-9_]/.test(val)) score++;
  const colors = ['#EE8888','#EEB850','#D4A84B','#88C858','#28A060'];
  bar.style.width = val ? (['20%','40%','60%','80%','100%'][score - 1] || '12%') : '0';
  bar.style.background = val ? (colors[score - 1] || '#EE8888') : '';
}

function _rememberStoreRead() {
  try {
    return JSON.parse(localStorage.getItem('bejewelry_saved_credentials_v1') || '{}') || {};
  } catch {
    return {};
  }
}

function _rememberStoreWrite(store) {
  localStorage.setItem('bejewelry_saved_credentials_v1', JSON.stringify(store));
}

function _rememberKey(email) {
  return String(email || '').trim().toLowerCase();
}

function initRememberMe() {
  const form = document.getElementById('loginFormPost');
  const emailInput = document.getElementById('loginEmail');
  const passInput = document.getElementById('loginPwd');
  const remember = document.getElementById('rememberMe');
  if (!form || !emailInput || !passInput || !remember) return;

  const store = _rememberStoreRead();
  const lastEmail = localStorage.getItem('bejewelry_last_login_email') || '';

  // If server did not repopulate email (e.g., first visit), restore last remembered email.
  if (!emailInput.value && lastEmail) {
    emailInput.value = lastEmail;
  }

  const syncForEmail = () => {
    const key = _rememberKey(emailInput.value);
    const saved = key ? store[key] : null;
    if (saved && saved.password) {
      passInput.value = saved.password;
      remember.checked = true;
    } else if (!remember.checked) {
      passInput.value = '';
    }
  };

  syncForEmail();

  emailInput.addEventListener('change', syncForEmail);
  emailInput.addEventListener('blur', syncForEmail);

  form.addEventListener('submit', () => {
    const key = _rememberKey(emailInput.value);
    if (!key) return;

    if (remember.checked) {
      const updated = _rememberStoreRead();
      updated[key] = { password: passInput.value };
      _rememberStoreWrite(updated);
      localStorage.setItem('bejewelry_last_login_email', emailInput.value.trim());
    } else {
      const updated = _rememberStoreRead();
      if (updated[key]) {
        delete updated[key];
        _rememberStoreWrite(updated);
      }
      const last = localStorage.getItem('bejewelry_last_login_email') || '';
      if (_rememberKey(last) === key) {
        localStorage.removeItem('bejewelry_last_login_email');
      }
    }
  });
}

  const courierRegisterBoot = <?= json_encode((($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') && $registerMode === 'courier' && $error !== '') ?>;
  if (window.location.search.indexOf('register=courier') !== -1 || courierRegisterBoot) {
    window.__registerMode = 'courier';
    showTab('register');
  } else if (window.location.search.indexOf('reg_error=') !== -1) {
    showTab('register');
  }
initRememberMe();

if (window.location.search.indexOf('open_privacy=1') !== -1) {
  bejOpenPrivacyPolicyModal();
}
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
