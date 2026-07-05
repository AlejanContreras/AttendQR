/**
 * AttendQR — Registro de Asistencia (aprendiz)
 *
 * El aprendiz ingresa el token QR mostrado por el docente.
 * Llama a POST /api/asistencias/registrar con { token }.
 *
 * Códigos de error del backend:
 *   403 — el aprendiz no pertenece a la ficha de esa sesión
 *   409 — ya registró asistencia en esta sesión
 *   410 — token expirado o rotado
 *   422 — la sesión no está abierta
 *   404 — token no encontrado
 */
const asistencia = (() => {

  async function registrar(e) {
    e.preventDefault();

    const tokenInput = document.getElementById('tokenInput');
    const token      = tokenInput?.value.trim() ?? '';

    ocultarResultado();

    if (!token) {
      mostrarError('Ingresa el token QR para continuar.');
      tokenInput?.focus();
      return;
    }

    const btn  = document.getElementById('btnRegistrar');
    const orig = btn?.innerHTML;
    if (btn) {
      btn.disabled = true;
      btn.innerHTML = '<div class="spinner" style="width:16px;height:16px;border-color:rgba(255,255,255,.3);border-top-color:#fff;display:inline-block"></div> Registrando...';
    }

    try {
      const resultado = await Api.asistencias.registrar({ token });

      if (btn) { btn.disabled = false; btn.innerHTML = orig; }
      if (tokenInput) tokenInput.value = '';

      mostrarExito(resultado);

    } catch (err) {
      if (btn) { btn.disabled = false; btn.innerHTML = orig; }

      const msg = mensajeError(err);
      mostrarError(msg);
    }
  }

  function mensajeError(err) {
    switch (err.status) {
      case 403: return 'No perteneces a la ficha de esta sesión. Verifica con tu docente.';
      case 409: return 'Ya registraste tu asistencia en esta sesión.';
      case 410: return 'El token ha expirado o ya no está activo. Pide el token actualizado a tu docente.';
      case 422: return err.message ?? 'La sesión ya no está abierta para registro de asistencia.';
      case 404: return 'Token no encontrado. Verifica que lo hayas ingresado correctamente.';
      default:  return err.message ?? 'Error al registrar la asistencia. Intenta de nuevo.';
    }
  }

  function mostrarExito(resultado) {
    const area = document.getElementById('resultadoArea');
    if (!area) return;

    const estado  = resultado.estado ?? 'presente';
    const badgeClass = estado === 'presente' ? 'badge-success' : estado === 'retardo' ? 'badge-warning' : 'badge-neutral';
    const labelEstado = { presente: 'Presente', retardo: 'Tardanza', ausente: 'Ausente' }[estado] ?? estado;
    const hora   = resultado.hora_registro ? resultado.hora_registro.slice(11,16) || resultado.hora_registro.slice(0,5) : '—';

    area.innerHTML = `
      <div class="alert" style="background:var(--green-light);border:1px solid var(--green-primary);border-radius:var(--r-md);padding:var(--sp-5)">
        <div style="display:flex;align-items:center;gap:var(--sp-3);margin-bottom:var(--sp-3)">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="var(--green-primary)" style="width:28px;height:28px;flex-shrink:0">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          <div>
            <div style="font-weight:var(--fw-semibold);color:var(--green-primary);font-size:var(--text-md)">¡Asistencia registrada!</div>
            <div style="font-size:var(--text-sm);color:var(--text-secondary)">Hora: ${hora}</div>
          </div>
        </div>
        <div style="display:flex;align-items:center;gap:var(--sp-2)">
          <span>Estado:</span>
          <span class="badge ${badgeClass}">${labelEstado}</span>
          ${resultado.minutos_retardo > 0 ? `<span style="font-size:var(--text-xs);color:var(--text-muted)">(${resultado.minutos_retardo} min de tardanza)</span>` : ''}
        </div>
      </div>`;
    area.style.display = 'block';
  }

  function mostrarError(mensaje) {
    const area = document.getElementById('resultadoArea');
    if (!area) return;
    area.innerHTML = `
      <div class="alert alert-danger" style="border-radius:var(--r-md)">
        <div style="display:flex;align-items:center;gap:var(--sp-2)">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" style="width:20px;height:20px;flex-shrink:0;color:var(--danger)">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
          </svg>
          <span>${esc(mensaje)}</span>
        </div>
      </div>`;
    area.style.display = 'block';
  }

  function ocultarResultado() {
    const area = document.getElementById('resultadoArea');
    if (area) { area.innerHTML = ''; area.style.display = 'none'; }
  }

  function esc(str) {
    return String(str ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
  }

  document.addEventListener('DOMContentLoaded', () => {
    if (window.ATTENDQR_VIEW !== 'registrar-asistencia') return;

    // Ocultar área de resultado al inicio
    ocultarResultado();

    // Focus automático al campo de token
    document.getElementById('tokenInput')?.focus();
  });

  return { registrar };
})();
