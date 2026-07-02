<?php

declare(strict_types=1);

/**
 * AttendQR – AuthController
 *
 * Responsabilidad: gestionar autenticación de docentes y aprendices.
 * Delega toda la lógica a AuthService.
 *
 * Rutas:
 *   POST /api/auth/login      → iniciar sesión (docente o aprendiz)
 *   POST /api/auth/logout     → cerrar sesión
 *   GET  /api/auth/verificar  → verificar sesión activa
 *
 * Ubicación en el proyecto: Src/Controllers/AuthController.php
 */
class AuthController
{
    private AuthService $servicio;

    public function __construct()
    {
        $this->servicio = new AuthService();
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