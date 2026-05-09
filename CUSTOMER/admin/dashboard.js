/* ═══════════════════════════════════════════════════════════
   BEJEWELRY ADMIN DASHBOARD — MySQL only (no API).
   Data is injected by dashboard.php as window.__DASHBOARD__.
═══════════════════════════════════════════════════════════ */

/* ── UTILITIES ── */
function toast(msg, duration = 2800) {
  const t = document.getElementById('toast');
  t.textContent = msg;
  t.classList.add('on');
  setTimeout(() => t.classList.remove('on'), duration);
}

function setLoading(on) {
  document.getElementById('loadingBar').classList.toggle('active', on);
}

function formatCurrency(amount) {
  return '₱' + Number(amount).toLocaleString('en-PH');
}

function timeAgo(dateStr) {
  const diff = Date.now() - new Date(dateStr).getTime();
  const mins = Math.floor(diff / 60000);
  if (mins < 1)  return 'Just now';
  if (mins < 60) return `${mins} min${mins > 1 ? 's' : ''} ago`;
  const hrs = Math.floor(mins / 60);
  if (hrs < 24)  return `${hrs} hr${hrs > 1 ? 's' : ''} ago`;
  return new Date(dateStr).toLocaleDateString('en-PH');
}

function badgeClass(status) {
  const map = {
    pending: 'b-pending', processing: 'b-processing',
    shipped: 'b-shipped', delivered: 'b-delivered',
    cancelled: 'b-cancelled'
  };
  return map[(status || '').toLowerCase()] || 'b-pending';
}

function clearSkel(el) {
  el.classList.remove('skel', 'skel-text', 'skel-val');
  el.innerHTML = '';
}

function escHtml(str) {
  const d = document.createElement('div');
  d.textContent = str || '';
  return d.innerHTML;
}

/* ── USE IN-PAGE DATA (window.__DASHBOARD__) ── */
function loadUser() {
  const data = window.__DASHBOARD__ && window.__DASHBOARD__.user;
  const name = data ? data.name : 'Admin';
  const role = data ? data.role : '—';
  const initials = (name || '?').split(' ').map(w => w[0]).join('').slice(0, 2).toUpperCase();
  document.getElementById('sbAvatar').textContent = initials;
  document.getElementById('sbUsername').textContent = name || '—';
  document.getElementById('sbUserRole').textContent = role || '—';
}

function loadStats() {
  const data = window.__DASHBOARD__ && window.__DASHBOARD__.stats;
  if (!data) return;

  clearSkel(document.getElementById('valRevenue'));
  document.getElementById('valRevenue').textContent = formatCurrency(data.revenue?.value ?? 0);
  const revTrend = document.getElementById('trendRevenue');
  clearSkel(revTrend);
  const revUp = (data.revenue?.change ?? 0) >= 0;
  revTrend.className = `stat-trend ${revUp ? 'up' : 'down'}`;
  revTrend.textContent = `${revUp ? '↑' : '↓'} ${Math.abs(data.revenue?.change ?? 0)}% vs last month`;

  const valRefunds = document.getElementById('valRefunds');
  const trendRefunds = document.getElementById('trendRefunds');
  if (valRefunds && trendRefunds) {
    clearSkel(valRefunds);
    const refunds = data.refunds?.value ?? data.revenue?.refunds ?? 0;
    valRefunds.textContent = `-${formatCurrency(refunds).replace(/^₱/, '₱')}`;
    clearSkel(trendRefunds);
    trendRefunds.className = 'stat-trend down';
    trendRefunds.textContent = 'Deducted from gross revenue';
  }

  const valOrders = document.getElementById('valOrders');
  const ordTrend = document.getElementById('trendOrders');
  if (valOrders && ordTrend) {
    clearSkel(valOrders);
    valOrders.textContent = data.orders?.value ?? '—';
    clearSkel(ordTrend);
    const ordUp = (data.orders?.change ?? 0) >= 0;
    ordTrend.className = `stat-trend ${ordUp ? 'up' : 'down'}`;
    ordTrend.textContent = `${ordUp ? '↑' : '↓'} ${Math.abs(data.orders?.change ?? 0)}% vs last month`;
  }

  clearSkel(document.getElementById('valCustomers'));
  document.getElementById('valCustomers').textContent = data.customers?.value ?? '—';
  const custTrend = document.getElementById('trendCustomers');
  clearSkel(custTrend);
  custTrend.className = 'stat-trend up';
  custTrend.textContent = `↑ ${data.customers?.new_this_month ?? 0} new this month`;

  clearSkel(document.getElementById('valRating'));
  document.getElementById('valRating').textContent = data.rating?.value ?? '—';
  const ratTrend = document.getElementById('trendRating');
  clearSkel(ratTrend);
  const ratUp = (data.rating?.change ?? 0) >= 0;
  ratTrend.className = `stat-trend ${ratUp ? 'up' : 'down'}`;
  ratTrend.textContent = `${ratUp ? '↑' : '↓'} from ${data.rating?.previous ?? '—'} last month`;

  document.getElementById('lastUpdated').textContent =
    `Overview · Last updated: ${new Date().toLocaleDateString('en-PH', { year: 'numeric', month: 'long', day: 'numeric' })}`;
}

function loadBadges() {
  const data = window.__DASHBOARD__ && window.__DASHBOARD__.badges;
  if (!data) return;
  const setBadge = (id, val) => {
    const el = document.getElementById(id);
    if (el && val != null && val !== '') el.textContent = String(val);
  };
  setBadge('badgeOrders', data.pending_orders);
  setBadge('badgeProducts', data.new_products);
  setBadge('badgeInventory', data.low_stock);
  setBadge('badgeReviews', data.pending_reviews);
  setBadge('badgeTickets', data.open_tickets);
  if (data.low_stock > 0) {
    const lowStockCount = document.getElementById('lowStockCount');
    const lowStockAlert = document.getElementById('lowStockAlert');
    if (lowStockCount && lowStockAlert) {
      lowStockCount.textContent = data.low_stock;
      lowStockAlert.style.display = 'flex';
    }
  }

  const ticketVal = document.getElementById('valTickets');
  const ticketTrend = document.getElementById('trendTickets');
  if (ticketVal && ticketTrend) {
    clearSkel(ticketVal);
    ticketVal.textContent = data.open_tickets ?? '—';
    clearSkel(ticketTrend);
    ticketTrend.className = 'stat-trend down';
    ticketTrend.textContent = (data.open_tickets > 0) ? 'Action needed now' : 'All caught up';
  }
}

function loadChart() {
  const data = window.__DASHBOARD__ && window.__DASHBOARD__.revenue;
  const wrap = document.getElementById('revenueChart');
  if (!wrap) return;
  if (!data || !data.labels || !data.values || data.labels.length === 0) {
    wrap.innerHTML = '<div class="empty-state" style="width:100%"><span class="empty-icon">📊</span>No data yet</div>';
    return;
  }
  wrap.innerHTML = '';
  const max = Math.max(...data.values);
  data.labels.forEach((label, i) => {
    const val = data.values[i] || 0;
    const col = document.createElement('div'); col.className = 'bar-col';
    const bar = document.createElement('div'); bar.className = 'bar-rect';
    bar.style.height = max > 0 ? ((val / max) * 140) + 'px' : '4px';
    bar.setAttribute('data-val', formatCurrency(val));
    const lbl = document.createElement('div'); lbl.className = 'bar-lbl';
    lbl.textContent = label;
    col.appendChild(bar); col.appendChild(lbl);
    wrap.appendChild(col);
  });
}

function loadTopProducts() {
  const data = window.__DASHBOARD__ && window.__DASHBOARD__.top_products;
  const list = document.getElementById('topProductsList');
  if (!list) return;
  if (!data || data.length === 0) {
    list.innerHTML = '<div class="empty-state"><span class="empty-icon">💎</span>No product data yet</div>';
    return;
  }
  const maxRev = Math.max(...data.map(p => p.revenue || 0));
  list.innerHTML = '';
  data.forEach(p => {
    const pct = maxRev > 0 ? Math.round((p.revenue / maxRev) * 100) : 0;
    const thumb = p.image_url
      ? `<button type="button" class="prod-thumb prod-thumb-btn js-prod-image" data-image-url="${escHtml(p.image_url)}" data-image-name="${escHtml(p.name)}" aria-label="Open ${escHtml(p.name)} image"><img src="${escHtml(p.image_url)}" alt="${escHtml(p.name)}"></button>`
      : '💎';
    list.innerHTML += `
      <div class="prod-row">
        ${p.image_url ? thumb : `<div class="prod-thumb">${thumb}</div>`}
        <div class="prod-info">
          <div class="prod-name">${escHtml(p.name)}</div>
          <div class="prod-meta">${p.sold ?? 0} sold · ${escHtml(p.category || '')}</div>
          <div class="prog-wrap"><div class="prog-fill" style="width:${pct}%"></div></div>
        </div>
        <div class="prod-rev">${formatCurrency(p.revenue)}</div>
      </div>`;
  });

  list.querySelectorAll('.js-prod-image').forEach(btn => {
    btn.addEventListener('click', () => {
      openProductImageModal(btn.getAttribute('data-image-url') || '', btn.getAttribute('data-image-name') || 'Product image');
    });
  });
}

function openProductImageModal(imageUrl, imageName) {
  if (!imageUrl) return;
  const modal = document.getElementById('productImageModal');
  const img = document.getElementById('productImageModalImg');
  const title = document.getElementById('productImageModalTitle');
  if (!modal || !img || !title) return;

  img.src = imageUrl;
  img.alt = imageName || 'Product image preview';
  title.textContent = imageName || 'Product image';
  modal.classList.add('is-open');
  modal.setAttribute('aria-hidden', 'false');
}

function closeProductImageModal() {
  const modal = document.getElementById('productImageModal');
  const img = document.getElementById('productImageModalImg');
  if (!modal || !img) return;

  modal.classList.remove('is-open');
  modal.setAttribute('aria-hidden', 'true');
  img.src = '';
}

window.closeProductImageModal = closeProductImageModal;

function loadRecentOrders() {
  const tbody = document.getElementById('recentOrdersBody');
  if (!tbody) return;
  const data = window.__DASHBOARD__ && window.__DASHBOARD__.recent_orders;
  if (!data || data.length === 0) {
    tbody.innerHTML = '<tr><td colspan="5"><div class="empty-state"><span class="empty-icon">📭</span>No orders yet</div></td></tr>';
    return;
  }
  tbody.innerHTML = '';
  data.forEach(o => {
    tbody.innerHTML += `
      <tr>
        <td class="order-id"><a href="orders.php?id=${o.id}">#${o.id}</a></td>
        <td>${escHtml(o.customer_name)}</td>
        <td>${escHtml(o.item_name)}</td>
        <td class="amount">${formatCurrency(o.total)}</td>
        <td><span class="badge ${badgeClass(o.status)}">${escHtml(o.status)}</span></td>
      </tr>`;
  });
}

function loadActivity() {
  const feed = document.getElementById('activityFeed');
  if (!feed) return;
  const data = window.__DASHBOARD__ && window.__DASHBOARD__.activity;
  if (!data || data.length === 0) {
    feed.innerHTML = '<div class="empty-state"><span class="empty-icon">🕐</span>No activity yet</div>';
    return;
  }
  feed.innerHTML = '';
  data.forEach((item, i) => {
    feed.innerHTML += `
      <div class="feed-item">
        <div class="feed-dot ${i === 0 ? 'active' : ''}"></div>
        <div>
          <div class="feed-text">${escHtml(item.message)}</div>
          <div class="feed-time">${timeAgo(item.created_at)}</div>
        </div>
      </div>`;
  });
}

/* ── ACTION HANDLERS ── */
function handleLogout() {
  if (typeof window.adminConfirm === 'function') {
    window.adminConfirm('Log out of Bejewelry Admin?', () => { window.location.href = '../logout.php'; }, { okText: 'Log out' });
    return;
  }
  window.location.href = '../logout.php';
}

function handleExport() {
  window.location.href = 'reports.php';
}

function handleNewOrder() {
  window.location.href = 'orders.php?action=new';
}

function handleNotifications() {
  window.location.href = 'notifications.php';
}

async function loadNotifDot(){
  const dot = document.getElementById('notifDot');
  if (!dot) return;
  try{
    const r = await fetch('settings/notifications_count.php');
    if(!r.ok) throw new Error(r.status);
    const d = await r.json();
    const n = Number(d && d.count) || 0;
    dot.style.display = n > 0 ? 'inline-block' : 'none';
    dot.textContent = '';
  }catch(e){
    dot.style.display = 'none';
  }
}

/* ── GLOBAL SEARCH ── */
let searchTimeout;
const globalSearchEl = document.getElementById('globalSearch');
if (globalSearchEl) {
  globalSearchEl.addEventListener('input', e => {
    clearTimeout(searchTimeout);
    const q = e.target.value.trim();
    if (q.length < 2) return;
    searchTimeout = setTimeout(() => {
      window.location.href = `search.php?q=${encodeURIComponent(q)}`;
    }, 500);
  });
}

/* ── MAIN LOAD (no fetch; data already in __DASHBOARD__) ── */
function loadDashboardData() {
  setLoading(true);
  try {
    try { loadUser(); } catch (e) { console.error(e); }
    try { loadStats(); } catch (e) { console.error(e); }
    try { loadBadges(); } catch (e) { console.error(e); }
    try { loadChart(); } catch (e) { console.error(e); }
    try { loadTopProducts(); } catch (e) { console.error(e); }
    try { loadRecentOrders(); } catch (e) { console.error(e); }
    try { loadActivity(); } catch (e) { console.error(e); }
    try { loadNotifDot(); } catch (e) { console.error(e); }
  } finally {
    setLoading(false);
  }
}

document.addEventListener('DOMContentLoaded', loadDashboardData);

document.addEventListener('click', (ev) => {
  const modal = document.getElementById('productImageModal');
  if (!modal) return;
  if (ev.target === modal) {
    closeProductImageModal();
  }
});

document.addEventListener('keydown', (ev) => {
  if (ev.key === 'Escape') {
    closeProductImageModal();
  }
});
// Data is from page load; refresh page to get new data from MySQL
// setInterval(loadDashboardData, 60_000);
