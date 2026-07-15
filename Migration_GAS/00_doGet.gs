// =============================================================
// AttendQR — doGet (router principal)
// =============================================================
// Único punto de entrada del WebApp.
// Toda la navegación ocurre mediante el parámetro ?page=
// Ejemplo: https://script.google.com/.../exec?page=historial
// =============================================================

function doGet(e) {

  // Tabla de páginas válidas → { nombreArchivo: títuloPágina }
  // Cualquier valor fuera de esta lista redirige a 404.
  var PAGINAS = {
    'login'                : 'Iniciar sesión',
    'registro'             : 'Crear cuenta',
    'dashboard-docente'    : 'Dashboard',
    'dashboard-aprendiz'   : 'Dashboard',
    'historial'            : 'Historial de Asistencia',
    'perfil'               : 'Mi Perfil',
    'crear-sesion'         : 'Mis Clases',
    'aprendices'           : 'Gestión de Aprendices',
    'registrar-asistencia' : 'Registrar Asistencia',
    'qr'                   : 'QR Dinámico',
    '404'                  : 'Página no encontrada',
    'test-camara'          : 'Test Cámara'
  };

  // Página solicitada (default: login)
  var pagina = (e && e.parameter && e.parameter.page) ? e.parameter.page : 'login';

  // Si la página no existe en la whitelist → 404
  var archivo = PAGINAS.hasOwnProperty(pagina) ? pagina : '404';
  var titulo  = PAGINAS[archivo];

  var template = HtmlService.createTemplateFromFile(archivo);
  template.urlParams = JSON.stringify(e && e.parameter ? e.parameter : {});

  return template
    .evaluate()
    .setTitle(titulo + ' — AttendQR')
    .addMetaTag('viewport', 'width=device-width, initial-scale=1.0')
    .setXFrameOptionsMode(HtmlService.XFrameOptionsMode.ALLOWALL);
}
