<?php
/* ═══════════════════════════════════════════════════════════
   BEJEWELRY — Helpers (JWT, response, auth guard)
═══════════════════════════════════════════════════════════ */

/* ── JSON Responses ── */
function respond(mixed $data, int $status = 200): never {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

function respondError(string $message, int $status = 400): never {
    respond(['error' => $message], $status);
}

/* ── CORS & JSON Headers ── */
function setHeaders(): void {
    if (function_exists('bejewelry_enforce_https_if_needed')) {
        bejewelry_enforce_https_if_needed();
    }
    if (function_exists('bejewelry_send_security_headers')) {
        bejewelry_send_security_headers();
    }
    header('Content-Type: application/json; charset=utf-8');
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}

/* ── Simple JWT (no library needed) ── */
function jwtEncode(array $payload): string {
    $header  = base64url(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
    $payload['exp'] = time() + JWT_EXPIRY;
    $body    = base64url(json_encode($payload));
    $sig     = base64url(hash_hmac('sha256', "$header.$body", JWT_SECRET, true));
    return "$header.$body.$sig";
}

function jwtDecode(string $token): ?array {
    $parts = explode('.', $token);
    if (count($parts) !== 3) return null;
    [$h, $b, $s] = $parts;
    $expected = base64url(hash_hmac('sha256', "$h.$b", JWT_SECRET, true));
    if (!hash_equals($expected, $s)) return null;
    $payload = json_decode(base64_decode(strtr($b, '-_', '+/')), true);
    if (!$payload || $payload['exp'] < time()) return null;
    return $payload;
}

function base64url(string $data): string {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

/** Short-lived JWT for completing TOTP after password (API login step 2). */
function jwtEncodeTotpChallenge(int $userId, int $ttlSeconds = 600): string {
    $header  = base64url(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
    $payload = [
        'user_id' => $userId,
        'purpose' => 'totp_login',
        'exp' => time() + $ttlSeconds,
    ];
    $body = base64url(json_encode($payload));
    $sig  = base64url(hash_hmac('sha256', "$header.$body", JWT_SECRET, true));
    return "$header.$body.$sig";
}

function jwtDecodeTotpChallenge(string $token): ?array {
    $parts = explode('.', $token);
    if (count($parts) !== 3) {
        return null;
    }
    [$h, $b, $s] = $parts;
    $expected = base64url(hash_hmac('sha256', "$h.$b", JWT_SECRET, true));
    if (!hash_equals($expected, $s)) {
        return null;
    }
    $payload = json_decode(base64_decode(strtr($b, '-_', '+/')), true);
    if (!$payload || ($payload['purpose'] ?? '') !== 'totp_login' || ($payload['exp'] ?? 0) < time()) {
        return null;
    }
    return $payload;
}

/* ── Auth Guard ── */
function requireAuth(): array {
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (!preg_match('/^Bearer\s+(.+)$/i', $header, $m)) {
        respondError('Unauthorized', 401);
    }
    $payload = jwtDecode($m[1]);
    if (!$payload) respondError('Token expired or invalid', 401);
    $uid = (int) ($payload['user_id'] ?? 0);
    if ($uid <= 0) {
        respondError('Unauthorized', 401);
    }
    $stmt = db()->prepare('SELECT locked_at FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$uid]);
    if (!empty($stmt->fetchColumn())) {
        respondError('This account is locked. Contact a super admin to unlock it.', 423);
    }
    return $payload; // ['user_id' => ..., 'email' => ...]
}

function optionalAuth(): ?array {
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (!preg_match('/^Bearer\s+(.+)$/i', $header, $m)) return null;
    return jwtDecode($m[1]);
}

/* ── Request Body ── */
function body(): array {
    static $parsed = null;
    if ($parsed !== null) return $parsed;
    $raw    = file_get_contents('php://input');
    $parsed = json_decode($raw, true) ?? [];
    return $parsed;
}

/* ── Product image helper ── */
function productImageUrl(?string $image): ?string {
    if (!$image) return null;
    // If already a full URL, return as-is
    if (str_starts_with($image, 'http')) return $image;
    return UPLOAD_URL . $image;
}
