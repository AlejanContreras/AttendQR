<?php

declare(strict_types=1);

/**
 * AttendQR – TokenService
 *
 * Responsabilidad: gestionar el ciclo de vida de los tokens de autenticación.
 * Flujo: TokenController → TokenService → TokenRepository → Database
 *
 * Ubicación en el proyecto: Src/Services/TokenService.php
 */
class TokenService
{
    /** Duración del token de acceso en segundos (15 minutos). */
    private const EXPIRACION_ACCESO_SEGUNDOS = 900;

    /** Duración del token de refresco en segundos (7 días). */
    private const EXPIRACION_REFRESCO_SEGUNDOS = 604800;

    private TokenRepository $tokenRepo;

    public function __construct()
    {
        $this->tokenRepo = new TokenRepository();
    }

    /**
     * Genera un par de tokens (acceso + refresco) para un usuario autenticado.
     * Persiste el token de refresco hasheado en la base de datos.
     *
     * @param int    $idUsuario Identificador del usuario.
     * @param string $rol       Rol del usuario ('docente' | 'admin' | 'aprendiz').
     * @return array<string, mixed>
     * @throws \RuntimeException 422 si el rol no es válido.
     */
    public function generar(int $idUsuario, string $rol): array
    {
        $rolesPermitidos = ['docente', 'admin', 'aprendiz'];

        if (!in_array($rol, $rolesPermitidos, true)) {
            throw new \RuntimeException(
                "Rol '{$rol}' no válido. Valores permitidos: " . implode(', ', $rolesPermitidos) . '.',
                422
            );
        }

        $tokenAcceso   = $this->generarTokenUnico();
        $tokenRefresco = $this->generarTokenUnico();
        $expAcceso     = $this->calcularExpiracion(self::EXPIRACION_ACCESO_SEGUNDOS);
        $expRefresco   = $this->calcularExpiracion(self::EXPIRACION_REFRESCO_SEGUNDOS);

        $this->tokenRepo->persistirRefresco(
            $idUsuario,
            hash('sha256', $tokenRefresco),
            $expRefresco,
            $rol
        );

        return [
            'token_acceso'    => $tokenAcceso,
            'token_refresco'  => $tokenRefresco,
            'expira_acceso'   => $expAcceso,
            'expira_refresco' => $expRefresco,
            'tipo'            => 'Bearer',
        ];
    }

    /**
     * Valida un token de acceso y retorna su payload si es correcto.
     *
     * @param string $token Token de acceso en texto plano.
     * @return array<string, mixed> Payload (id_usuario, rol, expiracion).
     * @throws \RuntimeException 401 si el token no existe, expiró o fue revocado.
     */
    public function validar(string $token): array
    {
        $tokenHash = hash('sha256', trim($token));
        $registro  = $this->tokenRepo->buscarAcceso($tokenHash);

        if ($registro === null) {
            throw new \RuntimeException('Token no encontrado.', 401);
        }

        if (strtotime((string) $registro['expiracion']) < time()) {
            throw new \RuntimeException('El token ha expirado.', 401);
        }

        if ((int) $registro['revocado'] === 1) {
            throw new \RuntimeException('El token fue revocado.', 401);
        }

        return [
            'id_usuario' => (int) $registro['id_usuario'],
            'rol'        => $registro['rol'],
            'expiracion' => $registro['expiracion'],
        ];
    }

    /**
     * Renueva el token de acceso usando el token de refresco.
     *
     * @param string $tokenRefresco Token de refresco en texto plano.
     * @return array<string, mixed> Nuevo token de acceso y su expiración.
     * @throws \RuntimeException 401 si el token de refresco es inválido, expiró o fue revocado.
     */
    public function renovar(string $tokenRefresco): array
    {
        $refrescoHash = hash('sha256', trim($tokenRefresco));
        $registro     = $this->tokenRepo->buscarRefresco($refrescoHash);

        if ($registro === null) {
            throw new \RuntimeException('Token de refresco no encontrado.', 401);
        }

        if (strtotime((string) $registro['expiracion']) < time()) {
            throw new \RuntimeException('El token de refresco ha expirado.', 401);
        }

        if ((int) $registro['revocado'] === 1) {
            throw new \RuntimeException('El token de refresco fue revocado.', 401);
        }

        $nuevoToken      = $this->generarTokenUnico();
        $nuevaExpiracion = $this->calcularExpiracion(self::EXPIRACION_ACCESO_SEGUNDOS);

        return [
            'token_acceso' => $nuevoToken,
            'expira_en'    => $nuevaExpiracion,
            'tipo'         => 'Bearer',
        ];
    }

    /**
     * Revoca un token invalidándolo aunque no haya expirado.
     *
     * @param string $token Token en texto plano.
     * @return array<string, mixed>
     * @throws \RuntimeException 404 si el token no existe.
     */
    public function eliminar(string $token): array
    {
        $tokenHash = hash('sha256', trim($token));
        $afectadas = $this->tokenRepo->revocar($tokenHash);

        if ($afectadas === 0) {
            throw new \RuntimeException('Token no encontrado.', 404);
        }

        return ['success' => true, 'message' => 'Token revocado correctamente.'];
    }

    // -------------------------------------------------------------------------
    // Métodos privados de apoyo
    // -------------------------------------------------------------------------

    private function generarTokenUnico(): string
    {
        return bin2hex(random_bytes(32));
    }

    private function calcularExpiracion(int $segundos): string
    {
        return date('Y-m-d H:i:s', time() + $segundos);
    }
}