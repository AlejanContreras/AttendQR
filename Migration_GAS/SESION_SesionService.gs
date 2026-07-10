// =============================================================
// AttendQR — SESION_SesionService
// =============================================================
// Lógica de negocio del ciclo de vida de las sesiones de asistencia.
// Replica: Src/Services/SesionService.php
//
// Dependencias:
//   SesionRepository — acceso a datos de sesiones y tablas relacionadas
//   QrRepository     — crear / invalidarPorSesion tokens QR
//
// Reglas de negocio:
//   PRESENTE  → H a H+5 min
//   RETARDO   → H+6 min a H+20 min
//   Rechazado → H+21 min en adelante
//   límite_retardo_minutos  = 5  (fijo por sesión)
//   duracion_maxima_minutos = 20 (fijo por sesión)
// =============================================================

var SesionService = (function () {

  // ── Crear ─────────────────────────────────────────────────
  // Abre una nueva sesión para una ficha.
  // Reglas:
  //   1. La ficha debe existir y estar activa.
  //   2. El docente autenticado debe ser el propietario.
  //   3. No puede haber otra sesión ABIERTA en la misma ficha hoy.
  //   4. Genera el primer token QR al crear.

  function crear(
    idFicha, horaInicioClase, nombreMateria, usuarioActual,
    ubicacionActiva, latDocente, lngDocente, accuracyDocente
  ) {
    var ficha = SesionRepository.obtenerFichaConJornada(idFicha);

    if (ficha === null) {
      throw new Error('La ficha indicada no existe.');
    }

    if (parseInt(ficha.activa, 10) !== 1) {
      throw new Error('La ficha se encuentra inactiva.');
    }

    if (parseInt(ficha.id_docente, 10) !== parseInt(usuarioActual.id, 10)) {
      throw new Error('No tiene permisos para crear sesiones en esta ficha.');
    }

    var fecha = Utilities.formatDate(new Date(), Session.getScriptTimeZone(), 'yyyy-MM-dd');

    if (SesionRepository.existeAbiertaParaFicha(idFicha, fecha)) {
      throw new Error('Ya existe una sesión abierta para esta ficha. Ciérrala antes de crear una nueva.');
    }

    var limiteRetardoMinutos  = 5;
    var duracionMaximaMinutos = 20;

    var idSesion = SesionRepository.crear(
      idFicha,
      fecha,
      horaInicioClase,
      nombreMateria || null,
      limiteRetardoMinutos,
      duracionMaximaMinutos,
      ubicacionActiva  || false,
      latDocente       !== undefined ? latDocente       : null,
      lngDocente       !== undefined ? lngDocente       : null,
      accuracyDocente  !== undefined ? accuracyDocente  : null
    );

    _generarPrimerToken(idSesion, 30);

    return SesionRepository.obtenerPorId(idSesion) ||
           { id_sesion: idSesion, id_ficha: idFicha, estado_sesion: 'abierta' };
  }

  // ── Consultar ─────────────────────────────────────────────
  // Detalle completo de una sesión por ID.

  function consultar(idSesion) {
    var sesion = SesionRepository.obtenerDetalle(idSesion);
    if (!sesion) throw new Error('Sesión no encontrada.');
    return sesion;
  }

  // ── SesionActivaPorFicha ──────────────────────────────────
  // Sesión actualmente abierta para una ficha.

  function sesionActivaPorFicha(idFicha) {
    var sesion = SesionRepository.obtenerActivaPorFicha(idFicha);
    if (!sesion) throw new Error('No hay sesión abierta para esta ficha.');
    return sesion;
  }

  // ── Listar ────────────────────────────────────────────────
  // Lista sesiones con filtros opcionales de ficha y estado.
  // Cierra vencidas de forma lazy antes de responder.

  function listar(idFicha, estado) {
    var estadosPermitidos = ['abierta', 'cerrada', 'cancelada'];

    if (estado !== null && estado !== undefined &&
        estadosPermitidos.indexOf(estado) === -1) {
      throw new Error(
        "Estado '" + estado + "' no válido. Permitidos: " + estadosPermitidos.join(', ') + '.'
      );
    }

    SesionRepository.cerrarVencidas();

    var sesiones = SesionRepository.listar(idFicha, estado);

    return {
      sesiones: sesiones,
      total   : sesiones.length
    };
  }

  // ── Cerrar ────────────────────────────────────────────────
  // Cierra una sesión abierta e invalida todos sus tokens QR activos.
  // Reglas:
  //   1. La sesión debe existir.
  //   2. El docente debe ser el propietario.
  //   3. La sesión debe estar en estado 'abierta'.

  function cerrar(idSesion, usuarioActual) {
    var sesion = SesionRepository.obtenerPorId(idSesion);

    if (!sesion) {
      throw new Error('Sesión no encontrada.');
    }

    if (parseInt(sesion.id_docente, 10) !== parseInt(usuarioActual.id, 10)) {
      throw new Error('No tiene permisos para cerrar esta sesión.');
    }

    if (String(sesion.estado_sesion) !== 'abierta') {
      throw new Error('La sesión ya no está abierta.');
    }

    QrRepository.invalidarPorSesion(idSesion);

    var horaCierre = Utilities.formatDate(new Date(), Session.getScriptTimeZone(), 'yyyy-MM-dd HH:mm:ss');
    SesionRepository.cerrar(idSesion, horaCierre);

    return {
      id_sesion   : idSesion,
      hora_cierre : horaCierre
    };
  }

  // ── AsistenciasDeSesion ───────────────────────────────────
  // Registros de asistencia de una sesión con datos del aprendiz.

  function asistenciasDeSesion(idSesion) {
    var sesion = SesionRepository.obtenerPorId(idSesion);
    if (!sesion) throw new Error('Sesión no encontrada.');

    var registros = SesionRepository.obtenerAsistenciasDeSesion(idSesion);

    return {
      id_sesion     : idSesion,
      fecha_sesion  : sesion.fecha_sesion,
      estado_sesion : sesion.estado_sesion,
      registros     : registros,
      total         : registros.length
    };
  }

  // ── EstadisticasDeSesion ──────────────────────────────────
  // Resumen estadístico: presentes, retardos, sin registro, porcentaje.

  function estadisticasDeSesion(idSesion) {
    var stats = SesionRepository.obtenerEstadisticasDeSesion(idSesion);

    if (!stats) throw new Error('Sesión no encontrada.');

    var totalAprendices  = parseInt(stats.total_aprendices,  10) || 0;
    var totalRegistrados = parseInt(stats.total_registrados, 10) || 0;
    var presentes        = parseInt(stats.presentes,         10) || 0;
    var retardos         = parseInt(stats.retardos,          10) || 0;
    var ausentesMarcados = parseInt(stats.ausentes_marcados, 10) || 0;
    var excusas          = parseInt(stats.excusas,           10) || 0;
    var sinRegistro      = Math.max(0, totalAprendices - totalRegistrados);

    var pctAsistencia = totalAprendices > 0
      ? Math.round((presentes + retardos) / totalAprendices * 1000) / 10  // redondeo a 1 decimal
      : 0.0;

    return {
      id_sesion          : idSesion,
      fecha_sesion       : stats.fecha_sesion,
      estado_sesion      : stats.estado_sesion,
      hora_inicio_clase  : stats.hora_inicio_clase,
      codigo_ficha       : stats.codigo_ficha,
      nombre_programa    : stats.nombre_programa,
      estadisticas       : {
        total_aprendices      : totalAprendices,
        total_registrados     : totalRegistrados,
        presentes             : presentes,
        retardos              : retardos,
        ausentes_marcados     : ausentesMarcados,
        excusas               : excusas,
        sin_registro          : sinRegistro,
        porcentaje_asistencia : pctAsistencia
      }
    };
  }

  // ── HistorialPorFicha ─────────────────────────────────────
  // Historial de sesiones de una ficha con totales de asistencia.
  // Consumido por fichaHistorial (stub activado al migrar esta Fase).

  function historialPorFicha(idFicha, fechaInicio, fechaFin, estado) {
    var estadosPermitidos = ['abierta', 'cerrada', 'cancelada'];

    if (estado !== null && estado !== undefined &&
        estadosPermitidos.indexOf(estado) === -1) {
      throw new Error(
        "Estado '" + estado + "' no válido. Permitidos: " + estadosPermitidos.join(', ') + '.'
      );
    }

    var sesiones = SesionRepository.obtenerHistorialPorFicha(
      idFicha,
      fechaInicio !== undefined ? fechaInicio : null,
      fechaFin    !== undefined ? fechaFin    : null,
      estado      !== undefined ? estado      : null
    );

    return {
      id_ficha : idFicha,
      sesiones : sesiones,
      total    : sesiones.length,
      filtros  : {
        fecha_inicio : fechaInicio || null,
        fecha_fin    : fechaFin    || null,
        estado       : estado      || null
      }
    };
  }

  // ── Helpers privados ──────────────────────────────────────

  // Genera el primer token QR al crear una sesión.
  // PHP: $token = strtoupper(bin2hex(random_bytes(3))); // 6 hex chars
  function _generarPrimerToken(idSesion, rotacionSegundos) {
    var bytes = Utilities.getSecureRandomBytes(3);
    var hex   = '';
    for (var i = 0; i < bytes.length; i++) {
      var b = (bytes[i] < 0 ? bytes[i] + 256 : bytes[i]).toString(16);
      hex  += (b.length === 1 ? '0' : '') + b;
    }
    var token    = hex.toUpperCase();
    var expiraEn = Utilities.formatDate(
      new Date(new Date().getTime() + rotacionSegundos * 1000),
      Session.getScriptTimeZone(),
      'yyyy-MM-dd HH:mm:ss'
    );
    QrRepository.crear(idSesion, token, expiraEn);
  }

  return {
    crear                : crear,
    consultar            : consultar,
    sesionActivaPorFicha : sesionActivaPorFicha,
    listar               : listar,
    cerrar               : cerrar,
    asistenciasDeSesion  : asistenciasDeSesion,
    estadisticasDeSesion : estadisticasDeSesion,
    historialPorFicha    : historialPorFicha
  };

})();
