/**
 * AttendQR — Sesiones
 */
const sesiones = (() => {

  async function init() {
    await Promise.all([cargarFichas(), cargarSesionesActivas()]);

    const params = new URLSearchParams(window.location.search);
    const fichaId = params.get('ficha');
    if (fichaId) {
      const sel = document.getElementById('idFicha');
      if (sel) sel.value = fichaId;
    }
  }

  async function cargarFichas() {
    const sel = document.getElementById('idFicha');
    if (!sel) return;

    sel.disabled = true;
    sel.innerHTML = '<option value="">Cargando fichas...</option>';

    try {
      const usuario = window.ATTENDQR_USER;
      // Api.fichas.listar() → { fichas: [...], total }
      const data   = await Api.fichas.listar({ id_docente: usuario.id, estado: 'activa' });
      const fichas = data.fichas ?? [];

      if (!fichas.length) {
        sel.innerHTML = '<option value="">Sin fichas activas</option>';
        return;
      }

      sel.innerHTML = '<option value="">— Selecciona una ficha —</option>' +
        fichas.map(f =>
          `<option value="${f.id_ficha}">${esc(f.codigo_ficha)} · ${esc(f.nombre_programa)}</option>`
        ).join('');
    } catch (err) {
      sel.innerHTML = '<option value="">Error al cargar fichas</option>';
      AttendQR.toast.error('No se pudieron cargar las fichas: ' + err.message);
    } finally {
      sel.disabled = false;
    }
  }

  async function cargarSesionesActivas() {
    const container = document.getElementById('sesionesActivasSidebar');
    const badge     = document.getElementById('sesionesActivasBadge');
    if (!container) return;

    try {
      // Api.sesiones.listar() → { sesiones: [...], total }
      const data     = await Api.sesiones.listar({ estado: 'abierta' });
      const sesiones = data.sesiones ?? [];

      if (badge) badge.textContent = sesiones.length + (sesiones.length === 1 ? ' abierta' : ' abiertas');

      if (!sesiones.length) {
        container.innerHTML = `<p style="font-size:var(--text-sm);color:var(--text-muted)">No hay sesiones abiertas en este momento.</p>`;
        return;
      }

      container.innerHTML = sesiones.map(s => {
        const hora = s.hora_apertura ? s.hora_apertura.slice(0,5) : '—';
        return `<div style="padding:var(--sp-3);background:var(--surface-alt);border-radius:var(--r-md);border-left:3px solid var(--green-primary)">
          <div style="font-size:var(--text-sm);font-weight:var(--fw-medium);color:var(--text-primary)">Ficha ${esc(s.codigo_ficha ?? s.id_ficha)}</div>
          <div style="font-size:var(--text-xs);color:var(--text-muted)">${esc(s.nombre_programa ?? '')} · Desde ${hora}</div>
          <a href="index.php?view=qr&rol=docente&sesion=${s.id_sesion}" class="btn btn-ghost btn-sm" style="margin-top:var(--sp-2)">Ver QR</a>
        </div>`;
      }).join('');
    } catch {
      if (container) container.innerHTML = `<p style="font-size:var(--text-sm);color:var(--text-muted)">No se pudieron cargar las sesiones activas.</p>`;
    }
  }

  async function crear(e) {
    e.preventDefault();

    const idFicha = document.getElementById('idFicha')?.value;
    if (!idFicha) {
      AttendQR.toast.warning('Selecciona una ficha para continuar.');
      document.getElementById('idFicha')?.focus();
      return;
    }

    const btn = document.getElementById('btnCrear');
    const orig = btn?.innerHTML;
    if (btn) {
      btn.disabled = true;
      btn.innerHTML = '<div class="spinner" style="width:16px;height:16px;border-color:rgba(255,255,255,.3);border-top-color:#fff"></div> Creando...';
    }

    try {
      const sesion = await Api.sesiones.crear({ id_ficha: parseInt(idFicha, 10) });

      AttendQR.toast.success('Sesión creada correctamente.');

      try {
        await Api.qr.generar(sesion.id_sesion);
      } catch { /* si falla el QR inicial, continúa igual */ }

      setTimeout(() => {
        window.location.href = `index.php?view=qr&rol=docente&sesion=${sesion.id_sesion}`;
      }, 600);

    } catch (err) {
      if (btn) { btn.disabled = false; btn.innerHTML = orig; }
      AttendQR.toast.error(err.message ?? 'Error al crear la sesión.');
    }
  }

  function esc(str) {
    return String(str ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
  }

  document.addEventListener('DOMContentLoaded', () => {
    if (window.ATTENDQR_VIEW === 'crear-sesion') init();
  });

  return { crear };
})();
