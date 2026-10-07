/**
 * AttendQR — Gestión de Aprendices (docente)
 * Vista: aprendices
 */
const aprendicesGestion = (() => {

  let todos      = []; // cache local de todos los aprendices
  let sortField  = 'apellidos';
  let sortAsc    = true;

  // ─── Init ────────────────────────────────────────────────────────────────

  async function init() {
    if (window.ATTENDQR_VIEW !== 'aprendices') return;
    await Promise.all([cargarFichasSelect(), cargarAprendices()]);
  }

  // ─── Carga de datos ──────────────────────────────────────────────────────

  async function cargarFichasSelect() {
    try {
      const data = await Api.fichas.listar({ activa: 1 });
      const fichas = Array.isArray(data) ? data : (data.fichas ?? []);
      const sel = document.getElementById('filtroFicha');
      if (!sel) return;
      fichas.forEach(f => {
        const opt = document.createElement('option');
        opt.value = f.id_ficha;
        opt.textContent = `${f.codigo_ficha} — ${f.nombre_programa}`;
        sel.appendChild(opt);
      });
    } catch { /* filtro de fichas es opcional */ }
  }

  async function cargarAprendices() {
    const params = {};
    const ficha   = document.getElementById('filtroFicha')?.value;
    const estado  = document.getElementById('filtroEstado')?.value;
    const cuenta  = document.getElementById('filtroCuenta')?.value;

    if (ficha)  params.id_ficha = ficha;
    if (estado) params.estado   = estado;
    if (cuenta) params.cuenta   = cuenta;

    try {
      const data = await Api.aprendices.listar(params);
      todos = Array.isArray(data) ? data : (data.aprendices ?? []);
      _sortInPlace(todos);
      renderTabla(todos);
      actualizarStats(todos);
    } catch (err) {
      renderError(err.message ?? 'Error al cargar aprendices.');
    }
  }

  // ─── Renderizado ─────────────────────────────────────────────────────────

  function renderTabla(lista) {
    const tbody = document.getElementById('tbodyAprendices');
    if (!tbody) return;

    if (lista.length === 0) {
      tbody.innerHTML = `
        <tr>
          <td colspan="7" style="text-align:center;padding:var(--sp-8);cursor:pointer"
              onclick="document.getElementById('inputCsv').click()"
              title="Haz clic para seleccionar un archivo CSV">
            <div style="color:var(--text-muted);font-size:var(--text-2xl);margin-bottom:var(--sp-2)">👥</div>
            <div style="font-weight:var(--fw-semibold);margin-bottom:var(--sp-1)">No hay aprendices</div>
            <div style="font-size:var(--text-sm);color:var(--text-muted)">
              Haz clic aquí o usa el botón <strong>Importar CSV</strong> para agregar aprendices.
            </div>
          </td>
        </tr>`;
      return;
    }

    tbody.innerHTML = lista.map(a => `
      <tr>
        <td style="font-family:monospace;font-weight:var(--fw-semibold)">${esc(a.numero_documento)}</td>
        <td>${esc(a.nombres)} ${esc(a.apellidos)}</td>
        <td><span class="badge badge-neutral">${esc(a.codigo_ficha)}</span></td>
        <td style="text-align:center">${resetBtn(a)}</td>
        <td>${badgeCuenta(a.cuenta_activada)}</td>
        <td>${badgeEstado(a.activo)}</td>
        <td style="text-align:center;white-space:nowrap">
          ${accionesHTML(a)}
        </td>
      </tr>`).join('');
  }

  function resetBtn(a) {
    if (!parseInt(a.tiene_solicitud)) {
      return '<span style="color:var(--text-muted);font-size:var(--text-xs)">—</span>';
    }
    return `<button class="btn btn-sm"
              style="background:#FEF3C7;color:#92400E;border:1px solid #FCD34D"
              onclick="aprendicesGestion.restablecerContrasena(${a.id_aprendiz},this)">
              🔑 Restablecer
            </button>`;
  }

  function badgeCuenta(v) {
    return parseInt(v) === 1
      ? '<span class="badge badge-success">Activada</span>'
      : '<span class="badge badge-warning">Pendiente</span>';
  }

  function badgeEstado(v) {
    return parseInt(v) === 1
      ? '<span class="badge badge-success">Activo</span>'
      : '<span class="badge badge-danger">Inactivo</span>';
  }

  function accionesHTML(a) {
    const activo = parseInt(a.activo) === 1;
    return activo
      ? `<button class="btn btn-ghost btn-sm" onclick="aprendicesGestion.desactivar(${a.id_aprendiz})" title="Desactivar aprendiz">Desactivar</button>`
      : `<button class="btn btn-ghost btn-sm" style="color:var(--green-primary)" onclick="aprendicesGestion.activar(${a.id_aprendiz})" title="Activar aprendiz">Activar</button>`;
  }

  function actualizarStats(lista) {
    const total      = lista.length;
    const activos    = lista.filter(a => parseInt(a.activo) === 1).length;
    const pendientes = lista.filter(a => parseInt(a.cuenta_activada) === 0).length;
    const inactivos  = lista.filter(a => parseInt(a.activo) === 0).length;

    const setVal = (id, v) => {
      const el = document.getElementById(id);
      if (el) { el.textContent = v; el.classList.remove('skeleton', 'skeleton-value'); }
    };

    setVal('statTotal',      total);
    setVal('statActivos',    activos);
    setVal('statPendientes', pendientes);
    setVal('statInactivos',  inactivos);
  }

  function renderError(msg) {
    const tbody = document.getElementById('tbodyAprendices');
    if (tbody) {
      tbody.innerHTML = `<tr><td colspan="7" style="text-align:center;padding:var(--sp-6);color:var(--danger)">${esc(msg)}</td></tr>`;
    }
  }

  // ─── Filtros ─────────────────────────────────────────────────────────────

  async function filtrar() {
    document.getElementById('filtroBuscar').value = '';
    await cargarAprendices();
  }

  function buscarLocal(query) {
    const q = query.toLowerCase();
    const filtrados = todos.filter(a =>
      a.numero_documento.toLowerCase().includes(q) ||
      a.nombres.toLowerCase().includes(q) ||
      a.apellidos.toLowerCase().includes(q)
    );
    renderTabla(filtrados);
  }

  function limpiarFiltros() {
    ['filtroFicha', 'filtroEstado', 'filtroCuenta', 'filtroBuscar'].forEach(id => {
      const el = document.getElementById(id);
      if (el) el.value = '';
    });
    cargarAprendices();
  }

  // ─── Ordenamiento ─────────────────────────────────────────────────────────

  function _sortInPlace(lista) {
    lista.sort((a, b) => {
      const va = (a[sortField] ?? '').toString().toLowerCase();
      const vb = (b[sortField] ?? '').toString().toLowerCase();
      return sortAsc ? va.localeCompare(vb, 'es') : vb.localeCompare(va, 'es');
    });
  }

  function sortBy(campo) {
    if (sortField === campo) {
      sortAsc = !sortAsc;
    } else {
      sortField = campo;
      sortAsc   = true;
    }
    // Actualizar indicadores visuales en cabeceras
    document.querySelectorAll('[data-sort]').forEach(th => {
      const icon = document.getElementById(`sort-${th.dataset.sort}`);
      if (!icon) return;
      if (th.dataset.sort === campo) {
        icon.textContent = sortAsc ? '▲' : '▼';
      } else {
        icon.textContent = '';
      }
    });

    const sorted = [...todos];
    _sortInPlace(sorted);
    renderTabla(sorted);
  }

  // ─── Acciones individuales ────────────────────────────────────────────────

  async function activar(id) {
    try {
      await Api.aprendices.activar(id);
      toast('Aprendiz activado correctamente.');
      await cargarAprendices();
    } catch (err) {
      toast(err.message ?? 'Error al activar.', 'error');
    }
  }

  async function restablecerContrasena(id, btn) {
    if (!confirm('¿Restablecer la contraseña de este aprendiz?\nSe generará una contraseña temporal aleatoria.')) return;
    btn.disabled = true;
    try {
      const resultado = await Api.aprendices.restablecerContrasena(id);
      alert(`✅ Contraseña restablecida\n\nAprendiz: ${resultado.nombres}\nContraseña temporal: ${resultado.password_temporal}\n\nEntrega esta contraseña al aprendiz para que inicie sesión.`);
      await cargarAprendices();
    } catch (err) {
      toast(err.message ?? 'Error al restablecer la contraseña.', 'error');
      btn.disabled = false;
    }
  }

  async function desactivar(id) {
    if (!confirm('¿Desactivar este aprendiz? No podrá iniciar sesión hasta que lo reactives.')) return;
    try {
      await Api.aprendices.desactivar(id);
      toast('Aprendiz desactivado.');
      await cargarAprendices();
    } catch (err) {
      toast(err.message ?? 'Error al desactivar.', 'error');
    }
  }

  // ─── Importación CSV ─────────────────────────────────────────────────────

  async function importar(input) {
    const file = input?.files?.[0];
    if (!file) return;

    const label = document.getElementById('btnImportarLabel');
    const textoOrig = label?.querySelector('svg') ? null : label?.textContent;
    if (label) label.style.opacity = '0.6';

    const fd = new FormData();
    fd.append('archivo', file);

    try {
      const resultado = await Api.aprendices.importar(fd);
      mostrarResultadoImportacion(resultado);
      await cargarAprendices();
    } catch (err) {
      toast(err.message ?? 'Error durante la importación.', 'error');
    } finally {
      if (label) label.style.opacity = '1';
      // Limpiar input para permitir re-importar el mismo archivo
      input.value = '';
    }
  }

  function mostrarResultadoImportacion(resultado) {
    const modal = document.getElementById('modalImportBackdrop');
    const body  = document.getElementById('modalImportBody');
    if (!modal || !body) return;

    const exitosos = resultado.exitosos ?? 0;
    const errores  = resultado.errores  ?? [];
    const total    = resultado.total    ?? (exitosos + errores.length);

    let html = `
      <div style="display:flex;gap:var(--sp-4);margin-bottom:var(--sp-5)">
        <div style="flex:1;background:var(--green-light);border-radius:var(--r-md);padding:var(--sp-4);text-align:center">
          <div style="font-size:var(--text-2xl);font-weight:var(--fw-bold);color:var(--green-dark)">${exitosos}</div>
          <div style="font-size:var(--text-xs);color:var(--green-dark)">Importados</div>
        </div>
        <div style="flex:1;background:${errores.length > 0 ? '#FEE2E2' : 'var(--surface-2)'};border-radius:var(--r-md);padding:var(--sp-4);text-align:center">
          <div style="font-size:var(--text-2xl);font-weight:var(--fw-bold);color:${errores.length > 0 ? 'var(--danger)' : 'var(--text-muted)'}">${errores.length}</div>
          <div style="font-size:var(--text-xs);color:${errores.length > 0 ? 'var(--danger)' : 'var(--text-muted)'}">Con errores</div>
        </div>
        <div style="flex:1;background:var(--surface-2);border-radius:var(--r-md);padding:var(--sp-4);text-align:center">
          <div style="font-size:var(--text-2xl);font-weight:var(--fw-bold);color:var(--text-secondary)">${total}</div>
          <div style="font-size:var(--text-xs);color:var(--text-muted)">Total en archivo</div>
        </div>
      </div>`;

    if (exitosos > 0) {
      html += `<p style="font-size:var(--text-sm);color:var(--green-dark);margin-bottom:var(--sp-3)">
        ✅ Los aprendices importados recibirán un enlace para completar su registro con su número de documento en <strong>registro.php</strong>.
      </p>`;
    }

    if (errores.length > 0) {
      html += `<details style="margin-top:var(--sp-3)">
        <summary style="font-size:var(--text-sm);font-weight:var(--fw-semibold);cursor:pointer;margin-bottom:var(--sp-2)">
          Ver errores (${errores.length})
        </summary>
        <div style="overflow-x:auto">
          <table class="table" style="margin-top:var(--sp-2)">
            <thead><tr><th>Fila</th><th>Documento</th><th>Error</th></tr></thead>
            <tbody>
              ${errores.map(e => `<tr>
                <td>${e.fila}</td>
                <td style="font-family:monospace">${esc(e.documento)}</td>
                <td style="font-size:var(--text-xs);color:var(--danger)">${esc(e.error)}</td>
              </tr>`).join('')}
            </tbody>
          </table>
        </div>
      </details>`;
    }

    body.innerHTML = html;
    modal.style.display = 'flex';
  }

  // ─── Pase de trimestre (continuidad) ─────────────────────────────────────
  // Trimestres calendario: T1 ene–mar, T2 abr–jun, T3 jul–sep, T4 oct–dic.
  // Los desmarcados quedan retirados (activo = 0). No se borra ni se duplica nada:
  // sus asistencias del trimestre anterior siguen en el Excel de ese trimestre.

  let continuidadFicha = null;

  function trimestreActual() {
    const hoy = new Date();
    return { num: Math.floor(hoy.getMonth() / 3) + 1, anio: hoy.getFullYear() };
  }

  async function abrirContinuidad() {
    const sel     = document.getElementById('filtroFicha');
    const idFicha = sel?.value;
    if (!idFicha) {
      toast('Primero selecciona una ficha en el filtro "Ficha".', 'warning');
      sel?.focus();
      return;
    }
    const textoFicha = sel.options[sel.selectedIndex]?.textContent ?? '';
    const t = trimestreActual();

    try {
      const data    = await Api.aprendices.listar({ id_ficha: idFicha, estado: 'activo' });
      const activos = (Array.isArray(data) ? data : (data.aprendices ?? []))
        .filter(a => Number(a.activo) === 1);

      if (activos.length === 0) {
        toast('Esta ficha no tiene aprendices activos.', 'warning');
        return;
      }
      activos.sort((a, b) => String(a.apellidos).localeCompare(String(b.apellidos), 'es'));
      continuidadFicha = { id: parseInt(idFicha, 10), texto: textoFicha, total: activos.length, trim: t };

      document.getElementById('continuidadIntro').innerHTML =
        `Ficha <strong>${esc(textoFicha)}</strong>. Marca los aprendices que <strong>continúan</strong> en el `
        + `<strong>Trimestre ${t.num} de ${t.anio}</strong>. Los que desmarques quedarán como retirados; `
        + `su historial de trimestres anteriores no se modifica.`;

      document.getElementById('continuidadLista').innerHTML = activos.map(a => `
        <label style="display:flex;align-items:center;gap:8px;padding:6px 4px;cursor:pointer;border-bottom:1px solid var(--border)">
          <input type="checkbox" class="chk-continuidad" value="${Number(a.id_aprendiz)}" checked
                 onchange="aprendicesGestion.actualizarResumenContinuidad()">
          <span>${esc(a.apellidos)} ${esc(a.nombres)}</span>
          <span style="margin-left:auto;color:var(--text-muted);font-size:var(--text-xs)">${esc(a.numero_documento ?? '')}</span>
        </label>`).join('');

      document.getElementById('continuidadTodos').checked = true;
      actualizarResumenContinuidad();
      document.getElementById('modalContinuidadBackdrop').style.display = 'flex';
    } catch (err) {
      toast(err.message ?? 'No se pudo cargar la lista de aprendices.', 'error');
    }
  }

  function cerrarContinuidad() {
    document.getElementById('modalContinuidadBackdrop').style.display = 'none';
    continuidadFicha = null;
  }

  function marcarTodosContinuidad(marcado) {
    document.querySelectorAll('.chk-continuidad').forEach(c => { c.checked = marcado; });
    actualizarResumenContinuidad();
  }

  function actualizarResumenContinuidad() {
    const total     = document.querySelectorAll('.chk-continuidad').length;
    const marcados  = document.querySelectorAll('.chk-continuidad:checked').length;
    const retirados = total - marcados;
    const el = document.getElementById('continuidadResumen');
    if (el) {
      el.innerHTML = `<strong>${marcados}</strong> continúan · `
        + `<strong style="color:${retirados ? 'var(--danger)' : 'inherit'}">${retirados}</strong> quedarán retirados`;
    }
    const todos = document.getElementById('continuidadTodos');
    if (todos) todos.checked = total > 0 && marcados === total;
  }

  async function confirmarContinuidad() {
    if (!continuidadFicha) return;
    const continuan = [...document.querySelectorAll('.chk-continuidad:checked')].map(c => parseInt(c.value, 10));
    const retirados = continuidadFicha.total - continuan.length;

    if (continuan.length === 0) {
      toast('Selecciona al menos un aprendiz que continúe.', 'warning');
      return;
    }
    const t = continuidadFicha.trim;
    const msg = `¿Confirmar el pase al Trimestre ${t.num} de ${t.anio}?\n\n`
      + `• Continúan: ${continuan.length}\n`
      + `• Quedarán retirados: ${retirados}\n\n`
      + `Las asistencias de trimestres anteriores no se modifican.`;
    if (!confirm(msg)) return;

    const btn = document.getElementById('btnConfirmarContinuidad');
    if (btn) btn.disabled = true;
    try {
      const r = await Api.aprendices.confirmarContinuidad({ id_ficha: continuidadFicha.id, continuan });
      toast(`Listo: ${r.continuan} continúan en ${r.trimestre} y ${r.retirados} quedaron retirados.`);
      cerrarContinuidad();
      await cargarAprendices();
    } catch (err) {
      toast(err.message ?? 'Error al confirmar la continuidad.', 'error');
    } finally {
      if (btn) btn.disabled = false;
    }
  }

  // ─── Helpers ─────────────────────────────────────────────────────────────

  function esc(str) {
    return String(str ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
  }

  function toast(msg, type = 'success') {
    const container = document.getElementById('toast-container');
    if (!container) return;
    const t = document.createElement('div');
    t.className = `toast toast--${type}`;
    t.innerHTML = `<span class="toast__msg">${msg}</span>
      <button class="toast__close" onclick="this.closest('.toast').remove()">×</button>`;
    container.appendChild(t);
    setTimeout(() => { t.classList.add('toast--out'); setTimeout(() => t.remove(), 300); }, 3500);
  }

  document.addEventListener('DOMContentLoaded', () => {
    if (window.ATTENDQR_VIEW === 'aprendices') init();
  });

  return {
    filtrar, buscarLocal, limpiarFiltros, activar, desactivar, importar, sortBy, restablecerContrasena,
    abrirContinuidad, cerrarContinuidad, marcarTodosContinuidad, actualizarResumenContinuidad, confirmarContinuidad,
  };
})();
