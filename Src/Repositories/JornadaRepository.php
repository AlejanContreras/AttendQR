<?php

declare(strict_types=1);

/**
 * AttendQR – JornadaRepository
 *
 * Tabla: jornadas
 * Columnas reales: id_jornada, nombre, hora_inicio, hora_fin, minutos_gracia
 * NOTA: la tabla jornadas NO tiene columna 'estado'. Es una tabla de referencia.
 *
 * NO contiene lógica de negocio.
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
            'SELECT id_jornada, nombre, hora_inicio, hora_fin, minutos_gracia
             FROM jornadas
             WHERE id_jornada = :id',
            [':id' => $idJornada]
        );
    }

    /**
     * Lista todas las jornadas ordenadas por hora de inicio.
     *
     * @return array<int, array<string, mixed>>
     */
    public function listar(): array
    {
        return $this->consultar(
            'SELECT id_jornada, nombre, hora_inicio, hora_fin, minutos_gracia
             FROM jornadas
             ORDER BY hora_inicio'
        );
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
            $sql              .= ' AND id_jornada != :excluir';
            $params[':excluir'] = $excluirId;
        }

        return $this->existe($sql, $params);
    }

    /**
     * Inserta una nueva jornada.
     *
     * @param string      $nombre        Nombre de la jornada.
     * @param string|null $horaInicio    Hora de inicio (HH:MM).
     * @param string|null $horaFin       Hora de fin (HH:MM).
     * @param int         $minutosGracia Minutos de gracia para marcar presencia.
     * @return int ID de la jornada creada.
     */
    public function crear(
        string  $nombre,
        ?string $horaInicio    = null,
        ?string $horaFin       = null,
        int     $minutosGracia = 5
    ): int {
        return $this->insertar(
            'INSERT INTO jornadas (nombre, hora_inicio, hora_fin, minutos_gracia)
             VALUES (:nombre, :inicio, :fin, :gracia)',
            [
                ':nombre' => $nombre,
                ':inicio' => $horaInicio,
                ':fin'    => $horaFin,
                ':gracia' => $minutosGracia,
            ]
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
        $camposPermitidos = ['nombre', 'hora_inicio', 'hora_fin', 'minutos_gracia'];
        $set    = [];
        $params = [':id' => $idJornada];

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
            'UPDATE jornadas SET ' . implode(', ', $set) . ' WHERE id_jornada = :id',
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
            'DELETE FROM jornadas WHERE id_jornada = :id',
            [':id' => $idJornada]
        );
    }
}
