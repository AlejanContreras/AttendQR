<?php

declare(strict_types=1);

/**
 * AttendQR – AsistenciaRepository
 *
 * Responsabilidad: acceder a la tabla `asistencias` para registrar,
 * consultar y eliminar registros de asistencia de los aprendices.
 *
 * NO contiene lógica de negocio. Verificar si la sesión está activa
 * o si el aprendiz pertenece a la ficha corresponde a AsistenciaService.
 *
 * Flujo: AsistenciaService → AsistenciaRepository → BaseRepository → Database → MySQL
 *
 * Ubicación en el proyecto: Src/Repositories/AsistenciaRepository.php
 */
class AsistenciaRepository extends BaseRepository
{
    /**
     * Busca un registro de asistencia por su ID, incluyendo datos del aprendiz y la sesión.
     *
     * @param int $idAsistencia Identificador del registro.
     * @return array<string, mixed>|null Datos del registro o null.
     */
    public function obtenerPorId(int $idAsistencia): ?array
    {
        return $this->consultarUno(
            'SELECT a.id, a.id_aprendiz, a.id_sesion, a.fecha_hora,
                    ap.nombres, ap.apellidos,
                    s.fecha AS fecha_sesion
             FROM asistencias a
             JOIN aprendices ap ON ap.id = a.id_aprendiz
             JOIN sesiones    s  ON s.id  = a.id_sesion
             WHERE a.id = :id',
            [':id' => $idAsistencia]
        );
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
        return $this->existe(
            'SELECT COUNT(*) FROM asistencias
             WHERE id_aprendiz = :id_aprendiz
               AND id_sesion   = :id_sesion',
            [':id_aprendiz' => $idAprendiz, ':id_sesion' => $idSesion]
        );
    }

    /**
     * Retorna el historial de asistencias de un aprendiz con filtros opcionales.
     *
     * @param int         $idAprendiz  Identificador del aprendiz.
     * @param int|null    $idMateria   Filtro por materia.
     * @param string|null $fechaInicio Fecha de inicio del rango (Y-m-d).
     * @param string|null $fechaFin    Fecha de fin del rango (Y-m-d).
     * @return array<int, array<string, mixed>>
     */
    public function historialAprendiz(
        int     $idAprendiz,
        ?int    $idMateria   = null,
        ?string $fechaInicio = null,
        ?string $fechaFin    = null
    ): array {
        $sql    = 'SELECT a.id, a.fecha_hora, s.fecha AS fecha_sesion, s.id_materia
                   FROM asistencias a
                   JOIN sesiones s ON s.id = a.id_sesion
                   WHERE a.id_aprendiz = :id_aprendiz';
        $params = [':id_aprendiz' => $idAprendiz];

        if ($idMateria !== null) {
            $sql              .= ' AND s.id_materia   = :id_materia';
            $params[':id_materia']   = $idMateria;
        }

        if ($fechaInicio !== null) {
            $sql              .= ' AND s.fecha >= :fecha_inicio';
            $params[':fecha_inicio'] = $fechaInicio;
        }

        if ($fechaFin !== null) {
            $sql            .= ' AND s.fecha <= :fecha_fin';
            $params[':fecha_fin'] = $fechaFin;
        }

        $sql .= ' ORDER BY a.fecha_hora DESC';

        return $this->consultar($sql, $params);
    }

    /**
     * Cuenta el total de asistencias registradas hoy.
     *
     * @return int Total de asistencias del día actual.
     */
    public function contarHoy(): int
    {
        return $this->contar(
            "SELECT COUNT(*) FROM asistencias WHERE DATE(fecha_hora) = CURDATE()"
        );
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
        return $this->contar(
            'SELECT COUNT(*) FROM asistencias WHERE id_aprendiz = :id',
            [':id' => $idAprendiz]
        );
    }

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
        return $this->insertar(
            'INSERT INTO asistencias (id_aprendiz, id_sesion, fecha_hora)
             VALUES (:id_aprendiz, :id_sesion, :fecha_hora)',
            [':id_aprendiz' => $idAprendiz, ':id_sesion' => $idSesion, ':fecha_hora' => $fechaHora]
        );
    }

    /**
     * Elimina un registro de asistencia por su ID.
     *
     * @param int $idAsistencia Identificador del registro.
     * @return int Filas afectadas.
     */
    public function eliminar(int $idAsistencia): int
    {
        return $this->ejecutar(
            'DELETE FROM asistencias WHERE id = :id',
            [':id' => $idAsistencia]
        );
    }
}