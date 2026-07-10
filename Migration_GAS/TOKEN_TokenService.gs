// =============================================================
// AttendQR — TOKEN_TokenService
// =============================================================
// Lógica de negocio del módulo Token.
// Replica: Src/Services/TokenService.php
//
// Gestiona tokens de autenticación de usuario (access + refresh).
// Los tokens QR de sesiones son gestionados por QrService
// llamando directamente a TokenRepository.
//
// Dependencias:
//   TokenRepository — persistirRefresco, buscarAcceso,
//                     buscarRefresco, revocar
//
// Constantes (idénticas al PHP):
//   EXPIRACION_ACCESO_SEGUNDOS   = 900     (15 minutos)
//   EXPIRACION_REFRESCO_SEGUNDOS = 604800  (7 días)
//
// Token de acceso: stateless (no almacenado), retornado en claro.
// Token de refresco: almacenado hasheado (SHA-256) en tokens_auth.
// =============================================================

var TokenService = (function () {

  var EXPIRACION_ACCESO_SEGUNDOS   = 900;
  var EXPIRACION_REFRESCO_SEGUNDOS = 604800;

  // ── Generar ───────────────────────────────────────────────
  // Genera un par access + refresh. Persiste solo el refresh hasheado.
  // Replica: TokenService.generar()

  function generar(idUsuario, rol) {
    var rolesPermitidos = ['docente', 'admin', 'aprendiz'];

    if (rolesPermitidos.indexOf(rol) === -1) {
      throw new Error(
        "Rol '" + rol + "' no válido. Valores permitidos: " + rolesPermitidos.join(', ') + '.'
      );
    }

    var tokenAcceso   = _generarTokenUnico();
    var tokenRefresco = _generarTokenUnico();
    var expAcceso     = _calcularExpiracion(EXPIRACION_ACCESO_SEGUNDOS);
    var expRefresco   = _calcularExpiracion(EXPIRACION_REFRESCO_SEGUNDOS);

    TokenRepository.persistirRefresco(
      idUsuario,
      _sha256(tokenRefresco),
      expRefresco,
      rol
    );

    return {
      token_acceso    : tokenAcceso,
      token_refresco  : tokenRefresco,
      expira_acceso   : expAcceso,
      expira_refresco : expRefresco,
      tipo            : 'Bearer'
    };
  }

  // ── Validar ───────────────────────────────────────────────
  // Valida un token de acceso y retorna su payload.
  // NOTA: generar() no almacena el token de acceso (solo el refresh),
  // por lo que buscarAcceso() retorna null — comportamiento idéntico al PHP.
  // Replica: TokenService.validar()

  function validar(token) {
    var tokenHash = _sha256(String(token).trim());
    var registro  = TokenRepository.buscarAcceso(tokenHash);

    if (!registro) {
      throw new Error('Token no encontrado.');
    }

    if (_haExpirado(registro.expiracion)) {
      throw new Error('El token ha expirado.');
    }

    if (parseInt(registro.revocado, 10) === 1) {
      throw new Error('El token fue revocado.');
    }

    return {
      id_usuario : parseInt(registro.id_usuario, 10),
      rol        : registro.rol,
      expiracion : registro.expiracion
    };
  }

  // ── Renovar ───────────────────────────────────────────────
  // Renueva el token de acceso usando el refresh token.
  // El nuevo token de acceso es stateless (no se almacena).
  // Replica: TokenService.renovar()

  function renovar(tokenRefresco) {
    var refrescoHash = _sha256(String(tokenRefresco).trim());
    var registro     = TokenRepository.buscarRefresco(refrescoHash);

    if (!registro) {
      throw new Error('Token de refresco no encontrado.');
    }

    if (_haExpirado(registro.expiracion)) {
      throw new Error('El token de refresco ha expirado.');
    }

    if (parseInt(registro.revocado, 10) === 1) {
      throw new Error('El token de refresco fue revocado.');
    }

    var nuevoToken      = _generarTokenUnico();
    var nuevaExpiracion = _calcularExpiracion(EXPIRACION_ACCESO_SEGUNDOS);

    return {
      token_acceso : nuevoToken,
      expira_en    : nuevaExpiracion,
      tipo         : 'Bearer'
    };
  }

  // ── Eliminar ──────────────────────────────────────────────
  // Revoca un token (access o refresh) aunque no haya expirado.
  // Replica: TokenService.eliminar()

  function eliminar(token) {
    var tokenHash = _sha256(String(token).trim());
    var afectadas = TokenRepository.revocar(tokenHash);

    if (afectadas === 0) {
      throw new Error('Token no encontrado.');
    }

    return { success: true, message: 'Token revocado correctamente.' };
  }

  // ── Helpers privados ──────────────────────────────────────

  // Genera un token único de 64 chars hex (equivale a 32 bytes aleatorios).
  // Replica: bin2hex(random_bytes(32))
  // GAS no tiene Utilities.getSecureRandomBytes → usamos computeDigest (SHA_256)
  // mezclado con dos valores aleatorios distintos para mayor entropía.
  function _generarTokenUnico() {
    var seed  = new Date().getTime().toString()
              + Math.random().toString()
              + Math.random().toString();
    var bytes = Utilities.computeDigest(Utilities.DigestAlgorithm.SHA_256, seed);
    var hex   = '';
    for (var i = 0; i < bytes.length; i++) {
      var b = bytes[i] < 0 ? bytes[i] + 256 : bytes[i];
      hex  += (b < 16 ? '0' : '') + b.toString(16);
    }
    return hex; // 64 chars hex, minúsculas
  }

  // Calcula la fecha de expiración sumando `segundos` al momento actual.
  // Replica: date('Y-m-d H:i:s', time() + $segundos)
  function _calcularExpiracion(segundos) {
    var d = new Date(new Date().getTime() + segundos * 1000);
    return Utilities.formatDate(d, Session.getScriptTimeZone(), 'yyyy-MM-dd HH:mm:ss');
  }

  // SHA-256 hex. Replica: hash('sha256', $str)
  function _sha256(str) {
    var bytes = Utilities.computeDigest(Utilities.DigestAlgorithm.SHA_256, str);
    var hex   = '';
    for (var i = 0; i < bytes.length; i++) {
      var b = (bytes[i] < 0 ? bytes[i] + 256 : bytes[i]).toString(16);
      hex  += (b.length === 1 ? '0' : '') + b;
    }
    return hex;
  }

  // Comprueba si una fecha de expiración ya pasó. Replica: strtotime() < time()
  function _haExpirado(expiracion) {
    if (!expiracion) return true;
    var exp = expiracion instanceof Date
      ? expiracion
      : new Date(String(expiracion).replace(' ', 'T'));
    return exp.getTime() < new Date().getTime();
  }

  return {
    generar  : generar,
    validar  : validar,
    renovar  : renovar,
    eliminar : eliminar
  };

})();
