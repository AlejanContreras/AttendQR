/**
 * AttendQR — Perfil (Fase 1: UI simulada, sin API)
 */
const perfil = (() => {

  function guardar(e) {
    e.preventDefault();
    const btn = e.target.querySelector('button[type=submit]');
    btn.disabled = true;
    setTimeout(() => {
      btn.disabled = false;
      AttendQR.toast.success('Perfil actualizado correctamente.');
    }, 800);
  }

  function cambiarPassword(e) {
    e.preventDefault();
    const actual  = document.getElementById('passActual')?.value;
    const nueva   = document.getElementById('passNueva')?.value;
    const confirm = document.getElementById('passConfirm')?.value;

    if (!actual || !nueva || !confirm) {
      AttendQR.toast.warning('Completa todos los campos de contraseña.');
      return;
    }
    if (nueva !== confirm) {
      AttendQR.toast.error('Las contraseñas nuevas no coinciden.');
      return;
    }
    if (nueva.length < 8) {
      AttendQR.toast.warning('La contraseña debe tener al menos 8 caracteres.');
      return;
    }
    const btn = e.target.querySelector('button[type=submit]');
    btn.disabled = true;
    setTimeout(() => {
      btn.disabled = false;
      e.target.reset();
      resetStrength();
      AttendQR.toast.success('Contraseña actualizada correctamente.');
    }, 800);
  }

  function evalPassword(val) {
    const bars  = [1, 2, 3, 4].map(i => document.getElementById(`pbar${i}`));
    const label = document.getElementById('passLabel');
    if (!bars[0] || !label) return;

    let score = 0;
    if (val.length >= 8)            score++;
    if (/[A-Z]/.test(val))         score++;
    if (/[0-9]/.test(val))         score++;
    if (/[^A-Za-z0-9]/.test(val))  score++;

    const colors  = ['#EF4444', '#F97316', '#F59E0B', '#22C55E'];
    const labels  = ['Muy débil', 'Débil', 'Regular', 'Fuerte'];

    bars.forEach((bar, i) => {
      bar.style.background = i < score ? colors[score - 1] : 'var(--border)';
    });
    label.textContent = score > 0 ? labels[score - 1] : '—';
    label.style.color = score > 0 ? colors[score - 1] : 'var(--text-muted)';
  }

  function resetStrength() {
    [1, 2, 3, 4].forEach(i => {
      const bar = document.getElementById(`pbar${i}`);
      if (bar) bar.style.background = 'var(--border)';
    });
    const label = document.getElementById('passLabel');
    if (label) { label.textContent = '—'; label.style.color = 'var(--text-muted)'; }
  }

  return { guardar, cambiarPassword, evalPassword };
})();
