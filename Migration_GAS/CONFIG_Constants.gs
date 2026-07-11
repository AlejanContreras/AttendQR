// =============================================================
// AttendQR — CONFIG_Constants
// =============================================================
// Configuración global del proyecto GAS.
// Replica: Src/Config/database.php  (conexión PDO → ID de Spreadsheet)
//
// En PHP, database.php provee la conexión MySQL (PDO).
// En GAS, la "base de datos" es un Google Spreadsheet.
// Todas las hojas (tablas) están en el mismo Spreadsheet.
// La constante AUTH_SPREADSHEET_ID es el único punto de configuración
// equivalente a los parámetros de conexión MySQL.
//
// ── INSTRUCCIÓN DE DESPLIEGUE ────────────────────────────────
// 1. Crear el Spreadsheet de base de datos en Google Drive.
// 2. Copiar su ID desde la URL:
//    https://docs.google.com/spreadsheets/d/{ID}/edit
// 3. Pegar el ID en la constante AUTH_SPREADSHEET_ID más abajo.
// 4. Verificar que en AUTH_AuthRepository.gs la misma constante
//    NO esté también declarada como var (si existe, eliminarla de
//    AuthRepository para que este archivo sea el único origen).
//
// ── Hojas requeridas (equivalen a las tablas MySQL) ──────────
//   usuarios             → tabla unificada de login (si aplica)
//   docentes             → A:id_docente B:nombres C:apellidos D:correo
//                          E:password_hash F:activo G:creado_en
//   aprendices           → A:id_aprendiz B:nombres C:apellidos D:numero_documento
//                          E:password_hash F:id_ficha G:activo H:cuenta_activada
//   fichas               → A:id_ficha B:codigo_ficha C:nombre_programa D:activa
//                          E:nombre_materia F:id_jornada G:id_docente H:id_trimestre
//   jornadas             → A:id_jornada B:nombre C:hora_inicio D:hora_fin E:minutos_gracia
//   trimestres           → A:id_trimestre B:nombre C:fecha_inicio D:fecha_fin E:activo
//   sesiones_asistencia  → A:id_sesion B:id_ficha C:nombre_materia D:fecha_sesion
//                          E:estado_sesion F:hora_apertura G:hora_inicio_clase H:hora_cierre
//                          I:limite_retardo_minutos J:duracion_maxima_minutos
//                          K:rotacion_qr_segundos L:ubicacion_activa M:lat_docente
//                          N:lng_docente O:accuracy_docente
//   asistencias          → A:id_asistencia B:id_sesion C:id_aprendiz D:id_token_usado
//                          E:estado F:metodo_registro G:hora_registro H:minutos_retardo
//                          I:ubicacion_valida J:latitud K:longitud L:observacion M:registrado_en
//   tokens_qr            → A:id_token B:id_sesion C:token_valor D:creado_en E:expira_en
//                          F:activo G:veces_usado
//   tokens_auth          → A:id B:id_usuario C:token_hash D:tipo E:rol
//                          F:expiracion G:revocado H:creado_en
// =============================================================

// ── Identificador del Spreadsheet de base de datos ───────────
// REEMPLAZAR con el ID real del Spreadsheet antes de desplegar.
// Ejemplo: '1BxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs74OgVE2upms'
var AUTH_SPREADSHEET_ID = '1KdOnOkxXS8OlGJV1EbNru8N3268_fwwjQD5BTvCXb2w' /*'REEMPLAZAR_CON_ID_DEL_SPREADSHEET'*/;

// ── Versión del sistema ───────────────────────────────────────
// Debe coincidir con HealthService.VERSION y actualizarse en cada despliegue.
var APP_VERSION = '0.1.0';

// ── Nombre del proyecto ───────────────────────────────────────
var APP_NOMBRE = 'AttendQR';

// =============================================================
// BASE DE DATOS — Estructura y utilidades de instalación
// =============================================================
// Ejecutar inicializarSistema() desde el editor de Apps Script
// una sola vez por entorno (después de configurar AUTH_SPREADSHEET_ID).
// La función es idempotente: puede ejecutarse múltiples veces sin
// duplicar datos ni sobrescribir registros existentes.
// =============================================================

// ── Mapa de hojas y columnas ──────────────────────────────────
// Orden de columnas idéntico al documentado en el encabezado de este
// archivo. Cada array es la fila de encabezados que se escribe en A1.
var ESTRUCTURA_HOJAS = {
  docentes: [
    'id_docente', 'nombres', 'apellidos', 'correo',
    'password_hash', 'activo', 'creado_en'
  ],
  aprendices: [
    'id_aprendiz', 'nombres', 'apellidos', 'numero_documento',
    'password_hash', 'id_ficha', 'activo', 'cuenta_activada'
  ],
  fichas: [
    'id_ficha', 'codigo_ficha', 'nombre_programa', 'activa',
    'nombre_materia', 'id_jornada', 'id_docente', 'id_trimestre'
  ],
  jornadas: [
    'id_jornada', 'nombre', 'hora_inicio', 'hora_fin', 'minutos_gracia'
  ],
  trimestres: [
    'id_trimestre', 'nombre', 'fecha_inicio', 'fecha_fin', 'activo'
  ],
  sesiones_asistencia: [
    'id_sesion', 'id_ficha', 'nombre_materia', 'fecha_sesion',
    'estado_sesion', 'hora_apertura', 'hora_inicio_clase', 'hora_cierre',
    'limite_retardo_minutos', 'duracion_maxima_minutos',
    'rotacion_qr_segundos', 'ubicacion_activa', 'lat_docente',
    'lng_docente', 'accuracy_docente'
  ],
  asistencias: [
    'id_asistencia', 'id_sesion', 'id_aprendiz', 'id_token_usado',
    'estado', 'metodo_registro', 'hora_registro', 'minutos_retardo',
    'ubicacion_valida', 'latitud', 'longitud', 'observacion', 'registrado_en'
  ],
  tokens_qr: [
    'id_token', 'id_sesion', 'token_valor', 'creado_en',
    'expira_en', 'activo', 'veces_usado'
  ],
  tokens_auth: [
    'id', 'id_usuario', 'token_hash', 'tipo', 'rol',
    'expiracion', 'revocado', 'creado_en'
  ]
};

// Columnas que deben almacenarse como texto puro para evitar que
// Google Sheets autoconvierta strings numéricos (ej: '0000000' → 0).
var COLUMNAS_TEXTO_FORZADO = {
  fichas:     'codigo_ficha',
  aprendices: 'numero_documento'
};

// ── instalarBaseDatos() ───────────────────────────────────────
// Crea las hojas que faltan y escribe sus encabezados.
// Si la hoja ya existe con encabezados correctos, no la toca.
// Si la hoja existe vacía, le agrega los encabezados.
// Fuerza formato texto en columnas sensibles.
function instalarBaseDatos() {
  var ss = SpreadsheetApp.openById(AUTH_SPREADSHEET_ID);
  var informe = { creadas: [], existentes: [], actualizadas: [], diferentes: [] };

  for (var nombreHoja in ESTRUCTURA_HOJAS) {
    var columnas = ESTRUCTURA_HOJAS[nombreHoja];
    var hoja = ss.getSheetByName(nombreHoja);

    if (!hoja) {
      hoja = ss.insertSheet(nombreHoja);
      hoja.getRange(1, 1, 1, columnas.length).setValues([columnas]);
      informe.creadas.push(nombreHoja);
    } else {
      var ultimaCol = hoja.getLastColumn();
      if (ultimaCol === 0) {
        // Hoja vacía — agregar encabezados
        hoja.getRange(1, 1, 1, columnas.length).setValues([columnas]);
        informe.actualizadas.push(nombreHoja);
      } else {
        var encabezadosActuales = hoja.getRange(1, 1, 1, ultimaCol).getValues()[0];
        var iguales = columnas.every(function(col, i) { return col === encabezadosActuales[i]; });
        if (iguales) {
          informe.existentes.push(nombreHoja);
        } else {
          informe.diferentes.push(nombreHoja);
          Logger.log('ADVERTENCIA: encabezados distintos en hoja "' + nombreHoja + '"');
        }
      }
    }

    // Forzar formato texto en columnas sensibles
    if (COLUMNAS_TEXTO_FORZADO[nombreHoja]) {
      var colTexto = COLUMNAS_TEXTO_FORZADO[nombreHoja];
      var idxTexto = ESTRUCTURA_HOJAS[nombreHoja].indexOf(colTexto);
      if (idxTexto >= 0) {
        var letraCol = String.fromCharCode(65 + idxTexto);
        hoja.getRange(letraCol + '2:' + letraCol + '10000').setNumberFormat('@');
      }
    }
  }

  return informe;
}

// ── _buscarOCrear() ───────────────────────────────────────────
// Busca una fila en la hoja por columna clave + valor.
// Si no existe, inserta la fila completa (objeto {col: valor}).
// Detecta coincidencias numéricas y repara la celda a texto.
// Retorna el id del registro encontrado o creado.
function _buscarOCrear(ss, nombreHoja, columnaClaveNombre, valorClave, filaCompleta) {
  var hoja = ss.getSheetByName(nombreHoja);
  if (!hoja) throw new Error('Hoja "' + nombreHoja + '" no encontrada.');

  var columnas = ESTRUCTURA_HOJAS[nombreHoja];
  var idxClave = columnas.indexOf(columnaClaveNombre);
  var ultimaFila = hoja.getLastRow();

  if (ultimaFila > 1) {
    var datos = hoja.getRange(2, idxClave + 1, ultimaFila - 1, 1).getValues();
    for (var i = 0; i < datos.length; i++) {
      var celda = datos[i][0];
      var coincideTexto  = String(celda) === String(valorClave);
      var coincideNumero = !isNaN(valorClave) && Number(celda) === Number(valorClave);

      if (coincideTexto || coincideNumero) {
        if (coincideNumero && !coincideTexto) {
          // Corregir valor corrompido por Sheets (número → texto)
          hoja.getRange(i + 2, idxClave + 1).setValue("'" + valorClave);
          Logger.log('Reparada celda ' + nombreHoja + '!' + columnaClaveNombre +
                     ' fila ' + (i + 2) + ': ' + celda + ' → "' + valorClave + '"');
        }
        var idxId = columnas.indexOf(columnas[0]);
        return hoja.getRange(i + 2, idxId + 1).getValue();
      }
    }
  }

  // No encontrado — insertar fila nueva
  var filaValores = columnas.map(function(col) {
    return filaCompleta[col] !== undefined ? filaCompleta[col] : '';
  });
  hoja.appendRow(filaValores);
  Logger.log('Insertado en ' + nombreHoja + ': ' + columnaClaveNombre + '=' + valorClave);
  return filaCompleta[columnas[0]];
}

// ── _completarRelacionesSiFaltan() ───────────────────────────
// Rellena columnas vacías en una fila sin tocar las que ya tienen valor.
function _completarRelacionesSiFaltan(ss, nombreHoja, columnaClaveNombre, valorClave, extras) {
  var hoja = ss.getSheetByName(nombreHoja);
  if (!hoja) return;

  var columnas = ESTRUCTURA_HOJAS[nombreHoja];
  var idxClave = columnas.indexOf(columnaClaveNombre);
  var ultimaFila = hoja.getLastRow();
  if (ultimaFila < 2) return;

  var datos = hoja.getRange(2, idxClave + 1, ultimaFila - 1, 1).getValues();
  for (var i = 0; i < datos.length; i++) {
    if (String(datos[i][0]) === String(valorClave)) {
      var filaNum = i + 2;
      for (var col in extras) {
        var idx = columnas.indexOf(col);
        if (idx < 0) continue;
        var actual = hoja.getRange(filaNum, idx + 1).getValue();
        if (actual === '' || actual === null || actual === undefined) {
          hoja.getRange(filaNum, idx + 1).setValue(extras[col]);
        }
      }
      break;
    }
  }
}

// ── _existeValor() ───────────────────────────────────────────
// Verifica si un valor existe en una columna de una hoja.
function _existeValor(ss, nombreHoja, columnaNombre, valor) {
  var hoja = ss.getSheetByName(nombreHoja);
  if (!hoja || hoja.getLastRow() < 2) return false;
  var columnas = ESTRUCTURA_HOJAS[nombreHoja];
  var idx = columnas.indexOf(columnaNombre);
  if (idx < 0) return false;
  var datos = hoja.getRange(2, idx + 1, hoja.getLastRow() - 1, 1).getValues();
  return datos.some(function(fila) { return String(fila[0]) === String(valor); });
}

// ── crearDatosDePrueba() ─────────────────────────────────────
// Inserta datos iniciales mínimos necesarios para el primer login.
// Idempotente: no crea duplicados si los registros ya existen.
// Credenciales de prueba: docente@sena.edu.co / 123456
//                         documento aprendiz: 12345678 / 123456
// IMPORTANTE: cambiar contraseñas en producción.
function crearDatosDePrueba() {
  var ss = SpreadsheetApp.openById(AUTH_SPREADSHEET_ID);
  var ahora = new Date();
  var informe = { insertados: [], omitidos: [], reparados: [] };

  // Jornadas (Mañana, Tarde, Noche)
  var idJornada = _buscarOCrear(ss, 'jornadas', 'nombre', 'Mañana', {
    id_jornada: 1, nombre: 'Mañana', hora_inicio: '06:00', hora_fin: '12:00', minutos_gracia: 10
  });
  _existeValor(ss, 'jornadas', 'nombre', 'Mañana')
    ? informe.omitidos.push('jornada Mañana') : informe.insertados.push('jornada Mañana');

  _buscarOCrear(ss, 'jornadas', 'nombre', 'Tarde', {
    id_jornada: 2, nombre: 'Tarde', hora_inicio: '12:00', hora_fin: '18:00', minutos_gracia: 10
  });
  _existeValor(ss, 'jornadas', 'nombre', 'Tarde')
    ? informe.omitidos.push('jornada Tarde') : informe.insertados.push('jornada Tarde');

  _buscarOCrear(ss, 'jornadas', 'nombre', 'Noche', {
    id_jornada: 3, nombre: 'Noche', hora_inicio: '18:00', hora_fin: '22:00', minutos_gracia: 10
  });
  _existeValor(ss, 'jornadas', 'nombre', 'Noche')
    ? informe.omitidos.push('jornada Noche') : informe.insertados.push('jornada Noche');

  // Trimestres reales (busca por nombre para no colisionar con datos viejos)
  _buscarOCrear(ss, 'trimestres', 'nombre', 'Trimestre 1', {
    id_trimestre: 1,
    nombre: 'Trimestre 1',
    fecha_inicio: new Date(ahora.getFullYear(), 0, 15),
    fecha_fin:    new Date(ahora.getFullYear(), 3, 14),
    activo: 1
  });
  _buscarOCrear(ss, 'trimestres', 'nombre', 'Trimestre 2', {
    id_trimestre: 2,
    nombre: 'Trimestre 2',
    fecha_inicio: new Date(ahora.getFullYear(), 3, 15),
    fecha_fin:    new Date(ahora.getFullYear(), 7, 14),
    activo: 1
  });
  var idTrimestre = _buscarOCrear(ss, 'trimestres', 'nombre', 'Trimestre 3', {
    id_trimestre: 3,
    nombre: 'Trimestre 3',
    fecha_inicio: new Date(ahora.getFullYear(), 7, 15),
    fecha_fin:    new Date(ahora.getFullYear(), 11, 14),
    activo: 1
  });

  // Docente de prueba (password_hash = hash de '123456')
  var idDocente = _buscarOCrear(ss, 'docentes', 'correo', 'docente@sena.edu.co', {
    id_docente: 'docente-001',
    nombres: 'Docente',
    apellidos: 'Prueba',
    correo: 'docente@sena.edu.co',
    password_hash: '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    activo: true,
    creado_en: ahora
  });

  // Ficha de prueba
  var idFicha = _buscarOCrear(ss, 'fichas', 'codigo_ficha', '0000000', {
    id_ficha: 'ficha-001',
    codigo_ficha: "'0000000",
    nombre_programa: 'Programa de prueba',
    activa: true,
    nombre_materia: 'Materia de prueba',
    id_jornada: idJornada,
    id_docente: idDocente,
    id_trimestre: idTrimestre
  });

  // Reparar relaciones vacías de la ficha si las hubiese
  _completarRelacionesSiFaltan(ss, 'fichas', 'codigo_ficha', '0000000', {
    id_jornada: idJornada,
    id_docente: idDocente,
    id_trimestre: idTrimestre
  });

  // Aprendiz de prueba (password_hash = hash de '123456')
  _buscarOCrear(ss, 'aprendices', 'numero_documento', '12345678', {
    id_aprendiz: 'aprendiz-001',
    nombres: 'Aprendiz',
    apellidos: 'Prueba',
    numero_documento: '12345678',
    password_hash: '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    id_ficha: idFicha,
    activo: true,
    cuenta_activada: true
  });

  return informe;
}

// ── verificarSistema() ────────────────────────────────────────
// Verifica existencia de hojas y registros clave.
// Retorna un objeto con el diagnóstico completo y el flag listoParaLogin.
function verificarSistema() {
  var ss = SpreadsheetApp.openById(AUTH_SPREADSHEET_ID);
  var resultado = {
    hojas: {},
    registros: {},
    listoParaLogin: false
  };

  // Verificar hojas
  for (var nombreHoja in ESTRUCTURA_HOJAS) {
    resultado.hojas[nombreHoja] = !!ss.getSheetByName(nombreHoja);
  }

  // Verificar registros mínimos de prueba
  resultado.registros.jornada    = _existeValor(ss, 'jornadas',   'nombre',           'Mañana');
  resultado.registros.trimestre  = _existeValor(ss, 'trimestres', 'nombre',           'Trimestre 3');
  resultado.registros.docente    = _existeValor(ss, 'docentes',   'correo',           'docente@sena.edu.co');
  resultado.registros.ficha      = _existeValor(ss, 'fichas',     'codigo_ficha',     '0000000');
  resultado.registros.aprendiz   = _existeValor(ss, 'aprendices', 'numero_documento', '12345678');

  var todasLasHojas  = Object.keys(resultado.hojas).every(function(h) { return resultado.hojas[h]; });
  var todosRegistros = Object.keys(resultado.registros).every(function(r) { return resultado.registros[r]; });
  resultado.listoParaLogin = todasLasHojas && todosRegistros;

  return resultado;
}

// ── inicializarSistema() ──────────────────────────────────────
// Punto de entrada único. Orquesta instalación + datos + verificación.
// Ejecutar manualmente desde el editor de Apps Script una vez por
// entorno, después de configurar AUTH_SPREADSHEET_ID.
function inicializarSistema() {
  Logger.log('=== AttendQR — Inicializando sistema ===');
  Logger.log('Spreadsheet ID: ' + AUTH_SPREADSHEET_ID);

  var informeInstalacion = instalarBaseDatos();
  Logger.log('Hojas creadas: '      + JSON.stringify(informeInstalacion.creadas));
  Logger.log('Hojas existentes: '   + JSON.stringify(informeInstalacion.existentes));
  Logger.log('Hojas actualizadas: ' + JSON.stringify(informeInstalacion.actualizadas));
  Logger.log('Hojas diferentes: '   + JSON.stringify(informeInstalacion.diferentes));

  var informeDatos = crearDatosDePrueba();
  Logger.log('Datos insertados: ' + JSON.stringify(informeDatos.insertados));
  Logger.log('Datos omitidos: '   + JSON.stringify(informeDatos.omitidos));

  var verificacion = verificarSistema();
  Logger.log('Verificación: ' + JSON.stringify(verificacion, null, 2));

  if (verificacion.listoParaLogin) {
    Logger.log('✓ Sistema listo. Credenciales de prueba:');
    Logger.log('  Docente  → docente@sena.edu.co / 123456');
    Logger.log('  Aprendiz → documento 12345678 / 123456');
    Logger.log('  CAMBIAR CONTRASEÑAS EN PRODUCCIÓN.');
  } else {
    Logger.log('✗ Sistema NO listo. Revisar hojas o registros faltantes.');
  }

  return verificacion;
}
