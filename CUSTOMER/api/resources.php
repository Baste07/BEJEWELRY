<?php
/* ═══════════════════════════════════════════════════════════
   BEJEWELRY — Wishlist, Orders & Users Endpoints
   Routed via index.php based on $resource variable.
═══════════════════════════════════════════════════════════ */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/../order_commit.php';
require_once __DIR__ . '/../inc.php';
require_once __DIR__ . '/csrf_helper.php';

setHeaders();

$method   = $_SERVER['REQUEST_METHOD'];
$resource = $_GET['resource'] ?? '';  // set by index.php router
$auth     = requireAuth();
$userId   = $auth['user_id'];

/* ══════════════════════════════════════════════════════════
   WISHLIST
   GET    /api/wishlist
   POST   /api/wishlist?product_id=N
   DELETE /api/wishlist?product_id=N
══════════════════════════════════════════════════════════ */
if ($resource === 'wishlist') {
    if ($method === 'GET') {
        $stmt = db()->prepare('
            SELECT p.id, p.name, p.price, p.orig_price, p.image, p.badge,
                   p.stars, p.reviews, p.size_default, p.material,
                   c.name AS cat
            FROM wishlist w
            JOIN products p ON p.id = w.product_id
            LEFT JOIN categories c ON c.id = p.category_id
            WHERE w.user_id = ?
            ORDER BY w.added_at DESC
        ');
        $stmt->execute([$userId]);
        $items = $stmt->fetchAll();
        foreach ($items as &$item) {
            $item['id']        = (int)$item['id'];
            $item['price']     = (float)$item['price'];
            $item['orig_price']= $item['orig_price'] ? (float)$item['orig_price'] : null;
            $item['image_url'] = productImageUrl($item['image']);
        }
        respond(['items' => $items]);
    }

    $productId = (int)($_GET['product_id'] ?? 0);
    if (!$productId) respondError('product_id required.');

    if ($method === 'POST') {
        db()->prepare('INSERT IGNORE INTO wishlist (user_id, product_id) VALUES (?,?)')->execute([$userId, $productId]);
        http_response_code(204); exit;
    }
    if ($method === 'DELETE') {
        db()->prepare('DELETE FROM wishlist WHERE user_id = ? AND product_id = ?')->execute([$userId, $productId]);
        http_response_code(204); exit;
    }
    respondError('Not found.', 404);
}

/* ══════════════════════════════════════════════════════════
   ORDERS
   GET    /api/orders              → list
   GET    /api/orders?id=BJ-xxx    → single
   POST   /api/orders              → place order
   PATCH  /api/orders?id=BJ-xxx    → update status (admin)
══════════════════════════════════════════════════════════ */
if ($resource === 'orders') {
    // Single order
    if ($method === 'GET' && !empty($_GET['id'])) {
        $orderId = $_GET['id'];
        $stmt = db()->prepare('SELECT * FROM orders WHERE id = ? AND user_id = ?');
        $stmt->execute([$orderId, $userId]);
        $order = $stmt->fetch();
        if (!$order) respondError('Order not found.', 404);
        $order = bejewelry_decrypt_order_shipping_fields($order);

        $iStmt = db()->prepare('SELECT * FROM order_items WHERE order_id = ?');
        $iStmt->execute([$orderId]);
        $order['items'] = $iStmt->fetchAll();
        foreach ($order['items'] as &$i) {
            $i['price']     = (float)$i['price'];
            $i['qty']       = (int)$i['qty'];
            $i['image_url'] = productImageUrl($i['image']);
        }
        respond($order);
    }

    // List orders
    if ($method === 'GET') {
        $where  = ['o.user_id = ?'];
        $params = [$userId];
        if (!empty($_GET['status'])) {
            $status = (string) $_GET['status'];
            if ($status === 'pending') {
                $status = 'processing';
            }
            $where[] = 'o.status = ?';
            $params[] = $status;
        }
        $sql   = 'SELECT o.*, COUNT(oi.id) AS item_count FROM orders o LEFT JOIN order_items oi ON oi.order_id = o.id WHERE ' . implode(' AND ', $where) . ' GROUP BY o.id ORDER BY o.created_at DESC';
        $stmt  = db()->prepare($sql);
        $stmt->execute($params);
        $rows  = $stmt->fetchAll();
        foreach ($rows as &$r) {
            $r = bejewelry_decrypt_order_shipping_fields($r);
            $r['subtotal']     = (float)$r['subtotal'];
            $r['shipping_fee'] = (float)$r['shipping_fee'];
            $r['total']        = (float)$r['total'];
            $r['item_count']   = (int)$r['item_count'];
        }
        respond(['data' => $rows, 'total' => count($rows)]);
    }

    // Place order
    if ($method === 'POST') {
        csrf_validate();
        $b = body();
        if (empty($b['items']) || !is_array($b['items'])) respondError('items[] required.');

        $subtotal = 0;
        $lineItems = [];
        foreach ($b['items'] as $item) {
            $pid  = (int)($item['product_id'] ?? 0);
            $qty  = max(1, (int)($item['qty'] ?? 1));
            $size = $item['size'] ?? 'One Size';
            $pStmt = db()->prepare('SELECT p.*, c.name AS cat FROM products p LEFT JOIN categories c ON c.id=p.category_id WHERE p.id=? AND p.is_active=1');
            $pStmt->execute([$pid]);
            $prod = $pStmt->fetch();
            if (!$prod) respondError("Product ID $pid not found.");
            $avail = (int) ($prod['stock'] ?? 0);
            if ($avail < $qty) {
                respondError('Not enough stock for "' . ($prod['name'] ?? 'item') . '" (only ' . $avail . ' available).');
            }
            $linePrice = (float)$prod['price'] * $qty;
            $subtotal += $linePrice;
            $lineItems[] = ['product' => $prod, 'qty' => $qty, 'size' => $size];
        }

        $ship  = $subtotal >= FREE_SHIP_THRESHOLD ? 0 : SHIPPING_FEE;
        $total = $subtotal + $ship;

        $pdo = db();
        $orderId = '';
        $pdo->beginTransaction();
        try {
            bejewelry_lock_order_sequence($pdo);
            try {
                $orderId = bejewelry_next_order_id($pdo);

                $encShip = bejewelry_encrypt_order_shipping_fields([
                    'ship_name' => $b['ship_name'] ?? null,
                    'ship_street' => $b['ship_street'] ?? null,
                    'ship_city' => $b['ship_city'] ?? null,
                    'ship_province' => $b['ship_province'] ?? null,
                    'ship_zip' => $b['ship_zip'] ?? null,
                    'ship_phone' => $b['ship_phone'] ?? null,
                ]);

                $oStmt = $pdo->prepare('INSERT INTO orders (id, user_id, status, subtotal, shipping_fee, total, ship_name, ship_street, ship_city, ship_province, ship_zip, ship_phone, payment_method, notes) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)');
                $oStmt->execute([
                    $orderId, $userId, 'processing',
                    $subtotal, $ship, $total,
                    $encShip['ship_name'] ?? ($b['ship_name'] ?? null),
                    $encShip['ship_street'] ?? null,
                    $encShip['ship_city'] ?? null,
                    $encShip['ship_province'] ?? null,
                    $encShip['ship_zip'] ?? null,
                    $encShip['ship_phone'] ?? null,
                    $b['payment_method']?? 'ewallet',
                    $b['notes']         ?? null,
                ]);
                $iStmt = $pdo->prepare('INSERT INTO order_items (order_id, product_id, name, image, cat, size, price, qty) VALUES (?,?,?,?,?,?,?,?)');
                foreach ($lineItems as $li) {
                    $iStmt->execute([$orderId, $li['product']['id'], $li['product']['name'], $li['product']['image'], $li['product']['cat'], $li['size'], $li['product']['price'], $li['qty']]);
                }
                bejewelry_deduct_stock_for_order($pdo, $lineItems, $orderId, $userId);
                $pdo->prepare('DELETE FROM cart_items WHERE user_id = ?')->execute([$userId]);
                $pdo->commit();
            } finally {
                bejewelry_unlock_order_sequence($pdo);
            }
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            respondError('Order failed: ' . $e->getMessage(), 500);
        }

        respond(['order' => ['id' => $orderId, 'status' => 'processing', 'total' => $total]], 201);
    }

    // Update status (admin)
    if ($method === 'PATCH' && !empty($_GET['id'])) {
        if ($auth['role'] !== 'admin') respondError('Forbidden', 403);
        $b      = body();
        $status = $b['status'] ?? '';
        if (!in_array($status, ['processing','shipped','delivered','cancelled'])) {
            respondError('Invalid status.');
        }
        db()->prepare('UPDATE orders SET status = ? WHERE id = ?')->execute([$status, $_GET['id']]);
        http_response_code(204); exit;
    }

    respondError('Not found.', 404);
}

/* ══════════════════════════════════════════════════════════
   USERS (profile + addresses)
   GET    /api/users/me
   PATCH  /api/users/me
   GET    /api/users/me/addresses
   POST   /api/users/me/addresses
   PATCH  /api/users/me/addresses?id=N
   DELETE /api/users/me/addresses?id=N
══════════════════════════════════════════════════════════ */
if ($resource === 'users') {
    $sub = $_GET['sub'] ?? 'me'; // set by router

    // ── Profile ──────────────────────────────────────────
    if ($sub === 'me') {
        if ($method === 'GET') {
            $stmt = db()->prepare('SELECT id,first_name,last_name,username,email,phone,gender,birthday,city,role,created_at FROM users WHERE id=?');
            $stmt->execute([$userId]);
            $row = $stmt->fetch();
            if (is_array($row)) {
                $row = bejewelry_decrypt_user_private_fields($row);
            }
            respond($row);
        }
        if ($method === 'PATCH') {
            $b = body();
            $allowed = ['first_name','last_name','username','phone','gender','birthday','city'];
            $sets = []; $params = [];
            foreach ($allowed as $f) {
                if (array_key_exists($f, $b)) {
                    $v = $b[$f];
                    if (in_array($f, ['phone','birthday','city'], true)) {
                        $v = bejewelry_encrypt_sensitive($v === null ? null : (string) $v);
                    }
                    $sets[] = "$f=?";
                    $params[] = $v;
                }
            }
            // Password change
            if (!empty($b['new_password'])) {
                if (strlen($b['new_password']) < 6) respondError('Password too short.');
                $sets[]   = 'password_hash=?';
                $params[] = password_hash($b['new_password'], PASSWORD_BCRYPT);
            }
            if ($sets) {
                $params[] = $userId;
                db()->prepare('UPDATE users SET ' . implode(',', $sets) . ' WHERE id=?')->execute($params);
            }
            $stmt = db()->prepare('SELECT id,first_name,last_name,username,email,phone,gender,birthday,city,role FROM users WHERE id=?');
            $stmt->execute([$userId]);
            $row = $stmt->fetch();
            if (is_array($row)) {
                $row = bejewelry_decrypt_user_private_fields($row);
            }
            respond($row);
        }
    }

    // ── Addresses ─────────────────────────────────────────
    if ($sub === 'addresses') {
        if ($method === 'GET') {
            $stmt = db()->prepare('SELECT * FROM addresses WHERE user_id=? ORDER BY is_default DESC, id ASC');
            $stmt->execute([$userId]);
            $rows = $stmt->fetchAll();
            foreach ($rows as &$row) {
                $row = bejewelry_decrypt_address_private_fields($row);
            }
            respond($rows);
        }
        if ($method === 'POST') {
            csrf_validate();
            $b = body();
            if (!empty($b['is_default'])) {
                db()->prepare('UPDATE addresses SET is_default=0 WHERE user_id=?')->execute([$userId]);
            }
            $encAddr = bejewelry_encrypt_address_private_fields([
                'street' => $b['street'] ?? '',
                'city' => $b['city'] ?? '',
                'province' => $b['province'] ?? null,
                'zip' => $b['zip'] ?? null,
                'phone' => $b['phone'] ?? null,
            ]);
            $stmt = db()->prepare('INSERT INTO addresses (user_id,label,name,street,city,province,zip,phone,is_default) VALUES (?,?,?,?,?,?,?,?,?)');
            $stmt->execute([$userId, $b['label']??'Home', $b['name']??'', $encAddr['street'], $encAddr['city'], $encAddr['province'], $encAddr['zip'], $encAddr['phone'], !empty($b['is_default'])?1:0]);
            respond(['id' => (int)db()->lastInsertId(), ...$b], 201);
        }
        $addrId = (int)($_GET['id'] ?? 0);
        if ($method === 'PATCH' && $addrId) {
            $b = body();
            if (!empty($b['is_default'])) {
                db()->prepare('UPDATE addresses SET is_default=0 WHERE user_id=?')->execute([$userId]);
            }
            $allowed = ['label','name','street','city','province','zip','phone','is_default'];
            $sets = []; $params = [];
            foreach ($allowed as $f) {
                if (array_key_exists($f,$b)) {
                    $v = $b[$f];
                    if (in_array($f, ['street','city','province','zip','phone'], true)) {
                        $v = bejewelry_encrypt_sensitive($v === null ? null : (string) $v);
                    }
                    $sets[]="$f=?";
                    $params[]=$v;
                }
            }
            if ($sets) { $params[] = $addrId; $params[] = $userId; db()->prepare('UPDATE addresses SET '.implode(',',$sets).' WHERE id=? AND user_id=?')->execute($params); }
            $stmt = db()->prepare('SELECT * FROM addresses WHERE id=?'); $stmt->execute([$addrId]);
            $row = $stmt->fetch();
            if (is_array($row)) {
                $row = bejewelry_decrypt_address_private_fields($row);
            }
            respond($row);
        }
        if ($method === 'DELETE' && $addrId) {
            db()->prepare('DELETE FROM addresses WHERE id=? AND user_id=?')->execute([$addrId, $userId]);
            http_response_code(204); exit;
        }
    }

    respondError('Not found.', 404);
}

respondError('Not found.', 404);
