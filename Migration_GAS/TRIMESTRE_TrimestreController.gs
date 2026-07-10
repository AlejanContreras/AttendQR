// =============================================================
// AttendQR — TRIMESTRE_TrimestreController
// =============================================================
// Funciones públicas expuestas al frontend via google.script.run.
// Replica: Src/Controllers/TrimestreController.php
//
// Mapeo de rutas PHP → funciones GAS:
//   GET    /api/trimestres              → trimestreListar(filtros, token)
//   GET    /api/trimestres/listar       → trimestreListar(filtros, token)
//   GET    /api/trimestres/consultar/{id}  → trimestreConsultar(idTrimestre, token)
//   POST   /api/trimestres/crear           → trimestreCrear(payload, token)
//   PUT    /api/trimestres/actualizar/{id} → trimestreActualizar(idTrimestre, datos, token)
//   DELETE /api/trimestres/eliminar/{id}   → trimestreEliminar(idTrimestre, token)
//
// Todas las funciones devuelven: { success, message, data? }
// =============================================================

// ── trimestreListar ───────────────────────────────────────────
// filtros: { anio?: number, estado?: 'activo'|'cerrado' }
// token: string
function trimestreListar(filtros, token) {
  try {
    AuthService.verificarToken(token);
    filtros = filtros || {};

    var anio   = filtros.anio   ? parseInt(filtros.anio, 10) : null;
    var estado = filtros.estado ? String(filtros.estado)     : null;

    var resultado = TrimestreService.listar(anio, estado);
    return { success: true, message: 'Trimestres obtenidos correctamente.', data: resultado };
  } catch (e) {
    return { success: false, message: e.message };
  }
}

// ── trimestreConsultar ────────────────────────────────────────
// idTrimestre: number
// token: string
function trimestreConsultar(idTrimestre, token) {
  try {
    AuthService.verificarToken(token);
    var trimestre = TrimestreService.consultar(idTrimestre);
    return { success: true, message: 'Trimestre encontrado.', data: trimestre };
  } catch (e) {
    return { success: false, message: e.message };
  }
}

// ── trimestreCrear ────────────────────────────────────────────
// payload: { nombre, fecha_inicio, fecha_fin }
// token: string
function trimestreCrear(payload, token) {
  try {
    AuthService.verificarToken(token);
    payload = payload || {};

    var requeridos = ['nombre', 'fecha_inicio', 'fecha_fin'];
    for (var i = 0; i < requeridos.length; i++) {
      if (!payload[requeridos[i]]) {
        return { success: false, message: "El campo '" + requeridos[i] + "' es obligatorio." };
      }
    }

    var trimestre = TrimestreService.crear(
      String(payload.nombre),
      String(payload.fecha_inicio),
      String(payload.fecha_fin)
    );
    return { success: true, message: 'Trimestre creado correctamente.', data: trimestre };
  } catch (e) {
    return { success: false, message: e.message };
  }
}

// ── trimestreActualizar ───────────────────────────────────────
// idTrimestre: number
// datos: { nombre?, fecha_inicio?, fecha_fin?, activo? }
// token: string
function trimestreActualizar(idTrimestre, datos, token) {
  try {
    AuthService.verificarToken(token);

    if (!datos || Object.keys(datos).length === 0) {
      return { success: false, message: 'No se recibieron datos para actualizar.' };
    }

    var trimestre = TrimestreService.actualizar(idTrimestre, datos);
    return { success: true, message: 'Trimestre actualizado correctamente.', data: trimestre };
  } catch (e) {
    return { success: false, message: e.message };
  }
}

// ── trimestreEliminar ─────────────────────────────────────────
// idTrimestre: number
// token: string
function trimestreEliminar(idTrimestre, token) {
  try {
    AuthService.verificarToken(token);
    var resultado = TrimestreService.eliminar(idTrimestre);
    return { success: true, message: resultado.message || 'Trimestre eliminado correctamente.', data: {} };
  } catch (e) {
    return { success: false, message: e.message };
  }
}
