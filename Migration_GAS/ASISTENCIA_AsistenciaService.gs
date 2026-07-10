// =============================================================
// AttendQR — ASISTENCIA_AsistenciaService
// =============================================================
// Lógica de negocio del módulo Asistencia.
// Replica: Src/Services/AsistenciaService.php
//
// Dependencias:
//   AsistenciaRepository — obtenerPorId, existeEnSesion,
//     aprendizPerteneceAFicha, historialAprendiz, contarHoy,
//     contarPorAprendiz, crear, actualizarEstado, eliminar,
//     listarParaExportar, fichasParaReporte, sesionesParaReporte,
//     aprendicesPorFichas, asistenciasParaReporte
//   QrService            — validar, cerrarVencidas
//   XlsxExport           — crear, S_* (constantes de estilo)
//
// Constantes (idénticas al PHP):
//   RADIO_VALIDACION_METROS = 30
//   MAX_ACCURACY_METROS     = 50
// =============================================================

var AsistenciaService = (function () {

  var RADIO_VALIDACION_METROS = 30;
  var MAX_ACCURACY_METROS     = 50;

  // ── registrarPorQr ────────────────────────────────────────────
  // Flujo de 7 pasos para registrar asistencia vía QR.
  // Replica: AsistenciaService.registrarPorQr()
  //
  // Parámetros:
  //   idAprendiz  — id del aprendiz autenticado
  //   tokenValor  — código QR escaneado
  //   latitud     — float o null
  //   longitud    — float o null
  //   accuracy    — float (metros) o null
  //
  // Errores codificados en el mensaje para que el controller los maneje:
  //   '|409|' — registro duplicado
  //   '|428|' — geolocalización requerida pero no enviada
  //   '|451|' — fuera del radio de validación
  //   '|422|' — tiempo de registro expirado
  function registrarPorQr(idAprendiz, tokenValor, latitud, longitud, accuracy) {

    // Paso 1: validar el token QR
    var tokenData = QrService.validar(tokenValor, idAprendiz);
    var idSesion  = tokenData.id_sesion;
    var idToken   = tokenData.id_token;

    // Paso 2: obtener la sesión
    var sesion = SesionRepository.obtenerPorId(idSesion);
    if (!sesion) throw new Error('Sesión no encontrada.');

    // Paso 3: verificar que el aprendiz pertenece a la ficha de la sesión
    if (!AsistenciaRepository.aprendizPerteneceAFicha(idAprendiz, sesion.id_ficha)) {
      throw new Error('El aprendiz no pertenece a la ficha de esta sesión.');
    }

    // Paso 4: verificar que no ha registrado asistencia ya en esta sesión
    if (AsistenciaRepository.existeEnSesion(idAprendiz, idSesion)) {
      throw new Error('|409|Ya registraste tu asistencia en esta sesión.');
    }

    // Paso 5: validación de geolocalización
    var ubicacionValida = false;
    if (parseInt(sesion.ubicacion_activa, 10) === 1) {
      if (latitud === null || latitud === undefined ||
          longitud === null || longitud === undefined) {
        throw new Error('|428|Se requiere geolocalización para registrar asistencia.');
      }
      if (accuracy !== null && accuracy !== undefined &&
          parseFloat(accuracy) > MAX_ACCURACY_METROS) {
        throw new Error('|428|La precisión GPS es insuficiente (' + accuracy + 'm). Intenta de nuevo.');
      }
      var distancia = _haversine(
        parseFloat(sesion.lat_docente), parseFloat(sesion.lng_docente),
        parseFloat(latitud),            parseFloat(longitud)
      );
      if (distancia > RADIO_VALIDACION_METROS) {
        throw new Error('|451|Estás fuera del radio de validación (' +
                        Math.round(distancia) + 'm del docente).');
      }
      ubicacionValida = true;
    }

    // Paso 6: clasificar estado (presente / retardo) según hora de inicio de clase
    var ahora         = new Date();
    var horaRegistro  = Utilities.formatDate(ahora, Session.getScriptTimeZone(), 'HH:mm:ss');
    var clasificacion = _clasificar(sesion, horaRegistro);
    var estado        = clasificacion[0];
    var minutosRetardo= clasificacion[1];

    // Paso 7: persistir la asistencia
    var newId = AsistenciaRepository.crear(
      idAprendiz, idSesion, idToken, estado, horaRegistro,
      minutosRetardo, latitud, longitud, ubicacionValida
    );

    return {
      id_asistencia  : newId,
      id_aprendiz    : idAprendiz,
      id_sesion      : idSesion,
      estado         : estado,
      hora_registro  : horaRegistro,
      minutos_retardo: minutosRetardo
    };
  }

  // ── consultar ─────────────────────────────────────────────────
  // Replica: AsistenciaService.consultar()
  function consultar(idAsistencia, usuario) {
    var asistencia = AsistenciaRepository.obtenerPorId(idAsistencia);
    if (!asistencia) throw new Error('Asistencia no encontrada.');

    // Aprendiz solo puede ver sus propias asistencias
    if (usuario.rol === 'aprendiz' && asistencia.id_aprendiz != usuario.id) {
      throw new Error('No tienes permiso para consultar esta asistencia.');
    }
    return asistencia;
  }

  // ── historial ─────────────────────────────────────────────────
  // Replica: AsistenciaService.historial()
  // Retorna: { id_aprendiz, registros[], total, resumen, filtros }
  function historial(idAprendiz, fechaInicio, fechaFin) {
    var registros = AsistenciaRepository.historialAprendiz(idAprendiz, fechaInicio, fechaFin);

    var resumen = { presentes: 0, retardos: 0, ausentes: 0, excusas: 0 };
    registros.forEach(function (r) {
      if (r.estado === 'presente') resumen.presentes++;
      else if (r.estado === 'retardo') resumen.retardos++;
      else if (r.estado === 'ausente') resumen.ausentes++;
      else if (r.estado === 'excusa')  resumen.excusas++;
    });

    return {
      id_aprendiz : idAprendiz,
      registros   : registros,
      total       : registros.length,
      resumen     : resumen,
      filtros     : { fecha_inicio: fechaInicio || null, fecha_fin: fechaFin || null }
    };
  }

  // ── validar ───────────────────────────────────────────────────
  // Valida un token QR sin registrar asistencia (paso previo de confirmación).
  // Replica: AsistenciaService.validar()
  function validar(tokenValor, usuario) {
    // La validación completa (incluyendo cerrar vencidas) la hace QrService
    return QrService.validar(tokenValor, usuario.id);
  }

  // ── cambiarEstado ─────────────────────────────────────────────
  // Solo docente puede cambiar estados. Transiciones permitidas:
  //   ausente → excusa  |  excusa → ausente
  // Replica: AsistenciaService.cambiarEstado()
  function cambiarEstado(idAsistencia, nuevoEstado, observacion, usuario) {
    if (usuario.rol !== 'docente') {
      throw new Error('Solo el docente puede cambiar el estado de una asistencia.');
    }

    var estadosValidos = ['ausente', 'excusa'];
    if (estadosValidos.indexOf(nuevoEstado) === -1) {
      throw new Error(
        "Estado '" + nuevoEstado + "' no válido. Solo se permite: " + estadosValidos.join(', ') + '.'
      );
    }

    var asistencia = AsistenciaRepository.obtenerPorId(idAsistencia);
    if (!asistencia) throw new Error('Asistencia no encontrada.');

    // Verificar transición permitida
    var estadoActual = asistencia.estado;
    var transOk =
      (estadoActual === 'ausente' && nuevoEstado === 'excusa') ||
      (estadoActual === 'excusa'  && nuevoEstado === 'ausente');
    if (!transOk) {
      throw new Error(
        "No se puede cambiar de '" + estadoActual + "' a '" + nuevoEstado + "'. " +
        'Solo se permite: ausente↔excusa.'
      );
    }

    AsistenciaRepository.actualizarEstado(idAsistencia, nuevoEstado, observacion || null);
    return AsistenciaRepository.obtenerPorId(idAsistencia);
  }

  // ── eliminar ──────────────────────────────────────────────────
  // Replica: AsistenciaService.eliminar()
  function eliminar(idAsistencia, usuario) {
    if (usuario.rol !== 'docente' && usuario.rol !== 'admin') {
      throw new Error('No tienes permiso para eliminar esta asistencia.');
    }
    var existe = AsistenciaRepository.obtenerPorId(idAsistencia);
    if (!existe) throw new Error('Asistencia no encontrada.');
    AsistenciaRepository.eliminar(idAsistencia);
    return { eliminado: true };
  }

  // ── generarReporteCSV ─────────────────────────────────────────
  // Exportación CSV con BOM UTF-8. Rol aprendiz o docente con filtros.
  // Replica: AsistenciaController caso 'exportar' para aprendiz +
  //          AsistenciaService.generarReporte()
  // Retorna: base64 del CSV completo (con BOM)
  function generarReporteCSV(filtros, usuario) {
    filtros = filtros || {};
    var idDocente  = usuario.rol === 'docente' ? usuario.id : (filtros.id_docente  || null);
    var idAprendiz = usuario.rol === 'aprendiz'? usuario.id : (filtros.id_aprendiz || null);
    var idFicha    = filtros.id_ficha     || null;
    var fi         = filtros.fecha_inicio || null;
    var ff         = filtros.fecha_fin    || null;

    var filas = AsistenciaRepository.listarParaExportar(idDocente, idAprendiz, idFicha, fi, ff);

    var headers = ['Fecha', 'Ficha', 'Programa', 'Documento', 'Nombres', 'Apellidos',
                   'Estado', 'Hora ingreso', 'Hora clase', 'Minutos retardo', 'Observación'];

    var lines = [headers.join(';')];
    filas.forEach(function (f) {
      lines.push([
        f.fecha_sesion      || '',
        f.codigo_ficha      || '',
        f.nombre_programa   || '',
        f.numero_documento  || '',
        f.nombres           || '',
        f.apellidos         || '',
        f.estado            || '',
        f.hora_registro     || '',
        f.hora_inicio_clase || '',
        f.minutos_retardo   || 0,
        f.observacion       || ''
      ].join(';'));
    });

    // BOM UTF-8 + contenido
    var csvStr = '﻿' + lines.join('\r\n');
    return Utilities.base64Encode(Utilities.newBlob(csvStr, 'text/csv').getBytes());
  }

  // ── generarReporteExcel ───────────────────────────────────────
  // Exportación xlsx multi-hoja formato SENA.
  // Replica: AsistenciaService.generarReporteExcel() + escribirHojaFicha()
  // Retorna: base64 del archivo .xlsx
  function generarReporteExcel(filtros, usuario) {
    filtros = filtros || {};
    var idDocente = usuario.rol === 'docente' ? usuario.id : (filtros.id_docente || null);
    var idFicha   = filtros.id_ficha     || null;
    var fi        = filtros.fecha_inicio || null;
    var ff        = filtros.fecha_fin    || null;

    var fichas    = AsistenciaRepository.fichasParaReporte(idDocente, idFicha);
    if (fichas.length === 0) throw new Error('No hay fichas para exportar con los filtros indicados.');

    var idFichas  = fichas.map(function (f) { return f.id_ficha; });
    var sesiones  = AsistenciaRepository.sesionesParaReporte(idFichas, fi, ff);
    var aprendices= AsistenciaRepository.aprendicesPorFichas(idFichas);
    var asistencias=AsistenciaRepository.asistenciasParaReporte(idFichas, fi, ff);

    var xlsx = XlsxExport.crear();

    fichas.forEach(function (ficha) {
      _escribirHojaFicha(xlsx, ficha, sesiones, aprendices, asistencias);
    });

    return xlsx.output();
  }

  // ── Helpers privados ──────────────────────────────────────────

  // Determina el estado (presente/retardo) según hora de inicio de clase.
  // Replica: AsistenciaService.clasificar()
  // Lanza error con código |422| si el tiempo de registro ha expirado.
  function _clasificar(sesion, horaRegistro) {
    if (!sesion.hora_inicio_clase) {
      return ['presente', 0];
    }

    var pH = sesion.hora_inicio_clase.split(':').map(Number);
    var rH = horaRegistro.split(':').map(Number);

    var tsInicio   = pH[0] * 3600 + pH[1] * 60 + (pH[2] || 0);
    var tsRegistro = rH[0] * 3600 + rH[1] * 60 + (rH[2] || 0);

    var minutosDesdeH  = Math.floor((tsRegistro - tsInicio) / 60);
    var limitePresente = parseInt(sesion.limite_retardo_minutos,  10) || 5;
    var limiteRetardo  = parseInt(sesion.duracion_maxima_minutos, 10) || 20;

    if (minutosDesdeH > limiteRetardo) {
      throw new Error(
        '|422|El tiempo de registro ha expirado. Solo se acepta asistencia hasta H+' +
        limiteRetardo + ' minutos.'
      );
    }
    if (minutosDesdeH <= limitePresente) {
      return ['presente', 0];
    }
    return ['retardo', minutosDesdeH];
  }

  // Distancia Haversine entre dos coordenadas geográficas.
  // Replica: AsistenciaService.haversine()
  function _haversine(lat1, lon1, lat2, lon2) {
    var R     = 6371000.0;
    var phi1  = lat1 * Math.PI / 180;
    var phi2  = lat2 * Math.PI / 180;
    var dPhi  = (lat2 - lat1) * Math.PI / 180;
    var dLam  = (lon2 - lon1) * Math.PI / 180;
    var a     = Math.sin(dPhi / 2) * Math.sin(dPhi / 2) +
                Math.cos(phi1) * Math.cos(phi2) *
                Math.sin(dLam / 2) * Math.sin(dLam / 2);
    var c     = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
    return R * c;
  }

  // Etiqueta de mes en español: '2025-04-01' → 'ABRIL DEL 2025'
  // Replica: AsistenciaService.etiquetaMes()
  function _etiquetaMes(fechaStr) {
    var MESES = ['ENERO','FEBRERO','MARZO','ABRIL','MAYO','JUNIO',
                 'JULIO','AGOSTO','SEPTIEMBRE','OCTUBRE','NOVIEMBRE','DICIEMBRE'];
    var p = fechaStr.split('-');
    return MESES[parseInt(p[1], 10) - 1] + ' DEL ' + p[0];
  }

  // Abreviatura del día de semana: '2025-04-01' → 'MAR'
  function _abrevDia(fechaStr) {
    var DIAS = ['DOM','LUN','MAR','MIE','JUE','VIE','SAB'];
    var p    = fechaStr.split('-');
    var d    = new Date(parseInt(p[0],10), parseInt(p[1],10)-1, parseInt(p[2],10));
    return DIAS[d.getDay()];
  }

  // Escribe una hoja del libro xlsx para una ficha dada.
  // Replica: AsistenciaService.escribirHojaFicha()
  function _escribirHojaFicha(xlsx, ficha, todasSesiones, todosAprendices, todasAsistencias) {
    var S = XlsxExport;

    // Filtrar sesiones de esta ficha
    var sesiones = todasSesiones.filter(function (s) { return s.id_ficha == ficha.id_ficha; });
    if (sesiones.length === 0) return;

    // Filtrar aprendices de esta ficha
    var aprendices = todosAprendices.filter(function (a) { return a.id_ficha == ficha.id_ficha; });

    // Construir mapa de asistencias: [id_aprendiz][fecha_sesion] = estado
    var mapaA = {};
    todasAsistencias.forEach(function (a) {
      if (a.id_ficha != ficha.id_ficha) return;
      if (!mapaA[a.id_aprendiz]) mapaA[a.id_aprendiz] = {};
      mapaA[a.id_aprendiz][a.fecha_sesion] = a.estado;
    });

    // Determinar etiqueta de mes (tomamos el mes de la primera sesión)
    var mesLabel  = _etiquetaMes(sesiones[0].fecha_sesion);
    var fichaLabel= 'FICHA ' + ficha.codigo_ficha;

    var sheetIdx = xlsx.addSheet(fichaLabel);

    // Anchos de columna
    xlsx.colWidth(sheetIdx, 'A', 1.5);
    xlsx.colWidth(sheetIdx, 'B', 5);
    xlsx.colWidth(sheetIdx, 'C', 22);
    xlsx.colWidth(sheetIdx, 'D', 22);
    var colBase = 4; // columna E en base 0 = índice 4
    sesiones.forEach(function (_, si) {
      xlsx.colWidth(sheetIdx, xlsx.colLetter(colBase + si), 4.5);
    });
    var colFallas = colBase + sesiones.length;
    var colAsist  = colFallas + 1;
    xlsx.colWidth(sheetIdx, xlsx.colLetter(colFallas), 8);
    xlsx.colWidth(sheetIdx, xlsx.colLetter(colAsist),  8);

    // Columnas de datos (letras)
    var letFallas = xlsx.colLetter(colFallas);
    var letAsist  = xlsx.colLetter(colAsist);
    var letUltima = letAsist;

    // Fila 2: Nombre del docente (verde, combinada B2:última)
    var nombreDoc = (ficha.nombres_docente || '') + ' ' + (ficha.apellidos_docente || '');
    xlsx.cell(sheetIdx, 'B', 2, 'INSTRUCTOR: ' + nombreDoc.trim(), S.S_HDR_GREEN);
    xlsx.merge(sheetIdx, 'B2', letUltima + '2');

    // Fila 5: Código de ficha (gris) y mes (azul)
    xlsx.cell(sheetIdx, 'B', 5, fichaLabel, S.S_HDR_GREY);
    xlsx.merge(sheetIdx, 'B5', 'C5');
    xlsx.cell(sheetIdx, 'D', 5, mesLabel, S.S_HDR_BLUE);
    xlsx.merge(sheetIdx, 'D5', letUltima + '5');

    // Fila 6: Jornada (gris) + Programa (neutro)
    xlsx.cell(sheetIdx, 'B', 6, ficha.nombre_jornada || '', S.S_HDR_GREY);
    xlsx.merge(sheetIdx, 'B6', 'C6');
    xlsx.cell(sheetIdx, 'D', 6, ficha.nombre_programa || '', S.S_HDR_PROG);
    xlsx.merge(sheetIdx, 'D6', letUltima + '6');

    // Fila 7: encabezados de columnas
    xlsx.cell(sheetIdx, 'B', 7, '#',        S.S_COL_NAME);
    xlsx.cell(sheetIdx, 'C', 7, 'Nombres',  S.S_COL_NAME);
    xlsx.cell(sheetIdx, 'D', 7, 'Apellidos',S.S_COL_NAME);
    sesiones.forEach(function (ses, si) {
      var dia    = ses.fecha_sesion.substring(8, 10);
      var abrev  = _abrevDia(ses.fecha_sesion);
      var letCol = xlsx.colLetter(colBase + si);
      xlsx.cell(sheetIdx, letCol, 7, dia + '\n' + abrev, S.S_COL_DATE);
    });
    xlsx.cell(sheetIdx, letFallas, 7, 'Fallas',    S.S_TOTAL_F);
    xlsx.cell(sheetIdx, letAsist,  7, 'Asist.',    S.S_TOTAL_A);

    // Filas 8+ : datos de aprendices
    aprendices.forEach(function (ap, ai) {
      var rowNum    = 8 + ai;
      var fallas    = 0;
      var asistencias = 0;
      xlsx.cell(sheetIdx, 'B', rowNum, ai + 1, S.S_NAME_IDX);
      xlsx.cell(sheetIdx, 'C', rowNum, ap.nombres,   S.S_NAME_TXT);
      xlsx.cell(sheetIdx, 'D', rowNum, ap.apellidos, S.S_NAME_TXT);
      sesiones.forEach(function (ses, si) {
        var letCol = xlsx.colLetter(colBase + si);
        var estado = mapaA[ap.id_aprendiz] && mapaA[ap.id_aprendiz][ses.fecha_sesion];
        var celda, estilo;
        if (!estado || estado === 'ausente') {
          celda  = 'F';
          estilo = S.S_CELL_F;
          fallas++;
        } else if (estado === 'presente' || estado === 'retardo') {
          celda  = 'A';
          estilo = S.S_CELL_A;
          asistencias++;
        } else if (estado === 'excusa') {
          celda  = 'E';
          estilo = S.S_CELL_SESS;
        } else {
          celda  = '';
          estilo = S.S_DEFAULT;
        }
        xlsx.cell(sheetIdx, letCol, rowNum, celda, estilo);
      });
      xlsx.cell(sheetIdx, letFallas, rowNum, fallas,      S.S_TOTAL_F);
      xlsx.cell(sheetIdx, letAsist,  rowNum, asistencias, S.S_TOTAL_A);
    });

    // 3 filas en blanco al final (igual que PHP)
    // (no se requiere acción, el grid no incluirá esas filas vacías)
  }

  // ── API pública ───────────────────────────────────────────────
  return {
    registrarPorQr      : registrarPorQr,
    consultar           : consultar,
    historial           : historial,
    validar             : validar,
    cambiarEstado       : cambiarEstado,
    eliminar            : eliminar,
    generarReporteCSV   : generarReporteCSV,
    generarReporteExcel : generarReporteExcel
  };

})();
