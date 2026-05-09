/* BEJEWELRY ADMIN — Customers (MySQL only). Data from window.__CUSTOMERS__ */

const D = window.__CUSTOMERS__ || {};
let currentPage = D.page || 1;
let currentFilter = D.filter || 'all';
let currentSearch = D.search || '';
let currentView = localStorage.getItem('bejewelry_customer_view') || 'grid';

function customersUrl() {
  const p = new URLSearchParams();
  if (currentPage > 1) p.set('page', currentPage);
  if (currentFilter !== 'all') p.set('filter', currentFilter);
  if (currentSearch) p.set('search', currentSearch);
  return 'customers.php?' + p.toString();
}

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
function formatDate(dateStr) {
  if (!dateStr) return '—';
  return new Date(dateStr).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' });
}
function clearSkel(el) {
  if (!el) return;
  el.classList.remove('skel', 'skel-val', 'skel-text');
}
function capitalize(s) {
  return s ? s.charAt(0).toUpperCase() + s.slice(1) : '';
}
function initials(name) {
  return (name || '?').split(' ').map(w => w[0]).join('').slice(0, 3).toUpperCase();
}

function tierBadgeHtml(tier) {
  const map = { vip: '<span class="tier-badge tier-vip">💎 VIP</span>', new: '<span class="tier-badge tier-new">✨ New</span>', regular: '<span class="tier-badge tier-reg">Regular</span>' };
  return map[(tier || '').toLowerCase()] || map.regular;
}
function lockBadgeHtml(isLocked) {
  return isLocked
    ? '<span class="tier-badge tier-reg" style="background:#fff1f1;color:#b42318;border:1px solid #f2c0c0">Locked</span>'
    : '<span class="tier-badge tier-reg" style="background:#eef8f0;color:#16703d;border:1px solid #c8ead5">Active</span>';
}
function archiveBadgeHtml(isArchived) {
  return isArchived
    ? '<span class="tier-badge tier-reg" style="background:#f4f1ff;color:#5b4db3;border:1px solid #d8d2ff">Archived</span>'
    : '<span class="tier-badge tier-reg" style="background:#eef8f0;color:#16703d;border:1px solid #c8ead5">Live</span>';
}
function badgeClass(status) {
  const map = { pending:'b-pending', processing:'b-processing', shipped:'b-shipped', delivered:'b-delivered', cancelled:'b-cancelled' };
  return map[(status || '').toLowerCase()] || 'b-pending';
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
  setVal('valVip', s.vip);
  setVal('valNew', s.new_this_month);
  setVal('valRepeat', s.repeat_rate != null ? s.repeat_rate + '%' : '—');
  setTrend('trendTotal', 'All time');
  setTrend('trendVip', '3+ orders or ₱5k+');
  setTrend('trendNew', (s.new_pct_change >= 0 ? '↑' : '↓') + ' ' + Math.abs(s.new_pct_change || 0) + '% vs last month');
  setTrend('trendRepeat', (s.repeat_pct_change >= 0 ? '↑' : '↓') + ' ' + Math.abs(s.repeat_pct_change || 0) + '% vs last month');
  const sub = document.getElementById('custSubtitle');
  if (sub) sub.textContent = (s.total ?? '—') + ' registered customers · ' + (s.new_this_month ?? '—') + ' new this month';
}
function loadCustomers() {
  const grid = document.getElementById('custGrid');
  const tableWrap = document.getElementById('custTableWrap');
  const tableBody = document.getElementById('custTableBody');
  const customers = D.customers || [];
  const counts = D.counts || {};
  document.querySelectorAll('#filtersBar .fb').forEach(btn => {
    const f = btn.dataset.filter;
    const count = counts[f];
    btn.textContent = count !== undefined ? capitalize(f) + ' (' + count + ')' : capitalize(f);
    btn.classList.toggle('on', (f || 'all') === currentFilter);
  });
  if (customers.length === 0) {
    grid.innerHTML = '<div class="empty-state" style="grid-column:1/-1"><span class="empty-icon">👤</span>No customers found</div>';
    if (tableBody) tableBody.innerHTML = '<tr><td colspan="7"><div class="empty-state"><span class="empty-icon">👤</span>No customers found</div></td></tr>';
    renderPagination(0, 0);
    applyCustomerView();
    return;
  }
  grid.innerHTML = '';
  if (tableBody) tableBody.innerHTML = '';
  customers.forEach(c => {
    const avInit = initials(c.name);
    const tier = (c.tier || '').toLowerCase();
    const div = document.createElement('div');
    div.className = 'cust-card';
    div.onclick = () => openCustomerModal(c.id);
    const avHtml = c.avatar_url
      ? '<div class="cc-av"><img src="' + esc(c.avatar_url) + '" alt="" style="width:100%;height:100%;border-radius:50%;object-fit:cover"/></div>'
      : '<div class="cc-av">' + esc(avInit) + '</div>';
    div.innerHTML = avHtml + '<div class="cc-tier">' + tierBadgeHtml(tier) + ' ' + lockBadgeHtml(!!c.is_locked) + ' ' + archiveBadgeHtml(!!c.is_archived) + '</div><div class="cc-name">' + esc(c.name) + '</div><div class="cc-email">' + esc(c.email) + '</div><div class="cc-stats"><div><div class="cc-stat-v">' + esc(c.order_count ?? '—') + '</div><div class="cc-stat-l">Orders</div></div><div><div class="cc-stat-v">' + formatCurrency(c.total_spent) + '</div><div class="cc-stat-l">Spent</div></div><div><div class="cc-stat-v">' + (c.avg_rating != null ? esc(c.avg_rating) : '—') + '</div><div class="cc-stat-l">Rating</div></div></div>';
    grid.appendChild(div);

    if (tableBody) {
      const tr = document.createElement('tr');
      tr.style.cursor = 'pointer';
      tr.onclick = () => openCustomerModal(c.id);
      tr.innerHTML = '<td><div class="cust-name">' + (c.avatar_url ? '<img src="' + esc(c.avatar_url) + '" alt="" style="width:34px;height:34px;border-radius:50%;object-fit:cover"/>' : '<span class="cc-av" style="width:34px;height:34px;font-size:.65rem;margin:0">' + esc(avInit) + '</span>') + esc(c.name) + '</div></td>' +
                     '<td class="cust-email">' + esc(c.email) + '</td>' +
                     '<td class="cust-phone">' + esc(c.phone || '—') + '</td>' +
                     '<td>' + esc(String(c.order_count ?? 0)) + '</td>' +
                     '<td>' + formatCurrency(c.total_spent) + '</td>' +
                     '<td><span class="cust-tier">' + tierBadgeHtml(tier) + '</span></td>' +
                     '<td class="cust-status">' + lockBadgeHtml(!!c.is_locked) + ' ' + archiveBadgeHtml(!!c.is_archived) + '</td>';
      tableBody.appendChild(tr);
    }
  });
  renderPagination(D.total_pages || 1, D.page || 1);
  applyCustomerView();
}
function renderPagination(totalPages, page) {
  const wrap = document.getElementById('pagination');
  if (!wrap || totalPages <= 1) { if (wrap) wrap.innerHTML = ''; return; }
  let html = '<button class="page-btn" onclick="location.href=\'customers.php?page=' + (page - 1) + '&filter=' + encodeURIComponent(currentFilter) + '&search=' + encodeURIComponent(currentSearch) + '\'"' + (page === 1 ? ' disabled' : '') + '>‹</button>';
  for (let i = 1; i <= totalPages; i++) {
    if (i === 1 || i === totalPages || (i >= page - 1 && i <= page + 1)) {
      html += '<button class="page-btn' + (i === page ? ' active' : '') + '" onclick="location.href=\'customers.php?page=' + i + '&filter=' + encodeURIComponent(currentFilter) + '&search=' + encodeURIComponent(currentSearch) + '\'">' + i + '</button>';
    } else if (i === page - 2 || i === page + 2) html += '<span style="color:var(--muted-light);padding:0 4px">…</span>';
  }
  html += '<button class="page-btn" onclick="location.href=\'customers.php?page=' + (page + 1) + '&filter=' + encodeURIComponent(currentFilter) + '&search=' + encodeURIComponent(currentSearch) + '\'"' + (page === totalPages ? ' disabled' : '') + '>›</button>';
  wrap.innerHTML = html;
}
function openCustomerModal(customerId) {
  const data = (D.customer_details || {})[customerId];
  document.getElementById('modalTitle').textContent = data ? 'Customer Profile — ' + data.name : 'Customer Profile';
  const avEl = document.getElementById('modalAv');
  if (avEl) {
    if (data && data.avatar_url) {
      avEl.innerHTML = '<img src="' + esc(data.avatar_url) + '" alt="" style="width:100%;height:100%;border-radius:50%;object-fit:cover"/>';
    } else {
      avEl.textContent = data ? initials(data.name) : '—';
    }
  }
  document.getElementById('modalName').textContent = data ? data.name : '—';
  document.getElementById('modalContact').textContent = data ? (data.email || '—') + ' · ' + (data.phone || '—') : '—';
  document.getElementById('modalTierBadge').innerHTML = data ? tierBadgeHtml(data.tier) : '';
  const lockBtn = document.getElementById('modalToggleLockBtn');
  if (lockBtn) {
    lockBtn.style.display = data ? 'inline-flex' : 'none';
    lockBtn.textContent = data && data.is_locked ? 'Unlock Account' : 'Lock Account';
    lockBtn.dataset.customerId = data ? String(customerId) : '';
  }
  const archiveBtn = document.getElementById('modalToggleArchiveBtn');
  if (archiveBtn) {
    archiveBtn.style.display = data ? 'inline-flex' : 'none';
    archiveBtn.textContent = data && data.is_archived ? 'Unarchive Account' : 'Archive Account';
    archiveBtn.dataset.customerId = data ? String(customerId) : '';
  }
  document.getElementById('modalOrders').textContent = data ? (data.order_count ?? '—') : '—';
  document.getElementById('modalSpent').textContent = data ? formatCurrency(data.total_spent) : '—';
  document.getElementById('modalRating').textContent = data ? (data.avg_rating ?? '—') : '—';
  const orders = (data && data.recent_orders) || [];
  document.getElementById('modalOrdersBody').innerHTML = orders.length
    ? orders.map(o => '<tr><td style="font-family:var(--fd);font-weight:600;color:var(--rose-deep)">#' + esc(o.id) + '</td><td style="color:var(--muted)">' + esc(o.item_name) + '</td><td style="font-size:.75rem;color:var(--muted-light)">' + formatDate(o.created_at) + '</td><td style="font-family:var(--fd);font-weight:600">' + formatCurrency(o.total) + '</td><td><span class="badge ' + badgeClass(o.status) + '">' + esc(o.status) + '</span></td></tr>').join('')
    : '<tr><td colspan="5" style="text-align:center;color:var(--muted-light);padding:14px">No orders yet</td></tr>';
  document.getElementById('modalViewOrdersBtn').onclick = () => { location.href = 'orders.php?customer_id=' + customerId; };
  openModal('custModal');
}

async function toggleCustomerLockFromModal() {
  const btn = document.getElementById('modalToggleLockBtn');
  const customerId = btn ? btn.dataset.customerId : '';
  if (!customerId) return;
  const current = (D.customer_details || {})[customerId];
  const action = current && current.is_locked ? 'unlock' : 'lock';
  try {
    const csrfToken = (typeof document !== 'undefined' && document.querySelector('meta[name="csrf-token"]'))
      ? document.querySelector('meta[name="csrf-token"]').content
      : '';
    const r = await fetch('settings/account_lock.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        ...(csrfToken ? { 'X-CSRF-Token': csrfToken } : {})
      },
      body: JSON.stringify({ id: Number(customerId), action })
    });
    if (!r.ok) throw new Error(String(r.status));
    const res = await r.json();
    if (!res || res.ok === false) throw new Error('fail');
    toast(action === 'unlock' ? 'Customer account unlocked.' : 'Customer account locked.');
    location.reload();
  } catch (e) {
    toast('Could not update account lock state.');
  }
}

async function toggleCustomerArchiveFromModal() {
  const btn = document.getElementById('modalToggleArchiveBtn');
  const customerId = btn ? btn.dataset.customerId : '';
  if (!customerId) return;
  const current = (D.customer_details || {})[customerId];
  const action = current && current.is_archived ? 'unarchive' : 'archive';
  try {
    const csrfToken = (typeof document !== 'undefined' && document.querySelector('meta[name="csrf-token"]'))
      ? document.querySelector('meta[name="csrf-token"]').content
      : '';
    const r = await fetch('settings/account_archive.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        ...(csrfToken ? { 'X-CSRF-Token': csrfToken } : {})
      },
      body: JSON.stringify({ id: Number(customerId), action })
    });
    if (!r.ok) throw new Error(String(r.status));
    const res = await r.json();
    if (!res || res.ok === false) throw new Error('fail');
    toast(action === 'unarchive' ? 'Customer account restored.' : 'Customer account archived.');
    location.reload();
  } catch (e) {
    toast('Could not update account archive state.');
  }
}

document.querySelectorAll('#filtersBar .fb').forEach(btn => {
  btn.addEventListener('click', function () {
    const f = this.dataset.filter || 'all';
    location.href = 'customers.php?page=1&filter=' + encodeURIComponent(f) + '&search=' + encodeURIComponent(currentSearch);
  });
});
const custSearchEl = document.getElementById('custSearch');
if (custSearchEl) {
  custSearchEl.value = currentSearch;
  custSearchEl.addEventListener('keydown', function (e) {
    if (e.key === 'Enter') location.href = 'customers.php?page=1&filter=' + encodeURIComponent(currentFilter) + '&search=' + encodeURIComponent(this.value.trim());
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

function applyCustomerView() {
  const grid = document.getElementById('custGrid');
  const tableWrap = document.getElementById('custTableWrap');
  const buttons = document.querySelectorAll('.view-btn');
  const isTable = currentView === 'table';
  if (grid) grid.classList.toggle('hidden', isTable);
  if (tableWrap) tableWrap.classList.toggle('hidden', !isTable);
  buttons.forEach(btn => btn.classList.toggle('on', btn.dataset.view === currentView));
}

function setCustomerView(view) {
  currentView = view === 'table' ? 'table' : 'grid';
  try { localStorage.setItem('bejewelry_customer_view', currentView); } catch (e) {}
  applyCustomerView();
}

function handleExport() { location.href = 'customers_export.php?filter=' + encodeURIComponent(currentFilter) + '&search=' + encodeURIComponent(currentSearch); }
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
  loadCustomers();
  applyCustomerView();
}
document.addEventListener('DOMContentLoaded', loadPageData);
