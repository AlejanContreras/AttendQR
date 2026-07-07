<?php

declare(strict_types=1);

/**
 * AttendQR – Front Controller / API Router
 *
 * Punto de entrada único para todas las peticiones REST.
 * Las URLs amigables son redirigidas aquí por Public/.htaccess.
 *
 * Flujo de cada petición:
 *   Cliente → api.php → [AuthMiddleware] → [RoleMiddleware] → Controller
 *
 * Módulos registrados:
 *   /api/auth          → AuthController        (pública)
 *   /api/health        → HealthController      (pública)
 *   /api/asistencias   → AsistenciaController  (docente | aprendiz)
 *   /api/sesiones      → SesionController      (docente)
 *   /api/qr            → QrController          (docente | aprendiz)
 *   /api/fichas        → FichaController       (docente)
 *   /api/aprendices    → AprendizController    (docente)
 *   /api/docentes      → DocenteController     (docente)
 *   /api/jornadas      → JornadaController     (docente)
 *   /api/estadisticas  → EstadisticaController (docente)
 *   /api/tokens        → [desactivado — vestigial, no forma parte del MVP]
 *   /api/trimestres    → TrimestreController   (docente)
 *
 * Para agregar un nuevo módulo: registrar una entrada en $routes (sección 5)
 * y definir su política de acceso en $politicasAcceso (sección 6).
 */

// ---------------------------------------------------------------------------
// 0. Timezone de la aplicación
//
// PHP en este servidor corre con Europe/Berlin (UTC+2).
// Colombia opera en America/Bogota (UTC-5).
// Diferencia = 7 h → sin esta línea, date() y strtotime() calculan
// mal los minutos de retardo y clasifican TODOS los registros como
// "expirado". Debe ser la primera instrucción antes de cualquier
// llamada a funciones de fecha.
// ---------------------------------------------------------------------------
date_default_timezone_set('America/Bogota');

// ---------------------------------------------------------------------------
// 1. Helpers de respuesta
// ---------------------------------------------------------------------------

function jsonResponse(mixed $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=UTF-8');
    header('X-Content-Type-Options: nosniff');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function errorResponse(string $message, int $status): never
{
    jsonResponse(['error' => $message, 'code' => $status], $status);
}

// ---------------------------------------------------------------------------
// 2. Rutas base del proyecto
//
// api.php vive en Public/. La raíz del proyecto es un nivel arriba.
// ---------------------------------------------------------------------------

define('ROOT_PATH',         dirname(__DIR__));
define('SRC_PATH',          ROOT_PATH . '/Src');
define('CONTROLLERS_PATH',  SRC_PATH  . '/Controllers/');
define('SERVICES_PATH',     SRC_PATH  . '/Services/');
define('REPOSITORIES_PATH', SRC_PATH  . '/Repositories/');
define('MODELS_PATH',       SRC_PATH  . '/Models/');
define('MIDDLEWARE_PATH',   SRC_PATH  . '/Middleware/');
define('CONFIG_PATH',       SRC_PATH  . '/Config/');
define('UTILS_PATH',        SRC_PATH  . '/Utils/');

// ---------------------------------------------------------------------------
// 3. Bootstrap – carga de dependencias en orden
//
// El orden es crítico:
//   1. Database       → conexión PDO centralizada
//   2. BaseRepository → clase abstracta base
//   3. Repositories   → acceso a datos (dependen de BaseRepository)
//   4. Services       → lógica de negocio (dependen de Repositories)
//
// Los Models son DTOs simples sin dependencias; se cargan también
// para que estén disponibles en Services y Repositories.
//
// Los Controllers se cargan más adelante (sección 10), una vez
// conocido cuál es el controlador destino de la petición.
// ---------------------------------------------------------------------------

// 3a. Conexión a base de datos
require_once CONFIG_PATH . 'Database.php';

// 3b. Clase base de Repositories
require_once REPOSITORIES_PATH . 'BaseRepository.php';

// 3c. Repositories (orden alfabético — sin dependencias entre sí)
require_once REPOSITORIES_PATH . 'AprendizRepository.php';
require_once REPOSITORIES_PATH . 'AsistenciaRepository.php';
require_once REPOSITORIES_PATH . 'AuthRepository.php';
require_once REPOSITORIES_PATH . 'DocenteRepository.php';
require_once REPOSITORIES_PATH . 'FichaRepository.php';
require_once REPOSITORIES_PATH . 'JornadaRepository.php';
require_once REPOSITORIES_PATH . 'QrRepository.php';
require_once REPOSITORIES_PATH . 'SesionRepository.php';
require_once REPOSITORIES_PATH . 'TokenRepository.php';
require_once REPOSITORIES_PATH . 'TrimestreRepository.php';

// 3d. Models (DTOs simples, sin dependencias)
require_once MODELS_PATH . 'AprendizModel.php';
require_once MODELS_PATH . 'AsistenciaModel.php';
require_once MODELS_PATH . 'DocenteModel.php';
require_once MODELS_PATH . 'FichaModel.php';
require_once MODELS_PATH . 'JornadaModel.php';
require_once MODELS_PATH . 'SesionAsistenciaModel.php';
require_once MODELS_PATH . 'TokenQRModel.php';
require_once MODELS_PATH . 'TrimestreModel.php';

// 3e. Utilidades (sin dependencias de BD)
require_once UTILS_PATH . 'XlsxWriter.php';

// 3f. Services (dependen de Repositories — deben cargarse después)
require_once SERVICES_PATH . 'AuthService.php';
require_once SERVICES_PATH . 'AprendizService.php';
require_once SERVICES_PATH . 'AsistenciaService.php';
require_once SERVICES_PATH . 'DocenteService.php';
require_once SERVICES_PATH . 'EstadisticaService.php';
require_once SERVICES_PATH . 'FichaService.php';
require_once SERVICES_PATH . 'HealthService.php';
require_once SERVICES_PATH . 'JornadaService.php';
require_once SERVICES_PATH . 'QrService.php';
require_once SERVICES_PATH . 'SesionService.php';
require_once SERVICES_PATH . 'TokenService.php';
require_once SERVICES_PATH . 'TrimestreService.php';

// ---------------------------------------------------------------------------
// 4. Parseo de la petición
// ---------------------------------------------------------------------------

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

$rawUri   = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$rawUri   = rawurldecode($rawUri);
$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');

$longitudBase = strlen($basePath);
$relativePath = ($basePath !== '' && strncasecmp($rawUri, $basePath, $longitudBase) === 0)
    ? substr($rawUri, $longitudBase)
    : $rawUri;

$relativePath = $relativePath !== false ? $relativePath : $rawUri;

$innerPath = preg_replace('#^/api#i', '', $relativePath) ?? '';

$segments = array_values(
    array_filter(
        explode('/', trim($innerPath, '/')),
        fn(string $s): bool => $s !== ''
    )
);

$resource = $segments[0] ?? '';
$action   = $segments[1] ?? '';
$params   = array_slice($segments, 2);

// ---------------------------------------------------------------------------
// 5. Guard de métodos HTTP
// ---------------------------------------------------------------------------

$allowedMethods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];

if (!in_array($method, $allowedMethods, true)) {
    header('Allow: ' . implode(', ', $allowedMethods));
    errorResponse('Método HTTP no permitido.', 405);
}

// ---------------------------------------------------------------------------
// 6. Tabla de módulos registrados
// ---------------------------------------------------------------------------

/** @var array<string, array{file: string, class: string}> */
$routes = [
    'auth'         => ['file' => 'AuthController.php',        'class' => 'AuthController'],
    'asistencias'  => ['file' => 'AsistenciaController.php',  'class' => 'AsistenciaController'],
    'sesiones'     => ['file' => 'SesionController.php',      'class' => 'SesionController'],
    'qr'           => ['file' => 'QrController.php',          'class' => 'QrController'],
    'fichas'       => ['file' => 'FichaController.php',       'class' => 'FichaController'],
    'aprendices'   => ['file' => 'AprendizController.php',    'class' => 'AprendizController'],
    'docentes'     => ['file' => 'DocenteController.php',     'class' => 'DocenteController'],
    'jornadas'     => ['file' => 'JornadaController.php',     'class' => 'JornadaController'],
    'estadisticas' => ['file' => 'EstadisticaController.php', 'class' => 'EstadisticaController'],
    'trimestres'   => ['file' => 'TrimestreController.php',   'class' => 'TrimestreController'],
    'health'       => ['file' => 'HealthController.php',      'class' => 'HealthController'],
];

// ---------------------------------------------------------------------------
// 7. Políticas de acceso por recurso
//
// Valores posibles:
//   'publica'           → sin autenticación requerida
//   'autenticada'       → requiere sesión activa (cualquier rol)
//   'solo_docente'      → requiere sesión activa + rol docente
//   'solo_aprendiz'     → requiere sesión activa + rol aprendiz
//   'docente_aprendiz'  → requiere sesión activa + rol docente o aprendiz
// ---------------------------------------------------------------------------

/** @var array<string, string> */
$politicasAcceso = [
    'auth'         => 'publica',
    'health'       => 'publica',

    'asistencias'  => 'docente_aprendiz',
    'qr'           => 'docente_aprendiz',

    'sesiones'     => 'solo_docente',
    'fichas'       => 'solo_docente',
    'aprendices'   => 'docente_aprendiz',
    'docentes'     => 'solo_docente',
    'jornadas'     => 'solo_docente',
    'estadisticas' => 'solo_docente',
    'trimestres'   => 'solo_docente',
];

// ---------------------------------------------------------------------------
// 8. Endpoint raíz – health check público
// ---------------------------------------------------------------------------

if ($resource === '') {
    jsonResponse([
        'project' => 'AttendQR',
        'status'  => 'API operativa',
        'version' => '0.1.0',
    ]);
}

// ---------------------------------------------------------------------------
// 9. Resolución del controlador
// ---------------------------------------------------------------------------

if (!array_key_exists($resource, $routes)) {
    errorResponse("Ruta '/{$resource}' no encontrada.", 404);
}

$routeConfig     = $routes[$resource];
$controllerFile  = CONTROLLERS_PATH . $routeConfig['file'];
$controllerClass = $routeConfig['class'];

if (!file_exists($controllerFile)) {
    errorResponse(
        "Controlador '{$routeConfig['file']}' no encontrado en Src/Controllers/.",
        500
    );
}

// ---------------------------------------------------------------------------
// 10. Aplicación de Middleware
//
// AuthMiddleware::verificar()   → detiene con 401 si no hay sesión.
// RoleMiddleware::requerirRol() → detiene con 403 si el rol no coincide.
// ---------------------------------------------------------------------------

require_once MIDDLEWARE_PATH . 'AuthMiddleware.php';
require_once MIDDLEWARE_PATH . 'RoleMiddleware.php';

$politica = $politicasAcceso[$resource] ?? 'solo_docente';

switch ($politica) {

    case 'publica':
        break;

    case 'autenticada':
        AuthMiddleware::verificar();
        break;

    case 'solo_docente':
        $usuarioActual = AuthMiddleware::verificar();
        RoleMiddleware::soloDocente($usuarioActual);
        break;

    case 'solo_aprendiz':
        $usuarioActual = AuthMiddleware::verificar();
        RoleMiddleware::soloAprendiz($usuarioActual);
        break;

    case 'docente_aprendiz':
        $usuarioActual = AuthMiddleware::verificar();
        RoleMiddleware::requerirRol($usuarioActual, ['docente', 'aprendiz']);
        break;

    default:
        errorResponse('Política de acceso no definida para este recurso.', 500);
}

// ---------------------------------------------------------------------------
// 11. Carga e instanciación del controlador
//
// En este punto todas las dependencias (Database, Repositories, Services)
// ya están cargadas, por lo que new XxxService() dentro del constructor
// del Controller se resuelve correctamente.
// ---------------------------------------------------------------------------

require_once $controllerFile;

if (!class_exists($controllerClass)) {
    errorResponse(
        "La clase '{$controllerClass}' no fue encontrada en el archivo cargado.",
        500
    );
}

$controller = new $controllerClass();

// ---------------------------------------------------------------------------
// 12. Despacho
// ---------------------------------------------------------------------------

if (!method_exists($controller, 'handle')) {
    errorResponse(
        "La clase '{$controllerClass}' no implementa el método handle().",
        500
    );
}

$controller->handle($method, $action, $params);