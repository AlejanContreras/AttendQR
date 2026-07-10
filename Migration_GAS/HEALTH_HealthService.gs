// =============================================================
// AttendQR — HEALTH_HealthService
// =============================================================
// Verificación del estado operativo de la API y sus dependencias.
// Replica: Src/Services/HealthService.php
//
// Flujo: HealthController → HealthService → SpreadsheetApp (ping)
//
// En PHP el "ping a BD" es una consulta SELECT 1 vía PDO.
// En GAS el equivalente es intentar abrir el Spreadsheet principal
// (SpreadsheetApp.openById) y leer la lista de hojas — si falla,
// se reporta 'no disponible' igual que en PHP.
//
// Endpoints públicos: no requieren autenticación.
// =============================================================

var HealthService = (function () {

  var VERSION = '0.1.0';
  var PROYECTO = 'AttendQR';

  // ── status ────────────────────────────────────────────────────
  // Verifica el estado general de la API y sus dependencias críticas.
  // Si el Spreadsheet no responde, el estado global es 'degradado'.
  // Replica: HealthService.status()
  //
  // Retorna: { estado_global, dependencias, version, proyecto, timestamp }
  function status() {
    var estadoBD     = _verificarBaseDatos();
    var todoOperativo = estadoBD === 'operativa';

    return {
      estado_global: todoOperativo ? 'operativo' : 'degradado',
      dependencias : {
        base_de_datos: estadoBD
      },
      version  : VERSION,
      proyecto : PROYECTO,
      timestamp: _nowStr()
    };
  }

  // ── ping ──────────────────────────────────────────────────────
  // Respuesta mínima de vida del servidor.
  // No consulta el Spreadsheet ni dependencias externas.
  // Replica: HealthService.ping()
  //
  // Retorna: { respuesta, proyecto, timestamp }
  function ping() {
    return {
      respuesta: 'pong',
      proyecto : PROYECTO,
      timestamp: _nowStr()
    };
  }

  // ── version ───────────────────────────────────────────────────
  // Retorna información de versión del backend desplegado.
  // Replica: HealthService.version()
  //
  // Retorna: { version, proyecto, timestamp }
  function version() {
    return {
      version  : VERSION,
      proyecto : PROYECTO,
      timestamp: _nowStr()
    };
  }

  // ── Helpers privados ──────────────────────────────────────────

  // Verifica la conectividad con el Spreadsheet (equivale al SELECT 1 de PHP).
  // Nunca lanza excepciones: cualquier fallo retorna 'no disponible'.
  // Replica: HealthService.verificarBaseDatos()
  function _verificarBaseDatos() {
    try {
      var ss = SpreadsheetApp.openById(AUTH_SPREADSHEET_ID);
      // Leer lista de hojas equivale al ping de conectividad
      ss.getSheets();
      return 'operativa';
    } catch (e) {
      return 'no disponible';
    }
  }

  // Fecha/hora actual formateada. Replica: date('Y-m-d H:i:s')
  function _nowStr() {
    return Utilities.formatDate(new Date(), Session.getScriptTimeZone(), 'yyyy-MM-dd HH:mm:ss');
  }

  // ── API pública ───────────────────────────────────────────────
  return {
    status : status,
    ping   : ping,
    version: version
  };

})();
