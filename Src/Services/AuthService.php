<?php

declare(strict_types=1);

/**
 * AttendQR – AuthService
 *
 * Responsabilidad: lógica de negocio de autenticación de usuarios.
 * Flujo: AuthController → AuthService → AuthRepository / TokenRepository → Database
 *
 * Ubicación en el proyecto: Src/Services/AuthService.php
 */
class AuthService
{
    private AuthRepository  $authRepo;
    private TokenRepository $tokenRepo;

    public function __construct()
    {
        $this->authRepo  = new AuthRepository();
        $this->tokenRepo = new TokenRepository();
    }

    /**
     * Autentica un usuario verificando correo, contraseña y estado.
     * Registra el último acceso si las credenciales son válidas.
     *
     * @param string $correo     Correo electrónico del usuario.
     * @param string $contrasena Contraseña en texto plano.
     * @return array<string, mixed> Datos públicos del usuario autenticado.
     * @throws \RuntimeException 401 si las credenciales son incorrectas.
     * @throws \RuntimeException 403 si el usuario está inactivo.
     */
    public function login(string $correo, string $contrasena): array
    {
        $correo     = strtolower(trim($correo));
        $contrasena = trim($contrasena);

        $usuario = $this->authRepo->buscarPorCorreo($correo);

        if ($usuario === null || !password_verify($contrasena, (string) $usuario['contrasena_hash'])) {
            throw new \RuntimeException('Credenciales inválidas.', 401);
        }

        if ((string) $usuario['estado'] !== 'activo') {
            throw new \RuntimeException('El usuario se encuentra inactivo.', 403);
        }

        $this->authRepo->registrarAcceso((int) $usuario['id']);

        return $this->formatearUsuario($usuario);
    }

    /**
     * Cierra la sesión revocando el token de acceso recibido.
     *
     * @param string $token Token de acceso en texto plano.
     * @return array<string, mixed>
     */
    public function logout(string $token): array
    {
        $tokenHash = hash('sha256', trim($token));
        $this->tokenRepo->revocar($tokenHash);

        return ['success' => true, 'message' => 'Sesión cerrada correctamente.'];
    }

    /**
     * Verifica que un token de acceso sea válido, vigente y no revocado.
     *
     * @param string $token Token de acceso en texto plano.
     * @return array<string, mixed> Payload del token (id_usuario, rol, expiracion).
     * @throws \RuntimeException 401 si el token es inválido, expirado o revocado.
     */
    public function verificarToken(string $token): array
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

    // -------------------------------------------------------------------------
    // Métodos privados de apoyo
    // -------------------------------------------------------------------------

    /**
     * Retorna los datos del usuario eliminando campos sensibles.
     *
     * @param array<string, mixed> $usuario Fila cruda del Repository.
     * @return array<string, mixed>
     */
    private function formatearUsuario(array $usuario): array
    {
        foreach (['contrasena_hash', 'contrasena', 'password'] as $campo) {
            unset($usuario[$campo]);
        }

        return $usuario;
    }
}