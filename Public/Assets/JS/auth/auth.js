/**
 * AttendQR — Auth (Fase 1: simulación sin backend)
 * Gestiona estado de sesión en sessionStorage para navegación entre vistas.
 */
const auth = (() => {

  function getRol() {
    const params = new URLSearchParams(window.location.search);
    return params.get('rol') || 'docente';
  }

  function logout() {
    sessionStorage.clear();
    window.location.href = 'Views/login.php';
  }

  function checkSession() {
    // En Fase 1 no hay sesión real — siempre pasa
    return true;
  }

  return { getRol, logout, checkSession };
})();
