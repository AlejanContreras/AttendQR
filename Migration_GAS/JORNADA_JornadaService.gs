// =============================================================
// AttendQR — JORNADA_JornadaService
// =============================================================
// Lógica de negocio del módulo Jornada.
// Replica: Src/Services/JornadaService.php
//
// Dependencias:
//   JornadaRepository — acceso a datos de jornadas
//   FichaRepository   — contarActivasPorJornada() para validación de eliminación
// =============================================================

var JornadaService = (function () {

  // ─── Consulta ────────────────────────────────────────────

  function consultar(idJornada) {
    var jornada = JornadaRepository.obtenerPorId(idJornada);
    if (!jornada) throw new Error('Jornada no encontrada.');
    return jornada;
  }

  // ─── Listar ───────────────────────────────────────────────
  // La tabla jornadas es de referencia — sin filtro de estado.

  function listar() {
    var jornadas = JornadaRepository.listar();
    return {
      jornadas: jornadas,
      total   : jornadas.length
    };
  }

  // ─── Crear ────────────────────────────────────────────────

  function crear(nombre, horaInicio, horaFin) {
    nombre = String(nombre).trim();

    if (nombre === '') {
      throw new Error('El nombre de la jornada no puede estar vacío.');
    }

    if (JornadaRepository.existeNombre(nombre)) {
      throw new Error('Ya existe una jornada con ese nombre.');
    }

    if (horaInicio !== null && horaInicio !== undefined &&
        horaFin    !== null && horaFin    !== undefined) {
      if (!_esHorarioCoherente(String(horaInicio), String(horaFin))) {
        throw new Error('La hora de fin debe ser posterior a la hora de inicio.');
      }
    }

    var id      = JornadaRepository.crear(nombre, horaInicio || null, horaFin || null);
    var jornada = JornadaRepository.obtenerPorId(id);

    return jornada || {
      id_jornada    : id,
      nombre        : nombre,
      hora_inicio   : horaInicio   || null,
      hora_fin      : horaFin      || null,
      minutos_gracia: 5
    };
  }

  // ─── Actualizar ───────────────────────────────────────────

  function actualizar(idJornada, datos) {
    var jornada = JornadaRepository.obtenerPorId(idJornada);
    if (!jornada) throw new Error('Jornada no encontrada.');

    if (datos.hasOwnProperty('nombre')) {
      datos.nombre = String(datos.nombre).trim();

      if (JornadaRepository.existeNombre(datos.nombre, idJornada)) {
        throw new Error('Ya existe una jornada con ese nombre.');
      }
    }

    // Validar coherencia si ambos horarios vienen en la petición
    if (datos.hasOwnProperty('hora_inicio') && datos.hasOwnProperty('hora_fin')) {
      if (!_esHorarioCoherente(String(datos.hora_inicio), String(datos.hora_fin))) {
        throw new Error('La hora de fin debe ser posterior a la hora de inicio.');
      }
    }

    JornadaRepository.actualizar(idJornada, datos);

    return JornadaRepository.obtenerPorId(idJornada) || jornada;
  }

  // ─── Eliminar ─────────────────────────────────────────────

  function eliminar(idJornada) {
    var jornada = JornadaRepository.obtenerPorId(idJornada);
    if (!jornada) throw new Error('Jornada no encontrada.');

    if (FichaRepository.contarActivasPorJornada(idJornada) > 0) {
      throw new Error('La jornada tiene fichas activas. Desvincúlelas antes de eliminarla.');
    }

    JornadaRepository.eliminar(idJornada);

    return { success: true, message: 'Jornada eliminada correctamente.' };
  }

  // ─── Helpers privados ─────────────────────────────────────

  // Verifica que hora_fin > hora_inicio. Retorna true si alguna hora es inválida
  // (misma lógica permisiva del PHP original).
  function _esHorarioCoherente(horaInicio, horaFin) {
    var inicio = _horaAMinutos(horaInicio);
    var fin    = _horaAMinutos(horaFin);
    if (inicio === null || fin === null) return true; // formato inválido → no bloquear
    return fin > inicio;
  }

  // Convierte 'HH:MM' a minutos desde medianoche. Null si el formato es inválido.
  function _horaAMinutos(hora) {
    if (!hora) return null;
    var partes = String(hora).trim().split(':');
    if (partes.length !== 2) return null;
    var h = parseInt(partes[0], 10);
    var m = parseInt(partes[1], 10);
    if (isNaN(h) || isNaN(m) || h < 0 || h > 23 || m < 0 || m > 59) return null;
    return h * 60 + m;
  }

  return {
    consultar : consultar,
    listar    : listar,
    crear     : crear,
    actualizar: actualizar,
    eliminar  : eliminar
  };

})();
