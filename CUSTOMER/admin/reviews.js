/* BEJEWELRY ADMIN — Reviews (MySQL only). Data from window.__REVIEWS__. Stats from products table. */

const D = window.__REVIEWS__ || {};
let currentFilter = 'all';
let currentSearch = '';
let currentPage = 1;

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
function clearSkel(el) {
  if (!el) return;
  el.classList.remove('skel', 'skel-val', 'skel-text');
}
function starsHtml(rating) {
  const r = Math.round(rating || 0);
  return '★'.repeat(r) + '☆'.repeat(5 - r);
}
function statusBadgeHtml(status) {
  const map = { pending: '<span class="badge b-pending">⏳ Pending</span>', approved: '<span class="badge b-approved">✓ Approved</span>', rejected: '<span class="badge b-rejected">✕ Rejected</span>' };
  return map[status] || map.pending;
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
  setVal('valAvgRating', s.avg_rating != null ? Number(s.avg_rating).toFixed(1) : '—');
  setVal('valTotal', s.total);
  setVal('valPending', s.pending);
  setVal('valApproved', s.approved);
  setTrend('trendAvgRating', 'Out of 5.0 stars');
  setTrend('trendTotal', 'All time reviews');
  setTrend('trendPending', (s.pending || 0) > 0 ? 'Awaiting your review' : 'All caught up ✓');
  setTrend('trendApproved', 'Visible on store');
  const alertBar = document.getElementById('alertBar');
  if (alertBar) {
    if ((s.pending || 0) > 0) {
      document.getElementById('alertText').innerHTML = '<strong>' + s.pending + ' review' + (s.pending > 1 ? 's' : '') + '</strong> ' + (s.pending > 1 ? 'are' : 'is') + ' waiting for your approval.';
      alertBar.classList.remove('hidden');
    } else alertBar.classList.add('hidden');
  }
  const sub = document.getElementById('reviewsSubtitle');
  if (sub) sub.textContent = 'Manage customer reviews' + ((s.pending || 0) > 0 ? ' — ' + s.pending + ' pending approval' : ' — all up to date');
}
function loadReviews() {
  const list = document.getElementById('reviewList');
  const reviews = D.reviews || [];
  const counts = D.counts || {};
  const labelMap = { all:'All', pending:'Pending', approved:'Approved', fivestar:'5 Stars', below5:'4 Stars & Below' };
  document.querySelectorAll('#filtersBar .fb').forEach(btn => {
    const f = btn.dataset.filter;
    const count = counts[f];
    btn.textContent = count !== undefined ? (labelMap[f] || f) + ' (' + count + ')' : (labelMap[f] || f);
  });
  if (!reviews.length) {
    list.innerHTML = '<div class="empty-state"><span class="empty-icon">⭐</span>No reviews found</div>';
    const pag = document.getElementById('pagination'); if (pag) pag.innerHTML = '';
    return;
  }
  const baseUrl = 'review_action.php';
  list.innerHTML = reviews.map(r => {
    const isPending = r.status === 'pending';
    const actionUrl = baseUrl + '?id=' + r.id + (D.filter && D.filter !== 'all' ? '&filter=' + encodeURIComponent(D.filter) : '') + (D.page > 1 ? '&page=' + D.page : '') + (D.search ? '&search=' + encodeURIComponent(D.search) : '');
    const approveForm = '<form method="post" action="' + actionUrl + '" style="display:inline"><input type="hidden" name="action" value="approve"><input type="hidden" name="id" value="' + r.id + '"><button type="submit" class="btn btn-primary btn-sm">✓ Approve</button></form>';
    const rejectForm = '<form method="post" action="' + actionUrl + '" style="display:inline" onsubmit="return confirm(\'Reject this review?\')"><input type="hidden" name="action" value="reject"><input type="hidden" name="id" value="' + r.id + '"><button type="submit" class="btn btn-danger btn-sm">✕ Reject</button></form>';
    const deleteForm = '<form method="post" action="' + actionUrl + '" style="display:inline" onsubmit="return confirm(\'Delete this review permanently?\')"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="' + r.id + '"><button type="submit" class="btn btn-ghost btn-sm">🗑 Delete</button></form>';
    const actions = isPending ? '<div class="rev-actions">' + approveForm + ' ' + rejectForm + '</div>' : '<div class="rev-actions">' + deleteForm + '</div>';
    return '<div class="revc" id="rev-' + r.id + '"><div class="rev-top"><div><div class="rev-stars">' + starsHtml(r.rating) + '</div><div class="rev-product">' + esc(r.product_name) + ' · ' + esc(r.review_date) + '</div></div><div id="rev-status-' + r.id + '">' + statusBadgeHtml(r.status) + '</div></div><div class="rev-body">' + esc(r.body || r.title || '') + '</div><div class="rev-foot"><div class="rev-auth"><div class="rev-av">' + esc(r.customer_initials) + '</div><div><div class="rev-name">' + esc(r.customer_name) + '</div>' + (r.verified_buyer ? '<div class="rev-verified">✓ Verified Buyer</div>' : '') + '</div></div>' + actions + '</div></div>';
  }).join('');
  renderPagination(D.total_pages || 1);
}

// ── Pagination ──
function renderPagination(totalPages) {
  const el = document.getElementById('pagination');
  if (totalPages <= 1) { el.innerHTML = ''; return; }
  const page = D.page || 1;
  const filter = D.filter || 'all';
  const search = D.search || '';
  const q = (f, p, s) => {
    const params = new URLSearchParams();
    params.set('filter', f);
    params.set('page', String(p));
    if (s) params.set('search', s);
    return 'reviews.php?' + params.toString();
  };
  let html = '';
  if (page > 1) html += '<a class="page-btn" href="' + q(filter, page - 1, search) + '">‹</a>';
  for (let i = 1; i <= totalPages; i++) {
    html += '<a class="page-btn' + (i === page ? ' active' : '') + '" href="' + q(filter, i, search) + '">' + i + '</a>';
  }
  if (page < totalPages) html += '<a class="page-btn" href="' + q(filter, page + 1, search) + '">›</a>';
  el.innerHTML = html;
}

function approveReview(reviewId) {
  toast('Review approved ✓');
  const statusEl = document.getElementById('rev-status-' + reviewId);
  if (statusEl) statusEl.innerHTML = statusBadgeHtml('approved');
  const foot = document.querySelector('#rev-' + reviewId + ' .rev-actions');
  if (foot) foot.innerHTML = '<button class="btn btn-ghost btn-sm" onclick="deleteReview(' + reviewId + ')">🗑 Delete</button>';
}
function rejectReview(reviewId) {
  toast('Review rejected.');
  const statusEl = document.getElementById('rev-status-' + reviewId);
  if (statusEl) statusEl.innerHTML = statusBadgeHtml('rejected');
  const foot = document.querySelector('#rev-' + reviewId + ' .rev-actions');
  if (foot) foot.innerHTML = '<button class="btn btn-ghost btn-sm" onclick="deleteReview(' + reviewId + ')">🗑 Delete</button>';
}
function deleteReview(reviewId) {
  if (!confirm('Delete this review permanently?')) return;
  const el = document.getElementById('rev-' + reviewId);
  if (el) el.remove();
  toast('Review deleted.');
}

// ── Filters ──
document.querySelectorAll('#filtersBar .fb').forEach(btn => {
  btn.addEventListener('click', function () {
    setFilter(this.dataset.filter);
  });
});

function setFilter(filter) {
  const params = new URLSearchParams();
  params.set('filter', filter);
  params.set('page', '1');
  if (D.search) params.set('search', D.search);
  window.location.href = 'reviews.php?' + params.toString();
}

// ── Search ──
const revSearchEl = document.getElementById('revSearch');
if (revSearchEl) {
  revSearchEl.addEventListener('keydown', function (e) {
    if (e.key === 'Enter') {
      const params = new URLSearchParams();
          params.set('search', this.value.trim());
          params.set('filter', D.filter || 'all');
          params.set('page', '1');
      window.location.href = 'reviews.php?' + params.toString();
    }
  });
}

let globalTimeout;
document.getElementById('globalSearch').addEventListener('input', e => {
  clearTimeout(globalTimeout);
  const q = e.target.value.trim();
  if (q.length < 2) return;
  globalTimeout = setTimeout(() => {
    window.location.href = `search.php?q=${encodeURIComponent(q)}`;
  }, 500);
});

// ── Action handlers ──
function handleNotifications() { window.location.href = 'notifications.php'; }

function handleLogout() {
  if (typeof window.adminConfirm === 'function') {
    window.adminConfirm('Log out of Bejewelry Admin?', () => { window.location.href = '../logout.php'; }, { okText: 'Log out' });
    return;
  }
  window.location.href = '../logout.php';
}

function loadPageData() {
  setLoading(false);
  loadUser();
  loadBadges();
  loadStats();
  loadReviews();
}

document.addEventListener('DOMContentLoaded', loadPageData);