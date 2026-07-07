/**
 * AttendQR — Perfil (Fase 2: datos reales desde API)
 */
const perfil = (() => {

  let datosOriginales = null; // cache de datos del backend

  // ─── Init ───────────────────────────────────────────────────────────

  async function init() {
    const usuario = window.ATTENDQR_USER;
    if (!usuario) return;

    try {
      const datos = usuario.rol === 'aprendiz'
        ? await Api.aprendices.consultar(usuario.id)
        : await Api.docentes.consultar(usuario.id);

      datosOriginales = datos;
      rellenarFormulario(datos, usuario.rol);
      await cargarEstadisticas(usuario);
    } catch (err) {
      AttendQR.toast.error('No se pudo cargar el perfil: ' + err.message);
    }
  }

  function rellenarFormulario(datos, rol) {
    const nombreCompleto = datos.nombre ?? datos.nombre_completo
      ?? [datos.nombres, datos.apellidos].filter(Boolean).join(' ')
      ?? '';
    setVal('#perfilNombre',    nombreCompleto);
    setVal('#perfilEmail',     datos.correo ?? '');
    setVal('#perfilDoc',       datos.numero_documento ?? datos.documento ?? '');
    setVal('#perfilRolInput',  rol === 'aprendiz' ? 'Aprendiz' : 'Docente / Instructor');

    // Ficha info (solo aprendiz)
    if (rol === 'aprendiz' && (datos.codigo_ficha || datos.nombre_programa)) {
      const card = document.getElementById('perfilFichaCard');
      if (card) card.style.display = '';
      setTxt('#perfilFichaCodigo',   datos.codigo_ficha    ?? '—');
      setTxt('#perfilFichaPrograma', datos.nombre_programa ?? '—');
    }

    // Card lateral
    const nombre = nombreCompleto || '—';
    const iniciales = nombre !== '—' ? nombre.split(' ').slice(0,2).map(w => w[0]).join('').toUpperCase() : '?';

    setTxt('#perfilNombreCard', nombre);
    setTxt('#perfilRolCard',    rol === 'aprendiz' ? 'Aprendiz — SENA' : 'Docente — SENA');
    setTxt('#perfilAvatar',     iniciales);
  }

  async function cargarEstadisticas(usuario) {
    try {
      const stats = await Api.estadisticas.dashboard(
        usuario.rol === 'docente'
          ? { id_docente: usuario.id }
          : {}
      );

      if (usuario.rol === 'docente') {
        setTxt('#statPerfilSesiones',  stats.sesiones_totales  ?? stats.sesiones_activas ?? '—');
        setTxt('#statPerfilFichas',    stats.fichas_activas    ?? '—');
        setTxt('#statPerfilAprendices', stats.total_aprendices ?? '—');
        setTxt('#statPerfilPct',       stats.porcentaje_asistencia != null
          ? Math.round(stats.porcentaje_asistencia) + '%' : '—');
      } else {
        // Para aprendiz: cargar historial propio
        // asistencias/historial devuelve { registros, resumen: { presentes, retardos, ausentes }, total }
        const data     = await Api.asistencias.historial(usuario.id);
        const resumen  = data.resumen  ?? {};
        const total    = data.total    ?? 0;
        const presente = resumen.presentes ?? 0;
        const retardo  = resumen.retardos  ?? 0;
        const pct      = total > 0 ? Math.round(((presente + retardo) / total) * 100) : 0;

        setTxt('#statPerfilSesiones',   total);
        setTxt('#statPerfilFichas',     presente);
        setTxt('#statPerfilAprendices', retardo);
        setTxt('#statPerfilPct',        pct + '%');
        setTxt('#statPerfilLabel2',     'Presentes');
        setTxt('#statPerfilLabel3',     'Tardanzas');
      }
    } catch { /* estadísticas opcionales */ }
  }

  // ─── Guardar perfil ──────────────────────────────────────────────

  async function guardar(e) {
    e.preventDefault();
    const usuario = window.ATTENDQR_USER;
    if (!usuario) return;

    const nombre = document.getElementById('perfilNombre')?.value.trim();
    const correo = document.getElementById('perfilEmail')?.value.trim();

    if (!nombre) {
      AttendQR.toast.warning('El nombre es obligatorio.');
      return;
    }
    if (usuario.rol !== 'aprendiz' && !correo) {
      AttendQR.toast.warning('El correo es obligatorio.');
      return;
    }

    const btn  = document.getElementById('btnGuardarPerfil');
    const orig = btn?.innerHTML;
    if (btn) { btn.disabled = true; btn.textContent = 'Guardando...'; }

    try {
      let body;
      if (usuario.rol === 'aprendiz') {
        // Aprendices: nombres + apellidos (no correo ni telefono)
        const partes    = nombre.split(/\s+/).filter(Boolean);
        const apellidos = partes.length > 1 ? partes.slice(-2).join(' ') : '';
        const nombres   = partes.length > 1 ? partes.slice(0, -2).join(' ') || partes[0] : nombre;
        body = { nombres, apellidos };
        await Api.aprendices.actualizar(usuario.id, body);
      } else {
        // Docentes: dividir nombre completo en nombres + apellidos (igual que aprendiz)
        const partes    = nombre.split(/\s+/).filter(Boolean);
        const apellidos = partes.length > 1 ? partes.slice(-2).join(' ') : '';
        const nombres   = partes.length > 1 ? partes.slice(0, -2).join(' ') || partes[0] : nombre;
        body = { nombres, apellidos, correo };
        await Api.docentes.actualizar(usuario.id, body);
      }

      // Actualizar card lateral + sidebar + topbar + estado JS global
      setTxt('#perfilNombreCard', nombre);
      const iniciales = nombre.split(' ').slice(0,2).map(w => w[0]).join('').toUpperCase();
      setTxt('#perfilAvatar', iniciales);
      document.querySelectorAll('[data-usuario-nombre]').forEach(el => { el.textContent = nombre; });
      document.querySelectorAll('[data-usuario-iniciales]').forEach(el => { el.textContent = iniciales; });
      if (window.ATTENDQR_USER) window.ATTENDQR_USER.nombre = nombre;
      auth.setUsuario({ ...auth.getUsuario(), nombre });

      AttendQR.toast.success('Perfil actualizado correctamente.');
    } catch (err) {
      AttendQR.toast.error('Error al guardar: ' + err.message);
    } finally {
      if (btn) { btn.disabled = false; btn.innerHTML = orig; }
    }
  }

  // ─── Cambiar contraseña ──────────────────────────────────────────

  async function cambiarPassword(e) {
    e.preventDefault();
    const actual   = document.getElementById('passActual')?.value;
    const nueva    = document.getElementById('passNueva')?.value;
    const confirm  = document.getElementById('passConfirm')?.value;

    if (!actual || !nueva || !confirm) {
      AttendQR.toast.warning('Completa todos los campos de contraseña.');
      return;
    }
    if (nueva !== confirm) {
      AttendQR.toast.error('Las contraseñas nuevas no coinciden.');
      return;
    }
    if (nueva.length < 8) {
      AttendQR.toast.warning('La contraseña debe tener al menos 8 caracteres.');
      return;
    }

    const usuario = window.ATTENDQR_USER;
    const btn = e.target.querySelector('button[type=submit]');
    if (btn) btn.disabled = true;

    try {
      // Reutilizar endpoint de actualizar con campo password
      const body = { password_actual: actual, password_nueva: nueva };
      if (usuario?.rol === 'aprendiz') {
        await Api.aprendices.actualizar(usuario.id, body);
      } else {
        await Api.docentes.actualizar(usuario?.id, body);
      }
      e.target.reset();
      resetStrength();
      AttendQR.toast.success('Contraseña actualizada correctamente.');
    } catch (err) {
      AttendQR.toast.error(err.message ?? 'Error al actualizar la contraseña.');
    } finally {
      if (btn) btn.disabled = false;
    }
  }

  // ─── Evaluador de fortaleza ──────────────────────────────────────

  function evalPassword(val) {
    const bars  = [1, 2, 3, 4].map(i => document.getElementById(`pbar${i}`));
    const label = document.getElementById('passLabel');
    if (!bars[0] || !label) return;

    let score = 0;
    if (val.length >= 8)            score++;
    if (/[A-Z]/.test(val))         score++;
    if (/[0-9]/.test(val))         score++;
    if (/[^A-Za-z0-9]/.test(val))  score++;

    const colors = ['#EF4444', '#F97316', '#F59E0B', '#22C55E'];
    const labels = ['Muy débil', 'Débil', 'Regular', 'Fuerte'];

    bars.forEach((bar, i) => {
      bar.style.background = i < score ? colors[score - 1] : 'var(--border)';
    });
    label.textContent = score > 0 ? labels[score - 1] : '—';
    label.style.color = score > 0 ? colors[score - 1] : 'var(--text-muted)';
  }

  function resetStrength() {
    [1, 2, 3, 4].forEach(i => {
      const bar = document.getElementById(`pbar${i}`);
      if (bar) bar.style.background = 'var(--border)';
    });
    const label = document.getElementById('passLabel');
    if (label) { label.textContent = '—'; label.style.color = 'var(--text-muted)'; }
  }

  // ─── Helpers ────────────────────────────────────────────────────

  function setVal(sel, val) {
    const el = document.querySelector(sel);
    if (el) el.value = val ?? '';
  }

  function setTxt(sel, val) {
    const el = document.querySelector(sel);
    if (el) el.textContent = val ?? '—';
  }

  document.addEventListener('DOMContentLoaded', () => {
    if (window.ATTENDQR_VIEW === 'perfil') init();
  });

  return { guardar, cambiarPassword, evalPassword };
})();
