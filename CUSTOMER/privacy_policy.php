<?php
require_once __DIR__ . '/inc.php';

header('Location: ' . bejewelry_privacy_policy_url(), true, 302);
exit;

$title = 'Privacy Policy';
$updatedAt = 'May 3, 2026';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?> - Bejewelry</title>
  <style>
    :root {
      --bg: #fff7fa;
      --card: #ffffff;
      --text: #241418;
      --muted: #7a5e68;
      --rose: #b03050;
      --border: #ead8df;
      --shadow: 0 14px 36px rgba(36, 20, 24, 0.14);
    }
    * { box-sizing: border-box; }
    body {
      margin: 0;
      font-family: "DM Sans", system-ui, -apple-system, Segoe UI, sans-serif;
      background: radial-gradient(circle at 20% 0%, #ffeaf0 0%, var(--bg) 40%, #fff 100%);
      color: var(--text);
      line-height: 1.65;
    }
    .wrap {
      width: min(940px, 100% - 32px);
      margin: 28px auto 48px;
    }
    .card {
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 18px;
      padding: 26px;
      box-shadow: var(--shadow);
    }
    h1 {
      margin: 0 0 6px;
      font-family: "Playfair Display", Georgia, serif;
      font-size: clamp(1.9rem, 3vw, 2.4rem);
    }
    .muted { color: var(--muted); font-size: 0.95rem; }
    h2 {
      margin: 26px 0 8px;
      font-family: "Playfair Display", Georgia, serif;
      font-size: 1.2rem;
      color: #3b2129;
    }
    p { margin: 0 0 12px; }
    ul { margin: 0 0 14px 18px; padding: 0; }
    a { color: var(--rose); text-decoration: none; }
    a:hover { text-decoration: underline; }
    .top-links {
      display: flex;
      gap: 14px;
      flex-wrap: wrap;
      margin-bottom: 12px;
      font-size: 0.9rem;
    }
  </style>
</head>
<body>
  <main class="wrap">
    <div class="card">
      <div class="top-links">
        <a href="index.php">Back to Home</a>
        <a href="login.php">Back to Login</a>
      </div>

      <h1>Privacy Policy</h1>
      <p class="muted">Last updated: <?php echo htmlspecialchars($updatedAt, ENT_QUOTES, 'UTF-8'); ?></p>

      <p>
        This Privacy Policy explains how Bejewelry collects, uses, stores, and protects your personal information
        when you use this website and related services.
      </p>

      <h2>1. Information We Collect</h2>
      <p>We may collect:</p>
      <ul>
        <li>Account details such as name, email address, and phone number.</li>
        <li>Shipping and order details including delivery address and order history.</li>
        <li>Security information such as login activity and authentication events.</li>
        <li>Technical data such as session identifiers and essential cookie preferences.</li>
      </ul>

      <h2>2. How We Use Information</h2>
      <ul>
        <li>To create and manage your account.</li>
        <li>To process orders, payments, and deliveries.</li>
        <li>To provide customer support and improve service reliability.</li>
        <li>To secure accounts, prevent fraud, and enforce platform policies.</li>
      </ul>

      <h2>3. Data Security</h2>
      <p>
        We use technical safeguards including encrypted transport (HTTPS/TLS), secure session cookies,
        and encryption of sensitive fields in the database where applicable.
      </p>

      <h2>4. Cookies</h2>
      <p>
        We use essential cookies required for authentication, session continuity, and security.
        Where available, you may also choose whether to allow non-essential analytics cookies.
      </p>

      <h2>5. Data Sharing</h2>
      <p>
        We do not sell personal information. Data may be shared only with service providers necessary
        to operate the platform, such as payment and delivery integrations, and only for legitimate business purposes.
      </p>

      <h2>6. Data Retention</h2>
      <p>
        We retain personal data only as long as needed for account operations, order fulfillment, legal obligations,
        dispute handling, and security auditing.
      </p>

      <h2>7. Your Rights</h2>
      <p>
        You may request updates or corrections to your account data through your profile settings.
        You may also contact us to request account deletion, subject to legal and transactional requirements.
      </p>

      <h2>8. Policy Updates</h2>
      <p>
        We may update this policy from time to time. Material changes will be posted on this page with an updated date.
      </p>

      <h2>9. Contact</h2>
      <p>
        For privacy concerns, please contact the Bejewelry support/admin team using available support channels in the platform.
      </p>
    </div>
  </main>
</body>
</html>
