// =============================================================
// AttendQR — APRENDIZ_AprendizService
// =============================================================
// Lógica de negocio del módulo Aprendiz.
// Replica: Src/Services/AprendizService.php
//
// Contraseñas: se usa AuthService.hashPassword() (SHA-256) para
// mantener consistencia con el módulo AUTH migrado.
//
// Verificación de password actual: AuthService.hashPassword(plano)
// comparado contra el hash almacenado (sin bcrypt, sin password_verify).
// =============================================================

var AprendizService = (function () {

  // ─── Consulta ────────────────────────────────────────────

  function consultar(idAprendiz) {
    var aprendiz = AprendizRepository.obtenerPorId(idAprendiz);
    if (!aprendiz) throw new Error('Aprendiz no encontrado.');
    var result = _sinHash(aprendiz);
    return result;
  }

  function listar(idFicha, estado, documento, cuentaEstado) {
    var activo = estado === 'activo' ? 1 : estado === 'inactivo' ? 0 : null;
    var cuentaActiva = cuentaEstado === 'activada' ? 1 : cuentaEstado === 'pendiente' ? 0 : null;

    var aprendices = AprendizRepository.listar(idFicha || null, activo, documento || null, cuentaActiva);

    return {
      aprendices: aprendices.map(_sinHash),
      total     : aprendices.length
    };
  }

  // ─── Registro completo (docente crea cuenta lista) ───────

  function registrar(numeroDocumento, nombres, apellidos, password, idFicha) {
    numeroDocumento = String(numeroDocumento).trim();
    nombres         = String(nombres).trim();
    apellidos       = String(apellidos).trim();

    if (AprendizRepository.existeDocumento(numeroDocumento)) {
      throw new Error('El documento ya está registrado en el sistema.');
    }

    var ficha = AprendizRepository.obtenerFichaPorId(idFicha);
    if (!ficha) throw new Error('Ficha no encontrada.');
    if (ficha.activa != 1) throw new Error('La ficha no está activa.');

    var passwordHash = AuthService.hashPassword(password);
    var id = AprendizRepository.crear(numeroDocumento, nombres, apellidos, passwordHash, idFicha, 1);

    return _sinHash(AprendizRepository.obtenerPorId(id) || {
      id_aprendiz      : id,
      numero_documento : numeroDocumento,
      nombres          : nombres,
      apellidos        : apellidos,
      id_ficha         : idFicha,
      activo           : 1,
      cuenta_activada  : 1
    });
  }

  // ─── Auto-registro en dos pasos (flujo aprendiz) ─────────

  // Paso 1: verifica que el documento exista y esté pendiente de activación
  function verificarParaRegistro(documento) {
    documento = String(documento).trim();
    var aprendiz = AprendizRepository.buscarPorDocumento(documento);

    if (!aprendiz) {
      throw new Error('Tu documento no está registrado en ninguna ficha. Contacta a tu instructor.');
    }
    if (aprendiz.activo != 1) {
      throw new Error('Tu cuenta está desactivada. Contacta a tu instructor.');
    }
    if (aprendiz.cuenta_activada == 1) {
      throw new Error('Ya tienes una cuenta activa. Inicia sesión normalmente.');
    }

    // Solo datos públicos — nunca el hash ni el id completo
    return {
      id_aprendiz      : aprendiz.id_aprendiz,
      nombres          : aprendiz.nombres,
      apellidos        : aprendiz.apellidos,
      codigo_ficha     : aprendiz.codigo_ficha     || '',
      nombre_programa  : aprendiz.nombre_programa  || ''
    };
  }

  // Paso 2: activa la cuenta estableciendo la contraseña real
  // Devuelve datos de sesión en el mismo formato que AuthService.loginAprendiz
  function activarCuenta(idAprendiz, password) {
    if (String(password).length < 8) {
      throw new Error('La contraseña debe tener al menos 8 caracteres.');
    }

    var aprendiz = AprendizRepository.obtenerPorId(idAprendiz);
    if (!aprendiz) throw new Error('Aprendiz no encontrado.');

    if (aprendiz.cuenta_activada == 1) {
      throw new Error('Esta cuenta ya fue activada. Inicia sesión normalmente.');
    }

    var hash = AuthService.hashPassword(password);
    var filasActualizadas = AprendizRepository.activarCuenta(idAprendiz, hash);

    if (filasActualizadas === 0) {
      // Race condition — otra petición ya activó la cuenta
      throw new Error('Esta cuenta ya fue activada. Inicia sesión normalmente.');
    }

    // Mismo formato de sesión que AuthService.loginAprendiz
    return {
      id              : aprendiz.id_aprendiz,
      nombres         : aprendiz.nombres,
      apellidos       : aprendiz.apellidos,
      numero_documento: aprendiz.numero_documento,
      id_ficha        : aprendiz.id_ficha,
      codigo_ficha    : aprendiz.codigo_ficha    || '',
      nombre_programa : aprendiz.nombre_programa || '',
      rol             : 'aprendiz'
    };
  }

  // ─── Pre-registro para importación ───────────────────────

  // Crea un aprendiz pre-registrado (cuenta_activada = 0, contraseña placeholder)
  function preRegistrar(numeroDocumento, nombres, apellidos, idFicha) {
    numeroDocumento = String(numeroDocumento).trim();
    nombres         = String(nombres).trim();
    apellidos       = String(apellidos).trim();

    if (AprendizRepository.existeDocumento(numeroDocumento)) {
      throw new Error("El documento '" + numeroDocumento + "' ya está registrado.");
    }

    var ficha = AprendizRepository.obtenerFichaPorId(idFicha);
    if (!ficha) throw new Error('Ficha ID ' + idFicha + ' no encontrada.');
    if (ficha.activa != 1) throw new Error("La ficha '" + ficha.codigo_ficha + "' no está activa.");

    // Contraseña placeholder inaccesible — aprendiz no puede iniciar sesión
    // hasta completar su auto-registro con verificarParaRegistro + activarCuenta
    var placeholderHash = AuthService.hashPassword('PLACEHOLDER_' + Utilities.getUuid());

    var id = AprendizRepository.crear(numeroDocumento, nombres, apellidos, placeholderHash, idFicha, 0);

    return _sinHash(AprendizRepository.obtenerPorId(id) || {
      id_aprendiz     : id,
      numero_documento: numeroDocumento,
      nombres         : nombres,
      apellidos       : apellidos,
      id_ficha        : idFicha,
      activo          : 1,
      cuenta_activada : 0
    });
  }

  // Importa un lote de aprendices reutilizando preRegistrar()
  // filas: [{numero_documento, nombres, apellidos, codigo_ficha}, ...]
  function importar(filas) {
    var exitosos   = 0;
    var errores    = [];
    var fichaCache = {};

    for (var i = 0; i < filas.length; i++) {
      var nFila    = i + 1;
      var fila     = filas[i];
      var documento = String(fila.numero_documento || '').trim();

      if (!documento) {
        errores.push({ fila: nFila, documento: '', error: 'El campo numero_documento está vacío.' });
        continue;
      }

      var codigoFicha = String(fila.codigo_ficha || '').trim();
      if (!codigoFicha) {
        errores.push({ fila: nFila, documento: documento, error: 'El campo codigo_ficha está vacío.' });
        continue;
      }

      // Cache de fichas ya resueltas en esta importación
      if (!fichaCache.hasOwnProperty(codigoFicha)) {
        fichaCache[codigoFicha] = AprendizRepository.obtenerFichaPorCodigo(codigoFicha);
      }
      var ficha = fichaCache[codigoFicha];

      if (!ficha) {
        errores.push({ fila: nFila, documento: documento, error: "Ficha '" + codigoFicha + "' no encontrada." });
        continue;
      }

      try {
        preRegistrar(
          documento,
          String(fila.nombres   || '').trim(),
          String(fila.apellidos || '').trim(),
          ficha.id_ficha
        );
        exitosos++;
      } catch (e) {
        errores.push({ fila: nFila, documento: documento, error: e.message });
      }
    }

    return {
      exitosos: exitosos,
      errores : errores,
      total   : filas.length
    };
  }

  // ─── Actualización ───────────────────────────────────────

  function actualizar(idAprendiz, datos) {
    var aprendiz = AprendizRepository.obtenerPorId(idAprendiz);
    if (!aprendiz) throw new Error('Aprendiz no encontrado.');

    // Cambio de contraseña: verifica password_actual antes de aplicar password_nueva
    if (datos.hasOwnProperty('password_actual')) {
      var hashActual = AuthService.hashPassword(String(datos.password_actual));
      if (hashActual !== aprendiz.password_hash) {
        throw new Error('La contraseña actual es incorrecta.');
      }
      delete datos.password_actual;
    }

    if (datos.hasOwnProperty('password_nueva')) {
      datos.password_hash = AuthService.hashPassword(String(datos.password_nueva));
      delete datos.password_nueva;
    }

    // password plano directo (flujo docente)
    if (datos.hasOwnProperty('password')) {
      datos.password_hash = AuthService.hashPassword(String(datos.password));
      delete datos.password;
    }

    AprendizRepository.actualizar(idAprendiz, datos);

    return _sinHash(AprendizRepository.obtenerPorId(idAprendiz) || aprendiz);
  }

  // ─── Eliminación ─────────────────────────────────────────

  function eliminar(idAprendiz) {
    var aprendiz = AprendizRepository.obtenerPorId(idAprendiz);
    if (!aprendiz) throw new Error('Aprendiz no encontrado.');

    AprendizRepository.eliminar(idAprendiz);
    return { success: true, message: 'Aprendiz eliminado correctamente.' };
  }

  // ── Helper: quita password_hash antes de exponer al exterior ─
  function _sinHash(ap) {
    var resultado = {};
    for (var k in ap) {
      if (k !== 'password_hash') resultado[k] = ap[k];
    }
    return resultado;
  }

  return {
    consultar            : consultar,
    listar               : listar,
    registrar            : registrar,
    verificarParaRegistro: verificarParaRegistro,
    activarCuenta        : activarCuenta,
    preRegistrar         : preRegistrar,
    importar             : importar,
    actualizar           : actualizar,
    eliminar             : eliminar
  };

})();
