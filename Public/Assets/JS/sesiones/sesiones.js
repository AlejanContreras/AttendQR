/**
 * AttendQR — Sesiones (P2: ficha cards + modal)
 */
const sesiones = (() => {

  // IDs de fichas que tienen sesión activa actualmente
  let fichasConSesionActiva = new Set();

  async function init() {
    await Promise.all([cargarSesionesActivas(), cargarFichasGrid()]);
  }

  // ─── Cargar sesiones activas → banner ─────────────────────────────
  async function cargarSesionesActivas() {
    const banner = document.getElementById('bannerSesionesActivas');
    if (!banner) return;

    try {
      const data    = await Api.sesiones.listar({ estado: 'abierta' });
      const activas = data.sesiones ?? [];

      activas.forEach(s => {
        if (s.id_ficha) fichasConSesionActiva.add(String(s.id_ficha));
      });

      if (!activas.length) { banner.style.display = 'none'; return; }

      const links = activas.map(s => {
        const hora   = s.hora_apertura ? s.hora_apertura.slice(11, 16) : '—';
        const codigo = esc(s.codigo_ficha ?? `Sesión ${s.id_sesion}`);
        return `<a href="index.php?view=qr&rol=docente&sesion=${s.id_sesion}"
                   class="btn btn-sm"
                   style="background:var(--success-text);color:#fff;border:none">
                  Ver QR · ${codigo} (${hora})
                </a>`;
      }).join('');

      banner.style.display = 'block';
      banner.innerHTML = `
        <div class="sesion-activa-banner">
          <span class="sesion-activa-banner__dot"></span>
          <strong>${activas.length === 1 ? '1 clase activa ahora mismo' : `${activas.length} clases activas ahora mismo`}</strong>
          <div class="sesion-activa-banner__list">${links}</div>
        </div>`;
    } catch {
      banner.style.display = 'none';
    }
  }

  // ─── Cargar fichas → grid de cards ────────────────────────────────
  async function cargarFichasGrid() {
    const grid = document.getElementById('fichasGrid');
    if (!grid) return;

    try {
      const usuario = window.ATTENDQR_USER;
      const data    = await Api.fichas.listar({ id_docente: usuario.id, estado: 'activa' });
      const fichas  = data.fichas ?? [];

      if (!fichas.length) {
        grid.innerHTML = `
          <div style="grid-column:1/-1;text-align:center;padding:var(--sp-12) var(--sp-6)">
            <div style="font-size:48px;margin-bottom:var(--sp-4)">📋</div>
            <h3 style="font-size:var(--text-lg);font-weight:var(--fw-semibold);
                       color:var(--text-primary);margin-bottom:var(--sp-2)">
              No tienes fichas activas asignadas
            </h3>
            <p style="font-size:var(--text-sm);color:var(--text-muted)">
              Cuando el coordinador asigne una ficha a tu usuario, aparecerá aquí.
            </p>
          </div>`;
        return;
      }

      grid.innerHTML = fichas.map(f => renderFichaCard(f)).join('');
    } catch (err) {
      grid.innerHTML = `
        <div style="grid-column:1/-1;text-align:center;padding:var(--sp-10);color:var(--danger)">
          <p style="font-size:var(--text-sm)">No se pudieron cargar las fichas: ${esc(err.message)}</p>
        </div>`;
      AttendQR.toast.error('No se pudieron cargar las fichas: ' + err.message);
    }
  }

  function renderFichaCard(f) {
    const tieneActiva = fichasConSesionActiva.has(String(f.id_ficha));
    const fichaEsc    = esc(f.codigo_ficha);
    const progEsc     = esc(f.nombre_programa ?? '');

    if (tieneActiva) {
      // Ficha ya tiene clase activa — botón Ver QR
      const sesionActiva = [...fichasConSesionActiva].length; // solo para marcar
      return `
        <div class="ficha-card ficha-card--active">
          <div class="ficha-card__icon" style="background:var(--success-bg)">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                 style="color:var(--success-text)">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
          </div>
          <div class="ficha-card__body">
            <div class="ficha-card__code">Ficha ${fichaEsc}</div>
            <div class="ficha-card__prog">${progEsc}</div>
            <div class="ficha-card__status">
              <span style="display:inline-flex;align-items:center;gap:4px;
                           color:var(--success-text);font-weight:var(--fw-medium)">
                <span style="width:7px;height:7px;border-radius:50%;
                             background:var(--success);display:inline-block;
                             animation:blink 1.5s ease infinite"></span>
                Clase activa
              </span>
            </div>
          </div>
          <div class="ficha-card__cta">
            <a href="#" onclick="return sesiones.irQrFicha(${f.id_ficha})"
               class="btn btn-sm"
               style="background:var(--success-text);color:#fff;border:none;white-space:nowrap">
              Ver QR →
            </a>
          </div>
        </div>`;
    }

    return `
      <div class="ficha-card"
           onclick="sesiones.abrirModal(${f.id_ficha}, '${fichaEsc}', '${progEsc.replace(/'/g, "\\'")}')"
           tabindex="0" role="button"
           onkeydown="if(event.key==='Enter'||event.key===' ')sesiones.abrirModal(${f.id_ficha},'${fichaEsc}','${progEsc.replace(/'/g, "\\'")}')">
        <div class="ficha-card__icon">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
          </svg>
        </div>
        <div class="ficha-card__body">
          <div class="ficha-card__code">Ficha ${fichaEsc}</div>
          <div class="ficha-card__prog">${progEsc}</div>
          <div class="ficha-card__status">
            <span style="color:var(--text-muted)">Sin clase activa</span>
          </div>
        </div>
        <div class="ficha-card__cta">
          <button class="btn btn-primary btn-sm" style="white-space:nowrap"
                  onclick="event.stopPropagation();sesiones.abrirModal(${f.id_ficha},'${fichaEsc}','${progEsc.replace(/'/g, "\\'")}')">
            Iniciar clase →
          </button>
        </div>
      </div>`;
  }

  // ─── Navegar al QR de la ficha activa ─────────────────────────────
  async function irQrFicha(idFicha) {
    try {
      const data    = await Api.sesiones.listar({ estado: 'abierta' });
      const activas = data.sesiones ?? [];
      const sesion  = activas.find(s => String(s.id_ficha) === String(idFicha));
      if (sesion) {
        window.location.href = `index.php?view=qr&rol=docente&sesion=${sesion.id_sesion}`;
      }
    } catch { /* ignore */ }
    return false;
  }

  // ─── Modal ─────────────────────────────────────────────────────────
  function abrirModal(idFicha, codigo, programa) {
    document.getElementById('modalFichaId').value    = idFicha;
    document.getElementById('modalIniciarTitulo').textContent = `Ficha ${codigo}`;
    document.getElementById('modalIniciarSub').textContent    = programa;

    const horaInput = document.getElementById('horaInicioClase');
    if (horaInput) {
      const now = new Date();
      horaInput.value = now.getHours().toString().padStart(2, '0') + ':' + now.getMinutes().toString().padStart(2, '0');
    }

    document.getElementById('nombreMateria').value = '';

    const backdrop = document.getElementById('modalIniciarBackdrop');
    backdrop.style.display = 'flex';
    setTimeout(() => horaInput?.focus(), 100);
  }

  function cerrarModal() {
    const backdrop = document.getElementById('modalIniciarBackdrop');
    if (backdrop) backdrop.style.display = 'none';
  }

  // ─── Crear sesión ──────────────────────────────────────────────────
  async function crear(e) {
    e.preventDefault();

    const idFicha         = document.getElementById('modalFichaId')?.value;
    const horaInicioClase = document.getElementById('horaInicioClase')?.value;
    const nombreMateria   = document.getElementById('nombreMateria')?.value.trim() ?? '';

    if (!idFicha) {
      AttendQR.toast.warning('Error: no se identificó la ficha. Recarga la página.');
      return;
    }
    if (!horaInicioClase) {
      AttendQR.toast.warning('Ingresa la hora oficial de inicio de la clase.');
      document.getElementById('horaInicioClase')?.focus();
      return;
    }

    const btn  = document.getElementById('btnCrear');
    const orig = btn?.innerHTML;
    if (btn) {
      btn.disabled = true;
      btn.innerHTML = '<div class="spinner" style="width:16px;height:16px;border-color:rgba(255,255,255,.3);border-top-color:#fff"></div> Iniciando...';
    }

    try {
      const sesion = await Api.sesiones.crear({
        id_ficha:          parseInt(idFicha, 10),
        hora_inicio_clase: horaInicioClase,
        nombre_materia:    nombreMateria,
      });

      try { await Api.qr.generar(sesion.id_sesion); } catch { /* continúa */ }

      AttendQR.toast.success('Clase iniciada. Mostrando código QR...');
      cerrarModal();

      setTimeout(() => {
        window.location.href = `index.php?view=qr&rol=docente&sesion=${sesion.id_sesion}`;
      }, 500);

    } catch (err) {
      if (btn) { btn.disabled = false; btn.innerHTML = orig; }
      AttendQR.toast.error(err.message ?? 'Error al crear la sesión.');
    }
  }

  // Cerrar modal con Escape
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape') cerrarModal();
  });

  function esc(str) {
    return String(str ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
  }

  document.addEventListener('DOMContentLoaded', () => {
    if (window.ATTENDQR_VIEW === 'crear-sesion') init();
  });

  return { crear, abrirModal, cerrarModal, irQrFicha };
})();
