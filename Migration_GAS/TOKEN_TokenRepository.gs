// =============================================================
// AttendQR — TOKEN_TokenRepository
// =============================================================
// Capa de datos del módulo Token. Accede a Google Sheets.
// Replica: Src/Repositories/TokenRepository.php  (PDO → SpreadsheetApp)
//
// Usa AUTH_SPREADSHEET_ID definido en AUTH_AuthRepository.gs.
//
// Gestiona DOS hojas:
//
// ── tokens_qr (QR de sesiones — llamado por QrService) ──────────
//   A:id_token  B:id_sesion  C:token_valor  D:creado_en
//   E:expira_en  F:activo  G:veces_usado
//
//   activo = 1   → vigente
//   activo = ''  → rotado/invalidado  (NULL en MySQL: permite duplicados
//                  bajo el UNIQUE(id_sesion, activo) del schema)
//
// ── tokens_auth (auth de usuario — llamado por TokenService) ────
//   A:id  B:id_usuario  C:token_hash  D:tipo  E:rol
//   F:expiracion  G:revocado  H:creado_en
//
//   tipo: 'acceso' | 'refresco'
//   revocado: 0 = vigente, 1 = revocado
//
// NOTA: En el MVP PHP los tokens de autenticación de usuario se
// manejan en $_SESSION; los métodos de tokens_auth completan la
// implementación del TokenService que el PHP dejó pendiente.
// =============================================================

var TokenRepository = (function () {

  // ── Helpers de acceso ────────────────────────────────────

  function _ss() {
    return SpreadsheetApp.openById(AUTH_SPREADSHEET_ID);
  }

  function _getSheet(nombre) {
    var sheet = _ss().getSheetByName(nombre);
    if (!sheet) throw new Error('Hoja "' + nombre + '" no encontrada.');
    return sheet;
  }

  function _getSheetSafe(nombre) {
    try { return _ss().getSheetByName(nombre); } catch (e) { return null; }
  }

  function _nowStr() {
    return Utilities.formatDate(new Date(), Session.getScriptTimeZone(), 'yyyy-MM-dd HH:mm:ss');
  }

  function _cellDatetime(val) {
    if (!val && val !== 0) return null;
    if (val === '') return null;
    if (val instanceof Date) {
      return Utilities.formatDate(val, Session.getScriptTimeZone(), 'yyyy-MM-dd HH:mm:ss');
    }
    return String(val);
  }

  function _nextId(sheet) {
    var rows  = sheet.getDataRange().getValues();
    var maxId = 0;
    for (var i = 1; i < rows.length; i++) {
      var id = parseInt(rows[i][0], 10);
      if (!isNaN(id) && id > maxId) maxId = id;
    }
    return maxId + 1;
  }

  // ── Mapper: row → token QR ───────────────────────────────

  function _rowToTokenQr(row) {
    return {
      id_token    : row[0],
      id_sesion   : row[1],
      token_valor : String(row[2]),
      creado_en   : _cellDatetime(row[3]),
      expira_en   : _cellDatetime(row[4]),
      activo      : row[5] === 1 || row[5] === '1' ? 1 : null,
      veces_usado : parseInt(row[6], 10) || 0
    };
  }

  // ── Mapper: row → token auth ─────────────────────────────

  function _rowToTokenAuth(row) {
    return {
      id         : row[0],
      id_usuario : row[1],
      token_hash : String(row[2]),
      tipo       : String(row[3]),
      rol        : String(row[4]),
      expiracion : _cellDatetime(row[5]),
      revocado   : parseInt(row[6], 10) || 0,
      creado_en  : _cellDatetime(row[7])
    };
  }

  // ==========================================================
  // ── TOKENS QR (tokens_qr) ─────────────────────────────────
  // Flujo: QrService → TokenRepository
  // ==========================================================

  // ── buscarPorValor ────────────────────────────────────────
  // SELECT ... FROM tokens_qr WHERE token_valor = :token LIMIT 1
  function buscarPorValor(tokenValor) {
    var sheet = _getSheetSafe('tokens_qr');
    if (!sheet) return null;
    var rows = sheet.getDataRange().getValues();
    for (var i = 1; i < rows.length; i++) {
      if (String(rows[i][2]) === String(tokenValor)) {
        return _rowToTokenQr(rows[i]);
      }
    }
    return null;
  }

  // ── obtenerActivoPorSesion ────────────────────────────────
  // SELECT ... FROM tokens_qr
  //   WHERE id_sesion = :id AND activo = 1 AND expira_en > NOW() LIMIT 1
  function obtenerActivoPorSesion(idSesion) {
    var sheet = _getSheetSafe('tokens_qr');
    if (!sheet) return null;
    var rows = sheet.getDataRange().getValues();
    var now  = _nowStr();
    for (var i = 1; i < rows.length; i++) {
      if (rows[i][1] != idSesion) continue;
      if (rows[i][5] !== 1 && rows[i][5] !== '1') continue;
      var expira = _cellDatetime(rows[i][4]);
      if (!expira || expira <= now) continue;
      return {
        id_token   : rows[i][0],
        token_valor: String(rows[i][2]),
        expira_en  : expira,
        veces_usado: parseInt(rows[i][6], 10) || 0
      };
    }
    return null;
  }

  // ── estaActivo ────────────────────────────────────────────
  // SELECT COUNT(*) FROM tokens_qr
  //   WHERE token_valor = :token AND activo = 1 AND expira_en > NOW()
  function estaActivo(tokenValor) {
    var sheet = _getSheetSafe('tokens_qr');
    if (!sheet) return false;
    var rows = sheet.getDataRange().getValues();
    var now  = _nowStr();
    for (var i = 1; i < rows.length; i++) {
      if (String(rows[i][2]) !== String(tokenValor)) continue;
      if (rows[i][5] !== 1 && rows[i][5] !== '1') continue;
      var expira = _cellDatetime(rows[i][4]);
      if (expira && expira > now) return true;
    }
    return false;
  }

  // ── crear (QR) ────────────────────────────────────────────
  // INSERT INTO tokens_qr (id_sesion, token_valor, expira_en, activo)
  //   VALUES (:id_sesion, :token, :expira_en, 1)
  function crear(idSesion, tokenValor, expiraEn) {
    var sheet = _getSheet('tokens_qr');
    var newId = _nextId(sheet);
    var ahora = _nowStr();
    // A:id_token B:id_sesion C:token_valor D:creado_en E:expira_en F:activo G:veces_usado
    sheet.appendRow([newId, idSesion, tokenValor, ahora, expiraEn, 1, 0]);
    return newId;
  }

  // ── rotarPorSesion ────────────────────────────────────────
  // UPDATE tokens_qr SET activo = NULL
  //   WHERE id_sesion = :id AND activo = 1
  // En Sheets: activo = '' (equivalente a NULL; permite múltiples rotados)
  function rotarPorSesion(idSesion) {
    var sheet = _getSheetSafe('tokens_qr');
    if (!sheet) return 0;
    var rows  = sheet.getDataRange().getValues();
    var count = 0;
    for (var i = 1; i < rows.length; i++) {
      if (rows[i][1] != idSesion) continue;
      if (rows[i][5] !== 1 && rows[i][5] !== '1') continue;
      sheet.getRange(i + 1, 6).setValue(''); // col F = activo → '' (NULL)
      count++;
    }
    return count;
  }

  // ── invalidarPorSesion ────────────────────────────────────
  // Alias semántico de rotarPorSesion() usado por SesionService al cerrar.
  function invalidarPorSesion(idSesion) {
    return rotarPorSesion(idSesion);
  }

  // ── incrementarUso ────────────────────────────────────────
  // UPDATE tokens_qr SET veces_usado = veces_usado + 1 WHERE id_token = :id
  function incrementarUso(idToken) {
    var sheet = _getSheetSafe('tokens_qr');
    if (!sheet) return 0;
    var rows = sheet.getDataRange().getValues();
    for (var i = 1; i < rows.length; i++) {
      if (rows[i][0] == idToken) {
        var actual = parseInt(rows[i][6], 10) || 0;
        sheet.getRange(i + 1, 7).setValue(actual + 1); // col G
        return 1;
      }
    }
    return 0;
  }

  // ── limpiarExpirados ──────────────────────────────────────
  // DELETE FROM tokens_qr WHERE expira_en < NOW() AND activo IS NULL
  // (solo limpia los rotados — no los activos aunque hayan expirado)
  function limpiarExpirados() {
    var sheet = _getSheetSafe('tokens_qr');
    if (!sheet) return 0;
    var rows  = sheet.getDataRange().getValues();
    var now   = _nowStr();
    var count = 0;
    // Recorrer de abajo para arriba para no alterar los índices al borrar
    for (var i = rows.length - 1; i >= 1; i--) {
      var activo = rows[i][5];
      if (activo !== '' && activo !== null) continue; // solo rotados (NULL → '')
      var expira = _cellDatetime(rows[i][4]);
      if (!expira || expira >= now) continue;
      sheet.deleteRow(i + 1);
      count++;
    }
    return count;
  }

  // ==========================================================
  // ── TOKENS DE AUTENTICACIÓN (tokens_auth) ─────────────────
  // Flujo: TokenService → TokenRepository
  // Completa la implementación que el PHP dejó pendiente
  // (en el MVP PHP se usaba $_SESSION en su lugar).
  // ==========================================================

  // ── persistirRefresco ─────────────────────────────────────
  // INSERT INTO tokens_auth (id_usuario, token_hash, tipo, rol, expiracion, revocado)
  //   VALUES (:id, :hash, 'refresco', :rol, :expiracion, 0)
  function persistirRefresco(idUsuario, tokenHash, expiracion, rol) {
    var sheet = _getSheet('tokens_auth');
    var newId = _nextId(sheet);
    var ahora = _nowStr();
    // A:id B:id_usuario C:token_hash D:tipo E:rol F:expiracion G:revocado H:creado_en
    sheet.appendRow([newId, idUsuario, tokenHash, 'refresco', rol, expiracion, 0, ahora]);
    return newId;
  }

  // ── buscarAcceso ──────────────────────────────────────────
  // SELECT ... FROM tokens_auth WHERE token_hash = :hash AND tipo = 'acceso'
  // NOTA: en generar() solo se almacena el token de refresco,
  // por lo que este método retornará null mientras el flujo no cambie.
  function buscarAcceso(tokenHash) {
    var sheet = _getSheetSafe('tokens_auth');
    if (!sheet) return null;
    var rows  = sheet.getDataRange().getValues();
    for (var i = 1; i < rows.length; i++) {
      if (String(rows[i][2]) === String(tokenHash) && String(rows[i][3]) === 'acceso') {
        return _rowToTokenAuth(rows[i]);
      }
    }
    return null;
  }

  // ── buscarRefresco ────────────────────────────────────────
  // SELECT ... FROM tokens_auth WHERE token_hash = :hash AND tipo = 'refresco'
  function buscarRefresco(refrescoHash) {
    var sheet = _getSheetSafe('tokens_auth');
    if (!sheet) return null;
    var rows  = sheet.getDataRange().getValues();
    for (var i = 1; i < rows.length; i++) {
      if (String(rows[i][2]) === String(refrescoHash) && String(rows[i][3]) === 'refresco') {
        return _rowToTokenAuth(rows[i]);
      }
    }
    return null;
  }

  // ── revocar ───────────────────────────────────────────────
  // UPDATE tokens_auth SET revocado = 1 WHERE token_hash = :hash
  function revocar(tokenHash) {
    var sheet = _getSheetSafe('tokens_auth');
    if (!sheet) return 0;
    var rows  = sheet.getDataRange().getValues();
    var count = 0;
    for (var i = 1; i < rows.length; i++) {
      if (String(rows[i][2]) === String(tokenHash)) {
        sheet.getRange(i + 1, 7).setValue(1); // col G = revocado
        count++;
      }
    }
    return count;
  }

  return {
    // tokens_qr
    buscarPorValor          : buscarPorValor,
    obtenerActivoPorSesion  : obtenerActivoPorSesion,
    estaActivo              : estaActivo,
    crear                   : crear,
    rotarPorSesion          : rotarPorSesion,
    invalidarPorSesion      : invalidarPorSesion,
    incrementarUso          : incrementarUso,
    limpiarExpirados        : limpiarExpirados,
    // tokens_auth
    persistirRefresco       : persistirRefresco,
    buscarAcceso            : buscarAcceso,
    buscarRefresco          : buscarRefresco,
    revocar                 : revocar
  };

})();
