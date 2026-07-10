// =============================================================
// AttendQR — DOCENTE_DocenteRepository
// =============================================================
// Capa de datos del módulo Docente. Accede a Google Sheets.
// Replica: Src/Repositories/DocenteRepository.php  (PDO → SpreadsheetApp)
//
// Usa AUTH_SPREADSHEET_ID definido en AUTH_AuthRepository.gs
// (scope global compartido entre todos los .gs del proyecto).
//
// Estructura de hoja "docentes":
//   A:id_docente  B:nombres  C:apellidos  D:correo  E:password_hash
//   F:activo  G:creado_en
//
// Estructura de hoja "fichas" (columnas relevantes para este módulo):
//   A:id_ficha  B:codigo_ficha  C:nombre_programa  D:activa  E:id_docente  ...
//
// Estructura de hoja "sesiones_asistencia" (columnas mínimas requeridas):
//   Headers leídos dinámicamente — ver _contarSesionesActivasPorDocente
// =============================================================

var DocenteRepository = (function () {

  // ── Helpers internos ─────────────────────────────────────

  function _getSheet(nombre) {
    var sheet = SpreadsheetApp.openById(AUTH_SPREADSHEET_ID).getSheetByName(nombre);
    if (!sheet) throw new Error('Hoja "' + nombre + '" no encontrada.');
    return sheet;
  }

  // Convierte una fila en objeto docente completo (incluye password_hash para uso interno)
  function _rowToDocente(row) {
    return {
      id_docente   : row[0],
      nombres      : String(row[1]),
      apellidos    : String(row[2]),
      correo       : String(row[3]),
      password_hash: String(row[4]),
      activo       : row[5],
      creado_en    : row[6] || ''
    };
  }

  // Versión pública — nunca expone password_hash (equivale al SELECT sin password_hash de PHP)
  function _rowToDocentePublico(row) {
    return {
      id_docente: row[0],
      nombres   : String(row[1]),
      apellidos : String(row[2]),
      correo    : String(row[3]),
      activo    : row[5],
      creado_en : row[6] || ''
    };
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

  // Busca el índice de una columna leyendo la fila de cabeceras (columna 0 = índice 0)
  function _colIdx(headers, nombre) {
    for (var i = 0; i < headers.length; i++) {
      if (String(headers[i]).toLowerCase().trim() === nombre) return i;
    }
    return -1;
  }

  // ── obtenerPorId ─────────────────────────────────────────
  // SELECT id_docente, nombres, apellidos, correo, activo, creado_en
  // FROM docentes WHERE id_docente = :id
  function obtenerPorId(idDocente) {
    var rows = _getSheet('docentes').getDataRange().getValues();
    for (var i = 1; i < rows.length; i++) {
      if (rows[i][0] == idDocente) {
        return _rowToDocentePublico(rows[i]);
      }
    }
    return null;
  }

  // ── buscarPorCorreo ───────────────────────────────────────
  // SELECT id_docente, nombres, apellidos, correo, password_hash, activo
  // FROM docentes WHERE correo = :correo
  // (incluye password_hash — usado por AUTH y por verificaciones internas)
  function buscarPorCorreo(correo) {
    var rows = _getSheet('docentes').getDataRange().getValues();
    for (var i = 1; i < rows.length; i++) {
      if (String(rows[i][3]).toLowerCase().trim() === correo) {
        return _rowToDocente(rows[i]); // versión completa con hash
      }
    }
    return null;
  }

  // ── listar ───────────────────────────────────────────────
  // SELECT id_docente, nombres, apellidos, correo, activo, creado_en
  // FROM docentes [WHERE activo = :activo] ORDER BY apellidos, nombres
  function listar(activo) {
    var rows   = _getSheet('docentes').getDataRange().getValues();
    var result = [];

    for (var i = 1; i < rows.length; i++) {
      if (activo !== null && activo !== undefined && rows[i][5] != activo) continue;
      result.push(_rowToDocentePublico(rows[i]));
    }

    result.sort(function (a, b) {
      var cmp = String(a.apellidos).localeCompare(String(b.apellidos), 'es');
      return cmp !== 0 ? cmp : String(a.nombres).localeCompare(String(b.nombres), 'es');
    });

    return result;
  }

  // ── existeCorreo ─────────────────────────────────────────
  // SELECT COUNT(*) FROM docentes WHERE correo = :correo [AND id_docente != :excluir]
  function existeCorreo(correo, excluirId) {
    var rows = _getSheet('docentes').getDataRange().getValues();
    for (var i = 1; i < rows.length; i++) {
      if (String(rows[i][3]).toLowerCase().trim() === correo) {
        if (excluirId !== undefined && excluirId !== null && rows[i][0] == excluirId) continue;
        return true;
      }
    }
    return false;
  }

  // ── contarActivos ─────────────────────────────────────────
  // SELECT COUNT(*) FROM docentes WHERE activo = 1
  function contarActivos() {
    var rows  = _getSheet('docentes').getDataRange().getValues();
    var count = 0;
    for (var i = 1; i < rows.length; i++) {
      if (rows[i][5] == 1) count++;
    }
    return count;
  }

  // ── crear ─────────────────────────────────────────────────
  // INSERT INTO docentes (nombres, apellidos, correo, password_hash, activo) VALUES (...)
  function crear(nombres, apellidos, correo, passwordHash) {
    var sheet  = _getSheet('docentes');
    var newId  = _nextId(sheet);
    var ahora  = new Date();
    // Columnas: A=id B=nombres C=apellidos D=correo E=password_hash F=activo G=creado_en
    sheet.appendRow([newId, nombres, apellidos, correo, passwordHash, 1, ahora]);
    return newId;
  }

  // ── actualizar ────────────────────────────────────────────
  // UPDATE docentes SET <campos> WHERE id_docente = :id
  function actualizar(idDocente, datos) {
    var camposPermitidos = {
      nombres      : 2, // columna B (1-indexed)
      apellidos    : 3, // columna C
      correo       : 4, // columna D
      password_hash: 5, // columna E
      activo       : 6  // columna F
    };

    var sheet = _getSheet('docentes');
    var rows  = sheet.getDataRange().getValues();

    for (var i = 1; i < rows.length; i++) {
      if (rows[i][0] == idDocente) {
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
  // DELETE FROM docentes WHERE id_docente = :id
  function eliminar(idDocente) {
    var sheet = _getSheet('docentes');
    var rows  = sheet.getDataRange().getValues();
    for (var i = 1; i < rows.length; i++) {
      if (rows[i][0] == idDocente) {
        sheet.deleteRow(i + 1);
        return 1;
      }
    }
    return 0;
  }

  // ── contarSesionesActivasPorDocente ───────────────────────
  // SELECT COUNT(*) FROM sesiones_asistencia sa
  // JOIN fichas f ON f.id_ficha = sa.id_ficha
  // WHERE f.id_docente = :id AND sa.estado_sesion = 'abierta'
  //
  // Lee cabeceras dinámicamente para ser resistente al orden de columnas.
  // Temporal: cuando SESION sea migrado, este cálculo debe delegarse a SesionRepository.
  function contarSesionesActivasPorDocente(idDocente) {
    try {
      var sheetSes    = _getSheet('sesiones_asistencia');
      var sheetFichas = _getSheet('fichas');

      var rowsSes    = sheetSes.getDataRange().getValues();
      var rowsFichas = sheetFichas.getDataRange().getValues();

      if (rowsSes.length < 2 || rowsFichas.length < 2) return 0;

      // Leer índices de columna por nombre de cabecera
      var headersSes    = rowsSes[0].map(function (h) { return String(h).toLowerCase().trim(); });
      var headersFichas = rowsFichas[0].map(function (h) { return String(h).toLowerCase().trim(); });

      var colSesIdFicha   = _colIdx(headersSes,    'id_ficha');
      var colSesEstado    = _colIdx(headersSes,    'estado_sesion');
      var colFichaId      = _colIdx(headersFichas, 'id_ficha');
      var colFichaDocente = _colIdx(headersFichas, 'id_docente');

      if (colSesIdFicha < 0 || colSesEstado < 0 || colFichaId < 0 || colFichaDocente < 0) return 0;

      // Construir set de id_ficha que pertenecen al docente
      var fichasDelDocente = {};
      for (var j = 1; j < rowsFichas.length; j++) {
        if (rowsFichas[j][colFichaDocente] == idDocente) {
          fichasDelDocente[rowsFichas[j][colFichaId]] = true;
        }
      }

      var count = 0;
      for (var i = 1; i < rowsSes.length; i++) {
        var idFichaSes = rowsSes[i][colSesIdFicha];
        var estado     = String(rowsSes[i][colSesEstado]).toLowerCase().trim();
        if (fichasDelDocente[idFichaSes] && estado === 'abierta') count++;
      }
      return count;

    } catch (e) {
      // La hoja sesiones_asistencia no existe aún (SESION no migrado)
      return 0;
    }
  }

  return {
    obtenerPorId                    : obtenerPorId,
    buscarPorCorreo                 : buscarPorCorreo,
    listar                          : listar,
    existeCorreo                    : existeCorreo,
    contarActivos                   : contarActivos,
    crear                           : crear,
    actualizar                      : actualizar,
    eliminar                        : eliminar,
    contarSesionesActivasPorDocente : contarSesionesActivasPorDocente
  };

})();
