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
  <title>Bejewelry Order Manager — Settings</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&family=DM+Sans:opsz,wght@9..40,400;9..40,500;9..40,600;9..40,700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../admin/dashboard.css">
  <style>
.btn:disabled{opacity:.5;cursor:not-allowed;transform:none!important}
.settings-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px}
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
.skel{background:linear-gradient(90deg,var(--blush) 25%,var(--blush-mid) 50%,var(--blush) 75%);background-size:200% 100%;animation:skel 1.5s infinite}
@keyframes skel{0%{background-position:200% 0}100%{background-position:-200% 0}}
.loading-bar{position:fixed;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,var(--rose),var(--gold));background-size:200% 100%;animation:loadbar 1.2s linear infinite;z-index:99999;display:none}
.loading-bar.active{display:block}
@keyframes loadbar{0%{background-position:200% 0}100%{background-position:-200% 0}}
.toast{position:fixed;bottom:24px;right:24px;background:var(--dark);color:var(--white);padding:12px 20px;border-radius:var(--r-md);font-size:.8rem;z-index:99999;opacity:0;transform:translateY(8px);transition:all .25s;pointer-events:none;max-width:320px;box-shadow:var(--sh-lg);display:flex;align-items:center;gap:8px}
.toast.on{opacity:1;transform:translateY(0)}
.toast.success{background:var(--success)}
.toast.error{background:var(--danger)}
@media(max-width:1100px){.settings-grid{grid-template-columns:1fr}}
@media(max-width:900px){.sidebar{display:none}}
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
        <span class="topbar-bc">Order Manager › Settings</span>
      </div>
      <div class="topbar-right">
        <button class="icon-btn" onclick="loadPage()" title="Refresh">↺</button>
      </div>
    </header>

    <div class="content">
      <div class="page-hdr">
        <div><h2>Shipping Settings</h2><p>Configure shipping options</p></div>
        <div class="page-hdr-actions">
          <button class="btn btn-primary btn-sm" id="btnSaveAll" onclick="saveShippingSettings()">✓ Save Changes</button>
        </div>
      </div>

      <div style="max-width:600px">
        <div class="card">
          <div class="card-hd"><h3>Shipping Fee</h3><span class="card-hd-icon">🚚</span></div>
          <div class="card-body">
            <div class="fg"><label class="flabel">Shipping Fee (₱)</label><input class="finput" type="number" id="ship_fee" placeholder="50" min="0" step="1" style="font-size:1.1rem;padding:12px 14px"></div>
            <div class="fhint">This fee will be shown to customers at checkout and can be adjusted anytime.</div>
            <button class="btn btn-primary btn-sm" style="margin-top:20px" id="btnSaveShipping" onclick="saveShippingSettings()">✓ Save Shipping Fee</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="toast" id="toast"></div>

<script src="../admin/confirm_modal.js?v=1"></script>
<script src="whoami.js?v=1"></script>
<script>
const $=id=>document.getElementById(id);
function getToggle(id){return $$(id)&&$$(id).classList.contains('on');}
function setToggle(id,val){var el=$$(id);if(el){el.classList.toggle('on',!!val);}}
function toast(msg,type=''){var t=$('toast');t.textContent=msg;t.className='toast '+type;t.classList.add('on');setTimeout(()=>t.classList.remove('on'),3e3);}
function setLoading(btn,on,txt){if(!btn)return;btn.disabled=!!on;if(on)btn.dataset.text=btn.textContent,btn.textContent='Please wait…';else btn.textContent=btn.dataset.text||'Save Changes';}
function $$($){return document.getElementById($);}

async function loadPage(){location.reload();}

async function loadShippingSettings(){
  try{
    var r = await fetch('../admin/settings/shipping.php');
    var d = await r.json();
    $('ship_fee').value=d.shipping_fee||'50';
  }catch(e){console.error(e);} 
}
async function saveShippingSettings(){
  var btn=$('btnSaveShipping'); btn.disabled=true; btn.textContent='Saving…';
  try{
    // Send as form data (avoid preflight) and include csrf_token explicitly
    var csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
    var form = new URLSearchParams();
    form.append('shipping_fee', String(parseFloat($('ship_fee').value) || 0));
    if (csrfToken) form.append('csrf_token', csrfToken);
    var r = await fetch('../admin/settings/shipping_save.php', { method: 'POST', body: form, credentials: 'same-origin' });

    // attempt to parse JSON body even on non-2xx so we can show server error
    var resBody = null;
    try { resBody = await r.json(); } catch (er) { resBody = null; }

    if (!r.ok || !resBody || resBody.ok === false) {
      console.error('shipping_save failed', r.status, resBody);
      var msg = (resBody && resBody.error) ? resBody.error : 'Server error';
      toast('Failed to save shipping fee: ' + msg,'error');
      return;
    }

    toast('Shipping fee updated. It will show in customer checkout.','success');
  }catch(e){ console.error(e); toast('Failed to save shipping fee.','error'); }
  finally{ btn.disabled=false; btn.textContent='✓ Save Changes'; }
}

function handleLogout(){
  if (typeof window.adminConfirm === 'function') {
    window.adminConfirm('Log out of Bejewelry Admin?', function () { window.location.href = '../logout.php'; }, { okText: 'Log out' });
    return;
  }
  window.location.href = '../logout.php';
}

window.addEventListener('DOMContentLoaded',()=>{
  try { loadShippingSettings(); } catch(e) { console.error(e); }
});
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
