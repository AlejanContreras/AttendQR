<?php

declare(strict_types=1);

/**
 * AttendQR – TrimestreRepository
 *
 * Tabla: trimestres
 * Columnas reales: id_trimestre, nombre, fecha_inicio, fecha_fin,
 *                  activo (TINYINT 1=activo)
 *
 * NOTA: la tabla NO tiene columna 'anio'. El año puede derivarse de fecha_inicio.
 *
 * NO contiene lógica de negocio.
 * Flujo: TrimestreService → TrimestreRepository → BaseRepository → Database → MySQL
 *
 * Ubicación en el proyecto: Src/Repositories/TrimestreRepository.php
 */
class TrimestreRepository extends BaseRepository
{
    /**
     * Busca un trimestre por su ID.
     *
     * @param int $idTrimestre Identificador del trimestre.
     * @return array<string, mixed>|null Datos del trimestre o null.
     */
    public function obtenerPorId(int $idTrimestre): ?array
    {
        return $this->consultarUno(
            'SELECT id_trimestre, nombre, fecha_inicio, fecha_fin, activo
             FROM trimestres
             WHERE id_trimestre = :id',
            [':id' => $idTrimestre]
        );
    }

    /**
     * Lista trimestres con filtros opcionales de año y estado.
     * El año se filtra comparando contra YEAR(fecha_inicio) dado que la columna
     * anio no existe en el schema.
     *
     * @param int|null $anio   Filtro por año (p. ej. 2025).
     * @param int|null $activo Filtro por estado (1 = activo, 0 = cerrado).
     * @return array<int, array<string, mixed>>
     */
    public function listar(?int $anio = null, ?int $activo = null): array
    {
        $sql    = 'SELECT id_trimestre, nombre, fecha_inicio, fecha_fin, activo
                   FROM trimestres
                   WHERE 1=1';
        $params = [];

        if ($anio !== null) {
            $sql .= ' AND YEAR(fecha_inicio) = :anio';
            $params[':anio'] = $anio;
        }

        if ($activo !== null) {
            $sql .= ' AND activo = :activo';
            $params[':activo'] = $activo;
        }

        $sql .= ' ORDER BY fecha_inicio DESC';

        return $this->consultar($sql, $params);
    }

    /**
     * Verifica si ya existe un trimestre con el nombre indicado.
     *
     * @param string   $nombre    Nombre del trimestre.
     * @param int|null $excluirId ID a excluir (para actualizaciones).
     * @return bool true si existe.
     */
    public function existeNombre(string $nombre, ?int $excluirId = null): bool
    {
        $sql    = 'SELECT COUNT(*) FROM trimestres WHERE nombre = :nombre';
        $params = [':nombre' => $nombre];

        if ($excluirId !== null) {
            $sql              .= ' AND id_trimestre != :excluir';
            $params[':excluir'] = $excluirId;
        }

        return $this->existe($sql, $params);
    }

    /**
     * Verifica si las fechas indicadas se solapan con algún trimestre existente.
     *
     * @param string   $fechaInicio Fecha inicio (Y-m-d).
     * @param string   $fechaFin    Fecha fin (Y-m-d).
     * @param int|null $excluirId   ID a excluir (para actualizaciones).
     * @return bool true si hay solapamiento.
     */
    public function existeSolapamiento(string $fechaInicio, string $fechaFin, ?int $excluirId = null): bool
    {
        $sql    = 'SELECT COUNT(*) FROM trimestres
                   WHERE fecha_inicio <= :fin AND fecha_fin >= :inicio';
        $params = [':inicio' => $fechaInicio, ':fin' => $fechaFin];

        if ($excluirId !== null) {
            $sql              .= ' AND id_trimestre != :excluir';
            $params[':excluir'] = $excluirId;
        }

        return $this->existe($sql, $params);
    }

    /**
     * Inserta un nuevo trimestre.
     *
     * @param string $nombre      Nombre del trimestre.
     * @param string $fechaInicio Fecha de inicio (Y-m-d).
     * @param string $fechaFin    Fecha de fin (Y-m-d).
     * @return int ID del trimestre creado.
     */
    public function crear(string $nombre, string $fechaInicio, string $fechaFin): int
    {
        return $this->insertar(
            'INSERT INTO trimestres (nombre, fecha_inicio, fecha_fin, activo)
             VALUES (:nombre, :inicio, :fin, 1)',
            [
                ':nombre' => $nombre,
                ':inicio' => $fechaInicio,
                ':fin'    => $fechaFin,
            ]
        );
    }

    /**
     * Actualiza únicamente los campos enviados de un trimestre existente.
     *
     * @param int                  $idTrimestre Identificador del trimestre.
     * @param array<string, mixed> $datos       Campos a actualizar.
     * @return int Filas afectadas.
     */
    public function actualizar(int $idTrimestre, array $datos): int
    {
        $camposPermitidos = ['nombre', 'fecha_inicio', 'fecha_fin', 'activo'];
        $set    = [];
        $params = [':id' => $idTrimestre];

        foreach ($camposPermitidos as $campo) {
            if (array_key_exists($campo, $datos)) {
                $set[]              = "{$campo} = :{$campo}";
                $params[":{$campo}"] = $datos[$campo];
            }
        }

        if (empty($set)) {
            return 0;
        }

        return $this->ejecutar(
            'UPDATE trimestres SET ' . implode(', ', $set) . ' WHERE id_trimestre = :id',
            $params
        );
    }

    /**
     * Elimina un trimestre por su ID.
     *
     * @param int $idTrimestre Identificador del trimestre.
     * @return int Filas afectadas.
     */
    public function eliminar(int $idTrimestre): int
    {
        return $this->ejecutar(
            'DELETE FROM trimestres WHERE id_trimestre = :id',
            [':id' => $idTrimestre]
        );
    }
}
