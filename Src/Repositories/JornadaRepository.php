<?php

declare(strict_types=1);

/**
 * AttendQR – JornadaRepository
 *
 * Responsabilidad: acceder a la tabla `jornadas` para todas las
 * operaciones CRUD del catálogo de jornadas académicas.
 *
 * NO valida coherencia de horarios (eso lo hace JornadaService).
 * NO contiene lógica de negocio.
 *
 * Flujo: JornadaService → JornadaRepository → BaseRepository → Database → MySQL
 *
 * Ubicación en el proyecto: Src/Repositories/JornadaRepository.php
 */
class JornadaRepository extends BaseRepository
{
    /**
     * Busca una jornada por su ID.
     *
     * @param int $idJornada Identificador de la jornada.
     * @return array<string, mixed>|null Datos de la jornada o null.
     */
    public function obtenerPorId(int $idJornada): ?array
    {
        return $this->consultarUno(
            'SELECT id, nombre, hora_inicio, hora_fin, estado
             FROM jornadas
             WHERE id = :id',
            [':id' => $idJornada]
        );
    }

    /**
     * Lista jornadas con filtro opcional de estado.
     * Ordena por hora de inicio ascendente.
     *
     * @param string|null $estado Filtro por estado ('activa' | 'inactiva').
     * @return array<int, array<string, mixed>>
     */
    public function listar(?string $estado = null): array
    {
        $sql    = 'SELECT id, nombre, hora_inicio, hora_fin, estado
                   FROM jornadas
                   WHERE 1=1';
        $params = [];

        if ($estado !== null) {
            $sql .= ' AND estado = :estado';
            $params[':estado'] = $estado;
        }

        $sql .= ' ORDER BY hora_inicio ASC';

        return $this->consultar($sql, $params);
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
        $sql    = 'SELECT COUNT(*) FROM jornadas WHERE nombre = :nombre';
        $params = [':nombre' => $nombre];

        if ($excluirId !== null) {
            $sql              .= ' AND id != :excluir';
            $params[':excluir'] = $excluirId;
        }

        return $this->existe($sql, $params);
    }

    /**
     * Inserta una nueva jornada académica.
     *
     * @param string      $nombre     Nombre de la jornada.
     * @param string|null $horaInicio Hora de inicio ('HH:MM').
     * @param string|null $horaFin    Hora de fin ('HH:MM').
     * @return int ID de la jornada creada.
     */
    public function crear(string $nombre, ?string $horaInicio = null, ?string $horaFin = null): int
    {
        return $this->insertar(
            "INSERT INTO jornadas (nombre, hora_inicio, hora_fin, estado)
             VALUES (:nombre, :inicio, :fin, 'activa')",
            [':nombre' => $nombre, ':inicio' => $horaInicio, ':fin' => $horaFin]
        );
    }

    /**
     * Actualiza únicamente los campos enviados de una jornada existente.
     *
     * @param int                  $idJornada Identificador de la jornada.
     * @param array<string, mixed> $datos     Campos a actualizar.
     * @return int Filas afectadas.
     */
    public function actualizar(int $idJornada, array $datos): int
    {
        $camposPermitidos = ['nombre', 'hora_inicio', 'hora_fin', 'estado'];
        $set    = [];
        $params = [':id' => $idJornada];

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
            'UPDATE jornadas SET ' . implode(', ', $set) . ' WHERE id = :id',
            $params
        );
    }

    /**
     * Elimina una jornada por su ID.
     *
     * @param int $idJornada Identificador de la jornada.
     * @return int Filas afectadas.
     */
    public function eliminar(int $idJornada): int
    {
        return $this->ejecutar(
            'DELETE FROM jornadas WHERE id = :id',
            [':id' => $idJornada]
        );
    }
}