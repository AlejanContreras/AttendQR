<?php

declare(strict_types=1);

/**
 * AttendQR – SesionRepository
 *
 * Responsabilidad: acceder a la tabla `sesiones_asistencia` y a las tablas
 * relacionadas (fichas, jornadas, docentes) para el ciclo de vida de sesiones.
 *
 * No contiene lógica de negocio.
 *
 * Flujo: SesionService → SesionRepository → BaseRepository → Database → MySQL
 *
 * Tabla principal: sesiones_asistencia
 * Ubicación en el proyecto: Src/Repositories/SesionRepository.php
 */
class SesionRepository extends BaseRepository
{
    /**
     * Obtiene una ficha con los datos de su jornada.
     * Usado por SesionService para copiar hora_inicio_clase y minutos_gracia.
     *
     * @param int $idFicha Identificador de la ficha.
     * @return array<string, mixed>|null Ficha con jornada o null.
     */
    public function obtenerFichaConJornada(int $idFicha): ?array
    {
        return $this->consultarUno(
            'SELECT f.id_ficha, f.codigo_ficha, f.nombre_programa,
                    f.id_docente, f.activa,
                    j.id_jornada, j.nombre AS nombre_jornada,
                    j.hora_inicio, j.hora_fin, j.minutos_gracia
             FROM fichas f
             JOIN jornadas j ON j.id_jornada = f.id_jornada
             WHERE f.id_ficha = :id',
            [':id' => $idFicha]
        );
    }

    /**
     * Verifica si ya existe una sesión abierta para la ficha en la fecha indicada.
     *
     * @param int    $idFicha Identificador de la ficha.
     * @param string $fecha   Fecha en formato Y-m-d.
     * @return bool true si ya existe una sesión abierta.
     */
    public function existeAbiertaParaFicha(int $idFicha, string $fecha): bool
    {
        return $this->existe(
            "SELECT COUNT(*) FROM sesiones_asistencia
             WHERE id_ficha      = :id_ficha
               AND fecha_sesion  = :fecha
               AND estado_sesion = 'abierta'",
            [':id_ficha' => $idFicha, ':fecha' => $fecha]
        );
    }

    /**
     * Obtiene una sesión por su ID con datos de la ficha y el docente.
     *
     * @param int $idSesion Identificador de la sesión.
     * @return array<string, mixed>|null Datos de la sesión o null.
     */
    public function obtenerPorId(int $idSesion): ?array
    {
        return $this->consultarUno(
            'SELECT sa.id_sesion, sa.id_ficha, sa.fecha_sesion,
                    sa.estado_sesion, sa.hora_apertura, sa.hora_inicio_clase,
                    sa.hora_cierre, sa.limite_retardo_minutos,
                    sa.duracion_maxima_minutos, sa.rotacion_qr_segundos,
                    f.codigo_ficha, f.nombre_programa, f.id_docente,
                    d.nombres AS nombre_docente, d.apellidos AS apellido_docente
             FROM sesiones_asistencia sa
             JOIN fichas   f ON f.id_ficha   = sa.id_ficha
             JOIN docentes d ON d.id_docente = f.id_docente
             WHERE sa.id_sesion = :id',
            [':id' => $idSesion]
        );
    }

    /**
     * Obtiene una sesión con detalle completo incluyendo conteo de asistencias.
     *
     * @param int $idSesion Identificador de la sesión.
     * @return array<string, mixed>|null Datos completos o null.
     */
    public function obtenerDetalle(int $idSesion): ?array
    {
        return $this->consultarUno(
            'SELECT sa.id_sesion, sa.id_ficha, sa.fecha_sesion,
                    sa.estado_sesion, sa.hora_apertura, sa.hora_inicio_clase,
                    sa.hora_cierre, sa.limite_retardo_minutos,
                    sa.duracion_maxima_minutos, sa.rotacion_qr_segundos,
                    f.codigo_ficha, f.nombre_programa, f.id_docente,
                    d.nombres AS nombre_docente, d.apellidos AS apellido_docente,
                    COUNT(a.id_asistencia) AS total_asistencias
             FROM sesiones_asistencia sa
             JOIN fichas   f ON f.id_ficha   = sa.id_ficha
             JOIN docentes d ON d.id_docente = f.id_docente
             LEFT JOIN asistencias a ON a.id_sesion = sa.id_sesion
             WHERE sa.id_sesion = :id
             GROUP BY sa.id_sesion',
            [':id' => $idSesion]
        );
    }

    /**
     * Obtiene la sesión actualmente abierta para una ficha.
     *
     * @param int $idFicha Identificador de la ficha.
     * @return array<string, mixed>|null Sesión activa o null.
     */
    public function obtenerActivaPorFicha(int $idFicha): ?array
    {
        return $this->consultarUno(
            "SELECT sa.id_sesion, sa.id_ficha, sa.fecha_sesion,
                    sa.estado_sesion, sa.hora_apertura, sa.hora_inicio_clase,
                    sa.hora_cierre, sa.limite_retardo_minutos,
                    sa.duracion_maxima_minutos, sa.rotacion_qr_segundos,
                    f.codigo_ficha, f.nombre_programa
             FROM sesiones_asistencia sa
             JOIN fichas f ON f.id_ficha = sa.id_ficha
             WHERE sa.id_ficha      = :id_ficha
               AND sa.estado_sesion = 'abierta'
             LIMIT 1",
            [':id_ficha' => $idFicha]
        );
    }

    /**
     * Lista sesiones con filtros opcionales de ficha y estado.
     *
     * @param int|null    $idFicha Filtro por ficha.
     * @param string|null $estado  Filtro por estado_sesion.
     * @return array<int, array<string, mixed>>
     */
    public function listar(?int $idFicha = null, ?string $estado = null): array
    {
        $sql    = 'SELECT sa.id_sesion, sa.id_ficha, sa.fecha_sesion,
                          sa.estado_sesion, sa.hora_apertura, sa.hora_cierre,
                          f.codigo_ficha, f.nombre_programa
                   FROM sesiones_asistencia sa
                   JOIN fichas f ON f.id_ficha = sa.id_ficha
                   WHERE 1=1';
        $params = [];

        if ($idFicha !== null) {
            $sql .= ' AND sa.id_ficha = :id_ficha';
            $params[':id_ficha'] = $idFicha;
        }

        if ($estado !== null) {
            $sql .= ' AND sa.estado_sesion = :estado';
            $params[':estado'] = $estado;
        }

        $sql .= ' ORDER BY sa.fecha_sesion DESC, sa.hora_apertura DESC';

        return $this->consultar($sql, $params);
    }

    /**
     * Inserta una nueva sesión en estado 'abierta'.
     *
     * @param int    $idFicha               Identificador de la ficha.
     * @param string $fechaSesion           Fecha de la sesión (Y-m-d).
     * @param string $horaInicioClase       Hora oficial de inicio (HH:MM:SS).
     * @param int    $limiteRetardoMinutos  Copiado de jornadas.minutos_gracia.
     * @param int    $duracionMaximaMinutos Duración máxima en minutos.
     * @return int ID de la sesión creada.
     */
    public function crear(
        int    $idFicha,
        string $fechaSesion,
        string $horaInicioClase,
        int    $limiteRetardoMinutos,
        int    $duracionMaximaMinutos
    ): int {
        return $this->insertar(
            "INSERT INTO sesiones_asistencia
                 (id_ficha, fecha_sesion, estado_sesion, hora_apertura,
                  hora_inicio_clase, limite_retardo_minutos, duracion_maxima_minutos)
             VALUES (:id_ficha, :fecha, 'abierta', NOW(3),
                     :hora_inicio, :limite_retardo, :duracion_maxima)",
            [
                ':id_ficha'        => $idFicha,
                ':fecha'           => $fechaSesion,
                ':hora_inicio'     => $horaInicioClase,
                ':limite_retardo'  => $limiteRetardoMinutos,
                ':duracion_maxima' => $duracionMaximaMinutos,
            ]
        );
    }

    /**
     * Marca una sesión como 'cerrada' y registra la hora de cierre.
     *
     * @param int    $idSesion   Identificador de la sesión.
     * @param string $horaCierre Timestamp de cierre (Y-m-d H:i:s).
     * @return int Filas afectadas.
     */
    public function cerrar(int $idSesion, string $horaCierre): int
    {
        return $this->ejecutar(
            "UPDATE sesiones_asistencia
             SET estado_sesion = 'cerrada', hora_cierre = :hora_cierre
             WHERE id_sesion = :id",
            [':hora_cierre' => $horaCierre, ':id' => $idSesion]
        );
    }

    /**
     * Cuenta sesiones abiertas en este momento.
     * Usado por EstadisticaService.
     *
     * @return int Total de sesiones abiertas.
     */
    public function contarActivas(): int
    {
        return $this->contar(
            "SELECT COUNT(*) FROM sesiones_asistencia WHERE estado_sesion = 'abierta'"
        );
    }
}
