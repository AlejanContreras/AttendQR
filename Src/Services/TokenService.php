<?php

declare(strict_types=1);

/**
 * AttendQR – TokenService
 *
 * Responsabilidad: gestionar el ciclo de vida completo de los tokens
 * de autenticación: generación, validación, renovación y revocación.
 *
 * Mientras AuthService gestiona la identidad del usuario (quién eres),
 * este servicio gestiona las credenciales de acceso (con qué accedes).
 *
 * Esta clase NO debe:
 *   - Ejecutar SQL directamente.
 *   - Conocer el router ni los Controllers.
 *   - Acceder a $_POST, $_GET ni $_REQUEST.
 *   - Imprimir JSON, HTML ni usar header() o exit.
 *
 * Flujo esperado:
 *   TokenController → TokenService → TokenRepository → Modelo → Database
 *
 * Ubicación en el proyecto: Src/Services/TokenService.php
 */
class TokenService
{
    // -------------------------------------------------------------------------
    // Configuración del servicio
    // -------------------------------------------------------------------------

    /** Duración del token de acceso en segundos (por defecto: 15 minutos). */
    private const EXPIRACION_ACCESO_SEGUNDOS = 900;

    /** Duración del token de refresco en segundos (por defecto: 7 días). */
    private const EXPIRACION_REFRESCO_SEGUNDOS = 604800;

    // -------------------------------------------------------------------------
    // Dependencias (se inyectarán cuando existan los Repositories)
    // -------------------------------------------------------------------------

    // ► AQUÍ: declarar dependencias
    //
    // Ejemplo futuro:
    //   private TokenRepository $tokenRepo;
    //
    //   public function __construct(TokenRepository $tokenRepo)
    //   {
    //       $this->tokenRepo = $tokenRepo;
    //   }

    // -------------------------------------------------------------------------
    // Métodos públicos
    // -------------------------------------------------------------------------

    /**
     * Genera un par de tokens (acceso + refresco) para un usuario autenticado.
     *
     * Reglas de negocio:
     *   1. Generar token de acceso con expiración corta (EXPIRACION_ACCESO_SEGUNDOS).
     *   2. Generar token de refresco con expiración larga (EXPIRACION_REFRESCO_SEGUNDOS).
     *   3. Persistir el token de refresco hasheado en la base de datos.
     *   4. Retornar ambos tokens y sus tiempos de expiración.
     *
     * @param int    $idUsuario Identificador único del usuario autenticado.
     * @param string $rol       Rol del usuario en el sistema ('docente' | 'admin').
     * @return array<string, mixed>
     */
    public function generar(int $idUsuario, string $rol): array
    {
        $rolesPermitidos = ['docente', 'admin', 'aprendiz'];

        if (!in_array($rol, $rolesPermitidos, true)) {
            return [
                'success' => false,
                'message' => "Rol '{$rol}' no válido. Valores permitidos: " . implode(', ', $rolesPermitidos) . '.',
            ];
        }

        // ► AQUÍ: generar token de acceso con $this->generarTokenUnico()
        // ► AQUÍ: calcular expiración de acceso: time() + self::EXPIRACION_ACCESO_SEGUNDOS
        // ► AQUÍ: generar token de refresco con $this->generarTokenUnico()
        // ► AQUÍ: calcular expiración de refresco: time() + self::EXPIRACION_REFRESCO_SEGUNDOS
        // ► AQUÍ: llamar a TokenRepository->persistirRefresco($idUsuario, hash('sha256', $tokenRefresco), $expRefresco)
        //
        // Ejemplo futuro:
        //   $tokenAcceso   = $this->generarTokenUnico();
        //   $tokenRefresco = $this->generarTokenUnico();
        //   $expAcceso     = date('Y-m-d H:i:s', time() + self::EXPIRACION_ACCESO_SEGUNDOS);
        //   $expRefresco   = date('Y-m-d H:i:s', time() + self::EXPIRACION_REFRESCO_SEGUNDOS);
        //   $this->tokenRepo->persistirRefresco($idUsuario, hash('sha256', $tokenRefresco), $expRefresco);
        //   return ['token_acceso' => $tokenAcceso, 'token_refresco' => $tokenRefresco, 'expira_en' => $expAcceso];

        return [
            'success'    => true,
            'message'    => 'TokenService::generar() disponible. Pendiente de implementación.',
            'id_usuario' => $idUsuario,
            'rol'        => $rol,
        ];
    }

    /**
     * Valida un token de acceso y retorna su payload si es correcto.
     *
     * Reglas de negocio:
     *   1. Verificar que el token existe en la base de datos.
     *   2. Verificar que no ha expirado.
     *   3. Verificar que no está en la lista de revocados.
     *   4. Retornar el payload (id_usuario, rol, expiracion).
     *
     * @param string $token Token de acceso a validar.
     * @return array<string, mixed>
     */
    public function validar(string $token): array
    {
        $token = trim($token);

        if ($token === '') {
            return ['success' => false, 'message' => 'El token no puede estar vacío.'];
        }

        // ► AQUÍ: llamar a TokenRepository->buscarAcceso(hash('sha256', $token))
        // ► AQUÍ: si no existe, lanzar new \RuntimeException('Token no encontrado.', 401)
        // ► AQUÍ: si expiró (expiracion < now()), lanzar new \RuntimeException('Token expirado.', 401)
        // ► AQUÍ: si está revocado, lanzar new \RuntimeException('Token revocado.', 401)
        // ► AQUÍ: retornar el payload: ['id_usuario' => ..., 'rol' => ..., 'expiracion' => ...]

        return [
            'success' => true,
            'message' => 'TokenService::validar() disponible. Pendiente de implementación.',
        ];
    }

    /**
     * Renueva el token de acceso usando el token de refresco.
     *
     * Reglas de negocio:
     *   1. Verificar que el token de refresco existe y no ha expirado.
     *   2. Verificar que no está revocado.
     *   3. Generar un nuevo token de acceso con expiración renovada.
     *   4. Retornar el nuevo token de acceso.
     *
     * @param string $tokenRefresco Token de refresco del usuario.
     * @return array<string, mixed>
     */
    public function renovar(string $tokenRefresco): array
    {
        $tokenRefresco = trim($tokenRefresco);

        if ($tokenRefresco === '') {
            return ['success' => false, 'message' => 'El token de refresco no puede estar vacío.'];
        }

        // ► AQUÍ: llamar a TokenRepository->buscarRefresco(hash('sha256', $tokenRefresco))
        // ► AQUÍ: si no existe o expiró, lanzar new \RuntimeException('Token de refresco inválido.', 401)
        // ► AQUÍ: generar nuevo token de acceso con $this->generarTokenUnico()
        // ► AQUÍ: calcular nueva expiración: time() + self::EXPIRACION_ACCESO_SEGUNDOS
        // ► AQUÍ: persistir el nuevo token de acceso en TokenRepository
        // ► AQUÍ: retornar el nuevo token y su expiración

        return [
            'success' => true,
            'message' => 'TokenService::renovar() disponible. Pendiente de implementación.',
        ];
    }

    /**
     * Revoca un token, invalidándolo aunque técnicamente no haya expirado.
     *
     * Reglas de negocio:
     *   1. Verificar que el token existe.
     *   2. Marcarlo como revocado en la base de datos.
     *   3. Retornar confirmación.
     *
     * Útil para: cierre de sesión explícito, cierre de sesiones remotas,
     * revocación de acceso tras cambio de contraseña.
     *
     * @param string $token Token de acceso o de refresco a revocar.
     * @return array<string, mixed>
     */
    public function eliminar(string $token): array
    {
        $token = trim($token);

        if ($token === '') {
            return ['success' => false, 'message' => 'El token no puede estar vacío.'];
        }

        // ► AQUÍ: llamar a TokenRepository->revocar(hash('sha256', $token))
        // ► AQUÍ: si no existe, lanzar new \RuntimeException('Token no encontrado.', 404)

        return [
            'success' => true,
            'message' => 'TokenService::eliminar() disponible. Pendiente de implementación.',
        ];
    }

    // -------------------------------------------------------------------------
    // Métodos privados de apoyo
    // -------------------------------------------------------------------------

    /**
     * Genera un token único e impredecible usando entropía criptográfica.
     * Produce una cadena hexadecimal de 64 caracteres.
     *
     * ► AQUÍ: llamar desde generar() y renovar() cuando se implemente la lógica real.
     *
     * @return string Token hexadecimal de 64 caracteres.
     */
    private function generarTokenUnico(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Calcula una fecha/hora de expiración sumando segundos al momento actual.
     *
     * @param int $segundos Número de segundos de vida del token.
     * @return string Fecha y hora de expiración en formato 'Y-m-d H:i:s'.
     */
    private function calcularExpiracion(int $segundos): string
    {
        return date('Y-m-d H:i:s', time() + $segundos);
    }
}