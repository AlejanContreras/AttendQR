// =============================================================
// AttendQR — SESION_SesionController
// =============================================================
// Funciones públicas expuestas al frontend via google.script.run.
// Replica: Src/Controllers/SesionController.php
//
// Mapeo de rutas PHP → funciones GAS:
//   POST /api/sesiones/crear              → sesionCrear(payload, token)
//   GET  /api/sesiones/listar             → sesionListar(filtros, token)
//   GET  /api/sesiones/detalle/{id}       → sesionDetalle(idSesion, token)
//   GET  /api/sesiones/activa/{idFicha}   → sesionActiva(idFicha, token)
//   POST /api/sesiones/cerrar/{id}        → sesionCerrar(idSesion, token)
//   GET  /api/sesiones/asistencias/{id}   → sesionAsistencias(idSesion, token)
//   GET  /api/sesiones/estadisticas/{id}  → sesionEstadisticas(idSesion, token)
//
// Todas las funciones devuelven: { success, message, data? }
// =============================================================

// ── sesionCrear ───────────────────────────────────────────────
// payload: {
//   id_ficha, hora_inicio_clase,
//   nombre_materia?,
//   ubicacion_activa?, lat_docente?, lng_docente?, accuracy_docente?
// }
// token: string (docente)
function sesionCrear(payload, token) {
  try {
    var usuario = AuthService.verificarToken(token);
    payload = payload || {};

    if (!payload.id_ficha) {
      return { success: false, message: 'El campo id_ficha es obligatorio.' };
    }

    if (!payload.hora_inicio_clase) {
      return { success: false, message: 'La hora de inicio de la clase es obligatoria.' };
    }

    var horaInicioClase = String(payload.hora_inicio_clase).trim();

    // Validar formato HH:MM o HH:MM:SS
    if (!/^\d{2}:\d{2}(:\d{2})?$/.test(horaInicioClase)) {
      return { success: false, message: 'La hora de inicio debe tener formato HH:MM (ej: 10:00).' };
    }

    // Normalizar a HH:MM:SS
    if (horaInicioClase.length === 5) {
      horaInicioClase += ':00';
    }

    var nombreMateria   = payload.nombre_materia ? String(payload.nombre_materia).trim() : '';
    var ubicacionActiva = !!(payload.ubicacion_activa);
    var latDocente      = payload.lat_docente      !== undefined ? parseFloat(payload.lat_docente)      : null;
    var lngDocente      = payload.lng_docente      !== undefined ? parseFloat(payload.lng_docente)      : null;
    var accuracyDocente = payload.accuracy_docente !== undefined ? parseFloat(payload.accuracy_docente) : null;

    if (ubicacionActiva && (latDocente === null || lngDocente === null)) {
      return {
        success: false,
        message: 'Se activó validación de ubicación pero no se enviaron coordenadas del docente.'
      };
    }

    var sesion = SesionService.crear(
      parseInt(payload.id_ficha, 10),
      horaInicioClase,
      nombreMateria,
      usuario,
      ubicacionActiva,
      latDocente,
      lngDocente,
      accuracyDocente
    );
    return { success: true, message: 'Sesión creada correctamente.', data: sesion };
  } catch (e) {
    return { success: false, message: e.message };
  }
}

// ── sesionListar ──────────────────────────────────────────────
// filtros: { id_ficha?: number, estado?: 'abierta'|'cerrada'|'cancelada' }
// token: string
function sesionListar(filtros, token) {
  try {
    AuthService.verificarToken(token);
    filtros = filtros || {};

    var idFicha = filtros.id_ficha ? parseInt(filtros.id_ficha, 10) : null;
    var estado  = filtros.estado   ? String(filtros.estado)         : null;

    var resultado = SesionService.listar(idFicha, estado);
    return { success: true, message: 'Sesiones obtenidas correctamente.', data: resultado };
  } catch (e) {
    return { success: false, message: e.message };
  }
}

// ── sesionDetalle ─────────────────────────────────────────────
// idSesion: number
// token: string
function sesionDetalle(idSesion, token) {
  try {
    AuthService.verificarToken(token);
    var sesion = SesionService.consultar(idSesion);
    return { success: true, message: 'Sesión encontrada.', data: sesion };
  } catch (e) {
    return { success: false, message: e.message };
  }
}

// ── sesionActiva ──────────────────────────────────────────────
// idFicha: number
// token: string
function sesionActiva(idFicha, token) {
  try {
    AuthService.verificarToken(token);
    var sesion = SesionService.sesionActivaPorFicha(idFicha);
    return { success: true, message: 'Sesión activa encontrada.', data: sesion };
  } catch (e) {
    return { success: false, message: e.message };
  }
}

// ── sesionCerrar ──────────────────────────────────────────────
// idSesion: number
// token: string (docente propietario)
function sesionCerrar(idSesion, token) {
  try {
    var usuario   = AuthService.verificarToken(token);
    var resultado = SesionService.cerrar(idSesion, usuario);
    return { success: true, message: 'Sesión cerrada correctamente.', data: resultado };
  } catch (e) {
    return { success: false, message: e.message };
  }
}

// ── sesionAsistencias ─────────────────────────────────────────
// idSesion: number
// token: string
function sesionAsistencias(idSesion, token) {
  try {
    AuthService.verificarToken(token);
    var resultado = SesionService.asistenciasDeSesion(idSesion);
    return { success: true, message: 'Asistencias de la sesión obtenidas correctamente.', data: resultado };
  } catch (e) {
    return { success: false, message: e.message };
  }
}

// ── sesionEstadisticas ────────────────────────────────────────
// idSesion: number
// token: string
function sesionEstadisticas(idSesion, token) {
  try {
    AuthService.verificarToken(token);
    var resultado = SesionService.estadisticasDeSesion(idSesion);
    return { success: true, message: 'Estadísticas de la sesión obtenidas correctamente.', data: resultado };
  } catch (e) {
    return { success: false, message: e.message };
  }
}
