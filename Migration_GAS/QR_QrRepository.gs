// =============================================================
// AttendQR — QR_QrRepository
// =============================================================
// Capa de datos del módulo QR. Accede a Google Sheets.
// Replica: Src/Repositories/QrRepository.php  (PDO → SpreadsheetApp)
//
// Usa AUTH_SPREADSHEET_ID definido en AUTH_AuthRepository.gs.
//
// Opera exclusivamente sobre la hoja "tokens_qr":
//   A:id_token   B:id_sesion   C:token_valor
//   D:creado_en  E:expira_en   F:activo   G:veces_usado
//
//   activo = 1  → vigente
//   activo = '' → rotado/invalidado (NULL en MySQL)
//
// Hoja de apoyo para JOIN:
//   "sesiones_asistencia" — A:id_sesion ... E:estado_sesion
//
// NOTA: TokenRepository (Fase 12) ya implementa métodos sobre tokens_qr
// (buscarPorValor, obtenerActivoPorSesion, crear, rotarPorSesion,
// invalidarPorSesion, incrementarUso, limpiarExpirados).
// QrRepository es la fachada canónica que QrService usa, con adición
// del método buscarToken() que incluye el estado_sesion via JOIN.
// Los métodos compartidos delegan a TokenRepository para evitar
// duplicar lógica de acceso a datos.
// =============================================================

var QrRepository = (function () {

  // ── Helpers de acceso ─────────────────────────────────────────

  function _ss() {
    return SpreadsheetApp.openById(AUTH_SPREADSHEET_ID);
  }

  function _getSheetSafe(nombre) {
    try { return _ss().getSheetByName(nombre); } catch (e) { return null; }
  }

  function _nowStr() {
    return Utilities.formatDate(new Date(), Session.getScriptTimeZone(), 'yyyy-MM-dd HH:mm:ss');
  }

  function _cellDatetime(val) {
    if (val === null || val === undefined || val === '') return null;
    if (val instanceof Date) {
      return Utilities.formatDate(val, Session.getScriptTimeZone(), 'yyyy-MM-dd HH:mm:ss');
    }
    return String(val);
  }

  // ── buscarToken ───────────────────────────────────────────────
  // SELECT t.*, s.estado_sesion
  //   FROM tokens_qr t
  //   JOIN sesiones_asistencia s ON t.id_sesion = s.id_sesion
  //  WHERE t.token_valor = :token AND t.activo = 1
  //  LIMIT 1
  //
  // Incluye estado_sesion para que QrService.validar() pueda verificar
  // si la sesión sigue abierta sin llamar a SesionRepository.
  // Replica: QrRepository.buscarToken()
  function buscarToken(tokenValor) {
    var tokenSheet = _getSheetSafe('tokens_qr');
    if (!tokenSheet) return null;
    var tokRows = tokenSheet.getDataRange().getValues();

    for (var i = 1; i < tokRows.length; i++) {
      if (String(tokRows[i][2]) !== String(tokenValor)) continue;
      if (tokRows[i][5] !== 1 && tokRows[i][5] !== '1') continue; // activo = 1 únicamente

      var idSesion    = tokRows[i][1];
      var estadoSesion= null;

      // JOIN: buscar estado_sesion en sesiones_asistencia
      var sesSheet = _getSheetSafe('sesiones_asistencia');
      if (sesSheet) {
        var sesRows = sesSheet.getDataRange().getValues();
        for (var j = 1; j < sesRows.length; j++) {
          if (sesRows[j][0] == idSesion) {
            estadoSesion = String(sesRows[j][4] || '');
            break;
          }
        }
      }

      return {
        id_token    : tokRows[i][0],
        id_sesion   : idSesion,
        token_valor : String(tokRows[i][2]),
        creado_en   : _cellDatetime(tokRows[i][3]),
        expira_en   : _cellDatetime(tokRows[i][4]),
        activo      : 1,
        veces_usado : parseInt(tokRows[i][6], 10) || 0,
        estado_sesion: estadoSesion
      };
    }
    return null;
  }

  // ── obtenerActivoPorSesion ────────────────────────────────────
  // SELECT ... FROM tokens_qr
  //  WHERE id_sesion = :id AND activo = 1 AND expira_en > NOW()
  //  LIMIT 1
  // Delega a TokenRepository (misma lógica, ya implementado).
  function obtenerActivoPorSesion(idSesion) {
    return TokenRepository.obtenerActivoPorSesion(idSesion);
  }

  // ── crear ─────────────────────────────────────────────────────
  // INSERT INTO tokens_qr (id_sesion, token_valor, expira_en, activo)
  //   VALUES (:id, :token, :expira, 1)
  // Delega a TokenRepository.
  function crear(idSesion, tokenValor, expiraEn) {
    return TokenRepository.crear(idSesion, tokenValor, expiraEn);
  }

  // ── invalidarPrevios ──────────────────────────────────────────
  // UPDATE tokens_qr SET activo = NULL
  //  WHERE id_sesion = :id AND activo = 1
  // En Sheets: activo = '' (equivalente a NULL).
  // Replica: QrRepository.invalidarPrevios()
  // Delega a TokenRepository.rotarPorSesion() (misma operación).
  function invalidarPrevios(idSesion) {
    return TokenRepository.rotarPorSesion(idSesion);
  }

  // ── invalidarPorSesion ────────────────────────────────────────
  // Alias semántico de invalidarPrevios() usado al cerrar sesión.
  // Replica: QrRepository.invalidarPorSesion()
  function invalidarPorSesion(idSesion) {
    return TokenRepository.rotarPorSesion(idSesion);
  }

  // ── incrementarUso ────────────────────────────────────────────
  // UPDATE tokens_qr SET veces_usado = veces_usado + 1 WHERE id_token = :id
  // Delega a TokenRepository.
  function incrementarUso(idToken) {
    return TokenRepository.incrementarUso(idToken);
  }

  // ── limpiarExpirados ──────────────────────────────────────────
  // DELETE FROM tokens_qr WHERE expira_en < NOW() AND activo IS NULL
  // Delega a TokenRepository.
  function limpiarExpirados() {
    return TokenRepository.limpiarExpirados();
  }

  // ── API pública ───────────────────────────────────────────────
  return {
    buscarToken          : buscarToken,
    obtenerActivoPorSesion: obtenerActivoPorSesion,
    crear                : crear,
    invalidarPrevios     : invalidarPrevios,
    invalidarPorSesion   : invalidarPorSesion,
    incrementarUso       : incrementarUso,
    limpiarExpirados     : limpiarExpirados
  };

})();
