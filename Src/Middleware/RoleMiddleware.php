<?php

declare(strict_types=1);

/**
 * AttendQR – RoleMiddleware
 *
 * Responsabilidad: verificar que el usuario autenticado posea el rol
 * requerido para acceder a un recurso protegido.
 *
 * Roles definidos para el MVP:
 *   - docente   → instructores SENA
 *   - aprendiz  → estudiantes inscritos en fichas
 *
 * Debe usarse siempre después de AuthMiddleware::verificar(),
 * ya que asume que el usuario ya está autenticado en sesión.
 *
 * No contiene lógica de negocio.
 * No accede a la base de datos.
 * No conoce Controllers ni Services.
 *
 * Uso desde api.php (cuando se integre):
 *   $usuario = AuthMiddleware::verificar();
 *   RoleMiddleware::requerirRol($usuario, 'docente');
 *
 * Uso con múltiples roles permitidos:
 *   RoleMiddleware::requerirRol($usuario, ['docente', 'aprendiz']);
 *
 * Ubicación en el proyecto: Src/Middleware/RoleMiddleware.php
 */
class RoleMiddleware
{
    /**
     * Roles válidos definidos para el MVP.
     * Cualquier rol fuera de esta lista se considera inválido.
     */
    private const ROLES_PERMITIDOS = ['docente', 'aprendiz'];

    /**
     * Verifica que el usuario autenticado tenga uno de los roles requeridos.
     *
     * Si el rol es válido, retorna sin efecto y la ejecución continúa.
     * Si el rol no está autorizado, responde con JSON 403 y detiene la ejecución.
     *
     * @param array<string, mixed>  $usuario       Datos del usuario (obtenidos de AuthMiddleware::verificar()).
     * @param string|string[]       $rolesRequeridos Rol o lista de roles que pueden acceder.
     */
    public static function requerirRol(array $usuario, string|array $rolesRequeridos): void
    {
        $rolUsuario     = (string) ($usuario['rol'] ?? '');
        $rolesRequeridos = is_array($rolesRequeridos) ? $rolesRequeridos : [$rolesRequeridos];

        // Rechazar roles que no están definidos en el sistema
        foreach ($rolesRequeridos as $rol) {
            if (!in_array($rol, self::ROLES_PERMITIDOS, true)) {
                self::responderError(
                    "El rol '{$rol}' no está definido en el sistema.",
                    500
                );
            }
        }

        if ($rolUsuario === '' || !in_array($rolUsuario, $rolesRequeridos, true)) {
            self::responderError(
                'Acceso denegado. No tiene permisos para acceder a este recurso.',
                403
            );
        }
    }

    /**
     * Verifica si el usuario autenticado tiene el rol de docente.
     * Atajo semántico para no pasar el string 'docente' en cada llamada.
     *
     * @param array<string, mixed> $usuario Datos del usuario autenticado.
     */
    public static function soloDocente(array $usuario): void
    {
        self::requerirRol($usuario, 'docente');
    }

    /**
     * Verifica si el usuario autenticado tiene el rol de aprendiz.
     * Atajo semántico para no pasar el string 'aprendiz' en cada llamada.
     *
     * @param array<string, mixed> $usuario Datos del usuario autenticado.
     */
    public static function soloAprendiz(array $usuario): void
    {
        self::requerirRol($usuario, 'aprendiz');
    }

    /**
     * Verifica si el usuario autenticado tiene el rol indicado,
     * sin detener la ejecución si no lo tiene (modo no-bloqueante).
     *
     * Útil para lógica condicional dentro de un Controller.
     *
     * @param array<string, mixed> $usuario        Datos del usuario autenticado.
     * @param string|string[]      $rolesRequeridos Rol o roles a comprobar.
     * @return bool true si el usuario tiene uno de los roles requeridos.
     */
    public static function tieneRol(array $usuario, string|array $rolesRequeridos): bool
    {
        $rolUsuario      = (string) ($usuario['rol'] ?? '');
        $rolesRequeridos = is_array($rolesRequeridos) ? $rolesRequeridos : [$rolesRequeridos];

        return in_array($rolUsuario, $rolesRequeridos, true);
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