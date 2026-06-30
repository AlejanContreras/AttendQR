<?php

declare(strict_types=1);

/**
 * AttendQR – AuthMiddleware
 *
 * Responsabilidad: verificar que exista una sesión PHP activa y válida
 * antes de permitir el acceso a un recurso protegido.
 *
 * No contiene lógica de negocio.
 * No accede a la base de datos.
 * No conoce Controllers ni Services.
 *
 * Uso desde api.php (cuando se integre):
 *   AuthMiddleware::verificar();   // detiene la ejecución con 401 si no hay sesión
 *
 * Ubicación en el proyecto: Src/Middleware/AuthMiddleware.php
 */
class AuthMiddleware
{
    /**
     * Verifica que exista una sesión autenticada válida.
     *
     * Si la sesión es válida, retorna los datos del usuario autenticado.
     * Si no hay sesión activa, responde con JSON 401 y detiene la ejecución.
     *
     * @return array<string, mixed> Datos del usuario autenticado en sesión.
     */
    public static function verificar(): array
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (empty($_SESSION['usuario']) || !is_array($_SESSION['usuario'])) {
            self::responderError('No autenticado. Debe iniciar sesión para acceder a este recurso.', 401);
        }

        $usuario = $_SESSION['usuario'];

        // Validar que la sesión tenga los campos mínimos esperados
        if (empty($usuario['id']) || empty($usuario['rol'])) {
            self::responderError('Sesión inválida. Inicie sesión nuevamente.', 401);
        }

        return $usuario;
    }

    /**
     * Verifica la sesión y retorna el usuario autenticado,
     * o null si no hay sesión activa (modo no-bloqueante).
     *
     * Útil para rutas opcionales donde la autenticación no es obligatoria.
     *
     * @return array<string, mixed>|null Datos del usuario o null.
     */
    public static function obtenerUsuario(): ?array
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (empty($_SESSION['usuario']) || !is_array($_SESSION['usuario'])) {
            return null;
        }

        return $_SESSION['usuario'];
    }

    // -------------------------------------------------------------------------
    // Auxiliares privados
    // -------------------------------------------------------------------------

    /**
     * Envía una respuesta JSON de error y detiene la ejecución.
     *
     * @param string $mensaje Descripción del error.
     * @param int    $codigo  Código HTTP de error.
     */
    private static function responderError(string $mensaje, int $codigo): never
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