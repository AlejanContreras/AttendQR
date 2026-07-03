/**
 * AttendQR — QR Dinámico (Fase 1: simulación de rotación sin backend)
 */
const qr = (() => {

  const ROTATION_SECONDS = 30;
  let remaining = ROTATION_SECONDS;
  let countdownTimer = null;
  let elapsedSeconds = 24 * 60 + 15; // simulated session elapsed

  const TOKENS = ['ATQ-7F2K9X1M', 'ATQ-B3R8WZ4Q', 'ATQ-K9NP1YC6', 'ATQ-M2HX5TJ7', 'ATQ-V6DL3QA8'];
  let tokenIdx = 0;

  function init() {
    startCountdown();
    startElapsedTimer();
  }

  function startCountdown() {
    const timerEl  = document.getElementById('qrCountdown');
    const barEl    = document.getElementById('qrProgress');
    if (!timerEl) return;

    remaining = ROTATION_SECONDS;

    countdownTimer = setInterval(() => {
      remaining--;
      if (timerEl) timerEl.textContent = remaining;
      if (barEl)   barEl.style.width = `${(remaining / ROTATION_SECONDS) * 100}%`;

      if (remaining <= 10) {
        timerEl.classList.add('is-warning');
      } else {
        timerEl.classList.remove('is-warning');
      }

      if (remaining <= 0) {
        rotate();
        remaining = ROTATION_SECONDS;
      }
    }, 1000);
  }

  function rotate() {
    const overlay = document.getElementById('qrRefreshOverlay');
    const tokenEl = document.getElementById('qrToken');
    if (overlay) overlay.style.display = 'flex';

    setTimeout(() => {
      tokenIdx = (tokenIdx + 1) % TOKENS.length;
      if (tokenEl) tokenEl.textContent = TOKENS[tokenIdx];
      if (overlay) overlay.style.display = 'none';
    }, 600);
  }

  function startElapsedTimer() {
    const el = document.getElementById('sessionElapsed');
    if (!el) return;
    setInterval(() => {
      elapsedSeconds++;
      const h = Math.floor(elapsedSeconds / 3600).toString().padStart(2, '0');
      const m = Math.floor((elapsedSeconds % 3600) / 60).toString().padStart(2, '0');
      const s = (elapsedSeconds % 60).toString().padStart(2, '0');
      el.textContent = `${h}:${m}:${s}`;
    }, 1000);
  }

  document.addEventListener('DOMContentLoaded', init);

  return { rotate };
})();
