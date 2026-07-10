// =============================================================
// AttendQR — TRIMESTRE_TrimestreService
// =============================================================
// Lógica de negocio del módulo Trimestre.
// Replica: Src/Services/TrimestreService.php
//
// Dependencias:
//   TrimestreRepository — CRUD de trimestres
//   TrimestreRepository.contarPorTrimestre() — guard de eliminación
//     (inline helper, equivalente a SesionRepository.contarPorTrimestre)
// =============================================================

var TrimestreService = (function () {

  // ─── Consulta ────────────────────────────────────────────

  function consultar(idTrimestre) {
    var trimestre = TrimestreRepository.obtenerPorId(idTrimestre);
    if (!trimestre) throw new Error('Trimestre no encontrado.');
    return trimestre;
  }

  // ─── Listar ───────────────────────────────────────────────
  // estado: 'activo' | 'cerrado' | null

  function listar(anio, estado) {
    var activo = null;
    if (estado === 'activo')  activo = 1;
    if (estado === 'cerrado') activo = 0;

    var trimestres = TrimestreRepository.listar(
      anio   !== undefined ? anio   : null,
      activo !== null      ? activo : null
    );
    return {
      trimestres: trimestres,
      total     : trimestres.length
    };
  }

  // ─── Crear ────────────────────────────────────────────────
  // Reglas:
  //   1. nombre no vacío
  //   2. fechas formato Y-m-d válido
  //   3. fecha_fin > fecha_inicio
  //   4. nombre único
  //   5. fechas sin solapamiento con trimestres existentes

  function crear(nombre, fechaInicio, fechaFin) {
    nombre      = String(nombre).trim();
    fechaInicio = String(fechaInicio).trim();
    fechaFin    = String(fechaFin).trim();

    if (nombre === '') {
      throw new Error('El nombre del trimestre no puede estar vacío.');
    }

    if (!_esFechaValida(fechaInicio)) {
      throw new Error("Fecha de inicio '" + fechaInicio + "' no tiene el formato Y-m-d.");
    }

    if (!_esFechaValida(fechaFin)) {
      throw new Error("Fecha de fin '" + fechaFin + "' no tiene el formato Y-m-d.");
    }

    if (!_esPeriodoCoherente(fechaInicio, fechaFin)) {
      throw new Error('La fecha de fin debe ser posterior a la fecha de inicio.');
    }

    if (TrimestreRepository.existeNombre(nombre)) {
      throw new Error('Ya existe un trimestre con ese nombre.');
    }

    if (TrimestreRepository.existeSolapamiento(fechaInicio, fechaFin)) {
      throw new Error('Las fechas se solapan con un trimestre existente.');
    }

    var id        = TrimestreRepository.crear(nombre, fechaInicio, fechaFin);
    var trimestre = TrimestreRepository.obtenerPorId(id);

    return trimestre || {
      id_trimestre: id,
      nombre      : nombre,
      fecha_inicio: fechaInicio,
      fecha_fin   : fechaFin,
      activo      : 1
    };
  }

  // ─── Actualizar ───────────────────────────────────────────
  // Actualización parcial. Reglas:
  //   1. Trimestre debe existir
  //   2. Si nombre cambia → unicidad
  //   3. Si fecha cambia → validación formato
  //   4. Si ambas fechas presentes → coherencia y solapamiento

  function actualizar(idTrimestre, datos) {
    var trimestre = TrimestreRepository.obtenerPorId(idTrimestre);
    if (!trimestre) throw new Error('Trimestre no encontrado.');

    if (datos.hasOwnProperty('nombre')) {
      datos.nombre = String(datos.nombre).trim();

      if (TrimestreRepository.existeNombre(datos.nombre, idTrimestre)) {
        throw new Error('Ya existe un trimestre con ese nombre.');
      }
    }

    if (datos.hasOwnProperty('fecha_inicio') && !_esFechaValida(String(datos.fecha_inicio))) {
      throw new Error("Fecha de inicio '" + datos.fecha_inicio + "' no tiene el formato Y-m-d.");
    }

    if (datos.hasOwnProperty('fecha_fin') && !_esFechaValida(String(datos.fecha_fin))) {
      throw new Error("Fecha de fin '" + datos.fecha_fin + "' no tiene el formato Y-m-d.");
    }

    if (datos.hasOwnProperty('fecha_inicio') && datos.hasOwnProperty('fecha_fin')) {
      if (!_esPeriodoCoherente(String(datos.fecha_inicio), String(datos.fecha_fin))) {
        throw new Error('La fecha de fin debe ser posterior a la fecha de inicio.');
      }

      if (TrimestreRepository.existeSolapamiento(String(datos.fecha_inicio), String(datos.fecha_fin), idTrimestre)) {
        throw new Error('Las fechas se solapan con un trimestre existente.');
      }
    }

    TrimestreRepository.actualizar(idTrimestre, datos);

    return TrimestreRepository.obtenerPorId(idTrimestre) || trimestre;
  }

  // ─── Eliminar ─────────────────────────────────────────────
  // Reglas:
  //   1. Trimestre debe existir
  //   2. No puede tener sesiones asociadas

  function eliminar(idTrimestre) {
    var trimestre = TrimestreRepository.obtenerPorId(idTrimestre);
    if (!trimestre) throw new Error('Trimestre no encontrado.');

    if (TrimestreRepository.contarPorTrimestre(idTrimestre) > 0) {
      throw new Error('El trimestre tiene sesiones registradas. Elimínelas antes de continuar.');
    }

    TrimestreRepository.eliminar(idTrimestre);

    return { success: true, message: 'Trimestre eliminado correctamente.' };
  }

  // ─── Helpers privados ─────────────────────────────────────

  // Valida formato Y-m-d y que la fecha sea real (mismo criterio que PHP DateTime).
  function _esFechaValida(fecha) {
    if (!/^\d{4}-\d{2}-\d{2}$/.test(fecha)) return false;
    var partes = fecha.split('-');
    var anio   = parseInt(partes[0], 10);
    var mes    = parseInt(partes[1], 10);
    var dia    = parseInt(partes[2], 10);
    if (mes < 1 || mes > 12) return false;
    var diasEnMes = new Date(anio, mes, 0).getDate();
    return dia >= 1 && dia <= diasEnMes;
  }

  // Retorna true si fecha_fin > fecha_inicio (comparación lexicográfica Y-m-d).
  function _esPeriodoCoherente(fechaInicio, fechaFin) {
    return fechaFin > fechaInicio;
  }

  return {
    consultar : consultar,
    listar    : listar,
    crear     : crear,
    actualizar: actualizar,
    eliminar  : eliminar
  };

})();
