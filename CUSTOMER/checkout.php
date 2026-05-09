<?php
require_once __DIR__ . '/inc.php';
require_once __DIR__ . '/api/paymongo.php';
require_once __DIR__ . '/promotion_helpers.php';
$checkoutErr = isset($_GET['err']) ? (string) $_GET['err'] : '';
$checkoutCancelled = isset($_GET['cancelled']);
if (!current_user_id()) {
  header('Location: login.php?redirect=' . urlencode('checkout.php'));
  exit;
}
$cart = get_customer_cart();
$user = current_user();
$pdo = db();
$availableVouchers = bejewelry_fetch_active_promotions($pdo);
if (empty($cart)) {
  header('Location: cart.php');
  exit;
}

// Default saved address (prefill checkout fields)
$addr = null;
try {
  $aStmt = db()->prepare('SELECT name, street, city, province, zip, phone FROM addresses WHERE user_id = ? ORDER BY is_default DESC, id DESC LIMIT 1');
  $aStmt->execute([current_user_id()]);
  $addr = $aStmt->fetch(PDO::FETCH_ASSOC) ?: null;
  if (is_array($addr)) {
    $addr = bejewelry_decrypt_address_private_fields($addr);
  }
} catch (Exception $e) {
  $addr = null;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<?= csrf_meta_tag() ?>
<title>Checkout — Bejewelry</title>
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

/* ── VOUCHER REQUIREMENTS ─── */
.promo-req-card{margin-top:10px;border:1px solid var(--border-mid);border-radius:var(--r-md);background:linear-gradient(180deg,#fff 0%,#fff8fa 100%);padding:10px 11px}
.promo-req-head{display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:8px}
.promo-status-chip{display:inline-flex;align-items:center;padding:3px 9px;border-radius:var(--r-pill);font-size:.56rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;border:1px solid transparent}
.promo-status-chip.neutral{background:var(--blush);color:var(--muted);border-color:var(--border)}
.promo-status-chip.ok{background:#e8f9ef;color:#1e7748;border-color:#9fdfbb}
.promo-status-chip.no{background:#fff0f0;color:#b33a3a;border-color:#f1b7b7}
.promo-code-chip{font-size:.66rem;font-weight:700;letter-spacing:.06em;color:var(--rose-deep);padding:3px 8px;border-radius:var(--r-pill);background:var(--blush);border:1px solid var(--border)}
.promo-req-list{display:grid;grid-template-columns:1fr 1fr;gap:7px 10px;margin-bottom:8px}
.promo-req-item{display:flex;flex-direction:column;gap:1px;padding:6px 8px;border:1px dashed var(--border-mid);border-radius:10px;background:rgba(255,255,255,.7)}
.promo-req-item span{font-size:.58rem;letter-spacing:.12em;text-transform:uppercase;color:var(--muted-light);font-weight:700}
.promo-req-item strong{font-size:.73rem;color:var(--dark);font-weight:700;line-height:1.25}
.promo-req-msg{font-size:.71rem;line-height:1.45;color:var(--muted);padding-top:8px;border-top:1px solid var(--border)}
@media (max-width:420px){.promo-req-list{grid-template-columns:1fr}}

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
.pay-opt{padding:14px 16px;border-radius:var(--r-md);border:1.5px solid var(--border);cursor:pointer;transition:all var(--tr);display:block;margin-bottom:10px}
.pay-opt.active{border-color:var(--rose);background:var(--blush-mid)}
.pay-opt:hover{border-color:var(--rose-muted)}
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
<script>
window.__CART__ = <?= json_encode($cart, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
window.__USER__ = <?= json_encode($user, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
<?php
// Prefer dynamic shipping settings from app_settings when available
$checkoutDefaults = [
  'free_ship_threshold' => defined('FREE_SHIP_THRESHOLD') ? FREE_SHIP_THRESHOLD : 2000,
  'shipping_fee' => defined('SHIPPING_FEE') ? SHIPPING_FEE : 150,
  'paymongo_enabled' => paymongo_is_configured(),
];

// Attempt to load admin settings helper and override defaults from DB
try {
  if (file_exists(__DIR__ . '/admin/settings/_settings_db.php')) {
    require_once __DIR__ . '/admin/settings/_settings_db.php';
    $sPdo = settingsPdo();
    $dbShipping = settingsGetJson($sPdo, 'shipping', ['shipping_fee' => $checkoutDefaults['shipping_fee'], 'free_ship_threshold' => $checkoutDefaults['free_ship_threshold']]);
    $checkoutDefaults['shipping_fee'] = (float)($dbShipping['shipping_fee'] ?? $checkoutDefaults['shipping_fee']);
    $checkoutDefaults['free_ship_threshold'] = (float)($dbShipping['free_ship_threshold'] ?? $checkoutDefaults['free_ship_threshold']);
  }
} catch (Throwable $e) {
  // ignore and keep defaults
}

?>
window.__CHECKOUT__ = <?= json_encode($checkoutDefaults, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
</script>
<div id="toasts"></div>
<div class="js-header" data-active="cart" data-minimal="true"></div>

<div class="site-wrapper">
<aside class="sidebar" data-active="cart"></aside>
<div class="site-content">
<div class="main-content"><div class="container">

  <?php if ($checkoutErr !== ''): ?>
  <div class="card mb4" style="border-radius:var(--r-md);padding:14px 16px;background:#FFE8E8;border:1px solid #E0A0A0;color:#6a1010;font-size:.9rem"><?= htmlspecialchars($checkoutErr, ENT_QUOTES, 'UTF-8') ?></div>
  <?php elseif ($checkoutCancelled): ?>
  <div class="card mb4" style="border-radius:var(--r-md);padding:14px 16px;background:var(--blush);border:1px solid var(--border);font-size:.9rem;color:var(--dark)">Payment was cancelled. You can complete your order when ready.</div>
  <?php endif; ?>

  <!-- STEPS -->
  <div class="steps" id="stepsRow">
    <div class="step done"><div class="step-n">✓</div><div class="step-l">Cart</div></div>
    <div class="step active" id="step-details"><div class="step-n">2</div><div class="step-l">Details</div></div>
    <div class="step" id="step-payment"><div class="step-n">3</div><div class="step-l">Payment</div></div>
    <div class="step" id="step-confirm"><div class="step-n">4</div><div class="step-l">Confirm</div></div>
  </div>

  <div class="checkout-layout">
    <div>
      <!-- STEP 2: SHIPPING DETAILS -->
      <div id="panelDetails">
        <div class="card mb5">
          <div class="card-hd"><h3>Delivery Information</h3></div>
          <div class="card-body">
            <div class="frow">
              <div class="fg"><label class="flabel">First Name</label><input class="finput" type="text" id="firstName" placeholder="First name"></div>
              <div class="fg"><label class="flabel">Last Name</label><input class="finput" type="text" id="lastName" placeholder="Last name"></div>
            </div>
            <div class="fg"><label class="flabel">Email Address</label><input class="finput" type="email" id="email" placeholder="you@email.com"></div>
            <div class="fg"><label class="flabel">Phone Number</label><input class="finput" type="tel" id="phone" placeholder="09XX XXX XXXX" value="<?= htmlspecialchars((string)($addr['phone'] ?? $user['phone'] ?? '')) ?>" oninput="this.value = this.value.replace(/[^0-9]/g,'').slice(0,11)"></div>
            <div class="fg"><label class="flabel">Street Address</label><input class="finput" type="text" id="street" placeholder="House no., Street, Barangay" value="<?= htmlspecialchars((string)($addr['street'] ?? '')) ?>"></div>
            <div class="frow">
              <div class="fg"><label class="flabel">City / Municipality</label><input class="finput" type="text" id="city" placeholder="City" value="<?= htmlspecialchars((string)($addr['city'] ?? '')) ?>" oninput="this.value = this.value.replace(/[0-9]/g,'')"></div>
              <div class="fg"><label class="flabel">Province</label><input class="finput" type="text" id="province" placeholder="Province" value="<?= htmlspecialchars((string)($addr['province'] ?? '')) ?>" oninput="this.value = this.value.replace(/[0-9]/g,'')"></div>
            </div>
            <div class="frow">
              <div class="fg"><label class="flabel">ZIP Code</label><input class="finput" type="text" id="zip" placeholder="0000" value="<?= htmlspecialchars((string)($addr['zip'] ?? '')) ?>" oninput="this.value = this.value.replace(/[^0-9]/g,'')"></div>
              <div class="fg"><label class="flabel">Country</label><input class="finput" type="text" id="country" value="Philippines" readonly></div>
            </div>
            <button class="btn btn-primary btn-full mt4" onclick="goToPayment()">Continue to Payment</button>
          </div>
        </div>
      </div>

      <!-- STEP 3: PAYMENT -->
      <div id="panelPayment" class="hidden">
        <div class="card mb5">
          <div class="card-hd"><h3>Payment Method</h3></div>
          <div class="card-body">
            <p class="t-sm muted mb4" style="line-height:1.6">Online payments are processed securely by <strong>PayMongo</strong> (cards, GCash, Maya, and more).</p>
            <div id="payMethods">
              <label class="pay-opt" id="pay-paymongo" onclick="selectPay('paymongo')" style="<?= paymongo_is_configured() ? '' : 'opacity:.6' ?>">
                <div class="flex items-center g-4">
                  <div class="pay-radio" id="radio-paymongo"></div>
                  <div><div style="font-size:.87rem;font-weight:600">💳 Pay online (PayMongo)</div><div class="t-xs muted">GCash, Maya, credit/debit card, and other methods</div></div>
                </div>
              </label>
              <?php if (!paymongo_is_configured()): ?>
              <p class="t-xs muted mb2" style="margin-top:-4px;margin-left:32px">Configure <code>PAYMONGO_SECRET_KEY</code> in <code>api/config.php</code> to enable.</p>
              <?php endif; ?>
              <!-- Cash on Delivery option removed -->
            </div>
            <style>
              .pay-radio{width:18px;height:18px;border-radius:50%;border:2px solid var(--border-mid);flex-shrink:0;transition:all var(--tr)}
              .pay-radio.active{background:var(--rose);border-color:var(--rose)}
            </style>

            <!-- Card fields -->
            <div id="cardFields" style="margin-top:12px;display:none">
              <div class="fg"><label class="flabel">Card Number</label><input class="finput" type="text" placeholder="1234 5678 9012 3456" maxlength="19" oninput="formatCard(this)"></div>
              <div class="frow">
                <div class="fg"><label class="flabel">Expiry</label><input class="finput" type="text" placeholder="MM / YY" maxlength="7" oninput="formatExpiry(this)"></div>
                <div class="fg"><label class="flabel">CVV</label><input class="finput" type="password" placeholder="•••" maxlength="4"></div>
              </div>
              <div class="fg"><label class="flabel">Name on Card</label><input class="finput" type="text" placeholder="Full name as on card"></div>
            </div>

            <div class="flex g-3 mt4">
              <button class="btn btn-ghost btn-sm" onclick="goToDetails()">Back</button>
              <button class="btn btn-primary" style="flex:1" onclick="goToConfirm()">Review Order</button>
            </div>
          </div>
        </div>
      </div>

      <!-- STEP 4: CONFIRM -->
      <div id="panelConfirm" class="hidden">
        <div class="card mb5">
          <div class="card-hd"><h3>Order Review</h3></div>
          <div class="card-body">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px">
              <div style="background:var(--blush);border-radius:var(--r-md);padding:14px">
                <div class="t-xs muted mb2">Deliver to</div>
                <div style="font-size:.87rem;font-weight:600" id="confirmAddr"></div>
              </div>
              <div style="background:var(--blush);border-radius:var(--r-md);padding:14px">
                <div class="t-xs muted mb2">Payment</div>
                <div style="font-size:.87rem;font-weight:600" id="confirmPay"></div>
              </div>
            </div>

            <div id="confirmItems"></div>
            <div style="background:var(--blush);border-radius:var(--r-md);padding:14px;margin-top:16px">
              <div class="t-xs muted mb2">Estimated delivery</div>
              <div style="font-size:.87rem;font-weight:600" id="confirmShipping">5–7 business days</div>
            </div>
            <div class="alert alert-info mt4">ℹ️ By placing your order, you agree to our Terms of Service and Privacy Policy.</div>
            <div class="flex g-3 mt4">
              <button class="btn btn-ghost btn-sm" onclick="goToPayment()">Back</button>
              <button class="btn btn-primary btn-lg" style="flex:1" id="placeBtn" onclick="placeOrder()">Place Order</button>
            </div>
          </div>
        </div>
      </div>

      <!-- Hidden form submit (COD → place_order.php; PayMongo → paymongo_start.php) -->
      <form id="placeOrderForm" method="post" action="place_order.php" class="hidden">
          <?php echo csrf_token_field(); ?>
        <input type="hidden" name="ship_name" id="po_ship_name">
        <input type="hidden" name="ship_phone" id="po_ship_phone">
        <input type="hidden" name="ship_street" id="po_ship_street">
        <input type="hidden" name="ship_city" id="po_ship_city">
        <input type="hidden" name="ship_province" id="po_ship_province">
        <input type="hidden" name="ship_zip" id="po_ship_zip">
        <input type="hidden" name="payment_method" id="po_payment_method">
        <input type="hidden" name="shipping_fee" id="po_shipping_fee">
        <input type="hidden" name="notes" id="po_notes">
        <input type="hidden" name="promotion_id" id="po_promotion_id">
        <input type="hidden" name="promotion_code" id="po_promotion_code">
        <input type="hidden" name="promotion_discount" id="po_promotion_discount">
        <input type="hidden" name="order_total" id="po_order_total">
      </form>

      <!-- SUCCESS -->
      <div id="panelSuccess" class="hidden">
        <div class="card" style="text-align:center;padding:52px 40px">
          <div style="font-size:4rem;margin-bottom:16px">🎉</div>
          <h2 style="margin-bottom:8px;color:var(--success)">Order Placed!</h2>
          <p style="margin-bottom:6px" id="successMsg">Your order has been confirmed.</p>
          <p class="mb5">Order <strong id="successOrderNum"></strong> · Estimated delivery: 3–5 business days</p>
          <div class="flex jc g-3">
            <a href="order_history.php" class="btn btn-primary">View My Orders</a>
            <a href="index.php" class="btn btn-ghost">Continue Shopping</a>
          </div>
        </div>
      </div>
    </div>

    <!-- ORDER SUMMARY SIDEBAR -->
    <div id="checkoutSummary">
      <div class="sum-box">
        <div class="sum-title">Order Summary</div>
          <div class="fg" style="margin:12px 0 6px">
            <label class="flabel">Apply Promotion / Voucher</label>
            <select id="promoSelect" class="fselect">
              <option value="">— Select promotion or voucher —</option>
              <?php foreach ($availableVouchers as $p): ?>
                <?php
                  $type = (string)($p['type'] ?? 'percent');
                  $value = (float)($p['value'] ?? 0);
                  $minOrder = (float)($p['min_order'] ?? 0);
                  $applyTo = strtolower((string)($p['apply_to'] ?? 'all'));
                  $scopeLabel = $applyTo === 'all' ? 'All items' : ucfirst($applyTo) . ' only';
                  $discountLabel = $type === 'percent' ? (rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.') . '% off') : ('₱' . number_format($value, 2) . ' off');
                  $minLabel = $minOrder > 0 ? ('Min spend ₱' . number_format($minOrder, 0)) : 'No min spend';
                  $endAtRaw = trim((string)($p['end_at'] ?? ''));
                  $endLabel = $endAtRaw !== '' ? ('Until ' . date('M j, Y', strtotime($endAtRaw))) : 'No expiry';
                  $usedCount = (int)($p['used_count'] ?? 0);
                  $maxUses = $p['max_uses'] !== null ? (int)$p['max_uses'] : null;
                  $remainingLabel = $maxUses !== null ? ('Remaining ' . max(0, $maxUses - $usedCount)) : 'Unlimited uses';
                  $optionLabel = ($p['code'] ?? '') . ' — ' . ($p['name'] ?? '') . ' · ' . $discountLabel;
                ?>
                <option
                  value="<?= (int)$p['id'] ?>"
                  data-code="<?= htmlspecialchars($p['code'] ?? '') ?>"
                  data-name="<?= htmlspecialchars((string)($p['name'] ?? '')) ?>"
                  data-type="<?= htmlspecialchars($type) ?>"
                  data-value="<?= htmlspecialchars((string)$value) ?>"
                  data-min="<?= htmlspecialchars((string)$minOrder) ?>"
                  data-apply-to="<?= htmlspecialchars($applyTo) ?>"
                  data-end="<?= htmlspecialchars($endAtRaw) ?>"
                  data-end-label="<?= htmlspecialchars($endLabel) ?>"
                  data-min-label="<?= htmlspecialchars($minLabel) ?>"
                  data-scope-label="<?= htmlspecialchars($scopeLabel) ?>"
                  data-discount-label="<?= htmlspecialchars($discountLabel) ?>"
                  data-remaining-label="<?= htmlspecialchars($remainingLabel) ?>"
                  data-remaining="<?= htmlspecialchars((string)($maxUses !== null ? max(0, $maxUses - $usedCount) : -1)) ?>"
                ><?= htmlspecialchars($optionLabel) ?></option>
              <?php endforeach; ?>
            </select>
            <div class="t-xs muted">Choose a promotion to apply to this order.</div>
            <div class="promo-req-card" id="promoReqCard" aria-live="polite">
              <div class="promo-req-head">
                <span class="promo-status-chip neutral" id="promoReqStatus">No Selection</span>
                <span class="promo-code-chip" id="promoReqCode">Voucher</span>
              </div>
              <div class="promo-req-list">
                <div class="promo-req-item"><span>Discount</span><strong id="promoReqDiscount">—</strong></div>
                <div class="promo-req-item"><span>Min. Spend</span><strong id="promoReqMin">—</strong></div>
                <div class="promo-req-item"><span>Applies To</span><strong id="promoReqScope">—</strong></div>
                <div class="promo-req-item"><span>Validity</span><strong id="promoReqValidity">—</strong></div>
              </div>
              <div class="promo-req-msg" id="promoReqHint">Select a voucher to see requirements.</div>
            </div>
          </div>
        <div id="coItems"></div>
        <div id="coRows"></div>
        <div class="sum-total" id="coTotal"></div>
      </div>
    </div>
  </div>

</div></div>
<div class="js-footer"></div>
</div></div>

<script src="js/api.js"></script>
<script>
let selectedPay = '';
/** { cost: number, days: string } — standard shipping only (no method picker) */
let selectedShipping = { cost: 0, days: '5–7 business days' };

function checkoutCfg() {
  return window.__CHECKOUT__ || { free_ship_threshold: <?= defined('FREE_SHIP_THRESHOLD') ? (int)FREE_SHIP_THRESHOLD : 2000 ?>, shipping_fee: <?= defined('SHIPPING_FEE') ? (int)SHIPPING_FEE : 150 ?>, paymongo_enabled: <?= (function_exists('paymongo_is_configured') && paymongo_is_configured()) ? 'true' : 'false' ?> };
}

function computeDeliveryDays() {
  const province = document.getElementById('province').value.trim().toLowerCase();
  let days = '5–7 business days';
  const metro = ['metro manila', 'ncr', 'manila', 'cebu', 'cebu city'];
  const near = ['laguna', 'cavite', 'bulacan', 'rizal', 'batangas', 'bataan', 'pampanga', 'aurora', 'quezon', 'davao', 'cagayan de oro', 'iloilo'];
  if (metro.some(m => province.includes(m))) days = '3–5 business days';
  else if (province.length > 0 && !near.some(n => province.includes(n)) && !metro.some(m => province.includes(m))) days = '7–10 business days';
  return days;
}

function renderSummary() {
  const cart     = getCart();
  const subtotal = cart.reduce((a, i) => a + i.price * i.qty, 0);
  const CO = checkoutCfg();
  selectedShipping.days = computeDeliveryDays();
  selectedShipping.cost = subtotal >= CO.free_ship_threshold ? 0 : CO.shipping_fee;
  const shipping = selectedShipping.cost;

  // Compute promotion discount (if any)
  let discount = 0;
  let appliedPromo = null;
  const promoEl = document.getElementById('promoSelect');
  const promoHint = document.getElementById('promoReqHint');
  const promoStatus = document.getElementById('promoReqStatus');
  const promoCode = document.getElementById('promoReqCode');
  const promoDiscount = document.getElementById('promoReqDiscount');
  const promoMin = document.getElementById('promoReqMin');
  const promoScope = document.getElementById('promoReqScope');
  const promoValidity = document.getElementById('promoReqValidity');

  function titleCaseWord(text) {
    const t = (text || '').toString().trim();
    if (!t) return '';
    return t.charAt(0).toUpperCase() + t.slice(1);
  }

  if (promoEl && promoEl.value) {
    const opt = promoEl.selectedOptions[0];
    if (opt) {
      const type = (opt.dataset.type || '').toLowerCase();
      const val = parseFloat(opt.dataset.value || '0') || 0;
      const min = parseFloat(opt.dataset.min || '0') || 0;
      const applyTo = (opt.dataset.applyTo || 'all').toLowerCase();
      const endRaw = opt.dataset.end || '';
      const discountLabel = opt.dataset.discountLabel || (type === 'percent' ? `${val}% off` : `₱${val.toLocaleString()} off`);
      const minLabel = opt.dataset.minLabel || (min > 0 ? `Min spend ₱${Math.ceil(min).toLocaleString()}` : 'No min spend');
      const scopeLabel = opt.dataset.scopeLabel || (applyTo === 'all' ? 'All items' : `${titleCaseWord(applyTo)} only`);
      const validityLabel = opt.dataset.endLabel || (endRaw ? endRaw : 'No expiry');
      const remaining = parseInt(opt.dataset.remaining || '-1', 10);
      const remainingLabel = opt.dataset.remainingLabel || (!Number.isNaN(remaining) && remaining >= 0 ? `Remaining ${remaining}` : 'Unlimited uses');
      const codeText = (opt.dataset.code || '').trim();
      const cartMatchesScope = applyTo === 'all' || cart.some(i => ((i.cat || i.category || i.type || '').toString().toLowerCase()).includes(applyTo));
      const minMet = subtotal >= min;
      const isEligible = minMet && cartMatchesScope;

      if (promoCode) promoCode.textContent = codeText || 'Voucher';
      if (promoDiscount) promoDiscount.textContent = discountLabel;
      if (promoMin) promoMin.textContent = minLabel;
      if (promoScope) promoScope.textContent = scopeLabel;
      if (promoValidity) promoValidity.textContent = validityLabel;

      if (endRaw) {
        const endDate = new Date(endRaw.replace(' ', 'T'));
        if (!Number.isNaN(endDate.getTime())) {
          if (promoValidity) {
            promoValidity.textContent = `Until ${endDate.toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' })}`;
          }
        }
      }

      if (isEligible) {
        if (type === 'percent') discount = Math.round(subtotal * (val / 100));
        else discount = Math.round(val);
        appliedPromo = { id: opt.value, code: opt.dataset.code || '', type, value: val };
        if (promoStatus) {
          promoStatus.className = 'promo-status-chip ok';
          promoStatus.textContent = 'Eligible';
        }
        if (promoHint) {
          promoHint.style.color = 'var(--success)';
          promoHint.textContent = `You can use this voucher now. ${remainingLabel}.`;
        }
      } else if (promoHint) {
        if (promoStatus) {
          promoStatus.className = 'promo-status-chip no';
          promoStatus.textContent = 'Not Eligible';
        }
        const needed = Math.max(0, min - subtotal);
        const blockers = [];
        if (!minMet) blockers.push(`Spend ₱${Math.ceil(needed).toLocaleString()} more`);
        if (!cartMatchesScope) blockers.push(`Add at least one ${titleCaseWord(applyTo)} item`);
        promoHint.style.color = 'var(--danger)';
        promoHint.textContent = `${blockers.join(' and ')} to unlock this voucher. ${remainingLabel}.`;
      }
    }
  } else if (promoHint) {
    if (promoStatus) {
      promoStatus.className = 'promo-status-chip neutral';
      promoStatus.textContent = 'No Selection';
    }
    if (promoCode) promoCode.textContent = 'Voucher';
    if (promoDiscount) promoDiscount.textContent = '—';
    if (promoMin) promoMin.textContent = '—';
    if (promoScope) promoScope.textContent = '—';
    if (promoValidity) promoValidity.textContent = '—';
    promoHint.style.color = 'var(--rose-muted)';
    promoHint.textContent = 'Select a voucher to see requirements.';
  }

  const total    = Math.max(0, subtotal - discount + shipping);
  const estEl = document.getElementById('confirmShipping');
  if (estEl) estEl.textContent = 'Standard delivery · est. ' + selectedShipping.days;

  document.getElementById('coItems').innerHTML = cart.map(i => `
    <div style="display:flex;align-items:center;gap:10px;padding:10px 0;border-bottom:1px solid var(--border)">
      <div class="iph sm" style="width:48px;height:48px;border-radius:8px;background:var(--blush-mid);display:flex;align-items:center;justify-content:center;flex-shrink:0;overflow:hidden">
        ${i.image_url
          ? `<img src="${escapeAttr(i.image_url)}" alt="${escapeAttr(i.name)}" style="width:100%;height:100%;object-fit:cover">`
          : `<span style="font-size:1.3rem;font-weight:700;color:var(--rose-muted)">${escapeHtml((i.cat||i.name||'?')[0])}</span>`}
      </div>
      <div style="flex:1"><div style="font-size:.82rem;font-weight:500">${escapeHtml(i.name)}</div><div class="t-xs muted">Qty: ${i.qty}</div></div>
      <div style="font-family:var(--fd);font-size:.9rem;font-weight:600">₱${(i.price * i.qty).toLocaleString()}</div>
    </div>`).join('');

  document.getElementById('coRows').innerHTML = `
    <div class="sum-row"><span class="sl">Subtotal</span><span class="sv">₱${subtotal.toLocaleString()}</span></div>
    ${discount > 0 ? `<div class="sum-row"><span class="sl">Promotion (${escapeHtml(appliedPromo.code || '')})</span><span class="sv" style="color:var(--gold)">-₱${discount.toLocaleString()}</span></div>` : ''}
    <div class="sum-row"><span class="sl">Shipping</span><span class="sv" style="color:${shipping===0?'var(--success)':'var(--dark)'}">${shipping===0?'FREE':'₱'+Number(shipping).toLocaleString()}</span></div>`;
  document.getElementById('coTotal').innerHTML = `<span class="stl">Total</span><span class="stv">₱${total.toLocaleString()}</span>`;

  // Populate hidden form fields for promotion and totals
  const poPromoId = document.getElementById('po_promotion_id');
  const poPromoCode = document.getElementById('po_promotion_code');
  const poPromoDisc = document.getElementById('po_promotion_discount');
  const poOrderTotal = document.getElementById('po_order_total');
  if (poPromoId) poPromoId.value = appliedPromo ? appliedPromo.id : '';
  if (poPromoCode) poPromoCode.value = appliedPromo ? (appliedPromo.code || '') : '';
  if (poPromoDisc) poPromoDisc.value = discount;
  if (poOrderTotal) poOrderTotal.value = total;
}

function setStep(active) {
  const steps = ['details','payment','confirm'];
  steps.forEach((s, i) => {
    const el  = document.getElementById(`step-${s}`);
    const idx = steps.indexOf(active);
    el.className = i < idx ? 'step done' : i === idx ? 'step active' : 'step';
    el.querySelector('.step-n').textContent = i < idx ? '✓' : i + 2;
  });
}

function goToDetails() {
  setStep('details');
  document.getElementById('panelDetails').classList.remove('hidden');
  document.getElementById('panelPayment').classList.add('hidden');
  document.getElementById('panelConfirm').classList.add('hidden');
  // Keep delivery fields as the customer entered them (do not reset from __USER__ / broken user.name split).
}

function goToPayment() {
  if (!document.getElementById('firstName').value.trim()) { toast('Please enter your name'); return; }
  const phone = document.getElementById('phone').value.trim();
  if (!phone) { toast('Please enter your phone number'); return; }
  if (phone.length !== 11) { toast('Phone number must be exactly 11 digits'); return; }
  if (!document.getElementById('street').value.trim())    { toast('Please enter your address'); return; }
  setStep('payment');
  document.getElementById('panelDetails').classList.add('hidden');
  document.getElementById('panelPayment').classList.remove('hidden');
  document.getElementById('panelConfirm').classList.add('hidden');
}

function goToConfirm() {
  setStep('confirm');
  document.getElementById('panelPayment').classList.add('hidden');
  document.getElementById('panelConfirm').classList.remove('hidden');

  const fname = document.getElementById('firstName').value;
  const lname = document.getElementById('lastName').value;
  const street = document.getElementById('street').value;
  const city  = document.getElementById('city').value;
  const province = document.getElementById('province').value;
  const zip   = document.getElementById('zip').value;
  const country = document.getElementById('country').value;
  const line2 = [street, city, province, zip, country].filter(Boolean).join(', ');
  document.getElementById('confirmAddr').innerHTML = `${fname} ${lname}<br>${line2}`;

  const payLabels = { paymongo: '💳 Pay online (PayMongo)' };
  document.getElementById('confirmPay').textContent = payLabels[selectedPay] || payLabels.paymongo;

  const cart = getCart();
  document.getElementById('confirmItems').innerHTML = cart.map(i => `
    <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--border);font-size:.84rem">
      <span>${escapeHtml(i.name)} × ${i.qty}</span>
      <span style="font-weight:600">₱${(i.price * i.qty).toLocaleString()}</span>
    </div>`).join('');

  renderSummary();
}

function placeOrder() {
  const CO = checkoutCfg();
  if (selectedPay === 'paymongo' && !CO.paymongo_enabled) {
    toast('PayMongo is not configured. Choose another available payment method or set PAYMONGO_SECRET_KEY in api/config.php.');
    return;
  }

  const form = document.getElementById('placeOrderForm');
  if (!form) { toast('Order form not found.', 'warn'); return; }

  renderSummary();

  const shipName = `${document.getElementById('firstName').value} ${document.getElementById('lastName').value}`.trim();
  document.getElementById('po_ship_name').value = shipName;
  document.getElementById('po_ship_phone').value = document.getElementById('phone').value;
  document.getElementById('po_ship_street').value = document.getElementById('street').value;
  document.getElementById('po_ship_city').value = document.getElementById('city').value;
  document.getElementById('po_ship_province').value = document.getElementById('province').value;
  document.getElementById('po_ship_zip').value = document.getElementById('zip').value;
  document.getElementById('po_payment_method').value = selectedPay;
  document.getElementById('po_shipping_fee').value = selectedShipping.cost;
  document.getElementById('po_notes').value = 'Standard delivery · est. ' + selectedShipping.days;

  form.action = selectedPay === 'paymongo' ? 'paymongo_start.php' : 'place_order.php';
  form.submit();
}

function selectPay(method) {
  const CO = checkoutCfg();
  if (method === 'paymongo' && !CO.paymongo_enabled) return;
  selectedPay = method;
  ['paymongo'].forEach(m => {
    const el = document.getElementById(`pay-${m}`);
    const rad = document.getElementById(`radio-${m}`);
    if (el && rad) {
      el.classList.toggle('active', m === method);
      rad.classList.toggle('active', m === method);
    }
  });
}

function formatCard(el)   { let v = el.value.replace(/\D/g,'').substring(0,16); el.value = v.replace(/(.{4})/g,'$1 ').trim(); }
function formatExpiry(el) { let v = el.value.replace(/\D/g,'').substring(0,4); if (v.length >= 2) v = v.substring(0,2)+' / '+v.substring(2); el.value = v; }

document.addEventListener('DOMContentLoaded', () => {
  // Pre-fill from logged-in user if available
  const user = Auth.getUser();
  if (user) {
    if (user.first_name) document.getElementById('firstName').value = user.first_name;
    if (user.last_name)  document.getElementById('lastName').value  = user.last_name;
    if (user.email)      document.getElementById('email').value     = user.email;
    if (user.phone)      document.getElementById('phone').value     = user.phone;
  }

  const CO = checkoutCfg();
  selectPay(CO.paymongo_enabled ? 'paymongo' : '');

  if (getCart().length === 0) {
    document.querySelector('.checkout-layout').innerHTML = `<div style="grid-column:1/-1;text-align:center;padding:80px 0"><div style="font-size:3rem;margin-bottom:16px">🛍️</div><h2 style="margin-bottom:8px">Your cart is empty</h2><a href="product-list.html" class="btn btn-primary mt4">Shop Now →</a></div>`;
    return;
  }
  document.getElementById('province')?.addEventListener('input', () => renderSummary());
  const promoEl = document.getElementById('promoSelect');
  if (promoEl) promoEl.addEventListener('change', () => renderSummary());
  renderSummary();
});
</script>
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