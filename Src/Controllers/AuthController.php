<?php

declare(strict_types=1);

/**
 * AttendQR – AuthController
 *
 * Responsabilidad: gestionar autenticación de docentes y aprendices.
 * Delega toda la lógica a AuthService.
 *
 * Rutas:
 *   POST /api/auth/login                → iniciar sesión (docente o aprendiz)
 *   POST /api/auth/logout               → cerrar sesión
 *   GET  /api/auth/verificar            → verificar sesión activa
 *   POST /api/auth/verificar-documento  → paso 1 del auto-registro de aprendiz
 *   POST /api/auth/activar-cuenta       → paso 2 del auto-registro de aprendiz
 *
 * Ubicación en el proyecto: Src/Controllers/AuthController.php
 */
class AuthController
{
    private AuthService     $servicio;
    private AprendizService $aprendizServicio;

    public function __construct()
    {
        $this->servicio           = new AuthService();
        $this->aprendizServicio   = new AprendizService();
    }

    public function handle(string $metodo, string $accion, array $params): void
    {
        switch ($accion) {
            case 'login':
                $this->verificarMetodo($metodo, 'POST');
                $this->login();
                break;

            case 'logout':
                $this->verificarMetodo($metodo, 'POST');
                $this->logout();
                break;

            case 'verificar':
                $this->verificarMetodo($metodo, 'GET');
                $this->verificar();
                break;

            case 'verificar-documento':
                $this->verificarMetodo($metodo, 'POST');
                $this->verificarDocumento();
                break;

            case 'activar-cuenta':
                $this->verificarMetodo($metodo, 'POST');
                $this->activarCuenta();
                break;

            default:
                $this->responderError("Acción '{$accion}' no encontrada en AuthController.", 404);
        }
    }

    // -------------------------------------------------------------------------
    // Acciones
    // -------------------------------------------------------------------------

    /**
     * POST /api/auth/login
     *
     * Para docentes:   Body { "correo": "...",    "password": "..." }
     * Para aprendices: Body { "documento": "...", "password": "..." }
     */
    private function login(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $cuerpo = $this->leerCuerpoJson();

        $correo    = (string) ($cuerpo['correo']    ?? '');
        $documento = (string) ($cuerpo['documento'] ?? '');
        $password  = (string) ($cuerpo['password']  ?? '');

        if ($correo === '' && $documento === '') {
            $this->responderError(
                'Debe proporcionar correo (docente) o documento (aprendiz).', 422
            );
        }

        if ($password === '') {
            $this->responderError('La contraseña es obligatoria.', 422);
        }

        try {
            $usuario = $this->servicio->login($correo, $documento, $password);

            $_SESSION['usuario'] = $usuario;

            $this->responderExito('Sesión iniciada correctamente.', $usuario);

        } catch (\RuntimeException $e) {
            $this->responderError($e->getMessage(), $e->getCode() ?: 400);
        } catch (\Throwable $e) {
            $this->responderError('Error interno al iniciar sesión.', 500);
        }
    }

    /**
     * POST /api/auth/logout
     */
    private function logout(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        try {
            $resultado = $this->servicio->logout();
            $this->responderExito($resultado['message'] ?? 'Sesión cerrada correctamente.', []);

        } catch (\Throwable $e) {
            $this->responderError('Error interno al cerrar sesión.', 500);
        }
    }

    /**
     * GET /api/auth/verificar
     */
    private function verificar(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        try {
            $usuario = $this->servicio->verificarToken();
            $this->responderExito('Sesión activa.', $usuario);

        } catch (\RuntimeException $e) {
            $this->responderError($e->getMessage(), $e->getCode() ?: 401);
        } catch (\Throwable $e) {
            $this->responderError('Error interno al verificar la sesión.', 500);
        }
    }

    /**
     * POST /api/auth/verificar-documento
     * Paso 1 del auto-registro: verifica si el documento existe y está pendiente.
     * Body: { "documento": "1098234567" }
     */
    private function verificarDocumento(): void
    {
        $cuerpo    = $this->leerCuerpoJson();
        $documento = trim((string) ($cuerpo['documento'] ?? ''));

        if ($documento === '') {
            $this->responderError('El campo documento es obligatorio.', 422);
        }

        try {
            $datos = $this->aprendizServicio->verificarParaRegistro($documento);
            $this->responderExito('Documento verificado. Completa tu registro.', $datos);

        } catch (\RuntimeException $e) {
            $this->responderError($e->getMessage(), $e->getCode() ?: 400);
        } catch (\Throwable $e) {
            $this->responderError('Error interno al verificar el documento.', 500);
        }
    }

    /**
     * POST /api/auth/activar-cuenta
     * Paso 2 del auto-registro: establece contraseña y activa la cuenta.
     * Body: { "id_aprendiz": 5, "password": "...", "confirmar_password": "..." }
     * Tras la activación inicia sesión automáticamente.
     */
    private function activarCuenta(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $cuerpo    = $this->leerCuerpoJson();
        $idAprendiz = (int) ($cuerpo['id_aprendiz'] ?? 0);
        $password   = (string) ($cuerpo['password']           ?? '');
        $confirmar  = (string) ($cuerpo['confirmar_password'] ?? '');

        if ($idAprendiz <= 0) {
            $this->responderError('El campo id_aprendiz es obligatorio.', 422);
        }
        if ($password === '') {
            $this->responderError('La contraseña es obligatoria.', 422);
        }
        if ($password !== $confirmar) {
            $this->responderError('Las contraseñas no coinciden.', 422);
        }

        try {
            $usuario = $this->aprendizServicio->activarCuenta($idAprendiz, $password);

            // Iniciar sesión automáticamente igual que en login
            $_SESSION['usuario'] = $usuario;

            $this->responderExito('Cuenta activada correctamente. Bienvenido a AttendQR.', $usuario, 201);

        } catch (\RuntimeException $e) {
            $this->responderError($e->getMessage(), $e->getCode() ?: 400);
        } catch (\Throwable $e) {
            $this->responderError('Error interno al activar la cuenta.', 500);
        }
    }

    // -------------------------------------------------------------------------
    // Auxiliares
    // -------------------------------------------------------------------------

    private function verificarMetodo(string $metodoRecibido, string $metodoEsperado): void
    {
        if ($metodoRecibido !== $metodoEsperado) {
            header('Allow: ' . $metodoEsperado);
            $this->responderError(
                "Este endpoint solo acepta {$metodoEsperado}, se recibió {$metodoRecibido}.", 405
            );
        }
    }

    private function leerCuerpoJson(): array
    {
        $crudo = file_get_contents('php://input');

        if ($crudo === false || $crudo === '') {
            $this->responderError('El cuerpo de la petición está vacío.', 400);
        }

        $datos = json_decode($crudo, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->responderError('El cuerpo de la petición no es JSON válido.', 400);
        }

        return $datos ?? [];
    }

    private function responderExito(string $mensaje, array $datos = [], int $codigo = 200): never
    {
        http_response_code($codigo);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(
            ['success' => true, 'message' => $mensaje, 'data' => $datos],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
        exit;
    }

    private function responderError(string $mensaje, int $codigo): never
    {
        http_response_code($codigo);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(
            ['success' => false, 'message' => $mensaje],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
        exit;
    }
}