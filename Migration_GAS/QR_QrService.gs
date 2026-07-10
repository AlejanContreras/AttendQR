// =============================================================
// AttendQR — QR_QrService
// =============================================================
// Lógica de negocio del módulo QR.
// Replica: Src/Services/QrService.php
//
// Responsabilidades:
//   - Generar tokens QR para sesiones (invalidando el anterior)
//   - Obtener el token activo o auto-rotar si expiró
//   - Validar un token QR durante el registro de asistencia
//
// Dependencias:
//   QrRepository   — buscarToken, obtenerActivoPorSesion, crear,
//                    invalidarPrevios, incrementarUso, limpiarExpirados
//   SesionRepository — obtenerPorId, cerrarVencidas
//
// Constantes (idénticas al PHP):
//   SEGUNDOS_FALLBACK = 30  (duración del primer token si sesión no lo define)
//
// Token QR: 6 caracteres hexadecimales en mayúsculas.
//   PHP: strtoupper(bin2hex(random_bytes(3)))
//   GAS: Utilities.getSecureRandomBytes(3) → hex → toUpperCase()
// =============================================================

var QrService = (function () {

  var SEGUNDOS_FALLBACK = 30;

  // ── generar ───────────────────────────────────────────────────
  // Genera un nuevo token QR para una sesión abierta.
  // Invalida todos los tokens previos de la sesión antes de crear el nuevo.
  // Replica: QrService.generar()
  //
  // Parámetros:
  //   idSesion — id de la sesión
  //
  // Retorna: { id_sesion, token_valor, expira_en, segundos_restantes }
  function generar(idSesion) {
    SesionRepository.cerrarVencidas();

    var sesion = SesionRepository.obtenerPorId(idSesion);
    if (!sesion) throw new Error('Sesión no encontrada.');
    if (sesion.estado_sesion !== 'abierta') {
      throw new Error('La sesión no está abierta. No se puede generar un token QR.');
    }

    QrRepository.invalidarPrevios(idSesion);
    var segundos = parseInt(sesion.rotacion_qr_segundos, 10) || SEGUNDOS_FALLBACK;
    var resultado = _rotarYCrear(idSesion, segundos);

    return {
      id_sesion         : idSesion,
      token_valor       : resultado.token_valor,
      expira_en         : resultado.expira_en,
      segundos_restantes: segundos
    };
  }

  // ── tokenActivo ───────────────────────────────────────────────
  // Obtiene el token activo de una sesión. Si no existe o está expirado,
  // rota automáticamente creando uno nuevo (siempre que la sesión siga abierta).
  // Replica: QrService.tokenActivo()
  //
  // Retorna: { id_sesion, token_valor, expira_en, segundos_restantes }
  function tokenActivo(idSesion) {
    SesionRepository.cerrarVencidas();

    var tokenData = QrRepository.obtenerActivoPorSesion(idSesion);

    if (!tokenData) {
      // No hay token activo: verificar si la sesión sigue abierta antes de rotar
      var sesion = SesionRepository.obtenerPorId(idSesion);
      if (!sesion) throw new Error('Sesión no encontrada.');
      if (sesion.estado_sesion !== 'abierta') {
        throw new Error('La sesión no está abierta.');
      }
      var segundosSes = parseInt(sesion.rotacion_qr_segundos, 10) || SEGUNDOS_FALLBACK;
      QrRepository.invalidarPrevios(idSesion);
      var nuevo = _rotarYCrear(idSesion, segundosSes);
      return {
        id_sesion         : idSesion,
        token_valor       : nuevo.token_valor,
        expira_en         : nuevo.expira_en,
        segundos_restantes: _segundosRestantes(nuevo.expira_en)
      };
    }

    return {
      id_sesion         : idSesion,
      token_valor       : tokenData.token_valor,
      expira_en         : tokenData.expira_en,
      segundos_restantes: _segundosRestantes(tokenData.expira_en)
    };
  }

  // ── validar ───────────────────────────────────────────────────
  // Valida un token QR durante el registro de asistencia.
  // Verifica: token existe y activo, no expirado, sesión abierta.
  // Si todo es correcto, incrementa veces_usado.
  // Replica: QrService.validar()
  //
  // Parámetros:
  //   tokenValor  — código QR escaneado
  //   idAprendiz  — id del aprendiz que registra (incluido en el resultado)
  //
  // Retorna: { token_valido: true, id_token, id_sesion, id_aprendiz }
  function validar(tokenValor, idAprendiz) {
    SesionRepository.cerrarVencidas();

    var tokenData = QrRepository.buscarToken(tokenValor);

    if (!tokenData) {
      throw new Error('Token QR no válido o ya expirado.');
    }

    // Verificar que el token sigue activo (buscarToken ya filtra activo=1,
    // pero verificamos expiración explícitamente)
    var ahora   = _nowStr();
    if (!tokenData.expira_en || tokenData.expira_en <= ahora) {
      throw new Error('El token QR ha expirado.');
    }

    // Verificar que la sesión sigue abierta (JOIN ya lo trae en estado_sesion)
    if (tokenData.estado_sesion !== 'abierta') {
      throw new Error('La sesión no está abierta.');
    }

    // Incrementar contador de uso
    QrRepository.incrementarUso(tokenData.id_token);

    return {
      token_valido : true,
      id_token     : tokenData.id_token,
      id_sesion    : tokenData.id_sesion,
      id_aprendiz  : idAprendiz
    };
  }

  // ── Helpers privados ──────────────────────────────────────────

  // Invalida tokens previos, genera uno nuevo y lo persiste.
  // Replica: QrService.rotarYCrear() (private)
  function _rotarYCrear(idSesion, segundos) {
    var tokenValor = _generarToken();
    var expiraEn   = _calcularExpiracion(segundos);
    QrRepository.crear(idSesion, tokenValor, expiraEn);
    return { token_valor: tokenValor, expira_en: expiraEn };
  }

  // Genera 3 bytes aleatorios seguros → 6 chars hex mayúsculas.
  // Replica: strtoupper(bin2hex(random_bytes(3)))
  function _generarToken() {
    var bytes = Utilities.getSecureRandomBytes(3);
    var hex   = '';
    for (var i = 0; i < bytes.length; i++) {
      var b = (bytes[i] < 0 ? bytes[i] + 256 : bytes[i]).toString(16);
      hex  += (b.length === 1 ? '0' : '') + b;
    }
    return hex.toUpperCase(); // 6 chars hex MAYÚSCULAS
  }

  // Calcula fecha de expiración sumando `segundos` al momento actual.
  // Replica: date('Y-m-d H:i:s', time() + $segundos)
  function _calcularExpiracion(segundos) {
    var d = new Date(new Date().getTime() + segundos * 1000);
    return Utilities.formatDate(d, Session.getScriptTimeZone(), 'yyyy-MM-dd HH:mm:ss');
  }

  // Retorna la cadena datetime actual formateada.
  function _nowStr() {
    return Utilities.formatDate(new Date(), Session.getScriptTimeZone(), 'yyyy-MM-dd HH:mm:ss');
  }

  // Calcula los segundos restantes hasta expiraEn (mínimo 0).
  // Replica: max(0, strtotime($expira_en) - time())
  function _segundosRestantes(expiraEn) {
    if (!expiraEn) return 0;
    var exp = expiraEn instanceof Date
      ? expiraEn
      : new Date(String(expiraEn).replace(' ', 'T'));
    return Math.max(0, Math.round((exp.getTime() - new Date().getTime()) / 1000));
  }

  // ── API pública ───────────────────────────────────────────────
  return {
    generar      : generar,
    tokenActivo  : tokenActivo,
    validar      : validar
  };

})();
