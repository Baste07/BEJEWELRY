<?php
declare(strict_types=1);

/**
 * Persist PayMongo checkout context in MySQL so paymongo_return.php can recover
 * after a lost PHP session (same user must still be logged in; cart is in DB).
 */

/** Restore $_SESSION['paymongo_pending'] from DB when URL has pm_state or checkout_session_id. */
function paymongo_pending_restore_from_db(PDO $pdo): void
{
    if (isset($_SESSION['paymongo_pending']['checkout_session_id']) && ($_SESSION['paymongo_pending']['checkout_session_id'] ?? '') !== '') {
        return;
    }
    $uid = (int) current_user_id();
    if ($uid < 1) {
        return;
    }
    $pm = trim((string) ($_GET['pm_state'] ?? ''));
    $cs = trim((string) ($_GET['checkout_session_id'] ?? $_GET['session_id'] ?? $_GET['checkout_session'] ?? ''));
    if ($pm === '' && $cs === '') {
        return;
    }
    try {
        if ($pm !== '') {
            $st = $pdo->prepare('SELECT checkout_session_id, pm_state, expected_total_cents, post_json FROM paymongo_checkout_pending WHERE user_id = ? AND pm_state = ? LIMIT 1');
            $st->execute([$uid, $pm]);
        } else {
            $st = $pdo->prepare('SELECT checkout_session_id, pm_state, expected_total_cents, post_json FROM paymongo_checkout_pending WHERE user_id = ? AND checkout_session_id = ? LIMIT 1');
            $st->execute([$uid, $cs]);
        }
        $row = $st->fetch();
        if (!$row) {
            return;
        }
        $json = bejewelry_decrypt_sensitive((string) $row['post_json']);
        $post = json_decode((string) $json, true);
        if (!is_array($post)) {
            return;
        }
        $_SESSION['paymongo_pending'] = [
            'pm_state' => $row['pm_state'],
            'checkout_session_id' => $row['checkout_session_id'],
            'expected_total_cents' => (int) $row['expected_total_cents'],
            'post' => $post,
        ];
    } catch (Throwable $e) {
        error_log('paymongo_pending_restore: ' . $e->getMessage());
    }
}

function paymongo_pending_save(PDO $pdo, int $userId, string $checkoutSessionId, string $pmState, int $expectedCents, array $post): void
{
    try {
        $pdo->prepare('DELETE FROM paymongo_checkout_pending WHERE user_id = ?')->execute([$userId]);
        $payload = bejewelry_encrypt_sensitive(json_encode($post, JSON_UNESCAPED_UNICODE));
        $st = $pdo->prepare('INSERT INTO paymongo_checkout_pending (user_id, checkout_session_id, pm_state, expected_total_cents, post_json) VALUES (?,?,?,?,?)');
        $st->execute([$userId, $checkoutSessionId, $pmState, $expectedCents, $payload]);
    } catch (Throwable $e) {
        error_log('paymongo_pending_save: ' . $e->getMessage());
    }
}

function paymongo_pending_delete(PDO $pdo, int $userId): void
{
    try {
        $pdo->prepare('DELETE FROM paymongo_checkout_pending WHERE user_id = ?')->execute([$userId]);
    } catch (Throwable $e) {
        // table may be missing on old installs
    }
}
