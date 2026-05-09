<?php
/**
 * Bejewelry Admin — Product create/update/delete (MySQL only). POST only.
 * Redirects back to products.php after success.
 */
require_once __DIR__ . '/admin_auth.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../inc.php';
admin_require_page('products');
require_once __DIR__ . '/../notification_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: products.php');
  exit;
}

csrf_validate();

$pdo = adminDb();
$action = trim((string) ($_POST['action'] ?? ''));
$redirect = 'products.php';
if (!empty($_GET['page'])) $redirect .= '?page=' . (int)$_GET['page'];
if (!empty($_GET['category'])) $redirect .= (strpos($redirect, '?') !== false ? '&' : '?') . 'category=' . rawurlencode($_GET['category']);
if (!empty($_GET['search'])) $redirect .= (strpos($redirect, '?') !== false ? '&' : '?') . 'search=' . rawurlencode($_GET['search']);

function productsUploadDir(): string {
  $dir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'products';
  if (!is_dir($dir)) {
    @mkdir($dir, 0777, true);
  }
  return $dir;
}

function handleProductImageUpload(?array $file): ?string {
  if (!$file || !isset($file['error'])) return null;
  if ((int)$file['error'] === UPLOAD_ERR_NO_FILE) return null;
  if ((int)$file['error'] !== UPLOAD_ERR_OK) return null;
  if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) return null;
  if (!isset($file['size']) || (int)$file['size'] <= 0) return null;
  if ((int)$file['size'] > 5 * 1024 * 1024) return null; // 5MB

  $finfo = new finfo(FILEINFO_MIME_TYPE);
  $mime = strtolower((string) $finfo->file($file['tmp_name']));
  $allowed = [
    'image/jpeg' => 'jpg',
    'image/jpg'  => 'jpg',
    'image/pjpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
    'image/gif'  => 'gif',
    'image/avif' => 'avif',
  ];
  if (!isset($allowed[$mime])) {
    $ext = strtolower((string) pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
    $extMap = [
      'jpg' => 'jpg',
      'jpeg' => 'jpg',
      'png' => 'png',
      'webp' => 'webp',
      'gif' => 'gif',
      'avif' => 'avif',
    ];
    if (!isset($extMap[$ext])) return null;
    $mime = 'ext/' . $ext;
    $allowed[$mime] = $extMap[$ext];
  }

  $ext = $allowed[$mime];
  $name = bin2hex(random_bytes(12)) . '.' . $ext;
  $dest = productsUploadDir() . DIRECTORY_SEPARATOR . $name;
  if (!@move_uploaded_file($file['tmp_name'], $dest)) return null;
  return $name;
}

if ($action === 'create') {
  $name = trim((string) ($_POST['name'] ?? ''));
  $sku = trim((string) ($_POST['sku'] ?? ''));
  $price = isset($_POST['price']) ? (float) $_POST['price'] : 0;
  $stock = isset($_POST['stock']) ? (int) $_POST['stock'] : 0;
  $description = trim((string) ($_POST['description'] ?? ''));
  $material = trim((string) ($_POST['material'] ?? ''));
  $categoryName = trim((string) ($_POST['category'] ?? ''));
  $featured = !empty($_POST['featured']) ? 1 : 0;
  $categoryId = null;
  if ($categoryName !== '') {
    $c = $pdo->prepare('SELECT id FROM categories WHERE name = ?');
    $c->execute([$categoryName]);
    $categoryId = $c->fetchColumn();
  }
  if ($name !== '' && $price > 0) {
    $imageName = handleProductImageUpload($_FILES['image'] ?? null);
    $badge = $featured ? 'best' : '';
    $ins = $pdo->prepare('INSERT INTO products (name, description, category_id, price, material, stock, image, badge) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    $ins->execute([$name, $description ?: null, $categoryId, $price, $material ?: null, $stock, $imageName, $badge]);
    $actor = current_user();
    bejewelry_audit_log(
      (int) ($actor['id'] ?? 0) ?: null,
      (string) ($actor['email'] ?? ''),
      'add_product'
    );
  }
  header('Location: ' . $redirect);
  exit;
}

if ($action === 'toggle_sale') {
  $id = (int) ($_POST['id'] ?? 0);
  $saleState = (int) ($_POST['sale_state'] ?? 0);
  $salePrice = isset($_POST['sale_price']) ? (float) $_POST['sale_price'] : 0.0;

  if ($id > 0) {
    $sel = $pdo->prepare('SELECT price, orig_price FROM products WHERE id = ?');
    $sel->execute([$id]);
    $row = $sel->fetch(PDO::FETCH_ASSOC);

    if ($row) {
      $price = (float) ($row['price'] ?? 0);
      $origPrice = isset($row['orig_price']) ? (float) $row['orig_price'] : 0.0;

      if ($saleState === 1) {
        if ($salePrice > 0 && $salePrice < $price) {
          $newOrigPrice = $origPrice > $price ? $origPrice : $price;
          $up = $pdo->prepare('UPDATE products SET badge = ?, price = ?, orig_price = ?, updated_at = NOW() WHERE id = ?');
          $up->execute(['sale', $salePrice, $newOrigPrice, $id]);
          // push wishlist notifications for this sale
          $pid = $id;
          $pname = (string) ($pdo->query('SELECT name FROM products WHERE id = ' . (int)$pid)->fetchColumn() ?: 'An item');
          $priceVal = (float) $salePrice;
          $eventKey = 'wishlist_sale:' . $pid . ':' . number_format($priceVal, 2, '.', '');
          $title = 'Wishlist item on sale';
          $message = $pname . ' is on sale now.';
          $usersStmt = $pdo->prepare("SELECT w.user_id FROM wishlist w LEFT JOIN email_prefs ep ON ep.user_id = w.user_id WHERE w.product_id = ? AND COALESCE(ep.wishlist, 1) = 1");
          $usersStmt->execute([$pid]);
          foreach ($usersStmt->fetchAll(PDO::FETCH_ASSOC) as $urow) {
            $uid = (int) ($urow['user_id'] ?? 0);
            if ($uid <= 0) continue;
            bejewelry_notification_push($pdo, $uid, 'wishlist', $eventKey, $title, $message, 'product_detail.php?id=' . $pid);
          }
        }
      } else {
        if ($origPrice > $price) {
          $up = $pdo->prepare('UPDATE products SET badge = ?, price = ?, orig_price = NULL, updated_at = NOW() WHERE id = ?');
          $up->execute(['', $origPrice, $id]);
        } else {
          $up = $pdo->prepare('UPDATE products SET badge = ?, orig_price = NULL, updated_at = NOW() WHERE id = ?');
          $up->execute(['', $id]);
        }
      }

      $actor = current_user();
      bejewelry_audit_log(
        (int) ($actor['id'] ?? 0) ?: null,
        (string) ($actor['email'] ?? ''),
        'toggle_sale_product'
      );
    }
  }

  header('Location: ' . $redirect);
  exit;
}

if ($action === 'update') {
  $id = (int) ($_POST['id'] ?? 0);
  $name = trim((string) ($_POST['name'] ?? ''));
  $sku = trim((string) ($_POST['sku'] ?? ''));
  $price = isset($_POST['price']) ? (float) $_POST['price'] : 0;
  $stock = isset($_POST['stock']) ? (int) $_POST['stock'] : 0;
  $description = trim((string) ($_POST['description'] ?? ''));
  $material = trim((string) ($_POST['material'] ?? ''));
  $categoryName = trim((string) ($_POST['category'] ?? ''));
  $featured = !empty($_POST['featured']) ? 1 : 0;
  $categoryId = null;
  if ($categoryName !== '') {
    $c = $pdo->prepare('SELECT id FROM categories WHERE name = ?');
    $c->execute([$categoryName]);
    $categoryId = $c->fetchColumn();
  }
  if ($id > 0 && $name !== '' && $price > 0) {
    // load old product for change detection
    $oldStmt = $pdo->prepare('SELECT name, price, orig_price, badge FROM products WHERE id = ?');
    $oldStmt->execute([$id]);
    $oldRow = $oldStmt->fetch(PDO::FETCH_ASSOC) ?: [];
    $badge = $featured ? 'best' : '';

    $newImage = handleProductImageUpload($_FILES['image'] ?? null);
    if ($newImage) {
      $old = $pdo->prepare('SELECT image FROM products WHERE id = ?');
      $old->execute([$id]);
      $oldImage = (string) $old->fetchColumn();
      if ($oldImage) {
        $oldPath = productsUploadDir() . DIRECTORY_SEPARATOR . $oldImage;
        if (is_file($oldPath)) @unlink($oldPath);
      }
      $up = $pdo->prepare('UPDATE products SET name = ?, description = ?, category_id = ?, price = ?, material = ?, stock = ?, image = ?, badge = ?, updated_at = NOW() WHERE id = ?');
      $up->execute([$name, $description ?: null, $categoryId, $price, $material ?: null, $stock, $newImage, $badge, $id]);
    } else {
      $up = $pdo->prepare('UPDATE products SET name = ?, description = ?, category_id = ?, price = ?, material = ?, stock = ?, badge = ?, updated_at = NOW() WHERE id = ?');
      $up->execute([$name, $description ?: null, $categoryId, $price, $material ?: null, $stock, $badge, $id]);
    }
    // push wishlist notifications if item became a sale or price dropped
    $newStmt = $pdo->prepare('SELECT id, name, price, orig_price, badge FROM products WHERE id = ? LIMIT 1');
    $newStmt->execute([$id]);
    $newRow = $newStmt->fetch(PDO::FETCH_ASSOC) ?: [];
    $oldOnSale = (isset($oldRow['badge']) && $oldRow['badge'] === 'sale') || (isset($oldRow['orig_price']) && isset($oldRow['price']) && (float)$oldRow['orig_price'] > (float)$oldRow['price']);
    $newOnSale = (isset($newRow['badge']) && $newRow['badge'] === 'sale') || (isset($newRow['orig_price']) && isset($newRow['price']) && (float)$newRow['orig_price'] > (float)$newRow['price']);
    $priceDropped = (isset($oldRow['price']) && isset($newRow['price']) && (float)$newRow['price'] < (float)$oldRow['price']);
    if ($newOnSale && (!$oldOnSale || $priceDropped)) {
      $pid = (int) $newRow['id'];
      $pname = trim((string) ($newRow['name'] ?? 'An item'));
      $priceVal = (float) ($newRow['price'] ?? 0);
      $eventKey = 'wishlist_sale:' . $pid . ':' . number_format($priceVal, 2, '.', '');
      $title = 'Wishlist item on sale';
      $message = $pname . ' is on sale now.';
      if (isset($newRow['orig_price']) && (float)$newRow['orig_price'] > $priceVal && $priceVal > 0) {
        $message = $pname . ' dropped from PHP ' . number_format((float)$newRow['orig_price'], 2) . ' to PHP ' . number_format($priceVal, 2) . '.';
      }
      $usersStmt = $pdo->prepare("SELECT w.user_id FROM wishlist w LEFT JOIN email_prefs ep ON ep.user_id = w.user_id WHERE w.product_id = ? AND COALESCE(ep.wishlist, 1) = 1");
      $usersStmt->execute([$pid]);
      foreach ($usersStmt->fetchAll(PDO::FETCH_ASSOC) as $urow) {
        $uid = (int) ($urow['user_id'] ?? 0);
        if ($uid <= 0) continue;
        bejewelry_notification_push($pdo, $uid, 'wishlist', $eventKey, $title, $message, 'product_detail.php?id=' . $pid);
      }
    }
    $actor = current_user();
    bejewelry_audit_log(
      (int) ($actor['id'] ?? 0) ?: null,
      (string) ($actor['email'] ?? ''),
      'edit_product'
    );
  }
  header('Location: ' . $redirect);
  exit;
}

if ($action === 'delete') {
  $id = (int) ($_POST['id'] ?? 0);
  if ($id > 0) {
    $old = $pdo->prepare('SELECT image FROM products WHERE id = ?');
    $old->execute([$id]);
    $oldImage = (string) $old->fetchColumn();
    if ($oldImage) {
      $oldPath = productsUploadDir() . DIRECTORY_SEPARATOR . $oldImage;
      if (is_file($oldPath)) @unlink($oldPath);
    }
    $pdo->prepare('DELETE FROM products WHERE id = ?')->execute([$id]);
    $actor = current_user();
    bejewelry_audit_log(
      (int) ($actor['id'] ?? 0) ?: null,
      (string) ($actor['email'] ?? ''),
      'delete_product'
    );
  }
  header('Location: ' . $redirect);
  exit;
}

header('Location: ' . $redirect);
exit;
