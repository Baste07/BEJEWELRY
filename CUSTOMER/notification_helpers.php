<?php

function bejewelry_notifications_bootstrap(PDO $pdo): void
{
    static $ready = false;
    if ($ready) {
        return;
    }

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS customer_notifications (
            id BIGINT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            type VARCHAR(40) NOT NULL,
            event_key VARCHAR(191) NOT NULL,
            title VARCHAR(160) NOT NULL,
            message VARCHAR(255) NOT NULL,
            link_url VARCHAR(255) DEFAULT NULL,
            is_read TINYINT(1) NOT NULL DEFAULT 0,
            read_at DATETIME DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uq_customer_notifications_event (user_id, event_key),
            KEY idx_customer_notifications_feed (user_id, is_read, created_at),
            CONSTRAINT fk_customer_notifications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );

    $ready = true;
}

function bejewelry_notification_default_prefs(): array
{
    return [
        'order_updates' => 1,
        'promotions' => 1,
        'wishlist' => 0,
    ];
}

function bejewelry_notification_prefs(PDO $pdo, int $userId): array
{
    $defaults = bejewelry_notification_default_prefs();
    $stmt = $pdo->prepare('SELECT order_updates, launches, promos, wishlist FROM email_prefs WHERE user_id = ? LIMIT 1');
    $stmt->execute([$userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    if (!$row) {
        return $defaults;
    }

    return [
        'order_updates' => (int) (!empty($row['order_updates'])),
        'promotions' => (int) (!empty($row['launches']) || !empty($row['promos'])),
        'wishlist' => (int) (!empty($row['wishlist'])),
    ];
}

function bejewelry_notification_save_prefs(PDO $pdo, int $userId, array $prefs): array
{
    $defaults = bejewelry_notification_default_prefs();
    $normalized = [
        'order_updates' => isset($prefs['order_updates']) ? (int) ((bool) $prefs['order_updates']) : $defaults['order_updates'],
        'promotions' => isset($prefs['promotions']) ? (int) ((bool) $prefs['promotions']) : $defaults['promotions'],
        'wishlist' => isset($prefs['wishlist']) ? (int) ((bool) $prefs['wishlist']) : $defaults['wishlist'],
    ];

    $stmt = $pdo->prepare(
        'INSERT INTO email_prefs (user_id, order_updates, launches, promos, wishlist)
         VALUES (?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
            order_updates = VALUES(order_updates),
            launches = VALUES(launches),
            promos = VALUES(promos),
            wishlist = VALUES(wishlist)'
    );
    $stmt->execute([
        $userId,
        $normalized['order_updates'],
        $normalized['promotions'],
        $normalized['promotions'],
        $normalized['wishlist'],
    ]);

    return $normalized;
}

function bejewelry_notification_push(PDO $pdo, int $userId, string $type, string $eventKey, string $title, string $message, ?string $linkUrl = null): void
{
    if ($eventKey === '') {
        return;
    }

    $stmt = $pdo->prepare(
        'INSERT IGNORE INTO customer_notifications (user_id, type, event_key, title, message, link_url)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([$userId, $type, $eventKey, $title, $message, $linkUrl]);
}

function bejewelry_sync_customer_notifications(PDO $pdo, int $userId): void
{
    bejewelry_notifications_bootstrap($pdo);
    $prefs = bejewelry_notification_prefs($pdo, $userId);

    if (!empty($prefs['order_updates'])) {
        $orderStmt = $pdo->prepare(
            "SELECT id, status
             FROM orders
             WHERE user_id = ?
               AND status IN ('processing','delivered')
               AND created_at >= DATE_SUB(NOW(), INTERVAL 120 DAY)
             ORDER BY updated_at DESC"
        );
        $orderStmt->execute([$userId]);
        foreach ($orderStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $orderId = (string) ($row['id'] ?? '');
            $status = (string) ($row['status'] ?? '');
            if ($orderId === '' || $status === '') {
                continue;
            }
            if ($status === 'processing') {
                bejewelry_notification_push(
                    $pdo,
                    $userId,
                    'order_updates',
                    'order_status:' . $orderId . ':processing',
                    'Order ' . $orderId . ' is processing',
                    'Your order is now being prepared.',
                    'order_history.php'
                );
            } elseif ($status === 'delivered') {
                bejewelry_notification_push(
                    $pdo,
                    $userId,
                    'order_updates',
                    'order_status:' . $orderId . ':delivered',
                    'Order ' . $orderId . ' was delivered',
                    'Your order has been marked as delivered.',
                    'order_history.php'
                );
            }
        }
    }

    if (!empty($prefs['promotions'])) {
        $newProducts = $pdo->query(
            "SELECT id, name
             FROM products
             WHERE is_active = 1
               AND created_at >= DATE_SUB(NOW(), INTERVAL 45 DAY)
             ORDER BY created_at DESC"
        )->fetchAll(PDO::FETCH_ASSOC) ?: [];

        foreach ($newProducts as $row) {
            $productId = (int) ($row['id'] ?? 0);
            if ($productId <= 0) {
                continue;
            }
            $name = trim((string) ($row['name'] ?? 'New item'));
            bejewelry_notification_push(
                $pdo,
                $userId,
                'promotions',
                'new_product:' . $productId,
                'New product added',
                $name . ' is now available in the shop.',
                'product_detail.php?id=' . $productId
            );
        }

        $newPromotions = $pdo->query(
            "SELECT id, code, name
             FROM promotions
             WHERE is_active = 1
               AND created_at >= DATE_SUB(NOW(), INTERVAL 60 DAY)
             ORDER BY created_at DESC"
        )->fetchAll(PDO::FETCH_ASSOC) ?: [];

        foreach ($newPromotions as $row) {
            $promoId = (int) ($row['id'] ?? 0);
            if ($promoId <= 0) {
                continue;
            }
            $code = trim((string) ($row['code'] ?? ''));
            $name = trim((string) ($row['name'] ?? 'New promotion'));
            $label = $code !== '' ? $code : $name;
            bejewelry_notification_push(
                $pdo,
                $userId,
                'promotions',
                'new_promo:' . $promoId,
                'New promotion available',
                $label . ' can now be used on eligible orders.',
                'product-list.php?badge=sale'
            );
        }
    }

    if (!empty($prefs['wishlist'])) {
        $wishSaleStmt = $pdo->prepare(
            "SELECT p.id, p.name, p.price, p.orig_price
             FROM wishlist w
             INNER JOIN products p ON p.id = w.product_id
             WHERE w.user_id = ?
               AND p.is_active = 1
               AND (
                   p.badge = 'sale'
                   OR (p.orig_price IS NOT NULL AND p.orig_price > p.price)
               )"
        );
        $wishSaleStmt->execute([$userId]);
        foreach ($wishSaleStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $productId = (int) ($row['id'] ?? 0);
            if ($productId <= 0) {
                continue;
            }
            $name = trim((string) ($row['name'] ?? 'An item'));
            $price = (float) ($row['price'] ?? 0);
            $origPrice = (float) ($row['orig_price'] ?? 0);
            $eventKey = 'wishlist_sale:' . $productId . ':' . number_format($price, 2, '.', '');
            $message = $name . ' is on sale now.';
            if ($origPrice > $price && $price > 0) {
                $message = $name . ' dropped from PHP ' . number_format($origPrice, 2) . ' to PHP ' . number_format($price, 2) . '.';
            }
            bejewelry_notification_push(
                $pdo,
                $userId,
                'wishlist',
                $eventKey,
                'Wishlist item on sale',
                $message,
                'product_detail.php?id=' . $productId
            );
        }
    }
}

function bejewelry_list_customer_notifications(PDO $pdo, int $userId, int $limit = 25): array
{
    bejewelry_notifications_bootstrap($pdo);
    $limit = max(1, min(100, $limit));

    // Respect user preferences: only return notification types the user has enabled
    $prefs = bejewelry_notification_prefs($pdo, $userId);
    $allowed = [];
    if (!empty($prefs['order_updates'])) $allowed[] = 'order_updates';
    if (!empty($prefs['promotions'])) $allowed[] = 'promotions';
    if (!empty($prefs['wishlist'])) $allowed[] = 'wishlist';

    if (empty($allowed)) {
        return [];
    }

    $inPlaceholders = implode(',', array_fill(0, count($allowed), '?'));
    $sql = 'SELECT id, type, title, message, link_url, is_read, created_at
            FROM customer_notifications
            WHERE user_id = ? AND type IN (' . $inPlaceholders . ')
            ORDER BY created_at DESC, id DESC
            LIMIT ' . $limit;
    $params = array_merge([$userId], $allowed);
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

function bejewelry_unread_customer_notification_count(PDO $pdo, int $userId): int
{
    bejewelry_notifications_bootstrap($pdo);
    // Count only notifications for enabled types
    $prefs = bejewelry_notification_prefs($pdo, $userId);
    $allowed = [];
    if (!empty($prefs['order_updates'])) $allowed[] = 'order_updates';
    if (!empty($prefs['promotions'])) $allowed[] = 'promotions';
    if (!empty($prefs['wishlist'])) $allowed[] = 'wishlist';

    if (empty($allowed)) return 0;

    $inPlaceholders = implode(',', array_fill(0, count($allowed), '?'));
    $sql = 'SELECT COUNT(*) FROM customer_notifications WHERE user_id = ? AND is_read = 0 AND type IN (' . $inPlaceholders . ')';
    $params = array_merge([$userId], $allowed);
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return (int) $stmt->fetchColumn();
}

function bejewelry_mark_customer_notification_read(PDO $pdo, int $userId, int $id): void
{
    bejewelry_notifications_bootstrap($pdo);
    $stmt = $pdo->prepare('UPDATE customer_notifications SET is_read = 1, read_at = NOW() WHERE user_id = ? AND id = ?');
    $stmt->execute([$userId, $id]);
}

function bejewelry_mark_all_customer_notifications_read(PDO $pdo, int $userId): void
{
    bejewelry_notifications_bootstrap($pdo);
    $stmt = $pdo->prepare('UPDATE customer_notifications SET is_read = 1, read_at = NOW() WHERE user_id = ? AND is_read = 0');
    $stmt->execute([$userId]);
}
