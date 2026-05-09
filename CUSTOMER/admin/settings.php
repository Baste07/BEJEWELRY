<?php
require_once __DIR__ . '/admin_auth.php';
admin_require_page('settings');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <?= csrf_meta_tag() ?>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Bejewelry Admin — Settings</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600;9..40,700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="dashboard.css">
  <style>
.btn-danger{background:transparent;color:var(--danger);border:1.5px solid #EEAAAA}
.btn-danger:hover{background:#FFF0F0}
.btn:disabled{opacity:.5;cursor:not-allowed;transform:none!important}
.settings-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px}
.card-full{grid-column:1/-1}
.card{background:var(--white);border-radius:var(--r-lg);border:1px solid var(--border);box-shadow:var(--sh-xs);overflow:hidden}
.card-hd{padding:16px 24px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;background:linear-gradient(90deg,var(--blush),var(--white))}
.card-hd h3{font-family:var(--fd);font-size:.95rem;color:var(--dark)}
.card-hd-icon{font-size:1.1rem;opacity:.5}
.card-body{padding:24px}
.card-divider{border:none;border-top:1px solid var(--border);margin:16px 0}
.fg{margin-bottom:16px}
.fg:last-child{margin-bottom:0}
.flabel{display:block;font-size:.61rem;font-weight:700;letter-spacing:.15em;text-transform:uppercase;color:var(--muted);margin-bottom:6px}
.finput,.fselect{width:100%;padding:10px 14px;font-size:.87rem;color:var(--dark);background:var(--white);border:1.5px solid var(--border-mid);border-radius:var(--r-md);outline:none;transition:border-color var(--tr),box-shadow var(--tr);appearance:none;-webkit-appearance:none}
.finput:focus,.fselect:focus{border-color:var(--rose-muted);box-shadow:0 0 0 3px rgba(217,96,112,.1)}
.finput::placeholder{color:var(--muted-light)}
.fhint{font-size:.68rem;color:var(--muted-light);margin-top:4px}
.frow{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.toggle-row{display:flex;align-items:center;justify-content:space-between;padding:11px 0;border-bottom:1px solid var(--border)}
.toggle-row:last-of-type{border-bottom:none}
.toggle-row-info{flex:1}
.toggle-row-label{font-size:.84rem;color:var(--dark);font-weight:500}
.toggle-row-desc{font-size:.7rem;color:var(--muted-light);margin-top:1px}
.toggle{width:42px;height:24px;background:var(--border-mid);border-radius:var(--r-pill);position:relative;cursor:pointer;flex-shrink:0;transition:background var(--tr);border:none;outline:none;margin-left:12px}
.toggle.on{background:linear-gradient(135deg,var(--rose),var(--rose-deep))}
.toggle::after{content:'';position:absolute;top:3px;left:3px;width:18px;height:18px;background:var(--white);border-radius:50%;transition:left var(--tr);box-shadow:0 1px 4px rgba(0,0,0,.15)}
.toggle.on::after{left:21px}
.admin-profile{display:flex;align-items:center;gap:16px;margin-bottom:20px;padding-bottom:20px;border-bottom:1px solid var(--border)}
.admin-av{width:52px;height:52px;border-radius:50%;background:linear-gradient(135deg,var(--blush-deep),var(--rose-muted));display:flex;align-items:center;justify-content:center;font-size:1.2rem;font-weight:700;color:var(--white);flex-shrink:0;box-shadow:var(--sh-sm)}
.admin-av-name{font-family:var(--fd);font-size:1rem;color:var(--dark)}
.admin-av-role{font-size:.7rem;color:var(--muted-light);text-transform:uppercase;letter-spacing:.08em;margin-top:2px}
.admin-table-wrap{overflow-x:auto}
.admin-table{width:100%;border-collapse:collapse}
.admin-table th{font-size:.6rem;font-weight:700;letter-spacing:.15em;text-transform:uppercase;color:var(--muted-light);background:var(--blush);padding:10px 20px;text-align:left;border-bottom:1px solid var(--border);white-space:nowrap}
.admin-table td{padding:14px 20px;border-bottom:1px solid var(--border);font-size:.82rem;color:var(--dark);vertical-align:middle}
.admin-table tr:last-child td{border-bottom:none}
.admin-table tbody tr:hover td{background:var(--blush)}
.admin-cell-user{display:flex;align-items:center;gap:12px}
.admin-row-av{width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,var(--blush-deep),var(--rose-muted));display:flex;align-items:center;justify-content:center;font-size:.72rem;font-weight:700;color:var(--white);flex-shrink:0}
.admin-row-name{font-weight:600;font-size:.84rem;color:var(--dark)}
.admin-row-email{font-size:.68rem;color:var(--muted-light)}
.you-badge{font-size:.58rem;color:var(--rose);font-weight:700;text-transform:uppercase;background:var(--blush-mid);padding:2px 7px;border-radius:var(--r-pill);margin-left:6px;vertical-align:middle}
.role-badge{display:inline-flex;align-items:center;padding:3px 10px;border-radius:var(--r-pill);font-size:.59rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase}
.role-super_admin{background:linear-gradient(135deg,var(--rose),var(--rose-deep));color:var(--white)}
.role-manager{background:#E6F3FF;color:#1455A0;border:1px solid #88C0F0}
.role-inventory{background:#FFF7D6;color:#8C6800;border:1px solid #EDD050}
.role-courier{background:#EEF3FF;color:#3559C7;border:1px solid #C4D0FF}
.admin-status-active{display:inline-flex;align-items:center;gap:5px;font-size:.75rem;color:var(--success);font-weight:500}
.admin-status-active::before{content:'';width:7px;height:7px;border-radius:50%;background:var(--success);display:block}
.admin-status-locked{display:inline-flex;align-items:center;gap:5px;font-size:.75rem;color:var(--danger);font-weight:500}
.admin-status-locked::before{content:'';width:7px;height:7px;border-radius:50%;background:var(--danger);display:block}
.skel{background:linear-gradient(90deg,var(--blush) 25%,var(--blush-mid) 50%,var(--blush) 75%);background-size:200% 100%;animation:skel 1.5s infinite}
@keyframes skel{0%{background-position:200% 0}100%{background-position:-200% 0}}
.loading-bar{position:fixed;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,var(--rose),var(--gold));background-size:200% 100%;animation:loadbar 1.2s linear infinite;z-index:99999;display:none}
.loading-bar.active{display:block}
@keyframes loadbar{0%{background-position:200% 0}100%{background-position:-200% 0}}
.toast{position:fixed;bottom:24px;right:24px;background:var(--dark);color:var(--white);padding:12px 20px;border-radius:var(--r-md);font-size:.8rem;z-index:99999;opacity:0;transform:translateY(8px);transition:all .25s;pointer-events:none;max-width:320px;box-shadow:var(--sh-lg);display:flex;align-items:center;gap:8px}
.toast.on{opacity:1;transform:translateY(0)}
.toast.success{background:var(--success)}
.toast.error{background:var(--danger)}

/* ── MODAL OVERLAY ── */
.modal-overlay{
  position:fixed;top:0;left:0;right:0;bottom:0;
  background:rgba(36,20,24,.55);
  z-index:9999;
  display:none;
  align-items:center;
  justify-content:center;
  padding:16px;
  backdrop-filter:blur(5px);
}
.modal-overlay.on{display:flex !important;}

/* delete modal */
.modal-sm{background:var(--white);border-radius:var(--r-xl);border:1px solid var(--border-mid);width:420px;max-width:100%;max-height:90vh;overflow-y:auto;box-shadow:var(--sh-lg);animation:modal-in .2s ease}
@keyframes modal-in{from{transform:translateY(16px) scale(.97);opacity:0}to{transform:none;opacity:1}}
.modal-hd{display:flex;align-items:flex-start;justify-content:space-between;padding:24px 24px 20px;border-bottom:1px solid var(--border)}
.modal-hd h3{font-family:var(--fd);font-size:1.15rem;color:var(--dark);margin-bottom:3px}
.modal-hd p{font-size:.74rem;color:var(--muted-light)}
.modal-close{width:30px;height:30px;border-radius:50%;background:var(--blush);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:.9rem;color:var(--muted);transition:all var(--tr);flex-shrink:0;line-height:1}
.modal-close:hover{background:var(--blush-mid);color:var(--rose-deep)}
.modal-body{padding:24px}
.modal-body-center{text-align:center}
.modal-ft-simple{display:flex;gap:8px;justify-content:flex-end;padding:16px 24px 24px;border-top:1px solid var(--border)}

/* ── WIZARD MODAL ── */
.wizard-modal{background:var(--white);border-radius:var(--r-xl);border:1px solid var(--border-mid);width:620px;max-width:100%;max-height:92vh;overflow-y:auto;box-shadow:var(--sh-lg);animation:modal-in .22s ease;position:relative}
.wizard-modal::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,var(--rose-muted),var(--rose),var(--rose-deep),var(--gold));border-radius:var(--r-xl) var(--r-xl) 0 0}
.wiz-hd{padding:24px 28px 20px;border-bottom:1px solid var(--border);display:flex;align-items:flex-start;justify-content:space-between;gap:16px;background:linear-gradient(160deg,#fff 60%,var(--blush) 100%)}
.wiz-hd-left{display:flex;align-items:center;gap:13px}
.wiz-hd-icon{width:42px;height:42px;border-radius:var(--r-lg);background:linear-gradient(135deg,var(--blush-deep),var(--rose-muted));display:flex;align-items:center;justify-content:center;font-size:1.2rem;flex-shrink:0;box-shadow:var(--sh-sm)}
.wiz-hd h3{font-family:var(--fd);font-size:1.15rem;color:var(--dark);margin-bottom:3px;line-height:1.2}
.wiz-hd p{font-size:.72rem;color:var(--muted-light)}
.wiz-steps{display:flex;align-items:center;padding:0 28px;border-bottom:1px solid var(--border);background:var(--blush)}
.wiz-step{display:flex;align-items:center;gap:7px;padding:13px 0;font-size:.66rem;font-weight:600;letter-spacing:.08em;text-transform:uppercase;color:var(--muted-light);flex:1;transition:color var(--tr)}
.wiz-step.active{color:var(--rose-deep)}
.wiz-step.done{color:var(--success)}
.wiz-step-num{width:22px;height:22px;border-radius:50%;border:2px solid var(--border-mid);display:flex;align-items:center;justify-content:center;font-size:.62rem;font-weight:700;flex-shrink:0;transition:all var(--tr);background:var(--white);color:var(--muted-light)}
.wiz-step.active .wiz-step-num{border-color:var(--rose);background:var(--rose);color:var(--white);box-shadow:0 0 0 3px rgba(217,96,112,.18)}
.wiz-step.done .wiz-step-num{border-color:var(--success);background:var(--success);color:var(--white)}
.wiz-connector{height:1px;width:20px;background:var(--border-mid);flex-shrink:0;margin:0 2px;transition:background var(--tr)}
.wiz-connector.done{background:var(--success)}
.wiz-body{padding:26px 28px}
.wiz-panel{display:none}
.wiz-panel.active{display:block;animation:fade-up .2s ease}
@keyframes fade-up{from{opacity:0;transform:translateY(7px)}to{opacity:1;transform:none}}
.wiz-fg{margin-bottom:16px}
.wiz-fg:last-child{margin-bottom:0}
.wiz-label{display:flex;align-items:center;gap:5px;font-size:.6rem;font-weight:700;letter-spacing:.15em;text-transform:uppercase;color:var(--muted);margin-bottom:6px}
.wiz-req{color:var(--rose);font-size:.7rem;line-height:1}
.wiz-row{display:grid;grid-template-columns:1fr 1fr;gap:13px}
.wiz-input{width:100%;padding:10px 14px;font-size:.875rem;color:var(--dark);background:var(--white);border:1.5px solid var(--border-mid);border-radius:var(--r-md);outline:none;transition:border-color var(--tr),box-shadow var(--tr);font-family:inherit}
.wiz-input:focus{border-color:var(--rose-muted);box-shadow:0 0 0 3px rgba(217,96,112,.1)}
.wiz-input.err{border-color:var(--danger);box-shadow:0 0 0 3px rgba(187,51,51,.1)}
.wiz-input.ok{border-color:var(--success);box-shadow:0 0 0 3px rgba(34,136,85,.08)}
.wiz-input::placeholder{color:var(--muted-light)}
.wiz-msg{font-size:.67rem;margin-top:5px;min-height:14px;display:flex;align-items:center;gap:4px}
.wiz-msg.err{color:var(--danger)}
.wiz-msg.ok{color:var(--success)}
.wiz-msg.hint{color:var(--muted-light)}
.pw-wrap{position:relative}
.pw-wrap .wiz-input{padding-right:40px}
.pw-eye{position:absolute;right:11px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--muted-light);font-size:.88rem;padding:3px;line-height:1}
.pw-eye:hover{color:var(--rose-deep)}
.pw-strength{margin-top:7px;display:none}
.pw-bars{display:flex;gap:3px;margin-bottom:4px}
.pw-bar{height:3px;flex:1;border-radius:2px;background:var(--border-mid);transition:background .3s}
.pw-bar.weak{background:#CC4444}
.pw-bar.fair{background:var(--gold)}
.pw-bar.good{background:#55AA77}
.pw-bar.strong{background:var(--success)}
.pw-lbl{font-size:.62rem;color:var(--muted-light);font-weight:500}
.av-preview{display:flex;align-items:center;gap:13px;background:var(--blush);border:1px solid var(--border);border-radius:var(--r-lg);padding:13px 15px;margin-bottom:18px}
.av-circle{width:44px;height:44px;border-radius:50%;background:linear-gradient(135deg,var(--blush-deep),var(--rose-muted));display:flex;align-items:center;justify-content:center;font-size:.95rem;font-weight:700;color:var(--white);flex-shrink:0;border:2px solid var(--white);box-shadow:var(--sh-sm);font-family:var(--fd)}
.av-name{font-family:var(--fd);font-size:.92rem;color:var(--dark);margin-bottom:1px}
.av-email{font-size:.69rem;color:var(--muted-light)}
.av-badge{margin-left:auto;display:inline-flex;align-items:center;padding:3px 10px;border-radius:var(--r-pill);font-size:.58rem;font-weight:700;letter-spacing:.06em;text-transform:uppercase;white-space:nowrap}
.av-badge.empty{background:var(--blush-mid);color:var(--muted-light);border:1px solid var(--border)}
.av-badge.super_admin{background:linear-gradient(135deg,var(--rose),var(--rose-deep));color:var(--white)}
.av-badge.manager{background:#E6F3FF;color:#1455A0;border:1px solid #88C0F0}
.av-badge.inventory{background:#FFF7D6;color:#8C6800;border:1px solid #EDD050}
.av-badge.support{background:#E4FFEE;color:#156038;border:1px solid #68CC88}
.av-badge.marketing{background:#F3E6FF;color:#6A1ABB;border:1px solid #C088F0}
.av-badge.viewer{background:var(--blush-mid);color:var(--muted);border:1px solid var(--border-mid)}
.wiz-divider{display:flex;align-items:center;gap:10px;margin:18px 0;font-size:.62rem;color:var(--muted-light);letter-spacing:.1em;text-transform:uppercase}
.wiz-divider::before,.wiz-divider::after{content:'';flex:1;height:1px;background:var(--border)}
.role-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin-top:2px}
.role-card{border:1.5px solid var(--border-mid);border-radius:var(--r-lg);padding:13px 10px;cursor:pointer;transition:all var(--tr);background:var(--white);text-align:center;position:relative;overflow:hidden;user-select:none}
.role-card:hover{border-color:var(--rose-muted);background:var(--blush)}
.role-card.selected{border-color:var(--rose);background:linear-gradient(160deg,#fff,var(--blush-mid));box-shadow:0 0 0 3px rgba(217,96,112,.12)}
.role-card-check{position:absolute;top:6px;right:6px;width:15px;height:15px;border-radius:50%;background:var(--rose);display:none;align-items:center;justify-content:center;font-size:.52rem;color:var(--white);font-weight:700}
.role-card.selected .role-card-check{display:flex}
.role-card-icon{font-size:1.25rem;margin-bottom:5px;display:block}
.role-card-name{font-size:.7rem;font-weight:700;color:var(--dark);margin-bottom:2px}
.role-card-desc{font-size:.57rem;color:var(--muted-light);line-height:1.4}
.perms-box{background:var(--blush);border:1px solid var(--border);border-radius:var(--r-lg);padding:13px 15px;margin-top:10px;display:none}
.perms-box.show{display:block}
.perms-title{font-size:.58rem;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:var(--muted-light);margin-bottom:8px}
.perms-list{display:flex;flex-wrap:wrap;gap:5px}
.perm-tag{display:inline-flex;align-items:center;gap:4px;font-size:.64rem;font-weight:500;padding:3px 8px;border-radius:var(--r-pill)}
.perm-tag.allow{background:#E4FFEE;color:#156038;border:1px solid #A0DDB8}
.perm-tag.deny{background:#F8F0F0;color:#AC8898;border:1px solid #ECDCE0;text-decoration:line-through;opacity:.55}
.review-grid{display:grid;grid-template-columns:1fr 1fr;gap:9px}
.review-box{background:var(--blush);border:1px solid var(--border);border-radius:var(--r-lg);padding:13px 15px}
.review-box-label{font-size:.57rem;font-weight:700;letter-spacing:.15em;text-transform:uppercase;color:var(--muted-light);margin-bottom:5px}
.review-box-val{font-size:.88rem;font-weight:600;color:var(--dark)}
.success-panel{text-align:center;padding:16px 0 6px}
.success-ring{width:68px;height:68px;border-radius:50%;background:linear-gradient(135deg,#E4FFEE,#B0EED0);border:2px solid #68CC88;display:flex;align-items:center;justify-content:center;font-size:1.7rem;margin:0 auto 14px;box-shadow:0 0 0 6px rgba(34,136,85,.08);animation:pop .4s cubic-bezier(.34,1.56,.64,1)}
@keyframes pop{from{transform:scale(0);opacity:0}to{transform:scale(1);opacity:1}}
.success-title{font-family:var(--fd);font-size:1.25rem;color:var(--dark);margin-bottom:6px}
.success-sub{font-size:.79rem;color:var(--muted-light);line-height:1.6}
.wiz-ft{display:flex;align-items:center;justify-content:space-between;padding:16px 28px 22px;border-top:1px solid var(--border);background:linear-gradient(0deg,var(--blush),var(--white))}
.wiz-ft-left{font-size:.7rem;color:var(--muted-light)}
.wiz-ft-right{display:flex;gap:8px}
@media(max-width:1100px){.settings-grid{grid-template-columns:1fr}}
@media(max-width:900px){.sidebar{display:none}}
@media(max-width:560px){.wiz-row,.role-grid,.review-grid{grid-template-columns:1fr}}
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
  <?php $GLOBALS['NAV_ACTIVE'] = 'settings'; require __DIR__ . '/includes/nav_sidebar.php'; ?>

  <div class="site-content">
    <header class="topbar">
      <div class="topbar-left">
        <span class="topbar-title">Settings</span>
        <span class="topbar-bc">Bejewelry Admin › Settings</span>
      </div>
      <div class="topbar-right">
        <button class="icon-btn" onclick="loadPage()" title="Refresh">↺</button>
      </div>
    </header>

    <div class="content">
      <div class="page-hdr">
        <div><h2>Settings</h2><p>Configure your store preferences and accounts</p></div>
        <div class="page-hdr-actions">
          <button class="btn btn-primary btn-sm" id="btnSaveAll" onclick="saveAllSettings()">✓ Save All Changes</button>
        </div>
      </div>

      <div class="settings-grid">
        <div class="card">
          <div class="card-hd"><h3>Store Settings</h3><span class="card-hd-icon">🏪</span></div>
          <div class="card-body">
            <div class="fg"><label class="flabel">Store Name</label><input class="finput" type="text" id="store_name" placeholder="e.g. Bejewelry"></div>
            <div class="fg"><label class="flabel">Tagline</label><input class="finput" type="text" id="store_tagline" placeholder="e.g. Fine Jewellery · Philippines"></div>
            <div class="fg"><label class="flabel">Currency</label><select class="fselect" id="store_currency"><option value="PHP">Philippine Peso (₱)</option><option value="USD">US Dollar ($)</option><option value="SGD">Singapore Dollar (S$)</option></select></div>
            <div class="fg"><label class="flabel">Contact Email</label><input class="finput" type="email" id="store_email" placeholder="hello@yourdomain.ph"></div>
            <div class="fg"><label class="flabel">Phone</label><input class="finput" type="text" id="store_phone" placeholder="+63 9XX XXX XXXX"></div>
            <button class="btn btn-ghost btn-sm" id="btnSaveStore" onclick="saveStoreSettings()">Save Changes</button>
          </div>
        </div>

        <div class="card">
          <div class="card-hd"><h3>My Account</h3><span class="card-hd-icon">👤</span></div>
          <div class="card-body">
            <div class="admin-profile">
              <div class="admin-av" id="adminAvInitials">—</div>
              <div><div class="admin-av-name" id="adminName">Loading…</div><div class="admin-av-role" id="adminRoleSince">—</div></div>
            </div>
            <div class="fg"><label class="flabel">Display Name</label><input class="finput" type="text" id="acc_name" placeholder="Your full name"></div>
            <div class="fg"><label class="flabel">Email</label><input class="finput" type="email" id="acc_email" placeholder="admin@yourdomain.ph"></div>
            <div class="fg"><label class="flabel">New Password</label><input class="finput" type="password" id="acc_password" placeholder="Leave blank to keep current"><div class="fhint">Minimum 8 characters recommended</div></div>
            <button class="btn btn-ghost btn-sm" id="btnSaveAccount" onclick="saveAccountSettings()">Update Account</button>
          </div>
        </div>

        <div class="card">
          <div class="card-hd"><h3>Password Policy</h3><span class="card-hd-icon">🔐</span></div>
          <div class="card-body">
            <div class="fg">
              <label class="flabel">Enable Strong Password Policy</label>
              <div style="display:flex;align-items:center;gap:12px">
                <div style="flex:1">
                  <div class="fhint">Enforce required complexity for new passwords and password changes.</div>
                </div>
                <button class="toggle" id="tog_pw_policy"></button>
              </div>
            </div>
            <div class="fg">
              <label class="flabel">Minimum Length</label>
              <input class="finput" type="number" id="pw_min_length" min="6" max="64" value="12">
              <div class="fhint">Minimum number of characters required (recommended 12)</div>
            </div>
            <div class="frow" style="margin-top:10px">
              <div class="fg">
                <label class="flabel">Require Uppercase</label>
                <div style="display:flex;align-items:center;justify-content:space-between"><div class="fhint">At least one capital letter</div><button class="toggle" id="tog_pw_upper"></button></div>
              </div>
              <div class="fg">
                <label class="flabel">Require Lowercase</label>
                <div style="display:flex;align-items:center;justify-content:space-between"><div class="fhint">At least one small letter</div><button class="toggle" id="tog_pw_lower"></button></div>
              </div>
            </div>
            <div class="frow" style="margin-top:6px">
              <div class="fg">
                <label class="flabel">Require Number</label>
                <div style="display:flex;align-items:center;justify-content:space-between"><div class="fhint">At least one digit</div><button class="toggle" id="tog_pw_number"></button></div>
              </div>
              <div class="fg">
                <label class="flabel">Require Special Char</label>
                <div style="display:flex;align-items:center;justify-content:space-between"><div class="fhint">At least one symbol (e.g. !@#$%)</div><button class="toggle" id="tog_pw_special"></button></div>
              </div>
            </div>
            <div class="fg" style="margin-top:10px">
              <label class="flabel">Password Expiration (days)</label>
              <input class="finput" type="number" id="pw_exp_days" min="0" max="999" value="0">
              <div class="fhint">Set to 0 to disable expiration</div>
            </div>
            <button class="btn btn-ghost btn-sm" style="margin-top:12px" id="btnSavePasswordPolicy" onclick="savePasswordPolicySettings()">Save Password Policy</button>
          </div>
        </div>

        <div class="card card-full">
          <div class="card-hd">
            <div>
              <h3>Admin Accounts</h3>
              <p style="font-size:.72rem;color:var(--muted-light);margin-top:2px">Manage who has access to this admin panel</p>
            </div>
            <button class="btn btn-primary btn-sm" id="btnOpenAddAdmin">＋ Add Admin</button>
          </div>
          <div class="admin-table-wrap">
            <table class="admin-table">
              <thead><tr><th>Admin</th><th>Role</th><th>Date Added</th><th>Status</th><th style="width:100px"></th></tr></thead>
              <tbody id="adminAccountsList">
                <tr><td colspan="5"><div class="skel" style="height:13px;margin:14px 20px;width:55%;border-radius:4px"></div></td></tr>
                <tr><td colspan="5"><div class="skel" style="height:13px;margin:14px 20px;width:38%;border-radius:4px"></div></td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ADD ADMIN WIZARD -->
<div id="addAdminModal" class="modal-overlay">
  <div class="wizard-modal">
    <div class="wiz-hd">
      <div class="wiz-hd-left">
        <div class="wiz-hd-icon">👤</div>
        <div><h3>Add Admin Account</h3><p>Create a new user with panel access</p></div>
      </div>
      <button class="modal-close" id="btnCloseWiz">✕</button>
    </div>
    <div class="wiz-steps" id="wizStepsBar">
      <div class="wiz-step active" id="wiz-ind-1"><div class="wiz-step-num" id="wiz-num-1">1</div><span>Identity</span></div>
      <div class="wiz-connector" id="wiz-conn-1"></div>
      <div class="wiz-step" id="wiz-ind-2"><div class="wiz-step-num" id="wiz-num-2">2</div><span>Role</span></div>
      <div class="wiz-connector" id="wiz-conn-2"></div>
      <div class="wiz-step" id="wiz-ind-3"><div class="wiz-step-num" id="wiz-num-3">3</div><span>Security</span></div>
      <div class="wiz-connector" id="wiz-conn-3"></div>
      <div class="wiz-step" id="wiz-ind-4"><div class="wiz-step-num" id="wiz-num-4">4</div><span>Review</span></div>
    </div>
    <div class="wiz-body">
      <!-- Step 1 -->
      <div class="wiz-panel active" id="wiz-panel-1">
        <div class="wiz-row">
          <div class="wiz-fg">
            <label class="wiz-label">Full Name <span class="wiz-req">*</span></label>
            <input class="wiz-input" type="text" id="wiz_name" placeholder="e.g. Maria Santos" autocomplete="off">
            <div class="wiz-msg hint" id="wiz_msg_name">Enter first and last name</div>
          </div>
          <div class="wiz-fg">
            <label class="wiz-label">Email Address <span class="wiz-req">*</span></label>
            <input class="wiz-input" type="email" id="wiz_email" placeholder="maria@bejewelry.ph" autocomplete="off">
            <div class="wiz-msg hint" id="wiz_msg_email">Must be a valid email address</div>
          </div>
        </div>
        <div class="wiz-divider">Preview</div>
        <div class="av-preview">
          <div class="av-circle" id="wiz_av_initials">?</div>
          <div><div class="av-name" id="wiz_av_name">New Admin</div><div class="av-email" id="wiz_av_email">email not set</div></div>
          <span class="av-badge empty" id="wiz_av_badge">No role yet</span>
        </div>
      </div>
      <!-- Step 2 -->
      <div class="wiz-panel" id="wiz-panel-2">
        <div class="wiz-fg">
          <label class="wiz-label">Select Role <span class="wiz-req">*</span></label>
          <div class="role-grid" id="roleGrid">
            <div class="role-card" data-role="super_admin"><div class="role-card-check">✓</div><span class="role-card-icon">👑</span><div class="role-card-name">Super Admin</div><div class="role-card-desc">Customer accounts, account unlocks, inventory, staff &amp; audit — no orders or catalog edits</div></div>
            <div class="role-card" data-role="manager"><div class="role-card-check">✓</div><span class="role-card-icon">🎫</span><div class="role-card-name">Order Manager</div><div class="role-card-desc">Orders, shipping, tickets, review ratings &amp; promotions</div></div>
            <div class="role-card" data-role="inventory"><div class="role-card-check">✓</div><span class="role-card-icon">📦</span><div class="role-card-name">Inventory Manager</div><div class="role-card-desc">Stock, products, reports — no orders or customer accounts</div></div>
          </div>
          <div class="wiz-msg hint" id="wiz_msg_role" style="margin-top:7px">Choose the access level for this admin</div>
        </div>
        <div class="perms-box" id="wiz_perms">
          <div class="perms-title">Access Permissions</div>
          <div class="perms-list" id="wiz_perms_list"></div>
        </div>
      </div>
      <!-- Step 3 -->
      <div class="wiz-panel" id="wiz-panel-3">
        <div class="wiz-fg">
          <label class="wiz-label">Password <span class="wiz-req">*</span></label>
          <div class="pw-wrap">
            <input class="wiz-input" type="password" id="wiz_pw" placeholder="Minimum 8 characters" autocomplete="new-password">
            <button class="pw-eye" type="button" id="wiz_eye1">👁</button>
          </div>
          <div class="pw-strength" id="wiz_pw_strength">
            <div class="pw-bars"><div class="pw-bar" id="wiz_bar1"></div><div class="pw-bar" id="wiz_bar2"></div><div class="pw-bar" id="wiz_bar3"></div><div class="pw-bar" id="wiz_bar4"></div></div>
            <span class="pw-lbl" id="wiz_pw_lbl">Too short</span>
          </div>
          <div class="wiz-msg hint" id="wiz_msg_pw">Use letters, numbers, and symbols</div>
        </div>
        <div class="wiz-fg">
          <label class="wiz-label">Confirm Password <span class="wiz-req">*</span></label>
          <div class="pw-wrap">
            <input class="wiz-input" type="password" id="wiz_pw2" placeholder="Re-enter password" autocomplete="new-password">
            <button class="pw-eye" type="button" id="wiz_eye2">👁</button>
          </div>
          <div class="wiz-msg hint" id="wiz_msg_pw2">Must match the password above</div>
        </div>
      </div>
      <!-- Step 4 -->
      <div class="wiz-panel" id="wiz-panel-4">
        <div class="av-preview" style="margin-bottom:18px">
          <div class="av-circle" id="rev_initials">?</div>
          <div><div class="av-name" id="rev_name">—</div><div class="av-email" id="rev_email">—</div></div>
          <span class="av-badge empty" id="rev_badge">—</span>
        </div>
        <div class="review-grid">
          <div class="review-box"><div class="review-box-label">Full Name</div><div class="review-box-val" id="rev_name2">—</div></div>
          <div class="review-box"><div class="review-box-label">Email</div><div class="review-box-val" id="rev_email2" style="word-break:break-all">—</div></div>
          <div class="review-box"><div class="review-box-label">Role</div><div class="review-box-val" id="rev_role2">—</div></div>
          <div class="review-box"><div class="review-box-label">Password</div><div class="review-box-val">••••••••</div></div>
        </div>
        <div style="margin-top:12px;background:#FFF7D6;border:1px solid #EDD050;border-radius:var(--r-md);padding:10px 13px;font-size:.73rem;color:#8C6800;display:flex;align-items:flex-start;gap:7px;line-height:1.55">
          <span style="flex-shrink:0">⚠️</span><span>Please review all details before creating this account. The new admin will be able to log in immediately.</span>
        </div>
      </div>
      <!-- Success -->
      <div class="wiz-panel" id="wiz-panel-success">
        <div class="success-panel">
          <div class="success-ring">✓</div>
          <div class="success-title">Account Created!</div>
          <p class="success-sub"><strong id="wiz_succ_name">—</strong> has been added as<br><span id="wiz_succ_role">—</span> and can now log in.</p>
        </div>
      </div>
    </div>
    <div class="wiz-ft">
      <div class="wiz-ft-left" id="wiz_counter">Step 1 of 4</div>
      <div class="wiz-ft-right">
        <button class="btn btn-ghost btn-sm" id="wiz_btn_back" style="display:none">← Back</button>
        <button class="btn btn-primary btn-sm" id="wiz_btn_next">Continue →</button>
      </div>
    </div>
  </div>
</div>

<!-- DELETE ADMIN MODAL -->
<div id="deleteAdminModal" class="modal-overlay">
  <div class="modal-sm">
    <div class="modal-hd">
      <div><h3>Remove Admin?</h3><p>This action cannot be undone</p></div>
      <button class="modal-close" id="btnCloseDelete">✕</button>
    </div>
    <div class="modal-body modal-body-center">
      <div style="font-size:2.5rem;margin-bottom:12px">🗑️</div>
      <p style="font-size:.88rem;color:var(--dark);margin-bottom:8px">You're about to remove <strong id="deleteAdminName">this admin</strong>.</p>
      <p style="font-size:.76rem;color:var(--muted-light)">They will lose all access to the admin panel immediately.</p>
    </div>
    <div class="modal-ft-simple">
      <button class="btn btn-ghost btn-sm" id="btnCancelDelete">Cancel</button>
      <button class="btn btn-danger btn-sm" id="btnConfirmDelete">Remove Admin</button>
    </div>
    <input type="hidden" id="deleteAdminId">
  </div>
</div>

<div class="toast" id="toast"></div>

<script src="confirm_modal.js?v=1"></script>
<script>
// ═══════════════════════════════════════════════
// BEJEWELRY SETTINGS — Fully inline JS
// ═══════════════════════════════════════════════
const API_BASE = '../api';

function $(id){ return document.getElementById(id); }

function toast(msg, type, dur){
  dur = dur || 2800;
  var t = $('toast'); if(!t) return;
  var ic = {success:'✓', error:'✕'};
  t.innerHTML = '<span>'+(ic[type]||'ℹ')+'</span> '+msg;
  t.className = 'toast on'+(type?' '+type:'');
  setTimeout(function(){ t.className='toast'; }, dur);
}

function setLoading(on){
  var b = $('loadingBar'); if(b) b.classList.toggle('active', on);
}

function escHtml(s){
  var d = document.createElement('div');
  d.textContent = s||'';
  return d.innerHTML;
}

async function apiFetch(ep){
  var r = await fetch(API_BASE+'/'+ep);
  if(!r.ok) throw new Error(r.status);
  return r.json();
}

async function apiPost(ep, data){
  var csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
  var r = await fetch(API_BASE+'/'+ep, {
    method:'POST',
    headers:{'Content-Type':'application/json', ...(csrfToken ? {'X-CSRF-Token': csrfToken} : {})},
    body:JSON.stringify(data)
  });
  if(!r.ok) throw new Error(r.status);
  return r.json();
}

function setToggle(id, on){
  var e = $(id); if(e) e.classList.toggle('on', !!on);
}
function getToggle(id){
  var e = $(id); return e ? e.classList.contains('on') : false;
}

// ── INIT ──
window.addEventListener('DOMContentLoaded', function(){

  // Toggle switches
  document.querySelectorAll('.toggle').forEach(function(t){
    t.addEventListener('click', function(){ this.classList.toggle('on'); });
  });

  // Overlay click-to-close
  document.querySelectorAll('.modal-overlay').forEach(function(ov){
    ov.addEventListener('click', function(e){
      if(e.target === ov) ov.classList.remove('on');
    });
  });

  // Search
  var gs = $('globalSearch');
  if(gs){
    var st;
    gs.addEventListener('input', function(e){
      clearTimeout(st);
      var q = e.target.value.trim();
      if(q.length < 2) return;
      st = setTimeout(function(){ window.location.href='search.php?q='+encodeURIComponent(q); }, 500);
    });
  }

  // Add Admin button
  $('btnOpenAddAdmin').addEventListener('click', openAddAdminModal);

  // Wizard close/back/next
  $('btnCloseWiz').addEventListener('click', closeAddAdminModal);
  $('wiz_btn_back').addEventListener('click', wizPrev);
  $('wiz_btn_next').addEventListener('click', wizNext);

  // Wizard inputs
  $('wiz_name').addEventListener('input', wizOnName);
  $('wiz_email').addEventListener('input', wizOnEmail);
  $('wiz_pw').addEventListener('input', wizOnPw);
  $('wiz_pw2').addEventListener('input', wizOnPw2);

  // Password eye toggles
  $('wiz_eye1').addEventListener('click', function(){ wizTogglePw('wiz_pw','wiz_eye1'); });
  $('wiz_eye2').addEventListener('click', function(){ wizTogglePw('wiz_pw2','wiz_eye2'); });

  // Role cards
  document.querySelectorAll('.role-card').forEach(function(card){
    card.addEventListener('click', function(){
      wizSelectRole(this.dataset.role, this);
    });
  });

  // Delete modal
  $('btnCloseDelete').addEventListener('click', closeDeleteAdminModal);
  $('btnCancelDelete').addEventListener('click', closeDeleteAdminModal);
  $('btnConfirmDelete').addEventListener('click', handleDeleteAdmin);
  $('adminAccountsList').addEventListener('click', function(e){
    var btn = e.target && e.target.closest ? e.target.closest('.js-remove-admin') : null;
    if(!btn) return;
    openDeleteAdminModal(btn.getAttribute('data-admin-id') || '', btn.getAttribute('data-admin-name') || 'this admin');
  });

  loadPage();
});

// ── USER / BADGES / SETTINGS LOADERS ──
async function loadUser(){
  try{
    var r = await fetch('../api/auth/session.php');
    if(!r.ok) throw new Error(r.status);
    var d = await r.json();
    var ini = (d.name||'?').split(' ').map(function(w){return w[0];}).join('').slice(0,2).toUpperCase();
    $('sbAvatar').textContent = ini;
    $('sbUsername').textContent = d.name||'Admin';
    $('sbUserRole').textContent = d.role||'';
    $('adminAvInitials').textContent = ini;
    $('adminName').textContent = d.name||'—';
    $('adminRoleSince').textContent = (d.role||'Admin')+' · Since '+(d.since||'—');
    $('acc_name').value = d.name||'';
    $('acc_email').value = d.email||'';
  }catch(e){ $('sbUsername').textContent='Admin'; }
}
async function loadBadges(){
  try{
    var d = await apiFetch('dashboard/badges.php');
    if(d.pending_orders) $('badgeOrders').textContent=d.pending_orders;
    if(d.new_products) $('badgeProducts').textContent=d.new_products;
    if(d.low_stock) $('badgeInventory').textContent=d.low_stock;
    if(d.pending_reviews) $('badgeReviews').textContent=d.pending_reviews;
  }catch(e){}
}
async function loadStoreSettings(){
  try{
    var r = await fetch('settings/store.php'); if(!r.ok) throw new Error(r.status);
    var d = await r.json();
    $('store_name').value=d.store_name||''; $('store_tagline').value=d.tagline||'';
    $('store_currency').value=d.currency||'PHP'; $('store_email').value=d.contact_email||'';
    $('store_phone').value=d.phone||'';
  }catch(e){}
}
async function saveStoreSettings(){
  var btn=$('btnSaveStore'); btn.disabled=true; btn.textContent='Saving…';
  try{
    var csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    var r = await fetch('settings/store_save.php',{method:'POST',headers:{'Content-Type':'application/json', ...(csrfToken ? {'X-CSRF-Token': csrfToken} : {})},body:JSON.stringify({
      store_name:$('store_name').value.trim(), tagline:$('store_tagline').value.trim(),
      currency:$('store_currency').value, contact_email:$('store_email').value.trim(),
      phone:$('store_phone').value.trim()
    })}); if(!r.ok) throw new Error(r.status);
    var res = await r.json(); if(!res||res.ok===false) throw new Error('fail');
    toast('Store settings saved.','success');
  }catch(e){ toast('Failed to save store settings.','error'); }
  finally{ btn.disabled=false; btn.textContent='Save Changes'; }
}
async function loadShippingSettings(){
  try{
    var r = await fetch('settings/shipping.php'); if(!r.ok) throw new Error(r.status);
    var d = await r.json();
    $('ship_fee').value=d.shipping_fee||''; $('ship_threshold').value=d.free_ship_threshold||'';
    $('ship_tax').value=d.tax_rate||''; $('ship_carrier').value=d.carrier||'';
    setToggle('tog_cod',d.cod_enabled); setToggle('tog_sameday',d.same_day_enabled);
  }catch(e){}
}
async function saveShippingSettings(){
  var btn=$('btnSaveShipping'); btn.disabled=true; btn.textContent='Saving…';
  try{
    var csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    var r = await fetch('settings/shipping_save.php',{method:'POST',headers:{'Content-Type':'application/json', ...(csrfToken ? {'X-CSRF-Token': csrfToken} : {})},body:JSON.stringify({
      shipping_fee:$('ship_fee').value, free_ship_threshold:$('ship_threshold').value,
      tax_rate:$('ship_tax').value, carrier:$('ship_carrier').value,
      cod_enabled:getToggle('tog_cod'), same_day_enabled:getToggle('tog_sameday')
    })}); if(!r.ok) throw new Error(r.status);
    var res = await r.json(); if(!res||res.ok===false) throw new Error('fail');
    toast('Shipping settings saved.','success');
  }catch(e){ toast('Failed to save shipping settings.','error'); }
  finally{ btn.disabled=false; btn.textContent='Save Changes'; }
}
async function loadPaymentSettings(){
  try{
    var r = await fetch('settings/payment.php'); if(!r.ok) throw new Error(r.status);
    var d = await r.json();
    setToggle('tog_gcash',d.gcash); setToggle('tog_maya',d.maya);
    setToggle('tog_card',d.card); setToggle('tog_cod2',d.cod); setToggle('tog_bank',d.bank_transfer);
  }catch(e){}
}
async function savePaymentSettings(){
  var btn=$('btnSavePayment'); btn.disabled=true; btn.textContent='Saving…';
  try{
    var csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    var r = await fetch('settings/payment_save.php',{method:'POST',headers:{'Content-Type':'application/json', ...(csrfToken ? {'X-CSRF-Token': csrfToken} : {})},body:JSON.stringify({
      gcash:getToggle('tog_gcash'), maya:getToggle('tog_maya'), card:getToggle('tog_card'),
      cod:getToggle('tog_cod2'), bank_transfer:getToggle('tog_bank')
    })}); if(!r.ok) throw new Error(r.status);
    var res = await r.json(); if(!res||res.ok===false) throw new Error('fail');
    toast('Payment settings saved.','success');
  }catch(e){ toast('Failed to save.','error'); }
  finally{ btn.disabled=false; btn.textContent='Save Changes'; }
}
async function loadPasswordPolicySettings(){
  try{
    var r = await fetch('settings/password.php'); if(!r.ok) throw new Error(r.status);
    var d = await r.json();
    setToggle('tog_pw_policy', d.enabled);
    $('pw_min_length').value = d.min_length || 12;
    setToggle('tog_pw_upper', d.require_upper);
    setToggle('tog_pw_lower', d.require_lower);
    setToggle('tog_pw_number', d.require_number);
    setToggle('tog_pw_special', d.require_special);
    $('pw_exp_days').value = d.expiration_days || 0;
  }catch(e){ }
}

async function savePasswordPolicySettings(){
  var btn=$('btnSavePasswordPolicy'); btn.disabled=true; btn.textContent='Saving…';
  try{
    var payload = {
      enabled: getToggle('tog_pw_policy'),
      min_length: parseInt($('pw_min_length').value,10)||12,
      require_upper: getToggle('tog_pw_upper'),
      require_lower: getToggle('tog_pw_lower'),
      require_number: getToggle('tog_pw_number'),
      require_special: getToggle('tog_pw_special'),
      expiration_days: parseInt($('pw_exp_days').value,10)||0
    };
    var csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    var r = await fetch('settings/password_save.php',{method:'POST',headers:{'Content-Type':'application/json', ...(csrfToken ? {'X-CSRF-Token': csrfToken} : {})},body:JSON.stringify(payload)});
    if(!r.ok) throw new Error(r.status);
    var res = await r.json(); if(!res||res.ok===false) throw new Error('fail');
    toast('Password policy saved.','success');
  }catch(e){ toast('Failed to save password policy.','error'); }
  finally{ btn.disabled=false; btn.textContent='Save Password Policy'; }
}
async function saveAccountSettings(){
  var btn=$('btnSaveAccount'); btn.disabled=true; btn.textContent='Saving…';
  try{
    var payload={name:$('acc_name').value.trim(), email:$('acc_email').value.trim()};
    var pw=$('acc_password').value;
    if(pw){
      var check = validatePasswordClientSide(pw);
      if(!check.ok) throw new Error(check.errors.join('\n'));
      payload.password=pw;
    }
    await apiPost('settings/account_save.php',payload);
    $('acc_password').value=''; toast('Account updated!','success'); loadUser();
  }catch(e){ toast('Failed to update account.','error'); }
  finally{ btn.disabled=false; btn.textContent='Update Account'; }
}
async function saveAllSettings(){
  var btn=$('btnSaveAll'); btn.disabled=true; btn.textContent='Saving…';
  try{
    await Promise.all([saveStoreSettings(),saveShippingSettings(),savePaymentSettings()]);
    toast('All settings saved!','success');
  }catch(e){ toast('Some settings could not be saved.','error'); }
  finally{ btn.disabled=false; btn.textContent='✓ Save All Changes'; }
}

// ── ADMIN TABLE ──
var ROLE_LABELS={super_admin:'Super Admin',manager:'Order Manager',inventory:'Inventory Manager',courier:'Courier'};

function accountStatusHtml(a){
  if (a && a.is_locked) {
    return '<span class="admin-status-locked">Locked</span>';
  }
  return '<span class="admin-status-active">Active</span>';
}

function accountLockActionHtml(a){
  if (!a || !a.can_toggle_lock || a.is_current) {
    return '<span style="font-size:.72rem;color:var(--muted-light)">—</span>';
  }
  var label = a.is_locked ? 'Unlock' : 'Lock';
  var style = a.is_locked
    ? 'padding:5px 12px;font-size:.65rem'
    : 'color:var(--danger);border-color:#EEAAAA;padding:5px 12px;font-size:.65rem';
  return '<button class="btn btn-ghost btn-sm" style="' + style + '" data-id="' + escHtml(String(a.id)) + '" data-action="' + escHtml(a.is_locked ? 'unlock' : 'lock') + '" onclick="handleAdminLockAction(this)">' + escHtml(label) + '</button>';
}

function handleAdminLockAction(btn) {
  if (!btn) return;
  toggleAdminLock(parseInt(btn.dataset.id, 10), btn.dataset.action);
}

async function loadAdminAccounts(){
  var tbody=$('adminAccountsList');
  try{
    var r = await fetch('settings/admins.php');
    if(!r.ok) throw new Error(r.status);
    var data = await r.json();
    if(!data||data.length===0){
      tbody.innerHTML='<tr><td colspan="5" style="text-align:center;padding:40px;color:var(--muted-light);font-size:.82rem">No admin accounts found.</td></tr>';
      return;
    }
    tbody.innerHTML=data.map(function(a){
      var ini=(a.name||'?').split(' ').map(function(w){return w[0];}).join('').slice(0,2).toUpperCase();
      var rl=ROLE_LABELS[a.role]||a.role;
      var date=a.created_at?new Date(a.created_at).toLocaleDateString('en-PH',{year:'numeric',month:'short',day:'numeric'}):'—';
      var you=a.is_current?'<span class="you-badge">You</span>':'';
      var actions = '<span style="font-size:.72rem;color:var(--muted-light)">—</span>';
      if(a.can_edit_role){
        var opts = ['super_admin','manager','inventory'].map(function(v){
          var lbl = ROLE_LABELS[v] || v;
          return '<option value="'+escHtml(v)+'"'+(String(a.role)===String(v)?' selected':'')+'>'+escHtml(lbl)+'</option>';
        }).join('');
        var rmvBtn = a.can_remove
          ? '<button class="btn btn-ghost btn-sm js-remove-admin" style="color:var(--danger);border-color:#EEAAAA;padding:5px 12px;font-size:.65rem" data-admin-id="'+escHtml(String(a.id))+'" data-admin-name="'+escHtml(a.name)+'">Remove</button>'
          : '';
        actions =
          '<div style="display:flex;gap:6px;align-items:center;justify-content:flex-end">' +
            '<select class="fselect" style="padding:6px 10px;font-size:.72rem;border-radius:10px" id="roleSel_'+escHtml(String(a.id))+'">'+opts+'</select>' +
            '<button class="btn btn-ghost btn-sm" style="padding:5px 12px;font-size:.65rem" onclick="updateAdminRole('+escHtml(String(a.id))+')">Save</button>' +
            rmvBtn +
            accountLockActionHtml(a) +
          '</div>';
      } else if(a.can_remove) {
        actions = '<div style="display:flex;gap:6px;align-items:center;justify-content:flex-end"><button class="btn btn-ghost btn-sm js-remove-admin" style="color:var(--danger);border-color:#EEAAAA;padding:5px 12px;font-size:.65rem" data-admin-id="'+escHtml(String(a.id))+'" data-admin-name="'+escHtml(a.name)+'">Remove</button>'+accountLockActionHtml(a)+'</div>';
      } else {
        actions = accountLockActionHtml(a);
      }
      return '<tr><td><div class="admin-cell-user"><div class="admin-row-av">'+escHtml(ini)+'</div><div><div class="admin-row-name">'+escHtml(a.name)+you+'</div><div class="admin-row-email">'+escHtml(a.email)+'</div></div></div></td><td><span class="role-badge role-'+escHtml(a.role)+'">'+escHtml(rl)+'</span></td><td style="color:var(--muted);font-size:.78rem">'+date+'</td><td>'+accountStatusHtml(a)+'</td><td>'+actions+'</td></tr>';
    }).join('');
  }catch(e){
    tbody.innerHTML='<tr><td colspan="5" style="text-align:center;padding:32px;color:var(--muted-light);font-size:.8rem">Could not load admin accounts.</td></tr>';
  }
}

async function updateAdminRole(id){
  var sel = document.getElementById('roleSel_'+id);
  if(!sel) return;
  var role = sel.value;
  try{
    var r = await fetch('settings/admins_update_role.php', {
      method:'POST',
      headers:{'Content-Type':'application/json'},
      body:JSON.stringify({id:id, role:role})
    });
    if(!r.ok) throw new Error(r.status);
    var res = await r.json();
    if(!res || res.ok===false) throw new Error('fail');
    toast('Role updated.','success');
    loadAdminAccounts();
  }catch(e){
    toast('Could not update role.','error');
  }
}

async function toggleAdminLock(id, action){
  try{
    var r = await fetch('settings/account_lock.php', {
      method:'POST',
      headers:{'Content-Type':'application/json'},
      body:JSON.stringify({id:id, action:action})
    });
    if(!r.ok) throw new Error(r.status);
    var res = await r.json();
    if(!res || res.ok===false) throw new Error('fail');
    toast(action === 'unlock' ? 'Account unlocked.' : 'Account locked.','success');
    loadAdminAccounts();
  }catch(e){
    toast('Could not update account lock state.','error');
  }
}

function openDeleteAdminModal(id, name){
  $('deleteAdminId').value=id;
  $('deleteAdminName').textContent=name;
  $('deleteAdminModal').classList.add('on');
}
function closeDeleteAdminModal(){
  $('deleteAdminModal').classList.remove('on');
}
async function handleDeleteAdmin(){
  var id=$('deleteAdminId').value;
  var btn=$('btnConfirmDelete'); btn.disabled=true; btn.textContent='Removing…';
  try{
    var r = await fetch('settings/admins_delete.php', {
      method:'POST',
      headers:{'Content-Type':'application/json'},
      body:JSON.stringify({id:id})
    });
    if(!r.ok) throw new Error(r.status);
    var res = await r.json();
    if(!res || res.ok===false) throw new Error('fail');
    closeDeleteAdminModal(); toast('Admin account removed.',''); loadAdminAccounts();
  }catch(e){ toast('Failed to remove admin.','error'); }
  finally{ btn.disabled=false; btn.textContent='Remove Admin'; }
}

function handleLogout(){
  if (typeof window.adminConfirm === 'function') {
    window.adminConfirm('Log out of Bejewelry Admin?', function () { window.location.href = '../logout.php'; }, { okText: 'Log out' });
    return;
  }
  window.location.href='../logout.php';
}

// ═══════════════════════════════════════════════
// WIZARD
// ═══════════════════════════════════════════════
var WIZ_TOTAL=4, wizStep=1, wizRole='';

var WIZ_PERMS={
  super_admin:{
    allow:['Customer accounts','Account unlocks','Inventory','Settings & staff','Audit log'],
    deny:['Orders','Products','Tickets','Review ratings','Promotions','Reports']
  },
  manager:{
    allow:['Dashboard','Orders & shipping','Tickets','Review ratings','Promotions','Notifications'],
    deny:['Customer accounts','Inventory','Products','Reports','Settings','Audit log']
  },
  inventory:{
    allow:['Dashboard','Inventory','Products','Reports','Notifications'],
    deny:['Orders','Customer accounts','Tickets','Review ratings','Promotions','Settings','Audit log']
  }
};

function openAddAdminModal(){
  wizReset();
  $('addAdminModal').classList.add('on');
}
function closeAddAdminModal(){
  $('addAdminModal').classList.remove('on');
  setTimeout(wizReset, 300);
}

function wizReset(){
  wizStep=1; wizRole='';
  ['wiz_name','wiz_email','wiz_pw','wiz_pw2'].forEach(function(id){
    var el=$(id); if(el){ el.value=''; el.className='wiz-input'; }
  });
  document.querySelectorAll('.role-card').forEach(function(c){ c.classList.remove('selected'); });
  $('wiz_perms').classList.remove('show');
  $('wiz_pw_strength').style.display='none';
  $('wizStepsBar').style.display='';
  var bn=$('wiz_btn_next');
  bn.textContent='Continue →'; bn.style.background=''; bn.style.boxShadow=''; bn.disabled=false;
  $('wiz_btn_back').style.display='none';
  $('wiz_counter').textContent='Step 1 of 4';
  wizResetMsgs(); wizUpdateAvatarPreview(); wizShowPanel(1); wizUpdateStepUI();
}

function wizShowPanel(n){
  document.querySelectorAll('.wiz-panel').forEach(function(p){ p.classList.remove('active'); });
  var id=(n==='success')?'wiz-panel-success':'wiz-panel-'+n;
  var el=$(id); if(el) el.classList.add('active');
}

function wizUpdateStepUI(){
  for(var i=1;i<=WIZ_TOTAL;i++){
    var ind=$('wiz-ind-'+i), num=$('wiz-num-'+i);
    if(!ind) continue;
    ind.classList.remove('active','done');
    if(i<wizStep){ ind.classList.add('done'); num.textContent='✓'; }
    else if(i===wizStep){ ind.classList.add('active'); num.textContent=i; }
    else{ num.textContent=i; }
  }
  for(var j=1;j<=3;j++){
    var c=$('wiz-conn-'+j); if(c) c.classList.toggle('done', j<wizStep);
  }
  $('wiz_btn_back').style.display = wizStep>1?'inline-flex':'none';
  var bn=$('wiz_btn_next');
  if(wizStep===WIZ_TOTAL){
    bn.textContent='✓ Create Account';
    bn.style.background='linear-gradient(135deg,#228855,#156038)';
    bn.style.boxShadow='0 4px 14px rgba(34,136,85,.28)';
  } else {
    bn.textContent='Continue →'; bn.style.background=''; bn.style.boxShadow='';
  }
  $('wiz_counter').textContent='Step '+wizStep+' of '+WIZ_TOTAL;
}

function wizNext(){
  if(!wizValidate(wizStep)) return;
  if(wizStep===WIZ_TOTAL){ wizSubmit(); return; }
  wizStep++;
  if(wizStep===WIZ_TOTAL) wizBuildReview();
  wizShowPanel(wizStep); wizUpdateStepUI();
}
function wizPrev(){
  if(wizStep<=1) return;
  wizStep--; wizShowPanel(wizStep); wizUpdateStepUI();
}

function wizValidate(step){
  if(step===1){
    var name=$('wiz_name').value.trim(), email=$('wiz_email').value.trim(), ok=true;
    if(!name||name.split(' ').filter(Boolean).length<2){
      wizSetMsg('wiz_msg_name','err','⚠ Enter a full name (first & last)');
      $('wiz_name').classList.add('err'); ok=false;
    }
    if(!email||!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)){
      wizSetMsg('wiz_msg_email','err','⚠ Enter a valid email address');
      $('wiz_email').classList.add('err'); ok=false;
    }
    return ok;
  }
  if(step===2){
    if(!wizRole){ wizSetMsg('wiz_msg_role','err','⚠ Please select a role'); toast('Please select a role.','error'); return false; }
    return true;
  }
  if(step===3){
    var pw=$('wiz_pw').value, pw2=$('wiz_pw2').value, ok=true;
    if(pw.length<8){ wizSetMsg('wiz_msg_pw','err','⚠ Password must be at least 8 characters'); $('wiz_pw').classList.add('err'); ok=false; }
    if(!pw2||pw!==pw2){ wizSetMsg('wiz_msg_pw2','err','⚠ Passwords do not match'); $('wiz_pw2').classList.add('err'); ok=false; }
    return ok;
  }
  return true;
}

function wizSetMsg(id,type,text){ var el=$(id); if(!el)return; el.className='wiz-msg '+type; el.textContent=text; }
function wizResetMsgs(){
  var d={wiz_msg_name:'Enter first and last name',wiz_msg_email:'Must be a valid email address',wiz_msg_pw:'Use letters, numbers, and symbols',wiz_msg_pw2:'Must match the password above',wiz_msg_role:'Choose the access level for this admin'};
  Object.keys(d).forEach(function(id){ var el=$(id); if(el){ el.className='wiz-msg hint'; el.textContent=d[id]; } });
}

function wizOnName(){
  var val=$('wiz_name').value.trim(), el=$('wiz_name');
  el.classList.remove('err','ok');
  if(val.split(' ').filter(Boolean).length>=2){ el.classList.add('ok'); wizSetMsg('wiz_msg_name','ok','✓ Looks good'); }
  else wizSetMsg('wiz_msg_name','hint','Enter first and last name');
  wizUpdateAvatarPreview();
}
function wizOnEmail(){
  var val=$('wiz_email').value.trim(), el=$('wiz_email');
  el.classList.remove('err','ok');
  if(/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val)){ el.classList.add('ok'); wizSetMsg('wiz_msg_email','ok','✓ Valid email'); }
  else wizSetMsg('wiz_msg_email','hint','Must be a valid email address');
  wizUpdateAvatarPreview();
}
function wizOnPw(){
  var pw=$('wiz_pw').value;
  $('wiz_pw_strength').style.display=pw.length?'block':'none';
  var sc=wizPwScore(pw);
  var cls=['','weak','fair','good','strong'], lbs=['','Too weak','Fair','Good','Strong'], cols=['','var(--danger)','var(--gold)','#55AA77','var(--success)'];
  [1,2,3,4].forEach(function(i){ $('wiz_bar'+i).className='pw-bar'+(i<=sc?' '+cls[sc]:''); });
  $('wiz_pw_lbl').textContent=lbs[sc]; $('wiz_pw_lbl').style.color=cols[sc];
  $('wiz_pw').classList.remove('err');
  wizSetMsg('wiz_msg_pw',sc>=2?'ok':'hint',sc>=2?'✓ Good password':'Use letters, numbers, and symbols');
  if($('wiz_pw2').value) wizOnPw2();
}
function wizOnPw2(){
  var pw=$('wiz_pw').value, pw2=$('wiz_pw2').value, el=$('wiz_pw2');
  el.classList.remove('err','ok');
  if(!pw2){ wizSetMsg('wiz_msg_pw2','hint','Must match the password above'); return; }
  if(pw===pw2){ el.classList.add('ok'); wizSetMsg('wiz_msg_pw2','ok','✓ Passwords match'); }
  else wizSetMsg('wiz_msg_pw2','err','⚠ Passwords do not match');
}
function wizPwScore(pw){
  var s=0;
  if(pw.length>=8)s++; if(pw.length>=12)s++;
  if(/[A-Z]/.test(pw)&&/[a-z]/.test(pw))s++;
  if(/[0-9]/.test(pw)&&/[^A-Za-z0-9]/.test(pw))s++;
  return Math.min(4,Math.max(1,s));
}
function wizTogglePw(inputId,btnId){
  var input=$(inputId), btn=$(btnId);
  if(input.type==='password'){ input.type='text'; btn.textContent='🙈'; }
  else{ input.type='password'; btn.textContent='👁'; }
}
function wizUpdateAvatarPreview(){
  var name=$('wiz_name').value.trim(), email=$('wiz_email').value.trim();
  var ini=name?name.split(' ').filter(Boolean).map(function(w){return w[0];}).join('').slice(0,2).toUpperCase():'?';
  $('wiz_av_initials').textContent=ini;
  $('wiz_av_name').textContent=name||'New Admin';
  $('wiz_av_email').textContent=email||'email not set';
}
function wizSelectRole(role, card){
  wizRole=role;
  document.querySelectorAll('.role-card').forEach(function(c){ c.classList.remove('selected'); });
  card.classList.add('selected');
  wizSetMsg('wiz_msg_role','ok','✓ Role selected: '+ROLE_LABELS[role]);
  var badge=$('wiz_av_badge'); badge.className='av-badge '+role; badge.textContent=ROLE_LABELS[role];
  var perms=WIZ_PERMS[role];
  if(perms){
    var box=$('wiz_perms'), list=$('wiz_perms_list');
    box.classList.add('show');
    list.innerHTML=perms.allow.map(function(p){return '<span class="perm-tag allow">✓ '+p+'</span>';}).join('')+
      perms.deny.map(function(p){return '<span class="perm-tag deny">✕ '+p+'</span>';}).join('');
  }
}
function wizBuildReview(){
  var name=$('wiz_name').value.trim(), email=$('wiz_email').value.trim();
  var ini=name.split(' ').filter(Boolean).map(function(w){return w[0];}).join('').slice(0,2).toUpperCase();
  $('rev_initials').textContent=ini; $('rev_name').textContent=name; $('rev_email').textContent=email;
  $('rev_name2').textContent=name; $('rev_email2').textContent=email; $('rev_role2').textContent=ROLE_LABELS[wizRole]||'—';
  var b=$('rev_badge'); b.className='av-badge '+wizRole; b.textContent=ROLE_LABELS[wizRole]||'—';
}
function wizSubmit(){
  var bn=$('wiz_btn_next'); bn.disabled=true; bn.textContent='Creating…';
  var pw = $('wiz_pw').value;
  var check = validatePasswordClientSide(pw || '');
  if (!pw || !check.ok) {
    toast('Password does not meet policy requirements.','error');
    bn.disabled=false; bn.textContent='Create Account';
    return;
  }
  var payload={name:$('wiz_name').value.trim(), email:$('wiz_email').value.trim(), role:wizRole, password:pw};
  fetch('settings/admins_create.php', {
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body:JSON.stringify(payload)
  })
    .then(function(r){
      if(!r.ok) throw new Error(r.status);
      return r.json();
    })
    .then(function(res){
      if(!res || res.ok===false) throw new Error('fail');
      $('wizStepsBar').style.display='none';
      $('wiz_counter').innerHTML='<span style="color:var(--success);font-weight:600">✓ Account ready</span>';
      $('wiz_btn_back').style.display='none';
      bn.disabled=false; bn.textContent='Done';
      bn.style.background='linear-gradient(135deg,var(--rose),var(--rose-deep))';
      bn.style.boxShadow='0 4px 14px rgba(176,48,80,.28)';
      bn.onclick=closeAddAdminModal;
      $('wiz_succ_name').textContent=payload.name;
      $('wiz_succ_role').textContent=ROLE_LABELS[wizRole];
      wizShowPanel('success');
      toast(payload.name+' added as '+ROLE_LABELS[wizRole]+'!','success');
      loadAdminAccounts();
    })
    .catch(function(){
      toast('Could not create admin. Please check details.', 'error');
      bn.disabled=false; bn.textContent='Create Account';
    });
}

async function loadPage(){
  setLoading(true);
  try{
    await Promise.all([loadUser(),loadBadges(),loadStoreSettings(),loadShippingSettings(),loadPaymentSettings(),loadPasswordPolicySettings(),loadAdminAccounts()]);
  }finally{ setLoading(false); }
}

function validatePasswordClientSide(pw){
  try{
    var enabled = getToggle('tog_pw_policy');
    if (!enabled) return {ok:true, errors:[]};
    var min = parseInt($('pw_min_length').value,10)||12;
    var reqUpper = getToggle('tog_pw_upper');
    var reqLower = getToggle('tog_pw_lower');
    var reqNum = getToggle('tog_pw_number');
    var reqSpec = getToggle('tog_pw_special');
    var errors = [];
    if ((pw||'').length < min) errors.push('Password must be at least '+min+' characters.');
    if (reqUpper && !/[A-Z]/.test(pw)) errors.push('Include at least one uppercase letter.');
    if (reqLower && !/[a-z]/.test(pw)) errors.push('Include at least one lowercase letter.');
    if (reqNum && !/[0-9]/.test(pw)) errors.push('Include at least one number.');
    if (reqSpec && !/[^A-Za-z0-9]/.test(pw)) errors.push('Include at least one special character.');
    return {ok: errors.length===0, errors: errors};
  }catch(e){ return {ok:true, errors:[]}; }
}
</script>
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