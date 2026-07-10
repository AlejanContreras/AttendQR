// =============================================================
// AttendQR — FICHA_FichaController
// =============================================================
// Funciones públicas expuestas al frontend via google.script.run.
// Replica: Src/Controllers/FichaController.php
//
// Mapeo de rutas PHP → funciones GAS:
//   GET    /api/fichas/listar              → fichaListar(filtros, token)
//   GET    /api/fichas/consultar/{id}      → fichaConsultar(idFicha, token)
//   GET    /api/fichas/historial/{id}      → fichaHistorial(idFicha, filtros, token)  [pendiente SESION]
//   POST   /api/fichas/crear               → fichaCrear(payload, token)
//   PUT    /api/fichas/actualizar/{id}     → fichaActualizar(idFicha, datos, token)
//   DELETE /api/fichas/eliminar/{id}       → fichaEliminar(idFicha, token)
//
// Reglas de acceso (ownership):
//   - Solo el docente dueño de la ficha puede editarla o eliminarla.
//   - Al crear, id_docente se extrae del token (no del payload).
//   - Las consultas de listado son accesibles a cualquier sesión válida.
//
// Todas las funciones devuelven: { success, message, data? }
// =============================================================

// ── fichaListar ───────────────────────────────────────────────
// filtros: { nombre_programa?, estado?, id_jornada?, id_docente? }
// token: string
function fichaListar(filtros, token) {
  try {
    AuthService.verificarToken(token);
    filtros = filtros || {};
    var resultado = FichaService.listar(
      filtros.nombre_programa || null,
      filtros.estado          || null,
      filtros.id_jornada      ? parseInt(filtros.id_jornada, 10)  : null,
      filtros.id_docente      ? parseInt(filtros.id_docente, 10)  : null
    );
    return { success: true, message: 'Fichas obtenidas correctamente.', data: resultado };
  } catch (e) {
    return { success: false, message: e.message };
  }
}

// ── fichaConsultar ────────────────────────────────────────────
// idFicha: number
// token: string
function fichaConsultar(idFicha, token) {
  try {
    AuthService.verificarToken(token);
    var ficha = FichaService.consultar(idFicha);
    return { success: true, message: 'Ficha encontrada.', data: ficha };
  } catch (e) {
    return { success: false, message: e.message };
  }
}

// ── fichaHistorial ────────────────────────────────────────────
// Historial de sesiones de una ficha con totales de asistencia.
// Delega a SesionService.historialPorFicha (pendiente de migración).
// idFicha: number
// filtros: { fecha_inicio?, fecha_fin?, estado? }
// token: string
function fichaHistorial(idFicha, filtros, token) {
  try {
    AuthService.verificarToken(token);

    // Verificar que la ficha existe
    FichaService.consultar(idFicha);

    filtros = filtros || {};
    var resultado = SesionService.historialPorFicha(
      idFicha,
      filtros.fecha_inicio || null,
      filtros.fecha_fin    || null,
      filtros.estado       || null
    );
    return { success: true, message: 'Historial de la ficha obtenido correctamente.', data: resultado };
  } catch (e) {
    return { success: false, message: e.message };
  }
}

// ── fichaCrear ────────────────────────────────────────────────
// payload: { codigo_ficha, nombre_programa, id_jornada, nombre_materia? }
// token: string  (id_docente se extrae del token, no del payload)
function fichaCrear(payload, token) {
  try {
    var usuario = AuthService.verificarToken(token);
    payload = payload || {};

    var requeridos = ['codigo_ficha', 'nombre_programa', 'id_jornada'];
    for (var i = 0; i < requeridos.length; i++) {
      if (!payload[requeridos[i]] && payload[requeridos[i]] !== 0) {
        return { success: false, message: "El campo '" + requeridos[i] + "' es obligatorio." };
      }
    }

    // id_docente proviene de la sesión (igual que en PHP: $_SESSION['usuario']['id'])
    var idDocente = usuario.id;
    if (!idDocente) {
      return { success: false, message: 'No se pudo identificar al docente de la sesión.' };
    }

    var nombreMateria = (payload.nombre_materia && payload.nombre_materia !== '')
                        ? String(payload.nombre_materia)
                        : null;

    var idTrimestre = payload.id_trimestre ? parseInt(payload.id_trimestre, 10) : null;

    var ficha = FichaService.crear(
      String(payload.codigo_ficha),
      String(payload.nombre_programa),
      parseInt(payload.id_jornada, 10),
      idDocente,
      nombreMateria,
      idTrimestre
    );
    return { success: true, message: 'Clase creada correctamente.', data: ficha };
  } catch (e) {
    return { success: false, message: e.message };
  }
}

// ── fichaActualizar ───────────────────────────────────────────
// idFicha: number
// datos: campos a actualizar (parcial)
// token: string  — se verifica que el docente sea dueño de la ficha
function fichaActualizar(idFicha, datos, token) {
  try {
    var usuario = AuthService.verificarToken(token);

    if (!datos || Object.keys(datos).length === 0) {
      return { success: false, message: 'No se recibieron datos para actualizar.' };
    }

    // Verificar que la ficha existe y que el docente es el dueño
    var fichaActual = FichaService.consultar(idFicha);
    if (fichaActual.id_docente != usuario.id) {
      return { success: false, message: 'No tienes permiso para editar esta clase.' };
    }

    var ficha = FichaService.actualizar(idFicha, datos);
    return { success: true, message: 'Clase actualizada correctamente.', data: ficha };
  } catch (e) {
    return { success: false, message: e.message };
  }
}

// ── fichaEliminar ─────────────────────────────────────────────
// idFicha: number
// token: string  — se verifica que el docente sea dueño de la ficha
function fichaEliminar(idFicha, token) {
  try {
    var usuario = AuthService.verificarToken(token);

    // Verificar que la ficha existe y que el docente es el dueño
    var fichaActual = FichaService.consultar(idFicha);
    if (fichaActual.id_docente != usuario.id) {
      return { success: false, message: 'No tienes permiso para eliminar esta clase.' };
    }

    var resultado = FichaService.eliminar(idFicha);
    return { success: true, message: resultado.message || 'Clase eliminada correctamente.', data: {} };
  } catch (e) {
    return { success: false, message: e.message };
  }
}
