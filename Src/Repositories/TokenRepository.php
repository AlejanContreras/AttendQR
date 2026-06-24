<?php

declare(strict_types=1);

/**
 * AttendQR – TokenRepository
 *
 * Responsabilidad: acceder a la tabla `tokens` para gestionar
 * los tokens de autenticación (acceso y refresco).
 *
 * Los tokens se persisten como hashes SHA-256. Este Repository
 * nunca recibe ni devuelve tokens en texto plano.
 *
 * NO genera tokens (eso lo hace TokenService con bin2hex/random_bytes).
 * NO contiene lógica de autenticación ni de negocio.
 *
 * Flujo: TokenService → TokenRepository → BaseRepository → Database → MySQL
 *
 * Ubicación en el proyecto: Src/Repositories/TokenRepository.php
 */
class TokenRepository extends BaseRepository
{
    /**
     * Busca un token de acceso por su hash SHA-256.
     *
     * @param string $tokenHash Hash del token de acceso.
     * @return array<string, mixed>|null Datos del token o null si no existe.
     */
    public function buscarAcceso(string $tokenHash): ?array
    {
        return $this->consultarUno(
            "SELECT id, id_usuario, rol, expiracion, revocado
             FROM tokens
             WHERE hash = :hash AND tipo = 'acceso'
             LIMIT 1",
            [':hash' => $tokenHash]
        );
    }

    /**
     * Busca un token de refresco por su hash SHA-256.
     *
     * @param string $tokenHash Hash del token de refresco.
     * @return array<string, mixed>|null Datos del token o null si no existe.
     */
    public function buscarRefresco(string $tokenHash): ?array
    {
        return $this->consultarUno(
            "SELECT id, id_usuario, rol, expiracion, revocado
             FROM tokens
             WHERE hash = :hash AND tipo = 'refresco'
             LIMIT 1",
            [':hash' => $tokenHash]
        );
    }

    /**
     * Verifica si un token está marcado como revocado.
     *
     * @param string $tokenHash Hash SHA-256 del token.
     * @return bool true si el token fue revocado.
     */
    public function estaRevocado(string $tokenHash): bool
    {
        return $this->existe(
            'SELECT COUNT(*) FROM tokens WHERE hash = :hash AND revocado = 1',
            [':hash' => $tokenHash]
        );
    }

    /**
     * Persiste un token de refresco hasheado en la base de datos.
     *
     * @param int    $idUsuario    Identificador del usuario propietario.
     * @param string $refrescoHash Hash SHA-256 del token de refresco.
     * @param string $expiracion   Fecha y hora de expiración (Y-m-d H:i:s).
     * @param string $rol          Rol del usuario en el momento de la emisión.
     * @return int ID del registro creado.
     */
    public function persistirRefresco(int $idUsuario, string $refrescoHash, string $expiracion, string $rol): int
    {
        return $this->insertar(
            "INSERT INTO tokens (id_usuario, hash, tipo, rol, expiracion, revocado)
             VALUES (:id_usuario, :hash, 'refresco', :rol, :expiracion, 0)",
            [
                ':id_usuario' => $idUsuario,
                ':hash'       => $refrescoHash,
                ':rol'        => $rol,
                ':expiracion' => $expiracion,
            ]
        );
    }

    /**
     * Marca un token como revocado sin eliminarlo.
     * Mantiene el historial de revocaciones para auditoría.
     *
     * @param string $tokenHash Hash SHA-256 del token a revocar.
     * @return int Filas afectadas.
     */
    public function revocar(string $tokenHash): int
    {
        return $this->ejecutar(
            'UPDATE tokens SET revocado = 1, revocado_en = NOW() WHERE hash = :hash',
            [':hash' => $tokenHash]
        );
    }

    /**
     * Elimina todos los tokens expirados de la base de datos.
     * Puede invocarse desde un proceso de mantenimiento programado (cron).
     *
     * @return int Número de tokens eliminados.
     */
    public function limpiarExpirados(): int
    {
        return $this->ejecutar(
            'DELETE FROM tokens WHERE expiracion < NOW()'
        );
    }
}