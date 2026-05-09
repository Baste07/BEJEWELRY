<?php
require_once __DIR__ . '/admin_auth.php';
require_once __DIR__ . '/db.php';
admin_require_page('products');
$pdo = adminDb();

$page = max(1, (int)($_GET['page'] ?? 1));
$category = isset($_GET['category']) ? trim($_GET['category']) : 'all';
$search = trim((string)($_GET['search'] ?? ''));
$perPage = 9;

$where = ['(p.is_active = 1 OR p.is_active IS NULL)'];
$params = [];
if ($category !== 'all' && $category !== '') {
  $where[] = 'c.name = ?';
  $params[] = $category;
}
if ($search !== '') {
  $where[] = '(p.name LIKE ? OR p.description LIKE ?)';
  $s = '%' . $search . '%';
  $params[] = $s;
  $params[] = $s;
}
$whereSql = implode(' AND ', $where);

$cntSql = "SELECT COUNT(*) FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE $whereSql";
$cntStmt = $pdo->prepare($cntSql);
$cntStmt->execute($params);
$totalCount = (int) $cntStmt->fetchColumn();
$totalPages = max(1, (int) ceil($totalCount / $perPage));
$offset = ($page - 1) * $perPage;

$sql = "SELECT p.id, p.name, p.description, p.category_id, p.price, p.orig_price, p.image, p.badge, p.stock, p.material, c.name AS category_name
        FROM products p LEFT JOIN categories c ON p.category_id = c.id
        WHERE $whereSql ORDER BY p.id DESC LIMIT $perPage OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$productsList = $stmt->fetchAll(PDO::FETCH_ASSOC);

$baseUrl = '../uploads/products/';
foreach ($productsList as &$p) {
  $p['category'] = $p['category_name'];
  $p['image_url'] = !empty($p['image']) ? $baseUrl . $p['image'] : null;
  $p['sku'] = 'BJ-' . $p['id'];
  $p['is_featured'] = ($p['badge'] === 'best' || $p['badge'] === 'new');
  $p['is_on_sale'] = (($p['badge'] ?? '') === 'sale') || ((float)($p['orig_price'] ?? 0) > (float)($p['price'] ?? 0));
  $p['price'] = (float) $p['price'];
  $p['stock'] = (int) $p['stock'];
}
unset($p);

$categoriesStmt = $pdo->query("SELECT name FROM categories ORDER BY sort_order, name");
$categoriesList = $categoriesStmt->fetchAll(PDO::FETCH_COLUMN);

$productsData = [
  'user' => ['name' => 'Admin', 'role' => 'admin'],
  'badges' => [
    'pending_orders' => (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'processing'")->fetchColumn(),
    'new_products' => (int) $pdo->query("SELECT COUNT(*) FROM products WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn(),
    'low_stock' => (int) $pdo->query("SELECT COUNT(*) FROM products WHERE stock > 0 AND stock <= 5")->fetchColumn(),
    'pending_reviews' => (int) $pdo->query("SELECT COUNT(*) FROM product_reviews WHERE status = 'pending'")->fetchColumn(),
  ],
  'categories' => array_map(function ($n) { return ['name' => $n]; }, $categoriesList),
  'products' => $productsList,
  'total' => $totalCount,
  'pages' => $totalPages,
  'page' => $page,
  'perPage' => $perPage,
  'currentCat' => $category,
  'searchQuery' => $search,
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Bejewelry Admin — Products</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,500;0,600;0,700;1,400&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600;9..40,700&display=swap" rel="stylesheet">
  <style>
/* ═══════════════════════════════════════════════════════════
   BEJEWELRY — Admin Products Page
   Theme: Blush · Rose · Gold · Deep Plum
═══════════════════════════════════════════════════════════ */
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html{font-size:16px;scroll-behavior:smooth}
img{display:block;max-width:100%}
input,button,select,textarea{font-family:inherit}
a{text-decoration:none;color:inherit}
ul,ol{list-style:none}

:root {
  --fd:'Playfair Display',Georgia,serif;
  --fb:'DM Sans',system-ui,sans-serif;
  --blush:#FEF1F3;--blush-mid:#FAE3E8;--blush-deep:#F4C8D2;
  --rose:#D96070;--rose-deep:#B03050;--rose-muted:#CC8898;
  --white:#FFFFFF;--dark:#241418;--dark-soft:#3A2028;
  --muted:#7A5E68;--muted-light:#AC8898;
  --border:#ECDCE0;--border-mid:#DEC8D0;
  --gold:#B88830;--gold-light:#FFF7D6;--gold-border:#EDD050;--success:#228855;--danger:#BB3333;
  --flag-bg:#FFFBF0;--flag-border:#E8C97A;--flag-accent:#8C6800;--flag-badge-bg:#FFF7D6;--flag-badge-text:#7A4F00;
  --s1:4px;--s2:8px;--s3:12px;--s4:16px;--s5:20px;
  --s6:24px;--s8:32px;--s10:40px;--s12:48px;--s16:64px;
  --sidebar-w:220px;--hh:64px;
  --r-sm:8px;--r-md:12px;--r-lg:18px;--r-xl:26px;--r-pill:999px;
  --sh-xs:0 1px 3px rgba(160,40,60,.06);
  --sh-sm:0 2px 8px rgba(160,40,60,.09);
  --sh-md:0 4px 18px rgba(160,40,60,.13);
  --sh-lg:0 8px 36px rgba(160,40,60,.17);
  --tr:.2s ease;
}

body{font-family:var(--fb);background:var(--blush);color:var(--dark);line-height:1.5;min-height:100vh;-webkit-font-smoothing:antialiased}
h1,h2,h3,h4{font-family:var(--fd);color:var(--dark);line-height:1.2}

/* ── LAYOUT ── */
.site-wrapper{display:flex;min-height:100vh}
.site-content{flex:1;min-width:0;display:flex;flex-direction:column}

/* ── SIDEBAR ── */
.sidebar {
  width: var(--sidebar-w);
  min-width: var(--sidebar-w);
  flex-shrink: 0;
  background: var(--white);
  border-right: 1px solid var(--border);
  display: flex;
  flex-direction: column;
  position: sticky;
  top: 0;
  height: 100vh;
  overflow-y: auto;
  z-index: 90;
  scrollbar-width: thin;
  scrollbar-color: var(--blush-mid) transparent;
}
.sb-brand {
  padding: var(--s5) var(--s4) var(--s4);
  border-bottom: 1px solid var(--border);
}
.sb-logo { 
    font-family: var(--fd); font-size: 1.3rem; font-weight: 700; color: var(--dark); 
}

.sb-sub { font-size: .58rem; font-weight: 600; letter-spacing: .2em; text-transform: uppercase; color: var(--rose); margin-top: 3px; }

.sb-user {
  display: flex; align-items: center; gap: var(--s3);
  padding: var(--s3) var(--s4);
  border-bottom: 1px solid var(--border);
}
.sb-av {
  width: 34px; height: 34px; border-radius: 50%;
  background: linear-gradient(135deg, var(--blush-deep), var(--rose-muted));
  display: flex; align-items: center; justify-content: center;
  font-size: .78rem; font-weight: 700; color: var(--white);
  flex-shrink: 0; border: 2px solid var(--white); box-shadow: var(--sh-xs);
}
.sb-uname { font-size: .81rem; font-weight: 600; color: var(--dark); line-height: 1.3; }
.sb-urole { font-size: .59rem; color: var(--muted-light); text-transform: uppercase; letter-spacing: .08em; }

.sb-group {
  font-size: .57rem; font-weight: 700; text-transform: uppercase;
  letter-spacing: .2em; color: var(--muted-light);
  padding: var(--s4) var(--s4) var(--s2);
}
.sb-item {
  display: flex; align-items: center; gap: var(--s3);
  padding: 9px var(--s4); font-size: .81rem; color: var(--muted);
  border-left: 2.5px solid transparent; cursor: pointer;
  transition: all var(--tr);
}
.sb-item:hover { background: var(--blush); color: var(--dark); border-left-color: var(--rose-muted); }
.sb-item.active {
  background: linear-gradient(90deg, var(--blush-mid), var(--blush));
  color: var(--rose-deep); border-left-color: var(--rose); font-weight: 600;
}
.sb-icon { font-size: .9rem; width: 18px; text-align: center; flex-shrink: 0; }
.sb-badge {
  margin-left: auto; background: var(--rose); color: var(--white);
  font-size: .56rem; font-weight: 700; min-width: 17px; height: 17px;
  border-radius: var(--r-pill); display: flex; align-items: center;
  justify-content: center; padding: 0 4px;
}
.sb-badge.gold { background: var(--gold); }
.sb-div { border: none; border-top: 1px solid var(--border); margin: var(--s2) 0; }
.sb-foot {
  margin: var(--s4); padding: 10px 22px;
  font-size: .75rem; font-weight: 600; color: var(--white);
  background: linear-gradient(135deg, var(--rose), var(--rose-deep));
  border-radius: var(--r-pill); cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  gap: var(--s2); transition: all var(--tr); border: none;
}
.sb-foot:hover { transform: translateY(-2px); box-shadow: 0 4px 14px rgba(176,48,80,.3); }

/* ── TOPBAR ── */
.topbar{height:var(--hh);background:var(--white);border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;padding:0 var(--s8);position:sticky;top:0;z-index:80;box-shadow:var(--sh-xs)}
.topbar-left{display:flex;flex-direction:column}
.topbar-title{font-family:var(--fd);font-size:1rem;font-weight:500;color:var(--dark)}
.topbar-bc{font-size:.6rem;color:var(--muted-light);letter-spacing:.04em}
.topbar-right{display:flex;align-items:center;gap:var(--s3)}
.topbar-search{display:flex;align-items:center;gap:var(--s2);background:var(--blush);border:1.5px solid var(--border);border-radius:var(--r-pill);padding:6px 12px;width:200px;transition:border-color var(--tr)}
.topbar-search:focus-within{border-color:var(--rose-muted)}
.topbar-search input{border:none;outline:none;background:transparent;font-size:.76rem;color:var(--dark);width:100%}
.topbar-search input::placeholder{color:var(--muted-light)}
.icon-btn{width:32px;height:32px;border-radius:var(--r-md);background:var(--blush);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;cursor:pointer;transition:all var(--tr);color:var(--muted);font-size:.85rem;position:relative}
.icon-btn:hover{background:var(--blush-mid);border-color:var(--rose-muted);color:var(--rose-deep)}
.icon-btn .dot{position:absolute;top:6px;right:6px;width:9px;height:9px;border-radius:50%;background:var(--rose);border:2px solid var(--white);display:none;box-shadow:0 2px 8px rgba(176,48,80,.22)}

/* ── CONTENT ── */
.content{padding:var(--s6) var(--s8) var(--s16);flex:1}

/* ── PAGE HEADER ── */
.page-hdr{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:var(--s5);padding-bottom:var(--s4);border-bottom:1px solid var(--border)}
.page-hdr h2{font-size:1.35rem;margin-bottom:2px}
.page-hdr p{font-size:.74rem;color:var(--muted-light)}
.page-hdr-actions{display:flex;gap:var(--s2);align-items:center}

/* ── BUTTONS ── */
.btn{display:inline-flex;align-items:center;justify-content:center;gap:5px;padding:8px 18px;border-radius:var(--r-pill);font-family:var(--fb);font-size:.7rem;font-weight:600;letter-spacing:.07em;text-transform:uppercase;border:none;cursor:pointer;transition:all var(--tr);white-space:nowrap}
.btn-primary{background:linear-gradient(135deg,var(--rose),var(--rose-deep));color:var(--white);box-shadow:0 3px 12px rgba(176,48,80,.28)}
.btn-primary:hover{transform:translateY(-1px);box-shadow:0 5px 18px rgba(176,48,80,.38)}
.btn-ghost{background:transparent;color:var(--muted);border:1.5px solid var(--border-mid)}
.btn-ghost:hover{background:var(--blush);color:var(--dark);border-color:var(--rose-muted)}
.btn-danger-line{background:transparent;color:var(--danger);border:1.5px solid #EEAAAA}
.btn-danger-line:hover{background:#FFEEEE}
.btn-sm{padding:6px 13px;font-size:.66rem}
.btn:disabled{opacity:.5;cursor:not-allowed;transform:none!important}

/* ── FILTERS ── */
.filters-wrap{display:flex;align-items:center;gap:var(--s2);margin-bottom:var(--s4);flex-wrap:wrap}
.filter-tabs{display:flex;gap:6px;flex-wrap:wrap}
.ftab{padding:5px 12px;border-radius:var(--r-pill);font-size:.65rem;font-weight:600;letter-spacing:.06em;text-transform:uppercase;border:1.5px solid var(--border-mid);background:var(--white);color:var(--muted);cursor:pointer;transition:all var(--tr);white-space:nowrap}
.ftab:hover{background:var(--blush);color:var(--dark);border-color:var(--rose-muted)}
.ftab.on{background:linear-gradient(135deg,var(--rose),var(--rose-deep));color:var(--white);border-color:var(--rose)}
.filter-search{display:flex;align-items:center;gap:var(--s2);background:var(--white);border:1.5px solid var(--border-mid);border-radius:var(--r-pill);padding:6px 12px;margin-left:auto;width:200px;transition:border-color var(--tr)}
.filter-search:focus-within{border-color:var(--rose-muted)}
.filter-search input{border:none;outline:none;background:transparent;font-size:.76rem;color:var(--dark);width:100%}
.filter-search input::placeholder{color:var(--muted-light)}
.view-toggle{display:flex;border:1.5px solid var(--border-mid);border-radius:var(--r-md);overflow:hidden}
.vt-btn{width:30px;height:28px;display:flex;align-items:center;justify-content:center;background:var(--white);border:none;cursor:pointer;color:var(--muted);font-size:.85rem;transition:all var(--tr)}
.vt-btn.on{background:var(--blush-mid);color:var(--rose-deep)}
.vt-btn:hover:not(.on){background:var(--blush)}

/* ── PRODUCT GRID ── */
.prod-grid{display:grid;grid-template-columns:repeat(5,1fr);gap:10px}
.prod-grid.list-view{grid-template-columns:1fr}

/* ── PRODUCT CARD ── */
.pcard{background:var(--white);border-radius:var(--r-md);border:1px solid var(--border);overflow:hidden;transition:transform .22s ease,box-shadow .22s ease,border-color .22s ease;position:relative}
.pcard:hover{transform:translateY(-2px);box-shadow:var(--sh-md);border-color:var(--rose-muted)}
.pcard-img{aspect-ratio:1;background:var(--blush-mid);display:flex;align-items:center;justify-content:center;position:relative;overflow:hidden;transition:background .22s}
.pcard:hover .pcard-img{background:var(--blush-deep)}
.pcard-img img{width:100%;height:100%;object-fit:cover;position:absolute;inset:0;transition:transform .3s ease}
.pcard:hover .pcard-img img{transform:scale(1.04)}
.pcard-img .img-fallback{font-size:1.8rem;z-index:1;transition:transform .25s ease}
.pcard:hover .img-fallback{transform:scale(1.06)}
.pcard-stock-badge{position:absolute;top:5px;right:5px;z-index:2}
.pcard-featured-badge{position:absolute;top:5px;left:5px;z-index:2}
.pcard-body{padding:6px 8px 8px}
.pcard-cat{font-size:.48rem;font-weight:700;letter-spacing:.13em;text-transform:uppercase;color:var(--rose);margin-bottom:1px}
.pcard-name{font-family:var(--fd);font-size:.78rem;font-weight:500;color:var(--dark);margin-bottom:1px;line-height:1.25;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.pcard-sku{font-size:.54rem;color:var(--muted-light);margin-bottom:5px}
.pcard-foot{display:flex;align-items:center;justify-content:space-between;margin-bottom:5px}
.pcard-price-wrap{display:inline-flex;align-items:baseline;gap:4px;flex-wrap:wrap;min-width:0}
.pcard-price{font-family:var(--fd);font-size:.82rem;font-weight:600;color:var(--dark)}
.pcard-orig{font-size:.58rem;color:var(--muted-light);text-decoration:line-through}
.pcard-stock-num{font-size:.56rem;color:var(--muted)}
.pcard-stock-num.low{color:var(--danger);font-weight:600}
.pcard-actions{display:flex;gap:3px}
.pcard-btn{flex:1;padding:4px 2px;border-radius:var(--r-sm);font-size:.54rem;font-weight:600;letter-spacing:.03em;text-transform:uppercase;border:1px solid var(--border-mid);background:var(--white);color:var(--muted);cursor:pointer;transition:all var(--tr);display:flex;align-items:center;justify-content:center;gap:2px}
.pcard-btn:hover{background:var(--blush);color:var(--rose-deep);border-color:var(--rose-muted)}
.pcard-btn.del:hover{background:#FFEEEE;color:var(--danger);border-color:#EEAAAA}

/* ── LIST VIEW ── */
.prod-grid.list-view .pcard{display:flex;flex-direction:row}
.prod-grid.list-view .pcard-img{width:72px;min-width:72px;aspect-ratio:auto;height:72px}
.prod-grid.list-view .pcard-body{flex:1;display:flex;align-items:center;gap:var(--s4);padding:var(--s2) var(--s3)}
.prod-grid.list-view .pcard-main{flex:1;min-width:0}
.prod-grid.list-view .pcard-name{white-space:normal}
.prod-grid.list-view .pcard-foot{margin-bottom:0;flex-direction:column;align-items:flex-end;gap:2px;min-width:90px}
.prod-grid.list-view .pcard-actions{flex-direction:row;gap:var(--s2);min-width:150px;justify-content:flex-end}

/* ── BADGES ── */
.badge{display:inline-flex;align-items:center;padding:2px 6px;border-radius:var(--r-pill);font-size:.48rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase}
.b-instock{background:#E4FFEE;color:#156038;border:1px solid #68CC88}
.b-low{background:#FFF7D6;color:#8C6800;border:1px solid #EDD050}
.b-nostock{background:#FFEEEE;color:#982828;border:1px solid #EEAAAA}
.b-featured{background:linear-gradient(135deg,var(--gold),#d4a040);color:var(--white);border:none}
.b-sale{background:linear-gradient(135deg,#d96070,#b03050);color:var(--white);border:none}
.pcard-sale{position:absolute;top:5px;left:5px;z-index:2}
.pcard-btn.sale-on{background:#FFEEEE;color:#982828;border-color:#EEAAAA}
.pcard-btn.sale-off{background:#FFF7D6;color:#8C6800;border-color:#EDD050}

/* ── SKELETON ── */
.skel{background:linear-gradient(90deg,var(--blush) 25%,var(--blush-mid) 50%,var(--blush) 75%);background-size:200% 100%;animation:skel-shine 1.5s infinite;border-radius:var(--r-sm)}
@keyframes skel-shine{0%{background-position:200% 0}100%{background-position:-200% 0}}

/* ── EMPTY STATE ── */
.empty-state{text-align:center;padding:var(--s12) var(--s8);color:var(--muted-light);grid-column:1/-1}
.empty-icon{font-size:2.5rem;display:block;margin-bottom:var(--s3);opacity:.4}
.empty-state h3{font-size:1rem;margin-bottom:var(--s2);color:var(--muted)}
.empty-state p{font-size:.78rem}

/* ── MODAL ── */
.modal-bg{position:fixed;inset:0;background:rgba(36,20,24,.52);z-index:9000;display:none;align-items:center;justify-content:center;backdrop-filter:blur(4px);padding:16px}
.modal-bg.on{display:flex}
.modal{background:var(--white);border-radius:var(--r-xl);border:1px solid var(--border-mid);padding:var(--s6);width:560px;max-width:100%;max-height:92vh;overflow-y:auto;box-shadow:0 16px 60px rgba(160,40,60,.22);animation:modal-in .2s ease;position:relative}
.modal::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,var(--rose-muted),var(--rose),var(--rose-deep),var(--gold));border-radius:var(--r-xl) var(--r-xl) 0 0}
@keyframes modal-in{from{transform:translateY(10px);opacity:0}to{transform:none;opacity:1}}
.modal-hdr{display:flex;align-items:center;justify-content:space-between;margin-bottom:var(--s5);padding-bottom:var(--s3);border-bottom:1px solid var(--border)}
.modal-hdr h3{font-size:1.1rem}
.modal-close{width:28px;height:28px;border-radius:50%;background:var(--blush);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:.85rem;color:var(--muted);transition:all var(--tr);line-height:1}
.modal-close:hover{background:var(--blush-mid);color:var(--rose-deep)}
.modal-footer{display:flex;gap:var(--s2);justify-content:flex-end;margin-top:var(--s5);padding-top:var(--s3);border-top:1px solid var(--border)}

/* ── FORM ── */
.fg{margin-bottom:var(--s4)}
.fg:last-child{margin-bottom:0}
.flabel{display:block;font-size:.58rem;font-weight:700;letter-spacing:.14em;text-transform:uppercase;color:var(--muted);margin-bottom:5px}
.finput,.fselect,.ftextarea{width:100%;padding:9px 12px;font-size:.82rem;color:var(--dark);background:var(--white);border:1.5px solid var(--border-mid);border-radius:var(--r-md);outline:none;font-family:var(--fb);transition:border-color var(--tr),box-shadow var(--tr);appearance:none}
.finput:focus,.fselect:focus,.ftextarea:focus{border-color:var(--rose-muted);box-shadow:0 0 0 3px rgba(217,96,112,.1)}
.finput::placeholder{color:var(--muted-light)}
.ftextarea{resize:vertical;min-height:75px}
.frow{display:grid;grid-template-columns:1fr 1fr;gap:var(--s3)}
.fhint{font-size:.63rem;color:var(--muted-light);margin-top:3px}
.ferr{font-size:.63rem;color:var(--danger);margin-top:3px;display:none}
.finput.error{border-color:var(--danger)}
.finput.error+.ferr{display:block}

/* ── UPLOAD ── */
.upload-box{border:2px dashed var(--border-mid);border-radius:var(--r-lg);padding:var(--s5);text-align:center;cursor:pointer;background:var(--blush);transition:all var(--tr);position:relative}
.upload-box:hover,.upload-box.drag-over{background:var(--blush-mid);border-color:var(--rose-muted)}
.upload-icon{font-size:1.6rem;margin-bottom:var(--s2);display:block}
.upload-box strong{font-size:.78rem;color:var(--muted)}
.upload-box p{font-size:.68rem;color:var(--muted-light);margin-top:3px}
.upload-box input[type=file]{position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%}
.upload-preview{display:flex;flex-wrap:wrap;gap:var(--s2);margin-top:var(--s2)}
.upload-thumb{width:56px;height:56px;border-radius:var(--r-md);object-fit:cover;border:1px solid var(--border);background:var(--blush-mid)}

/* ── VIEW MODAL ── */
.view-modal{width:520px}
.view-product-img{width:100%;aspect-ratio:16/9;background:var(--blush-mid);border-radius:var(--r-lg);display:flex;align-items:center;justify-content:center;font-size:5rem;margin-bottom:var(--s4);overflow:hidden;position:relative}
.view-product-img img{width:100%;height:100%;object-fit:cover;position:absolute;inset:0}
.view-grid{display:grid;grid-template-columns:1fr 1fr;gap:var(--s3);margin-bottom:var(--s4)}
.view-field{background:var(--blush);border:1px solid var(--border);border-radius:var(--r-md);padding:var(--s3)}
.view-field-label{font-size:.54rem;font-weight:700;letter-spacing:.14em;text-transform:uppercase;color:var(--muted-light);margin-bottom:3px}
.view-field-val{font-size:.84rem;font-weight:600;color:var(--dark)}
.view-desc{background:var(--blush);border:1px solid var(--border);border-radius:var(--r-md);padding:var(--s3);margin-bottom:var(--s3)}
.view-desc-label{font-size:.54rem;font-weight:700;letter-spacing:.14em;text-transform:uppercase;color:var(--muted-light);margin-bottom:5px}
.view-desc-val{font-size:.82rem;color:var(--muted);line-height:1.6}

/* ── DELETE MODAL ── */
.delete-modal{width:400px}
.delete-icon{font-size:2.2rem;text-align:center;display:block;margin-bottom:var(--s3)}

/* ── SALE MODAL ── */
.sale-modal{width:460px}
.sale-current{display:flex;align-items:center;justify-content:space-between;padding:10px 12px;border:1px solid var(--border);border-radius:var(--r-md);background:var(--blush);margin-bottom:var(--s3)}
.sale-current-label{font-size:.62rem;color:var(--muted-light);text-transform:uppercase;letter-spacing:.1em}
.sale-current-price{font-family:var(--fd);font-size:1rem;font-weight:600;color:var(--dark)}
.sale-chips{display:flex;gap:6px;flex-wrap:wrap;margin-top:8px}
.sale-chip{padding:5px 10px;border:1px solid var(--border-mid);background:var(--white);color:var(--muted);border-radius:var(--r-pill);font-size:.64rem;font-weight:700;letter-spacing:.04em;cursor:pointer;transition:all var(--tr)}
.sale-chip:hover{background:var(--blush);border-color:var(--rose-muted);color:var(--rose-deep)}
.sale-preview{margin-top:10px;padding:10px 12px;border:1px dashed var(--border-mid);border-radius:var(--r-md);background:#fff9fb;font-size:.75rem;color:var(--muted)}
.sale-preview strong{color:var(--rose-deep)}

/* ── REMOVE SALE MODAL ── */
.remove-sale-modal{width:460px}
.remove-sale-hero{display:flex;gap:12px;align-items:flex-start;padding:12px 14px;border-radius:var(--r-lg);background:linear-gradient(135deg,#fff1f4,#fff7f8);border:1px solid #f0d5dc;margin-bottom:var(--s3)}
.remove-sale-icon{width:40px;height:40px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,var(--rose),var(--rose-deep));color:var(--white);font-size:1rem;flex-shrink:0;box-shadow:0 6px 18px rgba(176,48,80,.22)}
.remove-sale-title{font-family:var(--fd);font-size:1rem;font-weight:600;color:var(--dark);margin-bottom:3px}
.remove-sale-copy{font-size:.78rem;color:var(--muted);line-height:1.6}
.remove-sale-list{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:10px}
.remove-sale-item{padding:10px 12px;border-radius:var(--r-md);border:1px solid var(--border);background:var(--white)}
.remove-sale-item span{display:block;font-size:.56rem;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:var(--muted-light);margin-bottom:4px}
.remove-sale-item strong{font-family:var(--fd);font-size:.92rem;color:var(--dark)}

/* ── PAGINATION ── */
.pagination{display:flex;align-items:center;justify-content:space-between;margin-top:var(--s6)}
.pag-info{font-size:.7rem;color:var(--muted-light)}
.pag-btns{display:flex;gap:6px}
.pg{width:30px;height:30px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.72rem;font-weight:600;border:1.5px solid var(--border);background:var(--white);color:var(--muted);cursor:pointer;transition:all var(--tr)}
.pg:hover{background:var(--blush);color:var(--dark);border-color:var(--rose-muted)}
.pg.active{background:var(--rose);color:var(--white);border-color:var(--rose)}
.pg:disabled{opacity:.4;cursor:not-allowed}

/* ── LOADING BAR ── */
.loading-bar{position:fixed;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,var(--rose),var(--rose-deep),var(--gold));background-size:200% 100%;animation:loading 1.2s linear infinite;z-index:9999;display:none}
.loading-bar.active{display:block}
@keyframes loading{0%{background-position:200% 0}100%{background-position:-200% 0}}

/* ── TOAST ── */
.toast{position:fixed;bottom:20px;right:20px;background:var(--dark);color:var(--white);padding:10px 18px;border-radius:var(--r-md);font-size:.76rem;z-index:9999;opacity:0;transform:translateY(8px);transition:all .25s;pointer-events:none;max-width:300px;box-shadow:var(--sh-lg);display:flex;align-items:center;gap:7px}
.toast.on{opacity:1;transform:translateY(0)}
.toast.success{background:var(--success)}
.toast.error{background:var(--danger)}

/* ── RESPONSIVE ── */
@media(max-width:1400px){.prod-grid{grid-template-columns:repeat(4,1fr)}}
@media(max-width:1100px){.prod-grid{grid-template-columns:repeat(3,1fr)}}
@media(max-width:800px){.prod-grid{grid-template-columns:repeat(2,1fr)}}
@media(max-width:500px){.prod-grid{grid-template-columns:1fr}.sidebar{display:none}}

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

<div class="loading-bar" id="loadingBar"></div>

<div class="site-wrapper">

  <?php $GLOBALS['NAV_ACTIVE'] = 'products'; require __DIR__ . '/includes/nav_sidebar.php'; ?>
  <div class="site-content">

    <!-- Topbar -->
    <header class="topbar">
      <div class="topbar-left">
        <span class="topbar-title">Products</span>
        <span class="topbar-bc">Bejewelry Admin › Products</span>
      </div>
      <div class="topbar-right">
        <button class="icon-btn" title="Notifications" onclick="handleNotifications()">🔔<span class="dot" id="notifDot"></span></button>
        <button class="icon-btn" title="Refresh" onclick="loadPage()">↺</button>
      </div>
    </header>

    <!-- Page Content -->
    <div class="content">

      <!-- Page Header -->
      <div class="page-hdr">
        <div>
          <h2>Products</h2>
          <p id="productCount">Loading catalogue…</p>
        </div>
        <div class="page-hdr-actions">
          <button class="btn btn-primary btn-sm" onclick="openAddModal()">＋ Add Product</button>
        </div>
      </div>

      <!-- Filters + Search + View Toggle -->
      <div class="filters-wrap">
        <div class="filter-tabs" id="categoryTabs">
          <!-- built by JS -->
        </div>
        <div style="display:flex;align-items:center;gap:var(--s2);margin-left:auto">
          <div class="filter-search">
            <span style="color:var(--muted-light)">⌕</span>
            <input type="text" placeholder="Search products…" id="productSearch">
          </div>
          <div class="view-toggle">
            <button class="vt-btn on" id="gridViewBtn" onclick="setView('grid')" title="Grid view">⊞</button>
            <button class="vt-btn"    id="listViewBtn" onclick="setView('list')" title="List view">☰</button>
          </div>
        </div>
      </div>

      <!-- Product Grid -->
      <div class="prod-grid" id="productGrid">
        <!-- skeleton placeholders while JS loads -->
        <div class="pcard"><div class="pcard-img skel" style="aspect-ratio:1"></div><div class="pcard-body"><div class="skel" style="height:10px;width:40%;margin-bottom:7px"></div><div class="skel" style="height:15px;width:80%;margin-bottom:7px"></div><div class="skel" style="height:11px;width:55%"></div></div></div>
        <div class="pcard"><div class="pcard-img skel" style="aspect-ratio:1"></div><div class="pcard-body"><div class="skel" style="height:10px;width:40%;margin-bottom:7px"></div><div class="skel" style="height:15px;width:80%;margin-bottom:7px"></div><div class="skel" style="height:11px;width:55%"></div></div></div>
        <div class="pcard"><div class="pcard-img skel" style="aspect-ratio:1"></div><div class="pcard-body"><div class="skel" style="height:10px;width:40%;margin-bottom:7px"></div><div class="skel" style="height:15px;width:80%;margin-bottom:7px"></div><div class="skel" style="height:11px;width:55%"></div></div></div>
        <div class="pcard"><div class="pcard-img skel" style="aspect-ratio:1"></div><div class="pcard-body"><div class="skel" style="height:10px;width:40%;margin-bottom:7px"></div><div class="skel" style="height:15px;width:80%;margin-bottom:7px"></div><div class="skel" style="height:11px;width:55%"></div></div></div>
      </div>

      <!-- Pagination -->
      <div class="pagination" id="pagination" style="display:none">
        <span class="pag-info" id="pagInfo"></span>
        <div class="pag-btns" id="pagBtns"></div>
      </div>

    </div><!-- /content -->
  </div><!-- /site-content -->
</div><!-- /site-wrapper -->


<!-- ══════════════════════════════════════════════════════════
     MODAL: ADD PRODUCT
══════════════════════════════════════════════════════════ -->
<div class="modal-bg" id="addModal">
  <div class="modal">
    <div class="modal-hdr">
      <h3>Add New Product</h3>
      <button class="modal-close" onclick="closeModal('addModal')">✕</button>
    </div>

    <form id="addProductForm" method="post" action="products_action.php" enctype="multipart/form-data">
      <?php echo csrf_token_field(); ?>
    <input type="hidden" name="action" value="create">

    <div class="frow">
      <div class="fg">
        <label class="flabel">Product Name *</label>
        <input class="finput" id="add_name" name="name" type="text" placeholder="e.g. Solara Solitaire Ring">
        <span class="ferr">Product name is required</span>
      </div>
      <div class="fg">
        <label class="flabel">SKU</label>
        <input class="finput" id="add_sku" name="sku" type="text" placeholder="Auto-generated if blank">
        <span class="fhint">Leave blank to auto-generate</span>
      </div>
    </div>

    <div class="frow">
      <div class="fg">
        <label class="flabel">Category *</label>
        <select class="fselect" id="add_category" name="category">
          <option value="">Select category…</option>
        </select>
      </div>
      <div class="fg">
        <label class="flabel">Material</label>
        <input class="finput" id="add_material" name="material" type="text" placeholder="e.g. 18K Gold, Sterling Silver">
      </div>
    </div>

    <div class="frow">
      <div class="fg">
        <label class="flabel">Price (₱) *</label>
        <input class="finput" id="add_price" name="price" type="number" min="0" step="0.01" placeholder="0.00">
        <span class="ferr">Valid price is required</span>
      </div>
      <div class="fg">
        <label class="flabel">Stock Quantity *</label>
        <input class="finput" id="add_stock" name="stock" type="number" min="0" placeholder="0">
        <span class="ferr">Stock quantity is required</span>
      </div>
    </div>

    <div class="fg">
      <label class="flabel">Description</label>
      <textarea class="ftextarea" id="add_desc" name="description" placeholder="Describe this piece — materials, gemstones, occasion…"></textarea>
    </div>

    <div class="fg">
      <label class="flabel">Product Image</label>
      <div class="upload-box" id="addUploadBox">
        <span class="upload-icon">📷</span>
        <strong style="font-size:.8rem;color:var(--muted)">Click to upload or drag &amp; drop</strong>
        <p>PNG, JPG up to 5 MB</p>
        <input type="file" id="add_image" name="image" accept="image/*" onchange="previewImage(this,'addPreview')">
      </div>
      <div class="upload-preview" id="addPreview"></div>
    </div>

    <div class="fg" style="display:flex;align-items:center;gap:10px">
      <input type="checkbox" id="add_featured" name="featured" value="1" style="width:15px;height:15px;accent-color:var(--rose);cursor:pointer">
      <label for="add_featured" class="flabel" style="margin:0;cursor:pointer">Mark as Featured product</label>
    </div>

    <div class="modal-footer">
      <button type="button" class="btn btn-ghost btn-sm" onclick="closeModal('addModal')">Cancel</button>
      <button type="button" class="btn btn-primary btn-sm" id="addSubmitBtn" onclick="handleAddProduct()">Add Product</button>
    </div>
    </form>
  </div>
</div>


<!-- ══════════════════════════════════════════════════════════
     MODAL: EDIT PRODUCT
══════════════════════════════════════════════════════════ -->
<div class="modal-bg" id="editModal">
  <div class="modal">
    <div class="modal-hdr">
      <h3 id="editModalTitle">Edit Product</h3>
      <button class="modal-close" onclick="closeModal('editModal')">✕</button>
    </div>

    <form id="editProductForm" method="post" action="products_action.php" enctype="multipart/form-data">
      <?php echo csrf_token_field(); ?>
    <input type="hidden" name="action" value="update">
    <input type="hidden" id="edit_id" name="id">

    <div class="frow">
      <div class="fg">
        <label class="flabel">Product Name *</label>
        <input class="finput" id="edit_name" name="name" type="text">
        <span class="ferr">Product name is required</span>
      </div>
      <div class="fg">
        <label class="flabel">SKU</label>
        <input class="finput" id="edit_sku" name="sku" type="text">
      </div>
    </div>

    <div class="frow">
      <div class="fg">
        <label class="flabel">Category</label>
        <select class="fselect" id="edit_category" name="category">
          <option value="">Select category…</option>
        </select>
      </div>
      <div class="fg">
        <label class="flabel">Material</label>
        <input class="finput" id="edit_material" name="material" type="text">
      </div>
    </div>

    <div class="frow">
      <div class="fg">
        <label class="flabel">Price (₱) *</label>
        <input class="finput" id="edit_price" name="price" type="number" min="0" step="0.01">
        <span class="ferr">Valid price is required</span>
      </div>
      <div class="fg">
        <label class="flabel">Stock Quantity</label>
        <input class="finput" id="edit_stock" name="stock" type="number" min="0">
      </div>
    </div>

    <div class="fg">
      <label class="flabel">Description</label>
      <textarea class="ftextarea" id="edit_desc" name="description"></textarea>
    </div>

    <div class="fg">
      <label class="flabel">Replace Image</label>
      <div class="upload-box">
        <span class="upload-icon">📷</span>
        <strong style="font-size:.8rem;color:var(--muted)">Click to upload a new image</strong>
        <p>Leave empty to keep current image</p>
        <input type="file" id="edit_image" name="image" accept="image/*" onchange="previewImage(this,'editPreview')">
      </div>
      <div class="upload-preview" id="editPreview"></div>
    </div>

    <div class="fg" style="display:flex;align-items:center;gap:10px">
      <input type="checkbox" id="edit_featured" name="featured" value="1" style="width:15px;height:15px;accent-color:var(--rose);cursor:pointer">
      <label for="edit_featured" class="flabel" style="margin:0;cursor:pointer">Mark as Featured product</label>
    </div>

    <div class="modal-footer">
      <button type="button" class="btn btn-ghost btn-sm" onclick="closeModal('editModal')">Cancel</button>
      <button type="button" class="btn btn-primary btn-sm" id="editSubmitBtn" onclick="handleEditProduct()">Save Changes</button>
    </div>
    </form>
  </div>
</div>


<!-- ══════════════════════════════════════════════════════════
     MODAL: SET SALE PRICE
══════════════════════════════════════════════════════════ -->
<div class="modal-bg" id="saleModal">
  <div class="modal sale-modal">
    <div class="modal-hdr">
      <h3>Set Sale Price</h3>
      <button class="modal-close" onclick="closeModal('saleModal')">✕</button>
    </div>

    <input type="hidden" id="sale_product_id">

    <div class="fg">
      <label class="flabel">Product</label>
      <input class="finput" id="sale_product_name" type="text" readonly>
    </div>

    <div class="sale-current">
      <div>
        <div class="sale-current-label">Current Price</div>
        <div class="sale-current-price" id="sale_current_price">₱0</div>
      </div>
    </div>

    <div class="fg">
      <label class="flabel">Sale Price</label>
      <input class="finput" id="sale_new_price" type="number" min="0.01" step="0.01" placeholder="e.g. 599" oninput="updateSalePreview()">
      <div class="sale-chips">
        <button type="button" class="sale-chip" onclick="applySaleDiscount(10)">10% Off</button>
        <button type="button" class="sale-chip" onclick="applySaleDiscount(20)">20% Off</button>
        <button type="button" class="sale-chip" onclick="applySaleDiscount(30)">30% Off</button>
        <button type="button" class="sale-chip" onclick="applySaleDiscount(40)">40% Off</button>
      </div>
      <div class="sale-preview" id="sale_preview">
        <div id="sale_preview_price">Enter a sale price to see preview.</div>
        <div id="sale_preview_pct" style="margin-top:6px;font-weight:700;color:var(--rose-deep)"></div>
      </div>
    </div>

    <div class="modal-footer">
      <button type="button" class="btn btn-ghost btn-sm" onclick="closeModal('saleModal')">Cancel</button>
      <button type="button" class="btn btn-primary btn-sm" onclick="submitSalePrice()">Apply Sale</button>
    </div>
  </div>
</div>


<!-- ══════════════════════════════════════════════════════════
     MODAL: REMOVE SALE
══════════════════════════════════════════════════════════ -->
<div class="modal-bg" id="removeSaleModal">
  <div class="modal remove-sale-modal">
    <div class="modal-hdr">
      <h3>Remove Sale</h3>
      <button class="modal-close" onclick="closeModal('removeSaleModal')">✕</button>
    </div>

    <input type="hidden" id="remove_sale_product_id">

    <div class="remove-sale-hero">
      <div class="remove-sale-icon">%</div>
      <div>
        <div class="remove-sale-title" id="remove_sale_product_name">Product</div>
        <div class="remove-sale-copy">This will restore the original price and remove the Sale badge from the product.</div>
      </div>
    </div>

    <div class="remove-sale-list">
      <div class="remove-sale-item">
        <span>Current Sale Price</span>
        <strong id="remove_sale_current_price">₱0</strong>
      </div>
      <div class="remove-sale-item">
        <span>Restored Price</span>
        <strong id="remove_sale_restored_price">₱0</strong>
      </div>
    </div>

    <div class="modal-footer">
      <button type="button" class="btn btn-ghost btn-sm" onclick="closeModal('removeSaleModal')">Cancel</button>
      <button type="button" class="btn btn-primary btn-sm" onclick="submitRemoveSale()">Remove Sale</button>
    </div>
  </div>
</div>


<!-- ══════════════════════════════════════════════════════════
     MODAL: VIEW PRODUCT
══════════════════════════════════════════════════════════ -->
<div class="modal-bg" id="viewModal">
  <div class="modal view-modal">
    <div class="modal-hdr">
      <h3>Product Details</h3>
      <button class="modal-close" onclick="closeModal('viewModal')">✕</button>
    </div>

    <!-- Product image banner -->
    <div class="view-product-img" id="viewProductImg">
      <!-- filled by JS -->
    </div>

    <!-- Name + badges row -->
    <div style="margin-bottom:var(--s4)">
      <div style="font-size:.52rem;font-weight:700;letter-spacing:.16em;text-transform:uppercase;color:var(--rose);margin-bottom:3px" id="viewCat">—</div>
      <div style="font-family:var(--fd);font-size:1.2rem;font-weight:600;margin-bottom:6px" id="viewName">—</div>
      <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
        <span id="viewStockBadge"></span>
        <span style="font-size:.72rem;color:var(--muted-light)" id="viewFeatured"></span>
      </div>
    </div>

    <!-- Field grid -->
    <div class="view-grid">
      <div class="view-field">
        <div class="view-field-label">SKU</div>
        <div class="view-field-val" id="viewSku">—</div>
      </div>
      <div class="view-field">
        <div class="view-field-label">Material</div>
        <div class="view-field-val" id="viewMat">—</div>
      </div>
      <div class="view-field">
        <div class="view-field-label">Price</div>
        <div class="view-field-val" id="viewPrice" style="color:var(--rose-deep)">—</div>
      </div>
      <div class="view-field">
        <div class="view-field-label">Stock</div>
        <div class="view-field-val" id="viewStock">—</div>
      </div>
    </div>

    <!-- Description -->
    <div class="view-desc">
      <div class="view-desc-label">Description</div>
      <div class="view-desc-val" id="viewDesc">No description provided.</div>
    </div>

    <div class="modal-footer">
      <button class="btn btn-ghost btn-sm" onclick="closeModal('viewModal')">Close</button>
      <button class="btn btn-primary btn-sm" id="viewEditBtn">✏ Edit Product</button>
    </div>
  </div>
</div>


<!-- ══════════════════════════════════════════════════════════
     MODAL: DELETE CONFIRM
══════════════════════════════════════════════════════════ -->
<div class="modal-bg" id="deleteModal">
  <div class="modal delete-modal">
    <div class="modal-hdr">
      <h3>Delete Product?</h3>
      <button class="modal-close" onclick="closeModal('deleteModal')">✕</button>
    </div>

    <input type="hidden" id="delete_id">

    <span class="delete-icon">🗑️</span>
    <p style="text-align:center;font-size:.86rem;color:var(--muted);margin-bottom:var(--s2)">
      You're about to permanently delete<br>
      <strong id="deleteProductName">this product</strong>.
    </p>
    <p style="text-align:center;font-size:.75rem;color:var(--muted-light)">
      This cannot be undone. All product data will be removed from your catalogue.
    </p>

    <div class="modal-footer">
      <button class="btn btn-ghost btn-sm" onclick="closeModal('deleteModal')">Cancel</button>
      <button class="btn btn-danger-line btn-sm" id="deleteConfirmBtn" onclick="handleDeleteProduct()">Delete Product</button>
    </div>
  </div>
</div>


<!-- Toast notification -->
<div class="toast" id="toast"></div>

<script>window.__PRODUCTS__ = <?= json_encode($productsData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;</script>
<script src="whoami.js?v=1"></script>
<script src="../admin/notif_dot.js?v=1"></script>
<script src="../admin/confirm_modal.js?v=1"></script>
<script src="../admin/products.js?v=9"></script>
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