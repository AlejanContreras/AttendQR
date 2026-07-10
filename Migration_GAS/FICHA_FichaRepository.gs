// =============================================================
// AttendQR — FICHA_FichaRepository
// =============================================================
// Capa de datos del módulo Ficha. Accede a Google Sheets.
// Replica: Src/Repositories/FichaRepository.php  (PDO → SpreadsheetApp)
//
// Usa AUTH_SPREADSHEET_ID definido en AUTH_AuthRepository.gs.
//
// Estructura de hoja "fichas":
//   A:id_ficha  B:codigo_ficha  C:nombre_programa  D:activa
//   E:nombre_materia  F:id_jornada  G:id_docente  H:id_trimestre
//
//   NOTA: activa está en columna D (índice 3) para mantener
//   compatibilidad con APRENDIZ_AprendizRepository que ya lee
//   fichas[col 3] como campo activa.
//
// Estructura de hoja "jornadas":
//   A:id_jornada  B:nombre  C:hora_inicio  D:hora_fin  E:minutos_gracia
// =============================================================

var FichaRepository = (function () {

  // ── Helpers internos ─────────────────────────────────────

  function _getSheet(nombre) {
    var sheet = SpreadsheetApp.openById(AUTH_SPREADSHEET_ID).getSheetByName(nombre);
    if (!sheet) throw new Error('Hoja "' + nombre + '" no encontrada.');
    return sheet;
  }

  // Columnas fichas (0-indexed): A=0 B=1 C=2 D=3 E=4 F=5 G=6 H=7
  function _rowToFicha(row) {
    return {
      id_ficha        : row[0],
      codigo_ficha    : String(row[1]),
      nombre_programa : String(row[2]),
      activa          : row[3],
      nombre_materia  : row[4] || null,
      id_jornada      : row[5] || null,
      id_docente      : row[6],
      id_trimestre    : row[7] || null
    };
  }

  // Columnas jornadas (0-indexed): A=0 B=1 C=2 D=3 E=4
  function _rowToJornada(row) {
    return {
      id_jornada    : row[0],
      nombre        : String(row[1]),
      hora_inicio   : String(row[2]),
      hora_fin      : String(row[3]),
      minutos_gracia: row[4]
    };
  }

  // Enriquece una ficha con los datos de su jornada (equivale al LEFT JOIN SQL)
  function _joinJornada(ficha) {
    if (!ficha.id_jornada) {
      ficha.nombre_jornada = null;
      ficha.hora_inicio    = null;
      ficha.hora_fin       = null;
      ficha.minutos_gracia = null;
      return ficha;
    }
    var rowsJ = _getSheet('jornadas').getDataRange().getValues();
    for (var j = 1; j < rowsJ.length; j++) {
      if (rowsJ[j][0] == ficha.id_jornada) {
        var jornada          = _rowToJornada(rowsJ[j]);
        ficha.nombre_jornada = jornada.nombre;
        ficha.hora_inicio    = jornada.hora_inicio;
        ficha.hora_fin       = jornada.hora_fin;
        ficha.minutos_gracia = jornada.minutos_gracia;
        return ficha;
      }
    }
    ficha.nombre_jornada = null;
    ficha.hora_inicio    = null;
    ficha.hora_fin       = null;
    ficha.minutos_gracia = null;
    return ficha;
  }

  // Devuelve el siguiente ID disponible
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
  // SELECT f.*, j.nombre AS nombre_jornada, j.hora_inicio, j.hora_fin, j.minutos_gracia
  // FROM fichas f LEFT JOIN jornadas j ON j.id_jornada = f.id_jornada
  // WHERE f.id_ficha = :id
  function obtenerPorId(idFicha) {
    var rows = _getSheet('fichas').getDataRange().getValues();
    for (var i = 1; i < rows.length; i++) {
      if (rows[i][0] == idFicha) {
        return _joinJornada(_rowToFicha(rows[i]));
      }
    }
    return null;
  }

  // ── listar ───────────────────────────────────────────────
  // SELECT f.*, j.nombre AS nombre_jornada
  // FROM fichas f LEFT JOIN jornadas j ON j.id_jornada = f.id_jornada
  // WHERE [filtros] ORDER BY f.id_ficha DESC
  function listar(nombrePrograma, activa, idJornada, idDocente) {
    var rowsFichas  = _getSheet('fichas').getDataRange().getValues();
    var jornadas    = {};

    // Cargar jornadas en caché para el JOIN (evita leer la hoja una vez por fila)
    try {
      var rowsJ = _getSheet('jornadas').getDataRange().getValues();
      for (var j = 1; j < rowsJ.length; j++) {
        var jor = _rowToJornada(rowsJ[j]);
        jornadas[jor.id_jornada] = jor;
      }
    } catch (e) { /* hoja jornadas no existe aún */ }

    var result = [];

    for (var i = 1; i < rowsFichas.length; i++) {
      var f = _rowToFicha(rowsFichas[i]);

      // Filtros opcionales
      if (nombrePrograma !== null && nombrePrograma !== undefined) {
        if (String(f.nombre_programa).toLowerCase().indexOf(String(nombrePrograma).toLowerCase()) === -1) continue;
      }
      if (activa !== null && activa !== undefined && f.activa != activa) continue;
      if (idJornada !== null && idJornada !== undefined && f.id_jornada != idJornada) continue;
      if (idDocente !== null && idDocente !== undefined && f.id_docente != idDocente) continue;

      // Enriquecer con jornada desde caché
      var jor = jornadas[f.id_jornada] || null;
      f.nombre_jornada = jor ? jor.nombre        : null;
      f.hora_inicio    = jor ? jor.hora_inicio   : null;
      f.hora_fin       = jor ? jor.hora_fin      : null;
      f.minutos_gracia = jor ? jor.minutos_gracia: null;

      result.push(f);
    }

    // ORDER BY id_ficha DESC
    result.sort(function (a, b) { return b.id_ficha - a.id_ficha; });

    return result;
  }

  // ── obtenerPorCodigo ──────────────────────────────────────
  // SELECT f.*, j.nombre AS nombre_jornada, ...
  // FROM fichas f LEFT JOIN jornadas j ON j.id_jornada = f.id_jornada
  // WHERE f.codigo_ficha = :codigo LIMIT 1
  function obtenerPorCodigo(codigoFicha) {
    var rows = _getSheet('fichas').getDataRange().getValues();
    for (var i = 1; i < rows.length; i++) {
      if (String(rows[i][1]).trim() === String(codigoFicha).trim()) {
        return _joinJornada(_rowToFicha(rows[i]));
      }
    }
    return null;
  }

  // ── existeCodigo ──────────────────────────────────────────
  // SELECT COUNT(*) FROM fichas WHERE codigo_ficha = :codigo [AND id_ficha != :excluir]
  function existeCodigo(codigoFicha, excluirId) {
    var rows = _getSheet('fichas').getDataRange().getValues();
    for (var i = 1; i < rows.length; i++) {
      if (String(rows[i][1]).trim() === String(codigoFicha).trim()) {
        if (excluirId !== undefined && excluirId !== null && rows[i][0] == excluirId) continue;
        return true;
      }
    }
    return false;
  }

  // ── contarActivas ─────────────────────────────────────────
  // SELECT COUNT(*) FROM fichas WHERE activa = 1
  function contarActivas() {
    var rows  = _getSheet('fichas').getDataRange().getValues();
    var count = 0;
    for (var i = 1; i < rows.length; i++) {
      if (rows[i][3] == 1) count++;
    }
    return count;
  }

  // ── contarActivasPorJornada ───────────────────────────────
  // SELECT COUNT(*) FROM fichas WHERE id_jornada = :id AND activa = 1
  function contarActivasPorJornada(idJornada) {
    var rows  = _getSheet('fichas').getDataRange().getValues();
    var count = 0;
    for (var i = 1; i < rows.length; i++) {
      if (rows[i][5] == idJornada && rows[i][3] == 1) count++;
    }
    return count;
  }

  // ── crear ─────────────────────────────────────────────────
  // INSERT INTO fichas (codigo_ficha, nombre_programa, activa, nombre_materia,
  //                     id_jornada, id_docente, id_trimestre)
  // VALUES (...) → retorna nuevo id_ficha
  function crear(codigoFicha, nombrePrograma, idJornada, idDocente, nombreMateria, idTrimestre) {
    var sheet = _getSheet('fichas');
    var newId = _nextId(sheet);
    // Columnas: A=id B=codigo C=programa D=activa E=materia F=jornada G=docente H=trimestre
    sheet.appendRow([
      newId,
      codigoFicha,
      nombrePrograma,
      1,                          // activa = 1
      nombreMateria || '',
      idJornada     || '',
      idDocente,
      idTrimestre   || ''
    ]);
    return newId;
  }

  // ── actualizar ────────────────────────────────────────────
  // UPDATE fichas SET <campos> WHERE id_ficha = :id
  function actualizar(idFicha, datos) {
    var camposPermitidos = {
      codigo_ficha   : 2, // columna B (1-indexed)
      nombre_programa: 3, // columna C
      activa         : 4, // columna D
      nombre_materia : 5, // columna E
      id_jornada     : 6, // columna F
      id_docente     : 7, // columna G
      id_trimestre   : 8  // columna H
    };

    var sheet = _getSheet('fichas');
    var rows  = sheet.getDataRange().getValues();

    for (var i = 1; i < rows.length; i++) {
      if (rows[i][0] == idFicha) {
        var rowNum = i + 1;
        var count  = 0;
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

  // ── eliminar ──────────────────────────────────────────────
  // DELETE FROM fichas WHERE id_ficha = :id
  function eliminar(idFicha) {
    var sheet = _getSheet('fichas');
    var rows  = sheet.getDataRange().getValues();
    for (var i = 1; i < rows.length; i++) {
      if (rows[i][0] == idFicha) {
        sheet.deleteRow(i + 1);
        return 1;
      }
    }
    return 0;
  }

  return {
    obtenerPorId           : obtenerPorId,
    listar                 : listar,
    obtenerPorCodigo       : obtenerPorCodigo,
    existeCodigo           : existeCodigo,
    contarActivas          : contarActivas,
    contarActivasPorJornada: contarActivasPorJornada,
    crear                  : crear,
    actualizar             : actualizar,
    eliminar               : eliminar
  };

})();
