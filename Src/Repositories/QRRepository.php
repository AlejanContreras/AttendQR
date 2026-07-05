<?php

declare(strict_types=1);

/**
 * AttendQR – QrRepository
 *
 * Responsabilidad: acceder a la tabla `tokens_qr` para gestionar
 * los tokens QR vinculados a sesiones de asistencia.
 *
 * Tabla real del schema: tokens_qr
 * Columnas: id_token, id_sesion, token_valor, creado_en,
 *           expira_en, activo (1=vigente / NULL=rotado), veces_usado
 *
 * Regla schema v1.2:
 *   Al rotar: SET activo = NULL  (NO = 0)
 *   UNIQUE(id_sesion, activo) → BD garantiza un solo token
 *   activo=1 por sesión. MySQL permite múltiples NULL en UNIQUE.
 *
 * Flujo: QrService → QrRepository → BaseRepository → Database → MySQL
 *
 * Ubicación en el proyecto: Src/Repositories/QrRepository.php
 */
class QrRepository extends BaseRepository
{
    /**
     * Busca un token QR por su valor, incluyendo el estado de la sesión.
     *
     * @param string $tokenValor Valor del token QR.
     * @return array<string, mixed>|null Datos del token o null si no existe.
     */
    public function buscarToken(string $tokenValor): ?array
    {
        return $this->consultarUno(
            'SELECT tq.id_token, tq.id_sesion, tq.token_valor,
                    tq.expira_en, tq.activo, tq.veces_usado,
                    sa.estado_sesion
             FROM tokens_qr tq
             JOIN sesiones_asistencia sa ON sa.id_sesion = tq.id_sesion
             WHERE tq.token_valor = :token
               AND tq.activo = 1
             LIMIT 1',
            [':token' => $tokenValor]
        );
    }

    /**
     * Obtiene el token QR activo y vigente de una sesión.
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
     * Inserta un nuevo token QR activo para una sesión.
     * Antes de llamar a este método se debe rotar el token previo
     * para que el UNIQUE(id_sesion, activo) del schema no rechace el INSERT.
     *
     * @param int    $idSesion   Identificador de la sesión.
     * @param string $tokenValor Token generado (hex de 64 caracteres).
     * @param string $expiraEn   Fecha y hora de expiración (Y-m-d H:i:s.000).
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
     * Según schema v1.2: activo = NULL al rotar (no 0).
     * Esto permite al UNIQUE(id_sesion, activo) aceptar el siguiente INSERT.
     *
     * @param int $idSesion Identificador de la sesión.
     * @return int Filas afectadas.
     */
    public function invalidarPrevios(int $idSesion): int
    {
        return $this->ejecutar(
            'UPDATE tokens_qr SET activo = NULL
             WHERE id_sesion = :id_sesion AND activo = 1',
            [':id_sesion' => $idSesion]
        );
    }

    /**
     * Invalida todos los tokens QR de una sesión al cerrarla.
     * Alias semántico de invalidarPrevios() para SesionService.
     *
     * @param int $idSesion Identificador de la sesión cerrada.
     * @return int Filas afectadas.
     */
    public function invalidarPorSesion(int $idSesion): int
    {
        return $this->invalidarPrevios($idSesion);
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
     * Elimina tokens QR ya rotados y expirados (limpieza de mantenimiento).
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