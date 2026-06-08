<?php

declare(strict_types=1);

/**
 * AttendQR – Front Controller / API Router
 *
 * Punto de entrada único para todas las peticiones REST.
 * Las URLs amigables son redirigidas aquí por Public/.htaccess.
 *
 * Rutas soportadas:
 *   GET  /api
 *   GET  /api/auth/login
 *   POST /api/auth/logout
 *   POST /api/asistencias/registrar
 *   GET  /api/qr/token-activo
 *   GET  /api/sesiones/15
 *
 * Convención de despacho:
 *   Segmento 0 → recurso   → selecciona el controlador
 *   Segmento 1 → acción    → método del controlador a invocar
 *   Segmento 2+ → params   → parámetros posicionales (p. ej. IDs)
 *
 * Contrato de cada controlador:
 *   public function handle(string $method, string $action, array $params): void
 */

// ---------------------------------------------------------------------------
// 1. Helpers de respuesta
// ---------------------------------------------------------------------------

/**
 * Envía una respuesta JSON y termina la ejecución.
 *
 * @param mixed $data   Payload a serializar.
 * @param int   $status Código HTTP de respuesta.
 */
function jsonResponse(mixed $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=UTF-8');
    header('X-Content-Type-Options: nosniff');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Envía una respuesta de error estructurada y termina la ejecución.
 *
 * @param string $message Descripción legible del error.
 * @param int    $status  Código HTTP de respuesta.
 */
function errorResponse(string $message, int $status): never
{
    jsonResponse(['error' => $message, 'code' => $status], $status);
}

// ---------------------------------------------------------------------------
// 2. Parseo de la petición
// ---------------------------------------------------------------------------

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

// --- Cálculo dinámico de la ruta relativa -----------------------------------
//
// El objetivo es obtener la parte de la URL *después* del punto de montaje
// del script, sin depender del nombre de ninguna carpeta (AttendQR, Public…).
//
// Ejemplo con XAMPP:
//   REQUEST_URI  → /AttendQR/Public/api/auth/login?foo=bar
//   SCRIPT_NAME  → /AttendQR/Public/api.php
//   basePath     → /AttendQR/Public          (dirname de SCRIPT_NAME)
//   rawPath      → /api/auth/login           (REQUEST_URI – basePath – QS)
//   segments     → ["auth", "login"]         (sin el segmento "api")
//
// Funciona igual si el proyecto se mueve a la raíz o a cualquier otro
// subdirectorio porque todo se calcula en tiempo de ejecución.
// ---------------------------------------------------------------------------

// Extraer solo la parte del path de REQUEST_URI (sin query string).
$rawUri  = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$rawUri  = rawurldecode($rawUri);

// Calcular la base de montaje a partir de la ubicación real del script.
// dirname() sobre SCRIPT_NAME entrega la carpeta que contiene api.php.
$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');

// Retirar la base de montaje del URI completo.
// Si basePath es "" (raíz del servidor), la operación es un no-op seguro.
$relativePath = $basePath !== ''
    ? preg_replace('#^' . preg_quote($basePath, '#') . '#', '', $rawUri)
    : $rawUri;

$relativePath = $relativePath ?? $rawUri;

// Eliminar el prefijo /api para aislar los segmentos de recurso.
// /api/auth/login → /auth/login → ["auth", "login"]
$innerPath = preg_replace('#^/api#i', '', $relativePath) ?? '';

$segments = array_values(
    array_filter(
        explode('/', trim($innerPath, '/')),
        fn(string $s): bool => $s !== ''
    )
);

$resource = $segments[0] ?? '';          // Recurso → controlador
$action   = $segments[1] ?? '';          // Acción   → método del controlador
$params   = array_slice($segments, 2);   // Parámetros posicionales (IDs, etc.)

// ---------------------------------------------------------------------------
// 3. Guard de métodos HTTP
//
// Ampliar cuando se necesiten PUT, PATCH o DELETE.
// ---------------------------------------------------------------------------

$allowedMethods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];

if (!in_array($method, $allowedMethods, true)) {
    header('Allow: ' . implode(', ', $allowedMethods));
    errorResponse('Método HTTP no permitido.', 405);
}

// ---------------------------------------------------------------------------
// 4. Tabla de rutas
//
// Formato:
//   'recurso' => [
//       'file'  => 'ruta relativa al controlador desde /Src/Controllers/',
//       'class' => 'NombreController',
//   ]
//
// La ruta base de controladores es: __DIR__ . '/../Src/Controllers/'
//
// ► REGISTRAR AQUÍ CADA CONTROLADOR A MEDIDA QUE SE DESARROLLE ◄
//
// Ejemplos (descomentar cuando el controlador exista):
//   'auth'        => ['file' => 'AuthController.php',        'class' => 'AuthController'],
//   'asistencias' => ['file' => 'AsistenciasController.php', 'class' => 'AsistenciasController'],
//   'qr'          => ['file' => 'QrController.php',          'class' => 'QrController'],
//   'usuarios'    => ['file' => 'UsuariosController.php',    'class' => 'UsuariosController'],
//   'sesiones'    => ['file' => 'SesionesController.php',    'class' => 'SesionesController'],
// ---------------------------------------------------------------------------

/** @var array<string, array{file: string, class: string}> */
$routes = [
    // Los controladores se registran aquí.
];

// Directorio base de controladores (independiente del nombre del proyecto).
define('CONTROLLERS_PATH', __DIR__ . '/../Src/Controllers/');

// ---------------------------------------------------------------------------
// 5. Endpoint raíz – health check
// ---------------------------------------------------------------------------

if ($resource === '') {
    jsonResponse([
        'project' => 'AttendQR',
        'status'  => 'API operativa',
        'version' => '0.1.0',
    ]);
}

// ---------------------------------------------------------------------------
// 6. Resolución del controlador
// ---------------------------------------------------------------------------

if (!array_key_exists($resource, $routes)) {
    errorResponse("Ruta '/{$resource}' no encontrada.", 404);
}

$routeConfig     = $routes[$resource];
$controllerFile  = CONTROLLERS_PATH . $routeConfig['file'];
$controllerClass = $routeConfig['class'];

if (!file_exists($controllerFile)) {
    // El archivo no existe: error de configuración, no del cliente.
    errorResponse('Error interno del servidor.', 500);
}

require_once $controllerFile;

if (!class_exists($controllerClass)) {
    errorResponse('Error interno del servidor.', 500);
}

$controller = new $controllerClass();

// ---------------------------------------------------------------------------
// 7. Despacho
//
// El controlador recibe método, acción y parámetros posicionales.
// Decide internamente cómo manejar cada combinación.
// ---------------------------------------------------------------------------

if (!method_exists($controller, 'handle')) {
    errorResponse('Error interno del servidor.', 500);
}

$controller->handle($method, $action, $params);
