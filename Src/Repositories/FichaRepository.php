<?php

declare(strict_types=1);

/**
 * AttendQR – FichaRepository
 *
 * Tabla: fichas
 * Columnas reales: id_ficha, codigo_ficha, nombre_programa (VARCHAR),
 *                  id_jornada, id_docente, id_trimestre, activa (TINYINT 1=activa)
 *
 * NO contiene lógica de negocio.
 * Flujo: FichaService → FichaRepository → BaseRepository → Database → MySQL
 *
 * Ubicación en el proyecto: Src/Repositories/FichaRepository.php
 */
class FichaRepository extends BaseRepository
{
    /**
     * Busca una ficha por su ID con datos de la jornada.
     *
     * @param int $idFicha Identificador de la ficha.
     * @return array<string, mixed>|null Datos de la ficha o null.
     */
    public function obtenerPorId(int $idFicha): ?array
    {
        return $this->consultarUno(
            'SELECT f.id_ficha, f.codigo_ficha, f.nombre_programa, f.nombre_materia,
                    f.id_jornada, f.id_docente, f.id_trimestre, f.activa,
                    j.nombre AS nombre_jornada,
                    j.hora_inicio, j.hora_fin, j.minutos_gracia
             FROM fichas f
             LEFT JOIN jornadas j ON j.id_jornada = f.id_jornada
             WHERE f.id_ficha = :id',
            [':id' => $idFicha]
        );
    }

    /**
     * Lista fichas con filtros opcionales.
     *
     * @param string|null $nombrePrograma Filtro parcial por nombre del programa.
     * @param int|null    $activa         Filtro por estado (1 = activa, 0 = inactiva).
     * @param int|null    $idJornada      Filtro por jornada.
     * @param int|null    $idDocente      Filtro por docente.
     * @return array<int, array<string, mixed>>
     */
    public function listar(
        ?string $nombrePrograma = null,
        ?int    $activa         = null,
        ?int    $idJornada      = null,
        ?int    $idDocente      = null
    ): array {
        $sql    = 'SELECT f.id_ficha, f.codigo_ficha, f.nombre_programa, f.nombre_materia,
                          f.id_jornada, f.id_docente, f.id_trimestre, f.activa,
                          j.nombre AS nombre_jornada
                   FROM fichas f
                   LEFT JOIN jornadas j ON j.id_jornada = f.id_jornada
                   WHERE 1=1';
        $params = [];

        if ($nombrePrograma !== null) {
            $sql .= ' AND f.nombre_programa LIKE :prog';
            $params[':prog'] = '%' . $nombrePrograma . '%';
        }

        if ($activa !== null) {
            $sql .= ' AND f.activa = :activa';
            $params[':activa'] = $activa;
        }

        if ($idJornada !== null) {
            $sql .= ' AND f.id_jornada = :id_jornada';
            $params[':id_jornada'] = $idJornada;
        }

        if ($idDocente !== null) {
            $sql .= ' AND f.id_docente = :id_docente';
            $params[':id_docente'] = $idDocente;
        }

        $sql .= ' ORDER BY f.id_ficha DESC';

        return $this->consultar($sql, $params);
    }

    /**
     * Busca una ficha por su código oficial SENA.
     * Usado por el servicio de importación masiva.
     */
    public function obtenerPorCodigo(string $codigoFicha): ?array
    {
        return $this->consultarUno(
            'SELECT f.id_ficha, f.codigo_ficha, f.nombre_programa, f.nombre_materia,
                    f.id_jornada, f.id_docente, f.id_trimestre, f.activa,
                    j.nombre AS nombre_jornada
             FROM fichas f
             LEFT JOIN jornadas j ON j.id_jornada = f.id_jornada
             WHERE f.codigo_ficha = :codigo
             LIMIT 1',
            [':codigo' => $codigoFicha]
        );
    }

    /**
     * Verifica si ya existe una ficha con el código indicado.
     *
     * @param string   $codigoFicha Código de la ficha.
     * @param int|null $excluirId   ID a excluir (para actualizaciones).
     * @return bool true si existe.
     */
    public function existeCodigo(string $codigoFicha, ?int $excluirId = null): bool
    {
        $sql    = 'SELECT COUNT(*) FROM fichas WHERE codigo_ficha = :codigo';
        $params = [':codigo' => $codigoFicha];

        if ($excluirId !== null) {
            $sql              .= ' AND id_ficha != :excluir';
            $params[':excluir'] = $excluirId;
        }

        return $this->existe($sql, $params);
    }

    /**
     * Cuenta fichas activas. Usado por EstadisticaService.
     *
     * @return int Total de fichas activas.
     */
    public function contarActivas(): int
    {
        return $this->contar('SELECT COUNT(*) FROM fichas WHERE activa = 1');
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
            'SELECT COUNT(*) FROM fichas WHERE id_jornada = :id AND activa = 1',
            [':id' => $idJornada]
        );
    }

    /**
     * Inserta una nueva ficha de formación.
     * id_jornada, id_docente e id_trimestre son NOT NULL en el schema.
     *
     * @param string $codigoFicha    Código único de la ficha SENA.
     * @param string $nombrePrograma Nombre del programa de formación.
     * @param int    $idJornada      Identificador de la jornada.
     * @param int    $idDocente      Identificador del docente responsable.
     * @param int    $idTrimestre    Identificador del trimestre.
     * @return int ID de la ficha creada.
     */
    public function crear(
        string  $codigoFicha,
        string  $nombrePrograma,
        int     $idJornada,
        int     $idDocente,
        ?string $nombreMateria = null,
        ?int    $idTrimestre   = null
    ): int {
        return $this->insertar(
            'INSERT INTO fichas (codigo_ficha, nombre_programa, nombre_materia, id_jornada, id_docente, id_trimestre, activa)
             VALUES (:codigo, :programa, :materia, :jornada, :docente, :trimestre, 1)',
            [
                ':codigo'    => $codigoFicha,
                ':programa'  => $nombrePrograma,
                ':materia'   => $nombreMateria,
                ':jornada'   => $idJornada,
                ':docente'   => $idDocente,
                ':trimestre' => $idTrimestre,
            ]
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
        $camposPermitidos = ['codigo_ficha', 'nombre_programa', 'nombre_materia', 'id_jornada', 'id_docente', 'id_trimestre', 'activa'];
        $set    = [];
        $params = [':id' => $idFicha];

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
            'UPDATE fichas SET ' . implode(', ', $set) . ' WHERE id_ficha = :id',
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
            'DELETE FROM fichas WHERE id_ficha = :id',
            [':id' => $idFicha]
        );
    }
}
