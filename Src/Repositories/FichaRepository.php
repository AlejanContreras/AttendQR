<?php

declare(strict_types=1);

/**
 * AttendQR – FichaRepository
 *
 * Responsabilidad: acceder a la tabla `fichas` para todas las
 * operaciones CRUD y consultas de soporte a otros módulos.
 *
 * NO contiene lógica de negocio.
 *
 * Flujo: FichaService → FichaRepository → BaseRepository → Database → MySQL
 *
 * Ubicación en el proyecto: Src/Repositories/FichaRepository.php
 */
class FichaRepository extends BaseRepository
{
    /**
     * Busca una ficha por su ID enriquecida con datos de la jornada.
     *
     * @param int $idFicha Identificador de la ficha.
     * @return array<string, mixed>|null Datos de la ficha o null.
     */
    public function obtenerPorId(int $idFicha): ?array
    {
        return $this->consultarUno(
            'SELECT f.id, f.numero_ficha, f.id_programa, f.id_jornada, f.estado,
                    j.nombre AS nombre_jornada
             FROM fichas f
             LEFT JOIN jornadas j ON j.id = f.id_jornada
             WHERE f.id = :id',
            [':id' => $idFicha]
        );
    }

    /**
     * Lista fichas con filtros opcionales de programa, estado y jornada.
     *
     * @param int|null    $idPrograma Filtro por programa.
     * @param string|null $estado     Filtro por estado.
     * @param int|null    $idJornada  Filtro por jornada.
     * @return array<int, array<string, mixed>>
     */
    public function listar(?int $idPrograma = null, ?string $estado = null, ?int $idJornada = null): array
    {
        $sql    = 'SELECT f.id, f.numero_ficha, f.estado,
                          j.nombre AS jornada
                   FROM fichas f
                   LEFT JOIN jornadas j ON j.id = f.id_jornada
                   WHERE 1=1';
        $params = [];

        if ($idPrograma !== null) {
            $sql .= ' AND f.id_programa = :prog';
            $params[':prog']    = $idPrograma;
        }

        if ($estado !== null) {
            $sql .= ' AND f.estado      = :estado';
            $params[':estado']  = $estado;
        }

        if ($idJornada !== null) {
            $sql .= ' AND f.id_jornada  = :jornada';
            $params[':jornada'] = $idJornada;
        }

        $sql .= ' ORDER BY f.numero_ficha';

        return $this->consultar($sql, $params);
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
        $sql    = 'SELECT COUNT(*) FROM fichas WHERE numero_ficha = :numero';
        $params = [':numero' => $numeroFicha];

        if ($excluirId !== null) {
            $sql              .= ' AND id != :excluir';
            $params[':excluir'] = $excluirId;
        }

        return $this->existe($sql, $params);
    }

    /**
     * Cuenta fichas activas. Usado por EstadisticaService::resumen().
     *
     * @return int Total de fichas activas.
     */
    public function contarActivas(): int
    {
        return $this->contar(
            "SELECT COUNT(*) FROM fichas WHERE estado = 'activa'"
        );
    }

    /**
     * Cuenta fichas activas vinculadas a una jornada.
     * Usado por JornadaService antes de eliminar una jornada.
     *
     * @param int $idJornada Identificador de la jornada.
     * @return int Total de fichas activas en la jornada.
     */
    public function contarActivasPorJornada(int $idJornada): int
    {
        return $this->contar(
            "SELECT COUNT(*) FROM fichas WHERE id_jornada = :id AND estado = 'activa'",
            [':id' => $idJornada]
        );
    }

    /**
     * Inserta una nueva ficha de formación.
     *
     * @param string   $numeroFicha Número único de la ficha.
     * @param int      $idPrograma  Identificador del programa.
     * @param int|null $idJornada   Identificador de la jornada.
     * @return int ID de la ficha creada.
     */
    public function crear(string $numeroFicha, int $idPrograma, ?int $idJornada = null): int
    {
        return $this->insertar(
            "INSERT INTO fichas (numero_ficha, id_programa, id_jornada, estado)
             VALUES (:numero, :prog, :jornada, 'activa')",
            [':numero' => $numeroFicha, ':prog' => $idPrograma, ':jornada' => $idJornada]
        );
    }

    /**
     * Actualiza únicamente los campos enviados de una ficha existente.
     *
     * @param int                  $idFicha Identificador de la ficha.
     * @param array<string, mixed> $datos   Campos a actualizar.
     * @return int Filas afectadas.
     */
    public function actualizar(int $idFicha, array $datos): int
    {
        $camposPermitidos = ['numero_ficha', 'id_programa', 'id_jornada', 'estado'];
        $set    = [];
        $params = [':id' => $idFicha];

        foreach ($camposPermitidos as $campo) {
            if (array_key_exists($campo, $datos)) {
                $set[]             = "{$campo} = :{$campo}";
                $params[":{$campo}"] = $datos[$campo];
            }
        }

        if (empty($set)) {
            return 0;
        }

        return $this->ejecutar(
            'UPDATE fichas SET ' . implode(', ', $set) . ' WHERE id = :id',
            $params
        );
    }

    /**
     * Elimina una ficha por su ID.
     *
     * @param int $idFicha Identificador de la ficha.
     * @return int Filas afectadas.
     */
    public function eliminar(int $idFicha): int
    {
        return $this->ejecutar(
            'DELETE FROM fichas WHERE id = :id',
            [':id' => $idFicha]
        );
    }
}