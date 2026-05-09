<?php
/* ═══════════════════════════════════════════════════════════
   BEJEWELRY — Products Endpoints
   GET    /api/products          → list with filters
   GET    /api/products?id=N     → single product
   POST   /api/products          → create (admin)
   POST   /api/products/upload   → upload product image (admin)
   PATCH  /api/products?id=N     → update (admin)
   DELETE /api/products?id=N     → soft-delete (admin)
═══════════════════════════════════════════════════════════ */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/csrf_helper.php';

setHeaders();

$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) ? (int)$_GET['id'] : null;

// ── Helper: format product row ────────────────────────────
function formatProduct(array $p): array {
    $p['id']         = (int)$p['id'];
    $p['price']      = (float)$p['price'];
    $p['orig_price'] = $p['orig_price'] ? (float)$p['orig_price'] : null;
    $p['stars']      = (int)$p['stars'];
    $p['reviews']    = (int)$p['reviews'];
    $p['stock']      = (int)$p['stock'];
    $p['is_active']  = (bool)$p['is_active'];
    $p['image_url']  = productImageUrl($p['image']);
    return $p;
}

// ── GET /products ─────────────────────────────────────────
if ($method === 'GET') {
    // Single product
    if ($id) {
        $stmt = db()->prepare('
            SELECT p.*, c.name AS cat, c.slug AS cat_slug
            FROM products p
            LEFT JOIN categories c ON c.id = p.category_id
            WHERE p.id = ? AND p.is_active = 1
        ');
        $stmt->execute([$id]);
        $p = $stmt->fetch();
        if (!$p) respondError('Product not found.', 404);
        respond(formatProduct($p));
    }

    // List with optional filters
    $where  = ['p.is_active = 1'];
    $params = [];

    if (!empty($_GET['cat'])) {
        $where[]  = 'c.name = ?';
        $params[] = $_GET['cat'];
    }
    if (!empty($_GET['cat_slug'])) {
        $where[]  = 'c.slug = ?';
        $params[] = $_GET['cat_slug'];
    }
    if (!empty($_GET['badge'])) {
        if ($_GET['badge'] === 'new') {
            // New Arrivals: any product created in the last month
            $where[] = 'p.created_at > DATE_SUB(NOW(), INTERVAL 1 MONTH)';
        } elseif ($_GET['badge'] === 'sale') {
            // Sale: products with discount (orig_price > price) or marked as sale
            $where[] = '(p.badge = ? OR (p.orig_price IS NOT NULL AND p.orig_price > p.price))';
            $params[] = 'sale';
        } else {
            // Default: match badge as-is
            $where[] = 'p.badge = ?';
            $params[] = $_GET['badge'];
        }
    }
    if (!empty($_GET['search'])) {
        $where[]  = '(p.name LIKE ? OR c.name LIKE ? OR p.material LIKE ?)';
        $s = '%' . $_GET['search'] . '%';
        $params = array_merge($params, [$s, $s, $s]);
    }

    $orderBy = match($_GET['sort'] ?? '') {
        'price_asc'  => 'p.price ASC',
        'price_desc' => 'p.price DESC',
        'name'       => 'p.name ASC',
        'newest'     => 'p.created_at DESC',
        'popular'    => 'p.reviews DESC',
        default      => 'p.id ASC',
    };

    $whereSQL = implode(' AND ', $where);
    $sql = "
        SELECT p.*, c.name AS cat, c.slug AS cat_slug
        FROM products p
        LEFT JOIN categories c ON c.id = p.category_id
        WHERE $whereSQL
        ORDER BY $orderBy
    ";

    // Pagination
    $limit  = max(1, min(100, (int)($_GET['limit']  ?? 100)));
    $offset = max(0, (int)($_GET['offset'] ?? 0));
    $sql   .= " LIMIT $limit OFFSET $offset";

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll();

    // Total count
    $cntSQL  = "SELECT COUNT(*) FROM products p LEFT JOIN categories c ON c.id = p.category_id WHERE $whereSQL";
    $cntStmt = db()->prepare($cntSQL);
    $cntStmt->execute($params);
    $total = (int)$cntStmt->fetchColumn();

    respond(['data' => array_map('formatProduct', $rows), 'total' => $total]);
}

// ── POST /products (create or upload) ────────────────────
if ($method === 'POST') {
    $auth = requireAuth();
    if ($auth['role'] !== 'admin') respondError('Forbidden', 403);

    // Image upload
    if (($_GET['action'] ?? '') === 'upload') {
        if (empty($_FILES['image'])) respondError('No file uploaded.');
        $file = $_FILES['image'];
        $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg','jpeg','png','webp'])) respondError('Only jpg, png, webp allowed.');
        if ($file['size'] > 5 * 1024 * 1024) respondError('Max file size is 5MB.');
        $filename = 'product_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        if (!move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $filename)) {
            respondError('Upload failed.', 500);
        }
        respond(['filename' => $filename, 'url' => UPLOAD_URL . $filename], 201);
    }

    // Create product
    $b = body();
    $required = ['name', 'category_id', 'price'];
    foreach ($required as $field) {
        if (empty($b[$field])) respondError("Field '$field' is required.");
    }

    $stmt = db()->prepare('
        INSERT INTO products (name, description, category_id, price, orig_price, image, badge, stars, reviews, size_default, material, stock)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?)
    ');
    $stmt->execute([
        $b['name'],
        $b['description']  ?? null,
        (int)$b['category_id'],
        (float)$b['price'],
        !empty($b['orig_price']) ? (float)$b['orig_price'] : null,
        $b['image']        ?? null,
        $b['badge']        ?? '',
        (int)($b['stars']  ?? 5),
        (int)($b['reviews']?? 0),
        $b['size_default'] ?? 'One Size',
        $b['material']     ?? null,
        (int)($b['stock']  ?? 100),
    ]);
    $newId = (int)db()->lastInsertId();
    $newStmt = db()->prepare('SELECT p.*, c.name AS cat FROM products p LEFT JOIN categories c ON c.id=p.category_id WHERE p.id=?');
    $newStmt->execute([$newId]);
    respond(formatProduct($newStmt->fetch()), 201);
}

// ── PATCH /products?id=N ─────────────────────────────────
if ($method === 'PATCH') {
    $auth = requireAuth();
    if ($auth['role'] !== 'admin') respondError('Forbidden', 403);
    if (!$id) respondError('Product ID required.');
    $b = body();

    $allowed = ['name','description','category_id','price','orig_price','image','badge','stars','reviews','size_default','material','stock','is_active'];
    $sets    = [];
    $params  = [];
    foreach ($allowed as $field) {
        if (array_key_exists($field, $b)) {
            $sets[]   = "$field = ?";
            $params[] = $b[$field];
        }
    }
    if (!$sets) respondError('Nothing to update.');
    $params[] = $id;
    db()->prepare('UPDATE products SET ' . implode(',', $sets) . ' WHERE id = ?')->execute($params);

    $stmt = db()->prepare('SELECT p.*, c.name AS cat FROM products p LEFT JOIN categories c ON c.id=p.category_id WHERE p.id=?');
    $stmt->execute([$id]);
    respond(formatProduct($stmt->fetch()));
}

// ── DELETE /products?id=N ────────────────────────────────
if ($method === 'DELETE') {
    $auth = requireAuth();
    if ($auth['role'] !== 'admin') respondError('Forbidden', 403);
    if (!$id) respondError('Product ID required.');
    db()->prepare('UPDATE products SET is_active = 0 WHERE id = ?')->execute([$id]);
    http_response_code(204);
    exit;
}

respondError('Not found.', 404);
