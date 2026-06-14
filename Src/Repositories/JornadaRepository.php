<?php

declare(strict_types=1);

/**
 * AttendQR – JornadaRepository
 *
 * Responsabilidad: acceder a la tabla `jornadas` para todas las
 * operaciones CRUD del catálogo de jornadas académicas.
 *
 * Esta clase NO debe:
 *   - Validar coherencia de horarios (eso lo hace JornadaService).
 *   - Conocer Controllers ni Services.
 *   - Generar HTML, JSON ni usar header() o exit.
 *
 * Flujo: JornadaService → JornadaRepository → Database → MySQL (tabla: jornadas)
 *
 * Ubicación en el proyecto: Src/Repositories/JornadaRepository.php
 */
class JornadaRepository extends BaseRepository
{
    // -------------------------------------------------------------------------
    // Consultas de lectura
    // -------------------------------------------------------------------------

    /**
     * Busca una jornada por su ID.
     *
     * @param int $idJornada Identificador único de la jornada.
     * @return array<string, mixed>|null Datos de la jornada o null.
     */
    public function obtenerPorId(int $idJornada): ?array
    {
        // ► AQUÍ: implementar
        //
        // return $this->consultarUno(
        //     'SELECT id, nombre, hora_inicio, hora_fin, estado FROM jornadas WHERE id = :id',
        //     [':id' => $idJornada]
        // );

        return null;
    }

    /**
     * Lista jornadas con filtro opcional de estado.
     * Ordena por hora de inicio ascendente para mostrar en orden cronológico.
     *
     * @param string|null $estado Filtro por estado ('activa' | 'inactiva').
     * @return array<int, array<string, mixed>>
     */
    public function listar(?string $estado = null): array
    {
        // ► AQUÍ: implementar
        //
        // $sql    = 'SELECT id, nombre, hora_inicio, hora_fin, estado FROM jornadas WHERE 1=1';
        // $params = [];
        // if ($estado !== null) { $sql .= ' AND estado = :estado'; $params[':estado'] = $estado; }
        // $sql .= ' ORDER BY hora_inicio ASC';
        // return $this->consultar($sql, $params);

        return [];
    }

    /**
     * Verifica si ya existe una jornada con el nombre indicado.
     *
     * @param string   $nombre    Nombre de la jornada.
     * @param int|null $excluirId ID a excluir (para actualizaciones).
     * @return bool true si existe.
     */
    public function existeNombre(string $nombre, ?int $excluirId = null): bool
    {
        // ► AQUÍ: implementar
        //
        // $sql    = 'SELECT COUNT(*) FROM jornadas WHERE nombre = :nombre';
        // $params = [':nombre' => $nombre];
        // if ($excluirId !== null) { $sql .= ' AND id != :excluir'; $params[':excluir'] = $excluirId; }
        // return $this->existe($sql, $params);

        return false;
    }

    // -------------------------------------------------------------------------
    // Consultas de escritura
    // -------------------------------------------------------------------------

    /**
     * Inserta una nueva jornada.
     *
     * @param string      $nombre     Nombre de la jornada.
     * @param string|null $horaInicio Hora de inicio ('HH:MM').
     * @param string|null $horaFin    Hora de fin ('HH:MM').
     * @return int ID de la jornada creada.
     */
    public function crear(string $nombre, ?string $horaInicio = null, ?string $horaFin = null): int
    {
        // ► AQUÍ: implementar
        //
        // return $this->insertar(
        //     'INSERT INTO jornadas (nombre, hora_inicio, hora_fin, estado)
        //      VALUES (:nombre, :inicio, :fin, "activa")',
        //     [':nombre' => $nombre, ':inicio' => $horaInicio, ':fin' => $horaFin]
        // );

        return 0;
    }

    /**
     * Actualiza los campos enviados de una jornada existente.
     *
     * @param int                  $idJornada Identificador de la jornada.
     * @param array<string, mixed> $datos     Campos a actualizar.
     * @return int Número de filas afectadas.
     */
    public function actualizar(int $idJornada, array $datos): int
    {
        // ► AQUÍ: construir SET dinámico
        //
        // $camposPermitidos = ['nombre', 'hora_inicio', 'hora_fin', 'estado'];
        // $set = []; $params = [':id' => $idJornada];
        // foreach ($camposPermitidos as $campo) {
        //     if (array_key_exists($campo, $datos)) {
        //         $set[] = "{$campo} = :{$campo}";
        //         $params[":{$campo}"] = $datos[$campo];
        //     }
        // }
        // if (empty($set)) { return 0; }
        // return $this->ejecutar("UPDATE jornadas SET " . implode(', ', $set) . " WHERE id = :id", $params);

        return 0;
    }

    /**
     * Elimina una jornada por su ID.
     *
     * @param int $idJornada Identificador de la jornada.
     * @return int Número de filas afectadas.
     */
    public function eliminar(int $idJornada): int
    {
        // ► AQUÍ: implementar
        //
        // return $this->ejecutar('DELETE FROM jornadas WHERE id = :id', [':id' => $idJornada]);

        return 0;
    }
}