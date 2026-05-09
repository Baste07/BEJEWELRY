/* ═══════════════════════════════════════════════════════════════════════
   BEJEWELRY — App Core  v2
   Load AFTER api.js.
   No hardcoded product data — all content comes from the PHP API.
═══════════════════════════════════════════════════════════════════════ */

/* ── PRODUCT IMAGE HELPER ─────────────────────────────────────────────
   Returns an <img> or a letter-based placeholder — no emojis needed.
───────────────────────────────────────────────────────────────────── */
function productThumb(p, style = '') {
  if (p.image_url) {
    return `<img src="${p.image_url}" alt="${p.name}"
              style="width:100%;height:100%;object-fit:cover;border-radius:inherit;${style}"
              onerror="this.style.display='none'">`;
  }
  return `<span style="font-size:1.8rem;font-weight:700;color:var(--rose-muted)">${(p.cat||p.name||'?')[0]}</span>`;
}

/* ── CART & WISHLIST STORAGE ──────────────────────────────────────── */
function getCart() {
  try {
    const local = JSON.parse(sessionStorage.getItem('bj_cart') || '[]');
    if (Array.isArray(local)) return local;
  } catch {}
  if (typeof window !== 'undefined' && Array.isArray(window.__CART__)) return window.__CART__;
  return [];
}
function setCart(c) {
  const next = Array.isArray(c) ? c : [];
  if (typeof window !== 'undefined') window.__CART__ = next;
  sessionStorage.setItem('bj_cart', JSON.stringify(next));
  _syncBadges();
}
function getWish() {
  if (typeof window !== 'undefined' && Array.isArray(window.__WISHLIST__)) return window.__WISHLIST__;
  try { return JSON.parse(sessionStorage.getItem('bj_wish') || '[]'); } catch { return []; }
}
function setWish(w) {
  if (typeof window !== 'undefined') window.__WISHLIST__ = Array.isArray(w) ? w : [];
  sessionStorage.setItem('bj_wish', JSON.stringify(w));
  _syncBadges();
}

const _notifState = {
  unread: 0,
  items: [],
  loaded: false,
  pollTimer: null,
};

function _lookupProductById(productId) {
  const id = Number(productId);
  const pools = [];
  if (typeof window !== 'undefined') {
    if (window.__PRODUCT__ && Number(window.__PRODUCT__.id) === id) pools.push(window.__PRODUCT__);
    if (window.__PRODUCT_LIST_DATA__ && Array.isArray(window.__PRODUCT_LIST_DATA__.products)) pools.push(...window.__PRODUCT_LIST_DATA__.products);
    if (window.__INDEX_DATA__ && Array.isArray(window.__INDEX_DATA__.products)) pools.push(...window.__INDEX_DATA__.products);
    if (window.__RELATED__ && Array.isArray(window.__RELATED__)) pools.push(...window.__RELATED__);
  }
  return pools.find(p => Number(p && p.id) === id) || null;
}

function _cartItemKey(productId, size = '') {
  return `${Number(productId)}-${size || 'One Size'}`;
}

function _upsertCartLocal(productId, qty, size = '') {
  const product = _lookupProductById(productId);
  if (!product) return;
  const cart = getCart().slice();
  const key = _cartItemKey(productId, size);
  let item = cart.find(x => String(x.key || _cartItemKey(x.product_id, x.size)) === key);
  if (item) {
    item.qty = Math.max(1, Number(item.qty || 1) + Number(qty || 1));
  } else {
    item = {
      id: Date.now(),
      key,
      product_id: Number(productId),
      name: product.name,
      cat: product.cat || '',
      price: Number(product.price || 0),
      image: product.image || '',
      image_url: product.image_url || '',
      size: size || product.size || product.size_default || 'One Size',
      qty: Math.max(1, Number(qty || 1)),
    };
    cart.push(item);
  }
  setCart(cart);
  if (document.getElementById('cartDrawer')?.classList.contains('open')) renderCartDrawer();
  return cart;
}

function _removeCartLocalById(itemId) {
  const next = getCart().filter(item => Number(item.id) !== Number(itemId));
  setCart(next);
  if (document.getElementById('cartDrawer')?.classList.contains('open')) renderCartDrawer();
  return next;
}

function _updateCartLocalById(itemId, newQty) {
  const cart = getCart().slice();
  const item = cart.find(x => Number(x.id) === Number(itemId));
  if (!item) return cart;
  item.qty = Math.max(1, Number(newQty) || 1);
  setCart(cart);
  if (document.getElementById('cartDrawer')?.classList.contains('open')) renderCartDrawer();
  return cart;
}

function _setWishlistLocal(productId, wished) {
  const id = Number(productId);
  const next = getWish().filter(x => Number(x) !== id);
  if (wished) next.unshift(id);
  setWish(Array.from(new Set(next.map(Number))));
  _refreshWishBtns(id, wished);
}

/* ── CART ACTIONS ─────────────────────────────────────────────────── */
async function addToCart(productId, qty = 1, size = '') {
  if (!Auth.isLoggedIn()) {
    window.location = 'login.php?redirect=' + encodeURIComponent(window.location.href);
    return;
  }
  const params = new URLSearchParams({ product_id: productId, qty: qty || 1, redirect: window.location.href, ajax: '1' });
  if (size) params.set('size', size);
  let res;
  try {
    res = await fetch('cart_add.php?' + params.toString(), { credentials: 'same-origin' });
  } catch (_) {
    toast('Could not add to cart. Please try again.', 'error');
    return false;
  }
  let payload = null;
  try { payload = await res.json(); } catch (_) { payload = null; }
  if (!res.ok || (payload && payload.ok === false)) {
    const msg = (payload && payload.message) ? payload.message : 'This product is currently out of stock.';
    toast(msg, 'error');
    return false;
  }
  _upsertCartLocal(productId, qty || 1, size);
  return true;
}

function removeFromCart(itemId) {
  if (Auth.isLoggedIn()) {
    fetch('cart_remove.php?id=' + itemId + '&redirect=' + encodeURIComponent(window.location.href), { credentials: 'same-origin' }).catch(() => {});
    _removeCartLocalById(itemId);
    return;
  }
  setCart(getCart().filter(x => x.id !== itemId));
  renderCartDrawer();
}

function updateCartQty(itemId, delta) {
  const cart = getCart();
  const item = cart.find(x => x.id === itemId);
  if (!item) return;
  const newQty = Math.max(1, item.qty + delta);
  if (Auth.isLoggedIn()) {
    fetch('cart_update.php?id=' + itemId + '&qty=' + newQty + '&redirect=' + encodeURIComponent(window.location.href), { credentials: 'same-origin' }).catch(() => {});
    _updateCartLocalById(itemId, newQty);
    return;
  }
  item.qty = newQty;
  setCart(cart);
  renderCartDrawer();
}

async function clearCart() {
  await API.clearCart().catch(() => {});
  setCart([]);
  renderCartDrawer();
}

async function syncCartFromServer() {
  if (!Auth.isLoggedIn()) return;
  try {
    const res = await API.getCart();
    setCart(res.items || []);
    renderCartDrawer();
  } catch { /* use local session cart */ }
}

/* ── WISHLIST ACTIONS ─────────────────────────────────────────────── */
async function toggleWish(productId, name = '') {
  if (!Auth.isLoggedIn()) {
    window.location = 'login.php?redirect=' + encodeURIComponent(window.location.href);
    return;
  }
  const id = Number(productId);
  const wished = isWished(id);
  const endpoint = wished ? 'wishlist_remove.php?product_id=' + id : 'wishlist_add.php?product_id=' + id;
  const redirect = encodeURIComponent(window.location.href);
  _setWishlistLocal(id, !wished);
  await fetch(endpoint + '&redirect=' + redirect, { credentials: 'same-origin' }).catch(() => {});
}

function isWished(id) { return getWish().includes(id); }

function _refreshWishBtns(id, liked) {
  document.querySelectorAll(`.wish-btn[data-id="${id}"]`).forEach(btn => {
    btn.classList.toggle('liked', liked);
    btn.textContent = liked ? '♥' : '♡';
  });
}

/* ── CART DRAWER ──────────────────────────────────────────────────── */
async function ensureCheckoutSettings() {
  if (typeof window.__CHECKOUT__ !== 'undefined') return;
  try {
    const res = await fetch('admin/settings/shipping.php', { credentials: 'same-origin' });
    if (!res.ok) return;
    const body = await res.json().catch(() => null);
    if (body && typeof body === 'object') {
      window.__CHECKOUT__ = {
        shipping_fee: Number(body.shipping_fee || 150),
        free_ship_threshold: Number(body.free_ship_threshold || 2000),
        paymongo_enabled: Boolean(body.paymongo_enabled || false),
      };
    }
  } catch (e) {
    // ignore network errors
  }
}

function openCart()  { (async function(){ await ensureCheckoutSettings(); await renderCartDrawer(); document.getElementById('cartDrawer')?.classList.add('open'); document.getElementById('drawerOv')?.classList.add('open'); })(); }
function closeCart() { document.getElementById('cartDrawer')?.classList.remove('open'); document.getElementById('drawerOv')?.classList.remove('open'); }

async function renderCartDrawer() {
  await ensureCheckoutSettings();
  const body = document.getElementById('cartBody');
  const foot = document.getElementById('cartFoot');
  if (!body) return;
  const cart = getCart();

  if (!cart.length) {
    body.innerHTML = `<div class="drw-empty"><span class="drw-empty-icon">??️</span><p>Your cart is empty</p><a href="product-list.php" class="btn btn-primary btn-sm" onclick="closeCart()" style="display:inline-flex;margin-top:16px">Browse Jewelry</a></div>`;
    if (foot) foot.innerHTML = '';
    return;
  }

  body.innerHTML = cart.map(item => `
    <div style="display:flex;gap:12px;align-items:start;padding:12px 0;border-bottom:1px solid var(--border)">
      <div style="width:55px;height:55px;background:var(--blush-mid);border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;overflow:hidden">
        ${item.image_url
          ? `<img src="${escapeAttr(item.image_url)}" alt="${escapeAttr(item.name)}" style="width:100%;height:100%;object-fit:cover">`
          : `<span style="font-size:1.1rem;font-weight:700;color:var(--rose-muted)">${escapeHtml((item.cat||item.name||'?')[0])}</span>`}
      </div>
      <div style="flex:1;min-width:0">
        <div style="font-family:var(--fd);font-size:.85rem;font-weight:600;color:var(--dark);margin-bottom:2px">${escapeHtml(item.name)}</div>
        <div style="font-size:.7rem;color:var(--muted-light);margin-bottom:5px">${escapeHtml(item.cat)} · ${escapeHtml(item.size)}</div>
        <div style="display:flex;gap:6px;align-items:center">
          <button style="width:26px;height:26px;background:var(--blush);border:none;border-radius:4px;color:var(--rose-deep);cursor:pointer;font-size:.8rem" onclick="updateCartQty(${item.id},-1)">−</button>
          <span style="min-width:16px;text-align:center;font-weight:600;font-size:.8rem">${item.qty}</span>
          <button style="width:26px;height:26px;background:var(--blush);border:none;border-radius:4px;color:var(--rose-deep);cursor:pointer;font-size:.8rem" onclick="updateCartQty(${item.id},1)">+</button>
        </div>
      </div>
      <div style="text-align:right;flex-shrink:0">
        <div style="font-family:var(--fd);font-size:.9rem;font-weight:700;color:var(--dark);margin-bottom:4px">₱${(item.price * item.qty).toLocaleString()}</div>
        <button class="ci-del" onclick="removeFromCart(${item.id})">Remove</button>
      </div>
    </div>`).join('');

  const sub  = cart.reduce((a, i) => a + i.price * i.qty, 0);
  const cfg  = window.__CHECKOUT__ || { shipping_fee: 150, free_ship_threshold: 2000 };
  const ship = sub >= (Number(cfg.free_ship_threshold) || 0) ? 0 : (Number(cfg.shipping_fee) || 0);
  if (foot) foot.innerHTML = `
    <div style="background:var(--blush);padding:12px;border-radius:8px;margin-bottom:12px">
      <div style="display:flex;justify-content:space-between;font-size:.77rem;color:var(--muted);margin-bottom:6px">
        <span>Subtotal</span><span style="font-weight:600">₱${sub.toLocaleString()}</span>
      </div>
      <div style="display:flex;justify-content:space-between;font-size:.77rem;color:var(--muted);padding-bottom:8px;border-bottom:1px solid rgba(0,0,0,.1);margin-bottom:8px">
        <span>Shipping</span>
        <span style="font-weight:600;color:${ship === 0 ? 'var(--success)' : 'var(--dark)'}">
          ${ship === 0 ? 'FREE' : '₱' + ship}
        </span>
      </div>
      <div style="display:flex;justify-content:space-between;font-size:.92rem;font-weight:700;color:var(--dark)">
        <span>Total</span><span style="color:var(--rose-deep)">₱${(sub+ship).toLocaleString()}</span>
      </div>
    </div>
    <a href="checkout.php" class="btn btn-primary btn-full" style="justify-content:center;margin-bottom:8px" onclick="closeCart()">Checkout</a>
    <button onclick="closeCart()" class="btn btn-ghost btn-full" style="justify-content:center">Continue Shopping</button>`;
}

/* ── BADGE SYNC ───────────────────────────────────────────────────── */
function _syncBadges() {
  const cc = getCart().reduce((a, i) => a + i.qty, 0);
  const wc = getWish().length;
  document.querySelectorAll('.js-cart-count').forEach(el => { el.textContent = cc || ''; el.style.display = cc ? 'flex' : 'none'; });
  document.querySelectorAll('.js-wish-count').forEach(el => { el.textContent = wc || ''; el.style.display = wc ? 'flex' : 'none'; });
  document.querySelectorAll('.js-notif-count').forEach(el => { el.textContent = _notifState.unread || ''; el.style.display = _notifState.unread ? 'flex' : 'none'; });
}

function _csrfTokenValue() {
  return document.querySelector('meta[name="csrf-token"]')?.content || '';
}

function _ensureNotifStyles() {
  if (document.getElementById('hdrNotifStyles')) return;
  const style = document.createElement('style');
  style.id = 'hdrNotifStyles';
  style.textContent = '' +
    '.hdr-notif{position:relative}' +
    '.hdr-notif-btn{position:relative}' +
    '.hdr-notif-panel{position:absolute;top:calc(100% + 10px);right:0;width:340px;max-height:420px;background:var(--white,#fff);border:1px solid var(--border-mid,#DEC8D0);border-radius:18px;box-shadow:0 12px 34px rgba(36,20,24,.16);opacity:0;pointer-events:none;transform:translateY(-8px);transition:opacity .2s ease,transform .2s ease;z-index:420;overflow:hidden}' +
    '.hdr-notif-panel.show{opacity:1;pointer-events:all;transform:translateY(0)}' +
    '.hdr-notif-head{display:flex;align-items:center;justify-content:space-between;padding:11px 13px;border-bottom:1px solid var(--border,#ECDCE0);font-size:.72rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--dark,#241418);background:var(--blush,#FEF1F3)}' +
    '.hdr-notif-head button{border:none;background:transparent;color:var(--rose-deep,#B03050);font-size:.66rem;font-weight:600;cursor:pointer;letter-spacing:.02em}' +
    '.hdr-notif-items{max-height:360px;overflow:auto}' +
    '.hdr-notif-item{display:block;padding:12px 13px;border-bottom:1px solid var(--border,#ECDCE0);text-decoration:none;transition:background .2s ease}' +
    '.hdr-notif-item:hover{background:var(--blush,#FEF1F3)}' +
    '.hdr-notif-item:last-child{border-bottom:none}' +
    '.hdr-notif-item.is-unread{background:rgba(217,96,112,.06)}' +
    '.hdr-notif-title{font-size:.8rem;font-weight:700;color:var(--dark,#241418);line-height:1.3}' +
    '.hdr-notif-msg{font-size:.73rem;color:var(--muted,#7A5E68);margin-top:3px;line-height:1.45}' +
    '.hdr-notif-time{font-size:.66rem;color:var(--muted-light,#AC8898);margin-top:6px}' +
    '.hdr-notif-empty{padding:16px 13px;color:var(--muted,#7A5E68);font-size:.78rem;text-align:center}' +
    '@media (max-width:900px){.hdr-notif-panel{right:-42px;width:min(90vw,340px)}}';
  document.head.appendChild(style);
}

async function _notificationsRequest(action, method = 'GET', payload = null) {
  const headers = {};
  if (method === 'POST') {
    headers['Content-Type'] = 'application/json';
    const csrf = _csrfTokenValue();
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

function _notificationRelativeTime(dateValue) {
  const ts = Date.parse(String(dateValue || ''));
  if (!Number.isFinite(ts)) return '';
  const diffMin = Math.max(1, Math.floor((Date.now() - ts) / 60000));
  if (diffMin < 60) return diffMin + 'm ago';
  const diffH = Math.floor(diffMin / 60);
  if (diffH < 24) return diffH + 'h ago';
  const diffD = Math.floor(diffH / 24);
  if (diffD < 7) return diffD + 'd ago';
  return new Date(ts).toLocaleDateString('en-PH', { month: 'short', day: 'numeric' });
}

function _renderNotificationPanel(items) {
  const list = document.getElementById('hdrNotifItems');
  if (!list) return;
  if (!Array.isArray(items) || items.length === 0) {
    list.innerHTML = '<div class="hdr-notif-empty">No notifications yet.</div>';
    return;
  }

  list.innerHTML = items.map((item) => {
    const id = Number(item.id || 0);
    const title = escapeHtml(item.title || 'Notification');
    const msg = escapeHtml(item.message || '');
    const href = escapeAttr(item.link_url || '#');
    const time = escapeHtml(_notificationRelativeTime(item.created_at));
    const readClass = Number(item.is_read || 0) ? 'is-read' : 'is-unread';
    return '<a href="' + href + '" class="hdr-notif-item ' + readClass + '" data-id="' + id + '">' +
      '<div class="hdr-notif-title">' + title + '</div>' +
      '<div class="hdr-notif-msg">' + msg + '</div>' +
      '<div class="hdr-notif-time">' + time + '</div>' +
    '</a>';
  }).join('');

  list.querySelectorAll('.hdr-notif-item').forEach((el) => {
    el.addEventListener('click', async function () {
      const id = Number(this.getAttribute('data-id') || 0);
      if (id > 0) {
        try {
          const data = await _notificationsRequest('read', 'POST', { id });
          _notifState.unread = Number(data.unread_count || 0);
          _syncBadges();
        } catch (_) {}
      }
    });
  });
}

async function loadNotifications(silent = false) {
  const wrap = document.getElementById('hdrNotifWrap');
  if (!wrap) return;
  try {
    const data = await _notificationsRequest('list', 'GET');
    _notifState.items = Array.isArray(data.items) ? data.items : [];
    _notifState.unread = Number(data.unread_count || 0);
    _notifState.loaded = true;
    _syncBadges();
    _renderNotificationPanel(_notifState.items);
  } catch (_) {
    if (!silent) {
      _renderNotificationPanel([]);
    }
  }
}

function toggleNotifPanel(event) {
  if (event) event.stopPropagation();
  const panel = document.getElementById('hdrNotifPanel');
  if (!panel) return;
  const open = panel.classList.toggle('show');
  if (open && !_notifState.loaded) {
    loadNotifications(true);
  }
}

async function markAllNotificationsRead() {
  try {
    await _notificationsRequest('read-all', 'POST', {});
    _notifState.items = _notifState.items.map((it) => Object.assign({}, it, { is_read: 1 }));
    _notifState.unread = 0;
    _syncBadges();
    _renderNotificationPanel(_notifState.items);
  } catch (_) {
    toast('Could not mark notifications as read.', 'warn');
  }
}

/* ── TOAST ────────────────────────────────────────────────────────── */
function toast(msg, type = '') {
  const area = document.getElementById('toasts');
  if (!area) return;
  const el = document.createElement('div');
  el.className = `toast${type ? ' ' + type : ''}`;
  el.textContent = msg;
  area.appendChild(el);
  setTimeout(() => el.remove(), 2800);
}

/* ── BUTTON LOADING STATE ─────────────────────────────────────────── */
function setLoading(btn, on, label = '') {
  if (!btn) return;
  btn.disabled = on;
  if (on) { btn._orig = btn.textContent; btn.textContent = '···'; }
  else btn.textContent = label || btn._orig || btn.textContent;
}

function escapeHtml(value) {
  return String(value ?? '').replace(/[&<>"']/g, function (ch) {
    return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[ch];
  });
}

function escapeAttr(value) {
  return escapeHtml(value);
}

/* ── PRODUCT CARD ─────────────────────────────────────────────────── */
function productCard(p, linkTo = 'product_detail.php') {
  const wished   = isWished(p.id);
  const stars    = '★'.repeat(p.stars) + (p.stars < 5 ? '☆'.repeat(5 - p.stars) : '');
  const badgeMap = { new: 'New', best: 'Best Seller', sale: 'Sale' };
  const salePrice = Number(p.price || 0);
  const origPrice = Number(p.orig_price || 0);
  const isSale = (p.badge === 'sale') || (origPrice > salePrice && salePrice > 0);
  const badgeKey = p.badge || (isSale ? 'sale' : '');
  const badge    = badgeKey ? `<span class="pbadge pb-${escapeAttr(badgeKey)}">${escapeHtml(badgeMap[badgeKey] || badgeKey)}</span>` : '';
  const orig     = isSale && origPrice > salePrice
    ? `<span class="pcard-orig">₱${origPrice.toLocaleString()}</span>`
    : '';
  const imgHTML  = p.image_url
    ? `<img src="${escapeAttr(p.image_url)}" alt="${escapeAttr(p.name)}" style="width:100%;height:100%;object-fit:cover;border-radius:inherit">`
    : `<span style="font-size:2.2rem;font-weight:700;color:var(--rose-muted)">${escapeHtml((p.cat||p.name||'?')[0])}</span>`;
  const stockCount = Number(p.stock);
  const isOutOfStock = Number.isFinite(stockCount) ? stockCount <= 0 : false;
  const addButton = isOutOfStock
    ? `<button class="pcard-add" type="button" disabled aria-disabled="true" title="Out of stock">Out of Stock</button>`
    : `<button class="pcard-add" onclick="event.stopPropagation();addToCart(${p.id}); return false;">+ Add to Cart</button>`;

  return `
    <div class="pcard" onclick="window.location='${linkTo}?id=${p.id}'">
      <div class="pcard-img">
        ${badge}
        <button class="wish-btn${wished ? ' liked' : ''}" data-id="${p.id}"
          data-name="${escapeAttr(p.name)}" onclick="event.stopPropagation();toggleWish(${p.id}, this.dataset.name); return false;">
          ${wished ? '♥' : '♡'}
        </button>
        ${imgHTML}
      </div>
      <div class="pcard-info">
        <div class="pcard-cat">${escapeHtml(p.cat || '')}</div>
        <div class="pcard-name">${escapeHtml(p.name)}</div>
        <div class="pcard-foot">
          <span class="pcard-price-wrap"><span class="pcard-price">₱${salePrice.toLocaleString()}</span>${orig}</span>
          <span class="pcard-stars">
            ${stars}
            <span class="pcard-rating">${typeof p.avg_rating !== 'undefined' && p.avg_rating !== null && p.avg_rating !== '' ? Number(p.avg_rating).toFixed(1) : ''}</span>
            <span class="pcard-review-count">${p.reviews ? ' (' + Number(p.reviews) + ')' : ''}</span>
          </span>
        </div>
        ${addButton}
      </div>
    </div>`;
}

/* ── SEARCH ───────────────────────────────────────────────────────── */
function toggleSearch() {
  const hs = document.getElementById('hdrSearch');
  if (!hs) return;
  const open = hs.classList.toggle('search-open');
  if (open) {
    setTimeout(() => document.getElementById('globalSearch')?.focus(), 50);
    document.addEventListener('click', closeSearchOnClickOutside);
  } else {
    document.getElementById('searchDrop')?.classList.remove('show');
    document.removeEventListener('click', closeSearchOnClickOutside);
  }
}

function closeSearchOnClickOutside(e) {
  const hs = document.getElementById('hdrSearch');
  if (hs && !hs.contains(e.target)) {
    hs.classList.remove('search-open');
    document.removeEventListener('click', closeSearchOnClickOutside);
  }
}

function clearSearch() {
  const inp = document.getElementById('globalSearch');
  if (inp) inp.value = '';
  document.getElementById('searchClear').style.display = 'none';
  document.getElementById('searchDrop')?.classList.remove('show');
}

let _searchTimer = null;
function _liveSearch(query) {
  const drop  = document.getElementById('searchDrop');
  const clear = document.getElementById('searchClear');
  if (!drop) return;
  if (clear) clear.style.display = query ? 'block' : 'none';
  const q = query.trim();
  if (!q) { drop.classList.remove('show'); return; }
  clearTimeout(_searchTimer);
  _searchTimer = setTimeout(() => {
    drop.innerHTML = `<a href="product-list.php?search=${encodeURIComponent(q)}" class="sd-all" onclick="document.getElementById('hdrSearch')?.classList.remove('search-open')">See all results for "${escapeHtml(q)}"</a>`;
    drop.classList.add('show');
  }, 200);
}

/* ── HEADER ───────────────────────────────────────────────────────── */
function buildHeader(activeNav = '', minimal = false) {
  const cc = getCart().reduce((a, i) => a + i.qty, 0);
  const wc = getWish().length;

  if (minimal) return `
    <header class="site-header" id="siteHeader">
      <div class="hdr-main"><div class="container"><div class="hdr-inner">
        <a href="index.php" class="hdr-logo"><span class="hdr-logo-text">Bejewelry</span></a>
        <div class="hdr-secure">Secure Checkout</div>
        <div class="hdr-actions" style="gap:8px">
          <a href="cart.php" class="hdr-btn hdr-cart-btn" style="padding:10px 14px">
            <span class="hdr-btn-icon">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
              <span class="hdr-badge js-cart-count" style="display:${cc?'flex':'none'}">${cc||0}</span>
            </span>
            <span class="hdr-btn-label">Cart</span>
          </a>
          <a href="cart.php" class="hdr-back">Return to Cart</a>
        </div>
      </div></div></div>
    </header>`;

  const navLinks = [
    { label: 'Rings',       href: 'product-list.php?cat=Rings'     },
    { label: 'Necklaces',   href: 'product-list.php?cat=Necklaces' },
    { label: 'Bracelets',   href: 'product-list.php?cat=Bracelets' },
    { label: 'Earrings',    href: 'product-list.php?cat=Earrings'  },
    { label: 'Collections', href: 'product-list.php'               },
  ];

  return `
    <header class="site-header" id="siteHeader">
      <div class="hdr-announce">
        <span>Free shipping on orders over ₱2,000</span>
        <span class="hdr-announce-sep">·</span>
        <span>Complimentary gift wrapping available</span>
        <a href="product-list.php" class="hdr-announce-cta">Shop Now</a>
      </div>
      <div class="hdr-main">
        <div class="container">
          <div class="hdr-inner">
            <a href="index.php" class="hdr-logo">
              <span class="hdr-logo-text">Bejewelry</span>
              <span class="hdr-logo-sub">Fine Jewelry</span>
            </a>
            <nav class="hdr-nav">
              ${navLinks.map(n => `<a href="${n.href}" class="hdr-nav-link${activeNav===n.label.toLowerCase()?' active':''}">${n.label}</a>`).join('')}
            </nav>
            <div class="hdr-actions">
              <div class="hdr-search" id="hdrSearch">
                <button class="hdr-search-btn" onclick="toggleSearch()" aria-label="Search">
                  <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                </button>
                <div class="hdr-search-panel" id="searchPanel">
                  <div class="hdr-search-inner">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" style="color:var(--muted-light);flex-shrink:0"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                    <input type="text" id="globalSearch" class="hdr-search-input" placeholder="Search rings, necklaces…"
                      oninput="_liveSearch(this.value)"
                      onkeydown="if(event.key==='Enter'&&this.value.trim())window.location='product-list.php?search='+encodeURIComponent(this.value.trim())"
                      autocomplete="off">
                    <button class="hdr-search-clear" id="searchClear" onclick="clearSearch()" style="display:none">✕</button>
                  </div>
                  <div class="hdr-search-drop" id="searchDrop"></div>
                </div>
              </div>
              <a href="wishlist.php" class="hdr-btn">
                <span class="hdr-btn-icon">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                  <span class="hdr-badge js-wish-count" style="display:${wc?'flex':'none'}">${wc||0}</span>
                </span>
                <span class="hdr-btn-label">Wishlist</span>
              </a>
              <div class="hdr-notif" id="hdrNotifWrap">
                <button class="hdr-btn hdr-notif-btn" onclick="toggleNotifPanel(event)">
                  <span class="hdr-btn-icon">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 8-3 8h18s-3-1-3-8"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
                    <span class="hdr-badge js-notif-count" style="display:none"></span>
                  </span>
                  <span class="hdr-btn-label">Alerts</span>
                </button>
                <div class="hdr-notif-panel" id="hdrNotifPanel">
                  <div class="hdr-notif-head">
                    <span>Notifications</span>
                    <button type="button" onclick="markAllNotificationsRead()">Mark all read</button>
                  </div>
                  <div class="hdr-notif-items" id="hdrNotifItems">
                    <div class="hdr-notif-empty">Loading...</div>
                  </div>
                </div>
              </div>
              <a href="profile.php" class="hdr-btn">
                <span class="hdr-btn-icon">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                </span>
                <span class="hdr-btn-label">Account</span>
              </a>
              <button class="hdr-btn hdr-cart-btn" onclick="openCart()">
                <span class="hdr-btn-icon">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                  <span class="hdr-badge js-cart-count" style="display:${cc?'flex':'none'}">${cc||0}</span>
                </span>
                <span class="hdr-btn-label">Cart</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </header>`;
}

/* ── SIDEBAR ──────────────────────────────────────────────────────── */
function buildSidebar(active = '') {
  const user = Auth.getUser();
  const isSignedIn = !!(user && user.id);
  const name = user ? `${user.first_name} ${user.last_name}` : 'Guest';
  const init = name[0]?.toUpperCase() || 'G';
  const cc   = getCart().reduce((a, i) => a + i.qty, 0);
  const wc   = getWish().length;
  const link = (href, icon, label, key, badge = '') => `
    <a href="${href}" class="sb-item${active===key?' active':''}">
      ${icon ? `<span class="sb-icon">${icon}</span>` : ''}<span>${label}</span>
      ${badge ? `<span class="sb-badge">${badge}</span>` : ''}
    </a>`;
  return `
    <div class="sb-brand"><div class="sb-logo">Bejewelry</div><div class="sb-sub">Fine Jewelry</div></div>
    <div class="sb-user">
      <div class="sb-av">${init}</div>
      <div><div class="sb-uname">${name}</div><div class="sb-urole">Customer</div></div>
    </div>
    <div class="sb-group">Browse</div>
    ${link('index.php',        '⊞', 'Home',        'home')}
    ${link('product-list.php', '◈', 'All Jewelry', 'shop')}
    <hr class="sb-div">
    <div class="sb-group">Categories</div>
    ${link('product-list.php?cat=Rings',     '○', 'Rings',       'rings'    )}
    ${link('product-list.php?cat=Necklaces', '○', 'Necklaces',   'necklaces')}
    ${link('product-list.php?cat=Bracelets', '○', 'Bracelets',   'bracelets')}
    ${link('product-list.php?cat=Earrings',  '○', 'Earrings',    'earrings' )}
    ${link('product-list.php?badge=new',     '✦', 'New Arrivals','new'      )}
    ${link('product-list.php?badge=sale',     '%', 'Sale',       'sale'     )}
    <hr class="sb-div">
    <div class="sb-group">My Account</div>
    ${link('profile.php',       '👤', 'Profile',  'profile')}
    ${link('order_history.php',   '📦', 'My Orders',     'orders'       )}
    ${link('support-ticket.php', '🎫', 'Submit a Ticket','ticket'       )}
    ${link('wishlist.php',      '♡', 'Wishlist', 'wish',  wc || ''    )}
    ${link('cart.php',          '🛍', 'Cart',     'cart',  cc || ''    )}
    ${isSignedIn
      ? '<a href="logout.php" class="sb-foot" onclick="_doSignOut(event)">Sign Out</a>'
      : '<a href="login.php" class="sb-foot">Sign In</a>'}`;
}

function _doSignOut(e) {
  e.preventDefault();
  Auth.clearToken();
  Auth.clearUser();
  window.location.href = 'logout.php';
}

/* ── FOOTER ───────────────────────────────────────────────────────── */
function buildFooter() {
  return `
    <footer class="site-footer">
      <div class="container">
        <div class="ft-grid">
          <div class="ft-brand">
            <span class="ft-logo">Bejewelry</span>
            <p>Timeless jewelry crafted for those who believe every moment deserves to shine.</p>

          </div>
          <div class="ft-col"><h5>Shop</h5><ul class="ft-links">
            <li><a href="product-list.php?cat=Rings">Rings</a></li>
            <li><a href="product-list.php?cat=Necklaces">Necklaces</a></li>
            <li><a href="product-list.php?cat=Earrings">Earrings</a></li>
            <li><a href="product-list.php?cat=Bracelets">Bracelets</a></li>
            <li><a href="product-list.php?badge=sale">Sale</a></li>
            <li><a href="product-list.php?badge=new">New Arrivals</a></li>
          </ul></div>
          <div class="ft-col"><h5>Account</h5><ul class="ft-links">
            <li><a href="login.php">Login / Register</a></li>
            <li><a href="profile.php">My Profile</a></li>
            <li><a href="order_history.php">My Orders</a></li>
            <li><a href="wishlist.php">Wishlist</a></li>
          </ul></div>
          <div class="ft-col"><h5>Support</h5><ul class="ft-links">
            <li><a href="#" onclick="openSupportModal('size');return false;">Size Guide</a></li>
            <li><a href="#" onclick="openSupportModal('returns');return false;">Returns Policy</a></li>
            <li><a href="#" onclick="openSupportModal('contact');return false;">Contact Us</a></li>
            <li><a href="#" onclick="openSupportModal('faq');return false;">FAQ</a></li>
          </ul></div>
        </div>
        <div class="ft-bottom">
          <span>© ${new Date().getFullYear()} Bejewelry. All rights reserved.</span>
          <span>Privacy · Terms</span>
        </div>
      </div>
    </footer>`;
}

/* ── PAGE INIT ────────────────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', () => {
  _ensureNotifStyles();
  document.querySelectorAll('.js-header').forEach(el => {
    const minimal = el.dataset.minimal === 'true';
    el.outerHTML  = buildHeader(el.dataset.active || '', minimal);
    _initHeaderScroll();
  });
  document.querySelectorAll('.sidebar[data-active]').forEach(el => {
    el.innerHTML = buildSidebar(el.dataset.active);
  });
  document.querySelectorAll('.js-footer').forEach(el => {
    el.outerHTML = buildFooter();
  });
  _syncBadges();
  renderCartDrawer();
  _initSearchClose();
  syncCartFromServer();
  loadNotifications(true);
  if (!_notifState.pollTimer) {
    _notifState.pollTimer = window.setInterval(() => loadNotifications(true), 45000);
  }
});

function _initHeaderScroll() {
  const hdr = document.getElementById('siteHeader');
  if (!hdr) return;
  let lastY = 0;
  window.addEventListener('scroll', () => {
    const y = window.scrollY;
    hdr.classList.toggle('scrolled', y > 30);
    hdr.classList.toggle('hdr-hidden', y > lastY && y > 120);
    lastY = y;
  }, { passive: true });
}

function _initSearchClose() {
  document.addEventListener('click', e => {
    if (!e.target.closest('#hdrSearch')) {
      document.getElementById('searchDrop')?.classList.remove('show');
      document.getElementById('hdrSearch')?.classList.remove('search-open');
    }
    if (!e.target.closest('#hdrNotifWrap')) {
      document.getElementById('hdrNotifPanel')?.classList.remove('show');
    }
  });
}

/* ── SUPPORT MODALS ──────────────────────────────────────────────── */
const _supportContent = {
  size: {
    title: 'Size Guide',
    html: `
      <p style="font-size:.85rem;color:var(--muted);margin-bottom:20px">Use the measurements below to find your perfect fit. When in between sizes, we recommend sizing up.</p>
      <h4 style="font-size:.8rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--dark);margin-bottom:10px">Rings</h4>
      <table style="width:100%;border-collapse:collapse;font-size:.82rem;margin-bottom:20px">
        <thead><tr style="background:var(--blush)">
          <th style="padding:8px 12px;text-align:left;border:1px solid var(--border)">Size</th>
          <th style="padding:8px 12px;text-align:left;border:1px solid var(--border)">Circumference (mm)</th>
          <th style="padding:8px 12px;text-align:left;border:1px solid var(--border)">Diameter (mm)</th>
        </tr></thead>
        <tbody>
          ${[['5','49.3','15.7'],['6','51.9','16.5'],['7','54.4','17.3'],['8','57.0','18.1'],['9','59.5','18.9'],['10','62.1','19.8']].map(([s,c,d])=>`<tr><td style="padding:8px 12px;border:1px solid var(--border)">${s}</td><td style="padding:8px 12px;border:1px solid var(--border)">${c}</td><td style="padding:8px 12px;border:1px solid var(--border)">${d}</td></tr>`).join('')}
        </tbody>
      </table>
      <h4 style="font-size:.8rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--dark);margin-bottom:10px">Bracelets</h4>
      <table style="width:100%;border-collapse:collapse;font-size:.82rem;margin-bottom:20px">
        <thead><tr style="background:var(--blush)">
          <th style="padding:8px 12px;text-align:left;border:1px solid var(--border)">Size</th>
          <th style="padding:8px 12px;text-align:left;border:1px solid var(--border)">Wrist Size (cm)</th>
        </tr></thead>
        <tbody>
          ${[['XS','14–15'],['S','15–16'],['M','16–17'],['L','17–18'],['XL','18–19']].map(([s,w])=>`<tr><td style="padding:8px 12px;border:1px solid var(--border)">${s}</td><td style="padding:8px 12px;border:1px solid var(--border)">${w}</td></tr>`).join('')}
        </tbody>
      </table>
      <h4 style="font-size:.8rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--dark);margin-bottom:10px">Necklaces</h4>
      <table style="width:100%;border-collapse:collapse;font-size:.82rem;margin-bottom:20px">
        <thead><tr style="background:var(--blush)">
          <th style="padding:8px 12px;text-align:left;border:1px solid var(--border)">Length</th>
          <th style="padding:8px 12px;text-align:left;border:1px solid var(--border)">cm</th>
          <th style="padding:8px 12px;text-align:left;border:1px solid var(--border)">Style / Sits At</th>
        </tr></thead>
        <tbody>
          ${[['14"','35 cm','Choker — base of neck'],['16"','40 cm','Collar — collarbone'],['18"','45 cm','Princess — below collarbone'],['20"','50 cm','Matinee — above chest'],['24"','60 cm','Opera — mid-chest'],].map(([l,c,s])=>`<tr><td style="padding:8px 12px;border:1px solid var(--border)">${l}</td><td style="padding:8px 12px;border:1px solid var(--border)">${c}</td><td style="padding:8px 12px;border:1px solid var(--border)">${s}</td></tr>`).join('')}
        </tbody>
      </table>
      <h4 style="font-size:.8rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--dark);margin-bottom:10px">Earrings</h4>
      <table style="width:100%;border-collapse:collapse;font-size:.82rem">
        <thead><tr style="background:var(--blush)">
          <th style="padding:8px 12px;text-align:left;border:1px solid var(--border)">Type</th>
          <th style="padding:8px 12px;text-align:left;border:1px solid var(--border)">Typical Size</th>
          <th style="padding:8px 12px;text-align:left;border:1px solid var(--border)">Notes</th>
        </tr></thead>
        <tbody>
          ${[['Studs','4–8 mm','Subtle, everyday wear'],['Hoops','20–40 mm diameter','Small to statement sizes'],['Drop','2–5 cm length','Hangs below the earlobe'],['Dangle','5–8 cm length','Movement, dressy occasions'],].map(([t,s,n])=>`<tr><td style="padding:8px 12px;border:1px solid var(--border)">${t}</td><td style="padding:8px 12px;border:1px solid var(--border)">${s}</td><td style="padding:8px 12px;border:1px solid var(--border)">${n}</td></tr>`).join('')}
        </tbody>
      </table>`
  },
  returns: {
    title: 'Returns Policy',
    html: `
      <div style="display:flex;flex-direction:column;gap:18px;font-size:.85rem;color:var(--dark);line-height:1.7">
        <div style="background:var(--blush);border-radius:var(--r-md);padding:14px 16px">
          <strong>30-Day Return Window</strong><br>
          <span style="color:var(--muted)">We accept returns within 30 days of delivery for unused, unworn items in their original packaging.</span>
        </div>
        <div>
          <strong>Eligible Items</strong>
          <ul style="margin-top:8px;padding-left:18px;color:var(--muted);display:flex;flex-direction:column;gap:6px">
            <li>Items in original, unworn condition</li>
            <li>Items with all original tags and packaging</li>
            <li>Non-personalized / non-engraved jewelry</li>
          </ul>
        </div>
        <div>
          <strong>Non-Returnable Items</strong>
          <ul style="margin-top:8px;padding-left:18px;color:var(--muted);display:flex;flex-direction:column;gap:6px">
            <li>Personalized or engraved pieces</li>
            <li>Items showing signs of wear or damage</li>
            <li>Sale or clearance items</li>
          </ul>
        </div>
        <div>
          <strong>How to Return</strong>
          <ol style="margin-top:8px;padding-left:18px;color:var(--muted);display:flex;flex-direction:column;gap:6px">
            <li>Contact us at <a href="mailto:support@bejewelry.ph" style="color:var(--rose)">support@bejewelry.ph</a></li>
            <li>Include your order number and reason for return</li>
            <li>We'll send a prepaid return label within 1–2 business days</li>
            <li>Refund is processed within 5–7 business days after we receive the item</li>
          </ol>
        </div>
      </div>`
  },
  contact: {
    title: 'Contact Us',
    html: `
      <p style="font-size:.85rem;color:var(--muted);margin-bottom:20px">We'd love to hear from you! Fill out the form below and we'll get back to you within 24 hours.</p>
      <div style="display:flex;flex-direction:column;gap:14px">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
          <div>
            <label style="font-size:.7rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--muted);display:block;margin-bottom:6px">Name</label>
            <input type="text" id="ctName" placeholder="Your name" style="width:100%;padding:10px 13px;font-size:.85rem;border:1.5px solid var(--border-mid);border-radius:var(--r-md);outline:none;font-family:var(--fb);color:var(--dark);background:var(--white);box-sizing:border-box">
          </div>
          <div>
            <label style="font-size:.7rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--muted);display:block;margin-bottom:6px">Email</label>
            <input type="email" id="ctEmail" placeholder="your@email.com" style="width:100%;padding:10px 13px;font-size:.85rem;border:1.5px solid var(--border-mid);border-radius:var(--r-md);outline:none;font-family:var(--fb);color:var(--dark);background:var(--white);box-sizing:border-box">
          </div>
        </div>
        <div>
          <label style="font-size:.7rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--muted);display:block;margin-bottom:6px">Subject</label>
          <select id="ctSubject" style="width:100%;padding:10px 13px;font-size:.85rem;border:1.5px solid var(--border-mid);border-radius:var(--r-md);outline:none;font-family:var(--fb);color:var(--dark);background:var(--white);appearance:none">
            <option value="">Select a topic…</option>
            <option>Order Issue</option>
            <option>Return / Refund</option>
            <option>Product Question</option>
            <option>Shipping Inquiry</option>
            <option>Other</option>
          </select>
        </div>
        <div>
          <label style="font-size:.7rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:var(--muted);display:block;margin-bottom:6px">Message</label>
          <textarea id="ctMsg" placeholder="How can we help you?" rows="4" style="width:100%;padding:10px 13px;font-size:.85rem;border:1.5px solid var(--border-mid);border-radius:var(--r-md);outline:none;font-family:var(--fb);color:var(--dark);background:var(--white);resize:vertical;box-sizing:border-box"></textarea>
        </div>
        <button onclick="submitContact()" style="align-self:flex-start;background:var(--rose);color:var(--white);border:none;padding:11px 28px;border-radius:var(--r-pill);font-size:.82rem;font-weight:600;letter-spacing:.06em;text-transform:uppercase;cursor:pointer;font-family:var(--fb)">Send Message</button>
        <div style="border-top:1px solid var(--border);padding-top:16px;display:flex;gap:24px;font-size:.82rem;color:var(--muted)">
          <span>📧 support@bejewelry.ph</span>
          <span>📍 Mon–Sat, 9am–6pm</span>
        </div>
      </div>`
  },
  faq: {
    title: 'Frequently Asked Questions',
    html: `
      <div style="display:flex;flex-direction:column;gap:4px" id="faqList">
        ${[
          ['What materials are your jewelry made of?','Our jewelry is crafted from sterling silver, 18k gold-plated brass, and stainless steel. Each product page lists the exact material used.'],
          ['How long does shipping take?','Standard shipping takes 3–5 business days. Express shipping (1–2 days) is available at checkout. Free shipping on orders ₱2,000 and above.'],
          ['Can I track my order?','Yes! Once your order ships, you\'ll receive a tracking number via email. You can also view your order status under My Orders in your account.'],
          ['Do you offer gift wrapping?','Complimentary gift wrapping is available on all orders. Just select the option at checkout and add a personal message.'],
          ['How do I care for my jewelry?','Store pieces in a cool, dry place. Avoid contact with perfumes, lotions, and water. Wipe clean with a soft cloth after wearing.'],
          ['Can I resize a ring after purchase?','Some rings can be resized — contact us within 7 days of delivery. Engraved rings cannot be resized.'],
        ].map(([q,a],i)=>`
          <div style="border:1px solid var(--border);border-radius:var(--r-md);overflow:hidden">
            <button onclick="toggleFaq(${i})" style="width:100%;text-align:left;padding:14px 16px;background:none;border:none;cursor:pointer;font-family:var(--fb);font-size:.87rem;font-weight:600;color:var(--dark);display:flex;justify-content:space-between;align-items:center">
              ${q}<span id="faq-icon-${i}" style="font-size:1rem;transition:transform .2s;display:inline-block">+</span>
            </button>
            <div id="faq-ans-${i}" style="display:none;padding:0 16px 14px;font-size:.83rem;color:var(--muted);line-height:1.7">${a}</div>
          </div>`).join('')}
      </div>`
  }
};

function openSupportModal(type) {
  const data = _supportContent[type];
  if (!data) return;

  // Remove existing modal
  document.getElementById('supportModal')?.remove();

  const m = document.createElement('div');
  m.id = 'supportModal';
  m.style.cssText = 'position:fixed;inset:0;z-index:9999;display:flex;align-items:center;justify-content:center;padding:20px;background:rgba(0,0,0,.45)';
  m.innerHTML = `
    <div style="background:var(--white);border-radius:var(--r-xl);width:100%;max-width:580px;max-height:82vh;box-shadow:0 20px 60px rgba(0,0,0,.18);display:flex;flex-direction:column;overflow:hidden">
      <div style="display:flex;align-items:center;justify-content:space-between;padding:22px 24px 16px;border-bottom:1px solid var(--border);position:sticky;top:0;background:var(--white);z-index:1">
        <h3 style="font-family:var(--fd);font-size:1.15rem;font-weight:600;color:var(--dark);margin:0">${data.title}</h3>
        <button onclick="document.getElementById('supportModal').remove()" style="background:var(--blush);border:none;width:30px;height:30px;border-radius:50%;cursor:pointer;font-size:1.1rem;color:var(--muted);display:flex;align-items:center;justify-content:center;line-height:1">×</button>
      </div>
      <div style="padding:22px 24px;overflow-y:auto;flex:1">${data.html}</div>
    </div>`;
  m.addEventListener('click', e => { if (e.target === m) m.remove(); });
  document.body.appendChild(m);
}

function toggleFaq(i) {
  const ans  = document.getElementById(`faq-ans-${i}`);
  const icon = document.getElementById(`faq-icon-${i}`);
  const open = ans.style.display !== 'none';
  ans.style.display  = open ? 'none' : 'block';
  icon.textContent   = open ? '+' : '−';
}

function submitContact() {
  const name = document.getElementById('ctName')?.value.trim();
  const email = document.getElementById('ctEmail')?.value.trim();
  const msg = document.getElementById('ctMsg')?.value.trim();
  if (!name || !email || !msg) {
    if (typeof toast === 'function') toast('Please fill in all required fields.', 'warn');
    return;
  }
  document.getElementById('supportModal')?.remove();
  if (typeof toast === 'function') toast('Message sent! We\'ll get back to you within 24 hours.', 'ok');
}

/* ── SESSION TIMEOUT (CLIENT IDLE REDIRECT) ─────────────────────── */
(function initSessionIdleLogout() {
  if (window.__bjSessionIdleBound) return;
  window.__bjSessionIdleBound = true;

  const isLoggedIn = typeof Auth !== 'undefined' && typeof Auth.isLoggedIn === 'function' && Auth.isLoggedIn();
  if (!isLoggedIn) return;

  const configuredSeconds = Number(window.BEJEWELRY_SESSION_TIMEOUT_SECONDS || 120);
  const timeoutMs = Math.max(60000, configuredSeconds * 1000);
  const warningMs = Math.min(30000, Math.max(5000, timeoutMs - 5000));
  const warningSeconds = Math.floor(warningMs / 1000);
  let warningTimer = null;
  let logoutTimer = null;

  const hideWarning = () => {
    const node = document.getElementById('bjSessionWarning');
    if (node) node.remove();
  };

  const showWarning = () => {
    hideWarning();
    const box = document.createElement('div');
    box.id = 'bjSessionWarning';
    box.style.cssText = 'position:fixed;right:16px;bottom:16px;z-index:10000;max-width:340px;background:#241418;color:#fff;border-radius:12px;padding:12px 14px;box-shadow:0 10px 24px rgba(0,0,0,.25);font-size:.82rem;line-height:1.5';
    box.innerHTML = '<div style="font-weight:700;margin-bottom:4px">Session expiring soon</div>' +
      '<div>You will be logged out in ' + warningSeconds + ' seconds due to inactivity.</div>';
    document.body.appendChild(box);
  };

  const resetTimer = () => {
    hideWarning();
    if (warningTimer) clearTimeout(warningTimer);
    if (logoutTimer) clearTimeout(logoutTimer);

    warningTimer = setTimeout(showWarning, Math.max(0, timeoutMs - warningMs));
    logoutTimer = setTimeout(() => {
      window.location.href = 'logout.php?reason=timeout';
    }, timeoutMs);
  };

  ['mousemove', 'mousedown', 'keydown', 'scroll', 'touchstart', 'click'].forEach((evt) => {
    window.addEventListener(evt, resetTimer, { passive: true });
  });

  resetTimer();
})();