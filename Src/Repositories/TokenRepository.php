<?php

declare(strict_types=1);

/**
 * AttendQR – TokenRepository
 *
 * Responsabilidad: acceder a la tabla `tokens_qr` para gestionar
 * los tokens de autenticación de sesión.
 *
 * Tabla real del schema: tokens_qr
 * Columnas reales:
 *   id_token, id_sesion, token_valor, creado_en, expira_en,
 *   activo (1 = vigente, NULL = vencido/rotado), veces_usado
 *
 * En este MVP los tokens de autenticación de usuario (login/logout)
 * se manejan en memoria de sesión PHP, ya que el schema no incluye
 * tabla de tokens de autenticación separada.
 * Esta clase gestiona exclusivamente los tokens QR de sesiones.
 *
 * Flujo: QrService → TokenRepository → BaseRepository → Database → MySQL
 *
 * Ubicación en el proyecto: Src/Repositories/TokenRepository.php
 */
class TokenRepository extends BaseRepository
{
    /**
     * Busca un token QR por su valor.
     *
     * @param string $tokenValor Valor del token QR.
     * @return array<string, mixed>|null Datos del token o null si no existe.
     */
    public function buscarPorValor(string $tokenValor): ?array
    {
        return $this->consultarUno(
            'SELECT id_token, id_sesion, token_valor, creado_en,
                    expira_en, activo, veces_usado
             FROM tokens_qr
             WHERE token_valor = :token
             LIMIT 1',
            [':token' => $tokenValor]
        );
    }

    /**
     * Obtiene el token QR activo vigente de una sesión.
     * activo = 1 y expira_en > NOW().
     *
     * @param int $idSesion Identificador de la sesión.
     * @return array<string, mixed>|null Token activo o null.
     */
    public function obtenerActivoPorSesion(int $idSesion): ?array
    {
        return $this->consultarUno(
            'SELECT id_token, token_valor, expira_en, veces_usado
             FROM tokens_qr
             WHERE id_sesion = :id_sesion
               AND activo    = 1
               AND expira_en > NOW()
             LIMIT 1',
            [':id_sesion' => $idSesion]
        );
    }

    /**
     * Verifica si un token QR está activo y vigente.
     *
     * @param string $tokenValor Valor del token QR.
     * @return bool true si está activo y no ha expirado.
     */
    public function estaActivo(string $tokenValor): bool
    {
        return $this->existe(
            'SELECT COUNT(*) FROM tokens_qr
             WHERE token_valor = :token
               AND activo      = 1
               AND expira_en   > NOW()',
            [':token' => $tokenValor]
        );
    }

    /**
     * Inserta un nuevo token QR para una sesión.
     * El UNIQUE(id_sesion, activo) del schema garantiza
     * que solo exista un token activo por sesión a nivel de BD.
     *
     * @param int    $idSesion   Identificador de la sesión.
     * @param string $tokenValor Token generado (hex de 64 caracteres).
     * @param string $expiraEn   Fecha y hora de expiración (Y-m-d H:i:s).
     * @return int ID del token creado.
     */
    public function crear(int $idSesion, string $tokenValor, string $expiraEn): int
    {
        return $this->insertar(
            'INSERT INTO tokens_qr (id_sesion, token_valor, expira_en, activo)
             VALUES (:id_sesion, :token, :expira_en, 1)',
            [':id_sesion' => $idSesion, ':token' => $tokenValor, ':expira_en' => $expiraEn]
        );
    }

    /**
     * Rota (invalida) el token activo de una sesión.
     * Según el schema v1.2: activo = NULL al rotar (no 0).
     * El UNIQUE(id_sesion, activo) permite múltiples NULL.
     *
     * @param int $idSesion Identificador de la sesión.
     * @return int Filas afectadas.
     */
    public function rotarPorSesion(int $idSesion): int
    {
        return $this->ejecutar(
            'UPDATE tokens_qr SET activo = NULL
             WHERE id_sesion = :id_sesion AND activo = 1',
            [':id_sesion' => $idSesion]
        );
    }

    /**
     * Invalida todos los tokens QR de una sesión al cerrarla.
     * Alias semántico de rotarPorSesion() para SesionService.
     *
     * @param int $idSesion Identificador de la sesión cerrada.
     * @return int Filas afectadas.
     */
    public function invalidarPorSesion(int $idSesion): int
    {
        return $this->rotarPorSesion($idSesion);
    }

    /**
     * Incrementa el contador de veces_usado de un token.
     * Solo auditoría — no limita el acceso de ningún aprendiz.
     *
     * @param int $idToken Identificador del token.
     * @return int Filas afectadas.
     */
    public function incrementarUso(int $idToken): int
    {
        return $this->ejecutar(
            'UPDATE tokens_qr SET veces_usado = veces_usado + 1
             WHERE id_token = :id',
            [':id' => $idToken]
        );
    }

    /**
     * Elimina tokens QR expirados de la base de datos.
     * Puede invocarse desde un proceso de mantenimiento (cron).
     *
     * @return int Número de tokens eliminados.
     */
    public function limpiarExpirados(): int
    {
        return $this->ejecutar(
            'DELETE FROM tokens_qr WHERE expira_en < NOW() AND activo IS NULL'
        );
    }
}