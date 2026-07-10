// =============================================================
// AttendQR — JORNADA_JornadaController
// =============================================================
// Funciones públicas expuestas al frontend via google.script.run.
// Replica: Src/Controllers/JornadaController.php
//
// Mapeo de rutas PHP → funciones GAS:
//   GET    /api/jornadas            → jornadaListar(token)
//   GET    /api/jornadas/listar     → jornadaListar(token)
//   GET    /api/jornadas/consultar/{id}  → jornadaConsultar(idJornada, token)
//   POST   /api/jornadas/crear           → jornadaCrear(payload, token)
//   PUT    /api/jornadas/actualizar/{id} → jornadaActualizar(idJornada, datos, token)
//   DELETE /api/jornadas/eliminar/{id}   → jornadaEliminar(idJornada, token)
//
// Todas las funciones devuelven: { success, message, data? }
// =============================================================

// ── jornadaListar ─────────────────────────────────────────────
// token: string
function jornadaListar(token) {
  try {
    AuthService.verificarToken(token);
    var resultado = JornadaService.listar();
    return { success: true, message: 'Jornadas obtenidas correctamente.', data: resultado };
  } catch (e) {
    return { success: false, message: e.message };
  }
}

// ── jornadaConsultar ──────────────────────────────────────────
// idJornada: number
// token: string
function jornadaConsultar(idJornada, token) {
  try {
    AuthService.verificarToken(token);
    var jornada = JornadaService.consultar(idJornada);
    return { success: true, message: 'Jornada encontrada.', data: jornada };
  } catch (e) {
    return { success: false, message: e.message };
  }
}

// ── jornadaCrear ──────────────────────────────────────────────
// payload: { nombre, hora_inicio?, hora_fin? }
// token: string
function jornadaCrear(payload, token) {
  try {
    AuthService.verificarToken(token);
    payload = payload || {};

    if (!payload.nombre) {
      return { success: false, message: 'El campo nombre es obligatorio.' };
    }

    var jornada = JornadaService.crear(
      String(payload.nombre),
      payload.hora_inicio !== undefined ? String(payload.hora_inicio) : null,
      payload.hora_fin    !== undefined ? String(payload.hora_fin)    : null
    );
    return { success: true, message: 'Jornada creada correctamente.', data: jornada };
  } catch (e) {
    return { success: false, message: e.message };
  }
}

// ── jornadaActualizar ─────────────────────────────────────────
// idJornada: number
// datos: { nombre?, hora_inicio?, hora_fin?, minutos_gracia? }
// token: string
function jornadaActualizar(idJornada, datos, token) {
  try {
    AuthService.verificarToken(token);

    if (!datos || Object.keys(datos).length === 0) {
      return { success: false, message: 'No se recibieron datos para actualizar.' };
    }

    var jornada = JornadaService.actualizar(idJornada, datos);
    return { success: true, message: 'Jornada actualizada correctamente.', data: jornada };
  } catch (e) {
    return { success: false, message: e.message };
  }
}

// ── jornadaEliminar ───────────────────────────────────────────
// idJornada: number
// token: string
function jornadaEliminar(idJornada, token) {
  try {
    AuthService.verificarToken(token);
    var resultado = JornadaService.eliminar(idJornada);
    return { success: true, message: resultado.message || 'Jornada eliminada correctamente.', data: {} };
  } catch (e) {
    return { success: false, message: e.message };
  }
}
