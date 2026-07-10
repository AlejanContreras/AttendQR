// =============================================================
// AttendQR — QR_QrController
// =============================================================
// Funciones públicas expuestas al frontend via google.script.run.
// Replica: Src/Controllers/QrController.php
//
// Mapeo de rutas PHP → funciones GAS:
//   POST /api/qr/generar/{idSesion}      → qrGenerar(idSesion, token)
//   GET  /api/qr/token-activo/{idSesion} → qrTokenActivo(idSesion, token)
//   POST /api/qr/validar                 → qrValidar(payload, token)
//
// Reglas de acceso:
//   - generar:      solo docente (quien gestiona la sesión)
//   - token-activo: cualquier sesión válida (docente para mostrar en pantalla)
//   - validar:      solo aprendiz (quien va a registrar asistencia)
//
// Todas las funciones devuelven: { success, message, data? }
// =============================================================

// ── qrGenerar ─────────────────────────────────────────────────
// Genera un nuevo token QR para una sesión (invalida el anterior).
// idSesion: number
// token: string  (sesión activa — debe ser docente)
function qrGenerar(idSesion, token) {
  try {
    var usuario = AuthService.verificarToken(token);
    if (usuario.rol !== 'docente') {
      return { success: false, message: 'Solo los docentes pueden generar tokens QR.' };
    }

    if (!idSesion) {
      return { success: false, message: 'El campo id_sesion es obligatorio.' };
    }

    var resultado = QrService.generar(parseInt(idSesion, 10));
    return { success: true, message: 'Token QR generado correctamente.', data: resultado };
  } catch (e) {
    return { success: false, message: e.message };
  }
}

// ── qrTokenActivo ─────────────────────────────────────────────
// Obtiene el token QR activo de una sesión.
// Si el token venció, lo rota automáticamente (siempre que la sesión esté abierta).
// idSesion: number
// token: string  (sesión activa)
function qrTokenActivo(idSesion, token) {
  try {
    AuthService.verificarToken(token);

    if (!idSesion) {
      return { success: false, message: 'El campo id_sesion es obligatorio.' };
    }

    var resultado = QrService.tokenActivo(parseInt(idSesion, 10));
    return { success: true, message: 'Token QR activo.', data: resultado };
  } catch (e) {
    return { success: false, message: e.message };
  }
}

// ── qrValidar ─────────────────────────────────────────────────
// Valida un token QR para el registro de asistencia.
// Solo aprendices pueden llamar a este endpoint.
// payload: { token_valor }
// token: string  (sesión activa — debe ser aprendiz)
function qrValidar(payload, token) {
  try {
    var usuario = AuthService.verificarToken(token);
    if (usuario.rol !== 'aprendiz') {
      return { success: false, message: 'Solo los aprendices pueden validar tokens QR.' };
    }

    payload = payload || {};
    if (!payload.token_valor) {
      return { success: false, message: 'El campo token_valor es obligatorio.' };
    }

    var resultado = QrService.validar(String(payload.token_valor), usuario.id);
    return { success: true, message: 'Token QR válido.', data: resultado };
  } catch (e) {
    return { success: false, message: e.message };
  }
}
