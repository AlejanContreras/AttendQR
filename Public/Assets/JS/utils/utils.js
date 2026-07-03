/**
 * AttendQR — Utilidades globales (UI only, sin llamadas API)
 */
const AttendQR = (() => {

  // ─── Sidebar ────────────────────────────────────────────────────────
  const sidebar = {
    toggle() {
      const s = document.getElementById('sidebar');
      if (!s) return;
      if (window.innerWidth < 768) {
        s.classList.toggle('is-open');
        const ov = document.getElementById('overlay');
        if (ov) ov.style.display = s.classList.contains('is-open') ? 'block' : 'none';
      } else {
        s.classList.toggle('is-collapsed');
        document.getElementById('mainWrapper')?.classList.toggle('sidebar-collapsed');
      }
    },
    close() {
      const s = document.getElementById('sidebar');
      if (s) s.classList.remove('is-open');
      const ov = document.getElementById('overlay');
      if (ov) ov.style.display = 'none';
    },
  };

  // ─── Toast notifications ────────────────────────────────────────────
  const toast = {
    show(msg, type = 'info', duration = 3500) {
      const container = document.getElementById('toast-container');
      if (!container) return;
      const t = document.createElement('div');
      t.className = `toast toast--${type}`;
      t.innerHTML = `<span class="toast__msg">${msg}</span>
        <button class="toast__close" onclick="this.closest('.toast').remove()">×</button>`;
      container.appendChild(t);
      setTimeout(() => { t.classList.add('toast--out'); setTimeout(() => t.remove(), 300); }, duration);
    },
    success: (m, d) => AttendQR.toast.show(m, 'success', d),
    error:   (m, d) => AttendQR.toast.show(m, 'error',   d),
    warning: (m, d) => AttendQR.toast.show(m, 'warning', d),
    info:    (m, d) => AttendQR.toast.show(m, 'info',    d),
  };

  // ─── Modal ──────────────────────────────────────────────────────────
  const modal = {
    open(id = 'modal') {
      const el = document.getElementById(`${id}Backdrop`);
      if (el) el.style.display = 'flex';
    },
    close(id = 'modal') {
      const el = document.getElementById(`${id}Backdrop`);
      if (el) el.style.display = 'none';
    },
    setTitle(id, title) {
      const el = document.getElementById(`${id}Title`);
      if (el) el.textContent = title;
    },
    setBody(id, html) {
      const el = document.getElementById(`${id}Body`);
      if (el) el.innerHTML = html;
    },
  };

  // ─── Clock ──────────────────────────────────────────────────────────
  const clock = {
    start(selector = '#topbarClock') {
      const el = document.querySelector(selector);
      if (!el) return;
      const tick = () => {
        el.textContent = new Date().toLocaleTimeString('es-CO', { hour: '2-digit', minute: '2-digit' });
      };
      tick();
      setInterval(tick, 1000);
    },
  };

  // ─── Loader ─────────────────────────────────────────────────────────
  const loader = {
    show(msg = 'Cargando...') {
      const el = document.getElementById('fullPageLoader');
      const msgEl = document.getElementById('loaderMessage');
      if (el) el.style.display = 'flex';
      if (msgEl) msgEl.textContent = msg;
    },
    hide() {
      const el = document.getElementById('fullPageLoader');
      if (el) el.style.display = 'none';
    },
  };

  // ─── Init ────────────────────────────────────────────────────────────
  function init() {
    document.getElementById('sidebarToggle')
      ?.addEventListener('click', () => sidebar.toggle());

    window.addEventListener('resize', () => {
      if (window.innerWidth >= 768) {
        const ov = document.getElementById('overlay');
        if (ov) ov.style.display = 'none';
        document.getElementById('sidebar')?.classList.remove('is-open');
      }
    });

    clock.start('#topbarClock');

    const today = new Date().toISOString().slice(0, 10);
    document.querySelectorAll('input[type="date"]:not([value])').forEach(el => {
      el.value = today;
    });
  }

  document.addEventListener('DOMContentLoaded', init);

  return { sidebar, toast, modal, clock, loader };
})();
