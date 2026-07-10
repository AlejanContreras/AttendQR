// =============================================================
// AttendQR — DOCENTE_DocenteService
// =============================================================
// Lógica de negocio del módulo Docente.
// Replica: Src/Services/DocenteService.php
//
// Contraseñas: AuthService.hashPassword() (SHA-256) para consistencia
// con el módulo AUTH. Nota: la versión PHP usa bcrypt.
//
// Dependencia de sesiones: DocenteRepository.contarSesionesActivasPorDocente
// (temporal — delegará a SesionRepository cuando SESION sea migrado).
// =============================================================

var DocenteService = (function () {

  // ─── Consulta ────────────────────────────────────────────

  function consultar(idDocente) {
    var docente = DocenteRepository.obtenerPorId(idDocente);
    if (!docente) throw new Error('Docente no encontrado.');
    return docente;
  }

  // ─── Listar ───────────────────────────────────────────────

  function listar(estado) {
    var activo = estado === 'activo' ? 1 : estado === 'inactivo' ? 0 : null;
    var docentes = DocenteRepository.listar(activo);
    return {
      docentes: docentes,
      total   : docentes.length
    };
  }

  // ─── Registrar ────────────────────────────────────────────

  function registrar(nombres, apellidos, correo, contrasena) {
    nombres   = String(nombres).trim();
    apellidos = String(apellidos).trim();
    correo    = String(correo).toLowerCase().trim();

    if (!_esCorreoValido(correo)) {
      throw new Error("El correo '" + correo + "' no tiene un formato válido.");
    }

    if (DocenteRepository.existeCorreo(correo)) {
      throw new Error('El correo ya está en uso por otro docente.');
    }

    var passwordHash = AuthService.hashPassword(contrasena);
    var id           = DocenteRepository.crear(nombres, apellidos, correo, passwordHash);

    return DocenteRepository.obtenerPorId(id) || {
      id_docente: id,
      nombres   : nombres,
      apellidos : apellidos,
      correo    : correo,
      activo    : 1
    };
  }

  // ─── Actualizar ───────────────────────────────────────────

  function actualizar(idDocente, datos) {
    var docente = DocenteRepository.obtenerPorId(idDocente);
    if (!docente) throw new Error('Docente no encontrado.');

    // Normalizar y validar correo si se envía
    if (datos.hasOwnProperty('correo')) {
      datos.correo = String(datos.correo).toLowerCase().trim();

      if (!_esCorreoValido(datos.correo)) {
        throw new Error("El correo '" + datos.correo + "' no tiene un formato válido.");
      }

      if (DocenteRepository.existeCorreo(datos.correo, idDocente)) {
        throw new Error('El correo ya está en uso por otro docente.');
      }
    }

    // Cambio de contraseña (campo 'contrasena' → hash → 'password_hash')
    if (datos.hasOwnProperty('contrasena') && datos.contrasena) {
      datos.password_hash = AuthService.hashPassword(String(datos.contrasena));
      delete datos.contrasena;
    }

    DocenteRepository.actualizar(idDocente, datos);

    return DocenteRepository.obtenerPorId(idDocente) || docente;
  }

  // ─── Eliminar ─────────────────────────────────────────────

  function eliminar(idDocente) {
    var docente = DocenteRepository.obtenerPorId(idDocente);
    if (!docente) throw new Error('Docente no encontrado.');

    if (DocenteRepository.contarSesionesActivasPorDocente(idDocente) > 0) {
      throw new Error('El docente tiene sesiones activas. Ciérrelas antes de eliminarlo.');
    }

    DocenteRepository.eliminar(idDocente);
    return { success: true, message: 'Docente eliminado correctamente.' };
  }

  // ─── Helpers privados ─────────────────────────────────────

  function _esCorreoValido(correo) {
    // Equivalente a filter_var($correo, FILTER_VALIDATE_EMAIL)
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(correo);
  }

  return {
    consultar : consultar,
    listar    : listar,
    registrar : registrar,
    actualizar: actualizar,
    eliminar  : eliminar
  };

})();
