// =============================================================
// AttendQR — AUTH_AuthRepository
// =============================================================
// Capa de datos para autenticación. Lee desde Google Sheets.
// Replica: Src/Repositories/AuthRepository.php  (PDO → SpreadsheetApp)
//
// Estructura de hojas requerida en el Spreadsheet:
//   "docentes"   → A:id_docente  B:nombres  C:apellidos  D:correo  E:password_hash  F:activo
//   "aprendices" → A:id_aprendiz B:nombres  C:apellidos  D:numero_documento  E:password_hash
//                  F:id_ficha  G:activo  H:cuenta_activada
//   "fichas"     → A:id_ficha  B:codigo_ficha  C:nombre_programa
//
// CONFIGURACIÓN: Reemplaza AUTH_SPREADSHEET_ID con el ID real del Spreadsheet.
//   El ID se obtiene de la URL: .../spreadsheets/d/<ID>/edit
// =============================================================

// AUTH_SPREADSHEET_ID se declara en CONFIG_Constants.gs — no redeclarar aquí.

var AuthRepository = (function () {

  // ── Helpers internos ─────────────────────────────────────

  function _getSheet(nombre) {
    var sheet = SpreadsheetApp.openById(AUTH_SPREADSHEET_ID).getSheetByName(nombre);
    if (!sheet) throw new Error('Hoja "' + nombre + '" no encontrada en el Spreadsheet.');
    return sheet;
  }

  function _rowToDocente(row) {
    return {
      id_docente   : row[0],
      nombres      : String(row[1]),
      apellidos    : String(row[2]),
      correo       : String(row[3]),
      password_hash: String(row[4]),
      activo       : row[5]
    };
  }

  function _rowToAprendiz(row) {
    return {
      id_aprendiz     : row[0],
      nombres         : String(row[1]),
      apellidos       : String(row[2]),
      numero_documento: String(row[3]),
      password_hash   : String(row[4]),
      id_ficha        : row[5],
      activo          : row[6],
      cuenta_activada : row[7]
    };
  }

  // ── buscarDocentePorCorreo ───────────────────────────────
  // SELECT id_docente, nombres, apellidos, correo, password_hash, activo
  // FROM docentes WHERE correo = :correo
  function buscarDocentePorCorreo(correo) {
    var rows = _getSheet('docentes').getDataRange().getValues();
    for (var i = 1; i < rows.length; i++) {
      if (String(rows[i][3]).toLowerCase().trim() === correo) {
        return _rowToDocente(rows[i]);
      }
    }
    return null;
  }

  // ── buscarAprendizPorDocumento ───────────────────────────
  // SELECT ap.*, f.codigo_ficha, f.nombre_programa
  // FROM aprendices ap JOIN fichas f ON f.id_ficha = ap.id_ficha
  // WHERE ap.numero_documento = :documento
  function buscarAprendizPorDocumento(documento) {
    var rowsAp     = _getSheet('aprendices').getDataRange().getValues();
    var rowsFichas = _getSheet('fichas').getDataRange().getValues();

    for (var i = 1; i < rowsAp.length; i++) {
      if (String(rowsAp[i][3]).trim() === documento) {
        var ap = _rowToAprendiz(rowsAp[i]);

        // JOIN con hoja fichas (equivalente al JOIN SQL)
        for (var j = 1; j < rowsFichas.length; j++) {
          if (rowsFichas[j][0] == ap.id_ficha) {
            ap.codigo_ficha    = String(rowsFichas[j][1]);
            ap.nombre_programa = String(rowsFichas[j][2]);
            break;
          }
        }
        return ap;
      }
    }
    return null;
  }

  // ── buscarDocentePorId (sin password_hash) ───────────────
  function buscarDocentePorId(id) {
    var rows = _getSheet('docentes').getDataRange().getValues();
    for (var i = 1; i < rows.length; i++) {
      if (rows[i][0] == id) {
        var d = _rowToDocente(rows[i]);
        delete d.password_hash;
        return d;
      }
    }
    return null;
  }

  // ── buscarAprendizPorId (sin password_hash) ──────────────
  function buscarAprendizPorId(id) {
    var rows = _getSheet('aprendices').getDataRange().getValues();
    for (var i = 1; i < rows.length; i++) {
      if (rows[i][0] == id) {
        var a = _rowToAprendiz(rows[i]);
        delete a.password_hash;
        return a;
      }
    }
    return null;
  }

  return {
    buscarDocentePorCorreo     : buscarDocentePorCorreo,
    buscarAprendizPorDocumento : buscarAprendizPorDocumento,
    buscarDocentePorId         : buscarDocentePorId,
    buscarAprendizPorId        : buscarAprendizPorId
  };

})();
