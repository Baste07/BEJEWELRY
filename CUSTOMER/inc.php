<?php
/* ═══════════════════════════════════════════════════════════
   BEJEWELRY — Customer include (session + DB, no API)
   Include at top of every customer PHP page.
═══════════════════════════════════════════════════════════ */
require_once __DIR__ . '/api/config.php';

bejewelry_enforce_https_if_needed();
require_once __DIR__ . '/api/csrf_helper.php';

bejewelry_send_security_headers();

if (session_status() === PHP_SESSION_NONE) {
    if (session_name() !== 'BEJEWELRY_C2_SESSID') {
        session_name('BEJEWELRY_C2_SESSID');
    }
    $isLocalhost = isset($_SERVER['HTTP_HOST']) && (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false);
    $secure = bejewelry_request_is_https();
    // Modern browsers require 'Secure' when SameSite=None. Use 'None' only over HTTPS.
    $samesite = $secure ? 'None' : 'Lax';
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => $samesite,
    ]);
    session_start();
}

const BEJEWELRY_LOGIN_MAX_ATTEMPTS = 3;
const BEJEWELRY_DEFAULT_SESSION_TIMEOUT_SECONDS = 120;
const BEJEWELRY_ENC_PREFIX = 'enc:v1:';

function bejewelry_base_path(): string
{
        $docRoot = rtrim(str_replace('\\', '/', (string) ($_SERVER['DOCUMENT_ROOT'] ?? '')), '/');
        $appDir = str_replace('\\', '/', __DIR__);
        $basePath = '';

        if ($docRoot !== '' && str_starts_with($appDir, $docRoot)) {
                $basePath = substr($appDir, strlen($docRoot));
        }

        $basePath = '/' . trim($basePath, '/');
        return str_replace(' ', '%20', $basePath);
}

function bejewelry_privacy_policy_url(): string
{
    return bejewelry_base_path() . '/login.php?open_privacy=1';
}

function bejewelry_is_login_page(): bool
{
    $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    return str_ends_with(strtolower($script), '/login.php');
}

function bejewelry_should_inject_cookie_banner(): bool
{
        if (PHP_SAPI === 'cli') {
                return false;
        }

        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        if (!in_array($method, ['GET', 'HEAD'], true)) {
                return false;
        }

        $xhr = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? ''));
        if ($xhr === 'xmlhttprequest') {
                return false;
        }

        $accept = strtolower((string) ($_SERVER['HTTP_ACCEPT'] ?? ''));
        if ($accept !== '' && str_contains($accept, 'application/json') && !str_contains($accept, 'text/html')) {
                return false;
        }

        $uri = strtolower((string) ($_SERVER['REQUEST_URI'] ?? ''));
        if (str_contains($uri, '/api/')) {
                return false;
        }

        return true;
}

function bejewelry_cookie_banner_markup(): string
{
        $policyUrl = htmlspecialchars(bejewelry_privacy_policy_url(), ENT_QUOTES, 'UTF-8');
    $privacyAction = bejewelry_is_login_page()
        ? '<button type="button" class="bej-cookie-inline-link" id="bej-cookie-open-privacy">Read Privacy Policy</button>'
        : '<a href="' . $policyUrl . '">Read Privacy Policy</a>';

        return <<<HTML
<style id="bej-cookie-banner-style">
    #bej-cookie-banner { position: fixed; left: 16px; right: 16px; bottom: 16px; z-index: 99999; background: #fff; border: 1px solid #e5d6db; border-radius: 14px; box-shadow: 0 12px 36px rgba(36,20,24,.18); padding: 14px 16px; display: none; }
    #bej-cookie-banner.show { display: block; }
    #bej-cookie-banner .bej-cookie-wrap { display: flex; gap: 12px; align-items: flex-start; justify-content: space-between; flex-wrap: wrap; }
    #bej-cookie-banner .bej-cookie-text { color: #3a2028; font: 500 13px/1.5 'DM Sans', system-ui, sans-serif; max-width: 820px; }
    #bej-cookie-banner .bej-cookie-text a { color: #b03050; text-decoration: none; }
    #bej-cookie-banner .bej-cookie-text a:hover { text-decoration: underline; }
    #bej-cookie-banner .bej-cookie-inline-link { background: none; border: none; padding: 0; margin: 0; color: #b03050; text-decoration: underline; cursor: pointer; font: inherit; }
    #bej-cookie-banner .bej-cookie-actions { display: flex; gap: 8px; }
    #bej-cookie-banner .bej-cookie-btn { border: 1px solid #ddc5ce; border-radius: 999px; font: 700 11px/1 'DM Sans', system-ui, sans-serif; letter-spacing: .06em; text-transform: uppercase; cursor: pointer; padding: 10px 14px; background: #fff; color: #6a3b4b; }
    #bej-cookie-banner .bej-cookie-btn.primary { background: linear-gradient(135deg,#d96070,#b03050); border-color: transparent; color: #fff; }
    @media (max-width: 680px) {
        #bej-cookie-banner .bej-cookie-actions { width: 100%; }
        #bej-cookie-banner .bej-cookie-btn { flex: 1; }
    }
</style>
<div id="bej-cookie-banner" role="dialog" aria-live="polite" aria-label="Cookie consent banner">
    <div class="bej-cookie-wrap">
        <div class="bej-cookie-text">
            We use essential cookies to keep your session secure and improve site performance. You can accept analytics cookies or keep essential only.
            {$privacyAction}
        </div>
        <div class="bej-cookie-actions">
            <button type="button" class="bej-cookie-btn" data-consent="essential">Essential only</button>
            <button type="button" class="bej-cookie-btn primary" data-consent="accepted">Accept all</button>
        </div>
    </div>
</div>
<script>
    (function () {
        var key = 'bejewelry_cookie_consent_v1';
        var banner = document.getElementById('bej-cookie-banner');
        if (!banner) return;

        function setConsent(value) {
            var payload = { value: value, saved_at: new Date().toISOString() };
            try { localStorage.setItem(key, JSON.stringify(payload)); } catch (e) {}
            var cookie = 'bej_cookie_consent=' + encodeURIComponent(value) + '; Path=/; Max-Age=' + (60 * 60 * 24 * 180) + '; SameSite=Lax';
            if (location.protocol === 'https:') cookie += '; Secure';
            document.cookie = cookie;
            banner.classList.remove('show');
        }

        function hasConsent() {
            try {
                var raw = localStorage.getItem(key);
                if (!raw) return false;
                var data = JSON.parse(raw);
                return !!(data && data.value);
            } catch (e) {
                return false;
            }
        }

        if (!hasConsent()) {
            banner.classList.add('show');
        }

        banner.addEventListener('click', function (ev) {
            var target = ev.target;
            if (!(target instanceof Element)) return;
            var value = target.getAttribute('data-consent');
            if (!value) return;
            setConsent(value);
        });

        var openPrivacy = document.getElementById('bej-cookie-open-privacy');
        if (openPrivacy) {
            openPrivacy.addEventListener('click', function () {
                if (typeof window.bejOpenPrivacyPolicyModal === 'function') {
                    window.bejOpenPrivacyPolicyModal();
                }
            });
        }
    })();
</script>
HTML;
}

function bejewelry_inject_cookie_banner(string $buffer): string
{
        if ($buffer === '' || !bejewelry_should_inject_cookie_banner()) {
                return $buffer;
        }

        if (stripos($buffer, '<html') === false || stripos($buffer, '</body>') === false) {
                return $buffer;
        }

        if (str_contains($buffer, 'id="bej-cookie-banner"')) {
                return $buffer;
        }

        $insertAt = strripos($buffer, '</body>');
        if ($insertAt === false) {
                return $buffer;
        }

        return substr($buffer, 0, $insertAt) . bejewelry_cookie_banner_markup() . substr($buffer, $insertAt);
}

if (bejewelry_should_inject_cookie_banner()) {
        ob_start('bejewelry_inject_cookie_banner');
}

function bejewelry_encryption_key_bytes(): string
{
    $raw = (string) (defined('DATA_ENCRYPTION_KEY') ? DATA_ENCRYPTION_KEY : JWT_SECRET);
    $raw = trim($raw);
    if ($raw === '') {
        $raw = (string) JWT_SECRET;
    }

    return hash('sha256', $raw, true);
}

function bejewelry_is_encrypted_value(?string $value): bool
{
    if (!is_string($value) || $value === '') {
        return false;
    }
    return strncmp($value, BEJEWELRY_ENC_PREFIX, strlen(BEJEWELRY_ENC_PREFIX)) === 0;
}

function bejewelry_encrypt_sensitive(?string $plain): ?string
{
    if ($plain === null || $plain === '') {
        return null;
    }
    if (bejewelry_is_encrypted_value($plain)) {
        return $plain;
    }
    if (!function_exists('openssl_encrypt')) {
        return $plain;
    }

    $iv = random_bytes(12);
    $tag = '';
    $cipher = openssl_encrypt($plain, 'aes-256-gcm', bejewelry_encryption_key_bytes(), OPENSSL_RAW_DATA, $iv, $tag, '', 16);
    if ($cipher === false) {
        return $plain;
    }

    return BEJEWELRY_ENC_PREFIX . base64_encode($iv . $tag . $cipher);
}

function bejewelry_decrypt_sensitive(?string $value): ?string
{
    if ($value === null || $value === '') {
        return $value;
    }
    if (!bejewelry_is_encrypted_value($value)) {
        return $value;
    }
    if (!function_exists('openssl_decrypt')) {
        return $value;
    }

    $raw = base64_decode(substr($value, strlen(BEJEWELRY_ENC_PREFIX)), true);
    if (!is_string($raw) || strlen($raw) < 29) {
        return $value;
    }

    $iv = substr($raw, 0, 12);
    $tag = substr($raw, 12, 16);
    $cipher = substr($raw, 28);
    $plain = openssl_decrypt($cipher, 'aes-256-gcm', bejewelry_encryption_key_bytes(), OPENSSL_RAW_DATA, $iv, $tag, '');
    if ($plain === false) {
        return $value;
    }

    return $plain;
}

function bejewelry_encrypt_fields(array $row, array $fields): array
{
    foreach ($fields as $field) {
        if (array_key_exists($field, $row)) {
            $v = $row[$field];
            $row[$field] = $v === null ? null : bejewelry_encrypt_sensitive((string) $v);
        }
    }
    return $row;
}

function bejewelry_decrypt_fields(array $row, array $fields): array
{
    foreach ($fields as $field) {
        if (array_key_exists($field, $row)) {
            $v = $row[$field];
            $row[$field] = $v === null ? null : bejewelry_decrypt_sensitive((string) $v);
        }
    }
    return $row;
}

function bejewelry_encrypt_user_private_fields(array $row): array
{
    return bejewelry_encrypt_fields($row, ['phone', 'city', 'birthday']);
}

function bejewelry_decrypt_user_private_fields(array $row): array
{
    return bejewelry_decrypt_fields($row, ['phone', 'city', 'birthday']);
}

function bejewelry_encrypt_address_private_fields(array $row): array
{
    return bejewelry_encrypt_fields($row, ['street', 'city', 'province', 'zip', 'phone']);
}

function bejewelry_decrypt_address_private_fields(array $row): array
{
    return bejewelry_decrypt_fields($row, ['street', 'city', 'province', 'zip', 'phone']);
}

function bejewelry_encrypt_order_shipping_fields(array $row): array
{
    return bejewelry_encrypt_fields($row, ['ship_street', 'ship_city', 'ship_province', 'ship_zip', 'ship_phone']);
}

function bejewelry_decrypt_order_shipping_fields(array $row): array
{
    return bejewelry_decrypt_fields($row, ['ship_street', 'ship_city', 'ship_province', 'ship_zip', 'ship_phone']);
}

function bejewelry_get_session_timeout_seconds(): int
{
    static $cache = null;
    if (is_int($cache)) {
        return $cache;
    }

    $timeout = defined('SESSION_TIMEOUT_SECONDS')
        ? (int) SESSION_TIMEOUT_SECONDS
        : BEJEWELRY_DEFAULT_SESSION_TIMEOUT_SECONDS;

    // Optional override from app_settings.security.session_timeout_minutes or session_timeout_seconds.
    try {
        $pdo = db();
        $stmt = $pdo->prepare('SELECT `value` FROM app_settings WHERE `key` = ? LIMIT 1');
        $stmt->execute(['security']);
        $raw = $stmt->fetchColumn();
        if ($raw !== false && $raw !== null && $raw !== '') {
            $decoded = json_decode((string) $raw, true);
            if (is_array($decoded)) {
                if (isset($decoded['session_timeout_seconds'])) {
                    $timeout = (int) $decoded['session_timeout_seconds'];
                } elseif (isset($decoded['session_timeout_minutes'])) {
                    $timeout = (int) $decoded['session_timeout_minutes'] * 60;
                }
            }
        }
    } catch (Throwable $e) {
        // Use config default when settings table/value is unavailable.
    }

    $cache = max(60, $timeout);
    return $cache;
}

function bejewelry_login_url_with_error(string $message): string
{
    $docRoot = rtrim(str_replace('\\', '/', (string) ($_SERVER['DOCUMENT_ROOT'] ?? '')), '/');
    $appDir = str_replace('\\', '/', __DIR__);
    $basePath = '';

    if ($docRoot !== '' && str_starts_with($appDir, $docRoot)) {
        $basePath = substr($appDir, strlen($docRoot));
    }

    $basePath = '/' . trim($basePath, '/');
    $basePath = str_replace(' ', '%20', $basePath);

    return $basePath . '/login.php?error=' . urlencode($message);
}

function bejewelry_logout_now(bool $expired = false): void
{
    $uid = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;
    $email = null;
    if (isset($_SESSION['user_row']) && is_array($_SESSION['user_row'])) {
        $email = $_SESSION['user_row']['email'] ?? null;
    }

    if ($uid > 0) {
        bejewelry_audit_log($uid, is_string($email) ? $email : null, $expired ? 'session_timeout' : 'logout');
    }

    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

function bejewelry_enforce_session_timeout(): void
{
    if (!isset($_SESSION['user_id'])) {
        return;
    }

    $now = time();
    $timeout = bejewelry_get_session_timeout_seconds();
    $last = isset($_SESSION['last_activity_at']) ? (int) $_SESSION['last_activity_at'] : 0;

    if ($last > 0 && ($now - $last) >= $timeout) {
        bejewelry_logout_now(true);
        if (!headers_sent()) {
            header('Location: ' . bejewelry_login_url_with_error('Session expired due to inactivity. Please sign in again.'));
        }
        exit;
    }

    $_SESSION['last_activity_at'] = $now;
}

function bejewelry_login_lock_defaults(): array
{
    return [
        'max_attempts' => BEJEWELRY_LOGIN_MAX_ATTEMPTS,
    ];
}

function bejewelry_get_login_lock_settings(): array
{
    static $cache = null;
    if (is_array($cache)) {
        return $cache;
    }

    $settings = bejewelry_login_lock_defaults();
    try {
        $pdo = db();
        $stmt = $pdo->prepare('SELECT `value` FROM app_settings WHERE `key` = ? LIMIT 1');
        $stmt->execute(['login_lock_settings']);
        $raw = $stmt->fetchColumn();
        if ($raw !== false && $raw !== null && $raw !== '') {
            $decoded = json_decode((string) $raw, true);
            if (is_array($decoded)) {
                $settings = array_merge($settings, $decoded);
            }
        }
    } catch (Throwable $e) {
        // Fall back to the built-in default when settings are unavailable.
    }

    $settings['max_attempts'] = max(1, (int) ($settings['max_attempts'] ?? BEJEWELRY_LOGIN_MAX_ATTEMPTS));
    $cache = $settings;
    return $cache;
}

function bejewelry_get_password_policy(): array
{
    static $cache = null;
    if (is_array($cache)) {
        return $cache;
    }

    $defaults = [
        'enabled' => true,
        'min_length' => 12,
        'require_upper' => true,
        'require_lower' => true,
        'require_number' => true,
        'require_special' => true,
        'expiration_days' => 0,
    ];

    try {
        $pdo = db();
        $stmt = $pdo->prepare('SELECT `value` FROM app_settings WHERE `key` = ? LIMIT 1');
        $stmt->execute(['security']);
        $raw = $stmt->fetchColumn();
        if ($raw !== false && $raw !== null && $raw !== '') {
            $decoded = json_decode((string) $raw, true);
            if (is_array($decoded)) {
                $defaults = array_merge($defaults, $decoded);
            }
        }
    } catch (Throwable $e) {
        // ignore and use defaults
    }

    $defaults['min_length'] = max(6, (int) ($defaults['min_length'] ?? 12));
    $defaults['expiration_days'] = max(0, (int) ($defaults['expiration_days'] ?? 0));
    $cache = $defaults;
    return $cache;
}

function bejewelry_validate_password(string $password): array
{
    $policy = bejewelry_get_password_policy();
    $errors = [];
    if (empty($policy['enabled'])) {
        return ['ok' => true, 'errors' => []];
    }
    $len = mb_strlen($password);
    if ($len < (int)$policy['min_length']) {
        $errors[] = 'Password must be at least ' . (int)$policy['min_length'] . ' characters.';
    }
    if (!empty($policy['require_upper']) && !preg_match('/[A-Z]/', $password)) {
        $errors[] = 'Password must include at least one uppercase letter.';
    }
    if (!empty($policy['require_lower']) && !preg_match('/[a-z]/', $password)) {
        $errors[] = 'Password must include at least one lowercase letter.';
    }
    if (!empty($policy['require_number']) && !preg_match('/[0-9]/', $password)) {
        $errors[] = 'Password must include at least one number.';
    }
    if (!empty($policy['require_special']) && !preg_match('/[^a-zA-Z0-9]/', $password)) {
        $errors[] = 'Password must include at least one special character.';
    }

    return ['ok' => empty($errors), 'errors' => $errors];
}

function bejewelry_get_login_max_attempts(): int
{
    $settings = bejewelry_get_login_lock_settings();
    return (int) ($settings['max_attempts'] ?? BEJEWELRY_LOGIN_MAX_ATTEMPTS);
}

function bejewelry_set_login_max_attempts(int $attempts): void
{
    $attempts = max(1, $attempts);
    $pdo = db();
    $payload = json_encode(['max_attempts' => $attempts], JSON_UNESCAPED_SLASHES);
    $stmt = $pdo->prepare('INSERT INTO app_settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)');
    $stmt->execute(['login_lock_settings', $payload]);

    // Refresh local cache for the current request.
    bejewelry_get_login_lock_settings();
}

function current_user_id(): ?int {
    if (!isset($_SESSION['user_id'])) {
        return null;
    }

    $uid = (int) $_SESSION['user_id'];
    if ($uid <= 0) {
        return null;
    }

    if (isset($_SESSION['user_row']) && is_array($_SESSION['user_row']) && array_key_exists('locked_at', $_SESSION['user_row'])) {
        if (!empty($_SESSION['user_row']['locked_at']) || !empty($_SESSION['user_row']['archived_at'])) {
            unset($_SESSION['user_id'], $_SESSION['user_row']);
            return null;
        }
        return $uid;
    }

    $stmt = db()->prepare('SELECT locked_at, archived_at FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$uid]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    if (!empty($row['locked_at']) || !empty($row['archived_at'])) {
        unset($_SESSION['user_id'], $_SESSION['user_row']);
        return null;
    }

    return $uid;
}

function current_user(): ?array {
    if (!current_user_id()) return null;
    if (isset($_SESSION['user_row']) && is_array($_SESSION['user_row'])) {
        // If cache is missing newer columns, refresh from DB
        $need = ['created_at', 'gender', 'birthday', 'city', 'phone', 'failed_login_attempts', 'locked_at', 'locked_by', 'lock_reason', 'archived_at', 'archived_by', 'archive_reason'];
        $missing = false;
        foreach ($need as $k) {
            if (!array_key_exists($k, $_SESSION['user_row'])) { $missing = true; break; }
        }
        if (!$missing) {
            $_SESSION['user_row'] = bejewelry_decrypt_user_private_fields($_SESSION['user_row']);
            return $_SESSION['user_row'];
        }
    }
    $stmt = db()->prepare('SELECT id, first_name, last_name, email, phone, gender, birthday, city, role, created_at, failed_login_attempts, locked_at, locked_by, lock_reason, archived_at, archived_by, archive_reason FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $row = $stmt->fetch();
    if ($row) {
        $row = bejewelry_decrypt_user_private_fields($row);
    }
    if ($row) $_SESSION['user_row'] = $row;
    return $row ?: null;
}

function bejewelry_is_account_locked(array $user): bool
{
    return !empty($user['locked_at']);
}

function bejewelry_is_account_archived(array $user): bool
{
    return !empty($user['archived_at']);
}

function bejewelry_reset_login_failures(int $userId): void
{
    $stmt = db()->prepare('UPDATE users SET failed_login_attempts = 0, updated_at = NOW() WHERE id = ?');
    $stmt->execute([$userId]);
    if (isset($_SESSION['user_row']) && is_array($_SESSION['user_row']) && (int) ($_SESSION['user_row']['id'] ?? 0) === $userId) {
        $_SESSION['user_row']['failed_login_attempts'] = 0;
    }
}

function bejewelry_register_login_failure(int $userId): array
{
    $pdo = db();
    $stmt = $pdo->prepare('SELECT failed_login_attempts, locked_at, email, username FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    if (!$row) {
        return ['attempts' => 0, 'locked' => false];
    }
    $maxAttempts = bejewelry_get_login_max_attempts();

    if (!empty($row['locked_at'])) {
        return ['attempts' => (int) ($row['failed_login_attempts'] ?? $maxAttempts), 'locked' => true];
    }

    $attempts = min($maxAttempts, ((int) ($row['failed_login_attempts'] ?? 0)) + 1);
    if ($attempts >= $maxAttempts) {
        $upd = $pdo->prepare(
            'UPDATE users
             SET failed_login_attempts = ?, locked_at = COALESCE(locked_at, NOW()), lock_reason = COALESCE(lock_reason, ?), updated_at = NOW()
             WHERE id = ?'
        );
        $upd->execute([$attempts, 'Too many failed login attempts.', $userId]);

        $lockName = trim((string) ($row['username'] ?? ''));
        if ($lockName === '') {
            $lockName = trim((string) ($row['email'] ?? ''));
        }
        bejewelry_log_account_lock($lockName, $attempts);

        bejewelry_audit_log($userId, (string) ($row['email'] ?? ''), 'lock_account');

        return ['attempts' => $attempts, 'locked' => true];
    }

    $upd = $pdo->prepare('UPDATE users SET failed_login_attempts = ?, updated_at = NOW() WHERE id = ?');
    $upd->execute([$attempts, $userId]);
    return ['attempts' => $attempts, 'locked' => false];
}

function bejewelry_set_account_lock(int $userId, bool $locked, ?int $lockedBy = null, ?string $reason = null): void
{
    $pdo = db();
    $stmt = $pdo->prepare('SELECT username, email, locked_at, failed_login_attempts FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$userId]);
    $before = $stmt->fetch();

    if ($locked) {
        $maxAttempts = bejewelry_get_login_max_attempts();
        $upd = $pdo->prepare(
            'UPDATE users
             SET failed_login_attempts = ?, locked_at = COALESCE(locked_at, NOW()), locked_by = ?, lock_reason = ?, updated_at = NOW()
             WHERE id = ?'
        );
        $upd->execute([
            $maxAttempts,
            $lockedBy,
            $reason ?: 'Locked by super admin.',
            $userId,
        ]);

        if ($before && empty($before['locked_at'])) {
            $lockName = trim((string) ($before['username'] ?? ''));
            if ($lockName === '') {
                $lockName = trim((string) ($before['email'] ?? ''));
            }
            bejewelry_log_account_lock($lockName, $maxAttempts);
        }
    } else {
        $upd = $pdo->prepare(
            'UPDATE users
             SET failed_login_attempts = 0, locked_at = NULL, locked_by = NULL, lock_reason = NULL, updated_at = NOW()
             WHERE id = ?'
        );
        $upd->execute([$userId]);
    }

    if (isset($_SESSION['user_row']) && is_array($_SESSION['user_row']) && (int) ($_SESSION['user_row']['id'] ?? 0) === $userId) {
        $_SESSION['user_row']['failed_login_attempts'] = $locked ? bejewelry_get_login_max_attempts() : 0;
        $_SESSION['user_row']['locked_at'] = $locked ? ($_SESSION['user_row']['locked_at'] ?? date('Y-m-d H:i:s')) : null;
        $_SESSION['user_row']['locked_by'] = $locked ? $lockedBy : null;
        $_SESSION['user_row']['lock_reason'] = $locked ? ($reason ?: 'Locked by super admin.') : null;
    }
}

function bejewelry_set_account_archive(int $userId, bool $archived, ?int $archivedBy = null, ?string $reason = null): void
{
    $pdo = db();
    $stmt = $pdo->prepare('SELECT username, email, archived_at FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$userId]);
    $before = $stmt->fetch();

    if ($archived) {
        $upd = $pdo->prepare(
            'UPDATE users
             SET archived_at = COALESCE(archived_at, NOW()), archived_by = ?, archive_reason = ?, updated_at = NOW()
             WHERE id = ?'
        );
        $upd->execute([
            $archivedBy,
            $reason ?: 'Archived by super admin.',
            $userId,
        ]);

        if ($before && empty($before['archived_at'])) {
            bejewelry_audit_log($userId, (string) ($before['email'] ?? ''), 'archive_account');
        }
    } else {
        $upd = $pdo->prepare(
            'UPDATE users
             SET archived_at = NULL, archived_by = NULL, archive_reason = NULL, updated_at = NOW()
             WHERE id = ?'
        );
        $upd->execute([$userId]);
    }

    if (isset($_SESSION['user_row']) && is_array($_SESSION['user_row']) && (int) ($_SESSION['user_row']['id'] ?? 0) === $userId) {
        $_SESSION['user_row']['archived_at'] = $archived ? ($_SESSION['user_row']['archived_at'] ?? date('Y-m-d H:i:s')) : null;
        $_SESSION['user_row']['archived_by'] = $archived ? $archivedBy : null;
        $_SESSION['user_row']['archive_reason'] = $archived ? ($reason ?: 'Archived by super admin.') : null;
    }
}

/**
 * Store an immutable record whenever an account becomes locked.
 */
function bejewelry_log_account_lock(string $username, int $failedAttempts): void
{
    $username = trim($username);
    if ($username === '') {
        return;
    }

    try {
        $pdo = db();
        $stmt = $pdo->prepare('INSERT INTO account_lock_logs (username, failed_login_attempts, locked_at) VALUES (?,?,NOW())');
        $stmt->execute([$username, $failedAttempts]);
    } catch (Throwable $e) {
        // Ignore until the lock log migration is applied.
    }
}

/** Product image URL for customer pages (relative to CUSTOMER) */
function customer_product_image_url(?string $image): ?string {
    if (!$image) return null;
    if (str_starts_with($image, 'http')) return $image;
    return 'uploads/products/' . $image;
}

/** Format product row for JSON/page (adds image_url, cat, etc.) */
function format_product_for_customer(array $p): array {
    $p['id'] = (int) $p['id'];
    $p['price'] = (float) $p['price'];
    $p['orig_price'] = isset($p['orig_price']) && $p['orig_price'] !== null ? (float) $p['orig_price'] : null;
    // Compute actual aggregated rating and review count from submitted customer reviews
    $p['stars'] = 0;
    $p['avg_rating'] = 0.0;
    $p['reviews'] = 0;
    try {
        $pdo = db();
        $rs = $pdo->prepare("SELECT AVG(rating) AS avg_rating, COUNT(*) AS review_count FROM product_reviews WHERE product_id = ? AND status IN ('approved', 'pending')");
        $rs->execute([$p['id']]);
        $r = $rs->fetch(PDO::FETCH_ASSOC);
        if ($r) {
            $p['avg_rating'] = isset($r['avg_rating']) && $r['avg_rating'] !== null ? (float) $r['avg_rating'] : 0.0;
            $p['reviews'] = isset($r['review_count']) ? (int) $r['review_count'] : 0;
            $p['stars'] = $p['reviews'] ? (int) round($p['avg_rating']) : 0;
        }
    } catch (Throwable $e) {
        // If DB access fails, fall back to provided values or defaults
        $p['stars'] = (int) ($p['stars'] ?? 0);
        $p['reviews'] = (int) ($p['reviews'] ?? 0);
        $p['avg_rating'] = $p['reviews'] ? ($p['avg_rating'] ?? 0.0) : 0.0;
    }
    $p['stock'] = (int) ($p['stock'] ?? 0);
    $p['image_url'] = customer_product_image_url($p['image'] ?? null);
    $p['cat'] = $p['cat'] ?? $p['category_name'] ?? '';
    return $p;
}

/** Cart items for current user (same shape as API cart response items) */
function get_customer_cart(): array {
    $uid = current_user_id();
    if (!$uid) return [];
    $stmt = db()->prepare('
        SELECT ci.id, ci.product_id, ci.size, ci.qty,
               p.name, p.price, p.image, c.name AS cat
        FROM cart_items ci
        JOIN products p ON p.id = ci.product_id
        LEFT JOIN categories c ON c.id = p.category_id
        WHERE ci.user_id = ?
        ORDER BY ci.added_at ASC
    ');
    $stmt->execute([$uid]);
    $out = [];
    foreach ($stmt->fetchAll() as $row) {
        $out[] = [
            'id' => (int) $row['id'],
            'key' => $row['product_id'] . '-' . $row['size'],
            'product_id' => (int) $row['product_id'],
            'name' => $row['name'],
            'cat' => $row['cat'],
            'price' => (float) $row['price'],
            'image' => $row['image'],
            'image_url' => customer_product_image_url($row['image']),
            'size' => $row['size'],
            'qty' => (int) $row['qty'],
        ];
    }
    return $out;
}

/** Wishlist product IDs (and optional full rows) for current user */
function get_customer_wishlist(): array {
    $uid = current_user_id();
    if (!$uid) return [];
    $stmt = db()->prepare('
        SELECT p.*, c.name AS cat, c.slug AS cat_slug
        FROM wishlist w
        JOIN products p ON p.id = w.product_id AND p.is_active = 1
        LEFT JOIN categories c ON c.id = p.category_id
        WHERE w.user_id = ?
        ORDER BY w.added_at DESC
    ');
    $stmt->execute([$uid]);
    $rows = $stmt->fetchAll();
    return array_map('format_product_for_customer', $rows);
}

/**
 * Record a sensitive admin/customer action in audit_log.
 */
function bejewelry_audit_log(?int $userId, ?string $email, string $action): void
{
    $action = trim(strtolower($action));
    if ($action === '') {
        return;
    }
    try {
        $pdo = db();
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        if (is_string($ip) && strlen($ip) > 45) {
            $ip = substr($ip, 0, 45);
        }
        $ua = isset($_SERVER['HTTP_USER_AGENT']) ? substr((string) $_SERVER['HTTP_USER_AGENT'], 0, 255) : null;
        $stmt = $pdo->prepare('INSERT INTO audit_log (user_id, email, action, ip, user_agent) VALUES (?,?,?,?,?)');
        $stmt->execute([$userId, $email !== '' ? $email : null, $action, $ip, $ua]);
    } catch (Throwable $e) {
        // Table may not exist until migration is applied.
    }
}

function bejewelry_audit_action_label(string $action): string
{
    $map = [
        'login' => 'Login',
        'logout' => 'Logout',
        'session_timeout' => 'Session Timeout',
        'register_account' => 'Register Account',
        'create_staff_account' => 'Create Staff Account',
        'lock_account' => 'Lock Account',
        'unlock_account' => 'Unlock Account',
        'add_product' => 'Add Product',
        'edit_product' => 'Edit Product',
        'edit_product_price' => 'Edit Product Price',
        'restock_product' => 'Restock Product',
        'delete_product' => 'Delete Product',
    ];

    $action = trim(strtolower($action));
    if (isset($map[$action])) {
        return $map[$action];
    }

    return trim(ucwords(str_replace(['_', '-'], ' ', $action)));
}

/**
 * First page after login for staff (paths relative to CUSTOMER root).
 */
function bejewelry_staff_post_login_path(string $role): string
{
    $r = $role === 'admin' ? 'super_admin' : $role;
    if ($r === 'courier') {
        return 'orderManager/courier_portal.php';
    }
    if ($r === 'manager') {
        return 'orderManager/order_manager.php?page=orders';
    }
    if ($r === 'inventory') {
        return 'InventoryManager/index.php';
    }
    if ($r === 'super_admin') {
        return 'admin/customers.php';
    }
    return 'index.php';
}

bejewelry_enforce_session_timeout();
