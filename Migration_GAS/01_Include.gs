// =============================================================
// AttendQR — include()
// =============================================================
// Permite inyectar el contenido de cualquier archivo .html
// directamente dentro de una vista usando la sintaxis:
//
//   <?!= include('CSS_LAYOUT_variables') ?>
//   <?!= include('COMP_sidebar') ?>
//   <?!= include('JS_AUTH_login') ?>
//
// GAS procesa estas llamadas en el servidor antes de enviar
// el HTML al navegador, igual que PHP include/require.
// =============================================================

function include(nombre) {
  return HtmlService
    .createHtmlOutputFromFile(nombre)
    .getContent();
}
