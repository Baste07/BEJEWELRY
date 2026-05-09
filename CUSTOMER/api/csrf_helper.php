<?php
/**
 * CSRF Protection Helper
 * Uses paragonie/anti-csrf library for token generation and validation
 */

require_once __DIR__ . '/../vendor/autoload.php';

use ParagonIE\AntiCSRF\AntiCSRF;

/**
 * Get the global AntiCSRF instance (singleton)
 * @return AntiCSRF
 */
function get_csrf(): AntiCSRF {
    static $antiCsrf = null;
    if ($antiCsrf === null) {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $antiCsrf = new AntiCSRF();
    }
    return $antiCsrf;
}

/**
 * Insert a hidden CSRF token field into an HTML form
 * Call this inside every <form method="POST">
 * @return string HTML hidden input with CSRF token
 */
function csrf_token_field(): string {
    $token = htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8');
    return '<input type="hidden" name="csrf_token" value="' . $token . '">' . "\n";
}

/**
 * Get the current CSRF token for use in AJAX requests
 * @return string The CSRF token
 */
function csrf_token(): string {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        // Ensure we use the same session name as the main app pages
        if (session_name() !== 'BEJEWELRY_C2_SESSID') {
            session_name('BEJEWELRY_C2_SESSID');
        }
        session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return (string) $_SESSION['csrf_token'];
}

/**
 * Emit a CSRF meta tag for AJAX-capable pages
 * @return string HTML meta tag
 */
function csrf_meta_tag(): string {
    return '<meta name="csrf-token" content="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Validate CSRF token from $_POST or custom header (for AJAX)
 * Call this at the very top of any file handling $_POST
 * 
 * Checks:
 * 1. $_POST['csrf_token'] (for form submissions)
 * 2. X-CSRF-Token header (for AJAX requests)
 * 
 * @throws Exception on validation failure
 * @return void
 */
function csrf_validate(): void {
    // Ensure session is active
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    
    // Ensure session token exists
    $sessionToken = csrf_token();
    
    // Get submitted token from POST or header
    $submitToken = null;
    if (!empty($_POST['csrf_token'])) {
        $submitToken = (string) $_POST['csrf_token'];
    } elseif (!empty($_SERVER['HTTP_X_CSRF_TOKEN'])) {
        $submitToken = (string) $_SERVER['HTTP_X_CSRF_TOKEN'];
    }

    // DEBUG: Log what we're comparing
    error_log("CSRF Debug - Session token exists: " . (!empty($sessionToken) ? 'yes' : 'no'));
    error_log("CSRF Debug - Submit token exists: " . (!empty($submitToken) ? 'yes' : 'no'));

    // If we have both tokens, validate they match
    if ($submitToken && $sessionToken) {
        if (hash_equals($sessionToken, $submitToken)) {
            error_log("CSRF Debug - Tokens match - VALID");
            return; // Valid token
        } else {
            error_log("CSRF Debug - Tokens do NOT match - session: " . substr($sessionToken, 0, 8) . "... vs submit: " . substr($submitToken, 0, 8) . "...");
            throw new \Exception('CSRF token mismatch');
        }
    } else {
        error_log("CSRF Debug - Missing one or both tokens");
        throw new \Exception('CSRF token missing');
    }
}

?>
