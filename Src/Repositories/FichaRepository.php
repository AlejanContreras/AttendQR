<?php

declare(strict_types=1);

/**
 * AttendQR – FichaRepository
 *
 * Responsabilidad: acceder a la tabla `fichas` para todas las
 * operaciones CRUD y consultas de soporte a otros módulos.
 *
 * Esta clase NO debe:
 *   - Validar reglas de negocio.
 *   - Conocer Controllers ni Services.
 *   - Generar HTML, JSON ni usar header() o exit.
 *
 * Flujo: FichaService → FichaRepository → Database → MySQL (tabla: fichas)
 *
 * Ubicación en el proyecto: Src/Repositories/FichaRepository.php
 */
class FichaRepository extends BaseRepository
{
    // -------------------------------------------------------------------------
    // Consultas de lectura
    // -------------------------------------------------------------------------

    /**
     * Busca una ficha por su ID, enriquecida con datos del programa y jornada.
     *
     * @param int $idFicha Identificador único de la ficha.
     * @return array<string, mixed>|null Datos de la ficha o null.
     */
    public function obtenerPorId(int $idFicha): ?array
    {
        // ► AQUÍ: implementar
        //
        // return $this->consultarUno(
        //     'SELECT f.*, j.nombre AS nombre_jornada
        //      FROM fichas f
        //      LEFT JOIN jornadas j ON j.id = f.id_jornada
        //      WHERE f.id = :id',
        //     [':id' => $idFicha]
        // );

        return null;
    }

    /**
     * Lista fichas con filtros opcionales.
     *
     * @param int|null    $idPrograma Filtro por programa.
     * @param string|null $estado     Filtro por estado.
     * @param int|null    $idJornada  Filtro por jornada.
     * @return array<int, array<string, mixed>>
     */
    public function listar(?int $idPrograma = null, ?string $estado = null, ?int $idJornada = null): array
    {
        // ► AQUÍ: construir consulta dinámica
        //
        // $sql = 'SELECT f.id, f.numero_ficha, f.estado, j.nombre AS jornada
        //         FROM fichas f LEFT JOIN jornadas j ON j.id = f.id_jornada WHERE 1=1';
        // $params = [];
        // if ($idPrograma !== null) { $sql .= ' AND f.id_programa = :prog';    $params[':prog']    = $idPrograma; }
        // if ($estado     !== null) { $sql .= ' AND f.estado      = :estado';  $params[':estado']  = $estado; }
        // if ($idJornada  !== null) { $sql .= ' AND f.id_jornada  = :jornada'; $params[':jornada'] = $idJornada; }
        // $sql .= ' ORDER BY f.numero_ficha';
        // return $this->consultar($sql, $params);

        return [];
    }

    /**
     * Verifica si ya existe una ficha con el número indicado.
     *
     * @param string   $numeroFicha Número de ficha.
     * @param int|null $excluirId   ID a excluir (para actualizaciones).
     * @return bool true si existe.
     */
    public function existeNumero(string $numeroFicha, ?int $excluirId = null): bool
    {
        // ► AQUÍ: implementar
        //
        // $sql    = 'SELECT COUNT(*) FROM fichas WHERE numero_ficha = :numero';
        // $params = [':numero' => $numeroFicha];
        // if ($excluirId !== null) { $sql .= ' AND id != :excluir'; $params[':excluir'] = $excluirId; }
        // return $this->existe($sql, $params);

        return false;
    }

    /**
     * Cuenta fichas activas. Usado por EstadisticaService::resumen().
     *
     * @return int Total de fichas activas.
     */
    public function contarActivas(): int
    {
        // ► AQUÍ: implementar
        //
        // return $this->contar("SELECT COUNT(*) FROM fichas WHERE estado = 'activa'");

        return 0;
    }

    /**
     * Cuenta fichas activas vinculadas a una jornada.
     * Usado por JornadaService antes de eliminar una jornada.
     *
     * @param int $idJornada Identificador de la jornada.
     * @return int Total de fichas activas.
     */
    public function contarActivasPorJornada(int $idJornada): int
    {
        // ► AQUÍ: implementar
        //
        // return $this->contar(
        //     "SELECT COUNT(*) FROM fichas WHERE id_jornada = :id AND estado = 'activa'",
        //     [':id' => $idJornada]
        // );

        return 0;
    }

    // -------------------------------------------------------------------------
    // Consultas de escritura
    // -------------------------------------------------------------------------

    /**
     * Inserta una nueva ficha de formación.
     *
     * @param string   $numeroFicha Número único de la ficha.
     * @param int      $idPrograma  Identificador del programa.
     * @param int|null $idJornada   Identificador opcional de la jornada.
     * @return int ID de la ficha creada.
     */
    public function crear(string $numeroFicha, int $idPrograma, ?int $idJornada = null): int
    {
        // ► AQUÍ: implementar
        //
        // return $this->insertar(
        //     'INSERT INTO fichas (numero_ficha, id_programa, id_jornada, estado)
        //      VALUES (:numero, :prog, :jornada, "activa")',
        //     [':numero' => $numeroFicha, ':prog' => $idPrograma, ':jornada' => $idJornada]
        // );

        return 0;
    }

    /**
     * Actualiza los campos enviados de una ficha existente.
     *
     * @param int                  $idFicha Identificador de la ficha.
     * @param array<string, mixed> $datos   Campos a actualizar.
     * @return int Número de filas afectadas.
     */
    public function actualizar(int $idFicha, array $datos): int
    {
        // ► AQUÍ: construir SET dinámico
        //
        // $camposPermitidos = ['numero_ficha', 'id_programa', 'id_jornada', 'estado'];
        // $set = []; $params = [':id' => $idFicha];
        // foreach ($camposPermitidos as $campo) {
        //     if (array_key_exists($campo, $datos)) {
        //         $set[] = "{$campo} = :{$campo}";
        //         $params[":{$campo}"] = $datos[$campo];
        //     }
        // }
        // if (empty($set)) { return 0; }
        // return $this->ejecutar("UPDATE fichas SET " . implode(', ', $set) . " WHERE id = :id", $params);

        return 0;
    }

    /**
     * Elimina una ficha por su ID.
     *
     * @param int $idFicha Identificador de la ficha.
     * @return int Número de filas afectadas.
     */
    public function eliminar(int $idFicha): int
    {
        // ► AQUÍ: implementar
        //
        // return $this->ejecutar('DELETE FROM fichas WHERE id = :id', [':id' => $idFicha]);

        return 0;
    }
}