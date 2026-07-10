// =============================================================
// AttendQR — ASISTENCIA_AsistenciaRepository
// =============================================================
// Capa de datos del módulo Asistencia. Accede a Google Sheets.
// Replica: Src/Repositories/AsistenciaRepository.php  (PDO → SpreadsheetApp)
//
// Usa AUTH_SPREADSHEET_ID definido en AUTH_AuthRepository.gs.
//
// Estructura de la hoja "asistencias" (13 columnas A–M):
//   A:id_asistencia   B:id_sesion       C:id_aprendiz
//   D:id_token_usado  E:estado          F:metodo_registro
//   G:hora_registro   H:minutos_retardo I:ubicacion_valida
//   J:latitud         K:longitud        L:observacion
//   M:registrado_en
//
// Hojas de apoyo (JOIN simulation):
//   "sesiones_asistencia" — A:id_sesion B:id_ficha C:nombre_materia
//     D:fecha_sesion E:estado_sesion F:hora_apertura G:hora_inicio_clase
//     H:hora_cierre I:limite_retardo_minutos J:duracion_maxima_minutos
//     K:rotacion_qr_segundos L:ubicacion_activa M:lat_docente
//     N:lng_docente O:accuracy_docente
//   "aprendices"  — A:id_aprendiz B:nombres C:apellidos
//     D:numero_documento E:password_hash F:id_ficha G:activo H:cuenta_activada
//   "fichas"      — A:id_ficha B:codigo_ficha C:nombre_programa D:activa
//     E:nombre_materia F:id_jornada G:id_docente H:id_trimestre
//   "jornadas"    — A:id_jornada B:nombre C:hora_inicio D:hora_fin E:minutos_gracia
//   "docentes"    — A:id_docente B:numero_documento C:nombres D:apellidos
//     E:correo F:password_hash G:activo H:registrado_en
// =============================================================

var AsistenciaRepository = (function () {

  // ── Helpers de acceso ─────────────────────────────────────────

  function _ss() {
    return SpreadsheetApp.openById(AUTH_SPREADSHEET_ID);
  }

  function _getSheet(nombre) {
    var sheet = _ss().getSheetByName(nombre);
    if (!sheet) throw new Error('Hoja "' + nombre + '" no encontrada.');
    return sheet;
  }

  function _getSheetSafe(nombre) {
    try { return _ss().getSheetByName(nombre); } catch (e) { return null; }
  }

  function _nowStr() {
    return Utilities.formatDate(new Date(), Session.getScriptTimeZone(), 'yyyy-MM-dd HH:mm:ss');
  }

  function _todayStr() {
    return Utilities.formatDate(new Date(), Session.getScriptTimeZone(), 'yyyy-MM-dd');
  }

  function _cellDatetime(val) {
    if (val === null || val === undefined || val === '') return null;
    if (val instanceof Date) {
      return Utilities.formatDate(val, Session.getScriptTimeZone(), 'yyyy-MM-dd HH:mm:ss');
    }
    return String(val);
  }

  function _cellDate(val) {
    if (val === null || val === undefined || val === '') return null;
    if (val instanceof Date) {
      return Utilities.formatDate(val, Session.getScriptTimeZone(), 'yyyy-MM-dd');
    }
    return String(val).split('T')[0];
  }

  function _cellTime(val) {
    if (val === null || val === undefined || val === '') return null;
    if (val instanceof Date) {
      return Utilities.formatDate(val, Session.getScriptTimeZone(), 'HH:mm:ss');
    }
    return String(val);
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

  // ── Mapper: fila → objeto asistencia ─────────────────────────

  function _rowToAsistencia(row) {
    return {
      id_asistencia   : row[0],
      id_sesion       : row[1],
      id_aprendiz     : row[2],
      id_token_usado  : row[3] || null,
      estado          : String(row[4]),
      metodo_registro : String(row[5] || 'qr'),
      hora_registro   : _cellTime(row[6]),
      minutos_retardo : parseInt(row[7], 10) || 0,
      ubicacion_valida: parseInt(row[8], 10) || 0,
      latitud         : (row[9] !== '' && row[9] !== null && row[9] !== undefined)
                          ? parseFloat(row[9]) : null,
      longitud        : (row[10] !== '' && row[10] !== null && row[10] !== undefined)
                          ? parseFloat(row[10]) : null,
      observacion     : row[11] || null,
      registrado_en   : _cellDatetime(row[12])
    };
  }

  // ── Carga mapas de apoyo para JOINs ──────────────────────────

  function _loadSesionesMap() {
    var sheet = _getSheetSafe('sesiones_asistencia');
    if (!sheet) return {};
    var rows = sheet.getDataRange().getValues();
    var map  = {};
    for (var i = 1; i < rows.length; i++) {
      var id = rows[i][0];
      map[id] = {
        id_sesion              : id,
        id_ficha               : rows[i][1],
        nombre_materia         : String(rows[i][2] || ''),
        fecha_sesion           : _cellDate(rows[i][3]),
        estado_sesion          : String(rows[i][4] || ''),
        hora_apertura          : _cellTime(rows[i][5]),
        hora_inicio_clase      : _cellTime(rows[i][6]),
        hora_cierre            : _cellTime(rows[i][7]),
        limite_retardo_minutos : parseInt(rows[i][8], 10) || 20,
        duracion_maxima_minutos: parseInt(rows[i][9], 10) || 120
      };
    }
    return map;
  }

  function _loadAprendicesMap() {
    var sheet = _getSheetSafe('aprendices');
    if (!sheet) return {};
    var rows = sheet.getDataRange().getValues();
    var map  = {};
    for (var i = 1; i < rows.length; i++) {
      var id = rows[i][0];
      map[id] = {
        id_aprendiz      : id,
        nombres          : String(rows[i][1] || ''),
        apellidos        : String(rows[i][2] || ''),
        numero_documento : String(rows[i][3] || ''),
        id_ficha         : rows[i][5],
        activo           : rows[i][6]
      };
    }
    return map;
  }

  function _loadFichasMap() {
    var sheet = _getSheetSafe('fichas');
    if (!sheet) return {};
    var rows = sheet.getDataRange().getValues();
    var map  = {};
    for (var i = 1; i < rows.length; i++) {
      var id = rows[i][0];
      map[id] = {
        id_ficha        : id,
        codigo_ficha    : String(rows[i][1] || ''),
        nombre_programa : String(rows[i][2] || ''),
        activa          : rows[i][3],
        nombre_materia  : rows[i][4] || null,
        id_jornada      : rows[i][5] || null,
        id_docente      : rows[i][6],
        id_trimestre    : rows[i][7] || null
      };
    }
    return map;
  }

  function _loadJornadasMap() {
    var sheet = _getSheetSafe('jornadas');
    if (!sheet) return {};
    var rows = sheet.getDataRange().getValues();
    var map  = {};
    for (var i = 1; i < rows.length; i++) {
      var id = rows[i][0];
      map[id] = { id_jornada: id, nombre: String(rows[i][1] || '') };
    }
    return map;
  }

  function _loadDocentesMap() {
    var sheet = _getSheetSafe('docentes');
    if (!sheet) return {};
    var rows = sheet.getDataRange().getValues();
    var map  = {};
    for (var i = 1; i < rows.length; i++) {
      var id = rows[i][0];
      map[id] = {
        id_docente : id,
        nombres    : String(rows[i][2] || ''),
        apellidos  : String(rows[i][3] || '')
      };
    }
    return map;
  }

  // =============================================================
  // ── Métodos de lectura ────────────────────────────────────────
  // =============================================================

  // SELECT a.*, ap.nombres, ap.apellidos, ap.numero_documento,
  //        s.fecha_sesion, s.id_ficha
  //   FROM asistencias JOIN aprendices JOIN sesiones_asistencia
  //  WHERE a.id_asistencia = :id
  function obtenerPorId(idAsistencia) {
    var sheet = _getSheetSafe('asistencias');
    if (!sheet) return null;
    var rows  = sheet.getDataRange().getValues();
    for (var i = 1; i < rows.length; i++) {
      if (rows[i][0] != idAsistencia) continue;
      var obj    = _rowToAsistencia(rows[i]);
      var sesMap = _loadSesionesMap();
      var apMap  = _loadAprendicesMap();
      var ses    = sesMap[obj.id_sesion]   || {};
      var ap     = apMap[obj.id_aprendiz]  || {};
      obj.fecha_sesion     = ses.fecha_sesion    || null;
      obj.id_ficha         = ses.id_ficha        || null;
      obj.nombres          = ap.nombres          || null;
      obj.apellidos        = ap.apellidos        || null;
      obj.numero_documento = ap.numero_documento || null;
      return obj;
    }
    return null;
  }

  // SELECT COUNT(*) > 0 FROM asistencias
  //  WHERE id_aprendiz = :idAp AND id_sesion = :idSes
  function existeEnSesion(idAprendiz, idSesion) {
    var sheet = _getSheetSafe('asistencias');
    if (!sheet) return false;
    var rows  = sheet.getDataRange().getValues();
    for (var i = 1; i < rows.length; i++) {
      if (rows[i][1] == idSesion && rows[i][2] == idAprendiz) return true;
    }
    return false;
  }

  // SELECT COUNT(*) > 0 FROM aprendices
  //  WHERE id_aprendiz = :id AND id_ficha = :idFicha AND activo = 1
  function aprendizPerteneceAFicha(idAprendiz, idFicha) {
    var sheet = _getSheetSafe('aprendices');
    if (!sheet) return false;
    var rows  = sheet.getDataRange().getValues();
    for (var i = 1; i < rows.length; i++) {
      if (rows[i][0] != idAprendiz) continue;
      if (rows[i][5] != idFicha)    continue;
      if (rows[i][6] != 1 && rows[i][6] !== '1') continue;
      return true;
    }
    return false;
  }

  // SELECT a.*, s.fecha_sesion, s.id_ficha, s.nombre_materia,
  //        s.estado_sesion, s.hora_inicio_clase, s.limite_retardo_minutos,
  //        f.codigo_ficha, f.nombre_programa
  //   FROM asistencias a JOIN sesiones_asistencia s JOIN fichas f
  //  WHERE a.id_aprendiz = :id
  //    AND (:fi IS NULL OR s.fecha_sesion >= :fi)
  //    AND (:ff IS NULL OR s.fecha_sesion <= :ff)
  //  ORDER BY s.fecha_sesion DESC
  function historialAprendiz(idAprendiz, fechaInicio, fechaFin) {
    var sheet = _getSheetSafe('asistencias');
    if (!sheet) return [];
    var rows   = sheet.getDataRange().getValues();
    var sesMap = _loadSesionesMap();
    var ficMap = _loadFichasMap();
    var result = [];

    for (var i = 1; i < rows.length; i++) {
      if (rows[i][2] != idAprendiz) continue;
      var obj = _rowToAsistencia(rows[i]);
      var ses = sesMap[obj.id_sesion];
      if (!ses) continue;
      if (fechaInicio && ses.fecha_sesion < fechaInicio) continue;
      if (fechaFin    && ses.fecha_sesion > fechaFin)   continue;
      var fic = ficMap[ses.id_ficha] || {};
      obj.fecha_sesion           = ses.fecha_sesion;
      obj.id_ficha               = ses.id_ficha;
      obj.nombre_materia         = ses.nombre_materia;
      obj.estado_sesion          = ses.estado_sesion;
      obj.hora_inicio_clase      = ses.hora_inicio_clase;
      obj.limite_retardo_minutos = ses.limite_retardo_minutos;
      obj.codigo_ficha           = fic.codigo_ficha    || null;
      obj.nombre_programa        = fic.nombre_programa || null;
      result.push(obj);
    }

    result.sort(function (a, b) {
      return a.fecha_sesion > b.fecha_sesion ? -1 : a.fecha_sesion < b.fecha_sesion ? 1 : 0;
    });
    return result;
  }

  // SELECT COUNT(*) FROM asistencias WHERE DATE(registrado_en) = CURDATE()
  function contarHoy() {
    var sheet = _getSheetSafe('asistencias');
    if (!sheet) return 0;
    var rows  = sheet.getDataRange().getValues();
    var today = _todayStr();
    var count = 0;
    for (var i = 1; i < rows.length; i++) {
      var reg = _cellDatetime(rows[i][12]);
      if (reg && String(reg).substring(0, 10) === today) count++;
    }
    return count;
  }

  // SELECT COUNT(*) FROM asistencias WHERE id_aprendiz = :id
  function contarPorAprendiz(idAprendiz) {
    var sheet = _getSheetSafe('asistencias');
    if (!sheet) return 0;
    var rows  = sheet.getDataRange().getValues();
    var count = 0;
    for (var i = 1; i < rows.length; i++) {
      if (rows[i][2] == idAprendiz) count++;
    }
    return count;
  }

  // =============================================================
  // ── Métodos de escritura ──────────────────────────────────────
  // =============================================================

  // INSERT INTO asistencias — metodo_registro siempre = 'qr'
  function crear(idAprendiz, idSesion, idToken, estado, horaRegistro,
                 minutosRetardo, latitud, longitud, ubicacionValida) {
    var sheet = _getSheet('asistencias');
    var newId = _nextId(sheet);
    var ahora = _nowStr();
    // A:id B:id_sesion C:id_aprendiz D:id_token_usado E:estado F:metodo_registro
    // G:hora_registro H:minutos_retardo I:ubicacion_valida J:latitud K:longitud
    // L:observacion M:registrado_en
    sheet.appendRow([
      newId,
      idSesion,
      idAprendiz,
      idToken        || '',
      estado,
      'qr',
      horaRegistro   || ahora.substring(11),
      minutosRetardo || 0,
      ubicacionValida ? 1 : 0,
      (latitud  !== null && latitud  !== undefined) ? latitud  : '',
      (longitud !== null && longitud !== undefined) ? longitud : '',
      '',
      ahora
    ]);
    return newId;
  }

  // UPDATE asistencias SET estado = :estado, observacion = :obs
  //  WHERE id_asistencia = :id
  function actualizarEstado(idAsistencia, nuevoEstado, observacion) {
    var sheet = _getSheet('asistencias');
    var rows  = sheet.getDataRange().getValues();
    for (var i = 1; i < rows.length; i++) {
      if (rows[i][0] != idAsistencia) continue;
      sheet.getRange(i + 1, 5).setValue(nuevoEstado);        // col E: estado
      sheet.getRange(i + 1, 12).setValue(observacion || ''); // col L: observacion
      return true;
    }
    return false;
  }

  // DELETE FROM asistencias WHERE id_asistencia = :id
  function eliminar(idAsistencia) {
    var sheet = _getSheet('asistencias');
    var rows  = sheet.getDataRange().getValues();
    for (var i = rows.length - 1; i >= 1; i--) {
      if (rows[i][0] == idAsistencia) {
        sheet.deleteRow(i + 1);
        return true;
      }
    }
    return false;
  }

  // =============================================================
  // ── Métodos de exportación ────────────────────────────────────
  // =============================================================

  // JOIN complejo para CSV. Devuelve filas con todos los campos del reporte.
  function listarParaExportar(idDocente, idAprendiz, idFicha, fechaInicio, fechaFin) {
    var sheet = _getSheetSafe('asistencias');
    if (!sheet) return [];

    var rows   = sheet.getDataRange().getValues();
    var sesMap = _loadSesionesMap();
    var apMap  = _loadAprendicesMap();
    var ficMap = _loadFichasMap();
    var result = [];

    for (var i = 1; i < rows.length; i++) {
      var obj = _rowToAsistencia(rows[i]);
      var ses = sesMap[obj.id_sesion];
      if (!ses) continue;
      var fic = ficMap[ses.id_ficha];
      if (!fic) continue;
      var ap  = apMap[obj.id_aprendiz];
      if (!ap) continue;

      if (idAprendiz && ap.id_aprendiz != idAprendiz)   continue;
      if (idFicha    && ses.id_ficha   != idFicha)      continue;
      if (idDocente  && fic.id_docente != idDocente)    continue;
      if (fechaInicio && ses.fecha_sesion < fechaInicio) continue;
      if (fechaFin    && ses.fecha_sesion > fechaFin)   continue;

      result.push({
        fecha_sesion     : ses.fecha_sesion,
        codigo_ficha     : fic.codigo_ficha,
        nombre_programa  : fic.nombre_programa,
        numero_documento : ap.numero_documento,
        nombres          : ap.nombres,
        apellidos        : ap.apellidos,
        estado           : obj.estado,
        hora_registro    : obj.hora_registro,
        hora_inicio_clase: ses.hora_inicio_clase,
        minutos_retardo  : obj.minutos_retardo,
        observacion      : obj.observacion || ''
      });
    }

    result.sort(function (a, b) {
      return a.fecha_sesion > b.fecha_sesion ? -1 : a.fecha_sesion < b.fecha_sesion ? 1 : 0;
    });
    return result;
  }

  // SELECT f.*, j.nombre AS nombre_jornada, d.nombres, d.apellidos
  //   FROM fichas f JOIN jornadas j JOIN docentes d
  //  WHERE f.activa = 1 AND (idDocente OR idFicha)
  function fichasParaReporte(idDocente, idFicha) {
    var ficSheet = _getSheetSafe('fichas');
    if (!ficSheet) return [];

    var ficRows = ficSheet.getDataRange().getValues();
    var jorMap  = _loadJornadasMap();
    var docMap  = _loadDocentesMap();
    var result  = [];

    for (var i = 1; i < ficRows.length; i++) {
      var f = ficRows[i];
      if (f[3] != 1 && f[3] !== '1') continue; // activa
      if (idDocente && f[6] != idDocente) continue;
      if (idFicha   && f[0] != idFicha)   continue;

      var jor = jorMap[f[5]] || {};
      var doc = docMap[f[6]] || {};
      result.push({
        id_ficha          : f[0],
        codigo_ficha      : String(f[1] || ''),
        nombre_programa   : String(f[2] || ''),
        activa            : f[3],
        nombre_materia    : f[4] || null,
        id_jornada        : f[5] || null,
        nombre_jornada    : jor.nombre    || null,
        id_docente        : f[6],
        nombres_docente   : doc.nombres   || null,
        apellidos_docente : doc.apellidos || null,
        id_trimestre      : f[7] || null
      });
    }
    return result;
  }

  // SELECT DISTINCT id_ficha, fecha_sesion, id_sesion, ...
  //   FROM sesiones_asistencia
  //  WHERE id_ficha IN (:idFichas) AND estado IN ('cerrada','abierta')
  //    AND fecha_sesion BETWEEN :fi AND :ff
  //  ORDER BY fecha_sesion ASC
  function sesionesParaReporte(idFichas, fechaInicio, fechaFin) {
    var sheet = _getSheetSafe('sesiones_asistencia');
    if (!sheet || !idFichas || idFichas.length === 0) return [];

    var fichaSet = {};
    idFichas.forEach(function (id) { fichaSet[id] = true; });

    var rows   = sheet.getDataRange().getValues();
    var result = [];
    var visto  = {};

    for (var i = 1; i < rows.length; i++) {
      var idFichaRow   = rows[i][1];
      var fechaSesion  = _cellDate(rows[i][3]);
      var estadoSesion = String(rows[i][4] || '');

      if (!fichaSet[idFichaRow]) continue;
      if (estadoSesion !== 'cerrada' && estadoSesion !== 'abierta') continue;
      if (fechaInicio && fechaSesion < fechaInicio) continue;
      if (fechaFin    && fechaSesion > fechaFin)   continue;

      var clave = idFichaRow + '_' + fechaSesion;
      if (visto[clave]) continue;
      visto[clave] = true;

      result.push({
        id_sesion              : rows[i][0],
        id_ficha               : idFichaRow,
        fecha_sesion           : fechaSesion,
        estado_sesion          : estadoSesion,
        nombre_materia         : String(rows[i][2] || ''),
        hora_inicio_clase      : _cellTime(rows[i][6]),
        limite_retardo_minutos : parseInt(rows[i][8], 10) || 20
      });
    }

    result.sort(function (a, b) {
      return a.fecha_sesion < b.fecha_sesion ? -1 : a.fecha_sesion > b.fecha_sesion ? 1 : 0;
    });
    return result;
  }

  // SELECT id_aprendiz, id_ficha, nombres, apellidos, numero_documento
  //   FROM aprendices
  //  WHERE activo = 1 AND id_ficha IN (:idFichas)
  //  ORDER BY apellidos ASC, nombres ASC
  function aprendicesPorFichas(idFichas) {
    var sheet = _getSheetSafe('aprendices');
    if (!sheet || !idFichas || idFichas.length === 0) return [];

    var fichaSet = {};
    idFichas.forEach(function (id) { fichaSet[id] = true; });

    var rows   = sheet.getDataRange().getValues();
    var result = [];

    for (var i = 1; i < rows.length; i++) {
      if (rows[i][6] != 1 && rows[i][6] !== '1') continue; // activo
      if (!fichaSet[rows[i][5]]) continue;                  // id_ficha
      result.push({
        id_aprendiz      : rows[i][0],
        nombres          : String(rows[i][1] || ''),
        apellidos        : String(rows[i][2] || ''),
        numero_documento : String(rows[i][3] || ''),
        id_ficha         : rows[i][5]
      });
    }

    result.sort(function (a, b) {
      var ap = (a.apellidos + ' ' + a.nombres).toLowerCase();
      var bp = (b.apellidos + ' ' + b.nombres).toLowerCase();
      return ap < bp ? -1 : ap > bp ? 1 : 0;
    });
    return result;
  }

  // SELECT a.id_aprendiz, s.id_ficha, s.fecha_sesion, a.estado
  //   FROM asistencias a JOIN sesiones_asistencia s
  //  WHERE s.id_ficha IN (:idFichas) AND fecha_sesion BETWEEN :fi AND :ff
  function asistenciasParaReporte(idFichas, fechaInicio, fechaFin) {
    var sheet = _getSheetSafe('asistencias');
    if (!sheet || !idFichas || idFichas.length === 0) return [];

    var fichaSet = {};
    idFichas.forEach(function (id) { fichaSet[id] = true; });

    var sesMap = _loadSesionesMap();
    var rows   = sheet.getDataRange().getValues();
    var result = [];

    for (var i = 1; i < rows.length; i++) {
      var ses = sesMap[rows[i][1]];
      if (!ses) continue;
      if (!fichaSet[ses.id_ficha]) continue;
      if (fechaInicio && ses.fecha_sesion < fechaInicio) continue;
      if (fechaFin    && ses.fecha_sesion > fechaFin)   continue;
      result.push({
        id_aprendiz  : rows[i][2],
        id_ficha     : ses.id_ficha,
        fecha_sesion : ses.fecha_sesion,
        estado       : String(rows[i][4])
      });
    }
    return result;
  }

  // ── API pública ───────────────────────────────────────────────
  return {
    obtenerPorId            : obtenerPorId,
    existeEnSesion          : existeEnSesion,
    aprendizPerteneceAFicha : aprendizPerteneceAFicha,
    historialAprendiz       : historialAprendiz,
    contarHoy               : contarHoy,
    contarPorAprendiz       : contarPorAprendiz,
    crear                   : crear,
    actualizarEstado        : actualizarEstado,
    eliminar                : eliminar,
    listarParaExportar      : listarParaExportar,
    fichasParaReporte       : fichasParaReporte,
    sesionesParaReporte     : sesionesParaReporte,
    aprendicesPorFichas     : aprendicesPorFichas,
    asistenciasParaReporte  : asistenciasParaReporte
  };

})();
