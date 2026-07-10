// =============================================================
// AttendQR — HEALTH_HealthController
// =============================================================
// Funciones públicas expuestas al frontend via google.script.run.
// Replica: Src/Controllers/HealthController.php
//
// Mapeo de rutas PHP → funciones GAS:
//   GET /api/health/status  → healthStatus()
//   GET /api/health/ping    → healthPing()
//   GET /api/health/version → healthVersion()
//
// NOTA: estos endpoints son públicos en PHP (no requieren sesión).
// En GAS se mantiene el mismo comportamiento: ninguna función
// valida token de autenticación.
//
// Todas las funciones devuelven: { success, message, data? }
// =============================================================

// ── healthStatus ──────────────────────────────────────────────
// Verifica el estado general de la API y sus dependencias.
// Retorna data.estado_global = 'operativo' | 'degradado'
function healthStatus() {
  try {
    var resultado = HealthService.status();
    return { success: true, message: 'Estado del sistema obtenido.', data: resultado };
  } catch (e) {
    return { success: false, message: 'Error interno al verificar el estado del sistema.' };
  }
}

// ── healthPing ────────────────────────────────────────────────
// Respuesta mínima de vida. No consulta el Spreadsheet.
function healthPing() {
  try {
    var resultado = HealthService.ping();
    return { success: true, message: 'pong', data: resultado };
  } catch (e) {
    return { success: false, message: 'Error interno al responder el ping.' };
  }
}

// ── healthVersion ─────────────────────────────────────────────
// Retorna la versión del backend desplegado.
function healthVersion() {
  try {
    var resultado = HealthService.version();
    return { success: true, message: 'Versión del sistema obtenida.', data: resultado };
  } catch (e) {
    return { success: false, message: 'Error interno al obtener la versión.' };
  }
}
