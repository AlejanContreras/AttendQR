/**
 * AttendQR — QR Dinámico (Fase 2: tokens reales desde backend)
 *
 * Flujo:
 * 1. Lee ?sesion=ID de la URL
 * 2. Carga detalle de la sesión + token activo
 * 3. Renderiza el QR (librería qrcode.js del CDN o SVG simple)
 * 4. Hace polling al backend cada 30s para obtener el token rotado
 * 5. Actualiza contadores via /api/sesiones/estadisticas/{id}
 */
const qr = (() => {

  let idSesion       = null;
  let countdownTimer = null;
  let pollTimer      = null;
  let statsTimer     = null;
  let remaining      = 30;
  let ROTATION_SEC   = 30;
  let elapsedSeconds = 0;
  let elapsedTimer   = null;

  async function init() {
    const params = new URLSearchParams(window.location.search);
    idSesion = parseInt(params.get('sesion') ?? '0', 10);

    if (!idSesion) {
      // Sin sesión explícita: buscar la primera sesión activa del docente
      try {
        const fichaData = await Api.fichas.listar({
          id_docente: window.ATTENDQR_USER?.id,
          estado: 'activa',
        });
        const fichas = fichaData.fichas ?? [];
        if (fichas.length) {
          const activa = await Api.sesiones.activa(fichas[0].id_ficha);
          idSesion = activa.id_sesion;
        }
      } catch {
        mostrarError('No hay sesiones activas. Crea una sesión primero.');
        return;
      }
    }

    if (!idSesion) {
      mostrarError('No se encontró una sesión activa.');
      return;
    }

    await Promise.all([
      cargarSesion(),
      cargarTokenYActualizar(),
      cargarEstadisticas(),
    ]);

    iniciarElapsed();
    iniciarCountdown();
    iniciarPollEstadisticas();
  }

  async function cargarSesion() {
    try {
      const sesion = await Api.sesiones.detalle(idSesion);
      renderInfoSesion(sesion);
    } catch (err) {
      AttendQR.toast.error('Error al cargar la sesión: ' + err.message);
    }
  }

  async function cargarTokenYActualizar() {
    const overlay  = document.getElementById('qrRefreshOverlay');
    const tokenEl  = document.getElementById('qrToken');

    if (overlay) overlay.style.display = 'flex';

    try {
      const resultado = await Api.qr.tokenActivo(idSesion);
      // tokenActivo devuelve: { token_valor, segundos_restantes, expira_en, id_sesion }
      const token = resultado.token_valor ?? '';

      if (tokenEl) tokenEl.textContent = token;
      renderQrSvg(token);

      if (resultado.segundos_restantes != null) {
        remaining = Math.max(1, parseInt(resultado.segundos_restantes, 10));
        ROTATION_SEC = remaining;
      }

    } catch (err) {
      if (tokenEl) tokenEl.textContent = 'Sin token';
      AttendQR.toast.warning('No hay token activo. Generando uno nuevo...');
      try {
        await Api.qr.generar(idSesion);
        await cargarTokenYActualizar();
        return;
      } catch { /* fallo silencioso */ }
    } finally {
      if (overlay) overlay.style.display = 'none';
    }
  }

  async function cargarEstadisticas() {
    try {
      const stats = await Api.sesiones.estadisticas(idSesion);
      renderContadores(stats);
    } catch { /* silencioso */ }
  }

  function iniciarCountdown() {
    clearInterval(countdownTimer);
    remaining = ROTATION_SEC;

    const timerEl = document.getElementById('qrCountdown');
    const barEl   = document.getElementById('qrProgress');
    if (timerEl) timerEl.classList.remove('is-warning');

    countdownTimer = setInterval(async () => {
      remaining--;

      if (timerEl) timerEl.textContent = remaining;
      if (barEl)   barEl.style.width = `${(remaining / ROTATION_SEC) * 100}%`;

      if (remaining <= 10 && timerEl) timerEl.classList.add('is-warning');
      else if (remaining > 10 && timerEl) timerEl.classList.remove('is-warning');

      if (remaining <= 0) {
        clearInterval(countdownTimer);
        // Generar nuevo token y recargar
        try {
          await Api.qr.generar(idSesion);
        } catch { /* el backend ya puede haber rotado automáticamente */ }
        await cargarTokenYActualizar();
        iniciarCountdown();
      }
    }, 1000);
  }

  function iniciarPollEstadisticas() {
    clearInterval(statsTimer);
    statsTimer = setInterval(cargarEstadisticas, 15_000); // cada 15s
  }

  function iniciarElapsed() {
    clearInterval(elapsedTimer);
    elapsedTimer = setInterval(() => {
      elapsedSeconds++;
      const h = Math.floor(elapsedSeconds / 3600).toString().padStart(2, '0');
      const m = Math.floor((elapsedSeconds % 3600) / 60).toString().padStart(2, '0');
      const s = (elapsedSeconds % 60).toString().padStart(2, '0');
      const el = document.getElementById('sessionElapsed');
      if (el) el.textContent = `${h}:${m}:${s}`;
    }, 1000);
  }

  // ─── Render ───────────────────────────────────────────────────────────

  function renderInfoSesion(sesion) {
    const ficha = sesion.codigo_ficha ?? sesion.id_ficha ?? '—';
    const prog  = sesion.nombre_programa ?? '—';
    const hora  = sesion.hora_apertura?.slice(0,5) ?? '—';
    setTxt('#qrFichaCodigo',   ficha);
    setTxt('#qrFichaCodigo2',  ficha);
    setTxt('#qrFichaProgram',  prog);
    setTxt('#qrFichaProgram2', prog);
    setTxt('#qrHoraApertura',  hora);
    setTxt('#qrHoraApertura2', hora);
    setTxt('#qrLimiteRetardo', (sesion.limite_retardo ?? '—') + ' min');
    setTxt('#qrRotacion',    (sesion.rotacion_qr ?? 30) + ' s');

    // Actualizar link "Cerrar sesión" en el botón
    const btnCerrar = document.getElementById('btnCerrarSesion');
    if (btnCerrar) {
      btnCerrar.onclick = () => confirmarCierre(sesion.id_sesion);
    }
  }

  function renderContadores(stats) {
    // estadisticas devuelve: { estadisticas: { presentes, retardos, ausentes_marcados, sin_registro, total_aprendices, ... } }
    const e = stats.estadisticas ?? {};
    setTxt('#countPresente',  e.presentes         ?? 0);
    setTxt('#countTardanza',  e.retardos          ?? 0);
    setTxt('#countAusente',   e.ausentes_marcados ?? 0);
    setTxt('#countPendiente', e.sin_registro      ?? 0);

    const total = (e.presentes ?? 0) + (e.retardos ?? 0) + (e.ausentes_marcados ?? 0);
    const cap   = e.total_aprendices ?? total;
    const pct   = cap > 0 ? Math.round(((e.presentes ?? 0) / cap) * 100) : 0;

    setTxt('#countTotal', `${total} / ${cap}`);
    const bar = document.getElementById('attendanceBar');
    if (bar) bar.style.width = pct + '%';
  }

  // QR SVG simple basado en el token (visual representativo)
  function renderQrSvg(token) {
    const el = document.getElementById('qrSvg');
    if (!el) return;
    // El SVG decorativo ya está en el HTML, solo actualizamos el token visible
    // Para un QR real se necesitaría una librería como qrcode.js
    const tokenEl = document.getElementById('qrToken');
    if (tokenEl) tokenEl.textContent = token || '—';
  }

  async function confirmarCierre(id) {
    AttendQR.modal.setTitle('modal', 'Cerrar sesión de asistencia');
    AttendQR.modal.setBody('modal', `
      <p style="color:var(--text-secondary);margin-bottom:var(--sp-4)">
        ¿Confirmas cerrar la sesión activa? Los registros quedarán guardados y no se podrán agregar más asistencias.
      </p>`);
    const confirmBtn = document.getElementById('modalConfirm');
    if (confirmBtn) {
      confirmBtn.textContent = 'Cerrar sesión';
      confirmBtn.onclick = async () => {
        AttendQR.modal.close('modal');
        AttendQR.loader.show('Cerrando sesión...');
        try {
          await Api.sesiones.cerrar(id);
          clearInterval(countdownTimer);
          clearInterval(statsTimer);
          clearInterval(elapsedTimer);
          AttendQR.loader.hide();
          AttendQR.toast.success('Sesión cerrada correctamente.');
          setTimeout(() => window.location.href = 'index.php?view=historial&rol=docente', 1000);
        } catch (err) {
          AttendQR.loader.hide();
          AttendQR.toast.error('Error al cerrar la sesión: ' + err.message);
        }
      };
    }
    AttendQR.modal.open('modal');
  }

  function mostrarError(msg) {
    const area = document.querySelector('.qr-display-card') ?? document.getElementById('contentArea');
    if (area) {
      area.innerHTML = `<div class="empty-state" style="padding:var(--sp-12)">
        <p class="empty-state__text">${msg}</p>
        <a href="index.php?view=crear-sesion&rol=docente" class="btn btn-primary">Crear sesión</a>
      </div>`;
    }
  }

  function setTxt(sel, val) {
    const el = document.querySelector(sel);
    if (el) el.textContent = val;
  }

  document.addEventListener('DOMContentLoaded', () => {
    if (window.ATTENDQR_VIEW === 'qr') init();
  });

  return { confirmarCierre };
})();
