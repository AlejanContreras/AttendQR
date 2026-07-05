/**
 * AttendQR — Login
 *
 * Gestiona el formulario de inicio de sesión para docentes y aprendices.
 * Consume POST /api/auth/login (endpoint unificado del backend).
 *
 * Flujo:
 *   1. Al cargar: verifica si ya hay sesión activa → redirige al dashboard.
 *   2. Al enviar: valida campos, llama a Api.auth.login(), guarda usuario,
 *      redirige al dashboard según rol.
 *   3. En error: muestra mensaje inline dentro del formulario.
 */
document.addEventListener('DOMContentLoaded', () => {

  // Si ya hay sesión activa, redirigir sin mostrar el login
  Api.auth.verificar()
    .then(usuario => {
      auth.setUsuario(usuario);
      auth.irADashboard(usuario.rol);
    })
    .catch(() => {
      // Sin sesión activa — quedarse en el login
      auth.clearUsuario();
    });

  // Filtro numérico estricto para el campo de documento del aprendiz
  document.getElementById('aprendizDoc')?.addEventListener('input', function () {
    this.value = this.value.replace(/\D/g, '');
  });

  // Focus automático al primer campo visible
  document.querySelector('#formDocente input:not([type=hidden])')?.focus();

  // Enter en cualquier campo dispara el submit del formulario correspondiente
  document.querySelectorAll('.login-form input').forEach(input => {
    input.addEventListener('keydown', e => {
      if (e.key === 'Enter') {
        e.preventDefault();
        input.closest('form')?.requestSubmit();
      }
    });
  });
});

// ─── Cambio de pestaña docente / aprendiz ──────────────────────────────

function switchRole(tab, role) {
  document.querySelectorAll('.login-tab').forEach(t => t.classList.remove('is-active'));
  tab.classList.add('is-active');

  document.getElementById('formDocente').style.display  = role === 'docente'  ? 'flex' : 'none';
  document.getElementById('formAprendiz').style.display = role === 'aprendiz' ? 'flex' : 'none';

  // Limpiar campos y alertas del formulario anterior
  document.querySelectorAll('.login-alert').forEach(el => el.remove());
  document.querySelectorAll('.login-form input').forEach(el => { el.value = ''; });

  // Focus al primer campo del formulario activado
  setTimeout(() => {
    const formId = role === 'docente' ? 'formDocente' : 'formAprendiz';
    document.getElementById(formId)?.querySelector('input:not([type=hidden])')?.focus();
  }, 50);
}

// ─── Submit ────────────────────────────────────────────────────────────────

async function handleLogin(e, role) {
  e.preventDefault();

  ocultarError(e.target);

  // Leer credenciales según el rol
  const body = role === 'docente'
    ? {
        correo:   document.getElementById('docenteCorreo')?.value.trim(),
        password: document.getElementById('docentePassword')?.value,
      }
    : {
        documento: document.getElementById('aprendizDoc')?.value.trim(),
        password:  document.getElementById('aprendizPassword')?.value,
      };

  // Validación de campos vacíos antes de llamar al backend
  if (role === 'docente' && !body.correo) {
    mostrarError(e.target, 'El correo electrónico es obligatorio.');
    document.getElementById('docenteCorreo')?.focus();
    return;
  }
  if (role === 'aprendiz' && !body.documento) {
    mostrarError(e.target, 'El número de documento es obligatorio.');
    document.getElementById('aprendizDoc')?.focus();
    return;
  }
  if (!body.password) {
    mostrarError(e.target, 'La contraseña es obligatoria.');
    const passId = role === 'docente' ? 'docentePassword' : 'aprendizPassword';
    document.getElementById(passId)?.focus();
    return;
  }

  // Estado de carga en el botón
  const btn      = e.target.querySelector('button[type=submit]');
  const original = btn.innerHTML;
  btn.disabled = true;
  btn.innerHTML = '<div class="spinner" style="width:16px;height:16px;border-color:rgba(255,255,255,.3);border-top-color:#fff;display:inline-block"></div> Verificando...';

  try {
    const usuario = await Api.auth.login(body);

    // Guardar usuario en sessionStorage para uso inmediato en el shell
    auth.setUsuario(usuario);

    // Redirigir al dashboard correspondiente al rol
    auth.irADashboard(usuario.rol ?? role);

  } catch (err) {
    btn.disabled = false;
    btn.innerHTML = original;

    // Distinguir error de credenciales vs error de red/servidor
    const msg = err.status === 401 || err.status === 400
      ? (err.message ?? 'Credenciales incorrectas. Verifica tus datos.')
      : err.status >= 500
        ? 'Error del servidor. Intenta de nuevo en unos momentos.'
        : (err.message ?? 'No se pudo conectar con el servidor.');

    mostrarError(e.target, msg);
  }
}

// ─── Toggle de visibilidad de contraseña ──────────────────────────────────

function togglePassword(inputId, btn) {
  const input = document.getElementById(inputId);
  if (!input) return;
  input.type = input.type === 'password' ? 'text' : 'password';
}

// ─── Helpers de alerta inline ──────────────────────────────────────────────

function mostrarError(form, mensaje) {
  let alertEl = form.querySelector('.login-alert');
  if (!alertEl) {
    alertEl = document.createElement('div');
    alertEl.className = 'alert alert-danger login-alert';
    alertEl.style.cssText = 'margin-bottom:var(--sp-4);border-radius:var(--r-md)';
    form.prepend(alertEl);
  }
  alertEl.textContent = mensaje;
  alertEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function ocultarError(form) {
  form.querySelector('.login-alert')?.remove();
}
