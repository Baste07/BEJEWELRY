<?php
require_once __DIR__ . '/inc.php';
if (!current_user_id()) {
  header('Location: login.php?redirect=' . urlencode('profile.php'));
  exit;
}
$user = current_user();
$cart = get_customer_cart();

// Precompute profile avatar URL if a file exists
$avatarUrl = null;
if ($user && !empty($user['id'])) {
  $uid = (int)$user['id'];
  $baseDir = __DIR__ . '/uploads/profile';
  foreach (['jpg','jpeg','png','webp'] as $ext) {
    $p = $baseDir . '/user_' . $uid . '.' . $ext;
    if (is_file($p)) {
      $avatarUrl = 'uploads/profile/' . basename($p);
      break;
    }
  }
  $user['avatar_url'] = $avatarUrl;
}

// Recent orders for quick stats + sidebar
$ordersStmt = db()->prepare('SELECT o.id, o.status, o.total, o.created_at,
  (SELECT oi.name FROM order_items oi WHERE oi.order_id = o.id LIMIT 1) AS item_name
  FROM orders o WHERE o.user_id = ? ORDER BY o.created_at DESC');
$ordersStmt->execute([current_user_id()]);
$ordersRows = $ordersStmt->fetchAll(PDO::FETCH_ASSOC);
$toRateCountStmt = db()->prepare(
  'SELECT COUNT(*)
   FROM order_items oi
   INNER JOIN orders o ON o.id = oi.order_id
   LEFT JOIN product_reviews pr
     ON pr.user_id = o.user_id
    AND pr.order_id = o.id
    AND pr.product_id = oi.product_id
   WHERE o.user_id = ?
     AND o.status = ?
     AND oi.product_id IS NOT NULL
     AND oi.product_id > 0
     AND pr.id IS NULL'
);
$toRateCountStmt->execute([current_user_id(), 'delivered']);
$toRateCount = (int) ($toRateCountStmt->fetchColumn() ?: 0);
$recentOrders = [];
foreach ($ordersRows as $oRow) {
  $dt = null;
  try { $dt = new DateTime((string)($oRow['created_at'] ?? '')); } catch (Exception $e) { $dt = null; }
  $recentOrders[] = [
    'id'     => $oRow['id'],
    'status' => $oRow['status'],
    'price'  => (float)($oRow['total'] ?? 0),
    'date'   => $dt ? $dt->format('M j, Y') : '',
    'product'=> $oRow['item_name'] ?? '—',
  ];
}

// Wishlist snapshot for profile page
$wishlist = $user ? get_customer_wishlist() : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<?= csrf_meta_tag() ?>
<title>My Account — Bejewelry</title>
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
.account-wrap{display:grid;grid-template-columns:180px 1fr;gap:var(--s5);align-items:flex-start}
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
<style>
.toggle-wrap{position:relative;display:inline-block;width:44px;height:24px;cursor:pointer;flex-shrink:0}
.toggle-wrap input{opacity:0;width:0;height:0}
.toggle-track{position:absolute;inset:0;border-radius:12px;background:var(--border);transition:.3s}
.toggle-wrap input:checked~.toggle-track{background:var(--rose)}
.toggle-thumb{position:absolute;top:3px;left:3px;width:18px;height:18px;background:white;border-radius:50%;transition:.3s;box-shadow:0 1px 3px rgba(0,0,0,.2)}
.toggle-wrap input:checked~.toggle-thumb{transform:translateX(20px)}
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
<script>window.__USER__ = <?= json_encode($user, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>; window.__CART__ = <?= json_encode($cart, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>; window.__ORDERS__ = <?= json_encode($recentOrders, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>; window.__TO_RATE_COUNT__ = <?= json_encode($toRateCount, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;</script>
<div id="toasts"></div>
<div class="drawer-ov" id="drawerOv" onclick="closeCart()"></div>
<aside class="cart-drawer" id="cartDrawer">
  <div class="drw-hd"><h3 style="font-size:1.05rem">Shopping Cart</h3><button class="drw-close" onclick="closeCart()">✕</button></div>
  <div class="drw-body" id="cartBody"></div>
  <div class="drw-foot" id="cartFoot"></div>
</aside>

<div class="js-header" data-active="profile"></div>

<div class="site-wrapper">
<aside class="sidebar" data-active="profile"></aside>
<div class="site-content">
<div class="main-content"><div class="container">

  <div class="page-hdr">
    <div class="bc"><a href="index.php">Home</a><span class="sep">›</span><span class="cur">My Account</span></div>
    <span class="eyebrow">Customer</span><h1>My Account</h1>
  </div>

  <!-- QUICK STATS — data loaded dynamically -->
  <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:28px" id="quickStats">
    <!-- Rendered by JS -->
  </div>

  <div class="account-wrap">
    <!-- ACCOUNT NAV -->
    <div class="acnav">
      <div class="acnav-head">
        <div class="acnav-av" id="navAvatar">?</div>
        <div class="acnav-name" id="navName">Loading…</div>
        <div class="acnav-email" id="navMeta"></div>
      </div>
      <div class="acnav-item active" onclick="showPanel('profile',this)"><span>👤</span>Profile</div>
      <div class="acnav-item" onclick="showPanel('orders',this)"><span>📦</span>My Orders</div>
      <div class="acnav-item" onclick="showPanel('wishlist',this)"><span>♡</span>Wishlist</div>
      <div class="acnav-item" onclick="showPanel('addresses',this)"><span>📍</span>Addresses</div>
      <div class="acnav-item" onclick="showPanel('notifs',this)"><span>🔔</span>Notifications</div>
      <div class="acnav-item" onclick="showPanel('settings',this)"><span>⚙️</span>Account Settings</div>

    </div>

    <!-- PANELS -->
    <div>
      <!-- PROFILE PANEL -->
      <div id="panel-profile" class="acpanel">
        <div class="panel-hd">
          <h2>Personal Information</h2>
          <button type="button" class="btn btn-ghost btn-sm" id="editBtn" onclick="toggleEdit()">✏️ Edit</button>
        </div>
        <div style="display:flex;align-items:center;gap:20px;margin-bottom:28px">
          <div style="position:relative;width:72px;height:72px;flex-shrink:0">
            <div class="acnav-av" style="width:72px;height:72px;font-size:1.7rem;overflow:hidden" id="profileAvatar">?</div>
            <input type="file" id="photoInput" accept="image/*" style="display:none" onchange="handlePhotoUpload(event)">
          </div>
          <div>
            <h3 style="font-size:1.1rem;margin-bottom:3px" id="profileDisplayName">Loading…</h3>
            <p style="font-size:.8rem;margin-bottom:6px" id="profileMeta"></p>
            <button class="btn btn-ghost btn-sm" onclick="document.getElementById('photoInput').click()">📷 Upload Photo</button>
          </div>
        </div>
        <form id="profileForm" method="post" action="profile_update.php">
          <?php echo csrf_token_field(); ?>
        <div class="frow">
          <div class="fg"><label class="flabel">First Name</label><input class="finput" id="pfFirst" name="first_name" readonly></div>
          <div class="fg"><label class="flabel">Last Name</label><input class="finput" id="pfLast" name="last_name" readonly></div>
        </div>
        <div class="frow">
          <div class="fg">
            <label class="flabel">Gender</label>
            <select class="fselect" id="pfGender" name="gender" disabled>
              <option value="">Prefer not to say</option>
              <option value="Female">Female</option>
              <option value="Male">Male</option>
            </select>
          </div>
          <div class="fg"><label class="flabel">Date of Birth</label><input class="finput" id="pfBirthday" name="birthday" type="date" readonly></div>
        </div>
        <div class="frow">
          <div class="fg"><label class="flabel">Phone Number</label><input class="finput" id="pfPhone" name="phone" type="tel" readonly></div>
          <div class="fg"><label class="flabel">Email Address</label><input class="finput" id="pfEmail" name="email" type="email" readonly></div>
        </div>
        <div class="fg"><label class="flabel">City / Municipality</label><input class="finput" id="pfCity" name="city" readonly></div>
        <div id="saveRow" class="hidden mt4">
          <button type="submit" class="btn btn-primary" id="saveBtn">Save Changes</button>
          <button type="button" class="btn btn-ghost" onclick="toggleEdit()" style="margin-left:8px">Cancel</button>
        </div>
        </form>
      </div>

      <!-- ORDERS PANEL -->
      <div id="panel-orders" class="acpanel hidden">
        <div class="panel-hd"><h2>Recent Orders</h2><a href="order_history.php" class="btn btn-ghost btn-sm">View All</a></div>
        <div id="recentOrders" style="display:flex;flex-direction:column;gap:12px">
          <p class="muted t-sm">Loading…</p>
        </div>
      </div>

      <!-- WISHLIST PANEL -->
      <div id="panel-wishlist" class="acpanel hidden">
        <div class="panel-hd"><h2>My Wishlist</h2><a href="wishlist.php" class="btn btn-ghost btn-sm">View All</a></div>
        <div class="g3" id="wishSnap" data-server-rendered="1">
          <?php if (empty($wishlist)): ?>
            <p class="muted t-sm">No wishlist items yet. <a href="product-list.php">Browse</a></p>
          <?php else: ?>
            <?php $count = 0; foreach ($wishlist as $p): if ($count++ >= 4) break; ?>
              <div class="wish-card" style="display:flex;gap:12px;align-items:center;padding:12px;border:1px solid var(--border);border-radius:12px;background:var(--white)">
                <div style="width:72px;height:72px;flex-shrink:0;border-radius:8px;overflow:hidden;background:#f7f7f7">
                  <img src="<?= htmlspecialchars($p['image_url'] ?? 'uploads/products/placeholder.png') ?>" alt="<?= htmlspecialchars($p['name']) ?>" style="width:100%;height:100%;object-fit:cover">
                </div>
                <div style="flex:1">
                  <div style="font-weight:600;color:var(--dark)"><?= htmlspecialchars($p['name']) ?></div>
                  <div style="color:var(--muted);font-size:.9rem">PHP <?= number_format($p['price'],2) ?></div>
                </div>
                <div style="flex-shrink:0">
                  <a href="product_detail.php?id=<?= (int)$p['id'] ?>" class="btn btn-ghost btn-sm">View</a>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>

      <!-- ADDRESSES PANEL -->
      <div id="panel-addresses" class="acpanel hidden">
        <div class="panel-hd"><h2>My Addresses</h2><button class="btn btn-ghost btn-sm" onclick="showAddressForm()">+ Add New</button></div>

        <!-- 
  BEJEWELRY - Philippine Address Lookup Form
  Uses Nominatim (OpenStreetMap) + Leaflet.js
  No API keys required. Embeds seamlessly into profile.php address form.
  
  Integration: Insert this section BEFORE the "New Address" form fields in profile.php
-->

<style>
  /* ── ADDRESS LOOKUP STYLES ─── */
  .addr-lookup-section {
    background: linear-gradient(135deg, #FEF1F3 0%, #FCE4EC 100%);
    border: 1px solid #E0CBD3;
    border-radius: 18px;
    padding: 24px;
    margin-bottom: 24px;
    box-shadow: 0 2px 8px rgba(36, 20, 24, 0.08);
  }

  .addr-lookup-title {
    font-family: 'Playfair Display', Georgia, serif;
    font-size: 1.1rem;
    font-weight: 600;
    color: #241418;
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .addr-lookup-title::before {
    content: '📍';
    font-size: 1.3rem;
  }

  /* Search Bar */
  .addr-search-wrapper {
    margin-bottom: 0;
    position: relative;
    z-index: 101;
    background: inherit;
  }

  .addr-search-input {
    width: 100%;
    padding: 13px 16px;
    font-size: 0.9rem;
    color: #241418;
    background: #FFFFFF;
    border: 1.5px solid #D0B8C1;
    border-radius: 12px;
    outline: none;
    font-family: inherit;
    transition: all 0.25s ease;
    box-shadow: 0 1px 3px rgba(36, 20, 24, 0.06);
  }

  .addr-search-input:focus {
    border-color: #D96070;
    box-shadow: 0 0 0 3px rgba(217, 96, 112, 0.1);
    background: #FFFBFC;
  }

  .addr-search-input::placeholder {
    color: #AC8898;
    font-weight: 500;
  }

  /* Autocomplete Dropdown */
  .addr-suggestions {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: #FFFFFF;
    border: 1px solid #E0CBD3;
    border-top: none;
    border-radius: 0 0 12px 12px;
    max-height: 280px;
    overflow-y: auto;
    z-index: 100;
    display: none;
    box-shadow: 0 4px 12px rgba(36, 20, 24, 0.12);
  }

  .addr-suggestions.show {
    display: block;
  }

  .addr-suggestion-item {
    padding: 12px 16px;
    cursor: pointer;
    font-size: 0.85rem;
    color: #3A2028;
    border-bottom: 1px solid #F5E8ED;
    transition: background-color 0.15s ease;
    line-height: 1.4;
  }

  .addr-suggestion-item:last-child {
    border-bottom: none;
  }

  .addr-suggestion-item:hover {
    background-color: #FEF1F3;
    color: #D96070;
    padding-left: 20px;
  }

  .addr-suggestion-item .location-name {
    font-weight: 600;
    display: block;
    margin-bottom: 2px;
  }

  .addr-suggestion-item .location-context {
    font-size: 0.75rem;
    color: #7A5E68;
  }

  /* Map Container */
  .addr-map-container {
    position: relative;
    width: 100%;
    height: 380px;
    border-radius: 14px;
    overflow: hidden;
    border: 1.5px solid #D0B8C1;
    box-shadow: 0 4px 18px rgba(36, 20, 24, 0.13);
    margin-bottom: 16px;
    margin-top: 300px;
    background: #F5F5F5;
  }

  #addr-map {
    width: 100%;
    height: 100%;
  }

  /* Map Info & Controls */
  .addr-map-info {
    position: absolute;
    bottom: 12px;
    left: 12px;
    background: #FFFFFF;
    padding: 10px 14px;
    border-radius: 10px;
    font-size: 0.75rem;
    color: #7A5E68;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    display: none;
    z-index: 10;
    max-width: 280px;
    line-height: 1.4;
  }

  .addr-map-info.show {
    display: block;
  }

  .addr-map-info strong {
    color: #241418;
    display: block;
    margin-bottom: 3px;
    font-weight: 600;
  }

  /* Helper Text */
  .addr-helper-text {
    font-size: 0.76rem;
    color: #7A5E68;
    margin-top: 10px;
    display: flex;
    align-items: center;
    gap: 6px;
  }

  .addr-helper-text::before {
    content: 'ℹ';
    font-weight: bold;
    color: #D96070;
  }

  /* Loading State */
  .addr-loading {
    display: none;
    text-align: center;
    padding: 16px;
    font-size: 0.82rem;
    color: #7A5E68;
  }

  .addr-loading.show {
    display: block;
  }

  /* Leaflet Overrides for Theme */
  .leaflet-control-zoom {
    border-radius: 8px;
    border: 1.5px solid #D0B8C1 !important;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12) !important;
  }

  .leaflet-control-zoom-in,
  .leaflet-control-zoom-out {
    color: #D96070 !important;
    font-weight: bold !important;
    font-size: 1.1rem !important;
  }

  .leaflet-control-zoom-in:hover,
  .leaflet-control-zoom-out:hover {
    background-color: #FCE4EC !important;
  }

  /* Mobile Responsive */
  @media (max-width: 768px) {
    .addr-lookup-section {
      padding: 18px;
    }

    .addr-map-container {
      height: 300px;
    }

    .addr-search-input {
      font-size: 16px; /* Prevents zoom on iOS */
    }
  }
</style>

<!-- HTML Structure -->
<div class="addr-lookup-section hidden" id="addressLookupSection">
  <div class="addr-lookup-title">Find Your Address on Map</div>

  <!-- Search Bar with Autocomplete -->
  <div class="addr-search-wrapper">
    <div style="display: flex; gap: 8px; margin-bottom: 12px;">
      <input
        type="text"
        id="addrSearchInput"
        class="addr-search-input"
        placeholder="Search Philippine address, city, or barangay..."
        autocomplete="off"
        style="flex: 1;"
      />
      <button
        type="button"
        id="useCurrentLocationBtn"
        class="btn btn-ghost"
        style="white-space: nowrap; padding: 8px 12px; font-size: 0.9rem;"
        title="Use your current GPS location"
      >
        📍 Current Location
      </button>
    </div>
    <div class="addr-suggestions" id="addrSuggestions"></div>
    <div class="addr-loading" id="addrLoading">
      <span>🔍 Searching...</span>
    </div>
  </div>

  <!-- Interactive Map -->
  <div class="addr-map-container">
    <div id="addr-map"></div>
    <div class="addr-map-info" id="addrMapInfo">
      <strong id="mapInfoName">Selected Location</strong>
      <div id="mapInfoAddress"></div>
    </div>
  </div>

  <!-- Helper Text -->
  <div class="addr-helper-text">
    Click the map or search above to select your location
  </div>
</div>

<!-- Include Leaflet CSS (must come first) -->
<link
  rel="stylesheet"
  href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css"
/>
<!-- Include Leaflet JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>

<script>
  /**
   * BEJEWELRY Address Lookup - Nominatim + Leaflet Integration
   * Handles Philippine address search and map-based location selection
   */

  (function () {
    'use strict';

    // Configuration
    const CONFIG = {
      map: {
        center: [12.8797, 121.774],        // Center of Philippines
        zoom: 6,
        maxZoom: 19,
        minZoom: 5,
      },
      nominatim: {
        baseUrl: 'nominatim_proxy.php',
        reverseUrl: 'nominatim_proxy.php',
        countryCode: 'ph',
        timeout: 5000,
        email: '<?php echo htmlspecialchars(SMTP_FROM_EMAIL); ?>', // Uses SMTP_FROM_EMAIL from api/config.php
      },
    };

    // DOM Elements
    const elements = {
      searchInput: document.getElementById('addrSearchInput'),
      suggestions: document.getElementById('addrSuggestions'),
      loading: document.getElementById('addrLoading'),
      mapContainer: document.getElementById('addr-map'),
      mapInfo: document.getElementById('addrMapInfo'),
      mapInfoName: document.getElementById('mapInfoName'),
      mapInfoAddress: document.getElementById('mapInfoAddress'),
      useCurrentLocationBtn: document.getElementById('useCurrentLocationBtn'),
    };

    function getAddressFormInputs() {
      return {
        streetInput: document.getElementById('addrStreet'),
        cityInput: document.getElementById('addrCity'),
        provinceInput: document.getElementById('addrProvince'),
        zipInput: document.getElementById('addrZip'),
        latInput: document.getElementById('addrLat'),
        lngInput: document.getElementById('addrLng'),
      };
    };

    let map = null;
    let marker = null;
    let currentLocation = null;
    let searchTimeout = null;
    let mapInitialized = false;
    let zipLookupTimeout = null;
    let zipLookupRequestId = 0;

    /**
     * Initialize the map
     */
    function initMap() {
      if (mapInitialized) return;
      map = L.map(elements.mapContainer).setView(CONFIG.map.center, CONFIG.map.zoom);

      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://osm.org/copyright">OpenStreetMap</a> contributors',
        maxZoom: CONFIG.map.maxZoom,
        minZoom: CONFIG.map.minZoom,
      }).addTo(map);

      // Handle map clicks
      map.on('click', function (e) {
        setMarkerLocation(e.latlng.lat, e.latlng.lng);
      });

      mapInitialized = true;
    }

    /**
     * Search using Nominatim API
     */
    async function searchAddress(query) {
      if (!query.trim()) {
        elements.suggestions.classList.remove('show');
        return;
      }

      elements.loading.classList.add('show');

      try {
        const params = new URLSearchParams({
          action: 'search',
          q: query,
          countrycodes: CONFIG.nominatim.countryCode,
          format: 'json',
          limit: 10,
          addressdetails: 1,
          email: CONFIG.nominatim.email,
        });

        const controller = new AbortController();
        const timeout = setTimeout(() => controller.abort(), CONFIG.nominatim.timeout);

        const response = await fetch(
          `${CONFIG.nominatim.baseUrl}?${params}`,
          { signal: controller.signal }
        );

        clearTimeout(timeout);

        if (!response.ok) throw new Error('API error');

        const results = await response.json();
        displaySuggestions(results);
      } catch (error) {
        console.error('Search error:', error);
        elements.suggestions.innerHTML = '<div class="addr-suggestion-item" style="color:#C0392B">Unable to search. Check your connection.</div>';
        elements.suggestions.classList.add('show');
      } finally {
        elements.loading.classList.remove('show');
      }
    }

    /**
     * Display autocomplete suggestions
     */
    function displaySuggestions(results) {
      elements.suggestions.innerHTML = '';

      if (results.length === 0) {
        elements.suggestions.innerHTML = '<div class="addr-suggestion-item" style="color:#7A5E68">No results found in Philippines</div>';
        elements.suggestions.classList.add('show');
        return;
      }

      results.forEach((result) => {
        const item = document.createElement('div');
        item.className = 'addr-suggestion-item';

        const name = result.name || result.address?.city || result.address?.town || 'Unknown';
        const context = buildLocationContext(result.address);

        item.innerHTML = `
          <span class="location-name">${name}</span>
          <span class="location-context">${context}</span>
        `;

        item.addEventListener('click', () => {
          selectLocation(result);
        });

        elements.suggestions.appendChild(item);
      });

      elements.suggestions.classList.add('show');
    }

    /**
     * Build context string from address components
     */
    function buildLocationContext(address) {
      if (!address) return '';

      const parts = [];
      if (address.city || address.town) parts.push(address.city || address.town);
      if (address.state) parts.push(address.state);

      return parts.join(', ');
    }

    /**
     * Select location from search results
     */
    function selectLocation(result) {
      elements.suggestions.classList.remove('show');
      elements.searchInput.value = result.name || '';

      currentLocation = result;
      setMarkerLocation(parseFloat(result.lat), parseFloat(result.lon));
      populateFormFields(result);
    }

    /**
     * Set marker on map at given coordinates
     */
    function setMarkerLocation(lat, lng) {
      const formInputs = getAddressFormInputs();
      if (marker) {
        map.removeLayer(marker);
      }

      marker = L.marker([lat, lng], {
        icon: L.icon({
          iconUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-icon.png',
          iconSize: [25, 41],
          iconAnchor: [12, 41],
          popupAnchor: [1, -34],
        }),
      }).addTo(map);

      map.setView([lat, lng], Math.max(13, map.getZoom()));

      // update hidden form lat/lng
      if (formInputs.latInput) formInputs.latInput.value = String(lat);
      if (formInputs.lngInput) formInputs.lngInput.value = String(lng);
      // Reverse geocode to get address
      reverseGeocode(lat, lng);
    }

    /**
     * Reverse geocode coordinates to get address
     */
    async function reverseGeocode(lat, lng) {
      try {
        const params = new URLSearchParams({
          action: 'reverse',
          lat,
          lon: lng,
          format: 'json',
          addressdetails: 1,
          zoom: 18,
          email: CONFIG.nominatim.email,
        });

        const response = await fetch(
          `${CONFIG.nominatim.reverseUrl}?${params}`
        );

        if (!response.ok) throw new Error('Reverse geocode error');

        const result = await response.json();
        currentLocation = result;

        updateMapInfo(result);
        populateFormFields(result);
      } catch (error) {
        console.error('Reverse geocode error:', error);
      }
    }

    /**
     * Update map info display
     */
    function updateMapInfo(result) {
      const address = result.address || {};
      const name = result.name || address.city || address.town || 'Selected Location';

      elements.mapInfoName.textContent = name;

      const parts = [];
      if (address.road) parts.push(address.road);
      if (address.suburb) parts.push(address.suburb);
      if (address.city || address.town) parts.push(address.city || address.town);

      elements.mapInfoAddress.textContent = parts.join(', ') || 'Location selected';
      elements.mapInfo.classList.add('show');
    }

    /**
     * Build intelligent street address from Nominatim response
     * Prioritizes exact street-level details over generic place names
     */
    function buildStreetAddress(address, displayName) {
      const streetParts = [];

      // Prioritize house number + road (most specific)
      if (address.house_number && address.road) {
        streetParts.push(address.house_number);
        streetParts.push(address.road);
      } else if (address.road) {
        // Just road if no house number
        streetParts.push(address.road);
      }

      // Add fine subdivision (barangay-level)
      if (address.suburb) {
        streetParts.push(address.suburb);
      } else if (address.neighbourhood) {
        streetParts.push(address.neighbourhood);
      }

      // If we got house number + road + suburb, we have good specificity
      if (streetParts.length >= 2) {
        return streetParts.join(', ');
      }

      // Fallback: use first 2-3 parts of display_name (most specific portion)
      if (displayName) {
        const parts = displayName.split(',').map(p => p.trim());
        // Take first 2-3 parts, skip generic city/country at end
        if (parts.length >= 2) {
          return parts.slice(0, Math.min(3, parts.length - 1)).join(', ');
        }
      }

      // Last resort: just the parts we have
      return streetParts.join(', ');
    }

    /**
     * Populate form fields from location data
     * Prefers specific address details over generic place names
     */
    function populateFormFields(result) {
      const formInputs = getAddressFormInputs();
      const address = result.address || {};

      // Street / Barangay - use intelligent priority logic
      if (formInputs.streetInput) {
        const streetAddr = buildStreetAddress(address, result.display_name);
        if (streetAddr) {
          formInputs.streetInput.value = streetAddr;
        }
      }

      // City / Municipality - prefer city > town > village > municipality
      if (formInputs.cityInput) {
        const city = address.city || address.town || address.village || address.municipality || '';
        if (city) formInputs.cityInput.value = city;
      }

      // Province / State
      if (formInputs.provinceInput) {
        const province = address.state || address.county || '';
        if (province) formInputs.provinceInput.value = province;
      }

      // ZIP Code
      if (formInputs.zipInput) {
        const zip = address.postcode || '';
        if (zip) formInputs.zipInput.value = zip;
      }

      // Latitude / Longitude - use the PINNED coordinates, not cached values
      if (formInputs.latInput && result.lat) {
        formInputs.latInput.value = String(result.lat);
      }
      if (formInputs.lngInput && result.lon) {
        formInputs.lngInput.value = String(result.lon);
      }

      // If reverse geocode did not provide ZIP, try a ZIP-only lookup from form text.
      if ((!address.postcode || !String(address.postcode).trim()) && formInputs.zipInput && !formInputs.zipInput.value.trim()) {
        scheduleZipLookup(true);
      }
    }

    function buildZipLookupQuery() {
      const formInputs = getAddressFormInputs();
      const street = formInputs.streetInput?.value.trim() || '';
      const city = formInputs.cityInput?.value.trim() || '';
      const province = formInputs.provinceInput?.value.trim() || '';

      if (!city || city.length < 2) return '';

      const parts = [street, city, province, 'Philippines'].filter(Boolean);
      return parts.join(', ');
    }

    async function lookupZipCodeOnly() {
      const formInputs = getAddressFormInputs();
      if (!formInputs.zipInput) return;

      const query = buildZipLookupQuery();
      if (!query) return;

      const requestId = ++zipLookupRequestId;

      try {
        const params = new URLSearchParams({
          action: 'search',
          q: query,
          countrycodes: CONFIG.nominatim.countryCode,
          format: 'json',
          limit: 5,
          addressdetails: 1,
          email: CONFIG.nominatim.email,
        });

        const controller = new AbortController();
        const timeout = setTimeout(() => controller.abort(), CONFIG.nominatim.timeout);
        const response = await fetch(`${CONFIG.nominatim.baseUrl}?${params}`, { signal: controller.signal });
        clearTimeout(timeout);

        if (!response.ok) return;
        const results = await response.json();
        if (!Array.isArray(results) || results.length === 0) return;

        // Ignore stale responses from older requests.
        if (requestId !== zipLookupRequestId) return;

        let zip = '';
        for (const item of results) {
          const candidate = item?.address?.postcode;
          if (candidate && String(candidate).trim()) {
            zip = String(candidate).trim();
            break;
          }
        }

        if (zip && formInputs.zipInput && !formInputs.zipInput.value.trim()) {
          formInputs.zipInput.value = zip;
        }
      } catch (error) {
        console.error('ZIP lookup error:', error);
      }
    }

    function scheduleZipLookup(force) {
      const formInputs = getAddressFormInputs();
      if (!formInputs.zipInput) return;

      if (!force && formInputs.zipInput.value.trim()) {
        return;
      }

      clearTimeout(zipLookupTimeout);
      zipLookupTimeout = setTimeout(() => {
        lookupZipCodeOnly();
      }, 450);
    }

    function bindZipAutofillListeners() {
      const formInputs = getAddressFormInputs();
      if (!formInputs) return;

      const maybeLookup = () => scheduleZipLookup(false);

      if (formInputs.streetInput && !formInputs.streetInput.dataset.zipAutoBound) {
        formInputs.streetInput.addEventListener('input', maybeLookup);
        formInputs.streetInput.addEventListener('blur', maybeLookup);
        formInputs.streetInput.dataset.zipAutoBound = '1';
      }

      if (formInputs.cityInput && !formInputs.cityInput.dataset.zipAutoBound) {
        formInputs.cityInput.addEventListener('input', maybeLookup);
        formInputs.cityInput.addEventListener('blur', maybeLookup);
        formInputs.cityInput.dataset.zipAutoBound = '1';
      }

      if (formInputs.provinceInput && !formInputs.provinceInput.dataset.zipAutoBound) {
        formInputs.provinceInput.addEventListener('input', maybeLookup);
        formInputs.provinceInput.addEventListener('blur', maybeLookup);
        formInputs.provinceInput.dataset.zipAutoBound = '1';
      }
    }

    /**
     * Handle search input with debounce
     */
    elements.searchInput.addEventListener('input', (e) => {
      clearTimeout(searchTimeout);
      searchTimeout = setTimeout(() => {
        searchAddress(e.target.value);
      }, 300);
    });

    /**
     * Handle "Use Current Location" button click
     */
    if (elements.useCurrentLocationBtn) {
      elements.useCurrentLocationBtn.addEventListener('click', (e) => {
        e.preventDefault();
        useCurrentLocation();
      });
    }

    /**
     * Pre-fill map when editing an existing address (geocode from form fields)
     */
    function prefillMapFromFormFields() {
      const formInputs = getAddressFormInputs();
      const street = formInputs.streetInput?.value.trim();
      const city = formInputs.cityInput?.value.trim();
      const province = formInputs.provinceInput?.value.trim();
      const zip = formInputs.zipInput?.value.trim();

      if (street || city || province) {
        // Build a search query from available fields with ZIP and Philippines for precision
        const query = [street, city, province, zip, 'Philippines'].filter(Boolean).join(', ');
        if (query.length > 3) {
          // Search and jump to first result
          geocodeAndPin(query);
        }
      }
    }

    /**
     * Geocode a text query and pin the first result (uses same-origin proxy)
     */
    async function geocodeAndPin(query) {
      if (!query || !query.trim()) return null;
      try {
        const params = new URLSearchParams({ action: 'search', q: query, countrycodes: CONFIG.nominatim.countryCode, format: 'json', limit: 1, addressdetails: 1 });
        const controller = new AbortController();
        const timeout = setTimeout(() => controller.abort(), CONFIG.nominatim.timeout);
        const resp = await fetch(`${CONFIG.nominatim.baseUrl}?${params}`, { signal: controller.signal });
        clearTimeout(timeout);
        if (!resp.ok) throw new Error('Geocode failed');
        const results = await resp.json();
        if (!Array.isArray(results) || results.length === 0) return null;
        const first = results[0];
        if (!mapInitialized) showAddressLookup();
        setMarkerLocation(parseFloat(first.lat), parseFloat(first.lon));
        populateFormFields(first);
        return first;
      } catch (err) {
        console.error('geocodeAndPin error', err);
        return null;
      }
    }

    function showAddressLookup() {
      const section = document.getElementById('addressLookupSection');
      if (!section) return;
      bindZipAutofillListeners();
      section.classList.remove('hidden');
      if (!mapInitialized) {
        initMap();
      }
      setTimeout(() => {
        if (map && typeof map.invalidateSize === 'function') {
          map.invalidateSize();
        }
      }, 50);
      setTimeout(prefillMapFromFormFields, 200);
    }

    function hideAddressLookup() {
      const section = document.getElementById('addressLookupSection');
      if (!section) return;
      section.classList.add('hidden');
    }

    /**
     * Get user's current location using browser Geolocation API
     * and reverse-geocode to fill the address form
     */
    async function useCurrentLocation() {
      const btn = elements.useCurrentLocationBtn;
      if (!btn) return;

      // Check if geolocation is available
      if (!navigator.geolocation) {
        alert('Geolocation is not supported by your browser. Please search for your address manually.');
        return;
      }

      // Show loading state
      const originalText = btn.textContent;
      btn.disabled = true;
      btn.textContent = '⏳ Getting location...';

      try {
        // Request current position with high accuracy
        const position = await new Promise((resolve, reject) => {
          navigator.geolocation.getCurrentPosition(resolve, reject, {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 0,
          });
        });

        const { latitude, longitude } = position.coords;

        // Show the address lookup section if not already visible
        if (!mapInitialized) {
          showAddressLookup();
        } else {
          const section = document.getElementById('addressLookupSection');
          if (section) section.classList.remove('hidden');
        }

        // Pin location on map and reverse-geocode
        setMarkerLocation(latitude, longitude);
        await reverseGeocode(latitude, longitude);

        // Update button
        btn.textContent = '✓ Location found!';
        setTimeout(() => {
          btn.textContent = originalText;
          btn.disabled = false;
        }, 2000);
      } catch (error) {
        console.error('Geolocation error:', error);

        let errorMsg = 'Could not get your location. ';
        if (error.code === 1) {
          errorMsg += 'Please enable location permissions for this site.';
        } else if (error.code === 2) {
          errorMsg += 'Location service is unavailable. Please try again.';
        } else if (error.code === 3) {
          errorMsg += 'Location request timed out. Please try again.';
        } else {
          errorMsg += 'Please search for your address manually.';
        }

        alert(errorMsg);
        btn.textContent = originalText;
        btn.disabled = false;
      }
    }

    window.__bejShowAddressLookup = showAddressLookup;
    window.__bejHideAddressLookup = hideAddressLookup;
    // Expose helpers so outer form actions can trigger map updates.
    if (typeof geocodeAndPin === 'function') window.__bejGeocodeAndPin = geocodeAndPin;
    window.__bejPinByCoords = function (lat, lng) {
      const nLat = parseFloat(lat);
      const nLng = parseFloat(lng);
      if (Number.isNaN(nLat) || Number.isNaN(nLng)) return false;
      if (!mapInitialized) showAddressLookup();
      setMarkerLocation(nLat, nLng);
      return true;
    };
    window.__bejReverseGeocodeAndFill = async function (lat, lng) {
      const nLat = parseFloat(lat);
      const nLng = parseFloat(lng);
      if (Number.isNaN(nLat) || Number.isNaN(nLng)) return false;
      if (!mapInitialized) showAddressLookup();
      await reverseGeocode(nLat, nLng);
      return true;
    };

    /**
     * Close suggestions when clicking outside
     */
    document.addEventListener('click', (e) => {
      if (!e.target.closest('.addr-search-wrapper')) {
        elements.suggestions.classList.remove('show');
      }
    });

    /**
     * Initialize on DOM ready
     */
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', function() {
        // Map is initialized only after the user opens the address form.
      });
    } else {
      // Map is initialized only after the user opens the address form.
    }
  })();
</script>


        <!-- Add/Edit Address Form (hidden by default) -->
        <div id="addressForm" style="display:none;background:var(--blush);border:1px solid var(--border);border-radius:var(--r-lg);padding:20px;margin-bottom:16px">
          <h4 style="font-family:var(--fd);font-size:.95rem;font-weight:600;color:var(--dark);margin-bottom:16px" id="addrFormTitle">New Address</h4>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px">
            <div>
              <label class="flabel">Full Name</label>
              <input class="finput" id="addrName" placeholder="e.g. Maria Santos">
            </div>
            <div>
              <label class="flabel">Phone</label>
              <input class="finput" id="addrPhone" placeholder="09XX XXX XXXX" inputmode="numeric" oninput="this.value=this.value.replace(/[^0-9+\s]/g,'')">
            </div>
          </div>
          <div style="margin-bottom:12px">
            <label class="flabel">Street / Barangay</label>
            <input class="finput" id="addrStreet" placeholder="House no., Street, Barangay">
          </div>
          <div style="margin-bottom:12px">
            <label class="flabel">City / Municipality</label>
            <input class="finput" id="addrCity" placeholder="City / Municipality" oninput="this.value=this.value.replace(/[^a-zA-Z\s\-\.]/g,'')">
          </div>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px">
            <div>
              <label class="flabel">Province</label>
              <input class="finput" id="addrProvince" placeholder="Province" oninput="this.value=this.value.replace(/[^a-zA-Z\\s\\-\\.]/g,'')">
            </div>
            <div>
              <label class="flabel">ZIP Code</label>
              <input class="finput" id="addrZip" placeholder="0000" inputmode="numeric" oninput="this.value=this.value.replace(/[^0-9]/g,'').slice(0,10)">
            </div>
          </div>
          <input type="hidden" id="addrLat" value="">
          <input type="hidden" id="addrLng" value="">
          <label style="display:flex;align-items:center;gap:8px;font-size:.82rem;color:var(--dark);margin-bottom:16px;cursor:pointer">
            <input type="checkbox" id="addrDefault" style="accent-color:var(--rose)"> Set as default address
          </label>
          <div style="display:flex;gap:10px;align-items:center">
            <div style="flex:1"></div>
            <button class="btn btn-primary btn-sm" onclick="saveAddress()">Save Address</button>
            <button class="btn btn-ghost btn-sm" onclick="hideAddressForm()">Cancel</button>
          </div>
        </div>

        <div id="addressList" style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
          <p class="muted t-sm">Loading…</p>
        </div>
      </div>

      <!-- NOTIFICATIONS PANEL -->
      <div id="panel-notifs" class="acpanel hidden">
        <div class="panel-hd"><h2>Notification Preferences</h2></div>
        <div id="notifList" style="display:flex;flex-direction:column"></div>
      </div>

      <div id="panel-settings" class="acpanel hidden">
        <div class="panel-hd"><h2>Account Settings</h2></div>

        <!-- Change Password -->
        <div style="background:var(--white);border:1px solid var(--border);border-radius:var(--r-lg);padding:28px;margin-bottom:16px">
          <h3 style="font-family:var(--fd);font-size:1rem;font-weight:600;color:var(--dark);margin-bottom:20px">Change Password</h3>
          <form id="pwForm" method="post" action="profile_settings.php">
                        <?php echo csrf_token_field(); ?>
            <input type="hidden" name="action" value="change_password">
            <div class="fg">
              <label class="flabel">Current Password</label>
              <input type="password" id="currPass" name="current_password" class="finput" placeholder="Enter current password">
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px">
              <div class="fg" style="margin-bottom:0">
                <label class="flabel">New Password</label>
                <input type="password" id="newPass" name="new_password" class="finput" placeholder="New password">
              </div>
              <div class="fg" style="margin-bottom:0">
                <label class="flabel">Confirm New Password</label>
                <input type="password" id="confPass" name="confirm_password" class="finput" placeholder="Re-enter new password">
              </div>
            </div>
            <button type="button" class="btn btn-ghost btn-sm" style="border:1.5px solid var(--border);font-weight:600;letter-spacing:.06em;text-transform:uppercase;font-size:.72rem" onclick="savePassword()">Update Password</button>
          </form>
        </div>


      </div>

    </div><!-- /panels -->
  </div>

</div></div>
<div class="js-footer"></div>
</div></div>



<script>
let editing = false;
let userData = null;

function savePassword() {
  const curr = document.getElementById('currPass').value.trim();
  const nw   = document.getElementById('newPass').value.trim();
  const conf = document.getElementById('confPass').value.trim();
  if (!curr || !nw || !conf) { toast('Please fill in all password fields.', 'warn'); return; }
  if (nw.length < 6)          { toast('New password must be at least 6 characters.', 'warn'); return; }
  if (nw !== conf)             { toast('Passwords do not match.', 'warn'); return; }
  const form = document.getElementById('pwForm');
  if (form) form.submit();
}


function showPanel(name, el) {
  document.querySelectorAll('[id^="panel-"]').forEach(p => p.classList.add('hidden'));
  document.getElementById(`panel-${name}`).classList.remove('hidden');
  document.querySelectorAll('.acnav-item').forEach(i => i.classList.remove('active'));
  if (el) el.classList.add('active');

  // Load data on demand
  if (name === 'orders')    loadRecentOrders();
  if (name === 'wishlist')  loadWishSnap();
  if (name === 'addresses') loadAddresses();
}

/* ── PROFILE ─── */
function populateProfile(user) {
  if (!user) return;
  userData = user;
  const fullName = `${user.first_name || ''} ${user.last_name || ''}`.trim() || 'Guest Customer';
  const init     = (user.first_name?.[0] || 'G').toUpperCase();

  const navAv = document.getElementById('navAvatar');
  if (navAv) {
    if (user.avatar_url) {
      navAv.innerHTML = '';
      const navImg = document.createElement('img');
      navImg.src = user.avatar_url;
      navImg.alt = fullName;
      navImg.style.width = '100%';
      navImg.style.height = '100%';
      navImg.style.objectFit = 'cover';
      navImg.style.borderRadius = '50%';
      navAv.appendChild(navImg);
    } else {
      navAv.textContent = init;
    }
  }
  document.getElementById('navName').textContent         = fullName;
  document.getElementById('navMeta').textContent         = `${user.email || ''}`;
  const profAv = document.getElementById('profileAvatar');
  if (profAv) {
    if (user.avatar_url) {
      profAv.innerHTML = '';
      const profImg = document.createElement('img');
      profImg.src = user.avatar_url;
      profImg.alt = fullName;
      profImg.style.width = '100%';
      profImg.style.height = '100%';
      profImg.style.objectFit = 'cover';
      profImg.style.borderRadius = '50%';
      profAv.appendChild(profImg);
    } else {
      profAv.textContent = init;
    }
  }
  document.getElementById('profileDisplayName').textContent = fullName;
  const created = user.created_at ? new Date(user.created_at) : null;
  const memberSince = user.member_since || (created ? created.toLocaleDateString('en-PH', { month:'short', year:'numeric' }) : '—');
  document.getElementById('profileMeta').textContent     = `Member since ${memberSince}`;

  const set = (id, val) => { const el = document.getElementById(id); if (el) el.value = val || ''; };
  set('pfFirst',    user.first_name);
  set('pfLast',     user.last_name);
    set('pfEmail',    user.email);
  set('pfPhone',    user.phone);
  set('pfBirthday', user.birthday);
  set('pfCity',     user.city);
  if (user.gender) document.getElementById('pfGender').value = user.gender;
}

function toggleEdit() {
  editing = !editing;
  const fields = ['pfFirst','pfLast','pfPhone','pfBirthday','pfEmail','pfCity'];
  fields.forEach(id => { const el = document.getElementById(id); if (el) el.readOnly = !editing; });
  const genderEl = document.getElementById('pfGender');
  if (genderEl) genderEl.disabled = !editing;
  document.getElementById('editBtn').textContent = editing ? '✕ Cancel' : '✏️ Edit';
  document.getElementById('saveRow').classList.toggle('hidden', !editing);
}

function saveProfile() {
  const form = document.getElementById('profileForm');
  if (form) form.submit();
}

/* ── QUICK STATS ─── */
async function loadQuickStats() {
  const statDefs = [
    { key:'pending',    icon:'🕐', label:'To Pay',     href:'order_history.php?status=pending'    },
    { key:'processing', icon:'📦', label:'To Ship',    href:'order_history.php?status=processing' },
    { key:'shipped',    icon:'🚚', label:'To Receive', href:'order_history.php?status=shipped'    },
    { key:'to_rate',    icon:'⭐', label:'To Rate',    href:'order_history.php?status=delivered'  },
  ];
  let counts = { pending:0, processing:0, shipped:0, to_rate:0 };
  const orders = Array.isArray(window.__ORDERS__) ? window.__ORDERS__ : [];
  orders.forEach(o => { if (counts[o.status] !== undefined) counts[o.status]++; });
  counts.to_rate = Number(window.__TO_RATE_COUNT__ || 0);

  document.getElementById('quickStats').innerHTML = statDefs.map(s => `
    <a href="${s.href}" style="display:block">
      <div style="background:var(--white);border-radius:var(--r-lg);border:1px solid var(--border);padding:16px 18px;text-align:center;transition:all var(--tr)" onmouseover="this.style.boxShadow='var(--sh-md)'" onmouseout="this.style.boxShadow=''">
        <div style="font-size:1.5rem;margin-bottom:6px">${escapeHtml(s.icon)}</div>
        <div style="font-family:var(--fd);font-size:1.6rem;font-weight:700;color:var(--dark)">${counts[s.key]}</div>
        <div class="t-xs muted">${s.label}</div>
      </div>
    </a>`).join('');
}

/* ── RECENT ORDERS ─── */
async function loadRecentOrders() {
  const el = document.getElementById('recentOrders');
  try {
    const orders = (Array.isArray(window.__ORDERS__) ? window.__ORDERS__ : []).slice(0, 5);
    if (!orders.length) { el.innerHTML = `<p class="muted t-sm">No orders yet. <a href="index.php" class="text-rose">Start shopping</a></p>`; return; }
    const badgeMap = { pending:'b-pending', processing:'b-processing', shipped:'b-shipped', delivered:'b-delivered', cancelled:'b-cancelled' };
    el.innerHTML = orders.map(o => `
      <div style="display:flex;align-items:center;gap:14px;padding:14px;background:var(--blush);border-radius:var(--r-md);cursor:pointer" onclick="window.location='order_history.php'">
        <div class="iph sm">${escapeHtml(o.emoji || '💍')}</div>
        <div style="flex:1">
          <div style="font-size:.86rem;font-weight:600">${escapeHtml(o.product || o.name || '')}</div>
          <div class="t-xs muted">#${o.id} · ${o.date || ''}</div>
        </div>
        <div style="text-align:right">
          <div style="font-family:var(--fd);font-weight:600;font-size:.9rem;margin-bottom:4px">₱${(o.price||0).toLocaleString()}</div>
          <span class="badge ${badgeMap[o.status]||''}">${escapeHtml(o.status)}</span>
        </div>
      </div>`).join('');
  } catch { el.innerHTML = ''; }
}

/* ── WISHLIST SNAP ─── */
function loadWishSnap() {
  const el   = document.getElementById('wishSnap');
  if (!el) return;

  // This panel is rendered server-side from DB data. Keep that markup intact.
  if (el.dataset.serverRendered === '1') return;

  const snap = [];  // optional client-side snapshot source
  if (!snap.length) {
    el.innerHTML = `<p class="muted t-sm">No wishlist items yet. <a href="product-list.php" class="text-rose">Browse</a></p>`;
    return;
  }
  el.innerHTML = snap.map(p => productCard(p)).join('');
}

/* ── ADDRESSES ─── */
async function addressApi(payload) {
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
  const res = await fetch('profile_addresses.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      ...(csrfToken ? { 'X-CSRF-Token': csrfToken } : {})
    },
    body: JSON.stringify(payload || {})
  });
  const json = await res.json().catch(() => null);
  if (!res.ok || !json?.ok) throw new Error(json?.error || 'Request failed');
  return json.data || [];
}

async function addressListApi() {
  const res = await fetch('profile_addresses.php', { method:'GET' });
  const json = await res.json().catch(() => null);
  if (!res.ok || !json?.ok) throw new Error(json?.error || 'Request failed');
  return json.data || [];
}

async function loadAddresses() {
  const el = document.getElementById('addressList');
  try {
    const addresses = await addressListApi();
    _addresses = addresses || [];
    if (!_addresses.length) { el.innerHTML = `<p class="muted t-sm">No saved addresses yet.</p>`; return; }
    renderAddresses();
  } catch { el.innerHTML = ''; }
}

function renderAddresses() {
  const el = document.getElementById('addressList');
  if (!_addresses.length) { el.innerHTML = `<p class="muted t-sm">No saved addresses yet.</p>`; return; }
  el.innerHTML = _addresses.map((a,i) => `
    <div style="background:var(--blush);border-radius:var(--r-md);padding:16px;border:${a.is_default?'2px solid var(--rose)':'1px solid var(--border)'};position:relative">
      ${a.is_default ? `<span class="badge b-delivered" style="position:absolute;top:12px;right:12px">Default</span>` : ''}
      <div style="font-size:.84rem;font-weight:600;margin-bottom:4px">${escapeHtml(a.name)}</div>
      <div class="t-sm muted">${escapeHtml(a.street)}<br>${escapeHtml([a.city, a.province, a.zip].filter(Boolean).join(', '))}<br>${escapeHtml(a.phone || '')}</div>
      <div class="flex g-2 mt4">
        <button class="btn btn-ghost btn-sm" onclick="editAddress(${i})">Edit</button>
        ${!a.is_default ? `<button class="btn btn-ghost btn-sm" onclick="setDefaultAddress(${i})">Set Default</button>` : ''}
        <button class="btn btn-ghost btn-sm" style="color:var(--danger)" onclick="deleteAddress(${i})">Delete</button>
      </div>
    </div>`).join('');
}

function setDefaultAddress(idx) {
  const a = _addresses[idx];
  if (!a?.id) return;
  addressApi({ action:'set_default', id: a.id })
    .then(data => { _addresses = data || []; renderAddresses(); toast('Default address updated!', 'ok'); })
    .catch(() => toast('Could not update default address.', 'err'));
}

function deleteAddress(idx) {
  if (!confirm('Remove this address?')) return;
  const a = _addresses[idx];
  if (!a?.id) return;
  addressApi({ action:'delete', id: a.id })
    .then(data => { _addresses = data || []; renderAddresses(); toast('Address removed.', 'ok'); })
    .catch(() => toast('Could not delete address.', 'err'));
}

function handlePhotoUpload(e) {
  const file = e.target.files[0];
  if (!file) return;
  if (!file.type.startsWith('image/')) { toast('Please select an image file.', 'warn'); return; }
  if (file.size > 5 * 1024 * 1024)    { toast('Image must be under 5MB.', 'warn'); return; }
  const formData = new FormData();
  formData.append('photo', file);
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
  if (csrfToken) formData.append('csrf_token', csrfToken);
  fetch('profile_photo.php', { method:'POST', body: formData })
    .then(r => r.json())
    .then(res => {
      if (!res?.ok || !res.url) { throw new Error(res?.error || 'Upload failed'); }
      const av = document.getElementById('profileAvatar');
      if (av) {
        av.innerHTML = '';
        const img = document.createElement('img');
        img.src = res.url;
        img.alt = 'Profile photo';
        img.style.width = '100%';
        img.style.height = '100%';
        img.style.objectFit = 'cover';
        img.style.borderRadius = '50%';
        av.appendChild(img);
      }
      const navAv = document.getElementById('navAvatar');
      if (navAv) {
        navAv.innerHTML = '';
        const navImg = document.createElement('img');
        navImg.src = res.url;
        navImg.alt = 'Profile photo';
        navImg.style.width = '100%';
        navImg.style.height = '100%';
        navImg.style.objectFit = 'cover';
        navImg.style.borderRadius = '50%';
        navAv.appendChild(navImg);
      }
      toast('Photo updated!', 'ok');
    })
    .catch(() => {
      toast('Could not upload photo. Please try again.', 'err');
    });
}

let _addresses = [];
let _editingAddrIdx = null;

function showAddressForm(idx = null) {
  _editingAddrIdx = idx;
  const form = document.getElementById('addressForm');
  document.getElementById('addrFormTitle').textContent = idx !== null ? 'Edit Address' : 'New Address';
  if (idx !== null && _addresses[idx]) {
    const a = _addresses[idx];
    document.getElementById('addrName').value     = a.name    || '';
    document.getElementById('addrPhone').value    = a.phone   || '';
    document.getElementById('addrStreet').value   = a.street  || '';
    document.getElementById('addrCity').value     = a.city    || '';
    document.getElementById('addrProvince').value = a.province || '';
    document.getElementById('addrZip').value      = a.zip     || '';
    document.getElementById('addrLat').value      = a.latitude ?? '';
    document.getElementById('addrLng').value      = a.longitude ?? '';
    document.getElementById('addrDefault').checked = !!a.is_default;
  } else {
    ['addrName','addrPhone','addrStreet','addrCity','addrProvince','addrZip','addrLat','addrLng'].forEach(id => document.getElementById(id).value = '');
    document.getElementById('addrDefault').checked = false;
  }
  form.style.display = 'block';
  form.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  if (window.__bejShowAddressLookup) {
    window.__bejShowAddressLookup();
  }
}

function hideAddressForm() {
  document.getElementById('addressForm').style.display = 'none';
  _editingAddrIdx = null;
  if (window.__bejHideAddressLookup) {
    window.__bejHideAddressLookup();
  }
}



async function saveAddress() {
  const name     = document.getElementById('addrName').value.trim();
  const phone    = document.getElementById('addrPhone').value.trim();
  const street   = document.getElementById('addrStreet').value.trim();
  const city     = document.getElementById('addrCity').value.trim();
  const province = document.getElementById('addrProvince').value.trim();
  const zip      = document.getElementById('addrZip').value.trim();
  const is_default = document.getElementById('addrDefault').checked;
  const lat = (document.getElementById('addrLat') && document.getElementById('addrLat').value) ? document.getElementById('addrLat').value.trim() : '';
  const lng = (document.getElementById('addrLng') && document.getElementById('addrLng').value) ? document.getElementById('addrLng').value.trim() : '';

  if (!name || !street || !city) {
    toast('Please fill in all required fields.', 'warn'); return;
  }

  try {
    if (_editingAddrIdx !== null && _addresses[_editingAddrIdx]?.id) {
      await addressApi({ action:'update', id:_addresses[_editingAddrIdx].id, name, phone, street, city, province, zip, is_default, latitude: lat || null, longitude: lng || null });
    } else {
      await addressApi({ action:'create', name, phone, street, city, province, zip, is_default, latitude: lat || null, longitude: lng || null });
    }
    hideAddressForm();
    toast('Address saved!', 'ok');
    loadAddresses();
  } catch {
    toast('Could not save address. Please try again.', 'err');
  }
}

function editAddress(idx) {
  showAddressForm(idx);
}

/* ── NOTIFICATIONS ─── */
async function notifApi(action, method = 'GET', payload = null) {
  const headers = {};
  if (method === 'POST') {
    headers['Content-Type'] = 'application/json';
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    if (csrf) headers['X-CSRF-Token'] = csrf;
  }
  const res = await fetch('notifications_api.php?action=' + encodeURIComponent(action), {
    method,
    headers,
    credentials: 'same-origin',
    body: payload && method === 'POST' ? JSON.stringify(payload) : null,
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data.error || 'Notification request failed');
  return data;
}

async function buildNotifPanel() {
  const prefs = [
    { key:'order_updates',  label:'Order Updates',      desc:'Shipping and delivery notifications', defaultOn:true  },
    { key:'promotions',     label:'Promotions & Deals', desc:'Exclusive offers and new arrivals',   defaultOn:true  },
    { key:'wishlist',       label:'Wishlist Reminders', desc:'When saved items go on sale',         defaultOn:false },
  ];

  let current = {};
  try {
    const res = await notifApi('prefs', 'GET');
    current = res.prefs || {};
  } catch (_) {}

  document.getElementById('notifList').innerHTML = prefs.map(p => `
    <div style="display:flex;justify-content:space-between;align-items:center;padding:14px 0;border-bottom:1px solid var(--border)">
      <div>
        <div style="font-size:.86rem;font-weight:600">${p.label}</div>
        <div class="t-xs muted">${p.desc}</div>
      </div>
      <label class="toggle-wrap">
        <input type="checkbox" data-pref="${p.key}" ${(current[p.key] ?? p.defaultOn)?'checked':''}>
        <span class="toggle-track"></span>
        <span class="toggle-thumb"></span>
      </label>
    </div>`).join('');

  document.querySelectorAll('#notifList input[type="checkbox"][data-pref]').forEach((el) => {
    el.addEventListener('change', async () => {
      const payload = {};
      document.querySelectorAll('#notifList input[type="checkbox"][data-pref]').forEach((cb) => {
        payload[cb.dataset.pref] = cb.checked ? 1 : 0;
      });
      try {
        await notifApi('prefs', 'POST', payload);
        toast('Preference saved', 'ok');
      } catch (_) {
        toast('Could not save preference.', 'err');
      }
    });
  });
}

/* ── SIGN OUT ─── */
async function doSignOut() {
  if (!confirm('Sign out?')) return;
  await API.logout().catch(() => {});
  window.location.href = 'login.php';
}

/* ── INIT ─── */
document.addEventListener('DOMContentLoaded', async () => {
  await buildNotifPanel();
  loadQuickStats();

  try {
    const user = await API.getMe();
    populateProfile(user);
  } catch {
    // Not logged in — show empty fields, let user log in
    document.getElementById('navName').textContent = 'Guest Customer';
    document.getElementById('navMeta').textContent  = 'Not signed in';
    document.getElementById('profileDisplayName').textContent = 'Guest Customer';
  }
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