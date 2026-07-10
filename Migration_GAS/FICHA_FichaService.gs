// =============================================================
// AttendQR — FICHA_FichaService
// =============================================================
// Lógica de negocio del módulo Ficha.
// Replica: Src/Services/FichaService.php
//
// Dependencias:
//   FichaRepository    — acceso a datos de fichas y jornadas
//   AprendizRepository — contarActivosPorFicha() para validación de eliminación
//   (SesionService.historialPorFicha — pendiente hasta migración SESION)
// =============================================================

var FichaService = (function () {

  // ─── Consulta ────────────────────────────────────────────

  function consultar(idFicha) {
    var ficha = FichaRepository.obtenerPorId(idFicha);
    if (!ficha) throw new Error('Ficha no encontrada.');
    return ficha;
  }

  // ─── Listar ───────────────────────────────────────────────

  function listar(nombrePrograma, estado, idJornada, idDocente) {
    var activa = estado === 'activa' ? 1 : estado === 'inactiva' ? 0 : null;

    var fichas = FichaRepository.listar(
      nombrePrograma || null,
      activa,
      idJornada      || null,
      idDocente      || null
    );

    return {
      fichas: fichas,
      total : fichas.length
    };
  }

  // ─── Crear ────────────────────────────────────────────────

  function crear(codigoFicha, nombrePrograma, idJornada, idDocente, nombreMateria, idTrimestre) {
    codigoFicha    = String(codigoFicha).trim();
    nombrePrograma = String(nombrePrograma).trim();
    nombreMateria  = (nombreMateria !== null && nombreMateria !== undefined)
                     ? String(nombreMateria).trim()
                     : null;

    // ── Validaciones (estándar SENA) ──────────────────────

    if (codigoFicha === '') {
      throw new Error('El código de ficha no puede estar vacío.');
    }

    if (!/^\d+$/.test(codigoFicha)) {
      throw new Error('El número de ficha solo puede contener dígitos.');
    }

    if (codigoFicha.length !== 7) {
      throw new Error('El número de ficha debe tener exactamente 7 dígitos (estándar SENA).');
    }

    if (FichaRepository.existeCodigo(codigoFicha)) {
      throw new Error('El número de ficha ya está registrado en el sistema.');
    }

    // ── Crear ──────────────────────────────────────────────

    var id    = FichaRepository.crear(codigoFicha, nombrePrograma, idJornada, idDocente, nombreMateria, idTrimestre || null);
    var ficha = FichaRepository.obtenerPorId(id);

    return ficha || {
      id_ficha        : id,
      codigo_ficha    : codigoFicha,
      nombre_programa : nombrePrograma,
      nombre_materia  : nombreMateria,
      id_jornada      : idJornada,
      id_docente      : idDocente,
      id_trimestre    : idTrimestre || null,
      activa          : 1
    };
  }

  // ─── Actualizar ───────────────────────────────────────────

  function actualizar(idFicha, datos) {
    var ficha = FichaRepository.obtenerPorId(idFicha);
    if (!ficha) throw new Error('Ficha no encontrada.');

    // Si se cambia el código, validar unicidad excluyendo la ficha actual
    if (datos.hasOwnProperty('codigo_ficha')) {
      datos.codigo_ficha = String(datos.codigo_ficha).trim();

      if (FichaRepository.existeCodigo(datos.codigo_ficha, idFicha)) {
        throw new Error('El código de ficha ya está en uso por otra ficha.');
      }
    }

    FichaRepository.actualizar(idFicha, datos);

    return FichaRepository.obtenerPorId(idFicha) || ficha;
  }

  // ─── Eliminar ─────────────────────────────────────────────

  function eliminar(idFicha) {
    var ficha = FichaRepository.obtenerPorId(idFicha);
    if (!ficha) throw new Error('Ficha no encontrada.');

    // Validar que no haya aprendices activos vinculados a la ficha
    if (AprendizRepository.contarActivosPorFicha(idFicha) > 0) {
      throw new Error('La ficha tiene aprendices activos. Desvincúlelos antes de eliminarla.');
    }

    FichaRepository.eliminar(idFicha);

    return { success: true, message: 'Ficha eliminada correctamente.' };
  }

  return {
    consultar: consultar,
    listar   : listar,
    crear    : crear,
    actualizar: actualizar,
    eliminar : eliminar
  };

})();
