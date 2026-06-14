<?php

declare(strict_types=1);

/**
 * AttendQR – AsistenciaRepository
 *
 * Responsabilidad: acceder a la tabla `asistencias` para registrar,
 * consultar y eliminar los registros de asistencia de los aprendices.
 *
 * Esta clase NO debe:
 *   - Contener lógica de negocio (p. ej. calcular porcentajes).
 *   - Verificar si la sesión está activa (eso lo hace AsistenciaService).
 *   - Conocer Controllers ni Services.
 *   - Generar HTML, JSON ni usar header() o exit.
 *
 * Flujo: AsistenciaService → AsistenciaRepository → Database → MySQL (tabla: asistencias)
 *
 * Ubicación en el proyecto: Src/Repositories/AsistenciaRepository.php
 */
class AsistenciaRepository extends BaseRepository
{
    // -------------------------------------------------------------------------
    // Consultas de lectura
    // -------------------------------------------------------------------------

    /**
     * Busca un registro de asistencia por su ID.
     *
     * @param int $idAsistencia Identificador único del registro.
     * @return array<string, mixed>|null Datos del registro o null si no existe.
     */
    public function obtenerPorId(int $idAsistencia): ?array
    {
        // ► AQUÍ: implementar
        //
        // return $this->consultarUno(
        //     'SELECT a.id, a.id_aprendiz, a.id_sesion, a.fecha_hora,
        //             ap.nombres, ap.apellidos, s.fecha AS fecha_sesion
        //      FROM asistencias a
        //      JOIN aprendices ap ON ap.id = a.id_aprendiz
        //      JOIN sesiones   s  ON s.id  = a.id_sesion
        //      WHERE a.id = :id',
        //     [':id' => $idAsistencia]
        // );

        return null;
    }

    /**
     * Verifica si un aprendiz ya registró asistencia en una sesión específica.
     *
     * @param int $idAprendiz Identificador del aprendiz.
     * @param int $idSesion   Identificador de la sesión.
     * @return bool true si el registro ya existe.
     */
    public function existeEnSesion(int $idAprendiz, int $idSesion): bool
    {
        // ► AQUÍ: implementar
        //
        // return $this->existe(
        //     'SELECT COUNT(*) FROM asistencias
        //      WHERE id_aprendiz = :id_aprendiz AND id_sesion = :id_sesion',
        //     [':id_aprendiz' => $idAprendiz, ':id_sesion' => $idSesion]
        // );

        return false;
    }

    /**
     * Retorna el historial de asistencias de un aprendiz con filtros opcionales.
     *
     * @param int         $idAprendiz  Identificador del aprendiz.
     * @param int|null    $idMateria   Filtro opcional por materia.
     * @param string|null $fechaInicio Filtro opcional de fecha de inicio (Y-m-d).
     * @param string|null $fechaFin    Filtro opcional de fecha de fin (Y-m-d).
     * @return array<int, array<string, mixed>> Listado de registros de asistencia.
     */
    public function historialAprendiz(
        int     $idAprendiz,
        ?int    $idMateria   = null,
        ?string $fechaInicio = null,
        ?string $fechaFin    = null
    ): array {
        // ► AQUÍ: construir la consulta dinámicamente según los filtros presentes
        //
        // $sql    = 'SELECT ... FROM asistencias a JOIN sesiones s ON ...
        //            WHERE a.id_aprendiz = :id_aprendiz';
        // $params = [':id_aprendiz' => $idAprendiz];
        //
        // if ($idMateria !== null) {
        //     $sql   .= ' AND s.id_materia = :id_materia';
        //     $params[':id_materia'] = $idMateria;
        // }
        // if ($fechaInicio !== null) {
        //     $sql   .= ' AND s.fecha >= :fecha_inicio';
        //     $params[':fecha_inicio'] = $fechaInicio;
        // }
        // if ($fechaFin !== null) {
        //     $sql   .= ' AND s.fecha <= :fecha_fin';
        //     $params[':fecha_fin'] = $fechaFin;
        // }
        // $sql .= ' ORDER BY a.fecha_hora DESC';
        //
        // return $this->consultar($sql, $params);

        return [];
    }

    /**
     * Cuenta el total de asistencias registradas hoy.
     *
     * @return int Total de asistencias del día actual.
     */
    public function contarHoy(): int
    {
        // ► AQUÍ: implementar
        //
        // return $this->contar(
        //     "SELECT COUNT(*) FROM asistencias WHERE DATE(fecha_hora) = CURDATE()"
        // );

        return 0;
    }

    /**
     * Cuenta el total de asistencias de un aprendiz.
     * Usado por AprendizService antes de eliminar un aprendiz.
     *
     * @param int $idAprendiz Identificador del aprendiz.
     * @return int Total de registros.
     */
    public function contarPorAprendiz(int $idAprendiz): int
    {
        // ► AQUÍ: implementar
        //
        // return $this->contar(
        //     'SELECT COUNT(*) FROM asistencias WHERE id_aprendiz = :id',
        //     [':id' => $idAprendiz]
        // );

        return 0;
    }

    // -------------------------------------------------------------------------
    // Consultas de escritura
    // -------------------------------------------------------------------------

    /**
     * Inserta un nuevo registro de asistencia.
     *
     * @param int    $idAprendiz Identificador del aprendiz.
     * @param int    $idSesion   Identificador de la sesión activa.
     * @param string $fechaHora  Fecha y hora del registro (Y-m-d H:i:s).
     * @return int ID del registro creado.
     */
    public function crear(int $idAprendiz, int $idSesion, string $fechaHora): int
    {
        // ► AQUÍ: implementar
        //
        // return $this->insertar(
        //     'INSERT INTO asistencias (id_aprendiz, id_sesion, fecha_hora)
        //      VALUES (:id_aprendiz, :id_sesion, :fecha_hora)',
        //     [':id_aprendiz' => $idAprendiz, ':id_sesion' => $idSesion, ':fecha_hora' => $fechaHora]
        // );

        return 0;
    }

    /**
     * Elimina un registro de asistencia por su ID.
     *
     * @param int $idAsistencia Identificador del registro a eliminar.
     * @return int Número de filas afectadas.
     */
    public function eliminar(int $idAsistencia): int
    {
        // ► AQUÍ: implementar
        //
        // return $this->ejecutar(
        //     'DELETE FROM asistencias WHERE id = :id',
        //     [':id' => $idAsistencia]
        // );

        return 0;
    }
}