// =============================================================
// AttendQR — APRENDIZ_AprendizRepository
// =============================================================
// Capa de datos del módulo Aprendiz. Accede a Google Sheets.
// Replica: Src/Repositories/AprendizRepository.php  (PDO → SpreadsheetApp)
//
// Usa la misma constante AUTH_SPREADSHEET_ID definida en AUTH_AuthRepository.gs
// (todos los archivos .gs comparten el mismo scope global en GAS).
//
// Estructura de hojas requerida:
//   "aprendices" → A:id_aprendiz  B:nombres  C:apellidos  D:numero_documento
//                  E:password_hash  F:id_ficha  G:activo  H:cuenta_activada
//   "fichas"     → A:id_ficha  B:codigo_ficha  C:nombre_programa  D:activa
// =============================================================

var AprendizRepository = (function () {

  // ── Helpers de acceso al Spreadsheet ────────────────────

  function _getSheet(nombre) {
    var sheet = SpreadsheetApp.openById(AUTH_SPREADSHEET_ID).getSheetByName(nombre);
    if (!sheet) throw new Error('Hoja "' + nombre + '" no encontrada.');
    return sheet;
  }

  // Convierte una fila de la hoja en objeto aprendiz completo (incluye password_hash para uso interno)
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

  // Busca la fila de una ficha por id y devuelve objeto básico
  function _getFichaById(idFicha) {
    var rows = _getSheet('fichas').getDataRange().getValues();
    for (var i = 1; i < rows.length; i++) {
      if (rows[i][0] == idFicha) {
        return {
          id_ficha      : rows[i][0],
          codigo_ficha  : String(rows[i][1]),
          nombre_programa: String(rows[i][2]),
          activa        : rows[i][3]
        };
      }
    }
    return null;
  }

  // Busca la fila de una ficha por codigo_ficha
  function _getFichaByCodigo(codigoFicha) {
    var rows = _getSheet('fichas').getDataRange().getValues();
    for (var i = 1; i < rows.length; i++) {
      if (String(rows[i][1]).trim() === codigoFicha) {
        return {
          id_ficha       : rows[i][0],
          codigo_ficha   : String(rows[i][1]),
          nombre_programa: String(rows[i][2]),
          activa         : rows[i][3]
        };
      }
    }
    return null;
  }

  // Enriquece un aprendiz con datos de su ficha (equivale al JOIN SQL)
  function _joinFicha(aprendiz) {
    var ficha = _getFichaById(aprendiz.id_ficha);
    aprendiz.codigo_ficha    = ficha ? String(ficha.codigo_ficha)   : '';
    aprendiz.nombre_programa = ficha ? String(ficha.nombre_programa) : '';
    return aprendiz;
  }

  // Devuelve el siguiente ID disponible para la hoja dada
  function _nextId(sheet) {
    var rows = sheet.getDataRange().getValues();
    var maxId = 0;
    for (var i = 1; i < rows.length; i++) {
      var id = parseInt(rows[i][0], 10);
      if (!isNaN(id) && id > maxId) maxId = id;
    }
    return maxId + 1;
  }

  // ── obtenerPorId ─────────────────────────────────────────
  // SELECT a.*, f.codigo_ficha, f.nombre_programa
  // FROM aprendices a JOIN fichas f ON f.id_ficha = a.id_ficha
  // WHERE a.id_aprendiz = :id
  function obtenerPorId(idAprendiz) {
    var rows = _getSheet('aprendices').getDataRange().getValues();
    for (var i = 1; i < rows.length; i++) {
      if (rows[i][0] == idAprendiz) {
        return _joinFicha(_rowToAprendiz(rows[i]));
      }
    }
    return null;
  }

  // ── buscarPorDocumento ───────────────────────────────────
  // SELECT a.*, f.codigo_ficha, f.nombre_programa
  // FROM aprendices a JOIN fichas f ON f.id_ficha = a.id_ficha
  // WHERE a.numero_documento = :doc LIMIT 1
  function buscarPorDocumento(documento) {
    var rows = _getSheet('aprendices').getDataRange().getValues();
    for (var i = 1; i < rows.length; i++) {
      if (String(rows[i][3]).trim() === documento) {
        return _joinFicha(_rowToAprendiz(rows[i]));
      }
    }
    return null;
  }

  // ── listar ───────────────────────────────────────────────
  // SELECT a.*, f.codigo_ficha, f.nombre_programa
  // FROM aprendices a JOIN fichas f ON f.id_ficha = a.id_ficha
  // WHERE [filtros] ORDER BY apellidos, nombres
  function listar(idFicha, activo, documento, cuentaActiva) {
    var rows   = _getSheet('aprendices').getDataRange().getValues();
    var result = [];

    for (var i = 1; i < rows.length; i++) {
      var ap = _rowToAprendiz(rows[i]);

      if (idFicha    !== null && idFicha    !== undefined && ap.id_ficha        != idFicha)    continue;
      if (activo     !== null && activo     !== undefined && ap.activo          != activo)     continue;
      if (documento  !== null && documento  !== undefined && String(ap.numero_documento) !== String(documento)) continue;
      if (cuentaActiva !== null && cuentaActiva !== undefined && ap.cuenta_activada != cuentaActiva) continue;

      result.push(_joinFicha(ap));
    }

    // ORDER BY apellidos, nombres
    result.sort(function (a, b) {
      var cmp = String(a.apellidos).localeCompare(String(b.apellidos), 'es');
      return cmp !== 0 ? cmp : String(a.nombres).localeCompare(String(b.nombres), 'es');
    });

    return result;
  }

  // ── existeDocumento ──────────────────────────────────────
  // SELECT COUNT(*) FROM aprendices WHERE numero_documento = :doc [AND id_aprendiz != :excluir]
  function existeDocumento(numeroDocumento, excluirId) {
    var rows = _getSheet('aprendices').getDataRange().getValues();
    for (var i = 1; i < rows.length; i++) {
      if (String(rows[i][3]).trim() === numeroDocumento) {
        if (excluirId !== undefined && excluirId !== null && rows[i][0] == excluirId) continue;
        return true;
      }
    }
    return false;
  }

  // ── contarActivosPorFicha ────────────────────────────────
  // SELECT COUNT(*) FROM aprendices WHERE id_ficha = :id AND activo = 1
  function contarActivosPorFicha(idFicha) {
    var rows  = _getSheet('aprendices').getDataRange().getValues();
    var count = 0;
    for (var i = 1; i < rows.length; i++) {
      if (rows[i][5] == idFicha && rows[i][6] == 1) count++;
    }
    return count;
  }

  // ── crear ────────────────────────────────────────────────
  // INSERT INTO aprendices (numero_documento, nombres, apellidos, password_hash, id_ficha, activo, cuenta_activada)
  // VALUES (...) → retorna el nuevo id_aprendiz
  function crear(numeroDocumento, nombres, apellidos, passwordHash, idFicha, cuentaActivada) {
    var sheet = _getSheet('aprendices');
    var newId = _nextId(sheet);
    // Columnas: A=id B=nombres C=apellidos D=numero_documento E=password_hash F=id_ficha G=activo H=cuenta_activada
    sheet.appendRow([newId, nombres, apellidos, numeroDocumento, passwordHash, idFicha, 1, cuentaActivada]);
    return newId;
  }

  // ── activarCuenta ────────────────────────────────────────
  // UPDATE aprendices SET password_hash = :hash, cuenta_activada = 1
  // WHERE id_aprendiz = :id AND cuenta_activada = 0
  // Retorna filas afectadas (1 = éxito, 0 = ya estaba activada)
  function activarCuenta(idAprendiz, passwordHash) {
    var sheet = _getSheet('aprendices');
    var rows  = sheet.getDataRange().getValues();

    for (var i = 1; i < rows.length; i++) {
      if (rows[i][0] == idAprendiz) {
        if (rows[i][7] != 0) return 0; // ya activada → guard de concurrencia
        var rowNum = i + 1; // 1-indexed
        sheet.getRange(rowNum, 5).setValue(passwordHash); // E = password_hash
        sheet.getRange(rowNum, 8).setValue(1);            // H = cuenta_activada
        return 1;
      }
    }
    return 0;
  }

  // ── actualizar ───────────────────────────────────────────
  // UPDATE aprendices SET <campos> WHERE id_aprendiz = :id
  // Solo actualiza los campos permitidos que estén presentes en datos
  function actualizar(idAprendiz, datos) {
    var camposPermitidos = {
      nombres         : 2, // columna B (1-indexed)
      apellidos       : 3, // columna C
      password_hash   : 5, // columna E
      id_ficha        : 6, // columna F
      activo          : 7, // columna G
      cuenta_activada : 8  // columna H
    };

    var sheet = _getSheet('aprendices');
    var rows  = sheet.getDataRange().getValues();
    var count = 0;

    for (var i = 1; i < rows.length; i++) {
      if (rows[i][0] == idAprendiz) {
        var rowNum = i + 1;
        for (var campo in camposPermitidos) {
          if (datos.hasOwnProperty(campo)) {
            sheet.getRange(rowNum, camposPermitidos[campo]).setValue(datos[campo]);
            count++;
          }
        }
        return count;
      }
    }
    return 0;
  }

  // ── eliminar ─────────────────────────────────────────────
  // DELETE FROM aprendices WHERE id_aprendiz = :id
  function eliminar(idAprendiz) {
    var sheet = _getSheet('aprendices');
    var rows  = sheet.getDataRange().getValues();

    for (var i = 1; i < rows.length; i++) {
      if (rows[i][0] == idAprendiz) {
        sheet.deleteRow(i + 1);
        return 1;
      }
    }
    return 0;
  }

  // ── Helpers de fichas (usados por AprendizService) ───────
  // FichaRepository aún no migrado — se exponen aquí temporalmente.
  function obtenerFichaPorId(idFicha) {
    return _getFichaById(idFicha);
  }

  function obtenerFichaPorCodigo(codigoFicha) {
    return _getFichaByCodigo(String(codigoFicha).trim());
  }

  return {
    obtenerPorId          : obtenerPorId,
    buscarPorDocumento    : buscarPorDocumento,
    listar                : listar,
    existeDocumento       : existeDocumento,
    contarActivosPorFicha : contarActivosPorFicha,
    crear                 : crear,
    activarCuenta         : activarCuenta,
    actualizar            : actualizar,
    eliminar              : eliminar,
    obtenerFichaPorId     : obtenerFichaPorId,
    obtenerFichaPorCodigo : obtenerFichaPorCodigo
  };

})();
