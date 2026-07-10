// =============================================================
// AttendQR — AUTH_AuthService
// =============================================================
// Lógica de negocio para autenticación.
// Replica: Src/Services/AuthService.php
//
// GESTIÓN DE SESIÓN:
//   PHP → $_SESSION['usuario']  (cookie de sesión del servidor)
//   GAS → ScriptProperties con clave 'sess_<uuid>'
//         login()  genera un token UUID y lo guarda en ScriptProperties.
//         El token se devuelve al cliente y se guarda en sessionStorage.
//         Cada llamada autenticada pasa el token como parámetro.
//         verificarToken(token) valida y retorna los datos del usuario.
//
// CONTRASEÑAS:
//   PHP → password_hash() / password_verify()  — bcrypt
//   GAS → Utilities.computeDigest(SHA_256)
//   ⚠ IMPORTANTE: Los hashes bcrypt de MySQL NO son compatibles con SHA-256.
//     Al migrar usuarios desde la BD PHP, sus contraseñas deben re-hashearse
//     usando hashPassword() antes de escribirlas en el Spreadsheet.
// =============================================================

var AuthService = (function () {

  var SESSION_DURACION_MS = 8 * 60 * 60 * 1000; // 8 horas

  // ── Sesión (ScriptProperties) ────────────────────────────

  function _crearSesion(usuario) {
    var token  = Utilities.getUuid();
    var expira = Date.now() + SESSION_DURACION_MS;
    PropertiesService.getScriptProperties().setProperty(
      'sess_' + token,
      JSON.stringify({ usuario: usuario, expira: expira })
    );
    return token;
  }

  function _leerSesion(token) {
    if (!token) return null;
    var raw = PropertiesService.getScriptProperties().getProperty('sess_' + token);
    if (!raw) return null;
    var data = JSON.parse(raw);
    if (Date.now() > data.expira) {
      PropertiesService.getScriptProperties().deleteProperty('sess_' + token);
      return null;
    }
    return data.usuario;
  }

  function _eliminarSesion(token) {
    if (token) {
      PropertiesService.getScriptProperties().deleteProperty('sess_' + token);
    }
  }

  // ── Hash de contraseña (SHA-256) ─────────────────────────

  function hashPassword(password) {
    var bytes = Utilities.computeDigest(
      Utilities.DigestAlgorithm.SHA_256,
      password,
      Utilities.Charset.UTF_8
    );
    return bytes.map(function (b) {
      return ('0' + (b & 0xff).toString(16)).slice(-2);
    }).join('');
  }

  function _verificarPassword(passwordPlano, hashAlmacenado) {
    return hashPassword(passwordPlano) === hashAlmacenado;
  }

  // ── loginDocente ─────────────────────────────────────────
  // Equivalente a AuthService::loginDocente($correo, $password)
  function loginDocente(correo, password) {
    correo = String(correo).toLowerCase().trim();

    var docente = AuthRepository.buscarDocentePorCorreo(correo);
    if (!docente) throw new Error('Credenciales incorrectas.');

    if (!_verificarPassword(password, docente.password_hash)) {
      throw new Error('Credenciales incorrectas.');
    }
    if (!docente.activo || docente.activo == 0) {
      throw new Error('Tu cuenta está desactivada. Contacta al administrador.');
    }

    var usuario = {
      id       : docente.id_docente,
      nombres  : docente.nombres,
      apellidos: docente.apellidos,
      correo   : docente.correo,
      rol      : 'docente'
    };

    return { usuario: usuario, token: _crearSesion(usuario) };
  }

  // ── loginAprendiz ────────────────────────────────────────
  // Equivalente a AuthService::loginAprendiz($documento, $password)
  function loginAprendiz(documento, password) {
    documento = String(documento).trim();

    var aprendiz = AuthRepository.buscarAprendizPorDocumento(documento);
    if (!aprendiz) throw new Error('Credenciales incorrectas.');

    if (!_verificarPassword(password, aprendiz.password_hash)) {
      throw new Error('Credenciales incorrectas.');
    }
    if (!aprendiz.activo || aprendiz.activo == 0) {
      throw new Error('Tu cuenta está desactivada. Contacta al administrador.');
    }
    if (aprendiz.cuenta_activada == 0 || aprendiz.cuenta_activada === false) {
      throw new Error('Tu cuenta no ha sido activada. Solicita acceso al docente.');
    }

    var usuario = {
      id              : aprendiz.id_aprendiz,
      nombres         : aprendiz.nombres,
      apellidos       : aprendiz.apellidos,
      numero_documento: aprendiz.numero_documento,
      id_ficha        : aprendiz.id_ficha,
      codigo_ficha    : aprendiz.codigo_ficha    || '',
      nombre_programa : aprendiz.nombre_programa || '',
      rol             : 'aprendiz'
    };

    return { usuario: usuario, token: _crearSesion(usuario) };
  }

  // ── login (dispatcher) ───────────────────────────────────
  // Si viene correo → docente. Si viene documento → aprendiz.
  // Equivalente a AuthService::login($correo, $documento, $password)
  function login(correo, documento, password) {
    if (correo) return loginDocente(correo, password);
    if (documento) return loginAprendiz(documento, password);
    throw new Error('Debes ingresar correo o número de documento.');
  }

  // ── logout ───────────────────────────────────────────────
  function logout(token) {
    _eliminarSesion(token);
  }

  // ── verificarToken ───────────────────────────────────────
  // Equivalente a AuthService::verificarToken() — lanza 401 si no hay sesión.
  function verificarToken(token) {
    var usuario = _leerSesion(token);
    if (!usuario) throw new Error('Sesión no válida o expirada. Inicia sesión nuevamente.');
    return usuario;
  }

  return {
    login          : login,
    loginDocente   : loginDocente,
    loginAprendiz  : loginAprendiz,
    logout         : logout,
    verificarToken : verificarToken,
    hashPassword   : hashPassword
  };

})();
