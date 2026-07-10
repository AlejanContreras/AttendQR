// =============================================================
// AttendQR — SESION_SesionRepository
// =============================================================
// Capa de datos del módulo Sesión. Accede a Google Sheets.
// Replica: Src/Repositories/SesionRepository.php  (PDO → SpreadsheetApp)
//
// Usa AUTH_SPREADSHEET_ID definido en AUTH_AuthRepository.gs.
//
// Estructura de hojas:
//
//   sesiones_asistencia:
//     A:id_sesion  B:id_ficha  C:nombre_materia  D:fecha_sesion
//     E:estado_sesion  F:hora_apertura  G:hora_inicio_clase  H:hora_cierre
//     I:limite_retardo_minutos  J:duracion_maxima_minutos  K:rotacion_qr_segundos
//     L:ubicacion_activa  M:lat_docente  N:lng_docente  O:accuracy_docente
//
//   asistencias:
//     A:id_asistencia  B:id_sesion  C:id_aprendiz  D:estado
//     E:metodo_registro  F:hora_registro  G:minutos_retardo
//     H:observacion  I:registrado_en
//
//   tokens_qr (accedida por helpers de QR — pendiente QR_QrRepository):
//     A:id_token  B:id_sesion  C:token  D:expira_en  E:activo
//
// Hojas ya definidas en fases anteriores:
//   fichas   : A:id_ficha B:codigo_ficha C:nombre_programa D:activa
//              E:nombre_materia F:id_jornada G:id_docente H:id_trimestre
//   jornadas : A:id_jornada B:nombre C:hora_inicio D:hora_fin E:minutos_gracia
//   docentes : A:id_docente B:nombres C:apellidos D:correo E:password_hash F:activo G:creado_en
//   aprendices: A:id B:nombres C:apellidos D:numero_documento E:password_hash
//               F:id_ficha G:activo H:cuenta_activada
// =============================================================

var SesionRepository = (function () {

  // ── Helpers de acceso ────────────────────────────────────

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

  // ── Normalización de valores de celda ────────────────────
  // Sheets puede devolver Date objects cuando reconoce el formato de la celda.

  function _cellDate(val) {
    if (!val && val !== 0) return null;
    if (val === '') return null;
    if (val instanceof Date) {
      return Utilities.formatDate(val, Session.getScriptTimeZone(), 'yyyy-MM-dd');
    }
    return String(val).substring(0, 10);
  }

  function _cellTime(val) {
    if (!val && val !== 0) return null;
    if (val === '') return null;
    if (val instanceof Date) {
      return Utilities.formatDate(val, Session.getScriptTimeZone(), 'HH:mm:ss');
    }
    return String(val);
  }

  function _cellDatetime(val) {
    if (!val && val !== 0) return null;
    if (val === '') return null;
    if (val instanceof Date) {
      return Utilities.formatDate(val, Session.getScriptTimeZone(), 'yyyy-MM-dd HH:mm:ss');
    }
    return String(val);
  }

  function _nowStr() {
    return Utilities.formatDate(new Date(), Session.getScriptTimeZone(), 'yyyy-MM-dd HH:mm:ss');
  }

  function _todayStr() {
    return Utilities.formatDate(new Date(), Session.getScriptTimeZone(), 'yyyy-MM-dd');
  }

  // ── Mapeadores ────────────────────────────────────────────

  function _rowToSesionBase(row) {
    return {
      id_sesion               : row[0],
      id_ficha                : row[1],
      nombre_materia          : row[2] !== '' && row[2] !== null ? String(row[2]) : null,
      fecha_sesion            : _cellDate(row[3]),
      estado_sesion           : String(row[4]),
      hora_apertura           : _cellDatetime(row[5]),
      hora_inicio_clase       : _cellTime(row[6]),
      hora_cierre             : row[7] !== '' && row[7] !== null ? _cellDatetime(row[7]) : null,
      limite_retardo_minutos  : parseInt(row[8], 10)  || 5,
      duracion_maxima_minutos : parseInt(row[9], 10)  || 20,
      rotacion_qr_segundos    : row[10] !== '' && row[10] !== null ? parseInt(row[10], 10) : 30,
      ubicacion_activa        : row[11] == 1 || row[11] === true,
      lat_docente             : row[12] !== '' && row[12] !== null ? parseFloat(row[12]) : null,
      lng_docente             : row[13] !== '' && row[13] !== null ? parseFloat(row[13]) : null,
      accuracy_docente        : row[14] !== '' && row[14] !== null ? parseFloat(row[14]) : null
    };
  }

  // ── Caches de hojas relacionadas ─────────────────────────

  function _loadFichasMap() {
    var sheet = _getSheetSafe('fichas');
    if (!sheet) return {};
    var rows = sheet.getDataRange().getValues();
    var map  = {};
    for (var i = 1; i < rows.length; i++) {
      map[rows[i][0]] = rows[i];
    }
    return map;
  }

  function _loadDocentesMap() {
    var sheet = _getSheetSafe('docentes');
    if (!sheet) return {};
    var rows = sheet.getDataRange().getValues();
    var map  = {};
    for (var i = 1; i < rows.length; i++) {
      map[rows[i][0]] = rows[i];
    }
    return map;
  }

  function _loadJornadasMap() {
    var sheet = _getSheetSafe('jornadas');
    if (!sheet) return {};
    var rows = sheet.getDataRange().getValues();
    var map  = {};
    for (var i = 1; i < rows.length; i++) {
      map[rows[i][0]] = rows[i];
    }
    return map;
  }

  // Devuelve mapa: id_ficha → total aprendices activos
  function _loadAprendicesActivosPorFicha() {
    var sheet = _getSheetSafe('aprendices');
    if (!sheet) return {};
    var rows = sheet.getDataRange().getValues();
    var map  = {};
    for (var i = 1; i < rows.length; i++) {
      var idFicha = rows[i][5];
      var activo  = rows[i][6];
      if (activo == 1) {
        map[idFicha] = (map[idFicha] || 0) + 1;
      }
    }
    return map;
  }

  // Devuelve mapa: id_sesion → { total, presentes, retardos, ausentes_marcados, excusas }
  function _loadAsistenciasAgregadas(filtroSesiones) {
    var sheet = _getSheetSafe('asistencias');
    var agg   = {};
    if (!sheet) return agg;
    var rows  = sheet.getDataRange().getValues();
    for (var i = 1; i < rows.length; i++) {
      var idSesion = rows[i][1];
      if (filtroSesiones && filtroSesiones[idSesion] === undefined) continue;
      if (!agg[idSesion]) {
        agg[idSesion] = { total_registrados: 0, presentes: 0, retardos: 0, ausentes_marcados: 0, excusas: 0 };
      }
      agg[idSesion].total_registrados++;
      var estado = String(rows[i][3]);
      if (estado === 'presente') agg[idSesion].presentes++;
      else if (estado === 'retardo')  agg[idSesion].retardos++;
      else if (estado === 'ausente')  agg[idSesion].ausentes_marcados++;
      else if (estado === 'excusa')   agg[idSesion].excusas++;
    }
    return agg;
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

  // ── Enriquecer sesion con datos de fichas + docentes ────

  function _enriquecerConFichaDocente(sesion, fichasMap, docentesMap) {
    var fichaRow  = fichasMap[sesion.id_ficha];
    if (fichaRow) {
      sesion.codigo_ficha    = String(fichaRow[1]);
      sesion.nombre_programa = String(fichaRow[2]);
      sesion.id_docente      = fichaRow[6];
      var docRow = docentesMap[fichaRow[6]];
      if (docRow) {
        sesion.nombre_docente   = String(docRow[1]);
        sesion.apellido_docente = String(docRow[2]);
      }
    }
    return sesion;
  }

  // ── obtenerFichaConJornada ───────────────────────────────
  // JOIN fichas + jornadas por id_ficha.
  // Replica: SesionRepository.obtenerFichaConJornada()
  function obtenerFichaConJornada(idFicha) {
    var fichasMap   = _loadFichasMap();
    var jornadasMap = _loadJornadasMap();
    var fichaRow    = fichasMap[idFicha];
    if (!fichaRow) return null;

    var result = {
      id_ficha       : fichaRow[0],
      codigo_ficha   : String(fichaRow[1]),
      nombre_programa: String(fichaRow[2]),
      activa         : fichaRow[3],
      id_docente     : fichaRow[6]
    };

    var jornadaRow = jornadasMap[fichaRow[5]];
    if (jornadaRow) {
      result.id_jornada     = jornadaRow[0];
      result.nombre_jornada = String(jornadaRow[1]);
      result.hora_inicio    = _cellTime(jornadaRow[2]);
      result.hora_fin       = _cellTime(jornadaRow[3]);
      result.minutos_gracia = jornadaRow[4] !== '' && jornadaRow[4] !== null
                               ? parseInt(jornadaRow[4], 10) : 5;
    }

    return result;
  }

  // ── existeAbiertaParaFicha ───────────────────────────────
  // SELECT COUNT(*) FROM sesiones_asistencia
  //   WHERE id_ficha=X AND fecha_sesion=Y AND estado_sesion='abierta'
  function existeAbiertaParaFicha(idFicha, fecha) {
    var sheet = _getSheetSafe('sesiones_asistencia');
    if (!sheet) return false;
    var rows = sheet.getDataRange().getValues();
    for (var i = 1; i < rows.length; i++) {
      if (rows[i][1] == idFicha &&
          _cellDate(rows[i][3]) === fecha &&
          String(rows[i][4]) === 'abierta') {
        return true;
      }
    }
    return false;
  }

  // ── obtenerPorId ─────────────────────────────────────────
  // SELECT sesion + ficha + docente WHERE id_sesion = :id
  function obtenerPorId(idSesion) {
    var sheet = _getSheetSafe('sesiones_asistencia');
    if (!sheet) return null;
    var rows = sheet.getDataRange().getValues();
    for (var i = 1; i < rows.length; i++) {
      if (rows[i][0] == idSesion) {
        var sesion     = _rowToSesionBase(rows[i]);
        var fichasMap  = _loadFichasMap();
        var docentesMap = _loadDocentesMap();
        return _enriquecerConFichaDocente(sesion, fichasMap, docentesMap);
      }
    }
    return null;
  }

  // ── obtenerDetalle ────────────────────────────────────────
  // Como obtenerPorId + COUNT(asistencias) AS total_asistencias
  function obtenerDetalle(idSesion) {
    var sesion = obtenerPorId(idSesion);
    if (!sesion) return null;

    var asistSheet = _getSheetSafe('asistencias');
    var total      = 0;
    if (asistSheet) {
      var rows = asistSheet.getDataRange().getValues();
      for (var i = 1; i < rows.length; i++) {
        if (rows[i][1] == idSesion) total++;
      }
    }
    sesion.total_asistencias = total;
    return sesion;
  }

  // ── obtenerActivaPorFicha ─────────────────────────────────
  // SELECT sesion + ficha WHERE id_ficha=X AND estado_sesion='abierta' LIMIT 1
  function obtenerActivaPorFicha(idFicha) {
    var sheet = _getSheetSafe('sesiones_asistencia');
    if (!sheet) return null;
    var rows      = sheet.getDataRange().getValues();
    var fichasMap = _loadFichasMap();
    for (var i = 1; i < rows.length; i++) {
      if (rows[i][1] == idFicha && String(rows[i][4]) === 'abierta') {
        var sesion    = _rowToSesionBase(rows[i]);
        var fichaRow  = fichasMap[sesion.id_ficha];
        if (fichaRow) {
          sesion.codigo_ficha    = String(fichaRow[1]);
          sesion.nombre_programa = String(fichaRow[2]);
        }
        return sesion;
      }
    }
    return null;
  }

  // ── listar ───────────────────────────────────────────────
  // SELECT sesion+ficha+agregados_asistencia [WHERE id_ficha / estado]
  // ORDER BY fecha_sesion DESC, hora_apertura DESC
  function listar(idFicha, estado) {
    var sheet = _getSheetSafe('sesiones_asistencia');
    if (!sheet) return [];
    var rows      = sheet.getDataRange().getValues();
    var fichasMap = _loadFichasMap();

    // Pre-filtrar ids de sesión para optimizar carga de asistencias
    var sesionIds = {};
    var filtered  = [];
    for (var i = 1; i < rows.length; i++) {
      if (idFicha !== null && idFicha !== undefined && rows[i][1] != idFicha) continue;
      if (estado  !== null && estado  !== undefined && String(rows[i][4]) !== estado) continue;
      sesionIds[rows[i][0]] = true;
      filtered.push(rows[i]);
    }

    var agg           = _loadAsistenciasAgregadas(sesionIds);
    var aprendizCount = _loadAprendicesActivosPorFicha();

    var result = [];
    for (var j = 0; j < filtered.length; j++) {
      var row      = filtered[j];
      var sesion   = _rowToSesionBase(row);
      var fichaRow = fichasMap[sesion.id_ficha];
      if (fichaRow) {
        sesion.codigo_ficha    = String(fichaRow[1]);
        sesion.nombre_programa = String(fichaRow[2]);
      }
      var a = agg[sesion.id_sesion] || { total_registrados:0, presentes:0, retardos:0, ausentes_marcados:0, excusas:0 };
      sesion.total_aprendices  = aprendizCount[sesion.id_ficha] || 0;
      sesion.total_registrados = a.total_registrados;
      sesion.presentes         = a.presentes;
      sesion.retardos          = a.retardos;
      sesion.ausentes_marcados = a.ausentes_marcados;
      sesion.excusas           = a.excusas;
      result.push(sesion);
    }

    // ORDER BY fecha_sesion DESC, hora_apertura DESC
    result.sort(function (a, b) {
      var fd = (b.fecha_sesion || '').localeCompare(a.fecha_sesion || '');
      if (fd !== 0) return fd;
      return (b.hora_apertura || '').localeCompare(a.hora_apertura || '');
    });

    return result;
  }

  // ── listarPorDocente ─────────────────────────────────────
  // SELECT sesion+ficha+agregados WHERE f.id_docente = :id [AND estado]
  // ORDER BY fecha_sesion DESC, hora_apertura DESC
  function listarPorDocente(idDocente, estado) {
    var sheet = _getSheetSafe('sesiones_asistencia');
    if (!sheet) return [];
    var rows      = sheet.getDataRange().getValues();
    var fichasMap = _loadFichasMap();

    var sesionIds = {};
    var filtered  = [];
    for (var i = 1; i < rows.length; i++) {
      var fichaRow = fichasMap[rows[i][1]];
      if (!fichaRow || fichaRow[6] != idDocente) continue;
      if (estado !== null && estado !== undefined && String(rows[i][4]) !== estado) continue;
      sesionIds[rows[i][0]] = true;
      filtered.push(rows[i]);
    }

    var agg           = _loadAsistenciasAgregadas(sesionIds);
    var aprendizCount = _loadAprendicesActivosPorFicha();

    var result = [];
    for (var j = 0; j < filtered.length; j++) {
      var row     = filtered[j];
      var sesion  = _rowToSesionBase(row);
      var fila    = fichasMap[sesion.id_ficha];
      if (fila) {
        sesion.codigo_ficha    = String(fila[1]);
        sesion.nombre_programa = String(fila[2]);
      }
      var a = agg[sesion.id_sesion] || { total_registrados:0, presentes:0, retardos:0, ausentes_marcados:0, excusas:0 };
      sesion.total_aprendices  = aprendizCount[sesion.id_ficha] || 0;
      sesion.total_registrados = a.total_registrados;
      sesion.presentes         = a.presentes;
      sesion.retardos          = a.retardos;
      sesion.ausentes_marcados = a.ausentes_marcados;
      sesion.excusas           = a.excusas;
      result.push(sesion);
    }

    result.sort(function (a, b) {
      var fd = (b.fecha_sesion || '').localeCompare(a.fecha_sesion || '');
      if (fd !== 0) return fd;
      return (b.hora_apertura || '').localeCompare(a.hora_apertura || '');
    });

    return result;
  }

  // ── crear ─────────────────────────────────────────────────
  // INSERT INTO sesiones_asistencia (...) VALUES (..., 'abierta', NOW(3), ...)
  function crear(
    idFicha, fechaSesion, horaInicioClase, nombreMateria,
    limiteRetardoMinutos, duracionMaximaMinutos,
    ubicacionActiva, latDocente, lngDocente, accuracyDocente
  ) {
    var sheet    = _getSheet('sesiones_asistencia');
    var newId    = _nextId(sheet);
    var horaAp   = _nowStr();
    // Columnas: A=id_sesion B=id_ficha C=nombre_materia D=fecha_sesion E=estado_sesion
    //           F=hora_apertura G=hora_inicio_clase H=hora_cierre I=limite_retardo_minutos
    //           J=duracion_maxima_minutos K=rotacion_qr_segundos L=ubicacion_activa
    //           M=lat_docente N=lng_docente O=accuracy_docente
    sheet.appendRow([
      newId,
      idFicha,
      nombreMateria || '',
      fechaSesion,
      'abierta',
      horaAp,
      horaInicioClase,
      '',
      limiteRetardoMinutos,
      duracionMaximaMinutos,
      30,                         // rotacion_qr_segundos por defecto
      ubicacionActiva ? 1 : 0,
      latDocente  !== null && latDocente  !== undefined ? latDocente  : '',
      lngDocente  !== null && lngDocente  !== undefined ? lngDocente  : '',
      accuracyDocente !== null && accuracyDocente !== undefined ? accuracyDocente : ''
    ]);
    return newId;
  }

  // ── cerrar ────────────────────────────────────────────────
  // UPDATE sesiones_asistencia SET estado_sesion='cerrada', hora_cierre=:hora WHERE id_sesion=:id
  function cerrar(idSesion, horaCierre) {
    var sheet = _getSheet('sesiones_asistencia');
    var rows  = sheet.getDataRange().getValues();
    for (var i = 1; i < rows.length; i++) {
      if (rows[i][0] == idSesion) {
        var rowNum = i + 1;
        sheet.getRange(rowNum, 5).setValue('cerrada');   // col E
        sheet.getRange(rowNum, 8).setValue(horaCierre);  // col H
        return 1;
      }
    }
    return 0;
  }

  // ── cerrarVencidas ────────────────────────────────────────
  // UPDATE sesiones SET estado='cerrada', hora_cierre=NOW()
  // WHERE estado='abierta' AND TIMESTAMP(fecha_sesion, hora_inicio_clase) + duracion_maxima < NOW()
  // Se ejecuta de forma lazy antes de consultas de estado.
  function cerrarVencidas() {
    var sheet = _getSheetSafe('sesiones_asistencia');
    if (!sheet) return 0;
    var rows  = sheet.getDataRange().getValues();
    var now   = new Date();
    var tz    = Session.getScriptTimeZone();
    var count = 0;

    for (var i = 1; i < rows.length; i++) {
      if (String(rows[i][4]) !== 'abierta') continue;

      var fechaStr   = _cellDate(rows[i][3]);
      var horaStr    = _cellTime(rows[i][6]);
      var durMaxima  = parseInt(rows[i][9], 10);

      if (!fechaStr || !horaStr || isNaN(durMaxima)) continue;

      var pF = fechaStr.split('-');
      var pH = horaStr.split(':');
      var inicio = new Date(
        parseInt(pF[0], 10),
        parseInt(pF[1], 10) - 1,
        parseInt(pF[2], 10),
        parseInt(pH[0], 10),
        parseInt(pH[1], 10),
        parseInt(pH[2] || 0, 10)
      );
      var limite = new Date(inicio.getTime() + durMaxima * 60 * 1000);

      if (limite < now) {
        var rowNum    = i + 1;
        var horaCierre = Utilities.formatDate(now, tz, 'yyyy-MM-dd HH:mm:ss');
        sheet.getRange(rowNum, 5).setValue('cerrada');
        sheet.getRange(rowNum, 8).setValue(horaCierre);
        count++;
      }
    }
    return count;
  }

  // ── contarActivas ─────────────────────────────────────────
  // SELECT COUNT(*) FROM sesiones_asistencia WHERE estado_sesion='abierta'
  function contarActivas() {
    var sheet = _getSheetSafe('sesiones_asistencia');
    if (!sheet) return 0;
    var rows  = sheet.getDataRange().getValues();
    var count = 0;
    for (var i = 1; i < rows.length; i++) {
      if (String(rows[i][4]) === 'abierta') count++;
    }
    return count;
  }

  // ── contarActivasPorDocente ───────────────────────────────
  // JOIN fichas: WHERE f.id_docente=:id AND estado_sesion='abierta'
  function contarActivasPorDocente(idDocente) {
    var sheet = _getSheetSafe('sesiones_asistencia');
    if (!sheet) return 0;
    var rows      = sheet.getDataRange().getValues();
    var fichasMap = _loadFichasMap();
    var count     = 0;
    for (var i = 1; i < rows.length; i++) {
      if (String(rows[i][4]) !== 'abierta') continue;
      var fichaRow = fichasMap[rows[i][1]];
      if (fichaRow && fichaRow[6] == idDocente) count++;
    }
    return count;
  }

  // ── contarPorTrimestre ────────────────────────────────────
  // JOIN fichas: WHERE f.id_trimestre=:id (todas las sesiones, sin filtrar estado)
  function contarPorTrimestre(idTrimestre) {
    var sheet = _getSheetSafe('sesiones_asistencia');
    if (!sheet) return 0;
    var rows      = sheet.getDataRange().getValues();
    var fichasMap = _loadFichasMap();
    var count     = 0;
    for (var i = 1; i < rows.length; i++) {
      var fichaRow = fichasMap[rows[i][1]];
      if (fichaRow && fichaRow[7] == idTrimestre) count++;
    }
    return count;
  }

  // ── obtenerAsistenciasDeSesion ────────────────────────────
  // SELECT asistencias + aprendiz WHERE id_sesion=:id
  // ORDER BY estado ASC, apellidos ASC, nombres ASC
  function obtenerAsistenciasDeSesion(idSesion) {
    var aSheet = _getSheetSafe('asistencias');
    var apSheet = _getSheetSafe('aprendices');
    if (!aSheet) return [];

    // Cache de aprendices
    var apMap = {};
    if (apSheet) {
      var apRows = apSheet.getDataRange().getValues();
      for (var k = 1; k < apRows.length; k++) {
        apMap[apRows[k][0]] = apRows[k];
      }
    }

    var rows   = aSheet.getDataRange().getValues();
    var result = [];
    for (var i = 1; i < rows.length; i++) {
      if (rows[i][1] != idSesion) continue;
      var reg = {
        id_asistencia   : rows[i][0],
        id_aprendiz     : rows[i][2],
        estado          : String(rows[i][3]),
        metodo_registro : rows[i][4] !== '' ? String(rows[i][4]) : null,
        hora_registro   : rows[i][5] !== '' ? _cellDatetime(rows[i][5]) : null,
        minutos_retardo : rows[i][6] !== '' ? parseInt(rows[i][6], 10) : null,
        observacion     : rows[i][7] !== '' ? String(rows[i][7]) : null,
        registrado_en   : rows[i][8] !== '' ? _cellDatetime(rows[i][8]) : null
      };
      var apRow = apMap[rows[i][2]];
      if (apRow) {
        reg.nombres          = String(apRow[1]);
        reg.apellidos        = String(apRow[2]);
        reg.numero_documento = String(apRow[3]);
      }
      result.push(reg);
    }

    // ORDER BY estado ASC, apellidos ASC, nombres ASC
    result.sort(function (a, b) {
      var es = (a.estado || '').localeCompare(b.estado || '');
      if (es !== 0) return es;
      var ap = (a.apellidos || '').localeCompare(b.apellidos || '');
      if (ap !== 0) return ap;
      return (a.nombres || '').localeCompare(b.nombres || '');
    });

    return result;
  }

  // ── obtenerEstadisticasDeSesion ───────────────────────────
  // SELECT sesion + ficha + SUM/COUNT asistencias por estado
  function obtenerEstadisticasDeSesion(idSesion) {
    var sesSheet = _getSheetSafe('sesiones_asistencia');
    if (!sesSheet) return null;
    var rows = sesSheet.getDataRange().getValues();

    var sesRow = null;
    for (var i = 1; i < rows.length; i++) {
      if (rows[i][0] == idSesion) { sesRow = rows[i]; break; }
    }
    if (!sesRow) return null;

    var fichasMap = _loadFichasMap();
    var fichaRow  = fichasMap[sesRow[1]];

    // Contar aprendices activos de la ficha
    var aprendizCount = _loadAprendicesActivosPorFicha();
    var totalAprendices = aprendizCount[sesRow[1]] || 0;

    // Agregar asistencias
    var agg = _loadAsistenciasAgregadas({ [idSesion]: true });
    var a   = agg[idSesion] || { total_registrados: 0, presentes: 0, retardos: 0, ausentes_marcados: 0, excusas: 0 };

    return {
      id_sesion           : sesRow[0],
      fecha_sesion        : _cellDate(sesRow[3]),
      estado_sesion       : String(sesRow[4]),
      hora_inicio_clase   : _cellTime(sesRow[6]),
      limite_retardo_minutos: parseInt(sesRow[8], 10) || 5,
      codigo_ficha        : fichaRow ? String(fichaRow[1]) : null,
      nombre_programa     : fichaRow ? String(fichaRow[2]) : null,
      total_aprendices    : totalAprendices,
      total_registrados   : a.total_registrados,
      presentes           : a.presentes,
      retardos            : a.retardos,
      ausentes_marcados   : a.ausentes_marcados,
      excusas             : a.excusas
    };
  }

  // ── obtenerHistorialPorFicha ──────────────────────────────
  // SELECT sesion + COUNT/SUM asistencias WHERE id_ficha=X [+ filtros]
  // ORDER BY fecha_sesion DESC
  function obtenerHistorialPorFicha(idFicha, fechaInicio, fechaFin, estado) {
    var sheet = _getSheetSafe('sesiones_asistencia');
    if (!sheet) return [];
    var rows = sheet.getDataRange().getValues();

    var sesionIds = {};
    var filtered  = [];
    for (var i = 1; i < rows.length; i++) {
      if (rows[i][1] != idFicha) continue;
      var fecha = _cellDate(rows[i][3]) || '';
      if (fechaInicio !== null && fechaInicio !== undefined && fecha < fechaInicio) continue;
      if (fechaFin    !== null && fechaFin    !== undefined && fecha > fechaFin)    continue;
      if (estado      !== null && estado      !== undefined && String(rows[i][4]) !== estado) continue;
      sesionIds[rows[i][0]] = true;
      filtered.push(rows[i]);
    }

    var agg    = _loadAsistenciasAgregadas(sesionIds);
    var result = [];

    for (var j = 0; j < filtered.length; j++) {
      var row    = filtered[j];
      var a      = agg[row[0]] || { total_registrados: 0, presentes: 0, retardos: 0, excusas: 0 };
      result.push({
        id_sesion              : row[0],
        fecha_sesion           : _cellDate(row[3]),
        estado_sesion          : String(row[4]),
        hora_apertura          : _cellDatetime(row[5]),
        hora_cierre            : row[7] !== '' && row[7] !== null ? _cellDatetime(row[7]) : null,
        hora_inicio_clase      : _cellTime(row[6]),
        limite_retardo_minutos : parseInt(row[8], 10) || 5,
        total_registrados      : a.total_registrados,
        presentes              : a.presentes,
        retardos               : a.retardos,
        excusas                : a.excusas
      });
    }

    // ORDER BY fecha_sesion DESC
    result.sort(function (a, b) {
      return (b.fecha_sesion || '').localeCompare(a.fecha_sesion || '');
    });

    return result;
  }

  return {
    obtenerFichaConJornada      : obtenerFichaConJornada,
    existeAbiertaParaFicha      : existeAbiertaParaFicha,
    obtenerPorId                : obtenerPorId,
    obtenerDetalle              : obtenerDetalle,
    obtenerActivaPorFicha       : obtenerActivaPorFicha,
    listar                      : listar,
    listarPorDocente            : listarPorDocente,
    crear                       : crear,
    cerrar                      : cerrar,
    cerrarVencidas              : cerrarVencidas,
    contarActivas               : contarActivas,
    contarActivasPorDocente     : contarActivasPorDocente,
    contarPorTrimestre          : contarPorTrimestre,
    obtenerAsistenciasDeSesion  : obtenerAsistenciasDeSesion,
    obtenerEstadisticasDeSesion : obtenerEstadisticasDeSesion,
    obtenerHistorialPorFicha    : obtenerHistorialPorFicha
  };

})();
