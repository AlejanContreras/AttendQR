// =============================================================
// AttendQR — ESTADISTICA_EstadisticaService
// =============================================================
// Lógica de negocio del módulo Estadísticas. Servicio de solo lectura.
// Replica: Src/Services/EstadisticaService.php
//
// Flujo: EstadisticaController → EstadisticaService → [múltiples Repositories]
//
// Dependencias (Repositories ya migrados):
//   AsistenciaRepository — contarHoy, historialAprendiz
//   SesionRepository     — contarActivas, listar, listarPorDocente
//   AprendizRepository   — obtenerPorId, listar
//   FichaRepository      — contarActivas, obtenerPorId
//   DocenteRepository    — contarActivos, obtenerPorId
// =============================================================

var EstadisticaService = (function () {

  // ── resumen ───────────────────────────────────────────────────
  // Genera un resumen general de actividad del sistema.
  // Agrega contadores globales de las principales entidades.
  // Replica: EstadisticaService.resumen()
  //
  // Retorna: { aprendices_activos, docentes_activos, fichas_activas,
  //            sesiones_activas, asistencias_hoy }
  function resumen() {
    return {
      aprendices_activos: _contarAprendicesActivos(),
      docentes_activos  : DocenteRepository.contarActivos(),
      fichas_activas    : FichaRepository.contarActivas(),
      sesiones_activas  : SesionRepository.contarActivas(),
      asistencias_hoy   : AsistenciaRepository.contarHoy()
    };
  }

  // ── dashboard ─────────────────────────────────────────────────
  // Construye los datos del panel principal con filtros opcionales.
  // Replica: EstadisticaService.dashboard()
  //
  // Parámetros:
  //   idDocente — number|null  filtro por docente
  //   idFicha   — number|null  filtro por ficha
  //   trimestre — number|null  filtro informativo (no filtra en repositorio)
  //
  // Retorna: { sesiones_activas, sesiones_cerradas, asistencias_hoy, filtros }
  function dashboard(idDocente, idFicha, trimestre) {
    idDocente = idDocente || null;
    idFicha   = idFicha   || null;
    trimestre = trimestre || null;

    var sesionesActivas  = _listarSesiones(idFicha, idDocente, 'abierta');
    var sesionesCerradas = _listarSesiones(idFicha, idDocente, 'cerrada');

    return {
      sesiones_activas  : sesionesActivas.length,
      sesiones_cerradas : sesionesCerradas.length,
      asistencias_hoy   : AsistenciaRepository.contarHoy(),
      filtros           : {
        id_docente : idDocente,
        id_ficha   : idFicha,
        trimestre  : trimestre
      }
    };
  }

  // ── asistencia ────────────────────────────────────────────────
  // Retorna métricas de asistencia con filtros opcionales.
  // Replica: EstadisticaService.asistencia()
  //
  // Parámetros:
  //   idFicha     — number|null
  //   idDocente   — number|null
  //   fechaInicio — string|null  'Y-m-d'
  //   fechaFin    — string|null  'Y-m-d'
  //
  // Retorna: { total_sesiones_cerradas, asistencias_hoy, filtros }
  function asistencia(idFicha, idDocente, fechaInicio, fechaFin) {
    idFicha   = idFicha   || null;
    idDocente = idDocente || null;
    fechaInicio = fechaInicio || null;
    fechaFin    = fechaFin    || null;

    var sesiones = _listarSesiones(idFicha, idDocente, 'cerrada');

    return {
      total_sesiones_cerradas: sesiones.length,
      asistencias_hoy        : AsistenciaRepository.contarHoy(),
      filtros: {
        id_ficha    : idFicha,
        id_docente  : idDocente,
        fecha_inicio: fechaInicio,
        fecha_fin   : fechaFin
      }
    };
  }

  // ── consultar ─────────────────────────────────────────────────
  // Obtiene estadísticas detalladas de una entidad específica.
  // Replica: EstadisticaService.consultar()
  //
  // Parámetros:
  //   idEntidad   — number
  //   tipoEntidad — 'aprendiz' | 'ficha' | 'docente'
  //
  // Lanza Error si el tipo es inválido (422) o si la entidad no existe (404).
  //
  // Retorna según tipo:
  //   aprendiz → { entidad, historial, total }
  //   ficha    → { entidad, aprendices, total }
  //   docente  → { entidad, sesiones, total }
  function consultar(idEntidad, tipoEntidad) {
    tipoEntidad = tipoEntidad || 'aprendiz';
    var tiposPermitidos = ['aprendiz', 'ficha', 'docente'];

    if (tiposPermitidos.indexOf(tipoEntidad) === -1) {
      throw new Error(
        "Tipo de entidad '" + tipoEntidad + "' no válido. Valores permitidos: " +
        tiposPermitidos.join(', ') + '.'
      );
    }

    if (tipoEntidad === 'aprendiz') {
      var entidadAp = AprendizRepository.obtenerPorId(idEntidad);
      if (!entidadAp) throw new Error('Aprendiz no encontrado.');
      var historial = AsistenciaRepository.historialAprendiz(idEntidad, null, null);
      return {
        entidad  : entidadAp,
        historial: historial,
        total    : historial.length
      };
    }

    if (tipoEntidad === 'ficha') {
      var entidadFic = FichaRepository.obtenerPorId(idEntidad);
      if (!entidadFic) throw new Error('Ficha no encontrada.');
      var aprendices = AprendizRepository.listar(idEntidad, 1);
      return {
        entidad   : entidadFic,
        aprendices: aprendices,
        total     : aprendices.length
      };
    }

    if (tipoEntidad === 'docente') {
      var entidadDoc = DocenteRepository.obtenerPorId(idEntidad);
      if (!entidadDoc) throw new Error('Docente no encontrado.');
      var sesiones = SesionRepository.listarPorDocente(idEntidad);
      return {
        entidad : entidadDoc,
        sesiones: sesiones,
        total   : sesiones.length
      };
    }

    return {};
  }

  // ── Helpers privados ──────────────────────────────────────────

  // Lista sesiones con el filtro más específico disponible.
  // Prioriza idFicha > idDocente > sin filtro.
  // Replica: EstadisticaService._listarSesiones()
  function _listarSesiones(idFicha, idDocente, estado) {
    if (idFicha !== null) {
      return SesionRepository.listar(idFicha, estado);
    }
    if (idDocente !== null) {
      return SesionRepository.listarPorDocente(idDocente, estado);
    }
    return SesionRepository.listar(null, estado);
  }

  // Cuenta el total de aprendices activos del sistema.
  // Replica: EstadisticaService.contarAprendicesActivos()
  function _contarAprendicesActivos() {
    return AprendizRepository.listar(null, 1).length;
  }

  // Calcula porcentaje con protección ante división por cero.
  // Replica: EstadisticaService.calcularPorcentaje()
  function _calcularPorcentaje(asistidas, total) {
    if (total === 0) return 0.0;
    return Math.round((asistidas / total) * 10000) / 100; // round a 2 decimales
  }

  // ── API pública ───────────────────────────────────────────────
  return {
    resumen   : resumen,
    dashboard : dashboard,
    asistencia: asistencia,
    consultar : consultar
  };

})();
