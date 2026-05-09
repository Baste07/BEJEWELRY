<?php
require_once __DIR__ . '/admin_auth.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../notification_helpers.php';

admin_require_page('promotions');

header('Content-Type: application/json; charset=utf-8');

// Ensure apply_to column exists
function ensure_promotions_apply_to_column(PDO $pdo): void
{
    try {
        $stmt = $pdo->prepare("SHOW COLUMNS FROM promotions WHERE Field = ?");
        $stmt->execute(['apply_to']);
        $exists = $stmt->fetch();
        
        if (!$exists) {
            // Column doesn't exist, add it
            $pdo->exec("ALTER TABLE promotions ADD COLUMN apply_to VARCHAR(50) DEFAULT 'all'");
        }
    } catch (PDOException $e) {
        // Log the error but don't fail the request
        error_log('promotions_action: Could not ensure apply_to column: ' . $e->getMessage());
    }
}

if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    csrf_validate();
} catch (Throwable $e) {
    http_response_code(419);
    echo json_encode(['error' => 'Invalid security token. Refresh the page and try again.']);
    exit;
}

$pdo = adminDb();
ensure_promotions_apply_to_column($pdo);

$raw = file_get_contents('php://input');
$body = json_decode((string) $raw, true);
if (!is_array($body)) {
    $body = [];
}

$action = strtolower(trim((string) ($body['action'] ?? '')));

function promotions_action_response(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

function promotions_action_parse_datetime(?string $value, bool $endOfDay = false): ?string
{
    $value = trim((string) $value);
    if ($value === '') {
        return null;
    }

    $formats = ['Y-m-d', 'Y-m-d H:i:s', 'Y-m-d\TH:i'];
    foreach ($formats as $format) {
        $dt = DateTime::createFromFormat($format, $value);
        if ($dt instanceof DateTime) {
            if ($format === 'Y-m-d') {
                $dt->setTime($endOfDay ? 23 : 0, $endOfDay ? 59 : 0, $endOfDay ? 59 : 0);
            }
            return $dt->format('Y-m-d H:i:s');
        }
    }

    return null;
}

function promotions_action_to_card(PDO $pdo, array $row): array
{
    $now = date('Y-m-d H:i:s');
    $savedStmt = $pdo->prepare('SELECT COALESCE(SUM(discount_amt), 0) FROM promotion_redemptions WHERE promotion_id = ?');
    $savedStmt->execute([(int) $row['id']]);
    $totalSaved = (float) $savedStmt->fetchColumn();

    $startAt = $row['start_at'] ?? null;
    $endAt = $row['end_at'] ?? null;
    $isActive = (int) ($row['is_active'] ?? 0) === 1;
    if ($endAt && $endAt < $now) {
        $status = 'expired';
    } elseif ($startAt && $startAt > $now) {
        $status = 'scheduled';
    } elseif (!$isActive) {
        $status = 'paused';
    } else {
        $status = 'active';
    }

    return [
        'id' => (int) $row['id'],
        'code' => (string) $row['code'],
        'name' => (string) ($row['name'] ?? ''),
        'description' => (string) ($row['name'] ?? ''),
        'status' => $status,
        'discount_type' => (string) ($row['type'] ?? 'percent') === 'percent' ? 'percentage' : 'fixed',
        'discount_value' => (float) ($row['value'] ?? 0),
        'min_order' => (float) ($row['min_order'] ?? 0),
        'usage_limit' => $row['max_uses'] !== null ? (int) $row['max_uses'] : 0,
        'times_used' => (int) ($row['used_count'] ?? 0),
        'total_saved' => $totalSaved,
        'start_date' => $startAt ? date('M j, Y', strtotime((string) $startAt)) : '',
        'end_date' => $endAt ? date('M j, Y', strtotime((string) $endAt)) : '',
        'start_date_input' => $startAt ? date('Y-m-d', strtotime((string) $startAt)) : '',
        'end_date_input' => $endAt ? date('Y-m-d', strtotime((string) $endAt)) : '',
        'apply_to' => $row['apply_to'] && strtolower((string) $row['apply_to']) !== 'all' ? ucfirst((string) $row['apply_to']) : 'All Products',
    ];
}

function promotions_action_normalize_payload(array $body): array
{
    $code = strtoupper(trim((string) ($body['code'] ?? '')));
    if ($code === '') {
        throw new InvalidArgumentException('Promo code is required.');
    }

    $discountType = strtolower(trim((string) ($body['discount_type'] ?? 'percentage')));
    $dbType = 'percent';
    $value = 0.0;

    if ($discountType === 'fixed') {
        $dbType = 'fixed';
        $value = (float) ($body['discount_value'] ?? 0);
    } elseif ($discountType === 'free_shipping') {
        $dbType = 'fixed';
        $value = defined('SHIPPING_FEE') ? (float) SHIPPING_FEE : 150.0;
    } else {
        $dbType = 'percent';
        $value = (float) ($body['discount_value'] ?? 0);
    }

    if ($value <= 0) {
        throw new InvalidArgumentException('Discount value must be greater than zero.');
    }

    $minOrder = max(0, (float) ($body['min_order'] ?? 0));
    $usageLimitRaw = $body['usage_limit'] ?? null;
    $maxUses = null;
    if ($usageLimitRaw !== null && $usageLimitRaw !== '') {
        $parsed = (int) $usageLimitRaw;
        if ($parsed <= 0) {
            throw new InvalidArgumentException('Usage limit must be empty or greater than zero.');
        }
        $maxUses = $parsed;
    }

    $startAt = promotions_action_parse_datetime((string) ($body['start_date'] ?? ''), false);
    $endAt = promotions_action_parse_datetime((string) ($body['end_date'] ?? ''), true);

    if (($body['start_date'] ?? '') !== '' && $startAt === null) {
        throw new InvalidArgumentException('Invalid start date.');
    }
    if (($body['end_date'] ?? '') !== '' && $endAt === null) {
        throw new InvalidArgumentException('Invalid end date.');
    }
    if ($startAt !== null && $endAt !== null && $startAt > $endAt) {
        throw new InvalidArgumentException('End date must be later than start date.');
    }

    $name = trim((string) ($body['description'] ?? ''));
    if ($name === '') {
        $name = $code;
    }

    $applyTo = strtolower(trim((string) ($body['apply_to'] ?? 'all')));
    $validCategories = ['all', 'rings', 'necklaces', 'bracelets', 'earrings'];
    if (!in_array($applyTo, $validCategories, true)) {
        $applyTo = 'all';
    }

    return [
        'code' => $code,
        'name' => $name,
        'type' => $dbType,
        'value' => $value,
        'min_order' => $minOrder,
        'max_uses' => $maxUses,
        'start_at' => $startAt,
        'end_at' => $endAt,
        'apply_to' => $applyTo,
    ];
}

try {
    if ($action === 'create') {
        $data = promotions_action_normalize_payload($body);

        error_log('promotions_action create: apply_to=' . $data['apply_to']);

        $stmt = $pdo->prepare('INSERT INTO promotions (code, name, type, value, min_order, max_uses, used_count, start_at, end_at, is_active, apply_to) VALUES (?, ?, ?, ?, ?, ?, 0, ?, ?, 1, ?)');
        try {
            $stmt->execute([
                $data['code'],
                $data['name'],
                $data['type'],
                $data['value'],
                $data['min_order'],
                $data['max_uses'],
                $data['start_at'],
                $data['end_at'],
                $data['apply_to'],
            ]);
        } catch (PDOException $insertErr) {
            error_log('Insert with apply_to failed: ' . $insertErr->getMessage() . '. Attempting to add apply_to column.');
            try {
                $pdo->exec("ALTER TABLE promotions ADD COLUMN apply_to VARCHAR(50) DEFAULT 'all'");
                error_log('Added apply_to column, retrying insert');
            } catch (Exception $altErr) {
                error_log('Could not add apply_to column: ' . $altErr->getMessage());
            }
            // Retry the insert
            $stmt->execute([
                $data['code'],
                $data['name'],
                $data['type'],
                $data['value'],
                $data['min_order'],
                $data['max_uses'],
                $data['start_at'],
                $data['end_at'],
                $data['apply_to'],
            ]);
        }

        $promoId = (int) $pdo->lastInsertId();
        $promoStmt = $pdo->prepare('SELECT * FROM promotions WHERE id = ? LIMIT 1');
        $promoStmt->execute([$promoId]);
        $promoRow = $promoStmt->fetch(PDO::FETCH_ASSOC) ?: [];

        if ($promoRow) {
            // Proactively enqueue promo notifications for customers with enabled promo alerts.
            bejewelry_notifications_bootstrap($pdo);
            $usersStmt = $pdo->query("SELECT u.id AS user_id
                                      FROM users u
                                      LEFT JOIN email_prefs ep ON ep.user_id = u.id
                                      WHERE u.role = 'customer'
                                        AND (COALESCE(ep.promos, 1) = 1 OR COALESCE(ep.launches, 1) = 1)");
            $title = 'New promotion available';
            $message = $promoRow['code'] . ' can now be used on eligible orders.';
            $eventKey = 'new_promo:' . $promoId;
            foreach ($usersStmt->fetchAll(PDO::FETCH_ASSOC) as $u) {
                $uid = (int) ($u['user_id'] ?? 0);
                if ($uid <= 0) {
                    continue;
                }
                bejewelry_notification_push($pdo, $uid, 'promotions', $eventKey, $title, $message, 'product-list.php?badge=sale');
            }
        }

        $actor = current_user();
        bejewelry_audit_log(
            (int) ($actor['id'] ?? 0) ?: null,
            (string) ($actor['email'] ?? ''),
            'create_promotion'
        );

        promotions_action_response([
            'ok' => true,
            'action' => 'create',
            'promo' => $promoRow ? promotions_action_to_card($pdo, $promoRow) : null,
        ]);
    }

    if ($action === 'update') {
        $id = (int) ($body['id'] ?? 0);
        if ($id <= 0) {
            promotions_action_response(['error' => 'Invalid promotion id.'], 400);
        }

        $data = promotions_action_normalize_payload($body);

        $stmt = $pdo->prepare('UPDATE promotions SET code = ?, name = ?, type = ?, value = ?, min_order = ?, max_uses = ?, start_at = ?, end_at = ?, apply_to = ?, updated_at = NOW() WHERE id = ?');
        try {
            $stmt->execute([
                $data['code'],
                $data['name'],
                $data['type'],
                $data['value'],
                $data['min_order'],
                $data['max_uses'],
                $data['start_at'],
                $data['end_at'],
                $data['apply_to'],
                $id,
            ]);
        } catch (PDOException $updateErr) {
            error_log('Update with apply_to failed: ' . $updateErr->getMessage() . '. Attempting to add apply_to column.');
            try {
                $pdo->exec("ALTER TABLE promotions ADD COLUMN apply_to VARCHAR(50) DEFAULT 'all'");
                error_log('Added apply_to column, retrying update');
            } catch (Exception $altErr) {
                error_log('Could not add apply_to column: ' . $altErr->getMessage());
            }
            // Retry the update
            $stmt->execute([
                $data['code'],
                $data['name'],
                $data['type'],
                $data['value'],
                $data['min_order'],
                $data['max_uses'],
                $data['start_at'],
                $data['end_at'],
                $data['apply_to'],
                $id,
            ]);
        }

        $promoStmt = $pdo->prepare('SELECT * FROM promotions WHERE id = ? LIMIT 1');
        $promoStmt->execute([$id]);
        $promoRow = $promoStmt->fetch(PDO::FETCH_ASSOC);
        if (!$promoRow) {
            promotions_action_response(['error' => 'Promotion not found.'], 404);
        }

        $actor = current_user();
        bejewelry_audit_log(
            (int) ($actor['id'] ?? 0) ?: null,
            (string) ($actor['email'] ?? ''),
            'edit_promotion'
        );

        promotions_action_response([
            'ok' => true,
            'action' => 'update',
            'promo' => promotions_action_to_card($pdo, $promoRow),
        ]);
    }

    if ($action === 'deactivate') {
        $id = (int) ($body['id'] ?? 0);
        if ($id <= 0) {
            promotions_action_response(['error' => 'Invalid promotion id.'], 400);
        }

        $stmt = $pdo->prepare('UPDATE promotions SET is_active = 0, updated_at = NOW() WHERE id = ?');
        $stmt->execute([$id]);

        $promoStmt = $pdo->prepare('SELECT * FROM promotions WHERE id = ? LIMIT 1');
        $promoStmt->execute([$id]);
        $promoRow = $promoStmt->fetch(PDO::FETCH_ASSOC);
        if (!$promoRow) {
            promotions_action_response(['error' => 'Promotion not found.'], 404);
        }

        $actor = current_user();
        bejewelry_audit_log(
            (int) ($actor['id'] ?? 0) ?: null,
            (string) ($actor['email'] ?? ''),
            'deactivate_promotion'
        );

        promotions_action_response([
            'ok' => true,
            'action' => 'deactivate',
            'promo' => promotions_action_to_card($pdo, $promoRow),
        ]);
    }

    if ($action === 'delete') {
        $id = (int) ($body['id'] ?? 0);
        if ($id <= 0) {
            promotions_action_response(['error' => 'Invalid promotion id.'], 400);
        }

        // Optional: prevent deletion if redemptions exist. For now, allow deletion.
        $stmt = $pdo->prepare('DELETE FROM promotions WHERE id = ?');
        $stmt->execute([$id]);

        $actor = current_user();
        bejewelry_audit_log(
            (int) ($actor['id'] ?? 0) ?: null,
            (string) ($actor['email'] ?? ''),
            'delete_promotion'
        );

        promotions_action_response([
            'ok' => true,
            'action' => 'delete',
            'id' => $id,
        ]);
    }

    promotions_action_response(['error' => 'Unsupported action.'], 400);
} catch (InvalidArgumentException $e) {
    promotions_action_response(['error' => $e->getMessage()], 422);
} catch (PDOException $e) {
    $message = 'Could not save promotion.';
    if ((int) ($e->errorInfo[1] ?? 0) === 1062) {
        $message = 'Promo code already exists.';
    }
    promotions_action_response(['error' => $message], 409);
} catch (Throwable $e) {
    promotions_action_response(['error' => 'Promotion request failed.'], 500);
}
