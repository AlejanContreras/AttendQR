/**
 * AttendQR — Historial (Fase 1: comportamiento UI, sin API)
 */
const historial = (() => {

  function toggle(sessionId, btn) {
    const detail = document.getElementById(`detail-${sessionId}`);
    if (!detail) return;
    detail.classList.toggle('is-open');
    btn.classList.toggle('is-open');
  }

  function filtrar() {
    const estado = document.getElementById('filterEstado')?.value.toLowerCase() ?? '';
    document.querySelectorAll('tr.session-row').forEach(row => {
      if (!estado) { row.style.display = ''; return; }
      const badge = row.querySelector('.badge');
      const rowEstado = badge?.textContent.trim().toLowerCase() ?? '';
      const match =
        (estado === 'abierta'   && rowEstado === 'abierta')  ||
        (estado === 'cerrada'   && rowEstado === 'cerrada')  ||
        (estado === 'cancelada' && rowEstado === 'cancelada');
      row.style.display = match ? '' : 'none';
      const detail = document.getElementById(`detail-${row.dataset.session}`);
      if (detail && !match) detail.classList.remove('is-open');
    });
  }

  function limpiar() {
    ['filterFicha', 'filterEstado', 'filterDesde', 'filterHasta'].forEach(id => {
      const el = document.getElementById(id);
      if (el) el.value = '';
    });
    document.querySelectorAll('tr.session-row').forEach(r => r.style.display = '');
    AttendQR.toast.info('Filtros limpiados.');
  }

  return { toggle, filtrar, limpiar };
})();
