<?php
require_once __DIR__ . '/inc.php';
if (!current_user_id()) {
  header('Location: login.php?redirect=' . urlencode('order_history.php'));
  exit;
}
$stmt = db()->prepare('SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC');
$stmt->execute([current_user_id()]);
$orders = $stmt->fetchAll();

try {
  db()->exec('CREATE TABLE IF NOT EXISTS order_receipts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id VARCHAR(64) NOT NULL,
    user_id INT NOT NULL,
    received_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_order_user (order_id, user_id),
    KEY idx_user_received (user_id, received_at)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');
} catch (Throwable $e) {
  // If table creation is blocked, fallback is handled below as "not received yet".
}

$receiptMap = [];
try {
  $receiptStmt = db()->prepare('SELECT order_id, received_at FROM order_receipts WHERE user_id = ?');
  $receiptStmt->execute([current_user_id()]);
  foreach ($receiptStmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $receiptRow) {
    $orderId = (string) ($receiptRow['order_id'] ?? '');
    if ($orderId === '') continue;
    $receiptMap[$orderId] = (string) ($receiptRow['received_at'] ?? '');
  }
} catch (Throwable $e) {
  $receiptMap = [];
}
$reviewStmt = db()->prepare('SELECT order_id, product_id, rating FROM product_reviews WHERE user_id = ?');
$reviewStmt->execute([current_user_id()]);
$reviewMap = [];
foreach ($reviewStmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $reviewRow) {
  $reviewMap[(string) ($reviewRow['order_id'] ?? '') . ':' . (string) ($reviewRow['product_id'] ?? '')] = (int) ($reviewRow['rating'] ?? 0);
}
$ticketOrderStmt = db()->prepare('SELECT DISTINCT order_id FROM support_tickets WHERE user_id = ?');
$ticketOrderStmt->execute([current_user_id()]);
$ticketOrderIds = array_map(
  static fn($row) => (string) ($row['order_id'] ?? ''),
  $ticketOrderStmt->fetchAll(PDO::FETCH_ASSOC) ?: []
);
$ticketOrderIds = array_values(array_filter($ticketOrderIds, static fn($id) => $id !== ''));
$ordersUi = [];
$pendingReviewItems = [];
foreach ($orders as $o) {
  $o = bejewelry_decrypt_order_shipping_fields($o);
  $o['subtotal'] = (float) $o['subtotal'];
  $o['shipping_fee'] = (float) ($o['shipping_fee'] ?? 0);
  $o['total'] = (float) $o['total'];
  $iStmt = db()->prepare('SELECT * FROM order_items WHERE order_id = ? ORDER BY id ASC');
  $iStmt->execute([$o['id']]);
  $items = $iStmt->fetchAll(PDO::FETCH_ASSOC);
  $first = $items[0] ?? null;

  $dt = null;
  try { $dt = new DateTime((string)($o['created_at'] ?? '')); } catch (Exception $e) { $dt = null; }
  $dateLabel = $dt ? $dt->format('M j, Y') : '';

  $pendingReviewCount = 0;
  $isReceived = array_key_exists((string) $o['id'], $receiptMap);
  $receivedAt = $isReceived ? (string) ($receiptMap[(string) $o['id']] ?? '') : '';
  foreach ($items as $item) {
    $productId = (int) ($item['product_id'] ?? 0);
    $reviewKey = (string) $o['id'] . ':' . (string) $productId;
    $itemReviewed = $productId > 0 && array_key_exists($reviewKey, $reviewMap);
    $item['reviewed'] = $itemReviewed;
    $item['review_rating'] = $itemReviewed ? (int) $reviewMap[$reviewKey] : null;
    if (($o['status'] ?? '') === 'delivered' && $isReceived && $productId > 0 && !$itemReviewed) {
      $pendingReviewCount++;
      $pendingReviewItems[] = [
        'order_id' => (string) $o['id'],
        'order_date' => $dateLabel,
        'product_id' => $productId,
        'product_name' => (string) ($item['name'] ?? ''),
        'product_cat' => (string) ($item['cat'] ?? ''),
        'product_size' => (string) ($item['size'] ?? ''),
        'product_image' => (string) ($item['image'] ?? ''),
        'qty' => (int) ($item['qty'] ?? 1),
      ];
    }
  }

  $ordersUi[] = [
    'id' => $o['id'],
    'status' => $o['status'] ?? 'pending',
    // List row expects these keys
    'product' => $first['name'] ?? '—',
    'cat' => $first['cat'] ?? '',
    'size' => $first['size'] ?? '',
    'qty' => isset($first['qty']) ? (int)$first['qty'] : 1,
    'date' => $dateLabel,
    'price' => (float)($o['total'] ?? 0),
    'pending_review_count' => $pendingReviewCount,
    'is_received' => $isReceived,
    'received_at' => $receivedAt,
    // Modal expects shipping block sometimes
    'shipping' => [
      'name' => $o['ship_name'] ?? '',
      'street' => $o['ship_street'] ?? '',
      'city' => $o['ship_city'] ?? '',
      'province' => $o['ship_province'] ?? '',
      'zip' => $o['ship_zip'] ?? '',
      'phone' => $o['ship_phone'] ?? '',
    ],
    // Keep full items for later enhancements
    'items' => $items,
    'courier_user_id' => isset($o['courier_user_id']) ? (int)$o['courier_user_id'] : null,
    'courier_name' => isset($o['courier_name']) ? (string)$o['courier_name'] : '',
  ];
}
$user = current_user();
$cart = get_customer_cart();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<?= csrf_meta_tag() ?>
<title>My Orders — Bejewelry</title>
<style>
/* ═══════════════════════════════════════════════════════════
   BEJEWELRY — Fonts
   Share with ALL roles: Customer · Admin · Inventory · Orders
   Load this BEFORE styles.css in every HTML page.

   <link rel="stylesheet" href="css/fonts.css">
   <link rel="stylesheet" href="css/styles.css">
═══════════════════════════════════════════════════════════ */

@import url('https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600;9..40,700&display=swap');

:root {
  --fd: 'Playfair Display', Georgia, serif;  /* Headings, logo, prices, display text */
  --fb: 'DM Sans', system-ui, sans-serif;    /* Body, UI, buttons, forms, nav */
}

body {
  font-family: var(--fb);
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}

h1, h2, h3, h4 {
  font-family: var(--fd);
}


/* ═══════════════════════════════════════════════════════════
   BEJEWELRY — Design System  v3
   Theme  : Blush · Rose · Gold · Deep Plum
   Shared by all 4 roles: Customer · Admin · Inventory · Orders

   REQUIRES: fonts.css loaded BEFORE this file
   <link rel="stylesheet" href="css/fonts.css">
   <link rel="stylesheet" href="css/styles.css">
═══════════════════════════════════════════════════════════ */

*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html{font-size:16px;scroll-behavior:smooth}
img{display:block;max-width:100%}
input,button,select,textarea{font-family:inherit}
a{text-decoration:none;color:inherit}
ul,ol{list-style:none}

:root{
  /* Palette */
  --blush:#FEF1F3; --blush-mid:#FAE3E8; --blush-deep:#F4C8D2;
  --rose:#D96070; --rose-deep:#B03050; --rose-muted:#CC8898;
  --white:#FFFFFF; --dark:#241418; --dark-soft:#3A2028;
  --muted:#7A5E68; --muted-light:#AC8898;
  --border:#ECDCE0; --border-mid:#DEC8D0;
  --gold:#B88830; --success:#228855; --danger:#BB3333;
  /* Fonts */
  /* Space */
  --s1:4px;--s2:8px;--s3:12px;--s4:16px;--s5:20px;
  --s6:24px;--s8:32px;--s10:40px;--s12:48px;--s16:64px;
  /* Layout */
  --sidebar-w:210px; --max-w:1180px; --hh:64px;
  /* Radius */
  --r-sm:8px;--r-md:12px;--r-lg:18px;--r-xl:26px;--r-pill:999px;
  /* Shadows */
  --sh-xs:0 1px 3px rgba(160,40,60,.06);
  --sh-sm:0 2px 8px rgba(160,40,60,.09);
  --sh-md:0 4px 18px rgba(160,40,60,.13);
  --sh-lg:0 8px 36px rgba(160,40,60,.17);
  --sh-xl:0 16px 60px rgba(160,40,60,.22);
  --tr:.2s ease; --tr-s:.35s ease;
}

body{background:var(--blush);color:var(--dark);line-height:1.6;min-height:100vh}
h1,h2,h3,h4{color:var(--dark);line-height:1.2}
h1{font-size:clamp(1.8rem,3vw,2.6rem);font-weight:600}
h2{font-size:clamp(1.4rem,2.4vw,2rem);font-weight:500}
h3{font-size:1.18rem;font-weight:500}
h4{font-size:.97rem;font-weight:600}
p{font-size:.9rem;color:var(--muted);line-height:1.8}

/* ── LAYOUT ─── */
.site-wrapper{display:flex;min-height:100vh}
.site-content{flex:1;min-width:0;display:flex;flex-direction:column}
.container{max-width:var(--max-w);margin:0 auto;padding:0 var(--s8);width:100%}
.main-content{padding:var(--s8) 0 var(--s16);flex:1}

/* ── SIDEBAR ─── */
.sidebar{width:var(--sidebar-w);min-width:var(--sidebar-w);flex-shrink:0;background:var(--white);border-right:1px solid var(--border);display:flex;flex-direction:column;position:sticky;top:0;height:100vh;overflow-y:auto;z-index:90;scrollbar-width:thin;scrollbar-color:var(--blush-mid) transparent}
.sb-brand{padding:var(--s5) var(--s4) var(--s4);border-bottom:1px solid var(--border)}
.sb-logo{font-family:var(--fd);font-size:1.18rem;font-weight:700;color:var(--dark)}
.sb-sub{font-size:.59rem;font-weight:600;letter-spacing:.18em;text-transform:uppercase;color:var(--rose);margin-top:2px}
.sb-user{display:flex;align-items:center;gap:var(--s3);padding:var(--s3) var(--s4);border-bottom:1px solid var(--border)}
.sb-av{width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,var(--blush-deep),var(--rose-muted));display:flex;align-items:center;justify-content:center;font-size:.78rem;font-weight:700;color:var(--white);flex-shrink:0;border:2px solid var(--white);box-shadow:var(--sh-xs)}
.sb-uname{font-size:.81rem;font-weight:600;color:var(--dark);line-height:1.3}
.sb-urole{font-size:.59rem;color:var(--muted-light);text-transform:uppercase;letter-spacing:.08em}
.sb-group{font-size:.57rem;font-weight:700;text-transform:uppercase;letter-spacing:.2em;color:var(--muted-light);padding:var(--s4) var(--s4) var(--s2)}
.sb-item{display:flex;align-items:center;gap:var(--s3);padding:9px var(--s4);font-size:.81rem;color:var(--muted);border-left:2.5px solid transparent;cursor:pointer;transition:all var(--tr)}
.sb-item:hover{background:var(--blush);color:var(--dark);border-left-color:var(--rose-muted)}
.sb-item.active{background:linear-gradient(90deg,var(--blush-mid),var(--blush));color:var(--rose-deep);border-left-color:var(--rose);font-weight:600}
.sb-icon{font-size:.86rem;width:16px;text-align:center;flex-shrink:0;opacity:.7}
.sb-badge{margin-left:auto;background:var(--rose);color:var(--white);font-size:.56rem;font-weight:700;min-width:17px;height:17px;border-radius:var(--r-pill);display:flex;align-items:center;justify-content:center;padding:0 4px}
.sb-badge.gold{background:var(--gold)}
.sb-div{border:none;border-top:1px solid var(--border);margin:var(--s2) 0}
.sb-foot{margin-top:auto;padding:var(--s4);border-top:1px solid var(--border);font-size:.76rem;color:var(--muted-light);cursor:pointer;display:flex;align-items:center;gap:var(--s2);transition:color var(--tr)}
.sb-foot:hover{color:var(--rose)}

/* ══════════════════════════════════════════════════════════════
   HEADER — full redesign
══════════════════════════════════════════════════════════════ */
.site-header{
  background:var(--white);
  border-bottom:1px solid var(--border);
  position:sticky;top:0;z-index:300;
  box-shadow:var(--sh-xs);
  transition:transform .3s ease, box-shadow .3s ease, background .3s ease;
}
.site-header.scrolled{box-shadow:var(--sh-md)}
.site-header.hdr-hidden{transform:translateY(-100%)}

/* Announce bar */
.hdr-announce{
  background:linear-gradient(90deg,var(--rose-deep) 0%,var(--rose) 50%,var(--rose-deep) 100%);
  background-size:200% 100%;
  animation:announce-slide 6s linear infinite;
  padding:8px var(--s4);
  text-align:center;
  font-size:.63rem;font-weight:600;letter-spacing:.13em;text-transform:uppercase;
  color:rgba(255,255,255,.95);
  display:flex;align-items:center;justify-content:center;gap:12px;flex-wrap:wrap;
}
@keyframes announce-slide{0%{background-position:0 0}100%{background-position:200% 0}}
.hdr-announce-sep{opacity:.4}
.hdr-announce-cta{
  color:rgba(255,255,255,.85);text-decoration:underline;
  text-underline-offset:2px;font-size:.6rem;letter-spacing:.1em;
  transition:color var(--tr)
}
.hdr-announce-cta:hover{color:var(--white)}

/* Main bar */
.hdr-main{height:var(--hh);display:flex;align-items:center;padding:0 var(--s8)}
.hdr-inner{display:flex;align-items:center;justify-content:space-between;gap:var(--s6);width:100%;max-width:var(--max-w);margin:0 auto}

/* Logo */
.hdr-logo{display:flex;flex-direction:column;flex-shrink:0;line-height:1;text-decoration:none}
.hdr-logo-text{font-family:var(--fd);font-size:1.36rem;font-weight:700;color:var(--dark);letter-spacing:-.01em;transition:color var(--tr)}
.hdr-logo-sub{font-size:.48rem;font-weight:700;letter-spacing:.22em;text-transform:uppercase;color:var(--rose);margin-top:2px}
.hdr-logo:hover .hdr-logo-text{color:var(--rose-deep)}

/* Nav */
.hdr-nav{display:flex;align-items:center;gap:8px}
.hdr-nav-link{
  font-size:.72rem;font-weight:500;letter-spacing:.1em;text-transform:uppercase;
  color:var(--muted);padding:10px 18px;border-radius:var(--r-pill);
  position:relative;transition:all var(--tr);
}
.hdr-nav-link::after{
  content:'';position:absolute;bottom:4px;left:50%;transform:translateX(-50%);
  width:0;height:1.5px;background:var(--rose);
  transition:width .25s ease;border-radius:2px;
}
.hdr-nav-link:hover{color:var(--rose-deep);background:var(--blush)}
.hdr-nav-link.active{color:var(--rose-deep);font-weight:600}
.hdr-nav-link:hover::after,.hdr-nav-link.active::after{width:calc(100% - 26px)}

/* Actions */
.hdr-actions{display:flex;align-items:center;gap:8px;flex-shrink:0}

/* Icon buttons — horizontal, clean */
.hdr-btn{
  display:flex;flex-direction:row;align-items:center;justify-content:center;
  gap:8px;padding:10px 15px;border-radius:var(--r-pill);
  font-size:.72rem;font-weight:500;letter-spacing:.07em;text-transform:uppercase;
  color:var(--muted);border:1px solid transparent;background:transparent;cursor:pointer;
  transition:all var(--tr);position:relative;white-space:nowrap;text-decoration:none;
}
.hdr-btn:hover{background:var(--blush);color:var(--rose-deep);border-color:var(--border)}
.hdr-btn-icon{position:relative;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.hdr-btn-label{font-size:.72rem;font-weight:500;color:inherit}

/* Badge on icon buttons */
.hdr-badge{
  position:absolute;top:-5px;right:-6px;
  background:var(--rose);color:var(--white);
  font-size:.5rem;font-weight:700;
  min-width:14px;height:14px;padding:0 3px;
  border-radius:var(--r-pill);
  display:flex;align-items:center;justify-content:center;
  border:1.5px solid var(--white);
  animation:badge-pop .2s ease;
  line-height:1;
}
@keyframes badge-pop{from{transform:scale(0)}to{transform:scale(1)}}

/* Cart button — highlighted */
.hdr-cart-btn{background:var(--blush);border:1px solid var(--border)!important;color:var(--rose-deep)!important}
.hdr-cart-btn:hover{background:var(--blush-mid);border-color:var(--rose-muted)!important}

/* Minimal header extras */
.hdr-secure{display:flex;align-items:center;gap:6px;font-size:.75rem;color:var(--muted)}
.hdr-back{font-size:.75rem;color:var(--muted);padding:6px 12px;border:1px solid var(--border);border-radius:var(--r-pill);transition:all var(--tr)}
.hdr-back:hover{background:var(--blush);color:var(--rose-deep)}

/* ── SEARCH PANEL ─── */
.hdr-search{position:relative}
.hdr-search-btn{
  display:flex;align-items:center;justify-content:center;
  width:42px;height:42px;border-radius:var(--r-md);
  border:1.5px solid var(--border);background:linear-gradient(135deg,var(--blush),var(--blush-mid));
  color:var(--rose-muted);cursor:pointer;transition:all var(--tr);
}
.hdr-search-btn:hover,.hdr-search.search-open .hdr-search-btn{
  background:linear-gradient(135deg,var(--blush-deep),var(--blush-mid));border-color:var(--rose);color:var(--rose-deep);box-shadow:var(--sh-sm);transform:scale(1.05)
}
.hdr-search-panel{
  position:fixed;
  top:calc(var(--hh) + 38px);
  left:55%;transform:translateX(-50%);
  width:320px;background:var(--white);
  border:1.5px solid var(--border-mid);
  border-radius:var(--r-lg);
  box-shadow:0 8px 32px rgba(160,40,60,.12);
  overflow:hidden;z-index:400;
  opacity:0;pointer-events:none;
  transform:translateX(-50%) translateY(-8px);
  transition:opacity .22s ease,transform .22s ease;
}
.hdr-search.search-open .hdr-search-panel{
  opacity:1;pointer-events:all;
  transform:translateX(-50%) translateY(0);
}
.hdr-search-inner{
  display:flex;align-items:center;gap:10px;
  padding:13px 16px;border-bottom:1px solid var(--border);
}
.hdr-search-input{
  flex:1;border:none;outline:none;background:transparent;
  font-size:.9rem;color:var(--dark);font-family:var(--fb);font-weight:500;
}
.hdr-search-input::placeholder{color:var(--muted-light);font-weight:500}
.hdr-search-clear{
  border:none;background:transparent;cursor:pointer;
  color:var(--muted-light);font-size:1.1rem;padding:4px;opacity:.7;
  transition:all var(--tr);font-weight:600;
}
.hdr-search-clear:hover{color:var(--rose);opacity:1}
.hdr-search-drop{max-height:360px;overflow-y:auto}
.sd-item{
  display:flex;align-items:center;gap:12px;
  padding:13px 16px;transition:all var(--tr);cursor:pointer;border-bottom:1px solid var(--blush);
}
.sd-item:last-of-type{border-bottom:none}
.sd-item:hover{background:var(--blush-mid);padding-left:18px}
.sd-emoji{font-size:1.6rem;width:40px;height:40px;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,var(--blush-mid),var(--blush-deep));border-radius:var(--r-md);flex-shrink:0;box-shadow:var(--sh-sm)}
.sd-info{flex:1;min-width:0;display:flex;flex-direction:column;gap:1px}
.sd-name{font-size:.87rem;font-weight:600;color:var(--dark);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.sd-cat{font-size:.7rem;color:var(--muted);font-weight:500;text-transform:uppercase;letter-spacing:.05em}
.sd-price{font-family:var(--fd);font-size:.92rem;font-weight:700;color:var(--rose-deep);flex-shrink:0}
.sd-empty{padding:28px 16px;font-size:.85rem;color:var(--muted);text-align:center;background:var(--blush);font-weight:500}
.sd-all{
  display:block;padding:12px 16px;font-size:.78rem;font-weight:700;
  color:var(--white);background:linear-gradient(135deg,var(--rose),var(--rose-deep));
  text-align:center;transition:all var(--tr);letter-spacing:.06em;text-transform:uppercase;
  box-shadow:inset 0 1px 0 rgba(255,255,255,.1);
}
.sd-all:hover{transform:translateY(-1px);box-shadow:inset 0 1px 0 rgba(255,255,255,.1),var(--sh-md)}
.hdr-search-drop.show .sd-item,.hdr-search-drop.show .sd-empty,.hdr-search-drop.show .sd-all{display:flex}
.sd-item,.sd-empty,.sd-all{display:none}
.hdr-search-drop.show .sd-all{display:block}

/* Legacy .hbtn/.search-box kept for backward compat with inline headers */
.hbtn{display:flex;align-items:center;gap:5px;padding:7px 13px;border-radius:var(--r-pill);font-size:.73rem;font-weight:500;color:var(--muted);border:1px solid var(--border);background:var(--white);cursor:pointer;transition:all var(--tr);white-space:nowrap;text-decoration:none}
.hbtn:hover{background:var(--blush);color:var(--rose-deep);border-color:var(--rose-muted)}
.search-box{display:flex;align-items:center;background:var(--blush);border:1px solid var(--border);border-radius:var(--r-pill);padding:7px 13px;gap:6px;transition:border-color var(--tr),width .28s;position:relative}
.search-box input{border:none;outline:none;background:transparent;font-size:.79rem;color:var(--dark)}
.search-box input::placeholder{color:var(--muted-light)}
.cart-count{background:var(--rose);color:var(--white);font-size:.57rem;font-weight:700;width:16px;height:16px;border-radius:50%;display:flex;align-items:center;justify-content:center;min-width:16px}
.js-cart-count,.js-wish-count{background:var(--rose);color:var(--white);font-size:.57rem;font-weight:700;width:16px;height:16px;border-radius:50%;display:flex;align-items:center;justify-content:center;min-width:16px}

/* ── BUTTONS ─── */
.btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;padding:10px 22px;border-radius:var(--r-pill);font-family:var(--fb);font-size:.75rem;font-weight:600;letter-spacing:.07em;text-transform:uppercase;border:none;cursor:pointer;transition:all var(--tr);white-space:nowrap}
.btn-primary{background:linear-gradient(135deg,var(--rose),var(--rose-deep));color:var(--white);box-shadow:0 4px 14px rgba(176,48,80,.3)}
.btn-primary:hover{transform:translateY(-1px);box-shadow:0 6px 22px rgba(176,48,80,.4)}
.btn-secondary{background:var(--white);color:var(--rose-deep);border:1.5px solid var(--rose)}
.btn-secondary:hover{background:var(--blush-mid)}
.btn-ghost{background:transparent;color:var(--muted);border:1.5px solid var(--border-mid)}
.btn-ghost:hover{background:var(--blush);color:var(--dark);border-color:var(--rose-muted)}
.btn-dark{background:var(--dark);color:var(--white)}
.btn-dark:hover{background:var(--dark-soft);transform:translateY(-1px)}
.btn-sm{padding:7px 15px;font-size:.7rem}
.btn-lg{padding:13px 30px;font-size:.81rem}
.btn-full{width:100%}
.btn:disabled{opacity:.5;cursor:not-allowed;transform:none!important}

/* ── CARD ─── */
.card{background:var(--white);border-radius:var(--r-lg);border:1px solid var(--border);box-shadow:var(--sh-xs)}
.card-body{padding:var(--s6)}
.card-hd{padding:var(--s5) var(--s6);border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between}
.card-hd h3{font-size:1.03rem}
.card-ft{padding:var(--s5) var(--s6);border-top:1px solid var(--border)}

/* ── PRODUCT CARD ─── */
.pcard{background:var(--white);border-radius:var(--r-lg);border:1px solid var(--border);overflow:hidden;cursor:pointer;transition:transform .28s ease,box-shadow .28s ease,border-color .28s ease;position:relative;display:block}
.pcard:hover{transform:translateY(-5px);box-shadow:var(--sh-lg);border-color:var(--rose-muted)}
.pcard:hover .pcard-img{background:var(--blush-deep)}
.pcard-img{aspect-ratio:1;background:var(--blush-mid);display:flex;align-items:center;justify-content:center;font-size:3.6rem;position:relative;transition:background .28s}
.pcard-img.alt{background:var(--blush-deep)}
.pbadge{position:absolute;top:10px;left:10px;padding:3px 10px;border-radius:var(--r-pill);font-size:.57rem;font-weight:700;letter-spacing:.07em;text-transform:uppercase;z-index:1}
.pb-new{background:var(--rose);color:var(--white)}
.pb-sale{background:var(--gold);color:var(--white)}
.pb-best{background:var(--dark);color:var(--white)}
.wish-btn{position:absolute;top:10px;right:10px;width:30px;height:30px;background:var(--white);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.86rem;cursor:pointer;box-shadow:var(--sh-sm);border:none;z-index:1;color:var(--muted-light);transition:all var(--tr)}
.wish-btn:hover,.wish-btn.liked{color:var(--rose-deep);transform:scale(1.1)}
.pcard-info{padding:var(--s3) var(--s4) var(--s4)}
.pcard-cat{font-size:.57rem;font-weight:700;letter-spacing:.16em;text-transform:uppercase;color:var(--rose);margin-bottom:3px}
.pcard-name{font-family:var(--fd);font-size:.96rem;font-weight:500;color:var(--dark);margin-bottom:7px;line-height:1.3}
.pcard-foot{display:flex;align-items:center;justify-content:space-between}
.pcard-price{font-family:var(--fd);font-size:1.03rem;font-weight:600;color:var(--dark)}
.pcard-orig{font-size:.77rem;color:var(--muted-light);text-decoration:line-through;margin-left:5px}
.pcard-stars{font-size:.69rem;color:var(--gold);letter-spacing:1px}
.pcard-add{width:100%;margin-top:var(--s3);padding:9px;border-radius:var(--r-md);background:var(--blush-mid);color:var(--rose-deep);font-size:.7rem;font-weight:700;letter-spacing:.09em;text-transform:uppercase;border:1.5px solid var(--blush-deep);cursor:pointer;transition:all var(--tr)}
.pcard-add:hover{background:var(--rose);color:var(--white);border-color:var(--rose)}

/* ── GRIDS ─── */
.g4{display:grid;grid-template-columns:repeat(4,1fr);gap:var(--s5)}
.g3{display:grid;grid-template-columns:repeat(3,1fr);gap:var(--s5)}
.g2{display:grid;grid-template-columns:repeat(2,1fr);gap:var(--s6)}
.sidebar-layout{display:flex;align-items:flex-start;gap:var(--s6)}
.checkout-layout{display:grid;grid-template-columns:1fr 370px;gap:var(--s6);align-items:flex-start}
.filter-col{width:220px;flex-shrink:0}
.content-col{flex:1;min-width:0}

/* ── FORMS ─── */
.fg{margin-bottom:var(--s5)}
.flabel{display:block;font-size:.61rem;font-weight:700;letter-spacing:.15em;text-transform:uppercase;color:var(--muted);margin-bottom:7px}
.finput,.fselect,.ftextarea{width:100%;padding:11px 14px;font-size:.87rem;color:var(--dark);background:var(--white);border:1.5px solid var(--border-mid);border-radius:var(--r-md);outline:none;font-family:var(--fb);transition:border-color var(--tr),box-shadow var(--tr);appearance:none}
.finput:focus,.fselect:focus,.ftextarea:focus{border-color:var(--rose-muted);box-shadow:0 0 0 3px rgba(217,96,112,.1)}
.finput::placeholder{color:var(--muted-light)}
.ftextarea{resize:vertical;min-height:100px}
.frow{display:grid;grid-template-columns:1fr 1fr;gap:var(--s4)}
.frow3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:var(--s4)}
.fhint{font-size:.69rem;color:var(--muted-light);margin-top:4px}

/* ── BADGES ─── */
.badge{display:inline-flex;align-items:center;padding:3px 10px;border-radius:var(--r-pill);font-size:.59rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase}
.b-pending{background:#FFF7D6;color:#8C6800;border:1px solid #EDD050}
.b-processing{background:#E6F3FF;color:#1455A0;border:1px solid #88C0F0}
.b-shipped{background:#ECF0FF;color:#2035A0;border:1px solid #9AA8F0}
.b-delivered{background:#E4FFEE;color:#156038;border:1px solid #68CC88}
.b-cancelled{background:#FFEEEE;color:#982828;border:1px solid #EEAAAA}

/* ── TABS ─── */
.tabs{display:flex;align-items:center;border-bottom:1.5px solid var(--border);gap:2px;margin-bottom:var(--s6)}
.tab{padding:10px 17px;font-size:.72rem;font-weight:600;letter-spacing:.06em;text-transform:uppercase;color:var(--muted);border-bottom:2.5px solid transparent;cursor:pointer;transition:all var(--tr);white-space:nowrap;margin-bottom:-1.5px;user-select:none}
.tab:hover{color:var(--dark)}
.tab.active{color:var(--rose-deep);border-bottom-color:var(--rose)}
.tab-n{background:var(--blush-mid);color:var(--rose);font-size:.56rem;font-weight:700;padding:2px 6px;border-radius:var(--r-pill);margin-left:4px}
.tab.active .tab-n{background:var(--rose);color:var(--white)}

/* ── TABLE ─── */
.tbl{width:100%;border-collapse:collapse}
.tbl th,.tbl td{padding:13px var(--s4);text-align:left;border-bottom:1px solid var(--border);font-size:.82rem}
.tbl th{font-size:.59rem;font-weight:700;letter-spacing:.15em;text-transform:uppercase;color:var(--muted-light);background:var(--blush);white-space:nowrap}
.tbl tbody tr{transition:background var(--tr)}
.tbl tbody tr:hover td{background:var(--blush)}
.tbl tr:last-child td{border-bottom:none}

/* ── QTY ─── */
.qty{display:flex;align-items:center;border:1.5px solid var(--border-mid);border-radius:var(--r-pill);overflow:hidden;width:fit-content}
.qty-btn{width:33px;height:33px;background:var(--blush);border:none;cursor:pointer;font-size:1rem;color:var(--rose-deep);display:flex;align-items:center;justify-content:center;transition:background var(--tr);flex-shrink:0}
.qty-btn:hover{background:var(--blush-deep)}
.qty-val{width:38px;text-align:center;border:none;font-size:.87rem;font-weight:600;color:var(--dark);background:var(--white);outline:none}

/* ── ORDER SUMMARY ─── */
.sum-box{background:var(--white);border:1px solid var(--border);border-radius:var(--r-lg);padding:var(--s6);box-shadow:var(--sh-xs)}
.sum-title{font-family:var(--fd);font-size:.99rem;font-weight:600;color:var(--dark);margin-bottom:var(--s5);padding-bottom:var(--s4);border-bottom:1px solid var(--border)}
.sum-row{display:flex;justify-content:space-between;align-items:center;padding:8px 0;font-size:.82rem;border-bottom:1px solid var(--border)}
.sum-row:last-of-type{border-bottom:none}
.sl{color:var(--muted)}.sv{font-weight:600;color:var(--dark)}
.sum-total{display:flex;justify-content:space-between;align-items:center;padding:var(--s4) 0 0;border-top:1.5px solid var(--border-mid);margin-top:var(--s2)}
.stl{font-size:.85rem;font-weight:700;color:var(--dark)}
.stv{font-family:var(--fd);font-size:1.42rem;font-weight:700;color:var(--rose-deep)}

/* ── FILTER PANEL ─── */
.fp{background:var(--white);border:1px solid var(--border);border-radius:var(--r-md);overflow:hidden;margin-bottom:var(--s3)}
.fp-hd{padding:10px 13px;background:var(--blush);border-bottom:1px solid var(--border);font-size:.61rem;font-weight:700;letter-spacing:.16em;text-transform:uppercase;color:var(--dark)}
.fp-body{padding:var(--s3) var(--s4)}
.fp-opt{display:flex;align-items:center;gap:8px;padding:5px 0;font-size:.82rem;color:var(--dark);cursor:pointer}
.fp-opt input[type=checkbox]{accent-color:var(--rose);cursor:pointer}
.fp-cnt{margin-left:auto;font-size:.67rem;color:var(--muted-light)}

/* ── CHECKOUT STEPS ─── */
.steps{display:flex;align-items:center;justify-content:center;padding:var(--s6) 0 var(--s8)}
.step{display:flex;flex-direction:column;align-items:center;gap:6px;flex:1;max-width:150px;position:relative}
.step::after{content:'';position:absolute;top:15px;left:60%;width:80%;height:1.5px;background:var(--border-mid)}
.step:last-child::after{display:none}
.step-n{width:31px;height:31px;border-radius:50%;background:var(--blush-mid);border:2px solid var(--border-mid);display:flex;align-items:center;justify-content:center;font-size:.77rem;font-weight:700;color:var(--muted);position:relative;z-index:1}
.step.active .step-n{background:var(--rose);color:var(--white);border-color:var(--rose);box-shadow:0 0 0 4px rgba(217,96,112,.18)}
.step.done .step-n{background:var(--rose-muted);color:var(--white);border-color:var(--rose-muted)}
.step-l{font-size:.59rem;font-weight:700;letter-spacing:.11em;text-transform:uppercase;color:var(--muted)}
.step.active .step-l{color:var(--rose-deep)}.step.done .step-l{color:var(--rose-muted)}

/* ── BREADCRUMB ─── */
.bc{display:flex;align-items:center;gap:7px;font-size:.71rem;color:var(--muted);margin-bottom:var(--s4)}
.bc a{color:var(--muted);transition:color var(--tr)}.bc a:hover{color:var(--rose)}
.bc .sep{color:var(--border-mid)}.bc .cur{color:var(--dark);font-weight:600}

/* ── PAGE HEADER ─── */
.page-hdr{padding:var(--s8) 0 var(--s5)}.page-hdr h1{margin-bottom:5px}
.eyebrow{display:block;font-size:.59rem;font-weight:700;letter-spacing:.24em;text-transform:uppercase;color:var(--rose);margin-bottom:var(--s2)}

/* ── ACCOUNT ─── */
.account-wrap{display:grid;grid-template-columns:220px 1fr;gap:var(--s6);align-items:flex-start}
.acnav{background:var(--white);border:1px solid var(--border);border-radius:var(--r-lg);overflow:hidden;box-shadow:var(--sh-xs)}
.acnav-head{padding:var(--s5);border-bottom:1px solid var(--border);background:var(--blush)}
.acnav-av{width:50px;height:50px;border-radius:50%;background:linear-gradient(135deg,var(--blush-deep),var(--rose-muted));display:flex;align-items:center;justify-content:center;font-size:1.25rem;font-weight:700;color:var(--white);margin-bottom:var(--s3);border:3px solid var(--white);box-shadow:var(--sh-sm)}
.acnav-name{font-family:var(--fd);font-size:.93rem;font-weight:600;color:var(--dark)}
.acnav-email{font-size:.69rem;color:var(--muted);margin-top:2px}
.acnav-item{display:flex;align-items:center;gap:9px;padding:12px var(--s5);font-size:.81rem;color:var(--muted);border-bottom:1px solid var(--border);cursor:pointer;transition:all var(--tr);border-left:2.5px solid transparent}
.acnav-item:last-child{border-bottom:none}
.acnav-item:hover{background:var(--blush);color:var(--dark)}
.acnav-item.active{background:linear-gradient(90deg,var(--blush-mid),var(--blush));color:var(--rose-deep);border-left-color:var(--rose);font-weight:600}
.acpanel{background:var(--white);border:1px solid var(--border);border-radius:var(--r-lg);padding:var(--s6);box-shadow:var(--sh-xs)}
.panel-hd{display:flex;align-items:center;justify-content:space-between;margin-bottom:var(--s6);padding-bottom:var(--s5);border-bottom:1px solid var(--border)}
.panel-hd h2{font-size:1.1rem}

/* ── PAGINATION ─── */
.pag{display:flex;align-items:center;justify-content:center;gap:5px;padding:var(--s8) 0 var(--s4)}
.pg{width:34px;height:34px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.79rem;font-weight:600;border:1.5px solid var(--border);background:var(--white);color:var(--muted);cursor:pointer;transition:all var(--tr)}
.pg:hover{background:var(--blush);color:var(--dark);border-color:var(--rose-muted)}
.pg.active{background:var(--rose);color:var(--white);border-color:var(--rose)}

/* ── TOOLBAR ─── */
.toolbar{display:flex;align-items:center;justify-content:space-between;padding:11px 16px;background:var(--white);border:1px solid var(--border);border-radius:var(--r-md);margin-bottom:var(--s5)}
.toolbar-info{font-size:.77rem;color:var(--muted)}
.toolbar-r{display:flex;align-items:center;gap:var(--s3)}
.sort-sel{padding:6px 11px;font-size:.77rem;border:1px solid var(--border);background:var(--blush);color:var(--dark);border-radius:8px;cursor:pointer;outline:none}

/* ── TOAST ─── */
#toasts{position:fixed;bottom:28px;left:50%;transform:translateX(-50%);z-index:9999;pointer-events:none;display:flex;flex-direction:column;align-items:center;gap:8px}
.toast{background:var(--dark);color:var(--white);padding:11px 22px;border-radius:var(--r-pill);font-size:.81rem;box-shadow:var(--sh-lg);pointer-events:none;animation:tin .3s ease,tout .3s ease 2.5s forwards;white-space:nowrap;display:flex;align-items:center;gap:8px}
.toast.ok{background:var(--success)}.toast.warn{background:var(--gold);color:var(--dark)}
@keyframes tin{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:translateY(0)}}
@keyframes tout{from{opacity:1;transform:translateY(0)}to{opacity:0;transform:translateY(12px)}}

/* ── CART DRAWER ─── */
.drawer-ov{position:fixed;inset:0;background:rgba(36,20,24,.48);z-index:500;opacity:0;transition:opacity var(--tr-s);pointer-events:none;backdrop-filter:blur(4px)}
.drawer-ov.open{opacity:1;pointer-events:all}
.cart-drawer{position:fixed;top:0;right:0;width:400px;height:100vh;background:var(--white);z-index:600;transform:translateX(105%);transition:transform var(--tr-s) cubic-bezier(.4,0,.2,1);display:flex;flex-direction:column;box-shadow:-20px 0 60px rgba(0,0,0,.15)}
.cart-drawer.open{transform:translateX(0)}
.drw-hd{padding:var(--s5) var(--s6);border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between}
.drw-close{width:30px;height:30px;border-radius:50%;background:var(--blush);border:none;cursor:pointer;display:flex;align-items:center;justify-content:center;color:var(--muted);transition:all var(--tr)}
.drw-close:hover{background:var(--blush-deep);color:var(--dark)}
.drw-body{flex:1;overflow-y:auto;padding:var(--s4) var(--s6)}
.drw-empty{text-align:center;padding:60px var(--s6);color:var(--muted-light)}
.drw-empty-icon{font-size:3rem;display:block;margin-bottom:var(--s4)}
.drw-foot{padding:var(--s5) var(--s6);border-top:1px solid var(--border)}
.citem{display:grid;grid-template-columns:55px 1fr auto;gap:12px;align-items:start;padding:12px 0;border-bottom:1px solid var(--border)}
.citem:last-child{border-bottom:none}
.ci-img{width:55px;height:55px;border-radius:8px;background:var(--blush-mid);display:flex;align-items:center;justify-content:center;font-size:1.6rem;flex-shrink:0}
.ci-name{font-family:var(--fd);font-size:.85rem;font-weight:600;color:var(--dark);margin-bottom:2px}
.ci-meta{font-size:.69rem;color:var(--muted-light);margin-bottom:7px}
.ci-price{font-family:var(--fd);font-size:.91rem;font-weight:600;color:var(--dark)}
.ci-del{font-size:.75rem;color:var(--muted);cursor:pointer;margin-top:4px;padding:6px 12px;border:1px solid var(--border-mid);border-radius:6px;background:transparent;transition:all var(--tr);font-weight:500}
.ci-del:hover{background:var(--blush);color:var(--dark);border-color:var(--rose-muted);transform:translateY(-1px)}

/* ── CATEGORY CARD ─── */
.catcard{border-radius:var(--r-lg);overflow:hidden;border:1px solid var(--border);cursor:pointer;transition:all .28s;display:block;background:var(--white);box-shadow:var(--sh-xs)}
.catcard:hover{transform:translateY(-4px);box-shadow:var(--sh-md);border-color:var(--rose-muted)}
.catcard-img{height:155px;display:flex;align-items:center;justify-content:center;font-size:3rem;background:var(--blush-mid);transition:background .28s}
.catcard-img.alt{background:var(--blush-deep)}
.catcard:hover .catcard-img{background:var(--blush-deep)}
.catcard-lbl{padding:13px 14px;border-top:1px solid var(--border)}
.catcard-lbl h4{font-family:var(--fd);font-size:.87rem;margin-bottom:2px}
.catcard-lbl small{font-size:.69rem;color:var(--muted-light)}

/* ── IMG PH ─── */
.iph{background:var(--blush-mid);display:flex;align-items:center;justify-content:center;position:relative;overflow:hidden;color:var(--rose-muted)}
.iph::before{content:'';position:absolute;inset:0;background:repeating-linear-gradient(45deg,transparent,transparent 20px,rgba(200,120,138,.04) 20px,rgba(200,120,138,.04) 21px)}
.iph.hero{width:100%;height:480px;font-size:6rem}
.iph.banner{width:100%;height:260px;border-radius:var(--r-xl);font-size:5rem}
.iph.sm{width:58px;height:58px;border-radius:var(--r-md);font-size:1.4rem;flex-shrink:0}
.iph.md{width:78px;height:78px;border-radius:var(--r-md);font-size:1.7rem;flex-shrink:0}
.iph.main{width:100%;height:440px;border-radius:var(--r-xl);font-size:8rem}

/* ── REVIEW ─── */
.review{padding:var(--s5) 0;border-bottom:1px solid var(--border)}
.review:last-child{border-bottom:none}
.review-hd{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:7px}
.reviewer{font-size:.86rem;font-weight:600;color:var(--dark)}
.review-date{font-size:.69rem;color:var(--muted-light)}
.review-body{font-size:.83rem;color:var(--muted);line-height:1.75}

/* ── FOOTER ─── */
.site-footer{background:var(--dark);padding:var(--s12) 0 0;margin-top:auto}
.ft-grid{display:grid;grid-template-columns:1.6fr 1fr 1fr 1fr;gap:var(--s10);padding-bottom:var(--s10);border-bottom:1px solid rgba(255,255,255,.08)}
.ft-logo{font-family:var(--fd);font-size:1.28rem;font-weight:700;color:var(--white);display:block;margin-bottom:var(--s3)}
.ft-brand p{font-size:.81rem;color:rgba(255,255,255,.35);line-height:1.85}
.ft-col h5{font-size:.59rem;font-weight:700;letter-spacing:.22em;text-transform:uppercase;color:rgba(255,255,255,.4);margin-bottom:var(--s4)}
.ft-links{display:flex;flex-direction:column;gap:9px}
.ft-links a{font-size:.81rem;color:rgba(255,255,255,.3);transition:color var(--tr)}
.ft-links a:hover{color:var(--rose-muted)}
.ft-bottom{padding:var(--s5) 0;display:flex;justify-content:space-between;align-items:center;font-size:.69rem;color:rgba(255,255,255,.18)}

.ft-social{width:32px;height:32px;border-radius:50%;background:rgba(255,255,255,.08);display:flex;align-items:center;justify-content:center;font-size:.9rem;transition:background var(--tr)}
.ft-social:hover{background:rgba(255,255,255,.16)}

/* ── ALERT ─── */
.alert{padding:11px 15px;border-radius:var(--r-md);font-size:.81rem;display:flex;align-items:center;gap:8px;margin-bottom:var(--s4)}
.alert-success{background:#E6FBF0;color:#156038;border:1px solid #78CC98}
.alert-warn{background:#FFFBE6;color:#886600;border:1px solid #DEC840}
.alert-error{background:#FFEEEE;color:#982828;border:1px solid #EEAAAA}
.alert-info{background:#EDF4FF;color:#1455A0;border:1px solid #98C4F0}

/* ── MODAL ─── */
.modal-ov{position:fixed;inset:0;background:rgba(36,20,24,.5);z-index:1000;display:flex;align-items:center;justify-content:center;backdrop-filter:blur(5px);opacity:0;transition:opacity .25s;pointer-events:none}
.modal-ov.open{opacity:1;pointer-events:all}
.modal{background:var(--white);border-radius:var(--r-xl);padding:var(--s8);max-width:500px;width:92%;box-shadow:var(--sh-xl);transform:scale(.95) translateY(16px);transition:transform .3s ease}
.modal-ov.open .modal{transform:scale(1) translateY(0)}
.modal-hd{display:flex;align-items:center;justify-content:space-between;margin-bottom:var(--s6);padding-bottom:var(--s4);border-bottom:1px solid var(--border)}
.modal-x{width:30px;height:30px;border-radius:50%;background:var(--blush);border:none;cursor:pointer;color:var(--muted);display:flex;align-items:center;justify-content:center;transition:all var(--tr)}
.modal-x:hover{background:var(--blush-deep);color:var(--dark)}
.rating-card{background:var(--blush);border:1px solid var(--border);border-radius:var(--r-lg);padding:16px;margin-bottom:14px}
.rating-stars{display:flex;gap:6px;flex-wrap:wrap;margin-top:10px}
.rating-star{width:40px;height:40px;border:1px solid var(--border-mid);border-radius:12px;background:var(--white);color:#c79aa6;cursor:pointer;font-size:1rem;transition:all var(--tr);display:flex;align-items:center;justify-content:center}
.rating-star:hover,.rating-star.active{background:linear-gradient(135deg,var(--rose),var(--rose-deep));border-color:var(--rose);color:var(--white);transform:translateY(-1px)}
.rating-actions{display:flex;justify-content:flex-end;gap:10px;margin-top:16px}
.rating-progress{font-size:.76rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--rose-deep)}

/* ── SECTION ─── */
.section{padding:var(--s16) 0}.section-sm{padding:var(--s10) 0}.section-w{background:var(--white)}

/* ── UTILS ─── */
.flex{display:flex}.flex-col{display:flex;flex-direction:column}
.items-center{align-items:center}.items-start{align-items:flex-start}
.jb{justify-content:space-between}.jc{justify-content:center}
.g-2{gap:8px}.g-3{gap:12px}.g-4{gap:16px}.g-5{gap:20px}.g-6{gap:24px}
.mt2{margin-top:8px}.mt3{margin-top:12px}.mt4{margin-top:16px}.mt5{margin-top:20px}.mt6{margin-top:24px}.mt8{margin-top:32px}
.mb2{margin-bottom:8px}.mb3{margin-bottom:12px}.mb4{margin-bottom:16px}.mb5{margin-bottom:20px}.mb6{margin-bottom:24px}.mb8{margin-bottom:32px}
.tc{text-align:center}.tr{text-align:right}
.muted{color:var(--muted)}.text-rose{color:var(--rose-deep)}.text-gold{color:var(--gold)}
.t-sm{font-size:.81rem}.t-xs{font-size:.71rem}
.fd{font-family:var(--fd)}.bold{font-weight:700}
.w-full{width:100%}.rel{position:relative}.hidden{display:none!important}
.divider{border:none;border-top:1px solid var(--border);margin:var(--s5) 0}

::-webkit-scrollbar{width:5px;height:5px}
::-webkit-scrollbar-track{background:transparent}
::-webkit-scrollbar-thumb{background:var(--blush-deep);border-radius:3px}
::-webkit-scrollbar-thumb:hover{background:var(--rose-muted)}

</style>
<script>
  window.__SCROLL_RESTORE_BOOTSTRAP__ = (function () {
    var key = 'scroll_restore::' + location.pathname;
    try { history.scrollRestoration = 'manual'; } catch (e) {}
    try {
      if (sessionStorage.getItem(key) !== null) {
        document.documentElement.style.opacity = '0';
      }
    } catch (e) {}
    return key;
  })();
</script>
</head>
<body>
<script>window.__ORDERS__ = <?= json_encode($ordersUi, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>; window.__USER__ = <?= json_encode($user, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>; window.__CART__ = <?= json_encode($cart, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>; window.__TICKET_ORDER_IDS__ = <?= json_encode($ticketOrderIds, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>; window.__PENDING_REVIEW_ITEMS__ = <?= json_encode($pendingReviewItems, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;</script>
<div id="toasts"></div>
<div class="drawer-ov" id="drawerOv" onclick="closeCart()"></div>
<aside class="cart-drawer" id="cartDrawer">
  <div class="drw-hd"><h3 style="font-size:1.05rem">Shopping Cart</h3><button class="drw-close" onclick="closeCart()">✕</button></div>
  <div class="drw-body" id="cartBody"></div>
  <div class="drw-foot" id="cartFoot"></div>
</aside>

<div class="js-header" data-active="orders"></div>

<div class="site-wrapper">
<aside class="sidebar" data-active="orders"></aside>
<div class="site-content">
<div class="main-content"><div class="container">

  <div class="page-hdr">
    <div class="bc"><a href="index.php">Home</a><span class="sep">›</span><a href="profile.php">Account</a><span class="sep">›</span><span class="cur">My Orders</span></div>
    <span class="eyebrow">Order History</span>
    <h1>My Orders</h1>
  </div>

  <!-- Status filter (single page) -->
  <div class="order-toolbar" style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:20px">
    <label for="orderStatusFilter" class="t-sm" style="color:var(--muted);font-weight:500">Filter by status</label>
    <select id="orderStatusFilter" class="finput" style="max-width:240px;padding:10px 14px;height:auto;border-radius:var(--r-md);border:1px solid var(--border);background:var(--white)" onchange="applyStatusFilter()">
      <option value="all">All orders</option>
      <option value="pending">Pending</option>
      <option value="processing">Processing</option>
      <option value="shipped">Shipped</option>
      <option value="delivered">Delivered</option>
      <option value="cancelled">Cancelled</option>
    </select>
  </div>

  <!-- LOADING STATE -->
  <div id="ordersLoading" style="text-align:center;padding:60px 0;color:var(--muted-light)">
    <div style="font-size:2rem;margin-bottom:12px">⏳</div><p>Loading your orders…</p>
  </div>

  <!-- EMPTY STATE -->
  <div id="ordersEmpty" class="hidden" style="text-align:center;padding:60px 0">
    <div style="font-size:3rem;margin-bottom:12px">📦</div>
    <h2 style="margin-bottom:8px">No orders found</h2>
    <p style="margin-bottom:24px">You haven't placed any orders yet.</p>
    <a href="product-list.php" class="btn btn-primary">Start Shopping</a>
  </div>

  <!-- ORDERS TABLE -->
  <div id="ordersTableWrap" class="hidden">
    <div class="card">
      <div class="card-body" style="padding:0">
        <table class="tbl">
          <thead><tr>
            <th>Order #</th><th>Item</th><th>Date</th><th>Amount</th><th>Status</th><th>Actions</th>
          </tr></thead>
          <tbody id="ordersBody"></tbody>
        </table>
      </div>
    </div>
  </div>

</div></div>

<!-- ORDER DETAIL MODAL -->
<div class="modal-ov" id="orderModal" onclick="if(event.target===this)closeModal()">
  <div class="modal">
    <div class="modal-hd">
      <h3 id="modalTitle">Order Details</h3>
      <button class="modal-x" onclick="closeModal()">✕</button>
    </div>
    <div id="modalBody"></div>
  </div>
</div>

<div class="modal-ov" id="reviewModal" aria-hidden="true">
  <div class="modal" style="max-width:640px">
    <div class="modal-hd">
      <div>
        <h3 id="reviewModalTitle">Rate your delivered item</h3>
        <p id="reviewModalSub" class="t-sm muted" style="margin-top:4px">Your rating is required for delivered products.</p>
      </div>
      <button class="modal-x" id="reviewModalClose" type="button" aria-label="Close review modal">✕</button>
    </div>
    <div id="reviewModalBody"></div>
  </div>
</div>

<div class="js-footer"></div>
</div></div>

<!-- COURIER RATING MODAL -->
<div class="modal-ov" id="courierModal" aria-hidden="true">
  <div class="modal" style="max-width:560px">
    <div class="modal-hd">
      <div>
        <h3 id="courierModalTitle">Rate your delivery</h3>
        <p id="courierModalSub" class="t-sm muted" style="margin-top:4px">Tell us about your courier experience.</p>
      </div>
      <button class="modal-x" id="courierModalClose" type="button" aria-label="Close courier modal">✕</button>
    </div>
    <div id="courierModalBody"></div>
  </div>
</div>



<script>
let allOrders     = [];
let currentStatus = 'all';
const existingTicketOrderIds = new Set((window.__TICKET_ORDER_IDS__ || []).map(String));

function applyStatusFilter() {
  const sel = document.getElementById('orderStatusFilter');
  currentStatus = sel ? sel.value : 'all';
  const filtered = currentStatus === 'all' ? allOrders : allOrders.filter(o => o.status === currentStatus);
  renderOrders(filtered);
}

const BADGE_CLASS = {
  pending    : 'b-pending',
  processing : 'b-processing',
  shipped    : 'b-shipped',
  delivered  : 'b-delivered',
  cancelled  : 'b-cancelled',
};

function renderOrders(list) {
  const loading  = document.getElementById('ordersLoading');
  const empty    = document.getElementById('ordersEmpty');
  const tableWrap= document.getElementById('ordersTableWrap');
  const body     = document.getElementById('ordersBody');

  loading.classList.add('hidden');

  if (!list.length) {
    empty.classList.remove('hidden');
    tableWrap.classList.add('hidden');
    return;
  }
  empty.classList.add('hidden');
  tableWrap.classList.remove('hidden');

  body.innerHTML = list.map(o => `
    <tr>
      <td><span style="font-weight:600;font-size:.82rem">#${o.id}</span></td>
      <td>
        <div style="display:flex;align-items:center;gap:10px;cursor:pointer" onclick="viewOrder('${o.id}')" title="View order details">
          <div class="iph sm" style="width:40px;height:40px;font-size:1.2rem;flex-shrink:0">${escapeHtml(o.emoji || '💍')}</div>
          <div>
            <div style="font-family:var(--fd);font-size:.87rem;font-weight:500">${escapeHtml(o.product || o.name || '—')}</div>
            <div class="t-xs muted">${escapeHtml(o.cat || '')} ${o.size ? '· ' + escapeHtml(o.size) : ''}</div>
          </div>
        </div>
      </td>
      <td class="t-sm muted">${o.date || '—'}</td>
      <td><span style="font-family:var(--fd);font-weight:600;font-size:.9rem">₱${(o.price||0).toLocaleString()}</span></td>
      <td><span class="badge ${BADGE_CLASS[o.status] || ''}">${escapeHtml(capitalize(o.status))}</span></td>
      <td>
        <div class="flex g-2">
          ${o.status === 'delivered' && !o.is_received ? `<button class="btn btn-primary btn-sm" onclick="markOrderReceived('${o.id}')">Order Received</button>` : ''}
          ${o.status === 'delivered' && o.is_received && (o.pending_review_count || 0) > 0 ? `<button class="btn btn-primary btn-sm" onclick="openOrderReviewQueue('${o.id}')">Rate</button>` : ''}
          ${o.status === 'delivered' && o.is_received && (o.pending_review_count || 0) === 0 ? `<button class="btn btn-ghost btn-sm" onclick="reorder('${o.id}')">Buy Again</button>` : ''}
          <button class="btn btn-ghost btn-sm" onclick="reportIssue('${o.id}')">Report</button>
          ${o.status === 'pending'   ? `<button class="btn btn-sm" style="background:#FFF0F0;color:var(--danger);border:1px solid #EEAAAA" onclick="cancelOrder('${o.id}')">Cancel</button>` : ''}
        </div>
      </td>
    </tr>`).join('');
}

function viewOrder(id) {
  const o = allOrders.find(x => x.id === id);
  if (!o) return;
  document.getElementById('modalTitle').textContent = `Order #${o.id}`;
  document.getElementById('modalBody').innerHTML = `
    <div style="display:flex;align-items:center;gap:14px;padding:16px 0;border-bottom:1px solid var(--border);margin-bottom:16px">
      <div class="iph" style="width:68px;height:68px;border-radius:var(--r-md);font-size:2rem;flex-shrink:0">${escapeHtml(o.emoji || '💍')}</div>
      <div>
        <div style="font-family:var(--fd);font-size:1rem;font-weight:500">${escapeHtml(o.product || o.name || '')}</div>
        <div class="t-xs muted">${escapeHtml(o.cat || '')} ${o.size ? '· ' + escapeHtml(o.size) : ''} · Qty: ${o.qty || 1}</div>
        <div style="font-family:var(--fd);font-weight:700;color:var(--rose-deep);margin-top:4px">₱${(o.price||0).toLocaleString()}</div>
      </div>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px">
      <div style="background:var(--blush);border-radius:var(--r-md);padding:12px"><div class="t-xs muted mb2">Status</div><span class="badge ${BADGE_CLASS[o.status]||''}">${escapeHtml(capitalize(o.status))}</span></div>
      <div style="background:var(--blush);border-radius:var(--r-md);padding:12px"><div class="t-xs muted mb2">Order Date</div><div style="font-size:.84rem;font-weight:600">${o.date || '—'}</div></div>
    </div>
    ${o.shipping ? `
    <div style="background:var(--blush);border-radius:var(--r-md);padding:14px;margin-bottom:16px">
      <div class="t-xs muted mb2">Delivery Address</div>
      <div style="font-size:.84rem;font-weight:600">${escapeHtml(o.shipping.name || '')}</div>
      <div class="t-sm muted">${escapeHtml(o.shipping.street || '')}, ${escapeHtml(o.shipping.city || '')} ${escapeHtml(o.shipping.zip || '')}</div>
    </div>` : ''}
    ${o.status === 'delivered' && !o.is_received ? `<div class="alert alert-info">Click <strong>Order Received</strong> once you have physically received this package to unlock product rating.</div>` : ''}
    ${o.status === 'delivered' && o.is_received && (o.pending_review_count || 0) > 0 ? `<div class="alert alert-warn">This delivered order still needs ${o.pending_review_count} product rating${(o.pending_review_count || 0) === 1 ? '' : 's'}.</div>` : ''}
    <div class="flex g-3">
      ${o.status === 'delivered' && !o.is_received ? `<button class="btn btn-primary" style="flex:1" onclick="markOrderReceived('${o.id}');closeModal()">Order Received</button>` : ''}
      ${o.status === 'delivered' && o.is_received && (o.pending_review_count || 0) > 0 ? `<button class="btn btn-primary" style="flex:1" onclick="openOrderReviewQueue('${o.id}');closeModal()">Rate now</button>` : ''}
      ${o.status === 'delivered' && o.is_received && (o.pending_review_count || 0) === 0 ? `<button class="btn btn-primary" style="flex:1" onclick="reorder('${o.id}');closeModal()">Buy Again</button>` : ''}
      <button class="btn btn-ghost" style="flex:1" onclick="closeModal()">Close</button>
    </div>`;
  document.getElementById('orderModal').classList.add('open');
}

function closeModal() { document.getElementById('orderModal').classList.remove('open'); }

function reorder(id) {
  const o = allOrders.find(x => x.id === id);
  if (!o) return;
  // Find matching product by name
  const p = null; // product info comes from API order items
  if (p) addToCart(p.id);
  openCart();
}

function reportIssue(id) {
  if (!id) return;
  if (existingTicketOrderIds.has(String(id))) {
    toast('A support ticket for this order already exists.', 'warn');
    return;
  }
  window.location.href = 'support-ticket.php?order_id=' + encodeURIComponent(id);
}

function cancelOrder(id) {
  if (!confirm(`Cancel order #${id}? This cannot be undone.`)) return;
  const o = allOrders.find(x => x.id === id);
  if (o) {
    o.status = 'cancelled';
    applyStatusFilter();
    toast('Order cancelled');
    // Backend: API.updateOrder(id, { status: 'cancelled' }) — or a dedicated cancel endpoint
  }
}

async function markOrderReceived(id) {
  if (!id) return;
  const csrfToken = getCsrfToken();
  const payload = new URLSearchParams({
    order_id: String(id),
    csrf_token: String(csrfToken || ''),
  });

  const resp = await fetch('api/mark_order_received.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
      ...(csrfToken ? { 'X-CSRF-Token': csrfToken } : {}),
    },
    body: payload.toString(),
  });

  const raw = await resp.text();
  let data = {};
  try {
    data = raw ? JSON.parse(raw) : {};
  } catch (e) {
    data = { error: raw || '' };
  }

  if (!resp.ok || data.ok === false) {
    toast((data && data.error) ? data.error : 'Unable to confirm order receipt.', 'warn');
    return;
  }

  const orderId = String(id);
  const newPending = Array.isArray(data.pending_items) ? data.pending_items : [];
  const existing = Array.isArray(window.__PENDING_REVIEW_ITEMS__) ? window.__PENDING_REVIEW_ITEMS__ : [];
  const keepOtherOrders = existing.filter(item => String(item.order_id) !== orderId);
  window.__PENDING_REVIEW_ITEMS__ = keepOtherOrders.concat(newPending);

  allOrders = allOrders.map(order => {
    if (String(order.id) !== orderId) return order;
    return {
      ...order,
      is_received: true,
      received_at: data.received_at || '',
      pending_review_count: Number(data.pending_count || 0),
    };
  });

  applyStatusFilter();
  toast(data.message || 'Order marked as received.', 'ok');

  if (newPending.length > 0) {
    openOrderReviewQueue(orderId);
  }
}

function capitalize(s) { return s ? s.charAt(0).toUpperCase() + s.slice(1) : ''; }

let reviewQueue = [];
let reviewRating = 0;
let reviewComment = '';
let reviewQueueTotal = 0;
let lastSubmittedOrderId = null;

function getCsrfToken() {
  const meta = document.querySelector('meta[name="csrf-token"]');
  return meta ? (meta.getAttribute('content') || '') : '';
}

function syncPendingReviewCounts() {
  const pendingByOrder = new Map();
  reviewQueue.forEach(item => {
    pendingByOrder.set(item.order_id, (pendingByOrder.get(item.order_id) || 0) + 1);
  });
  allOrders = allOrders.map(order => ({
    ...order,
    pending_review_count: pendingByOrder.get(order.id) || 0,
  }));
}

function openOrderReviewQueue(orderId) {
  reviewQueue = (window.__PENDING_REVIEW_ITEMS__ || []).filter(item => String(item.order_id) === String(orderId));
  reviewQueueTotal = reviewQueue.length;
  reviewRating = 0;
  reviewComment = '';
  renderReviewModal();
  document.getElementById('reviewModal').classList.add('open');
  document.getElementById('reviewModal').setAttribute('aria-hidden', 'false');
  document.body.style.overflow = 'hidden';
}

function renderRatingStars(currentRating) {
  return [1,2,3,4,5].map(value => `<button type="button" class="rating-star ${value <= currentRating ? 'active' : ''}" onclick="setReviewRating(${value})" aria-label="Rate ${value} star${value === 1 ? '' : 's'}">★</button>`).join('');
}

function renderReviewModal() {
  const body = document.getElementById('reviewModalBody');
  const closeBtn = document.getElementById('reviewModalClose');
  const item = reviewQueue[0];
  if (!body) return;

  if (!item) {
    body.innerHTML = '<div class="alert alert-success">All pending product ratings are complete.</div>';
    if (closeBtn) closeBtn.style.display = 'inline-flex';
    return;
  }

  if (closeBtn) closeBtn.style.display = 'none';
  const currentIndex = Math.max(1, (reviewQueueTotal - reviewQueue.length) + 1);
  body.innerHTML = `
    <div class="rating-card">
      <div class="rating-progress">${currentIndex} of ${reviewQueueTotal || reviewQueue.length} pending</div>
      <div style="display:flex;align-items:center;gap:12px;margin-top:10px">
        <div class="iph sm" style="width:52px;height:52px;font-size:1.4rem">💍</div>
        <div>
          <div style="font-family:var(--fd);font-size:1rem;font-weight:600">${escapeHtml(item.product_name || '')}</div>
          <div class="t-xs muted">${escapeHtml(item.product_cat || '')}${item.product_size ? ' · ' + escapeHtml(item.product_size) : ''}</div>
          <div class="t-xs muted">Order #${escapeHtml(item.order_id || '')} · ${escapeHtml(item.order_date || '')}</div>
        </div>
      </div>
      <div class="rating-stars">${renderRatingStars(reviewRating)}</div>
      <div class="fg" style="margin-top:14px;margin-bottom:0">
        <label class="flabel">Optional comment</label>
        <textarea class="ftextarea" id="reviewCommentInput" rows="4" maxlength="1000" placeholder="Tell us what you thought about this item...">${escapeHtml(reviewComment)}</textarea>
        <div class="fhint" style="display:flex;justify-content:space-between;align-items:center;gap:8px">
          <span>You still need to choose a star rating. Comment is optional.</span>
          <button type="button" class="btn btn-ghost btn-sm" id="skipCommentBtn" style="padding:6px 10px">Skip comment</button>
        </div>
      </div>
      <div class="rating-actions">
        <button type="button" class="btn btn-primary" id="submitReviewBtn" onclick="submitCurrentReview()" ${reviewRating < 1 ? 'disabled' : ''}>Submit rating</button>
      </div>
    </div>`;

  const commentInput = document.getElementById('reviewCommentInput');
  if (commentInput) {
    commentInput.value = reviewComment;
    commentInput.addEventListener('input', function () {
      reviewComment = this.value || '';
    });
  }

  const skipCommentBtn = document.getElementById('skipCommentBtn');
  if (skipCommentBtn && commentInput) {
    skipCommentBtn.addEventListener('click', function () {
      reviewComment = '';
      commentInput.value = '';
      commentInput.focus();
    });
  }
}

function setReviewRating(value) {
  reviewRating = Number(value) || 0;
  renderReviewModal();
}

async function submitCurrentReview() {
  const item = reviewQueue[0];
  if (!item || reviewRating < 1) return;
  const csrfToken = getCsrfToken();

  const payload = new URLSearchParams({
    order_id: String(item.order_id || ''),
    product_id: String(item.product_id || ''),
    rating: String(reviewRating),
    body: String(reviewComment || ''),
    csrf_token: String(csrfToken || ''),
  });

  const resp = await fetch('api/submit_review.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
      ...(csrfToken ? { 'X-CSRF-Token': csrfToken } : {}),
    },
    body: payload.toString(),
  });

  const raw = await resp.text();
  let data = {};
  try {
    data = raw ? JSON.parse(raw) : {};
  } catch (e) {
    data = { error: raw || '' };
  }
  if (!resp.ok || data.ok === false) {
    toast((data && data.error) ? data.error : 'Unable to save rating.', 'warn');
    return;
  }

  toast(data.message || 'Rating saved', 'ok');
  // track last submitted order so we can offer courier rating afterwards
  lastSubmittedOrderId = String(item.order_id || '');

  // Remove the just-reviewed item from the global pending list (if present)
  try {
    window.__PENDING_REVIEW_ITEMS__ = (window.__PENDING_REVIEW_ITEMS__ || []).filter(function(i) {
      return !(String(i.order_id) === String(item.order_id) && String(i.product_id) === String(item.product_id));
    });
  } catch (e) {}

  // Remove from local queue and update counts
  reviewQueue.shift();
  reviewRating = 0;
  reviewComment = '';
  syncPendingReviewCounts();
  renderOrders(currentStatus === 'all' ? allOrders : allOrders.filter(o => o.status === currentStatus));

  if (reviewQueue.length) {
    const body = document.getElementById('reviewModalBody');
    if (body) {
      body.style.opacity = '0.35';
      body.style.transition = 'opacity 0.18s ease';
    }
    setTimeout(function () {
      renderReviewModal();
      const refreshed = document.getElementById('reviewModalBody');
      if (refreshed) {
        refreshed.style.opacity = '1';
      }
    }, 180);
  } else {
    reviewQueueTotal = 0;
    closeReviewModal();
    // After all product ratings for this order are complete, prompt for courier rating
    if (lastSubmittedOrderId) {
      // small delay so UI transitions feel natural
      setTimeout(function () { openCourierRatingIfNeeded(lastSubmittedOrderId); lastSubmittedOrderId = null; }, 220);
    }
  }
}

function openCourierRatingIfNeeded(orderId) {
  if (!orderId) return;
  const order = (allOrders || []).find(o => String(o.id) === String(orderId));
  if (!order) return;
  // If no courier assigned, still show modal to allow optional feedback
  openCourierModal(orderId);
}

function openCourierModal(orderId) {
  const order = (allOrders || []).find(o => String(o.id) === String(orderId));
  if (!order) return;
  window.__CURRENT_COURIER_ORDER__ = String(orderId);
  renderCourierModal();
  const m = document.getElementById('courierModal');
  if (m) { m.classList.add('open'); m.setAttribute('aria-hidden', 'false'); }
  document.body.style.overflow = 'hidden';
}

function renderCourierModal() {
  const body = document.getElementById('courierModalBody');
  const orderId = window.__CURRENT_COURIER_ORDER__;
  const order = (allOrders || []).find(o => String(o.id) === String(orderId));
  if (!body) return;
  if (!order) {
    body.innerHTML = '<div class="alert alert-info">Order not found.</div>';
    return;
  }
  const courierName = order.courier_name || 'Delivery service';
  body.innerHTML = `
    <div class="rating-card">
      <div style="font-weight:700;margin-bottom:8px">${escapeHtml(courierName)}</div>
      <div class="rating-stars" id="courierStars">${[1,2,3,4,5].map(v => `<button type="button" class="rating-star" onclick="setCourierRating(${v})">★</button>`).join('')}</div>
      <div class="fg" style="margin-top:14px;margin-bottom:0">
        <label class="flabel">Optional comment</label>
        <textarea class="ftextarea" id="courierCommentInput" rows="4" maxlength="1000" placeholder="Tell us about the delivery..."></textarea>
      </div>
      <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:12px">
        <button class="btn btn-ghost" onclick="closeCourierModal()">Skip</button>
        <button class="btn btn-primary" id="submitCourierBtn" onclick="submitCourierRating()" disabled>Submit rating</button>
      </div>
    </div>`;
  window.__courierRating = 0;
  const comment = document.getElementById('courierCommentInput');
  if (comment) comment.addEventListener('input', function () {});
}

function setCourierRating(v) {
  window.__courierRating = Number(v) || 0;
  // update stars UI
  const stars = document.querySelectorAll('#courierStars .rating-star');
  stars.forEach((btn, idx) => { btn.classList.toggle('active', (idx+1) <= window.__courierRating); });
  const sub = document.getElementById('submitCourierBtn');
  if (sub) sub.disabled = window.__courierRating < 1;
}

async function submitCourierRating() {
  const orderId = window.__CURRENT_COURIER_ORDER__;
  if (!orderId) return;
  const rating = Number(window.__courierRating || 0);
  if (rating < 1) return;
  const comment = (document.getElementById('courierCommentInput') || {}).value || '';
  const csrfToken = getCsrfToken();
  const payload = new URLSearchParams({ order_id: String(orderId), rating: String(rating), body: String(comment || ''), csrf_token: String(csrfToken || '') });
  const resp = await fetch('api/submit_courier_rating.php', { method: 'POST', headers: { 'Content-Type':'application/x-www-form-urlencoded;charset=UTF-8', ...(csrfToken?{'X-CSRF-Token':csrfToken}:{}) }, body: payload.toString() });
  const raw = await resp.text(); let data = {};
  try { data = raw ? JSON.parse(raw) : {}; } catch (e) { data = { error: raw || '' }; }
  if (!resp.ok || data.ok === false) { toast((data && data.error) ? data.error : 'Unable to save courier rating.', 'warn'); return; }
  toast(data.message || 'Courier rating saved', 'ok');
  // close modal and clear current
  closeCourierModal();
}

function closeCourierModal() {
  const modal = document.getElementById('courierModal');
  if (!modal) return;
  modal.classList.remove('open');
  modal.setAttribute('aria-hidden', 'true');
  document.body.style.overflow = '';
  window.__CURRENT_COURIER_ORDER__ = null;
}

document.getElementById('courierModalClose')?.addEventListener('click', closeCourierModal);
document.getElementById('courierModal')?.addEventListener('click', function(ev) { if (ev.target === this) closeCourierModal(); });

function closeReviewModal() {
  const modal = document.getElementById('reviewModal');
  if (!modal) return;
  modal.classList.remove('open');
  modal.setAttribute('aria-hidden', 'true');
  document.body.style.overflow = '';
}

document.getElementById('reviewModalClose')?.addEventListener('click', function () {
  // Allow users to exit the review modal at any time. Keep the queue intact so
  // pending ratings can be resumed later.
  closeReviewModal();
});

document.getElementById('reviewModal')?.addEventListener('click', function (ev) {
  if (ev.target === this) {
    closeReviewModal();
  }
});

document.addEventListener('DOMContentLoaded', () => {
  allOrders = window.__ORDERS__ || [];
  syncPendingReviewCounts();
  renderOrders(allOrders);
  // Modals only open when user clicks Rate or after a product rating completes
});
</script>

  <script src="js/api.js"></script>
  <script src="js/app.js?v=3"></script>
<script>
  (function () {
    var key = window.__SCROLL_RESTORE_BOOTSTRAP__ || ('scroll_restore::' + location.pathname);

    function saveScrollPosition() {
      try {
        sessionStorage.setItem(key, String(window.scrollY || 0));
      } catch (e) {}
    }

    function restoreScrollPositionOnce() {
      var raw = null;
      try {
        raw = sessionStorage.getItem(key);
      } catch (e) {}
      if (raw === null) {
        document.documentElement.style.opacity = '1';
        return;
      }

      var savedY = Number(raw);
      try {
        sessionStorage.removeItem(key);
      } catch (e) {}

      requestAnimationFrame(function () {
        requestAnimationFrame(function () {
          if (!Number.isNaN(savedY) && savedY > 0) {
            window.scrollTo({ top: savedY, behavior: 'instant' });
          }
          document.documentElement.style.transition = 'opacity 0.15s ease';
          document.documentElement.style.opacity = '1';
        });
      });
    }

    window.addEventListener('beforeunload', saveScrollPosition);
    window.addEventListener('pagehide', saveScrollPosition);
    window.addEventListener('load', restoreScrollPositionOnce);
  })();
</script>
</body>
</html>