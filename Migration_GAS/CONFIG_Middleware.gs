// =============================================================
// AttendQR — CONFIG_Middleware
// =============================================================
// Middlewares de autenticación y control de acceso por rol.
// Replica: Src/Middleware/AuthMiddleware.php
//          Src/Middleware/RoleMiddleware.php
//
// ── Diferencia arquitectónica PHP → GAS ─────────────────────
// En PHP, AuthMiddleware lee $_SESSION['usuario'] — la sesión
// vive en el servidor y no requiere token explícito del cliente.
// En GAS no existe sesión de servidor: se usa PropertiesService
// con claves 'sess_<uuid>'. El token UUID debe ser enviado por
// el frontend en cada llamada como parámetro extra.
// Por ello, AuthMiddleware.verificar(token) recibe el token
// como argumento (diferencia inevitable de plataforma).
//
// ── Uso (equivalente al PHP) ─────────────────────────────────
// PHP:   $usuario = AuthMiddleware::verificar();
// GAS:   var usuario = AuthMiddleware.verificar(token);
//
// PHP:   RoleMiddleware::requerirRol($usuario, 'docente');
// GAS:   RoleMiddleware.requerirRol(usuario, 'docente');
//
// PHP:   RoleMiddleware::tieneRol($usuario, ['docente','aprendiz'])
// GAS:   RoleMiddleware.tieneRol(usuario, ['docente','aprendiz'])
//
// Los Middlewares son objetos globales reutilizables.
// No contienen lógica de negocio.
// No acceden directamente al Spreadsheet.
// Toda validación reutiliza AuthService.
// =============================================================


// =============================================================
// ── AuthMiddleware ────────────────────────────────────────────
// Verifica que exista una sesión autenticada válida.
// Replica: Src/Middleware/AuthMiddleware.php
// =============================================================
var AuthMiddleware = (function () {

  // ── verificar ─────────────────────────────────────────────
  // Verifica que exista una sesión autenticada válida.
  // Si la sesión es válida retorna los datos del usuario.
  // Si no hay sesión activa o está expirada, lanza Error (≡ HTTP 401).
  //
  // Replica: AuthMiddleware::verificar()
  //
  // En PHP lee $_SESSION['usuario'] (sin parámetro).
  // En GAS el token UUID viene del cliente y se pasa como argumento.
  //
  // Parámetros:
  //   token — UUID de sesión enviado por el frontend
  //
  // Retorna: objeto usuario { id, rol, nombre, ... }
  function verificar(token) {
    if (!token) {
      throw new Error('No autenticado. Debe iniciar sesión para acceder a este recurso.');
    }
    // AuthService.verificarToken ya valida expiración y estructura mínima
    // Lanza Error si el token no existe, está expirado o le falta id/rol
    var usuario = AuthService.verificarToken(token);

    // Validar campos mínimos esperados (igual que PHP: empty($usuario['id']) || empty($usuario['rol']))
    if (!usuario || !usuario.id || !usuario.rol) {
      throw new Error('Sesión inválida. Inicie sesión nuevamente.');
    }

    return usuario;
  }

  // ── obtenerUsuario ────────────────────────────────────────
  // Versión no-bloqueante. Retorna el usuario o null si no hay sesión.
  // Replica: AuthMiddleware::obtenerUsuario()
  //
  // Parámetros:
  //   token — UUID de sesión (puede ser null/undefined)
  //
  // Retorna: objeto usuario o null
  function obtenerUsuario(token) {
    if (!token) return null;
    try {
      var usuario = AuthService.verificarToken(token);
      if (!usuario || !usuario.id || !usuario.rol) return null;
      return usuario;
    } catch (e) {
      return null;
    }
  }

  return {
    verificar     : verificar,
    obtenerUsuario: obtenerUsuario
  };

})();


// =============================================================
// ── RoleMiddleware ────────────────────────────────────────────
// Verifica que el usuario autenticado posea el rol requerido.
// Replica: Src/Middleware/RoleMiddleware.php
// =============================================================
var RoleMiddleware = (function () {

  // Roles válidos definidos para el MVP.
  // Cualquier rol fuera de esta lista se considera inválido.
  // Replica: RoleMiddleware::ROLES_PERMITIDOS
  var ROLES_PERMITIDOS = ['docente', 'aprendiz'];

  // ── requerirRol ───────────────────────────────────────────
  // Verifica que el usuario autenticado tenga uno de los roles requeridos.
  // Si el rol es válido, retorna sin efecto y la ejecución continúa.
  // Si el rol no está autorizado, lanza Error (≡ HTTP 403).
  // Si un rol solicitado no existe en el sistema, lanza Error (≡ HTTP 500).
  //
  // Replica: RoleMiddleware::requerirRol($usuario, $rolesRequeridos)
  //
  // Parámetros:
  //   usuario         — objeto usuario obtenido de AuthMiddleware.verificar()
  //   rolesRequeridos — string o array de strings con los roles permitidos
  function requerirRol(usuario, rolesRequeridos) {
    var rolUsuario = String((usuario && usuario.rol) ? usuario.rol : '');
    rolesRequeridos = Array.isArray(rolesRequeridos) ? rolesRequeridos : [rolesRequeridos];

    // Rechazar roles que no están definidos en el sistema (≡ HTTP 500 en PHP)
    for (var i = 0; i < rolesRequeridos.length; i++) {
      if (ROLES_PERMITIDOS.indexOf(rolesRequeridos[i]) === -1) {
        throw new Error("El rol '" + rolesRequeridos[i] + "' no está definido en el sistema.");
      }
    }

    // Verificar que el rol del usuario esté en los roles requeridos (≡ HTTP 403)
    if (rolUsuario === '' || rolesRequeridos.indexOf(rolUsuario) === -1) {
      throw new Error('Acceso denegado. No tiene permisos para acceder a este recurso.');
    }
  }

  // ── soloDocente ───────────────────────────────────────────
  // Atajo semántico: verifica que el usuario sea docente.
  // Replica: RoleMiddleware::soloDocente($usuario)
  function soloDocente(usuario) {
    requerirRol(usuario, 'docente');
  }

  // ── soloAprendiz ──────────────────────────────────────────
  // Atajo semántico: verifica que el usuario sea aprendiz.
  // Replica: RoleMiddleware::soloAprendiz($usuario)
  function soloAprendiz(usuario) {
    requerirRol(usuario, 'aprendiz');
  }

  // ── tieneRol ──────────────────────────────────────────────
  // Verificación no-bloqueante. Retorna true/false sin lanzar Error.
  // Replica: RoleMiddleware::tieneRol($usuario, $rolesRequeridos)
  //
  // Parámetros:
  //   usuario         — objeto usuario autenticado
  //   rolesRequeridos — string o array de strings
  //
  // Retorna: boolean
  function tieneRol(usuario, rolesRequeridos) {
    var rolUsuario = String((usuario && usuario.rol) ? usuario.rol : '');
    rolesRequeridos = Array.isArray(rolesRequeridos) ? rolesRequeridos : [rolesRequeridos];
    return rolesRequeridos.indexOf(rolUsuario) !== -1;
  }

  return {
    ROLES_PERMITIDOS: ROLES_PERMITIDOS,
    requerirRol     : requerirRol,
    soloDocente     : soloDocente,
    soloAprendiz    : soloAprendiz,
    tieneRol        : tieneRol
  };

})();
