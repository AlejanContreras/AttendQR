// =============================================================
// AttendQR — ESTADISTICA_EstadisticaController
// =============================================================
// Funciones públicas expuestas al frontend via google.script.run.
// Replica: Src/Controllers/EstadisticaController.php
//
// Mapeo de rutas PHP → funciones GAS:
//   GET /api/estadisticas/resumen             → estadisticaResumen(token)
//   GET /api/estadisticas/dashboard           → estadisticaDashboard(filtros, token)
//   GET /api/estadisticas/asistencia          → estadisticaAsistencia(filtros, token)
//   GET /api/estadisticas/consultar/{id}      → estadisticaConsultar(idEntidad, tipo, token)
//
// Todas las funciones devuelven: { success, message, data? }
// =============================================================

// ── estadisticaResumen ────────────────────────────────────────
// Resumen general de actividad del sistema.
// token: string
function estadisticaResumen(token) {
  try {
    AuthService.verificarToken(token);
    var resultado = EstadisticaService.resumen();
    return { success: true, message: 'Resumen obtenido correctamente.', data: resultado };
  } catch (e) {
    return { success: false, message: e.message };
  }
}

// ── estadisticaDashboard ──────────────────────────────────────
// Panel principal con filtros opcionales.
// filtros: { id_docente?, id_ficha?, trimestre? }
// token: string
function estadisticaDashboard(filtros, token) {
  try {
    AuthService.verificarToken(token);
    filtros = filtros || {};
    var resultado = EstadisticaService.dashboard(
      filtros.id_docente ? parseInt(filtros.id_docente, 10) : null,
      filtros.id_ficha   ? parseInt(filtros.id_ficha,   10) : null,
      filtros.trimestre  ? parseInt(filtros.trimestre,  10) : null
    );
    return { success: true, message: 'Dashboard obtenido correctamente.', data: resultado };
  } catch (e) {
    return { success: false, message: e.message };
  }
}

// ── estadisticaAsistencia ─────────────────────────────────────
// Métricas de asistencia con filtros opcionales.
// filtros: { id_ficha?, id_docente?, fecha_inicio?, fecha_fin? }
// token: string
function estadisticaAsistencia(filtros, token) {
  try {
    AuthService.verificarToken(token);
    filtros = filtros || {};
    var resultado = EstadisticaService.asistencia(
      filtros.id_ficha    ? parseInt(filtros.id_ficha,   10) : null,
      filtros.id_docente  ? parseInt(filtros.id_docente, 10) : null,
      filtros.fecha_inicio || null,
      filtros.fecha_fin    || null
    );
    return { success: true, message: 'Métricas de asistencia obtenidas correctamente.', data: resultado };
  } catch (e) {
    return { success: false, message: e.message };
  }
}

// ── estadisticaConsultar ──────────────────────────────────────
// Estadísticas detalladas de una entidad específica.
// idEntidad: number
// tipo: 'aprendiz' | 'ficha' | 'docente'  (defecto: 'aprendiz')
// token: string
function estadisticaConsultar(idEntidad, tipo, token) {
  try {
    AuthService.verificarToken(token);
    if (!idEntidad) {
      return { success: false, message: 'Se requiere un ID numérico válido para la entidad.' };
    }
    var resultado = EstadisticaService.consultar(
      parseInt(idEntidad, 10),
      tipo || 'aprendiz'
    );
    return { success: true, message: 'Estadísticas obtenidas correctamente.', data: resultado };
  } catch (e) {
    return { success: false, message: e.message };
  }
}
