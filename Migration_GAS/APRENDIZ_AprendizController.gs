// =============================================================
// AttendQR — APRENDIZ_AprendizController
// =============================================================
// Funciones públicas expuestas al frontend via google.script.run.
// Replica: Src/Controllers/AprendizController.php
//
// Mapeo de rutas PHP → funciones GAS:
//   GET    /api/aprendices/listar            → aprendizListar(filtros, token)
//   GET    /api/aprendices/consultar/{id}    → aprendizConsultar(id, token)
//   GET    /api/aprendices/ficha/{id}        → aprendizListarPorFicha(idFicha, token)
//   POST   /api/aprendices/registrar         → aprendizRegistrar(payload, token)
//   POST   /api/aprendices/importar          → aprendizImportar(payload, token)
//   PUT    /api/aprendices/actualizar/{id}   → aprendizActualizar(id, datos, token)
//   DELETE /api/aprendices/eliminar/{id}     → aprendizEliminar(id, token)
//   POST   /api/auth/verificar-documento     → aprendizVerificarDocumento(payload)
//   POST   /api/auth/activar-cuenta          → aprendizActivarCuenta(payload)
//
// Nota sobre sesión: todas las funciones que requieren auth reciben el token
// (guardado en sessionStorage del cliente) y lo verifican con AuthService.
// Si el cliente es aprendiz, las funciones con restricción de acceso solo
// permiten operar sobre su propio registro.
//
// Todas las funciones devuelven: { success, message, data? }
// =============================================================

// ── aprendizListar ────────────────────────────────────────────
// filtros: { id_ficha?, estado?, documento?, cuenta? }
// token: string (sesión)
function aprendizListar(filtros, token) {
  try {
    AuthService.verificarToken(token);
    filtros = filtros || {};
    var resultado = AprendizService.listar(
      filtros.id_ficha || null,
      filtros.estado   || null,
      filtros.documento|| null,
      filtros.cuenta   || null
    );
    return { success: true, message: 'Aprendices obtenidos correctamente.', data: resultado };
  } catch (e) {
    return { success: false, message: e.message };
  }
}

// ── aprendizConsultar ─────────────────────────────────────────
// id: number (id_aprendiz)
// token: string
function aprendizConsultar(id, token) {
  try {
    var usuario = AuthService.verificarToken(token);
    _verificarAccesoAprendiz(usuario, id);
    var aprendiz = AprendizService.consultar(id);
    return { success: true, message: 'Aprendiz encontrado.', data: aprendiz };
  } catch (e) {
    return { success: false, message: e.message };
  }
}

// ── aprendizListarPorFicha ────────────────────────────────────
// idFicha: number
// token: string
function aprendizListarPorFicha(idFicha, token) {
  try {
    AuthService.verificarToken(token);
    var resultado = AprendizService.listar(idFicha, null, null, null);
    return { success: true, message: 'Aprendices de la ficha obtenidos correctamente.', data: resultado };
  } catch (e) {
    return { success: false, message: e.message };
  }
}

// ── aprendizRegistrar ─────────────────────────────────────────
// payload: { numero_documento, nombres, apellidos, password, id_ficha }
// token: string (debe ser docente)
function aprendizRegistrar(payload, token) {
  try {
    AuthService.verificarToken(token);
    payload = payload || {};

    var requeridos = ['numero_documento', 'nombres', 'apellidos', 'password', 'id_ficha'];
    for (var i = 0; i < requeridos.length; i++) {
      if (!payload[requeridos[i]] && payload[requeridos[i]] !== 0) {
        return { success: false, message: "El campo '" + requeridos[i] + "' es obligatorio." };
      }
    }

    var aprendiz = AprendizService.registrar(
      String(payload.numero_documento),
      String(payload.nombres),
      String(payload.apellidos),
      String(payload.password),
      parseInt(payload.id_ficha, 10)
    );
    return { success: true, message: 'Aprendiz registrado correctamente.', data: aprendiz };
  } catch (e) {
    return { success: false, message: e.message };
  }
}

// ── aprendizImportar ──────────────────────────────────────────
// payload: { aprendices: [{numero_documento, nombres, apellidos, codigo_ficha}, ...] }
// token: string (debe ser docente)
function aprendizImportar(payload, token) {
  try {
    var usuario = AuthService.verificarToken(token);
    if (usuario.rol !== 'docente') {
      return { success: false, message: 'Solo los docentes pueden importar aprendices.' };
    }

    payload = payload || {};
    var filas = payload.aprendices;
    if (!Array.isArray(filas) || filas.length === 0) {
      return { success: false, message: "Se requiere un array 'aprendices' con al menos una fila." };
    }

    var resultado = AprendizService.importar(filas);
    var exitosos  = resultado.exitosos;
    var nErrores  = resultado.errores.length;
    return {
      success: true,
      message: 'Importación completada: ' + exitosos + ' registrados, ' + nErrores + ' con errores.',
      data   : resultado
    };
  } catch (e) {
    return { success: false, message: e.message };
  }
}

// ── aprendizActualizar ────────────────────────────────────────
// id: number (id_aprendiz)
// datos: { nombres?, apellidos?, password_actual?, password_nueva?, id_ficha?, activo? }
// token: string
function aprendizActualizar(id, datos, token) {
  try {
    var usuario = AuthService.verificarToken(token);
    _verificarAccesoAprendiz(usuario, id);

    if (!datos || Object.keys(datos).length === 0) {
      return { success: false, message: 'No se recibieron datos para actualizar.' };
    }

    var aprendiz = AprendizService.actualizar(id, datos);

    // Si el aprendiz actualizó su propio perfil, renovar la sesión con nombre actualizado
    var resultado = { success: true, message: 'Aprendiz actualizado correctamente.', data: aprendiz };
    if (usuario.rol === 'aprendiz' && usuario.id == id) {
      resultado.sesionActualizada = true; // señal para que el cliente actualice sessionStorage
    }
    return resultado;
  } catch (e) {
    return { success: false, message: e.message };
  }
}

// ── aprendizEliminar ──────────────────────────────────────────
// id: number (id_aprendiz)
// token: string
function aprendizEliminar(id, token) {
  try {
    AuthService.verificarToken(token);
    var resultado = AprendizService.eliminar(id);
    return { success: true, message: resultado.message || 'Aprendiz eliminado correctamente.', data: {} };
  } catch (e) {
    return { success: false, message: e.message };
  }
}

// ── aprendizVerificarDocumento ────────────────────────────────
// Paso 1 del auto-registro del aprendiz.
// payload: { documento }
// (sin token — flujo público previo al login)
function aprendizVerificarDocumento(payload) {
  try {
    payload = payload || {};
    if (!payload.documento) {
      return { success: false, message: 'El campo documento es obligatorio.' };
    }
    var datos = AprendizService.verificarParaRegistro(String(payload.documento));
    return { success: true, message: 'Documento verificado.', data: datos };
  } catch (e) {
    return { success: false, message: e.message };
  }
}

// ── aprendizActivarCuenta ─────────────────────────────────────
// Paso 2 del auto-registro del aprendiz.
// payload: { id_aprendiz, password }
// (sin token — flujo público antes del primer login)
// Devuelve datos de sesión + token para login automático post-activación.
function aprendizActivarCuenta(payload) {
  try {
    payload = payload || {};
    if (!payload.id_aprendiz) {
      return { success: false, message: 'El campo id_aprendiz es obligatorio.' };
    }
    if (!payload.password) {
      return { success: false, message: 'El campo password es obligatorio.' };
    }

    var usuarioData = AprendizService.activarCuenta(
      parseInt(payload.id_aprendiz, 10),
      String(payload.password)
    );

    // Crear sesión automáticamente (igual que después del login)
    var token = AuthService.loginAprendiz(usuarioData.numero_documento, payload.password);

    return {
      success: true,
      message: '¡Cuenta activada correctamente! Bienvenido/a, ' + usuarioData.nombres + '.',
      data   : { usuario: usuarioData, token: token.token }
    };
  } catch (e) {
    return { success: false, message: e.message };
  }
}

// ── Helper de acceso ──────────────────────────────────────────
// Si el usuario autenticado es aprendiz, solo puede acceder a su propio registro.
// Docentes pasan sin restricción.
function _verificarAccesoAprendiz(usuario, idAprendiz) {
  if (usuario && usuario.rol === 'aprendiz' && usuario.id != idAprendiz) {
    throw new Error('Acceso denegado.');
  }
}
