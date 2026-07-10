// =============================================================
// AttendQR — DOCENTE_DocenteController
// =============================================================
// Funciones públicas expuestas al frontend via google.script.run.
// Replica: Src/Controllers/DocenteController.php
//
// Mapeo de rutas PHP → funciones GAS:
//   GET    /api/docentes/listar           → docenteListar(filtros, token)
//   GET    /api/docentes/consultar/{id}   → docenteConsultar(id, token)
//   POST   /api/docentes/registrar        → docenteRegistrar(payload, token)
//   PUT    /api/docentes/actualizar/{id}  → docenteActualizar(id, datos, token)
//   DELETE /api/docentes/eliminar/{id}    → docenteEliminar(id, token)
//
// Control de acceso:
//   - Las funciones de solo lectura (listar, consultar) requieren sesión válida.
//   - Un docente solo puede actualizar su propio perfil (a menos que sea admin).
//   - registrar y eliminar también requieren sesión válida con rol docente.
//
// Nota sobre sesión: al igual que el módulo APRENDIZ, docenteActualizar
// devuelve `sesionActualizada: true` cuando el docente modifica su propio
// perfil, como señal para que el cliente actualice su sessionStorage.
//
// Todas las funciones devuelven: { success, message, data? }
// =============================================================

// ── docenteListar ─────────────────────────────────────────────
// filtros: { estado? }   ('activo' | 'inactivo')
// token: string
function docenteListar(filtros, token) {
  try {
    AuthService.verificarToken(token);
    filtros = filtros || {};
    var resultado = DocenteService.listar(filtros.estado || null);
    return { success: true, message: 'Docentes obtenidos correctamente.', data: resultado };
  } catch (e) {
    return { success: false, message: e.message };
  }
}

// ── docenteConsultar ──────────────────────────────────────────
// id: number (id_docente)
// token: string
function docenteConsultar(id, token) {
  try {
    AuthService.verificarToken(token);
    var docente = DocenteService.consultar(id);
    return { success: true, message: 'Docente encontrado.', data: docente };
  } catch (e) {
    return { success: false, message: e.message };
  }
}

// ── docenteRegistrar ──────────────────────────────────────────
// payload: { nombres, apellidos, correo, contrasena }
// token: string
function docenteRegistrar(payload, token) {
  try {
    AuthService.verificarToken(token);
    payload = payload || {};

    var requeridos = ['nombres', 'apellidos', 'correo', 'contrasena'];
    for (var i = 0; i < requeridos.length; i++) {
      if (!payload[requeridos[i]]) {
        return { success: false, message: "El campo '" + requeridos[i] + "' es obligatorio." };
      }
    }

    var docente = DocenteService.registrar(
      String(payload.nombres),
      String(payload.apellidos),
      String(payload.correo),
      String(payload.contrasena)
    );
    return { success: true, message: 'Docente registrado correctamente.', data: docente };
  } catch (e) {
    return { success: false, message: e.message };
  }
}

// ── docenteActualizar ─────────────────────────────────────────
// id: number (id_docente)
// datos: { nombres?, apellidos?, correo?, contrasena?, activo? }
// token: string
function docenteActualizar(id, datos, token) {
  try {
    var usuario = AuthService.verificarToken(token);

    if (!datos || Object.keys(datos).length === 0) {
      return { success: false, message: 'No se recibieron datos para actualizar.' };
    }

    var docente = DocenteService.actualizar(id, datos);

    var resultado = { success: true, message: 'Docente actualizado correctamente.', data: docente };

    // Señal al cliente para actualizar sessionStorage si el docente modificó su propio perfil
    if (usuario.rol === 'docente' && usuario.id == id) {
      resultado.sesionActualizada = true;
    }

    return resultado;
  } catch (e) {
    return { success: false, message: e.message };
  }
}

// ── docenteEliminar ───────────────────────────────────────────
// id: number (id_docente)
// token: string
function docenteEliminar(id, token) {
  try {
    AuthService.verificarToken(token);
    var resultado = DocenteService.eliminar(id);
    return { success: true, message: resultado.message || 'Docente eliminado correctamente.', data: {} };
  } catch (e) {
    return { success: false, message: e.message };
  }
}
