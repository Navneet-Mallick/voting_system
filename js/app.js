/* ── app.js — shared utilities ──────────────────────────────── */

// ── Toast notifications ───────────────────────────────────────
function showToast(message, type = 'info') {
  let container = document.getElementById('toast-container');
  if (!container) {
    container = document.createElement('div');
    container.id = 'toast-container';
    document.body.appendChild(container);
  }
  const t = document.createElement('div');
  t.className = `toast toast-${type}`;
  t.textContent = message;
  container.appendChild(t);
  setTimeout(() => t.remove(), 3500);
}

// ── API helper ────────────────────────────────────────────────
async function api(url, data = null) {
  const opts = { method: data ? 'POST' : 'GET', credentials: 'include' };
  if (data) {
    opts.body = data instanceof FormData ? data : new URLSearchParams(data);
    if (!(data instanceof FormData)) {
      opts.headers = { 'Content-Type': 'application/x-www-form-urlencoded' };
    }
  }
  const res = await fetch(url, opts);
  return res.json();
}

// ── Session check ─────────────────────────────────────────────
async function getSession() {
  return api('php/auth.php?action=check');
}

async function requireAuth(adminOnly = false) {
  const sess = await getSession();
  if (!sess.loggedIn) { window.location.href = 'index.html'; return null; }
  if (adminOnly && sess.role !== 'admin') { window.location.href = 'dashboard.html'; return null; }
  return sess;
}

// ── Logout ────────────────────────────────────────────────────
async function logout() {
  await api('php/auth.php?action=logout');
  window.location.href = 'index.html';
}

// ── Render navbar user info ───────────────────────────────────
function renderNav(sess) {
  const el = document.getElementById('nav-user');
  if (el && sess) el.textContent = `👤 ${sess.name}`;
}

// ── Date helpers ──────────────────────────────────────────────
function fmtDate(d) {
  return new Date(d).toLocaleDateString('en-US', {
    year: 'numeric', month: 'short', day: 'numeric',
    hour: '2-digit', minute: '2-digit'
  });
}

function statusBadge(status) {
  const map = { active: 'badge-active', upcoming: 'badge-upcoming', closed: 'badge-closed' };
  return `<span class="badge ${map[status] || ''}">${status}</span>`;
}

// ── Confirm modal ─────────────────────────────────────────────
function confirmDialog(message, onConfirm) {
  const overlay = document.createElement('div');
  overlay.className = 'modal-overlay';
  overlay.innerHTML = `
    <div class="modal" style="max-width:380px">
      <p style="font-size:1.05rem;margin-bottom:1.5rem">${message}</p>
      <div style="display:flex;gap:.75rem;justify-content:flex-end">
        <button class="btn btn-outline" id="cd-cancel">Cancel</button>
        <button class="btn btn-accent"  id="cd-ok">Confirm</button>
      </div>
    </div>`;
  document.body.appendChild(overlay);
  overlay.querySelector('#cd-cancel').onclick = () => overlay.remove();
  overlay.querySelector('#cd-ok').onclick = () => { overlay.remove(); onConfirm(); };
}
