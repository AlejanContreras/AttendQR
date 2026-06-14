<?php

declare(strict_types=1);

/**
 * AttendQR – TrimestreRepository
 *
 * Responsabilidad: acceder a la tabla `trimestres` para gestionar
 * los períodos académicos del sistema.
 *
 * Esta clase NO debe:
 *   - Validar coherencia de fechas ni solapamientos (eso lo hace TrimestreService).
 *   - Conocer Controllers ni Services.
 *   - Generar HTML, JSON ni usar header() o exit.
 *
 * Flujo: TrimestreService → TrimestreRepository → Database → MySQL (tabla: trimestres)
 *
 * Ubicación en el proyecto: Src/Repositories/TrimestreRepository.php
 */
class TrimestreRepository extends BaseRepository
{
    // -------------------------------------------------------------------------
    // Consultas de lectura
    // -------------------------------------------------------------------------

    /**
     * Busca un trimestre por su ID.
     *
     * @param int $idTrimestre Identificador único del trimestre.
     * @return array<string, mixed>|null Datos del trimestre o null.
     */
    public function obtenerPorId(int $idTrimestre): ?array
    {
        // ► AQUÍ: implementar
        //
        // return $this->consultarUno(
        //     'SELECT id, nombre, fecha_inicio, fecha_fin, estado FROM trimestres WHERE id = :id',
        //     [':id' => $idTrimestre]
        // );

        return null;
    }

    /**
     * Lista trimestres con filtros opcionales de año y estado.
     *
     * @param int|null    $anio   Filtro por año (p. ej. 2025).
     * @param string|null $estado Filtro por estado ('activo' | 'cerrado').
     * @return array<int, array<string, mixed>>
     */
    public function listar(?int $anio = null, ?string $estado = null): array
    {
        // ► AQUÍ: construir consulta dinámica
        //
        // $sql    = 'SELECT id, nombre, fecha_inicio, fecha_fin, estado FROM trimestres WHERE 1=1';
        // $params = [];
        // if ($anio   !== null) { $sql .= ' AND YEAR(fecha_inicio) = :anio';  $params[':anio']   = $anio; }
        // if ($estado !== null) { $sql .= ' AND estado             = :estado'; $params[':estado'] = $estado; }
        // $sql .= ' ORDER BY fecha_inicio DESC';
        // return $this->consultar($sql, $params);

        return [];
    }

    /**
     * Verifica si ya existe un trimestre con el nombre indicado.
     *
     * @param string   $nombre    Nombre a verificar.
     * @param int|null $excluirId ID a excluir (para actualizaciones).
     * @return bool true si existe.
     */
    public function existeNombre(string $nombre, ?int $excluirId = null): bool
    {
        // ► AQUÍ: implementar
        //
        // $sql    = 'SELECT COUNT(*) FROM trimestres WHERE nombre = :nombre';
        // $params = [':nombre' => $nombre];
        // if ($excluirId !== null) { $sql .= ' AND id != :excluir'; $params[':excluir'] = $excluirId; }
        // return $this->existe($sql, $params);

        return false;
    }

    /**
     * Verifica si las fechas propuestas se solapan con un trimestre existente.
     * Detecta solapamiento parcial o total.
     *
     * @param string   $fechaInicio  Fecha de inicio propuesta (Y-m-d).
     * @param string   $fechaFin     Fecha de fin propuesta (Y-m-d).
     * @param int|null $excluirId    ID a excluir (para actualizaciones).
     * @return bool true si existe solapamiento.
     */
    public function existeSolapamiento(string $fechaInicio, string $fechaFin, ?int $excluirId = null): bool
    {
        // ► AQUÍ: implementar
        //
        // $sql = 'SELECT COUNT(*) FROM trimestres
        //         WHERE NOT (fecha_fin < :inicio OR fecha_inicio > :fin)';
        // $params = [':inicio' => $fechaInicio, ':fin' => $fechaFin];
        // if ($excluirId !== null) { $sql .= ' AND id != :excluir'; $params[':excluir'] = $excluirId; }
        // return $this->existe($sql, $params);

        return false;
    }

    // -------------------------------------------------------------------------
    // Consultas de escritura
    // -------------------------------------------------------------------------

    /**
     * Inserta un nuevo trimestre académico.
     *
     * @param string $nombre       Nombre del trimestre.
     * @param string $fechaInicio  Fecha de inicio (Y-m-d).
     * @param string $fechaFin     Fecha de fin (Y-m-d).
     * @return int ID del trimestre creado.
     */
    public function crear(string $nombre, string $fechaInicio, string $fechaFin): int
    {
        // ► AQUÍ: implementar
        //
        // return $this->insertar(
        //     "INSERT INTO trimestres (nombre, fecha_inicio, fecha_fin, estado)
        //      VALUES (:nombre, :inicio, :fin, 'activo')",
        //     [':nombre' => $nombre, ':inicio' => $fechaInicio, ':fin' => $fechaFin]
        // );

        return 0;
    }

    /**
     * Actualiza los campos enviados de un trimestre existente.
     *
     * @param int                  $idTrimestre Identificador del trimestre.
     * @param array<string, mixed> $datos       Campos a actualizar.
     * @return int Número de filas afectadas.
     */
    public function actualizar(int $idTrimestre, array $datos): int
    {
        // ► AQUÍ: construir SET dinámico
        //
        // $camposPermitidos = ['nombre', 'fecha_inicio', 'fecha_fin', 'estado'];
        // $set = []; $params = [':id' => $idTrimestre];
        // foreach ($camposPermitidos as $campo) {
        //     if (array_key_exists($campo, $datos)) {
        //         $set[] = "{$campo} = :{$campo}";
        //         $params[":{$campo}"] = $datos[$campo];
        //     }
        // }
        // if (empty($set)) { return 0; }
        // return $this->ejecutar("UPDATE trimestres SET " . implode(', ', $set) . " WHERE id = :id", $params);

        return 0;
    }

    /**
     * Elimina un trimestre por su ID.
     *
     * @param int $idTrimestre Identificador del trimestre.
     * @return int Número de filas afectadas.
     */
    public function eliminar(int $idTrimestre): int
    {
        // ► AQUÍ: implementar
        //
        // return $this->ejecutar('DELETE FROM trimestres WHERE id = :id', [':id' => $idTrimestre]);

        return 0;
    }
}