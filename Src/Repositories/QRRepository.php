<?php

declare(strict_types=1);

/**
 * AttendQR – QrRepository
 *
 * Responsabilidad: acceder a la tabla `qr_tokens` para todas las
 * operaciones sobre los tokens QR vinculados a sesiones activas.
 *
 * Esta clase NO debe:
 *   - Generar tokens (eso lo hace QrService).
 *   - Verificar si la sesión está activa.
 *   - Conocer Controllers ni Services.
 *   - Generar HTML, JSON ni usar header() o exit.
 *
 * Flujo: QrService → QrRepository → Database → MySQL (tabla: qr_tokens)
 *
 * Ubicación en el proyecto: Src/Repositories/QrRepository.php
 */
class QrRepository extends BaseRepository
{
    // -------------------------------------------------------------------------
    // Consultas de lectura
    // -------------------------------------------------------------------------

    /**
     * Busca un token QR por su valor exacto.
     * Retorna el token con la información de la sesión vinculada.
     *
     * @param string $token Valor del token QR.
     * @return array<string, mixed>|null Datos del token o null si no existe.
     */
    public function buscarToken(string $token): ?array
    {
        // ► AQUÍ: implementar
        //
        // return $this->consultarUno(
        //     'SELECT qt.id, qt.token, qt.id_sesion, qt.expiracion, qt.activo,
        //             s.estado AS estado_sesion
        //      FROM qr_tokens qt
        //      JOIN sesiones  s ON s.id = qt.id_sesion
        //      WHERE qt.token = :token
        //      LIMIT 1',
        //     [':token' => $token]
        // );

        return null;
    }

    /**
     * Busca el token QR activo y vigente de una sesión.
     * Retorna null si no existe o si todos los tokens están expirados.
     *
     * @param int $idSesion Identificador de la sesión.
     * @return array<string, mixed>|null Token activo o null.
     */
    public function obtenerActivoPorSesion(int $idSesion): ?array
    {
        // ► AQUÍ: implementar
        //
        // return $this->consultarUno(
        //     'SELECT id, token, expiracion
        //      FROM qr_tokens
        //      WHERE id_sesion = :id_sesion
        //        AND activo    = 1
        //        AND expiracion > NOW()
        //      ORDER BY id DESC
        //      LIMIT 1',
        //     [':id_sesion' => $idSesion]
        // );

        return null;
    }

    // -------------------------------------------------------------------------
    // Consultas de escritura
    // -------------------------------------------------------------------------

    /**
     * Inserta un nuevo token QR para una sesión.
     *
     * @param int    $idSesion   Identificador de la sesión.
     * @param string $token      Token generado (hex de 64 caracteres).
     * @param string $expiracion Fecha y hora de expiración (Y-m-d H:i:s).
     * @return int ID del token creado.
     */
    public function crear(int $idSesion, string $token, string $expiracion): int
    {
        // ► AQUÍ: implementar
        //
        // return $this->insertar(
        //     'INSERT INTO qr_tokens (id_sesion, token, expiracion, activo)
        //      VALUES (:id_sesion, :token, :expiracion, 1)',
        //     [':id_sesion' => $idSesion, ':token' => $token, ':expiracion' => $expiracion]
        // );

        return 0;
    }

    /**
     * Invalida todos los tokens QR activos de una sesión.
     * Se llama antes de generar uno nuevo o al cerrar la sesión.
     *
     * @param int $idSesion Identificador de la sesión.
     * @return int Número de tokens desactivados.
     */
    public function invalidarPrevios(int $idSesion): int
    {
        // ► AQUÍ: implementar
        //
        // return $this->ejecutar(
        //     'UPDATE qr_tokens SET activo = 0 WHERE id_sesion = :id_sesion AND activo = 1',
        //     [':id_sesion' => $idSesion]
        // );

        return 0;
    }

    /**
     * Invalida todos los tokens QR activos de una sesión al cerrarla.
     * Alias semántico de invalidarPrevios() para mayor claridad en SesionService.
     *
     * @param int $idSesion Identificador de la sesión cerrada.
     * @return int Número de tokens desactivados.
     */
    public function invalidarPorSesion(int $idSesion): int
    {
        return $this->invalidarPrevios($idSesion);
    }
}