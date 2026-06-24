<?php

declare(strict_types=1);

/**
 * AttendQR – JornadaService
 *
 * Responsabilidad: lógica de negocio del módulo de jornadas académicas.
 * Flujo: JornadaController → JornadaService → JornadaRepository / FichaRepository → Database
 *
 * Ubicación en el proyecto: Src/Services/JornadaService.php
 */
class JornadaService
{
    private JornadaRepository $jornadaRepo;
    private FichaRepository   $fichaRepo;

    public function __construct()
    {
        $this->jornadaRepo = new JornadaRepository();
        $this->fichaRepo   = new FichaRepository();
    }

    /**
     * Obtiene los datos de una jornada por su ID.
     *
     * @param int $idJornada Identificador de la jornada.
     * @return array<string, mixed>
     * @throws \RuntimeException 404 si la jornada no existe.
     */
    public function consultar(int $idJornada): array
    {
        $jornada = $this->jornadaRepo->obtenerPorId($idJornada);

        if ($jornada === null) {
            throw new \RuntimeException('Jornada no encontrada.', 404);
        }

        return $jornada;
    }

    /**
     * Lista jornadas con filtro opcional de estado.
     *
     * @param string|null $estado Filtro por estado ('activa' | 'inactiva').
     * @return array<string, mixed>
     */
    public function listar(?string $estado = null): array
    {
        $jornadas = $this->jornadaRepo->listar($estado);

        return [
            'jornadas' => $jornadas,
            'total'    => count($jornadas),
        ];
    }

    /**
     * Crea una nueva jornada académica.
     *
     * Reglas de negocio:
     *   1. El nombre no puede estar vacío ni duplicado.
     *   2. Si se envían horarios, hora_fin debe ser posterior a hora_inicio.
     *
     * @param string      $nombre     Nombre de la jornada (p. ej. 'Mañana').
     * @param string|null $horaInicio Hora de inicio ('HH:MM').
     * @param string|null $horaFin    Hora de fin ('HH:MM').
     * @return array<string, mixed> Datos de la jornada creada.
     * @throws \RuntimeException 422 si el nombre está vacío.
     * @throws \RuntimeException 409 si el nombre ya existe.
     * @throws \RuntimeException 422 si el horario es incoherente.
     */
    public function crear(string $nombre, ?string $horaInicio = null, ?string $horaFin = null): array
    {
        $nombre = trim($nombre);

        if ($nombre === '') {
            throw new \RuntimeException('El nombre de la jornada no puede estar vacío.', 422);
        }

        if ($this->jornadaRepo->existeNombre($nombre)) {
            throw new \RuntimeException('Ya existe una jornada con ese nombre.', 409);
        }

        if ($horaInicio !== null && $horaFin !== null && !$this->esHorarioCoherente($horaInicio, $horaFin)) {
            throw new \RuntimeException('La hora de fin debe ser posterior a la hora de inicio.', 422);
        }

        $id      = $this->jornadaRepo->crear($nombre, $horaInicio, $horaFin);
        $jornada = $this->jornadaRepo->obtenerPorId($id);

        return $jornada ?? [
            'id'          => $id,
            'nombre'      => $nombre,
            'hora_inicio' => $horaInicio,
            'hora_fin'    => $horaFin,
            'estado'      => 'activa',
        ];
    }

    /**
     * Actualiza los datos de una jornada existente (actualización parcial).
     *
     * Reglas de negocio:
     *   1. La jornada debe existir.
     *   2. Si se cambia el nombre, no puede estar duplicado.
     *   3. Si se actualizan horarios, deben ser coherentes.
     *
     * @param int                  $idJornada Identificador de la jornada.
     * @param array<string, mixed> $datos     Campos a actualizar.
     * @return array<string, mixed>
     * @throws \RuntimeException 404 si la jornada no existe.
     * @throws \RuntimeException 409 si el nombre ya está en uso.
     * @throws \RuntimeException 422 si el horario es incoherente.
     */
    public function actualizar(int $idJornada, array $datos): array
    {
        $jornada = $this->jornadaRepo->obtenerPorId($idJornada);

        if ($jornada === null) {
            throw new \RuntimeException('Jornada no encontrada.', 404);
        }

        if (isset($datos['nombre'])) {
            $datos['nombre'] = trim((string) $datos['nombre']);

            if ($this->jornadaRepo->existeNombre($datos['nombre'], $idJornada)) {
                throw new \RuntimeException('Ya existe una jornada con ese nombre.', 409);
            }
        }

        if (isset($datos['hora_inicio'], $datos['hora_fin'])) {
            if (!$this->esHorarioCoherente((string) $datos['hora_inicio'], (string) $datos['hora_fin'])) {
                throw new \RuntimeException('La hora de fin debe ser posterior a la hora de inicio.', 422);
            }
        }

        $this->jornadaRepo->actualizar($idJornada, $datos);

        return $this->jornadaRepo->obtenerPorId($idJornada) ?? $jornada;
    }

    /**
     * Elimina una jornada del sistema.
     *
     * Reglas de negocio:
     *   1. La jornada debe existir.
     *   2. No puede tener fichas activas vinculadas.
     *
     * @param int $idJornada Identificador de la jornada a eliminar.
     * @return array<string, mixed>
     * @throws \RuntimeException 404 si la jornada no existe.
     * @throws \RuntimeException 409 si tiene fichas activas.
     */
    public function eliminar(int $idJornada): array
    {
        $jornada = $this->jornadaRepo->obtenerPorId($idJornada);

        if ($jornada === null) {
            throw new \RuntimeException('Jornada no encontrada.', 404);
        }

        if ($this->fichaRepo->contarActivasPorJornada($idJornada) > 0) {
            throw new \RuntimeException('La jornada tiene fichas activas. Desvincúlelas antes de eliminarla.', 409);
        }

        $this->jornadaRepo->eliminar($idJornada);

        return ['success' => true, 'message' => 'Jornada eliminada correctamente.'];
    }

    // -------------------------------------------------------------------------
    // Métodos privados de apoyo
    // -------------------------------------------------------------------------

    /**
     * Verifica que la hora de fin sea posterior a la hora de inicio.
     *
     * @param string $horaInicio Hora de inicio ('HH:MM').
     * @param string $horaFin    Hora de fin ('HH:MM').
     * @return bool
     */
    private function esHorarioCoherente(string $horaInicio, string $horaFin): bool
    {
        $inicio = $this->horaAMinutos($horaInicio);
        $fin    = $this->horaAMinutos($horaFin);

        if ($inicio === null || $fin === null) {
            return true;
        }

        return $fin > $inicio;
    }

    /**
     * Convierte 'HH:MM' a minutos desde medianoche. Retorna null si el formato es inválido.
     *
     * @param string $hora Hora en formato 'HH:MM'.
     * @return int|null
     */
    private function horaAMinutos(string $hora): ?int
    {
        $partes = explode(':', trim($hora));

        if (count($partes) !== 2) {
            return null;
        }

        $h = (int) $partes[0];
        $m = (int) $partes[1];

        if ($h < 0 || $h > 23 || $m < 0 || $m > 59) {
            return null;
        }

        return $h * 60 + $m;
    }
}