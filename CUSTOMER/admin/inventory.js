/* BEJEWELRY ADMIN — Inventory (MySQL only). Data from window.__INVENTORY__ */

const D = window.__INVENTORY__ || {};
const LOW_STOCK_THRESHOLD = D.stats && D.stats.low_threshold != null ? D.stats.low_threshold : 5;
let currentFilter = D.filter || 'all';
let currentSearch = D.search || '';
let inventoryData = D.items || [];

function toast(msg, duration) {
  const el = document.getElementById('toastEl');
  if (el) { el.textContent = msg; el.classList.add('on'); setTimeout(() => el.classList.remove('on'), duration || 2800); }
}
function setLoading(on) {
  const el = document.getElementById('loadingBar');
  if (el) el.classList.toggle('active', on);
}
function esc(str) {
  const d = document.createElement('div');
  d.textContent = str ?? '';
  return d.innerHTML;
}
function formatCurrency(amount) {
  return '₱' + Number(amount || 0).toLocaleString('en-PH');
}
function clearSkel(el) {
  if (!el) return;
  el.classList.remove('skel', 'skel-val', 'skel-text');
}
function stockStatus(qty, threshold) {
  const t = threshold ?? LOW_STOCK_THRESHOLD;
  if (qty <= 0) return 'outofstock';
  if (qty <= 2) return 'critical';
  if (qty <= t) return 'low';
  return 'instock';
}
function stockBadgeHtml(status) {
  const map = {
    instock: '<span class="badge b-instock">✓ In Stock</span>',
    low: '<span class="badge b-low">⚠ Low Stock</span>',
    critical: '<span class="badge b-critical">🔴 Critical</span>',
    outofstock: '<span class="badge b-outofstock">✕ Out of Stock</span>'
  };
  return map[status] || map.instock;
}
function stockBarHtml(qty, maxStock) {
  const max = maxStock || 30;
  const pct = Math.min(100, Math.round((qty / max) * 100));
  const cls = pct >= 50 ? 'high' : pct >= 20 ? 'medium' : 'low';
  return '<div class="stock-bar-wrap"><div class="stock-bar"><div class="stock-bar-fill ' + cls + '" style="width:' + pct + '%"></div></div><span class="stock-bar-num">' + pct + '%</span></div>';
}
function categoryIcon(cat) {
  const map = { rings:'💍', necklaces:'📿', earrings:'✨', bracelets:'🔮', pendants:'💎' };
  return map[(cat || '').toLowerCase()] || '💎';
}

function loadUser() {
  const data = D.user;
  const name = (data && data.name) || 'Admin';
  const av = (name || '?').split(' ').map(w => w[0]).join('').slice(0, 2).toUpperCase();
  const a = document.getElementById('sbAvatar'); const u = document.getElementById('sbUsername'); const r = document.getElementById('sbUserRole');
  if (a) a.textContent = av; if (u) u.textContent = name; if (r) r.textContent = (data && data.role) || '—';
}
function loadBadges() {
  const b = D.badges || {};
  if (b.pending_orders) { const e = document.getElementById('badgeOrders'); if (e) e.textContent = b.pending_orders; }
  if (b.new_products) { const e = document.getElementById('badgeProducts'); if (e) e.textContent = b.new_products; }
  if (b.low_stock) { const e = document.getElementById('badgeInventory'); if (e) e.textContent = b.low_stock; }
  if (b.pending_reviews) { const e = document.getElementById('badgeReviews'); if (e) e.textContent = b.pending_reviews; }
}
function loadStats() {
  const s = D.stats || {};
  const setVal = (id, val) => { const el = document.getElementById(id); clearSkel(el); if (el) el.textContent = val ?? '—'; };
  const setTrend = (id, text) => { const el = document.getElementById(id); clearSkel(el); if (el) el.textContent = text ?? ''; };
  setVal('valTotal', s.total);
  setVal('valInStock', s.in_stock);
  setVal('valLow', s.low_stock);
  setVal('valUnits', s.total_units);
  setTrend('trendTotal', 'Across all categories');
  setTrend('trendInStock', (s.out_of_stock ?? 0) + ' out of stock');
  setTrend('trendLow', 'Below ' + (s.low_threshold ?? LOW_STOCK_THRESHOLD) + ' units');
  setTrend('trendUnits', 'Total units on hand');
  const sub = document.getElementById('invSubtitle');
  if (sub) sub.textContent = 'Stock management — ' + (s.total ?? '—') + ' products · ' + (s.total_units ?? '—') + ' units total';
  const lowCount = s.low_stock || 0;
  const alertBar = document.getElementById('alertBar');
  if (alertBar) {
    if (lowCount > 0) {
      document.getElementById('alertText').innerHTML = '<strong>' + lowCount + ' product' + (lowCount > 1 ? 's' : '') + '</strong> ' + (lowCount > 1 ? 'are' : 'is') + ' running critically low on stock.';
      alertBar.classList.remove('hidden');
    } else alertBar.classList.add('hidden');
  }
}
function loadInventory() {
  const tbody = document.getElementById('inventoryBody');
  const counts = D.counts || {};
  const labelMap = { all:'All', instock:'In Stock', low:'Low Stock', outofstock:'Out of Stock' };
  document.querySelectorAll('#filtersBar .fb').forEach(btn => {
    const f = btn.dataset.filter;
    const count = counts[f];
    btn.textContent = count !== undefined ? (labelMap[f] || f) + ' (' + count + ')' : (labelMap[f] || f);
    btn.classList.toggle('on', (f || 'all') === currentFilter);
  });
  if (!inventoryData.length) {
    tbody.innerHTML = '<tr><td colspan="8"><div class="empty-state"><span class="empty-icon">📦</span>No products found</div></td></tr>';
    return;
  }
  tbody.innerHTML = '';
  inventoryData.forEach(item => {
    const status = item.status || stockStatus(item.stock_qty, item.low_threshold);
    const isLow = status === 'low' || status === 'outofstock';
    const thumb = item.image_url ? '<img src="' + esc(item.image_url) + '" alt="' + esc(item.name) + '" style="width:100%;height:100%;object-fit:cover;border-radius:var(--r-md);cursor:pointer" onclick="openImageZoom(' + item.id + ', \'' + esc(item.image_url) + '\', \'' + esc(item.name) + '\')">' : '<span>💎</span>';
    const tr = document.createElement('tr');
    tr.id = 'row-' + item.id;
    tr.innerHTML = '<td class="prod-name"><div class="prod-thumb" style="width:40px;height:40px;border-radius:var(--r-md);background:var(--blush-mid);display:flex;align-items:center;justify-content:center;margin-right:8px;flex-shrink:0;cursor:pointer" onclick="item.image_url && openImageZoom(' + item.id + ', \'' + esc(item.image_url) + '\', \'' + esc(item.name) + '\')">' + thumb + '</div>' + esc(item.name) + '</td><td class="sku">' + esc(item.sku) + '</td><td style="color:var(--muted)">' + esc(item.category) + '</td><td class="price"><span class="price-val">' + formatCurrency(item.price) + '</span></td><td><span class="stock-qty-readonly" title="Quantity updates via Restock only">' + esc(String(item.stock_qty)) + '</span></td><td>' + stockBarHtml(item.stock_qty, item.max_stock) + '</td><td id="status-' + item.id + '">' + stockBadgeHtml(status) + '</td><td><button class="btn btn-ghost btn-sm" onclick="openRestockModal(' + item.id + ')" style="padding:6px 14px;font-size:.65rem">↺ Restock</button></td>';
    tbody.appendChild(tr);
  });
}

let transactionsData = D.transactions || [];
let currentTab = 'products';

function formatDateTime(dateStr) {
  if (!dateStr) return '—';
  const date = new Date(dateStr);
  return date.toLocaleString('en-PH', { 
    month: 'short', 
    day: '2-digit', 
    year: 'numeric', 
    hour: '2-digit', 
    minute: '2-digit',
    second: '2-digit',
    hour12: true 
  });
}

function loadTransactions() {
  const tbody = document.getElementById('transactionsBody');
  if (!transactionsData || transactionsData.length === 0) {
    tbody.innerHTML = '<tr><td colspan="7"><div class="empty-state"><span class="empty-icon">📊</span>No transactions found</div></td></tr>';
    return;
  }
  tbody.innerHTML = '';
  transactionsData.forEach(t => {
    const tr = document.createElement('tr');
    const qtyClass = t.qty_added > 0 ? 'qty-positive' : 'qty-negative';
    const qtySign = t.qty_added > 0 ? '+' : '';
    tr.innerHTML = '<td style="font-size:.85rem;white-space:nowrap">' + formatDateTime(t.created_at) + '</td>' +
                   '<td style="color:var(--muted)">' + esc(t.product_name) + '</td>' +
                   '<td class="' + qtyClass + '" style="font-weight:600">' + qtySign + esc(String(t.qty_added)) + '</td>' +
                   '<td style="font-family:var(--fd);font-weight:600;color:var(--dark)">' + formatCurrency(t.price) + '</td>' +
                   '<td style="color:var(--muted)">' + esc(String(t.stock_before)) + ' units</td>' +
                   '<td style="color:var(--muted)">' + esc(String(t.stock_after)) + ' units</td>' +
                   '<td style="font-size:.9rem;color:var(--muted)">' + esc(t.updated_by) + '</td>';
    tbody.appendChild(tr);
  });
}

function switchTab(tabName) {
  currentTab = tabName;
  const productsTab = document.getElementById('productsTab');
  const transactionsTab = document.getElementById('transactionsTab');
  
  document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.classList.toggle('on', btn.dataset.tab === tabName);
  });
  
  if (tabName === 'products') {
    productsTab.classList.remove('hidden');
    transactionsTab.classList.add('hidden');
  } else if (tabName === 'transactions') {
    productsTab.classList.add('hidden');
    transactionsTab.classList.remove('hidden');
  }
}

document.querySelectorAll('.tab-btn').forEach(btn => {
  btn.addEventListener('click', function () {
    switchTab(this.dataset.tab);
  });
});

// Filters buttons are wired later; accidental code removed to fix syntax error.
function getCsrfToken() {
  const meta = document.querySelector('meta[name="csrf-token"]');
  return meta ? meta.getAttribute('content') : '';
}
function submitPriceModal() {
  const id = document.getElementById('priceProductId').value;
  const raw = parseFloat(String(document.getElementById('priceInput').value).replace(/,/g, ''), 10);
  if (!id || isNaN(raw) || raw < 0) { toast('Enter a valid price.'); return; }
  const form = document.createElement('form');
  form.method = 'post';
  form.action = 'inventory_action.php' + (window.location.search || '');
  form.innerHTML = '<input type="hidden" name="action" value="update_price"><input type="hidden" name="product_id" value="' + esc(id) + '"><input type="hidden" name="price" value="' + esc(String(raw)) + '"><input type="hidden" name="csrf_token" value="' + esc(getCsrfToken()) + '">';
  document.body.appendChild(form);
  form.submit();
}
function openRestockModal(productId) {
  const item = inventoryData.find(i => String(i.id) === String(productId));
  if (!item) return;
  const status = item.status || stockStatus(item.stock_qty, item.low_threshold);
  document.getElementById('restockModalTitle').textContent = 'Restock — ' + item.name;
  document.getElementById('restockProductIcon').textContent = categoryIcon(item.category);
  document.getElementById('restockProductName').textContent = item.name;
  document.getElementById('restockProductMeta').textContent = item.sku + ' · ' + item.category;
  document.getElementById('restockCurrentQty').textContent = item.stock_qty ?? '—';
  document.getElementById('restockCurrentBadge').innerHTML = stockBadgeHtml(status);
  document.getElementById('restockAddQty').value = '';
  document.getElementById('restockReason').value = '';
  document.getElementById('restockNotes').value = '';
  document.getElementById('restockSubmitBtn').onclick = () => submitRestock(productId, item.name);
  openModal('restockModal');
}
function submitRestock(productId, productName) {
  const addQty = parseInt(document.getElementById('restockAddQty').value, 10);
  const reason = document.getElementById('restockReason').value;
  if (isNaN(addQty) || addQty <= 0) { toast('Please enter a valid quantity.'); return; }
  if (!reason) { toast('Please select a restock reason.'); return; }
  const form = document.createElement('form');
  form.method = 'post';
  form.action = 'inventory_action.php';
  form.innerHTML = '<input type="hidden" name="action" value="restock"><input type="hidden" name="product_id" value="' + productId + '"><input type="hidden" name="add_qty" value="' + addQty + '"><input type="hidden" name="reason" value="' + esc(reason) + '"><input type="hidden" name="notes" value="' + esc(document.getElementById('restockNotes').value) + '"><input type="hidden" name="csrf_token" value="' + esc(getCsrfToken()) + '">';
  document.body.appendChild(form);
  form.submit();
}

document.querySelectorAll('#filtersBar .fb').forEach(btn => {
  btn.addEventListener('click', function () {
    const f = this.dataset.filter || 'all';
    location.href = 'inventory.php?filter=' + encodeURIComponent(f) + (currentSearch ? '&search=' + encodeURIComponent(currentSearch) : '');
  });
});
function filterToLowStock() {
  document.querySelectorAll('#filtersBar .fb').forEach(b => b.classList.remove('on'));
  const lowBtn = document.querySelector('#filtersBar .fb[data-filter="low"]');
  if (lowBtn) lowBtn.classList.add('on');
  location.href = 'inventory.php?filter=low' + (currentSearch ? '&search=' + encodeURIComponent(currentSearch) : '');
}
const invSearchEl = document.getElementById('invSearch');
if (invSearchEl) {
  invSearchEl.value = currentSearch;
  invSearchEl.addEventListener('keydown', function (e) {
    if (e.key === 'Enter') location.href = 'inventory.php?filter=' + encodeURIComponent(currentFilter) + '&search=' + encodeURIComponent(this.value.trim());
  });
}
let globalTimeout;
const globalSearchEl = document.getElementById('globalSearch');
if (globalSearchEl) globalSearchEl.addEventListener('input', e => {
  clearTimeout(globalTimeout);
  const q = e.target.value.trim();
  if (q.length < 2) return;
  globalTimeout = setTimeout(() => { location.href = 'search.php?q=' + encodeURIComponent(q); }, 500);
});

function openModal(id) { const el = document.getElementById(id); if (el) el.classList.add('on'); }
function closeModal(id) { const el = document.getElementById(id); if (el) el.classList.remove('on'); }
document.querySelectorAll('.modal-bg').forEach(m => m.addEventListener('click', e => { if (e.target === m) m.classList.remove('on'); }));
function openImageZoom(productId, imageUrl, productName) {
  const modal = document.getElementById('imageZoomModal');
  const img = document.getElementById('zoomImage');
  if (modal && img) {
    img.src = imageUrl;
    img.alt = productName;
    document.getElementById('zoomImageTitle').textContent = productName;
    modal.classList.add('on');
  }
}

function handleBulkRestock() {
  if (!confirm('Send a bulk restock order for all low-stock items?')) return;
  const form = document.createElement('form');
  form.method = 'post';
  form.action = 'inventory_action.php';
  form.innerHTML = '<input type="hidden" name="action" value="bulk_restock"><input type="hidden" name="csrf_token" value="' + esc(getCsrfToken()) + '">';
  document.body.appendChild(form);
  form.submit();
}
function handleExport() {
  location.href = 'inventory_export.php?filter=' + encodeURIComponent(currentFilter) + '&search=' + encodeURIComponent(currentSearch);
}
function handleNotifications() { location.href = 'notifications.php'; }
function handleLogout() {
  if (typeof window.adminConfirm === 'function') {
    window.adminConfirm('Log out of Bejewelry Admin?', () => { location.href = '../logout.php'; }, { okText: 'Log out' });
    return;
  }
  location.href = '../logout.php';
}

function loadPageData() {
  setLoading(false);
  loadUser();
  loadBadges();
  loadStats();
  loadInventory();
  loadTransactions();
}
document.addEventListener('DOMContentLoaded', loadPageData);
