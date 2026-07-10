// =============================================================
// AttendQR — AUTH_AuthController
// =============================================================
// Funciones públicas expuestas al frontend via google.script.run.
// Replica: Src/Controllers/AuthController.php
//
// CÓMO LLAMAR DESDE EL CLIENTE HTML:
//   google.script.run
//     .withSuccessHandler(function(res) { ... })
//     .withFailureHandler(function(err) { ... })
//     .authLogin({ correo: '...', password: '...' });
//
// Todas las funciones devuelven: { success, message, data? }
// Mismo contrato JSON que el backend PHP.
//
// Mapeo de rutas PHP → funciones GAS:
//   POST /api/auth/login               → authLogin(payload)
//   POST /api/auth/logout              → authLogout(token)
//   GET  /api/auth/verificar           → authVerificar(token)
//   POST /api/auth/verificar-documento → authVerificarDocumento(payload)  [pendiente módulo Aprendiz]
//   POST /api/auth/activar-cuenta      → authActivarCuenta(payload)       [pendiente módulo Aprendiz]
// =============================================================

// ── authLogin ─────────────────────────────────────────────────
// payload: { correo?, documento?, password }
// Retorna: { success, message, data: { usuario, token } }
//   El cliente debe guardar `data.token` en sessionStorage
//   y enviarlo en cada llamada autenticada posterior.
function authLogin(payload) {
  try {
    var result = AuthService.login(
      payload.correo    || '',
      payload.documento || '',
      payload.password  || ''
    );
    return {
      success: true,
      message: 'Sesión iniciada correctamente.',
      data   : result
    };
  } catch (e) {
    return { success: false, message: e.message };
  }
}

// ── authLogout ────────────────────────────────────────────────
// token: string (el token que se guardó en sessionStorage al hacer login)
// Retorna: { success, message }
function authLogout(token) {
  try {
    AuthService.logout(token);
    return { success: true, message: 'Sesión cerrada correctamente.' };
  } catch (e) {
    return { success: false, message: e.message };
  }
}

// ── authVerificar ─────────────────────────────────────────────
// Valida que el token de sesión siga activo y retorna el usuario.
// token: string
// Retorna: { success, message, data: usuario }
function authVerificar(token) {
  try {
    var usuario = AuthService.verificarToken(token);
    return {
      success: true,
      message: 'Sesión válida.',
      data   : usuario
    };
  } catch (e) {
    return { success: false, message: e.message };
  }
}

// ── authVerificarDocumento ────────────────────────────────────
// Verifica si un número de documento tiene cuenta en el sistema.
// payload: { documento }
// PENDIENTE: requiere módulo Aprendiz (Fase X).
function authVerificarDocumento(payload) {
  return {
    success: false,
    message: 'Función pendiente de migración (requiere módulo Aprendiz).'
  };
}

// ── authActivarCuenta ─────────────────────────────────────────
// Activa la cuenta de un aprendiz y establece su contraseña inicial.
// payload: { documento, password, confirmar_password }
// PENDIENTE: requiere módulo Aprendiz (Fase X).
function authActivarCuenta(payload) {
  return {
    success: false,
    message: 'Función pendiente de migración (requiere módulo Aprendiz).'
  };
}
