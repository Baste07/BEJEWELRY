<?php
require_once __DIR__ . '/inc.php';
if (!current_user_id()) {
  header('Location: login.php?redirect=' . urlencode('wishlist.php'));
  exit;
}
$wishlist = get_customer_wishlist();
$user = current_user();
$cart = get_customer_cart();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<?= csrf_meta_tag() ?>
<title>Wishlist — Bejewelry</title>
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
<script>window.__WISHLIST__ = <?= json_encode($wishlist, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>; window.__USER__ = <?= json_encode($user, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>; window.__CART__ = <?= json_encode($cart, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;</script>
<div id="toasts"></div>
<div class="drawer-ov" id="drawerOv" onclick="closeCart()"></div>
<aside class="cart-drawer" id="cartDrawer">
  <div class="drw-hd"><h3 style="font-size:1.05rem">Shopping Cart</h3><button class="drw-close" onclick="closeCart()">✕</button></div>
  <div class="drw-body" id="cartBody"></div>
  <div class="drw-foot" id="cartFoot"></div>
</aside>

<div class="js-header" data-active="wish"></div>

<div class="site-wrapper">
<aside class="sidebar" data-active="wish"></aside>
<div class="site-content">
<div class="main-content"><div class="container">

  <div class="page-hdr">
    <div class="bc"><a href="index.php">Home</a><span class="sep">›</span><span class="cur">My Wishlist</span></div>
    <div class="flex jb items-center">
      <div><span class="eyebrow">My Collection</span><h1>Wishlist <span id="wishCount" style="font-size:1.2rem;color:var(--muted)"></span></h1></div>
      <button class="btn btn-primary" id="addAllBtn" onclick="addAllToCart()" style="display:none">🛍️ Add All to Cart</button>
    </div>
  </div>

  <!-- EMPTY STATE -->
  <div id="wishEmpty" class="hidden" style="text-align:center;padding:80px 0">
    <div style="font-size:4rem;margin-bottom:16px">♡</div>
    <h2 style="margin-bottom:8px">Your wishlist is empty</h2>
    <p style="margin-bottom:24px">Save pieces you love and come back for them later</p>
    <a href="product-list.php" class="btn btn-primary btn-lg">Browse Jewelry</a>
  </div>

  <div class="g4" id="wishGrid"></div>

</div></div>
<div class="js-footer"></div>
</div></div>



<script>
function renderWishlist() {
  const prods = window.__WISHLIST__ || [];
  const empty = document.getElementById('wishEmpty');
  const grid = document.getElementById('wishGrid');
  const addAllBtn = document.getElementById('addAllBtn');
  const countEl = document.getElementById('wishCount');
  if (!prods.length) {
    empty.classList.remove('hidden');
    grid.innerHTML = '';
    addAllBtn.style.display = 'none';
    if (countEl) countEl.textContent = '';
    return;
  }
  empty.classList.add('hidden');
  addAllBtn.style.display = 'inline-flex';
  if (countEl) countEl.textContent = '(' + prods.length + ')';
  setWish(prods.map(p => p.id));
  grid.innerHTML = prods.map(p => productCard(p, 'product_detail.php')).join('');
}

function addAllToCart() {
  const prods = window.__WISHLIST__ || [];
  prods.forEach(p => addToCart(p.id));
  toast('✓ ' + prods.length + ' item(s) added to cart!', 'ok');
  openCart();
}

document.addEventListener('DOMContentLoaded', () => renderWishlist());
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