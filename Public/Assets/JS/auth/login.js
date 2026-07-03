/**
 * AttendQR — Login (Fase 1: comportamiento UI sin API)
 * Las funciones switchRole, handleLogin y togglePassword
 * están definidas inline en login.php para mantener el archivo
 * standalone sin dependencia de módulos externos.
 * Este archivo está reservado para lógica adicional de validación.
 */

document.addEventListener('DOMContentLoaded', () => {
  // Auto-focus primer campo visible
  const firstInput = document.querySelector('#formDocente input:first-of-type');
  if (firstInput) firstInput.focus();

  // Enter en campos dispara submit del form padre
  document.querySelectorAll('.login-form input').forEach(input => {
    input.addEventListener('keydown', e => {
      if (e.key === 'Enter') {
        e.preventDefault();
        input.closest('form')?.requestSubmit();
      }
    });
  });
});
