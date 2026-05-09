// Shared admin "current user" loader (name/role/avatar initials).
(function () {
  function initials(name) {
    const s = String(name || '').trim();
    if (!s) return 'AD';
    const parts = s.split(/\s+/).filter(Boolean);
    const a = (parts[0] || '').slice(0, 1);
    const b = (parts.length > 1 ? parts[parts.length - 1] : '').slice(0, 1);
    return (a + b).toUpperCase() || 'AD';
  }

  async function loadWhoAmI() {
    const nameEl = document.getElementById('sbUsername');
    const roleEl = document.getElementById('sbUserRole');
    const avEl = document.getElementById('sbAvatar');
    if (!nameEl && !roleEl && !avEl) return;

    try {
      const url = new URL('../api/auth/session.php', window.location.href);
      const r = await fetch(url, { cache: 'no-store', credentials: 'same-origin' });
      if (!r.ok) throw new Error(String(r.status));
      const u = await r.json();
      const name = (u && u.name) ? String(u.name) : 'Admin';
      const role = (u && u.role) ? String(u.role) : 'admin';
      if (nameEl) nameEl.textContent = name;
      if (roleEl) roleEl.textContent = role;
      if (avEl) avEl.textContent = initials(name);
    } catch (_) {
      // Keep whatever the page already shows.
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', loadWhoAmI);
  } else {
    loadWhoAmI();
  }
})();

