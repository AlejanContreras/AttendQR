/**
 * AttendQR — Sesiones (Fase 1: UI simulada, sin API)
 */
const sesiones = (() => {

  function crear(e) {
    e.preventDefault();
    const btn = document.getElementById('btnCrear');
    if (!btn) return;

    const ficha = document.getElementById('idFicha')?.value;
    if (!ficha) {
      AttendQR.toast.warning('Selecciona una ficha para continuar.');
      document.getElementById('idFicha')?.focus();
      return;
    }

    btn.disabled = true;
    btn.innerHTML = '<div class="spinner" style="border-color:rgba(255,255,255,.3);border-top-color:#fff;width:16px;height:16px"></div> Creando...';

    // Simulación: redirige a vista QR tras breve delay
    setTimeout(() => {
      const rol = new URLSearchParams(window.location.search).get('rol') || 'docente';
      AttendQR.toast.success('Sesión creada. Redirigiendo al QR...');
      setTimeout(() => {
        window.location.href = `index.php?view=qr&rol=${rol}`;
      }, 800);
    }, 1000);
  }

  return { crear };
})();
