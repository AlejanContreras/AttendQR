// =============================================================
// AttendQR — TOKEN_TokenController
// =============================================================
// Funciones públicas expuestas al frontend via google.script.run.
// Replica: Src/Controllers/TokenController.php
//
// Mapeo de rutas PHP → funciones GAS:
//   POST   /api/tokens/generar → tokenGenerar(payload)
//   POST   /api/tokens/validar → tokenValidar(payload)
//   POST   /api/tokens/renovar → tokenRenovar(payload)
//   DELETE /api/tokens/eliminar → tokenEliminar(payload)
//
// NOTA: igual que en PHP, estas funciones NO exigen sesión activa
// previa (el sistema de tokens es independiente del sistema de
// sesiones de usuario). El caller pasa id_usuario/rol o el token
// directamente.
//
// Todas las funciones devuelven: { success, message, data? }
// =============================================================

// ── tokenGenerar ──────────────────────────────────────────────
// payload: { id_usuario: number, rol: 'docente'|'admin'|'aprendiz' }
function tokenGenerar(payload) {
  try {
    payload = payload || {};

    if (!payload.id_usuario || !payload.rol) {
      return { success: false, message: 'Los campos id_usuario y rol son obligatorios.' };
    }

    var tokens = TokenService.generar(
      parseInt(payload.id_usuario, 10),
      String(payload.rol)
    );
    return { success: true, message: 'Tokens generados correctamente.', data: tokens };
  } catch (e) {
    return { success: false, message: e.message };
  }
}

// ── tokenValidar ──────────────────────────────────────────────
// payload: { token: string }
function tokenValidar(payload) {
  try {
    payload = payload || {};

    if (!payload.token) {
      return { success: false, message: 'El campo token es obligatorio.' };
    }

    var resultado = TokenService.validar(String(payload.token));
    return { success: true, message: 'Token válido.', data: resultado };
  } catch (e) {
    return { success: false, message: e.message };
  }
}

// ── tokenRenovar ──────────────────────────────────────────────
// payload: { token_refresco: string }
function tokenRenovar(payload) {
  try {
    payload = payload || {};

    if (!payload.token_refresco) {
      return { success: false, message: 'El campo token_refresco es obligatorio.' };
    }

    var resultado = TokenService.renovar(String(payload.token_refresco));
    return { success: true, message: 'Token renovado correctamente.', data: resultado };
  } catch (e) {
    return { success: false, message: e.message };
  }
}

// ── tokenEliminar ─────────────────────────────────────────────
// payload: { token: string }
function tokenEliminar(payload) {
  try {
    payload = payload || {};

    if (!payload.token) {
      return { success: false, message: 'El campo token es obligatorio.' };
    }

    var resultado = TokenService.eliminar(String(payload.token));
    return { success: true, message: resultado.message || 'Token revocado correctamente.', data: {} };
  } catch (e) {
    return { success: false, message: e.message };
  }
}
