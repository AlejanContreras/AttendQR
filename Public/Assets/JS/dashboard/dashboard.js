/**
 * AttendQR — Dashboard (Fase 1: UI simulada)
 */
document.addEventListener('DOMContentLoaded', () => {
  // Animación de números en stat cards al cargar
  document.querySelectorAll('.stat-card__value').forEach(el => {
    const raw = el.textContent.trim();
    const num = parseFloat(raw.replace('%', '').replace('/', '_').split('_')[0]);
    if (isNaN(num)) return;
    const hasPct = raw.includes('%');
    const hasFrac = raw.includes('/');
    if (hasFrac) return; // skip fractions
    let start = 0;
    const step = num / 30;
    const timer = setInterval(() => {
      start = Math.min(start + step, num);
      el.textContent = hasPct ? Math.round(start) + '%' : Math.round(start);
      if (start >= num) clearInterval(timer);
    }, 30);
  });
});
