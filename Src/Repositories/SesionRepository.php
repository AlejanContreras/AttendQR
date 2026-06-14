<?php

declare(strict_types=1);

/**
 * AttendQR – SesionRepository
 *
 * Responsabilidad: acceder a la tabla `sesiones` para gestionar
 * el ciclo de vida completo de las sesiones de clase.
 *
 * Esta clase NO debe:
 *   - Verificar reglas de negocio (docente activo, duplicados, etc.).
 *   - Conocer Controllers ni Services.
 *   - Generar HTML, JSON ni usar header() o exit.
 *
 * Flujo: SesionService → SesionRepository → Database → MySQL (tabla: sesiones)
 *
 * Ubicación en el proyecto: Src/Repositories/SesionRepository.php
 */
class SesionRepository extends BaseRepository
{
    // -------------------------------------------------------------------------
    // Consultas de lectura
    // -------------------------------------------------------------------------

    /**
     * Busca una sesión por su ID con datos del docente y la materia.
     *
     * @param int $idSesion Identificador único de la sesión.
     * @return array<string, mixed>|null Datos de la sesión o null.
     */
    public function obtenerPorId(int $idSesion): ?array
    {
        // ► AQUÍ: implementar
        //
        // return $this->consultarUno(
        //     'SELECT s.*, d.nombres AS nombre_docente, d.apellidos AS apellido_docente
        //      FROM sesiones s
        //      JOIN docentes d ON d.id = s.id_docente
        //      WHERE s.id = :id',
        //     [':id' => $idSesion]
        // );

        return null;
    }

    /**
     * Busca una sesión con el detalle completo incluyendo conteo de asistencias.
     *
     * @param int $idSesion Identificador único de la sesión.
     * @return array<string, mixed>|null Datos completos o null.
     */
    public function obtenerDetalle(int $idSesion): ?array
    {
        // ► AQUÍ: implementar
        //
        // return $this->consultarUno(
        //     'SELECT s.*,
        //             d.nombres AS nombre_docente,
        //             COUNT(a.id) AS total_asistencias
        //      FROM sesiones s
        //      JOIN docentes   d ON d.id = s.id_docente
        //      LEFT JOIN asistencias a ON a.id_sesion = s.id
        //      WHERE s.id = :id
        //      GROUP BY s.id',
        //     [':id' => $idSesion]
        // );

        return null;
    }

    /**
     * Lista sesiones con filtros opcionales.
     *
     * @param int|null    $idDocente Filtro por docente.
     * @param string|null $fecha     Filtro por fecha (Y-m-d).
     * @param string|null $estado    Filtro por estado ('activa' | 'cerrada').
     * @return array<int, array<string, mixed>>
     */
    public function listar(?int $idDocente = null, ?string $fecha = null, ?string $estado = null): array
    {
        // ► AQUÍ: construir consulta dinámica
        //
        // $sql    = 'SELECT s.id, s.fecha, s.estado, s.hora_apertura, d.nombres AS docente
        //            FROM sesiones s JOIN docentes d ON d.id = s.id_docente WHERE 1=1';
        // $params = [];
        // if ($idDocente !== null) { $sql .= ' AND s.id_docente = :docente'; $params[':docente'] = $idDocente; }
        // if ($fecha     !== null) { $sql .= ' AND s.fecha      = :fecha';   $params[':fecha']   = $fecha; }
        // if ($estado    !== null) { $sql .= ' AND s.estado     = :estado';  $params[':estado']  = $estado; }
        // $sql .= ' ORDER BY s.fecha DESC, s.hora_apertura DESC';
        // return $this->consultar($sql, $params);

        return [];
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
        // ► AQUÍ: implementar
        //
        // return $this->existe(
        //     "SELECT COUNT(*) FROM sesiones
        //      WHERE id_docente = :docente AND fecha = :fecha AND estado = 'activa'",
        //     [':docente' => $idDocente, ':fecha' => $fecha]
        // );

        return false;
    }

    /**
     * Cuenta sesiones activas en este momento.
     * Usado por EstadisticaService::resumen().
     *
     * @return int Total de sesiones activas.
     */
    public function contarActivas(): int
    {
        // ► AQUÍ: implementar
        //
        // return $this->contar("SELECT COUNT(*) FROM sesiones WHERE estado = 'activa'");

        return 0;
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
        // ► AQUÍ: implementar
        //
        // return $this->contar(
        //     "SELECT COUNT(*) FROM sesiones WHERE id_docente = :id AND estado = 'activa'",
        //     [':id' => $idDocente]
        // );

        return 0;
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
        // ► AQUÍ: implementar
        //
        // return $this->contar(
        //     'SELECT COUNT(*) FROM sesiones WHERE id_trimestre = :id',
        //     [':id' => $idTrimestre]
        // );

        return 0;
    }

    // -------------------------------------------------------------------------
    // Consultas de escritura
    // -------------------------------------------------------------------------

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
        // ► AQUÍ: implementar
        //
        // return $this->insertar(
        //     "INSERT INTO sesiones (id_materia, id_docente, fecha, estado, hora_apertura)
        //      VALUES (:id_materia, :id_docente, :fecha, 'activa', NOW())",
        //     [':id_materia' => $idMateria, ':id_docente' => $idDocente, ':fecha' => $fecha]
        // );

        return 0;
    }

    /**
     * Marca una sesión como 'cerrada' y registra la hora de cierre.
     *
     * @param int    $idSesion  Identificador de la sesión.
     * @param string $horaCierre Fecha y hora de cierre (Y-m-d H:i:s).
     * @return int Número de filas afectadas.
     */
    public function cerrar(int $idSesion, string $horaCierre): int
    {
        // ► AQUÍ: implementar
        //
        // return $this->ejecutar(
        //     "UPDATE sesiones SET estado = 'cerrada', hora_cierre = :hora WHERE id = :id",
        //     [':hora' => $horaCierre, ':id' => $idSesion]
        // );

        return 0;
    }
}