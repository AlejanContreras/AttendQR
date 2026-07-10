// =============================================================
// AttendQR — JORNADA_JornadaRepository
// =============================================================
// Capa de datos del módulo Jornada. Accede a Google Sheets.
// Replica: Src/Repositories/JornadaRepository.php  (PDO → SpreadsheetApp)
//
// Usa AUTH_SPREADSHEET_ID definido en AUTH_AuthRepository.gs.
//
// Estructura de hoja "jornadas":
//   A:id_jornada  B:nombre  C:hora_inicio  D:hora_fin  E:minutos_gracia
//
// NOTA: jornadas es una tabla de referencia sin columna 'estado'.
//   Coincide exactamente con la definición usada en FICHA_FichaRepository.gs.
// =============================================================

var JornadaRepository = (function () {

  // ── Helpers internos ─────────────────────────────────────

  function _getSheet(nombre) {
    var sheet = SpreadsheetApp.openById(AUTH_SPREADSHEET_ID).getSheetByName(nombre);
    if (!sheet) throw new Error('Hoja "' + nombre + '" no encontrada.');
    return sheet;
  }

  function _rowToJornada(row) {
    return {
      id_jornada    : row[0],
      nombre        : String(row[1]),
      hora_inicio   : row[2] !== '' && row[2] !== null ? String(row[2]) : null,
      hora_fin      : row[3] !== '' && row[3] !== null ? String(row[3]) : null,
      minutos_gracia: row[4] !== '' && row[4] !== null ? parseInt(row[4], 10) : 5
    };
  }

  function _nextId(sheet) {
    var rows  = sheet.getDataRange().getValues();
    var maxId = 0;
    for (var i = 1; i < rows.length; i++) {
      var id = parseInt(rows[i][0], 10);
      if (!isNaN(id) && id > maxId) maxId = id;
    }
    return maxId + 1;
  }

  // ── obtenerPorId ─────────────────────────────────────────
  // SELECT id_jornada, nombre, hora_inicio, hora_fin, minutos_gracia
  // FROM jornadas WHERE id_jornada = :id
  function obtenerPorId(idJornada) {
    var rows = _getSheet('jornadas').getDataRange().getValues();
    for (var i = 1; i < rows.length; i++) {
      if (rows[i][0] == idJornada) {
        return _rowToJornada(rows[i]);
      }
    }
    return null;
  }

  // ── listar ───────────────────────────────────────────────
  // SELECT id_jornada, nombre, hora_inicio, hora_fin, minutos_gracia
  // FROM jornadas ORDER BY hora_inicio
  function listar() {
    var rows   = _getSheet('jornadas').getDataRange().getValues();
    var result = [];
    for (var i = 1; i < rows.length; i++) {
      result.push(_rowToJornada(rows[i]));
    }
    // ORDER BY hora_inicio (null values last)
    result.sort(function (a, b) {
      if (!a.hora_inicio && !b.hora_inicio) return 0;
      if (!a.hora_inicio) return 1;
      if (!b.hora_inicio) return -1;
      return a.hora_inicio.localeCompare(b.hora_inicio);
    });
    return result;
  }

  // ── existeNombre ─────────────────────────────────────────
  // SELECT COUNT(*) FROM jornadas WHERE nombre = :nombre [AND id_jornada != :excluir]
  function existeNombre(nombre, excluirId) {
    var rows = _getSheet('jornadas').getDataRange().getValues();
    for (var i = 1; i < rows.length; i++) {
      if (String(rows[i][1]).trim().toLowerCase() === String(nombre).trim().toLowerCase()) {
        if (excluirId !== undefined && excluirId !== null && rows[i][0] == excluirId) continue;
        return true;
      }
    }
    return false;
  }

  // ── crear ─────────────────────────────────────────────────
  // INSERT INTO jornadas (nombre, hora_inicio, hora_fin, minutos_gracia) VALUES (...)
  function crear(nombre, horaInicio, horaFin, minutosGracia) {
    if (minutosGracia === undefined || minutosGracia === null) minutosGracia = 5;
    var sheet = _getSheet('jornadas');
    var newId = _nextId(sheet);
    // Columnas: A=id B=nombre C=hora_inicio D=hora_fin E=minutos_gracia
    sheet.appendRow([newId, nombre, horaInicio || '', horaFin || '', minutosGracia]);
    return newId;
  }

  // ── actualizar ────────────────────────────────────────────
  // UPDATE jornadas SET <campos> WHERE id_jornada = :id
  function actualizar(idJornada, datos) {
    var camposPermitidos = {
      nombre        : 2, // columna B (1-indexed)
      hora_inicio   : 3, // columna C
      hora_fin      : 4, // columna D
      minutos_gracia: 5  // columna E
    };

    var sheet = _getSheet('jornadas');
    var rows  = sheet.getDataRange().getValues();

    for (var i = 1; i < rows.length; i++) {
      if (rows[i][0] == idJornada) {
        var rowNum = i + 1;
        var count  = 0;
        for (var campo in camposPermitidos) {
          if (datos.hasOwnProperty(campo)) {
            sheet.getRange(rowNum, camposPermitidos[campo]).setValue(
              datos[campo] !== null && datos[campo] !== undefined ? datos[campo] : ''
            );
            count++;
          }
        }
        return count;
      }
    }
    return 0;
  }

  // ── eliminar ──────────────────────────────────────────────
  // DELETE FROM jornadas WHERE id_jornada = :id
  function eliminar(idJornada) {
    var sheet = _getSheet('jornadas');
    var rows  = sheet.getDataRange().getValues();
    for (var i = 1; i < rows.length; i++) {
      if (rows[i][0] == idJornada) {
        sheet.deleteRow(i + 1);
        return 1;
      }
    }
    return 0;
  }

  return {
    obtenerPorId : obtenerPorId,
    listar       : listar,
    existeNombre : existeNombre,
    crear        : crear,
    actualizar   : actualizar,
    eliminar     : eliminar
  };

})();
