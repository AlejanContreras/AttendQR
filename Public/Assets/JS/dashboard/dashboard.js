/**
 * AttendQR — Dashboard
 */
document.addEventListener('DOMContentLoaded', async () => {
  const usuario = window.ATTENDQR_USER;
  if (!usuario) return;

  if (usuario.rol === 'docente') {
    await cargarDashboardDocente(usuario.id);
  } else {
    await cargarDashboardAprendiz(usuario.id);
  }
});

async function cargarDashboardDocente(idDocente) {
  try {
    const stats = await Api.estadisticas.dashboard({ id_docente: idDocente });
    renderStatsDocente(stats);
  } catch (err) {
    AttendQR.toast.error('No se pudieron cargar las estadísticas: ' + err.message);
  }

  try {
    // Api.fichas.listar() → { fichas: [...], total }
    const data   = await Api.fichas.listar({ id_docente: idDocente, estado: 'activa' });
    renderFichasDocente(data.fichas ?? []);
  } catch { /* silencioso */ }

  try {
    // Api.sesiones.listar() → { sesiones: [...], total }
    const data = await Api.sesiones.listar({ estado: 'abierta' });
    renderSesionesRecientes(data.sesiones ?? []);
  } catch { /* silencioso */ }
}

async function cargarDashboardAprendiz(idAprendiz) {
  try {
    // Api.asistencias.historial() → { registros: [...], resumen: { presentes, retardos, ausentes }, total }
    const data    = await Api.asistencias.historial(idAprendiz);
    const registros = data.registros ?? [];
    const resumen   = data.resumen   ?? {};
    renderStatsAprendiz(resumen, registros.length);
    renderHistorialAprendiz(registros);
  } catch (err) {
    AttendQR.toast.error('No se pudo cargar tu historial: ' + err.message);
  }
}

// ─── Render docente ───────────────────────────────────────────────────

function renderStatsDocente(stats) {
  // Backend devuelve: { sesiones_activas, sesiones_cerradas, asistencias_hoy, filtros }
  // total_aprendices, fichas_activas y porcentaje_asistencia no están disponibles en este endpoint
  setVal('#statSesionesActivas', stats.sesiones_activas ?? '—');
  setVal('#statAprendices',      '—');
  setVal('#statAsistenciaHoy',   stats.asistencias_hoy ?? '—');
  setVal('#statFichasActivas',   '—'); // se actualiza en renderFichasDocente
}

function renderFichasDocente(fichas) {
  const el = document.getElementById('fichasActivasBadge');
  if (el) el.textContent = fichas.length + ' fichas';
  setVal('#statFichasActivas', fichas.length);
}

function renderSesionesRecientes(sesiones) {
  const tbody = document.getElementById('sesionesRecientesBody');
  if (!tbody) return;

  if (!sesiones.length) {
    tbody.innerHTML = `<tr><td colspan="4" style="text-align:center;color:var(--text-muted);padding:var(--sp-6)">
      Sin sesiones activas</td></tr>`;
    return;
  }

  tbody.innerHTML = sesiones.slice(0, 5).map(s => {
    const fecha = s.fecha_sesion ? new Date(s.fecha_sesion).toLocaleDateString('es-CO') : '—';
    const hora  = s.hora_apertura ? s.hora_apertura.slice(11, 16) : '—';
    const badge = estadoBadge(s.estado_sesion);
    return `<tr>
      <td>
        <div style="font-weight:var(--fw-medium)">${esc(s.codigo_ficha ?? s.id_ficha)}</div>
        <div style="font-size:var(--text-xs);color:var(--text-muted)">${esc(s.nombre_programa ?? '')}</div>
      </td>
      <td>${fecha}, ${hora}</td>
      <td>${badge}</td>
      <td>
        <a href="index.php?view=qr&rol=docente&sesion=${s.id_sesion}" class="btn btn-ghost btn-sm">Ver QR</a>
      </td>
    </tr>`;
  }).join('');
}

// ─── Render aprendiz ──────────────────────────────────────────────────

function renderStatsAprendiz(resumen, total) {
  // resumen = { presentes, retardos, ausentes } de la API
  const presente = resumen.presentes ?? 0;
  const retardo  = resumen.retardos  ?? 0;
  const ausente  = resumen.ausentes  ?? 0;
  const pct      = total > 0 ? Math.round((presente / total) * 100) : 0;

  setVal('#statAsistenciaPct',  pct + '%');
  setVal('#statSesionesTotal',  `${presente}/${total}`);
  setVal('#statTardanzas',      retardo);
  setVal('#statAusencias',      ausente);

  actualizarDonut(pct, retardo, ausente, total);
}

function renderHistorialAprendiz(registros) {
  const tbody = document.getElementById('historialAprendizBody');
  if (!tbody) return;

  if (!registros.length) {
    tbody.innerHTML = `<tr><td colspan="3" style="text-align:center;color:var(--text-muted);padding:var(--sp-6)">
      Sin registros</td></tr>`;
    return;
  }

  tbody.innerHTML = registros.slice(0, 5).map(r => {
    const fecha = r.fecha_sesion
      ? new Date(r.fecha_sesion).toLocaleDateString('es-CO', { day:'2-digit', month:'2-digit', year:'numeric' })
      : '—';
    const hora  = r.hora_apertura ? r.hora_apertura.slice(11,16) : '';
    return `<tr>
      <td>
        <div>${fecha}</div>
        <div style="font-size:var(--text-xs);color:var(--text-muted)">${hora}</div>
      </td>
      <td>${esc(r.nombre_programa ?? r.codigo_ficha ?? '—')}</td>
      <td>${asistenciaBadge(r.estado)}</td>
    </tr>`;
  }).join('');
}

function actualizarDonut(pct, retardo, ausente, total) {
  const texto = document.querySelector('#attendanceCircleText');
  if (texto) texto.textContent = pct + '%';
}

// ─── Helpers ──────────────────────────────────────────────────────────

function setVal(selector, val) {
  const el = document.querySelector(selector);
  if (el) el.textContent = val;
}

function esc(str) {
  return String(str ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

function estadoBadge(estado) {
  const map = {
    abierta:   '<span class="badge badge-success">Abierta</span>',
    cerrada:   '<span class="badge badge-neutral">Cerrada</span>',
    cancelada: '<span class="badge badge-danger">Cancelada</span>',
  };
  return map[estado] ?? `<span class="badge badge-neutral">${esc(estado)}</span>`;
}

function asistenciaBadge(estado) {
  const map = {
    presente: '<span class="badge badge-success">Presente</span>',
    retardo:  '<span class="badge badge-warning">Tardanza</span>',
    ausente:  '<span class="badge badge-danger">Ausente</span>',
  };
  return map[estado] ?? `<span class="badge badge-neutral">${esc(estado)}</span>`;
}
