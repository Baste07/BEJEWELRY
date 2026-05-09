/* BEJEWELRY ADMIN — Reports (MySQL only). Data from window.__REPORTS__. */

const D = window.__REPORTS__ || {};
let currentPeriod = D.period || 'this_month';

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
  el.classList.remove('skel', 'skel-val', 'skel-text', 'skel-row');
}
function categoryIcon(name) {
  const map = { rings:'💍', necklaces:'📿', earrings:'✨', bracelets:'🔮', pendants:'💎' };
  return map[(name || '').toLowerCase()] || '💎';
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
function loadSummary() {
  const data = D.summary || {};
  const setVal = (id, val) => { const el = document.getElementById(id); clearSkel(el); if (el) el.textContent = val ?? '—'; };
  const setTrend = (id, text, dir) => { const el = document.getElementById(id); clearSkel(el); if (el) { el.textContent = text ?? ''; el.className = 'stat-trend' + (dir === 'up' ? ' up' : dir === 'down' ? ' down' : ''); } };
  setVal('valRevenue', formatCurrency(data.revenue));
  setVal('valOrders', data.orders);
  setVal('valAov', formatCurrency(data.avg_order_value));
  setVal('valNewCust', data.new_customers);
  const fmt = (val, suffix) => val != null ? (val >= 0 ? '↑' : '↓') + ' ' + Math.abs(val) + (suffix || '%') + ' vs last period' : '—';
  const dir = val => val >= 0 ? 'up' : 'down';
  setTrend('trendRevenue', fmt(data.revenue_change), dir(data.revenue_change));
  setTrend('trendOrders', fmt(data.orders_change), dir(data.orders_change));
  setTrend('trendAov', fmt(data.aov_change, ''), dir(data.aov_change));
  setTrend('trendNewCust', fmt(data.new_customers_change), dir(data.new_customers_change));
  const sub = document.getElementById('reportsSubtitle');
  if (sub) sub.textContent = 'Sales analytics and performance overview · ' + (data.period_label || '—');
}
function loadCategories() {
  const tbody = document.getElementById('categoryBody');
  const cats = D.categories || [];
  document.getElementById('categoryPeriodLabel').textContent = document.querySelector('#periodSelect option:checked') ? document.querySelector('#periodSelect option:checked').text : '—';
  if (!cats.length) {
    tbody.innerHTML = '<tr><td colspan="4"><div class="empty-state"><span class="empty-icon">📊</span>No data available</div></td></tr>';
    return;
  }
  tbody.innerHTML = cats.map(c => '<tr><td class="t-cat">' + categoryIcon(c.name) + ' ' + esc(c.name) + '</td><td class="t-num">' + formatCurrency(c.revenue) + '</td><td class="t-muted">' + esc(c.orders) + '</td><td><div class="prog-wrap"><div class="prog-bar"><div class="prog-fill" style="width:' + esc(c.share) + '%"></div></div><span class="prog-pct">' + esc(c.share) + '%</span></div></td></tr>').join('');
}
function loadWeeklyChart() {
  const chart = document.getElementById('weeklyChart');
  const weeks = D.weeks || [];
  const lbl = document.getElementById('chartPeriodLabel');
  if (lbl) lbl.textContent = weeks.length ? ('Past ' + weeks.length + ' week' + (weeks.length !== 1 ? 's' : '')) : '—';
  if (!weeks.length) {
    chart.innerHTML = '<div class="empty-state" style="width:100%"><span class="empty-icon">📈</span>No data</div>';
    return;
  }
  const maxVal = Math.max(...weeks.map(w => w.value || 0), 1);
  chart.innerHTML = weeks.map(w => {
    const pct = Math.round((w.value / maxVal) * 100);
    return '<div class="bar-col"><div class="bar-rect" style="height:' + pct + '%" data-tip="' + formatCurrency(w.value) + '"></div><span class="bar-lbl">' + esc(w.label) + '</span></div>';
  }).join('');
}
function loadPaymentMethods() {
  const list = document.getElementById('paymentList');
  const methods = D.payment_methods || [];
  if (!methods.length) {
    list.innerHTML = '<div class="empty-state"><span class="empty-icon">💳</span>No payment data</div>';
    return;
  }
  list.innerHTML = methods.map(m => '<div class="pay-row"><div class="pay-meta"><span class="pay-name">' + esc(m.name) + '</span><span class="pay-detail">' + esc(m.pct) + '% · ' + formatCurrency(m.amount) + '</span></div><div class="pay-bar"><div class="pay-fill ' + esc(m.key) + '" style="width:' + esc(m.pct) + '%"></div></div></div>').join('');
}
function loadInsights() {
  const container = document.getElementById('insightsList');
  const insights = D.insights || [];
  if (!insights.length) {
    container.innerHTML = '<div class="empty-state"><span class="empty-icon">👥</span>No insight data</div>';
    return;
  }
  container.innerHTML = insights.map(i => '<div class="insight-row"><span class="insight-label">' + esc(i.label) + '</span><span class="insight-val ' + esc(i.direction) + '">' + esc(i.value) + '</span></div>').join('');
}

let globalTimeout;
const gs = document.getElementById('globalSearch');
if (gs) gs.addEventListener('input', e => {
  clearTimeout(globalTimeout);
  const q = e.target.value.trim();
  if (q.length < 2) return;
  globalTimeout = setTimeout(() => { location.href = 'search.php?q=' + encodeURIComponent(q); }, 500);
});

function handleExport() {
  location.href = 'reports.php?period=' + encodeURIComponent(currentPeriod);
}
function handleNotifications() { location.href = 'notifications.php'; }
function handleLogout() {
  if (typeof window.adminConfirm === 'function') {
    window.adminConfirm('Log out of Bejewelry Admin?', () => { location.href = '../logout.php'; }, { okText: 'Log out' });
    return;
  }
  location.href = '../logout.php';
}

function loadReportData() {
  loadSummary();
  loadCategories();
  loadWeeklyChart();
  loadPaymentMethods();
  loadInsights();
}
function loadPageData() {
  setLoading(false);
  loadUser();
  loadBadges();
  loadReportData();
}
document.addEventListener('DOMContentLoaded', loadPageData);