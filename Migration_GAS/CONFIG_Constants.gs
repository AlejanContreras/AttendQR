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
var AUTH_SPREADSHEET_ID = 'REEMPLAZAR_CON_ID_DEL_SPREADSHEET';

// ── Versión del sistema ───────────────────────────────────────
// Debe coincidir con HealthService.VERSION y actualizarse en cada despliegue.
var APP_VERSION = '0.1.0';

// ── Nombre del proyecto ───────────────────────────────────────
var APP_NOMBRE = 'AttendQR';
