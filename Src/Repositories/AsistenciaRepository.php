<?php

declare(strict_types=1);

/**
 * AttendQR – AsistenciaRepository
 *
 * Responsabilidad: acceder a la tabla `asistencias` para registrar,
 * consultar y eliminar registros de asistencia de los aprendices.
 *
 * NO contiene lógica de negocio. La clasificación (PRESENTE/RETARDO),
 * la validación de token y el cálculo de minutos corresponden a AsistenciaService.
 *
 * Tabla principal: asistencias
 * Columnas: id_asistencia, id_sesion, id_aprendiz, id_token_usado,
 *           estado, metodo_registro, hora_registro, minutos_retardo,
 *           ubicacion_valida, latitud, longitud, observacion, registrado_en
 *
 * Flujo: AsistenciaService → AsistenciaRepository → BaseRepository → Database → MySQL
 *
 * Ubicación en el proyecto: Src/Repositories/AsistenciaRepository.php
 */
class AsistenciaRepository extends BaseRepository
{
    /**
     * Busca un registro de asistencia por su ID con datos del aprendiz y la sesión.
     *
     * @param int $idAsistencia Identificador del registro.
     * @return array<string, mixed>|null Datos del registro o null.
     */
    public function obtenerPorId(int $idAsistencia): ?array
    {
        return $this->consultarUno(
            'SELECT a.id_asistencia, a.id_aprendiz, a.id_sesion, a.id_token_usado,
                    a.estado, a.metodo_registro, a.hora_registro,
                    a.minutos_retardo, a.registrado_en,
                    ap.nombres, ap.apellidos, ap.numero_documento,
                    sa.fecha_sesion, sa.hora_inicio_clase
             FROM asistencias a
             JOIN aprendices          ap ON ap.id_aprendiz = a.id_aprendiz
             JOIN sesiones_asistencia sa ON sa.id_sesion   = a.id_sesion
             WHERE a.id_asistencia = :id',
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
     * Verifica si un aprendiz activo pertenece a la ficha de una sesión.
     * Usado para impedir que aprendices de otras fichas registren asistencia.
     *
     * @param int $idAprendiz Identificador del aprendiz.
     * @param int $idFicha    Identificador de la ficha.
     * @return bool true si el aprendiz está activo y pertenece a la ficha.
     */
    public function aprendizPerteneceAFicha(int $idAprendiz, int $idFicha): bool
    {
        return $this->existe(
            'SELECT COUNT(*) FROM aprendices
             WHERE id_aprendiz = :id_aprendiz
               AND id_ficha    = :id_ficha
               AND activo      = 1',
            [':id_aprendiz' => $idAprendiz, ':id_ficha' => $idFicha]
        );
    }

    /**
     * Retorna el historial de asistencias de un aprendiz con filtros opcionales de fecha.
     *
     * @param int         $idAprendiz  Identificador del aprendiz.
     * @param string|null $fechaInicio Fecha de inicio del rango (Y-m-d).
     * @param string|null $fechaFin    Fecha de fin del rango (Y-m-d).
     * @return array<int, array<string, mixed>>
     */
    public function historialAprendiz(
        int     $idAprendiz,
        ?string $fechaInicio = null,
        ?string $fechaFin    = null
    ): array {
        $sql    = 'SELECT a.id_asistencia, a.estado, a.metodo_registro,
                          a.hora_registro, a.minutos_retardo, a.registrado_en,
                          sa.fecha_sesion, sa.hora_inicio_clase,
                          sa.id_ficha, f.codigo_ficha, f.nombre_programa
                   FROM asistencias a
                   JOIN sesiones_asistencia sa ON sa.id_sesion = a.id_sesion
                   JOIN fichas              f  ON f.id_ficha   = sa.id_ficha
                   WHERE a.id_aprendiz = :id_aprendiz';
        $params = [':id_aprendiz' => $idAprendiz];

        if ($fechaInicio !== null) {
            $sql                    .= ' AND sa.fecha_sesion >= :fecha_inicio';
            $params[':fecha_inicio'] = $fechaInicio;
        }

        if ($fechaFin !== null) {
            $sql                .= ' AND sa.fecha_sesion <= :fecha_fin';
            $params[':fecha_fin'] = $fechaFin;
        }

        $sql .= ' ORDER BY sa.fecha_sesion DESC, a.registrado_en DESC';

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
            'SELECT COUNT(*) FROM asistencias WHERE DATE(registrado_en) = CURDATE()'
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
     * Inserta un nuevo registro de asistencia vía QR.
     *
     * @param int    $idAprendiz      Identificador del aprendiz.
     * @param int    $idSesion        Identificador de la sesión activa.
     * @param int    $idTokenUsado    ID del token QR escaneado.
     * @param string $estado          'presente' o 'retardo'.
     * @param string $horaRegistro    Timestamp exacto de la entrada (Y-m-d H:i:s.000).
     * @param int    $minutosRetardo  Minutos desde hora_inicio_clase. 0 si PRESENTE.
     * @return int ID del registro creado.
     */
    public function crear(
        int    $idAprendiz,
        int    $idSesion,
        int    $idTokenUsado,
        string $estado,
        string $horaRegistro,
        int    $minutosRetardo
    ): int {
        return $this->insertar(
            "INSERT INTO asistencias
                 (id_aprendiz, id_sesion, id_token_usado,
                  estado, metodo_registro, hora_registro, minutos_retardo)
             VALUES
                 (:id_aprendiz, :id_sesion, :id_token_usado,
                  :estado, 'qr', :hora_registro, :minutos_retardo)",
            [
                ':id_aprendiz'   => $idAprendiz,
                ':id_sesion'     => $idSesion,
                ':id_token_usado' => $idTokenUsado,
                ':estado'        => $estado,
                ':hora_registro' => $horaRegistro,
                ':minutos_retardo' => $minutosRetardo,
            ]
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
            'DELETE FROM asistencias WHERE id_asistencia = :id',
            [':id' => $idAsistencia]
        );
    }
}
