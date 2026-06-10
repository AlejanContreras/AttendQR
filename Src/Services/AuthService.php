<?php

declare(strict_types=1);

/**
 * AttendQR – AuthService
 *
 * Responsabilidad: contener toda la lógica de negocio relacionada
 * con la autenticación de usuarios del sistema.
 *
 * Esta clase NO debe:
 *   - Ejecutar SQL directamente.
 *   - Conocer el router ni los Controllers.
 *   - Acceder a $_POST, $_GET ni $_REQUEST.
 *   - Imprimir JSON ni HTML.
 *
 * Flujo esperado cuando se integren las capas inferiores:
 *   AuthController → AuthService → AuthRepository → Modelo → Database
 *
 * Ubicación en el proyecto: Src/Services/AuthService.php
 */
class AuthService
{
    // -------------------------------------------------------------------------
    // Dependencias (se inyectarán cuando se creen los Repositories)
    // -------------------------------------------------------------------------

    // ► AQUÍ: declarar dependencias cuando existan los Repositories
    //
    // Ejemplo futuro:
    //   private UsuarioRepository $usuarioRepo;
    //   private TokenRepository   $tokenRepo;
    //
    //   public function __construct(
    //       UsuarioRepository $usuarioRepo,
    //       TokenRepository   $tokenRepo
    //   ) {
    //       $this->usuarioRepo = $usuarioRepo;
    //       $this->tokenRepo   = $tokenRepo;
    //   }

    // -------------------------------------------------------------------------
    // Métodos públicos del servicio
    // -------------------------------------------------------------------------

    /**
     * Autentica a un usuario verificando sus credenciales.
     *
     * Reglas de negocio que este método deberá aplicar:
     *   1. Verificar que el correo existe en la base de datos.
     *   2. Verificar la contraseña usando password_verify().
     *   3. Verificar que el usuario está activo (no suspendido).
     *   4. Generar un token de acceso (vida corta, p. ej. 15 min).
     *   5. Generar un token de refresco (vida larga, p. ej. 7 días).
     *   6. Persistir el token de refresco hasheado en la base de datos.
     *   7. Retornar los tokens y los datos públicos del usuario.
     *
     * @param string $correo     Correo electrónico del usuario.
     * @param string $contrasena Contraseña en texto plano (se hasheará en el Repository).
     * @return array<string, mixed> Tokens de acceso y datos del usuario autenticado.
     */
    public function login(string $correo, string $contrasena): array
    {
        // Sanitizar entradas básicas antes de delegar al Repository
        $correo     = strtolower(trim($correo));
        $contrasena = trim($contrasena);

        // ► AQUÍ: llamar a UsuarioRepository->buscarPorCorreo($correo)
        // ► AQUÍ: verificar password_verify($contrasena, $usuario->contrasena_hash)
        // ► AQUÍ: verificar que $usuario->estado === 'activo'
        // ► AQUÍ: llamar a TokenRepository->crearParSesion($usuario->id, $usuario->rol)
        //
        // Ejemplo futuro:
        //   $usuario = $this->usuarioRepo->buscarPorCorreo($correo);
        //   if (!$usuario || !password_verify($contrasena, $usuario['contrasena_hash'])) {
        //       throw new \RuntimeException('Credenciales inválidas.', 401);
        //   }
        //   if ($usuario['estado'] !== 'activo') {
        //       throw new \RuntimeException('Usuario inactivo.', 403);
        //   }
        //   $tokens = $this->tokenRepo->crearParSesion($usuario['id'], $usuario['rol']);
        //   return ['usuario' => $this->formatearUsuario($usuario), 'tokens' => $tokens];

        return [
            'success' => true,
            'message' => 'AuthService::login() disponible. Pendiente de implementación.',
            'correo'  => $correo,
        ];
    }

    /**
     * Cierra la sesión del usuario invalidando su token activo.
     *
     * Reglas de negocio que este método deberá aplicar:
     *   1. Verificar que el token existe y pertenece a un usuario activo.
     *   2. Marcarlo como revocado en la base de datos.
     *   3. Si el usuario tiene múltiples sesiones, invalidar solo la indicada.
     *
     * @param string $token Token de acceso a invalidar.
     * @return array<string, mixed> Confirmación de cierre de sesión.
     */
    public function logout(string $token): array
    {
        $token = trim($token);

        // ► AQUÍ: llamar a TokenRepository->revocar($token)
        //
        // Ejemplo futuro:
        //   $this->tokenRepo->revocar($token);
        //   return ['success' => true, 'message' => 'Sesión cerrada correctamente.'];

        return [
            'success' => true,
            'message' => 'AuthService::logout() disponible. Pendiente de implementación.',
        ];
    }

    /**
     * Verifica que un token de acceso sea válido y no haya expirado.
     *
     * Reglas de negocio que este método deberá aplicar:
     *   1. Decodificar y verificar la firma del token.
     *   2. Comprobar que no haya expirado (campo exp del payload).
     *   3. Verificar que no esté en la lista de tokens revocados.
     *   4. Retornar el payload decodificado (id_usuario, rol, etc.).
     *
     * @param string $token Token de acceso a verificar.
     * @return array<string, mixed> Payload del token si es válido.
     */
    public function verificarToken(string $token): array
    {
        $token = trim($token);

        // ► AQUÍ: decodificar el JWT y verificar su firma con la clave secreta
        // ► AQUÍ: llamar a TokenRepository->estaRevocado($token)
        //
        // Ejemplo futuro:
        //   $payload = $this->decodificarJwt($token);
        //   if ($this->tokenRepo->estaRevocado($token)) {
        //       throw new \RuntimeException('Token revocado.', 401);
        //   }
        //   return $payload;

        return [
            'success' => true,
            'message' => 'AuthService::verificarToken() disponible. Pendiente de implementación.',
        ];
    }

    // -------------------------------------------------------------------------
    // Métodos privados de apoyo
    // -------------------------------------------------------------------------

    /**
     * Formatea los datos públicos de un usuario eliminando campos sensibles.
     * Nunca debe retornar la contraseña hasheada hacia el Controller.
     *
     * ► AQUÍ: implementar cuando exista el Modelo de Usuario.
     *
     * @param array<string, mixed> $usuario Fila cruda del Repository.
     * @return array<string, mixed> Datos seguros del usuario.
     */
    private function formatearUsuario(array $usuario): array
    {
        // Campos que NUNCA deben salir hacia el Controller
        $camposSensibles = ['contrasena_hash', 'contrasena', 'password'];

        foreach ($camposSensibles as $campo) {
            unset($usuario[$campo]);
        }

        return $usuario;
    }
}