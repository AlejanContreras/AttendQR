<?php

declare(strict_types=1);

/**
 * AttendQR – Front Controller / API Router
 *
 * Punto de entrada único para todas las peticiones REST.
 * Las URLs amigables son redirigidas aquí por Public/.htaccess.
 *
 * Módulos registrados:
 *   /api/auth          → AuthController
 *   /api/asistencias   → AsistenciaController
 *   /api/sesiones      → SesionController
 *   /api/qr            → QrController
 *   /api/fichas        → FichaController        (pendiente)
 *   /api/aprendices    → AprendizController     (pendiente)
 *   /api/docentes      → DocenteController      (pendiente)
 *   /api/jornadas      → JornadaController      (pendiente)
 *   /api/estadisticas  → EstadisticaController  (pendiente)
 *   /api/tokens        → TokenController        (pendiente)
 *   /api/trimestres    → TrimestreController    (pendiente)
 *   /api/health        → HealthController       (pendiente)
 *
 * Convención de despacho:
 *   Segmento 0  → recurso  → selecciona el controlador
 *   Segmento 1  → acción   → método del controlador a invocar
 *   Segmento 2+ → params   → parámetros posicionales (p. ej. IDs)
 *
 * Contrato de cada controlador:
 *   public function handle(string $method, string $action, array $params): void
 *
 * Para agregar un nuevo módulo: registrar una entrada en $routes (sección 5).
 * No es necesario modificar ninguna otra parte de este archivo.
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

// Retirar la base de montaje del URI completo usando comparación sin distinción
// de mayúsculas/minúsculas (strncasecmp), porque en Windows/XAMPP el servidor
// puede entregar REQUEST_URI en minúsculas (/attendqr/...) mientras que
// SCRIPT_NAME conserva las mayúsculas originales (/AttendQR/...).
// substr() recorta exactamente los caracteres de basePath sin depender de regex.
$longitudBase = strlen($basePath);
$relativePath = ($basePath !== '' && strncasecmp($rawUri, $basePath, $longitudBase) === 0)
    ? substr($rawUri, $longitudBase)
    : $rawUri;

// Garantizar que relativePath sea siempre una cadena (substr puede devolver false).
$relativePath = $relativePath !== false ? $relativePath : $rawUri;

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
// 4. Directorio base de controladores
//
// Se calcula a partir de la ubicación real de este archivo (api.php vive en
// Public/) para ser completamente independiente del nombre del proyecto.
//
//   __DIR__  →  .../AttendQR/Public
//   CONTROLLERS_PATH  →  .../AttendQR/Src/Controllers/
// ---------------------------------------------------------------------------

define('CONTROLLERS_PATH', dirname(__DIR__) . '/Src/Controllers/');

// ---------------------------------------------------------------------------
// 5. Tabla de módulos registrados
//
// Cada entrada mapea el segmento de recurso de la URL al archivo y clase
// del controlador correspondiente.
//
// Formato de cada entrada:
//   'segmento-url' => ['file' => 'NombreController.php', 'class' => 'NombreController']
//
// Para agregar un nuevo módulo basta con añadir una línea aquí.
// No es necesario modificar ninguna otra parte del router.
//
// Estado de implementación:
//   ✓ Controlador creado y operativo
//   ○ Pendiente de implementar (el archivo aún no existe)
// ---------------------------------------------------------------------------

/** @var array<string, array{file: string, class: string}> */
$routes = [
    // ✓ Autenticación de usuarios (login, logout, verificar token)
    'auth'         => ['file' => 'AuthController.php',        'class' => 'AuthController'],

    // ✓ Registro y consulta de asistencias
    'asistencias'  => ['file' => 'AsistenciaController.php',  'class' => 'AsistenciaController'],

    // ✓ Gestión de sesiones de clase (crear, listar, cerrar)
    'sesiones'     => ['file' => 'SesionController.php',      'class' => 'SesionController'],

    // ✓ Generación y validación de códigos QR
    'qr'           => ['file' => 'QrController.php',          'class' => 'QrController'],

    // ○ Gestión de fichas de formación
    'fichas'       => ['file' => 'FichaController.php',       'class' => 'FichaController'],

    // ○ Gestión de aprendices
    'aprendices'   => ['file' => 'AprendizController.php',    'class' => 'AprendizController'],

    // ○ Gestión de docentes / instructores
    'docentes'     => ['file' => 'DocenteController.php',     'class' => 'DocenteController'],

    // ○ Gestión de jornadas (mañana, tarde, noche, etc.)
    'jornadas'     => ['file' => 'JornadaController.php',     'class' => 'JornadaController'],

    // ○ Estadísticas e informes del sistema
    'estadisticas' => ['file' => 'EstadisticaController.php', 'class' => 'EstadisticaController'],

    // ○ Gestión de tokens de acceso / refresco
    'tokens'       => ['file' => 'TokenController.php',       'class' => 'TokenController'],

    // ○ Gestión de trimestres académicos
    'trimestres'   => ['file' => 'TrimestreController.php',   'class' => 'TrimestreController'],

    // ○ Health check del sistema (estado de la API y dependencias)
    'health'       => ['file' => 'HealthController.php',      'class' => 'HealthController'],
];

// ---------------------------------------------------------------------------
// 6. Endpoint raíz – health check
// ---------------------------------------------------------------------------

if ($resource === '') {
    jsonResponse([
        'project' => 'AttendQR',
        'status'  => 'API operativa',
        'version' => '0.1.0',
    ]);
}

// ---------------------------------------------------------------------------
// 7. Resolución del controlador
// ---------------------------------------------------------------------------

if (!array_key_exists($resource, $routes)) {
    errorResponse("Ruta '/{$resource}' no encontrada.", 404);
}

$routeConfig     = $routes[$resource];
$controllerFile  = CONTROLLERS_PATH . $routeConfig['file'];
$controllerClass = $routeConfig['class'];

if (!file_exists($controllerFile)) {
    // El archivo del controlador no existe en disco.
    // Verificar que el archivo esté creado en Src/Controllers/
    errorResponse(
        "Controlador '{$routeConfig['file']}' no encontrado en Src/Controllers/.",
        500
    );
}

require_once $controllerFile;

if (!class_exists($controllerClass)) {
    // El archivo existe pero la clase no coincide con el nombre registrado.
    errorResponse(
        "La clase '{$controllerClass}' no fue encontrada en el archivo cargado.",
        500
    );
}

$controller = new $controllerClass();

// ---------------------------------------------------------------------------
// 8. Despacho
//
// El controlador recibe método, acción y parámetros posicionales.
// Decide internamente cómo manejar cada combinación.
// ---------------------------------------------------------------------------

if (!method_exists($controller, 'handle')) {
    // El controlador existe pero no implementa el contrato requerido.
    errorResponse(
        "La clase '{$controllerClass}' no implementa el método handle().",
        500
    );
}

$controller->handle($method, $action, $params);
