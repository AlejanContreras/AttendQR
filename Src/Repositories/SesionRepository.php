<?php

declare(strict_types=1);

/**
 * AttendQR – SesionRepository
 *
 * Responsabilidad: acceder a la tabla `sesiones` para gestionar
 * el ciclo de vida completo de las sesiones de clase.
 *
 * NO verifica reglas de negocio (docente activo, duplicados, etc.).
 * NO contiene lógica de negocio.
 *
 * Flujo: SesionService → SesionRepository → BaseRepository → Database → MySQL
 *
 * Ubicación en el proyecto: Src/Repositories/SesionRepository.php
 */
class SesionRepository extends BaseRepository
{
    /**
     * Busca una sesión por su ID con datos del docente.
     *
     * @param int $idSesion Identificador de la sesión.
     * @return array<string, mixed>|null Datos de la sesión o null.
     */
    public function obtenerPorId(int $idSesion): ?array
    {
        return $this->consultarUno(
            'SELECT s.id, s.id_materia, s.id_docente, s.fecha,
                    s.estado, s.hora_apertura, s.hora_cierre,
                    d.nombres AS nombre_docente,
                    d.apellidos AS apellido_docente
             FROM sesiones s
             JOIN docentes d ON d.id = s.id_docente
             WHERE s.id = :id',
            [':id' => $idSesion]
        );
    }

    /**
     * Busca una sesión con detalle completo incluyendo conteo de asistencias.
     *
     * @param int $idSesion Identificador de la sesión.
     * @return array<string, mixed>|null Datos completos o null.
     */
    public function obtenerDetalle(int $idSesion): ?array
    {
        return $this->consultarUno(
            'SELECT s.id, s.id_materia, s.fecha, s.estado,
                    s.hora_apertura, s.hora_cierre,
                    d.nombres AS nombre_docente,
                    d.apellidos AS apellido_docente,
                    COUNT(a.id) AS total_asistencias
             FROM sesiones s
             JOIN docentes     d ON d.id = s.id_docente
             LEFT JOIN asistencias a ON a.id_sesion = s.id
             WHERE s.id = :id
             GROUP BY s.id',
            [':id' => $idSesion]
        );
    }

    /**
     * Lista sesiones con filtros opcionales de docente, fecha y estado.
     *
     * @param int|null    $idDocente Filtro por docente.
     * @param string|null $fecha     Filtro por fecha (Y-m-d).
     * @param string|null $estado    Filtro por estado ('activa' | 'cerrada').
     * @return array<int, array<string, mixed>>
     */
    public function listar(?int $idDocente = null, ?string $fecha = null, ?string $estado = null): array
    {
        $sql    = 'SELECT s.id, s.fecha, s.estado, s.hora_apertura,
                          d.nombres AS docente
                   FROM sesiones s
                   JOIN docentes d ON d.id = s.id_docente
                   WHERE 1=1';
        $params = [];

        if ($idDocente !== null) {
            $sql .= ' AND s.id_docente = :docente';
            $params[':docente'] = $idDocente;
        }

        if ($fecha !== null) {
            $sql .= ' AND s.fecha      = :fecha';
            $params[':fecha']   = $fecha;
        }

        if ($estado !== null) {
            $sql .= ' AND s.estado     = :estado';
            $params[':estado']  = $estado;
        }

        $sql .= ' ORDER BY s.fecha DESC, s.hora_apertura DESC';

        return $this->consultar($sql, $params);
    }

    /**
     * Verifica si el docente ya tiene una sesión activa en la fecha indicada.
     *
     * @param int    $idDocente Identificador del docente.
     * @param string $fecha     Fecha en formato Y-m-d.
     * @return bool true si ya existe una sesión activa.
     */
    public function existeActivaParaDocente(int $idDocente, string $fecha): bool
    {
        return $this->existe(
            "SELECT COUNT(*) FROM sesiones
             WHERE id_docente = :docente
               AND fecha      = :fecha
               AND estado     = 'activa'",
            [':docente' => $idDocente, ':fecha' => $fecha]
        );
    }

    /**
     * Cuenta sesiones activas en este momento.
     * Usado por EstadisticaService::resumen().
     *
     * @return int Total de sesiones activas.
     */
    public function contarActivas(): int
    {
        return $this->contar(
            "SELECT COUNT(*) FROM sesiones WHERE estado = 'activa'"
        );
    }

    /**
     * Cuenta sesiones activas de un docente.
     * Usado por DocenteService antes de eliminar un docente.
     *
     * @param int $idDocente Identificador del docente.
     * @return int Total de sesiones activas del docente.
     */
    public function contarActivasPorDocente(int $idDocente): int
    {
        return $this->contar(
            "SELECT COUNT(*) FROM sesiones WHERE id_docente = :id AND estado = 'activa'",
            [':id' => $idDocente]
        );
    }

    /**
     * Cuenta sesiones asociadas a un trimestre.
     * Usado por TrimestreService antes de eliminar un trimestre.
     *
     * @param int $idTrimestre Identificador del trimestre.
     * @return int Total de sesiones en el trimestre.
     */
    public function contarPorTrimestre(int $idTrimestre): int
    {
        return $this->contar(
            'SELECT COUNT(*) FROM sesiones WHERE id_trimestre = :id',
            [':id' => $idTrimestre]
        );
    }

    /**
     * Inserta una nueva sesión en estado 'activa'.
     *
     * @param int    $idMateria  Identificador de la materia.
     * @param int    $idDocente  Identificador del docente.
     * @param string $fecha      Fecha de la sesión (Y-m-d).
     * @return int ID de la sesión creada.
     */
    public function crear(int $idMateria, int $idDocente, string $fecha): int
    {
        return $this->insertar(
            "INSERT INTO sesiones (id_materia, id_docente, fecha, estado, hora_apertura)
             VALUES (:id_materia, :id_docente, :fecha, 'activa', NOW())",
            [':id_materia' => $idMateria, ':id_docente' => $idDocente, ':fecha' => $fecha]
        );
    }

    /**
     * Marca una sesión como 'cerrada' y registra la hora de cierre.
     *
     * @param int    $idSesion   Identificador de la sesión.
     * @param string $horaCierre Fecha y hora de cierre (Y-m-d H:i:s).
     * @return int Filas afectadas.
     */
    public function cerrar(int $idSesion, string $horaCierre): int
    {
        return $this->ejecutar(
            "UPDATE sesiones SET estado = 'cerrada', hora_cierre = :hora WHERE id = :id",
            [':hora' => $horaCierre, ':id' => $idSesion]
        );
    }
}