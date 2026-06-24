<?php

declare(strict_types=1);

/**
 * AttendQR – AuthController
 *
 * Responsabilidad: gestionar autenticación de usuarios.
 * Delega toda la lógica a AuthService.
 *
 * Rutas:
 *   POST /api/auth/login      → iniciar sesión
 *   POST /api/auth/logout     → cerrar sesión
 *   GET  /api/auth/verificar  → verificar token activo
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

    /**
     * Punto de entrada del router.
     *
     * @param string   $metodo Método HTTP (GET, POST, etc.)
     * @param string   $accion Segundo segmento de la URL (/api/auth/{accion})
     * @param string[] $params Parámetros posicionales adicionales
     */
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
                $this->verificarToken();
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
     * Body: { "correo": "...", "contrasena": "..." }
     */
    private function login(): void
    {
        $cuerpo = $this->leerCuerpoJson();

        if (empty($cuerpo['correo']) || empty($cuerpo['contrasena'])) {
            $this->responderError('Los campos correo y contrasena son obligatorios.', 422);
        }

        try {
            $resultado = $this->servicio->login(
                (string) $cuerpo['correo'],
                (string) $cuerpo['contrasena']
            );
            $this->responderExito('Sesión iniciada correctamente.', $resultado);

        } catch (\RuntimeException $e) {
            $this->responderError($e->getMessage(), $e->getCode() ?: 400);
        } catch (\Throwable $e) {
            $this->responderError('Error interno al iniciar sesión.', 500);
        }
    }

    /**
     * POST /api/auth/logout
     * Header: Authorization: Bearer {token}
     */
    private function logout(): void
    {
        $token = $this->leerTokenHeader();

        try {
            $resultado = $this->servicio->logout($token);
            $this->responderExito($resultado['message'] ?? 'Sesión cerrada correctamente.', []);

        } catch (\RuntimeException $e) {
            $this->responderError($e->getMessage(), $e->getCode() ?: 400);
        } catch (\Throwable $e) {
            $this->responderError('Error interno al cerrar sesión.', 500);
        }
    }

    /**
     * GET /api/auth/verificar
     * Header: Authorization: Bearer {token}
     */
    private function verificarToken(): void
    {
        $token = $this->leerTokenHeader();

        try {
            $payload = $this->servicio->verificarToken($token);
            $this->responderExito('Token válido.', $payload);

        } catch (\RuntimeException $e) {
            $this->responderError($e->getMessage(), $e->getCode() ?: 401);
        } catch (\Throwable $e) {
            $this->responderError('Error interno al verificar el token.', 500);
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
                "Este endpoint solo acepta {$metodoEsperado}, se recibió {$metodoRecibido}.",
                405
            );
        }
    }

    /**
     * Lee el token Bearer del header Authorization.
     * Responde 401 si no está presente o tiene formato incorrecto.
     */
    private function leerTokenHeader(): string
    {
        $header = $_SERVER['HTTP_AUTHORIZATION']
            ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION']
            ?? '';

        if (str_starts_with($header, 'Bearer ')) {
            return trim(substr($header, 7));
        }

        $this->responderError('Token de autorización no proporcionado.', 401);
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