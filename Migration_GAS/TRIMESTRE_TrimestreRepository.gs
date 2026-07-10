// =============================================================
// AttendQR — TRIMESTRE_TrimestreRepository
// =============================================================
// Capa de datos del módulo Trimestre. Accede a Google Sheets.
// Replica: Src/Repositories/TrimestreRepository.php  (PDO → SpreadsheetApp)
//
// Usa AUTH_SPREADSHEET_ID definido en AUTH_AuthRepository.gs.
//
// Estructura de hoja "trimestres":
//   A:id_trimestre  B:nombre  C:fecha_inicio  D:fecha_fin  E:activo
//
// NOTA: No existe columna 'anio'. El año se deriva de fecha_inicio.
// =============================================================

var TrimestreRepository = (function () {

  // ── Helpers internos ─────────────────────────────────────

  function _getSheet(nombre) {
    var sheet = SpreadsheetApp.openById(AUTH_SPREADSHEET_ID).getSheetByName(nombre);
    if (!sheet) throw new Error('Hoja "' + nombre + '" no encontrada.');
    return sheet;
  }

  function _rowToTrimestre(row) {
    return {
      id_trimestre: row[0],
      nombre      : String(row[1]),
      fecha_inicio: row[2] !== '' && row[2] !== null ? String(row[2]).split('T')[0] : null,
      fecha_fin   : row[3] !== '' && row[3] !== null ? String(row[3]).split('T')[0] : null,
      activo      : parseInt(row[4], 10)
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
  // SELECT id_trimestre, nombre, fecha_inicio, fecha_fin, activo
  // FROM trimestres WHERE id_trimestre = :id
  function obtenerPorId(idTrimestre) {
    var rows = _getSheet('trimestres').getDataRange().getValues();
    for (var i = 1; i < rows.length; i++) {
      if (rows[i][0] == idTrimestre) {
        return _rowToTrimestre(rows[i]);
      }
    }
    return null;
  }

  // ── listar ───────────────────────────────────────────────
  // SELECT ... FROM trimestres [WHERE YEAR(fecha_inicio)=:anio] [AND activo=:activo]
  // ORDER BY fecha_inicio DESC
  function listar(anio, activo) {
    var rows   = _getSheet('trimestres').getDataRange().getValues();
    var result = [];
    for (var i = 1; i < rows.length; i++) {
      var t = _rowToTrimestre(rows[i]);

      if (anio !== null && anio !== undefined) {
        var anioFila = t.fecha_inicio ? parseInt(t.fecha_inicio.substring(0, 4), 10) : null;
        if (anioFila !== anio) continue;
      }

      if (activo !== null && activo !== undefined) {
        if (t.activo != activo) continue;
      }

      result.push(t);
    }
    // ORDER BY fecha_inicio DESC
    result.sort(function (a, b) {
      var fa = a.fecha_inicio || '';
      var fb = b.fecha_inicio || '';
      if (fa < fb) return 1;
      if (fa > fb) return -1;
      return 0;
    });
    return result;
  }

  // ── existeNombre ─────────────────────────────────────────
  // SELECT COUNT(*) FROM trimestres WHERE nombre = :nombre [AND id_trimestre != :excluir]
  function existeNombre(nombre, excluirId) {
    var rows = _getSheet('trimestres').getDataRange().getValues();
    for (var i = 1; i < rows.length; i++) {
      if (String(rows[i][1]) === String(nombre)) {
        if (excluirId !== undefined && excluirId !== null && rows[i][0] == excluirId) continue;
        return true;
      }
    }
    return false;
  }

  // ── existeSolapamiento ───────────────────────────────────
  // SELECT COUNT(*) FROM trimestres
  //   WHERE fecha_inicio <= :fin AND fecha_fin >= :inicio
  //   [AND id_trimestre != :excluir]
  function existeSolapamiento(fechaInicio, fechaFin, excluirId) {
    var rows = _getSheet('trimestres').getDataRange().getValues();
    for (var i = 1; i < rows.length; i++) {
      if (excluirId !== undefined && excluirId !== null && rows[i][0] == excluirId) continue;
      var fi = rows[i][2] ? String(rows[i][2]).split('T')[0] : null;
      var ff = rows[i][3] ? String(rows[i][3]).split('T')[0] : null;
      if (!fi || !ff) continue;
      // Overlap: fi <= fechaFin AND ff >= fechaInicio
      if (fi <= fechaFin && ff >= fechaInicio) {
        return true;
      }
    }
    return false;
  }

  // ── contarPorTrimestre ───────────────────────────────────
  // Inline helper para validación de eliminar.
  // Equivale a SesionRepository.contarPorTrimestre() (no migrado aún).
  // Cuenta filas en hoja 'sesiones_asistencia' cuyo id_trimestre coincide.
  function contarPorTrimestre(idTrimestre) {
    try {
      var ss    = SpreadsheetApp.openById(AUTH_SPREADSHEET_ID);
      var sheet = ss.getSheetByName('sesiones_asistencia');
      if (!sheet) return 0;

      var rows    = sheet.getDataRange().getValues();
      if (rows.length < 2) return 0;

      // Localizar columna id_trimestre dinámicamente por header
      var headers = rows[0].map(function (h) { return String(h).toLowerCase().trim(); });
      var colIdx  = headers.indexOf('id_trimestre');
      if (colIdx === -1) return 0;

      var count = 0;
      for (var i = 1; i < rows.length; i++) {
        if (rows[i][colIdx] == idTrimestre) count++;
      }
      return count;
    } catch (e) {
      return 0;
    }
  }

  // ── crear ─────────────────────────────────────────────────
  // INSERT INTO trimestres (nombre, fecha_inicio, fecha_fin, activo) VALUES (..., 1)
  function crear(nombre, fechaInicio, fechaFin) {
    var sheet = _getSheet('trimestres');
    var newId = _nextId(sheet);
    // Columnas: A=id_trimestre B=nombre C=fecha_inicio D=fecha_fin E=activo
    sheet.appendRow([newId, nombre, fechaInicio, fechaFin, 1]);
    return newId;
  }

  // ── actualizar ────────────────────────────────────────────
  // UPDATE trimestres SET <campos> WHERE id_trimestre = :id
  function actualizar(idTrimestre, datos) {
    var camposPermitidos = {
      nombre      : 2, // columna B (1-indexed)
      fecha_inicio: 3, // columna C
      fecha_fin   : 4, // columna D
      activo      : 5  // columna E
    };

    var sheet = _getSheet('trimestres');
    var rows  = sheet.getDataRange().getValues();

    for (var i = 1; i < rows.length; i++) {
      if (rows[i][0] == idTrimestre) {
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
  // DELETE FROM trimestres WHERE id_trimestre = :id
  function eliminar(idTrimestre) {
    var sheet = _getSheet('trimestres');
    var rows  = sheet.getDataRange().getValues();
    for (var i = 1; i < rows.length; i++) {
      if (rows[i][0] == idTrimestre) {
        sheet.deleteRow(i + 1);
        return 1;
      }
    }
    return 0;
  }

  return {
    obtenerPorId       : obtenerPorId,
    listar             : listar,
    existeNombre       : existeNombre,
    existeSolapamiento : existeSolapamiento,
    contarPorTrimestre : contarPorTrimestre,
    crear              : crear,
    actualizar         : actualizar,
    eliminar           : eliminar
  };

})();
