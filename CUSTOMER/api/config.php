<?php
/* ═══════════════════════════════════════════════════════════
   BEJEWELRY — Database Configuration
   Edit these values to match your server/hosting setup.
═══════════════════════════════════════════════════════════ */

define('DB_HOST',     'localhost');
define('DB_NAME',     'bejewelry');
define('DB_USER',     'root');        // ← change to your DB username
define('DB_PASS',     '');            // ← change to your DB password
define('DB_CHARSET',  'utf8mb4');

define('JWT_SECRET',  'change_this_to_a_random_secret_string_min_32_chars');
define('JWT_EXPIRY',  60 * 60 * 24 * 7); // 7 days in seconds

// Field-level encryption key for sensitive DB data (phone, address details, etc.).
// Use a long random value and keep it secret. Changing this key makes old encrypted data unreadable.
define('DATA_ENCRYPTION_KEY', 'change_this_to_a_random_secret_encryption_key_min_32_chars');

// Inactivity timeout for PHP session-based pages (seconds). Example: 120 = 2 minutes.
define('SESSION_TIMEOUT_SECONDS', 120);

// Transport security.
// Set FORCE_HTTPS to true in production when HTTPS is available.
define('FORCE_HTTPS', true);
define('ENABLE_HSTS', false);

// Google reCAPTCHA v2 (checkbox) — https://www.google.com/recaptcha/admin
// Test keys (always pass): site 6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI | secret 6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe
define('RECAPTCHA_SITE_KEY',   'your-recaptcha-site-key-here');
define('RECAPTCHA_SECRET_KEY', 'your-recaptcha-secret-key-here');
// Strict mode: disallow localhost bypass and Google public test keys.
define('RECAPTCHA_STRICT_REAL', true);

// Outgoing mail (activation links). Use Gmail App Password or your SMTP.
define('SMTP_HOST',       'smtp.gmail.com');
define('SMTP_PORT',       587);
define('SMTP_ENCRYPTION', 'tls');
define('SMTP_USER', 'your-email@gmail.com');
define('SMTP_PASS', 'your-app-password-here');// App password, not your normal Gmail password
define('SMTP_FROM_EMAIL', SMTP_USER);
define('SMTP_FROM_NAME',  'Bejewelry');

/** Hours until activation link expires */
define('ACTIVATION_LINK_HOURS', 48);

define('UPLOAD_DIR',  __DIR__ . '/../uploads/products/');
define('UPLOAD_URL',  '/uploads/products/');   // public URL prefix
define('DELIVERY_PROOF_DIR', __DIR__ . '/../uploads/delivery_proofs/');
define('DELIVERY_PROOF_URL', '/CUSTOMER/uploads/delivery_proofs/');
define('SUPPORT_TICKET_PHOTO_DIR', __DIR__ . '/../uploads/support_tickets/');
define('SUPPORT_TICKET_PHOTO_URL', '/BEJEWELRY/CUSTOMER/uploads/support_tickets/');
define('FREE_SHIP_THRESHOLD', 2000);
define('SHIPPING_FEE', 150);

// PayMongo (Philippines) — https://dashboard.paymongo.com/ → Settings → Developers → API keys
// Use sk_test_... for test mode, sk_live_... for production. If you see "API key does not exist", copy a fresh key or regenerate in the dashboard.
define('PAYMONGO_SECRET_KEY', 'your-paymongo-secret-key-here');// use Copy next to Secret Key in Dashboard — do not type by hand

// Third-party carrier delivery confirmation
define('CARRIER_WEBHOOK_KEY', 'change_this_to_a_long_random_carrier_secret');
define('CARRIER_PORTAL_PASSWORD', 'courier_portal_f5d8c8e6b2a24d5e91d1a3f4c8a7e2d9');
define('CARRIER_PROOF_MAX_BYTES', 8388608); // 8 MB

function bejewelry_request_is_https(): bool
{
    if (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') {
        return true;
    }
    if ((int) ($_SERVER['SERVER_PORT'] ?? 0) === 443) {
        return true;
    }
    $forwardedProto = strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
    if ($forwardedProto === 'https') {
        return true;
    }
    return false;
}

function bejewelry_enforce_https_if_needed(): void
{
    if (!defined('FORCE_HTTPS') || FORCE_HTTPS !== true) {
        return;
    }
    if (bejewelry_request_is_https()) {
        return;
    }
    $host = (string) ($_SERVER['HTTP_HOST'] ?? '');
    if ($host === '') {
        return;
    }
    $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
    header('Location: https://' . $host . $uri, true, 301);
    exit;
}

function bejewelry_send_security_headers(): void
{
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    
    /* ═══════════════════════════════════════════════════════════════════════════════
       Content-Security-Policy (CSP) Header
       
       CURRENT POLICY: Allows 'unsafe-inline' for temporary compatibility.
       
       TO HARDEN (once inline scripts/styles are migrated to external files):
       1. Identify all inline <script> and <style> tags in HTML.
       2. Move them to separate .js and .css files.
       3. Replace 'unsafe-inline' with nonces: generate a random nonce per request and
          add it to script/style tags like <script nonce="<?= $nonce ?>">
       4. Update this policy to use: script-src 'self' 'nonce-$RANDOM'; style-src 'self' 'nonce-$RANDOM';
       5. Remove 'unsafe-inline' entirely once all migrations are complete.
       
       CURRENT ALLOWED EXTERNAL RESOURCES:
       - Google reCAPTCHA v2 (script and frame sources)
       ═════════════════════════════════════════════════════════════════════════════ */
        // Allow Nominatim, OpenStreetMap tiles and CDN hosts used by Leaflet
        $csp = "default-src 'self'; " .
            "script-src 'self' 'unsafe-inline' https://www.google.com/recaptcha/ https://www.gstatic.com/recaptcha/ https://cdnjs.cloudflare.com https://unpkg.com https://cdn.jsdelivr.net; " .
            "script-src-elem 'self' 'unsafe-inline' https://www.google.com/recaptcha/ https://www.gstatic.com/recaptcha/ https://cdnjs.cloudflare.com https://unpkg.com https://cdn.jsdelivr.net; " .
            "style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://unpkg.com https://cdn.jsdelivr.net; " .
            "style-src-elem 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://unpkg.com https://cdn.jsdelivr.net; " .
            "img-src 'self' data: https: https://*.tile.openstreetmap.org https://tile.openstreetmap.org; " .
            "font-src 'self' data: https://cdnjs.cloudflare.com https://unpkg.com https://cdn.jsdelivr.net https://fonts.googleapis.com; " .
            "connect-src 'self' https://www.google.com/recaptcha/ https://www.gstatic.com/recaptcha/ https://nominatim.openstreetmap.org https://*.openstreetmap.org https://tile.openstreetmap.org https://cdnjs.cloudflare.com https://unpkg.com https://cdn.jsdelivr.net https://fonts.googleapis.com; " .
            "frame-src 'self' https://www.google.com/recaptcha/";
    header('Content-Security-Policy: ' . $csp);
    
    if (defined('ENABLE_HSTS') && ENABLE_HSTS === true && bejewelry_request_is_https()) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

function bejewelry_store_support_ticket_photo(array $file): ?string
{
    $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($error === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($error !== UPLOAD_ERR_OK) {
        return null;
    }

    $tmpPath = (string) ($file['tmp_name'] ?? '');
    if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
        return null;
    }

    $maxBytes = defined('CARRIER_PROOF_MAX_BYTES') ? (int) CARRIER_PROOF_MAX_BYTES : 8388608;
    $size = (int) ($file['size'] ?? 0);
    if ($size <= 0 || $size > $maxBytes) {
        return null;
    }

    $mime = '';
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo) {
            $detected = finfo_file($finfo, $tmpPath);
            if (is_string($detected)) {
                $mime = $detected;
            }
            finfo_close($finfo);
        }
    }

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
    ];

    if (!isset($allowed[$mime])) {
        return null;
    }

    if (!is_dir(SUPPORT_TICKET_PHOTO_DIR) && !mkdir(SUPPORT_TICKET_PHOTO_DIR, 0775, true) && !is_dir(SUPPORT_TICKET_PHOTO_DIR)) {
        return null;
    }

    $filename = 'ticket_' . date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $allowed[$mime];
    $target = SUPPORT_TICKET_PHOTO_DIR . $filename;
    if (!move_uploaded_file($tmpPath, $target)) {
        return null;
    }

    return $filename;
}

function bejewelry_support_ticket_photo_url(?string $filename): ?string
{
    $filename = trim((string) $filename);
    if ($filename === '') {
        return null;
    }

    return SUPPORT_TICKET_PHOTO_URL . rawurlencode($filename);
}

/* ── PDO Connection ── */
function db(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
    return $pdo;
}
