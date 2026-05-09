// Shared admin notification dot loader (no number).
(function () {
  async function fetchCountWithFallback() {
    const candidates = [
      'settings/notifications_count.php',
      '../admin/settings/notifications_count.php',
      '/CUSTOMER/admin/settings/notifications_count.php'
    ];

    for (const p of candidates) {
      try {
        const url = new URL(p, window.location.href);
        const r = await fetch(url, { cache: 'no-store', credentials: 'same-origin' });
        if (!r.ok) continue;
        const d = await r.json();
        return Number(d && d.count) || 0;
      } catch (_) {
        // try next candidate
      }
    }
    throw new Error('notifications_count endpoint not reachable');
  }

  async function loadNotifDot() {
    const dot = document.getElementById('notifDot');
    if (!dot) return;
    try {
      const n = await fetchCountWithFallback();
      // Ensure dot is visible even if page CSS missed it.
      if (n > 0) {
        dot.style.display = 'block';
        dot.style.width = dot.style.width || '9px';
        dot.style.height = dot.style.height || '9px';
        dot.style.borderRadius = dot.style.borderRadius || '50%';
        dot.style.background = dot.style.background || 'var(--rose, #D96070)';
        dot.style.border = dot.style.border || '2px solid var(--white, #fff)';
        dot.style.boxShadow = dot.style.boxShadow || '0 2px 8px rgba(176,48,80,.22)';
      } else {
        dot.style.display = 'none';
      }
    } catch (_) {
      dot.style.display = 'none';
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', loadNotifDot);
  } else {
    loadNotifDot();
  }
})();

