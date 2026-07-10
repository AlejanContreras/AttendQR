// =============================================================
// AttendQR — ASISTENCIA_AsistenciaController
// =============================================================
// Funciones públicas expuestas al frontend via google.script.run.
// Replica: Src/Controllers/AsistenciaController.php
//
// Mapeo de rutas PHP → funciones GAS:
//   POST   /api/asistencias/registrar         → asistenciaRegistrar(payload, token)
//   GET    /api/asistencias/consultar/{id}    → asistenciaConsultar(idAsistencia, token)
//   GET    /api/asistencias/historial/{idAp}  → asistenciaHistorial(idAprendiz, filtros, token)
//   POST   /api/asistencias/validar           → asistenciaValidar(payload, token)
//   PUT    /api/asistencias/estado/{id}       → asistenciaCambiarEstado(idAsistencia, datos, token)
//   GET    /api/asistencias/exportar          → asistenciaExportar(filtros, token)
//   DELETE /api/asistencias/eliminar/{id}     → asistenciaEliminar(idAsistencia, token)
//
// Reglas de acceso:
//   - registrar: solo rol 'aprendiz'
//   - historial: aprendiz solo ve el suyo; docente puede ver cualquiera
//   - exportar: docente → .xlsx (base64), aprendiz → CSV con BOM (base64)
//   - cambiarEstado: solo docente (ausente↔excusa)
//   - eliminar: docente o admin
//
// Respuesta en caso de error con código HTTP embebido en mensaje:
//   El service lanza Error('|409|...'), '|428|...', '|451|...', '|422|...'
//   El controller los detecta y los retorna con campo 'code' en la respuesta.
//
// Todas las funciones devuelven: { success, message, data?, code? }
// =============================================================

// ── asistenciaRegistrar ───────────────────────────────────────
// Solo aprendices pueden registrar su propia asistencia.
// payload: { token_valor, latitud?, longitud?, accuracy? }
// token: string (sesión activa)
function asistenciaRegistrar(payload, token) {
  try {
    var usuario = AuthService.verificarToken(token);
    if (usuario.rol !== 'aprendiz') {
      return { success: false, message: 'Solo los aprendices pueden registrar asistencia.' };
    }

    payload = payload || {};
    if (!payload.token_valor) {
      return { success: false, message: 'El campo token_valor es obligatorio.' };
    }

    var latitud  = (payload.latitud  !== undefined && payload.latitud  !== null)
                   ? parseFloat(payload.latitud)  : null;
    var longitud = (payload.longitud !== undefined && payload.longitud !== null)
                   ? parseFloat(payload.longitud) : null;
    var accuracy = (payload.accuracy !== undefined && payload.accuracy !== null)
                   ? parseFloat(payload.accuracy) : null;

    var resultado = AsistenciaService.registrarPorQr(
      usuario.id,
      String(payload.token_valor),
      latitud,
      longitud,
      accuracy
    );
    return { success: true, message: 'Asistencia registrada correctamente.', data: resultado };
  } catch (e) {
    return _responderError(e);
  }
}

// ── asistenciaConsultar ───────────────────────────────────────
// idAsistencia: number
// token: string
function asistenciaConsultar(idAsistencia, token) {
  try {
    var usuario    = AuthService.verificarToken(token);
    var asistencia = AsistenciaService.consultar(parseInt(idAsistencia, 10), usuario);
    return { success: true, message: 'Asistencia encontrada.', data: asistencia };
  } catch (e) {
    return _responderError(e);
  }
}

// ── asistenciaHistorial ───────────────────────────────────────
// idAprendiz: number
// filtros: { fecha_inicio?, fecha_fin? }
// token: string
function asistenciaHistorial(idAprendiz, filtros, token) {
  try {
    var usuario = AuthService.verificarToken(token);
    filtros = filtros || {};

    // Aprendiz solo puede ver su propio historial
    if (usuario.rol === 'aprendiz' && parseInt(idAprendiz, 10) !== usuario.id) {
      return { success: false, message: 'No tienes permiso para consultar este historial.' };
    }

    var resultado = AsistenciaService.historial(
      parseInt(idAprendiz, 10),
      filtros.fecha_inicio || null,
      filtros.fecha_fin    || null
    );
    return { success: true, message: 'Historial obtenido correctamente.', data: resultado };
  } catch (e) {
    return _responderError(e);
  }
}

// ── asistenciaValidar ─────────────────────────────────────────
// Valida un token QR sin registrar asistencia.
// payload: { token_valor }
// token: string
function asistenciaValidar(payload, token) {
  try {
    var usuario = AuthService.verificarToken(token);
    payload = payload || {};
    if (!payload.token_valor) {
      return { success: false, message: 'El campo token_valor es obligatorio.' };
    }
    var resultado = AsistenciaService.validar(String(payload.token_valor), usuario);
    return { success: true, message: 'Token QR válido.', data: resultado };
  } catch (e) {
    return _responderError(e);
  }
}

// ── asistenciaCambiarEstado ───────────────────────────────────
// Solo docentes. Cambia estado ausente↔excusa.
// idAsistencia: number
// datos: { estado, observacion? }
// token: string
function asistenciaCambiarEstado(idAsistencia, datos, token) {
  try {
    var usuario = AuthService.verificarToken(token);
    datos = datos || {};
    if (!datos.estado) {
      return { success: false, message: 'El campo estado es obligatorio.' };
    }
    var resultado = AsistenciaService.cambiarEstado(
      parseInt(idAsistencia, 10),
      String(datos.estado),
      datos.observacion || null,
      usuario
    );
    return { success: true, message: 'Estado actualizado correctamente.', data: resultado };
  } catch (e) {
    return _responderError(e);
  }
}

// ── asistenciaExportar ────────────────────────────────────────
// Exporta asistencias. La respuesta incluye base64 del archivo y metadatos.
//   docente → .xlsx multi-hoja formato SENA
//   aprendiz → .csv con BOM UTF-8, separador ';'
// filtros: { fecha_inicio?, fecha_fin?, id_ficha?, id_aprendiz? }
// token: string
function asistenciaExportar(filtros, token) {
  try {
    var usuario = AuthService.verificarToken(token);
    filtros = filtros || {};

    var base64, mime, filename;

    if (usuario.rol === 'docente' || usuario.rol === 'admin') {
      base64   = AsistenciaService.generarReporteExcel(filtros, usuario);
      mime     = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
      filename = 'reporte_asistencia.xlsx';
    } else {
      // aprendiz
      base64   = AsistenciaService.generarReporteCSV(filtros, usuario);
      mime     = 'text/csv;charset=utf-8';
      filename = 'mi_historial.csv';
    }

    return {
      success : true,
      message : 'Reporte generado correctamente.',
      data    : { base64: base64, mime: mime, filename: filename }
    };
  } catch (e) {
    return _responderError(e);
  }
}

// ── asistenciaEliminar ────────────────────────────────────────
// Solo docente o admin.
// idAsistencia: number
// token: string
function asistenciaEliminar(idAsistencia, token) {
  try {
    var usuario = AuthService.verificarToken(token);
    var resultado = AsistenciaService.eliminar(parseInt(idAsistencia, 10), usuario);
    return { success: true, message: 'Asistencia eliminada correctamente.', data: resultado };
  } catch (e) {
    return _responderError(e);
  }
}

// ── Helper: traducir código HTTP embebido en mensaje ──────────
// AsistenciaService lanza Error('|409|Mensaje') para errores con código.
// Replica el comportamiento de HTTP 409/428/451/422 del PHP.
function _responderError(e) {
  var msg = e.message || 'Error desconocido.';
  var code;
  var match = msg.match(/^\|(\d{3})\|(.*)/);
  if (match) {
    code = parseInt(match[1], 10);
    msg  = match[2];
    if (code === 428) {
      return { success: false, message: msg, code: 428, data: { geo_required: true } };
    }
    return { success: false, message: msg, code: code };
  }
  return { success: false, message: msg };
}
