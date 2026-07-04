/**
 * AttendQR — Historial (Fase 2: datos reales desde API)
 */
const historial = (() => {

  const PER_PAGE = 15;
  let todasSesiones = []; // cache de datos cargados
  let paginaActual  = 1;
  const rol = () => window.ATTENDQR_USER?.rol ?? 'docente';

  // ─── Init ───────────────────────────────────────────────────────────

  async function init() {
    await cargarFichasFilter();
    await cargarDatos();
  }

  async function cargarFichasFilter() {
    if (rol() !== 'docente') return;
    const sel = document.getElementById('filterFicha');
    if (!sel) return;
    try {
      const fichaData = await Api.fichas.listar({ id_docente: window.ATTENDQR_USER.id });
      (fichaData.fichas ?? []).forEach(f => {
        const opt = document.createElement('option');
        opt.value = f.id_ficha;
        opt.textContent = `${f.codigo_ficha} · ${f.nombre_programa}`;
        sel.appendChild(opt);
      });
    } catch { /* filter sin fichas es aceptable */ }
  }

  async function cargarDatos() {
    const tbody = document.getElementById('historialBody');
    if (tbody) {
      tbody.innerHTML = `<tr><td colspan="10" style="text-align:center;padding:var(--sp-10)">
        <div class="spinner" style="margin:0 auto"></div></td></tr>`;
    }

    try {
      if (rol() === 'aprendiz') {
        await cargarHistorialAprendiz();
      } else {
        await cargarHistorialDocente();
      }
    } catch (err) {
      if (tbody) {
        tbody.innerHTML = `<tr><td colspan="10" style="text-align:center;color:var(--danger);padding:var(--sp-8)">
          Error al cargar datos: ${esc(err.message)}</td></tr>`;
      }
      AttendQR.toast.error('Error al cargar el historial: ' + err.message);
    }
  }

  async function cargarHistorialDocente() {
    const params  = obtenerFiltros();
    const data    = await Api.sesiones.listar(params);
    let sesiones  = data.sesiones ?? [];
    sesiones      = aplicarFiltroFecha(sesiones);
    todasSesiones = sesiones;
    renderResumenDocente(sesiones);
    renderTablaDocente(sesiones);
  }

  async function cargarHistorialAprendiz() {
    const id        = window.ATTENDQR_USER.id;
    const data      = await Api.asistencias.historial(id);
    const registros = data.registros ?? [];
    todasSesiones   = registros;
    renderResumenAprendiz(registros);
    renderTablaAprendiz(registros);
  }

  // ─── Render Docente ────────────────────────────────────────────────

  function renderResumenDocente(sesiones) {
    const total     = sesiones.length;
    const cerradas  = sesiones.filter(s => s.estado_sesion === 'cerrada').length;
    const abiertas  = sesiones.filter(s => s.estado_sesion === 'abierta').length;
    const cancelada = sesiones.filter(s => s.estado_sesion === 'cancelada').length;

    // Calcular media de asistencia usando los counts reales que ahora devuelve sesiones/listar
    const conDatos = sesiones.filter(s => (s.total_aprendices ?? 0) > 0);
    const mediaPct = conDatos.length
      ? Math.round(conDatos.reduce((acc, s) => {
          const pct = ((parseInt(s.presentes, 10) || 0) / s.total_aprendices) * 100;
          return acc + pct;
        }, 0) / conDatos.length)
      : null;

    setTxt('#sumTotal',     total);
    setTxt('#sumCerradas',  cerradas);
    setTxt('#sumAbiertas',  abiertas);
    setTxt('#sumCanceladas', cancelada);
    setTxt('#sumPct',       mediaPct !== null ? mediaPct + '%' : '—');
    setTxt('#historialSubtitle', `${total} registros encontrados`);
  }

  function renderTablaDocente(sesiones) {
    const tbody = document.getElementById('historialBody');
    if (!tbody) return;

    if (!sesiones.length) {
      tbody.innerHTML = `<tr><td colspan="10" class="empty-state" style="padding:var(--sp-10);text-align:center;color:var(--text-muted)">
        Sin sesiones que coincidan con los filtros</td></tr>`;
      renderPaginacion(0, 0);
      return;
    }

    const inicio = (paginaActual - 1) * PER_PAGE;
    const pagina = sesiones.slice(inicio, inicio + PER_PAGE);

    tbody.innerHTML = pagina.map(s => {
      const id     = s.id_sesion;
      const ficha  = esc(s.codigo_ficha ?? s.id_ficha ?? '—');
      const prog   = esc(s.nombre_programa ?? '');
      const fecha  = s.fecha_sesion ? fmtFecha(s.fecha_sesion) : '—';
      const apertu = s.hora_apertura  ? s.hora_apertura.slice(11,16)  : '—';
      const cierre = s.hora_cierre    ? s.hora_cierre.slice(11,16)    : '—';
      const badge  = estadoBadge(s.estado_sesion);

      const presentes = s.presentes        != null ? parseInt(s.presentes, 10)        : null;
      const retardos  = s.retardos         != null ? parseInt(s.retardos, 10)         : null;
      const ausentes  = s.ausentes_marcados != null ? parseInt(s.ausentes_marcados, 10) : null;
      const total     = s.total_aprendices  != null ? parseInt(s.total_aprendices, 10)  : null;
      const pct       = (total > 0 && presentes !== null) ? Math.round((presentes / total) * 100) : null;
      const fillClass = pct !== null ? (pct >= 80 ? '' : pct >= 60 ? ' pct-cell__fill--warning' : ' pct-cell__fill--danger') : '';

      return `
        <tr class="session-row" data-session="${id}" data-estado="${s.estado_sesion ?? ''}">
          <td>
            <button class="session-expand-toggle" onclick="historial.toggle(${id}, this)">
              <span class="session-expand-toggle__icon">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                     stroke="currentColor" style="width:14px;height:14px">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
              </span>
            </button>
          </td>
          <td>
            <div style="font-weight:var(--fw-medium)">${ficha}</div>
            <div style="font-size:var(--text-xs);color:var(--text-muted)">${prog}</div>
          </td>
          <td>${fecha}</td>
          <td>${apertu}</td>
          <td>${cierre}</td>
          <td>${badge}</td>
          <td>${presentes ?? '—'}</td>
          <td>${retardos  ?? '—'}</td>
          <td>${ausentes  ?? '—'}</td>
          <td class="pct-cell">
            <span>${pct !== null ? pct + '%' : '—'}</span>
            <div class="pct-cell__bar"><div class="pct-cell__fill${fillClass}" style="width:${pct ?? 0}%"></div></div>
          </td>
        </tr>
        <tr class="session-detail-row" id="detail-${id}">
          <td colspan="10">
            <div class="session-detail-inner" id="detail-inner-${id}">
              <div class="spinner" style="width:20px;height:20px;margin:var(--sp-2) auto"></div>
            </div>
          </td>
        </tr>`;
    }).join('');

    renderPaginacion(sesiones.length, pagina.length);
  }

  // ─── Render Aprendiz ──────────────────────────────────────────────

  function renderResumenAprendiz(registros) {
    const total    = registros.length;
    const presente = registros.filter(r => r.estado === 'presente').length;
    const retardo  = registros.filter(r => r.estado === 'retardo').length;
    const ausente  = registros.filter(r => r.estado === 'ausente').length;
    const pct      = total > 0 ? Math.round(((presente + retardo) / total) * 100) : 0;

    setTxt('#sumTotal',     total);
    setTxt('#sumCerradas',  presente);
    setTxt('#sumAbiertas',  retardo);
    setTxt('#sumCanceladas', ausente);
    setTxt('#sumPct',       pct + '%');

    // Renombrar etiquetas para rol aprendiz
    const labels = document.querySelectorAll('.summary-card__label');
    if (labels[1]) labels[1].textContent = 'Presentes';
    if (labels[2]) labels[2].textContent = 'Tardanzas';
    if (labels[3]) labels[3].textContent = 'Ausencias';

    setTxt('#historialSubtitle', `${total} sesiones registradas`);

    // Ajustar encabezados de tabla para aprendiz
    const thead = document.querySelector('#historialBody')?.closest('table')?.querySelector('thead tr');
    if (thead) {
      thead.innerHTML = `<th>Fecha</th><th>Ficha / Programa</th><th>Estado asistencia</th><th>Hora registro</th>`;
    }
  }

  function renderTablaAprendiz(registros) {
    const tbody = document.getElementById('historialBody');
    if (!tbody) return;

    if (!registros.length) {
      tbody.innerHTML = `<tr><td colspan="4" style="text-align:center;color:var(--text-muted);padding:var(--sp-10)">
        Sin registros de asistencia</td></tr>`;
      return;
    }

    const inicio = (paginaActual - 1) * PER_PAGE;
    const pagina = registros.slice(inicio, inicio + PER_PAGE);

    tbody.innerHTML = pagina.map(r => {
      const fecha = r.fecha_sesion ? fmtFecha(r.fecha_sesion) : '—';
      const hora  = r.hora_registro ? r.hora_registro.slice(0,5) : '—';
      const prog  = esc(r.nombre_programa ?? r.codigo_ficha ?? '—');
      return `<tr>
        <td>${fecha}</td>
        <td>${prog}</td>
        <td>${asistenciaBadge(r.estado)}</td>
        <td>${hora}</td>
      </tr>`;
    }).join('');

    renderPaginacion(registros.length, pagina.length);
  }

  // ─── Detalle expandible (docente) ─────────────────────────────────

  async function toggle(idSesion, btn) {
    const row   = document.getElementById(`detail-${idSesion}`);
    const inner = document.getElementById(`detail-inner-${idSesion}`);
    if (!row) return;

    const isOpen = row.classList.toggle('is-open');
    btn.classList.toggle('is-open');

    if (isOpen && inner && !inner.dataset.loaded) {
      inner.dataset.loaded = '1';
      try {
        // asistencias devuelve: { registros: [...], total, ... }
        const data = await Api.sesiones.asistencias(idSesion);
        renderDetalleAsistencias(inner, data.registros ?? []);
      } catch (err) {
        inner.innerHTML = `<p style="color:var(--danger);font-size:var(--text-sm)">
          Error al cargar asistencias: ${esc(err.message)}</p>`;
      }
    }
  }

  function renderDetalleAsistencias(container, asistencias) {
    if (!asistencias.length) {
      container.innerHTML = `<p style="font-size:var(--text-sm);color:var(--text-muted)">Sin registros de asistencia en esta sesión.</p>`;
      return;
    }
    container.innerHTML = `
      <h4 style="font-size:var(--text-sm);font-weight:var(--fw-semibold);margin-bottom:var(--sp-3)">
        Detalle de asistencia
      </h4>
      <table class="mini-table">
        <thead><tr><th>Aprendiz</th><th>Documento</th><th>Hora registro</th><th>Estado</th></tr></thead>
        <tbody>
          ${asistencias.map(a => `<tr>
            <td>${esc(a.nombre_aprendiz ?? a.nombre ?? '—')}</td>
            <td>${esc(a.numero_documento ?? '—')}</td>
            <td>${a.hora_registro ? a.hora_registro.slice(0,5) : '—'}</td>
            <td>${asistenciaBadge(a.estado)}</td>
          </tr>`).join('')}
        </tbody>
      </table>`;
  }

  // ─── Filtros ──────────────────────────────────────────────────────

  function obtenerFiltros() {
    const params = {};
    const ficha  = document.getElementById('filterFicha')?.value;
    const estado = document.getElementById('filterEstado')?.value;
    // Backend sesiones/listar solo soporta id_ficha y estado
    if (ficha)  params.id_ficha = ficha;
    if (estado) params.estado   = estado;
    return params;
  }

  function filtrosFecha() {
    const desde = document.getElementById('filterDesde')?.value;
    const hasta = document.getElementById('filterHasta')?.value;
    return { desde, hasta };
  }

  function aplicarFiltroFecha(sesiones) {
    const { desde, hasta } = filtrosFecha();
    if (!desde && !hasta) return sesiones;
    return sesiones.filter(s => {
      if (!s.fecha_sesion) return true;
      const f = s.fecha_sesion.slice(0, 10);
      if (desde && f < desde) return false;
      if (hasta && f > hasta) return false;
      return true;
    });
  }

  async function filtrar() {
    paginaActual = 1;
    await cargarDatos();
  }

  async function limpiar() {
    ['filterFicha', 'filterEstado', 'filterDesde', 'filterHasta'].forEach(id => {
      const el = document.getElementById(id);
      if (el) el.value = '';
    });
    paginaActual = 1;
    await cargarDatos();
    AttendQR.toast.info('Filtros limpiados.');
  }

  // ─── Paginación ───────────────────────────────────────────────────

  function renderPaginacion(total, enPagina) {
    const info = document.getElementById('historialPaginaInfo');
    const nav  = document.getElementById('historialPaginacion');
    if (!info || !nav) return;

    const inicio  = (paginaActual - 1) * PER_PAGE + 1;
    const fin     = Math.min(inicio + enPagina - 1, total);
    const paginas = Math.ceil(total / PER_PAGE);

    info.textContent = total > 0 ? `Mostrando ${inicio}–${fin} de ${total} sesiones` : '';

    if (paginas <= 1) { nav.innerHTML = ''; return; }

    const btns = [];
    btns.push(`<button class="btn btn-ghost btn-sm" onclick="historial.irPagina(${paginaActual - 1})" ${paginaActual === 1 ? 'disabled' : ''}>Anterior</button>`);
    for (let p = 1; p <= paginas; p++) {
      const activa = p === paginaActual ? 'style="background:var(--green-primary);color:#fff"' : '';
      btns.push(`<button class="btn btn-ghost btn-sm" ${activa} onclick="historial.irPagina(${p})">${p}</button>`);
    }
    btns.push(`<button class="btn btn-ghost btn-sm" onclick="historial.irPagina(${paginaActual + 1})" ${paginaActual === paginas ? 'disabled' : ''}>Siguiente</button>`);
    nav.innerHTML = btns.join('');
  }

  async function irPagina(p) {
    const paginas = Math.ceil(todasSesiones.length / PER_PAGE);
    if (p < 1 || p > paginas) return;
    paginaActual = p;
    if (rol() === 'aprendiz') {
      renderTablaAprendiz(todasSesiones);
    } else {
      renderTablaDocente(todasSesiones);
    }
  }

  // ─── Exportar (CSV simple) ────────────────────────────────────────

  function exportar() {
    if (!todasSesiones.length) {
      AttendQR.toast.warning('No hay datos para exportar.');
      return;
    }
    const cols = rol() === 'aprendiz'
      ? ['fecha_sesion', 'nombre_programa', 'estado', 'hora_registro']
      : ['fecha_sesion', 'codigo_ficha', 'nombre_programa', 'hora_apertura', 'hora_cierre', 'estado_sesion', 'presentes', 'retardos', 'ausentes'];

    const head = cols.join(',');
    const rows = todasSesiones.map(r =>
      cols.map(c => `"${String(r[c] ?? '').replace(/"/g, '""')}"`).join(',')
    );

    const csv  = [head, ...rows].join('\n');
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const a    = document.createElement('a');
    a.href     = URL.createObjectURL(blob);
    a.download = `historial_${new Date().toISOString().slice(0,10)}.csv`;
    a.click();
  }

  // ─── Helpers ──────────────────────────────────────────────────────

  function fmtFecha(str) {
    const d = new Date(str + 'T00:00:00');
    return d.toLocaleDateString('es-CO', { day:'2-digit', month:'2-digit', year:'numeric' });
  }

  function setTxt(sel, val) {
    const el = document.querySelector(sel);
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
    return map[estado] ?? `<span class="badge badge-neutral">${esc(estado ?? '—')}</span>`;
  }

  function asistenciaBadge(estado) {
    const map = {
      presente: '<span class="badge badge-success">Presente</span>',
      retardo:  '<span class="badge badge-warning">Tardanza</span>',
      ausente:  '<span class="badge badge-danger">Ausente</span>',
    };
    return map[estado] ?? `<span class="badge badge-neutral">${esc(estado ?? '—')}</span>`;
  }

  document.addEventListener('DOMContentLoaded', () => {
    if (window.ATTENDQR_VIEW === 'historial') init();
  });

  return { toggle, filtrar, limpiar, irPagina, exportar };
})();
