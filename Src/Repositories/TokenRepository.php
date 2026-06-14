<?php

declare(strict_types=1);

/**
 * AttendQR – TokenRepository
 *
 * Responsabilidad: acceder a la tabla `tokens` para gestionar
 * los tokens de autenticación (acceso y refresco).
 *
 * Los tokens se almacenan hasheados (SHA-256). Este Repository solo
 * recibe y devuelve los hashes; nunca ve los tokens en texto plano.
 * La generación de tokens corresponde a TokenService.
 *
 * Esta clase NO debe:
 *   - Generar ni verificar tokens.
 *   - Contener lógica de autenticación.
 *   - Conocer Controllers ni Services.
 *   - Generar HTML, JSON ni usar header() o exit.
 *
 * Flujo: TokenService → TokenRepository → Database → MySQL (tabla: tokens)
 *
 * Ubicación en el proyecto: Src/Repositories/TokenRepository.php
 */
class TokenRepository extends BaseRepository
{
    // -------------------------------------------------------------------------
    // Consultas de lectura
    // -------------------------------------------------------------------------

    /**
     * Busca un token de acceso por su hash.
     * Retorna el token con el ID de usuario y la fecha de expiración.
     *
     * @param string $tokenHash Hash SHA-256 del token.
     * @return array<string, mixed>|null Datos del token o null si no existe.
     */
    public function buscarAcceso(string $tokenHash): ?array
    {
        // ► AQUÍ: implementar
        //
        // return $this->consultarUno(
        //     "SELECT id, id_usuario, rol, expiracion, revocado
        //      FROM tokens
        //      WHERE hash = :hash AND tipo = 'acceso'
        //      LIMIT 1",
        //     [':hash' => $tokenHash]
        // );

        return null;
    }

    /**
     * Busca un token de refresco por su hash.
     *
     * @param string $tokenHash Hash SHA-256 del token de refresco.
     * @return array<string, mixed>|null Datos del token o null si no existe.
     */
    public function buscarRefresco(string $tokenHash): ?array
    {
        // ► AQUÍ: implementar
        //
        // return $this->consultarUno(
        //     "SELECT id, id_usuario, rol, expiracion, revocado
        //      FROM tokens
        //      WHERE hash = :hash AND tipo = 'refresco'
        //      LIMIT 1",
        //     [':hash' => $tokenHash]
        // );

        return null;
    }

    /**
     * Verifica si un token está marcado como revocado.
     *
     * @param string $tokenHash Hash SHA-256 del token.
     * @return bool true si el token fue revocado.
     */
    public function estaRevocado(string $tokenHash): bool
    {
        // ► AQUÍ: implementar
        //
        // return $this->existe(
        //     'SELECT COUNT(*) FROM tokens WHERE hash = :hash AND revocado = 1',
        //     [':hash' => $tokenHash]
        // );

        return false;
    }

    // -------------------------------------------------------------------------
    // Consultas de escritura
    // -------------------------------------------------------------------------

    /**
     * Persiste un token de refresco hasheado en la base de datos.
     *
     * @param int    $idUsuario     Identificador del usuario propietario.
     * @param string $refrescoHash  Hash SHA-256 del token de refresco.
     * @param string $expiracion    Fecha y hora de expiración (Y-m-d H:i:s).
     * @param string $rol           Rol del usuario en el momento de la emisión.
     * @return int ID del registro creado.
     */
    public function persistirRefresco(int $idUsuario, string $refrescoHash, string $expiracion, string $rol): int
    {
        // ► AQUÍ: implementar
        //
        // return $this->insertar(
        //     "INSERT INTO tokens (id_usuario, hash, tipo, rol, expiracion, revocado)
        //      VALUES (:id_usuario, :hash, 'refresco', :rol, :expiracion, 0)",
        //     [':id_usuario' => $idUsuario, ':hash' => $refrescoHash,
        //      ':rol' => $rol, ':expiracion' => $expiracion]
        // );

        return 0;
    }

    /**
     * Marca un token como revocado sin eliminarlo.
     * Mantiene el historial de revocaciones para auditoría.
     *
     * @param string $tokenHash Hash SHA-256 del token a revocar.
     * @return int Número de filas afectadas.
     */
    public function revocar(string $tokenHash): int
    {
        // ► AQUÍ: implementar
        //
        // return $this->ejecutar(
        //     'UPDATE tokens SET revocado = 1, revocado_en = NOW() WHERE hash = :hash',
        //     [':hash' => $tokenHash]
        // );

        return 0;
    }

    /**
     * Elimina todos los tokens expirados de la base de datos.
     * Se puede llamar desde un proceso de mantenimiento programado (cron).
     *
     * @return int Número de tokens eliminados.
     */
    public function limpiarExpirados(): int
    {
        // ► AQUÍ: implementar
        //
        // return $this->ejecutar(
        //     'DELETE FROM tokens WHERE expiracion < NOW()'
        // );

        return 0;
    }
}