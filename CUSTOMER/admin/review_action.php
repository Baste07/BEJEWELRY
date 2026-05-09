<?php
declare(strict_types=1);

require_once __DIR__ . '/admin_auth.php';
require_once __DIR__ . '/db.php';
admin_require_page('review_action');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: reviews.php');
  exit;
}
csrf_validate();
$pdo = adminDb();
$action = $_POST['action'] ?? '';
$id = (int) ($_POST['id'] ?? 0);
$redirect = 'reviews.php';
if (isset($_GET['filter'])) $redirect .= '?filter=' . urlencode($_GET['filter']);
if (isset($_GET['page'])) $redirect .= (strpos($redirect, '?') !== false ? '&' : '?') . 'page=' . (int) $_GET['page'];
if (isset($_GET['search'])) $redirect .= (strpos($redirect, '?') !== false ? '&' : '?') . 'search=' . urlencode($_GET['search']);

if (!$id) {
  header('Location: ' . $redirect);
  exit;
}

if ($action === 'approve') {
  $pdo->prepare("UPDATE product_reviews SET status = 'approved' WHERE id = ?")->execute([$id]);
} elseif ($action === 'reject') {
  $pdo->prepare("UPDATE product_reviews SET status = 'rejected' WHERE id = ?")->execute([$id]);
} elseif ($action === 'delete') {
  $pdo->prepare('DELETE FROM product_reviews WHERE id = ?')->execute([$id]);
}

header('Location: ' . $redirect);
exit;
