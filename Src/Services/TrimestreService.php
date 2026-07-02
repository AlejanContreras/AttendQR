<?php

declare(strict_types=1);

/**
 * AttendQR – TrimestreService
 *
 * Responsabilidad: lógica de negocio del módulo de trimestres académicos.
 * Flujo: TrimestreController → TrimestreService → TrimestreRepository / SesionRepository → Database
 *
 * Ubicación en el proyecto: Src/Services/TrimestreService.php
 */
class TrimestreService
{
    private TrimestreRepository $trimestreRepo;
    private SesionRepository    $sesionRepo;

    public function __construct()
    {
        $this->trimestreRepo = new TrimestreRepository();
        $this->sesionRepo    = new SesionRepository();
    }

    /**
     * Obtiene los datos de un trimestre por su ID.
     *
     * @param int $idTrimestre Identificador del trimestre.
     * @return array<string, mixed>
     * @throws \RuntimeException 404 si el trimestre no existe.
     */
    public function consultar(int $idTrimestre): array
    {
        $trimestre = $this->trimestreRepo->obtenerPorId($idTrimestre);

        if ($trimestre === null) {
            throw new \RuntimeException('Trimestre no encontrado.', 404);
        }

        return $trimestre;
    }

    /**
     * Lista trimestres con filtros opcionales de año y estado.
     *
     * @param int|null    $anio   Filtro por año (p. ej. 2025).
     * @param string|null $estado Filtro por estado ('activo' | 'cerrado').
     * @return array<string, mixed>
     */
    public function listar(?int $anio = null, ?string $estado = null): array
    {
        $activo = match ($estado) {
            'activo'  => 1,
            'cerrado' => 0,
            default   => null,
        };

        $trimestres = $this->trimestreRepo->listar($anio, $activo);

        return [
            'trimestres' => $trimestres,
            'total'      => count($trimestres),
        ];
    }

    /**
     * Crea un nuevo trimestre académico.
     *
     * Reglas de negocio:
     *   1. El nombre no puede estar vacío ni duplicado.
     *   2. Las fechas deben tener formato Y-m-d válido.
     *   3. La fecha de fin debe ser posterior a la de inicio.
     *   4. Las fechas no deben solaparse con trimestres existentes.
     *
     * @param string $nombre      Nombre del trimestre.
     * @param string $fechaInicio Fecha de inicio (Y-m-d).
     * @param string $fechaFin    Fecha de fin (Y-m-d).
     * @return array<string, mixed> Datos del trimestre creado.
     * @throws \RuntimeException 422 si el nombre está vacío o las fechas son inválidas.
     * @throws \RuntimeException 409 si el nombre ya existe o las fechas se solapan.
     */
    public function crear(string $nombre, string $fechaInicio, string $fechaFin): array
    {
        $nombre      = trim($nombre);
        $fechaInicio = trim($fechaInicio);
        $fechaFin    = trim($fechaFin);

        if ($nombre === '') {
            throw new \RuntimeException('El nombre del trimestre no puede estar vacío.', 422);
        }

        if (!$this->esFechaValida($fechaInicio)) {
            throw new \RuntimeException("Fecha de inicio '{$fechaInicio}' no tiene el formato Y-m-d.", 422);
        }

        if (!$this->esFechaValida($fechaFin)) {
            throw new \RuntimeException("Fecha de fin '{$fechaFin}' no tiene el formato Y-m-d.", 422);
        }

        if (!$this->esPeriodoCoherente($fechaInicio, $fechaFin)) {
            throw new \RuntimeException('La fecha de fin debe ser posterior a la fecha de inicio.', 422);
        }

        if ($this->trimestreRepo->existeNombre($nombre)) {
            throw new \RuntimeException('Ya existe un trimestre con ese nombre.', 409);
        }

        if ($this->trimestreRepo->existeSolapamiento($fechaInicio, $fechaFin)) {
            throw new \RuntimeException('Las fechas se solapan con un trimestre existente.', 409);
        }

        $id         = $this->trimestreRepo->crear($nombre, $fechaInicio, $fechaFin);
        $trimestre  = $this->trimestreRepo->obtenerPorId($id);

        return $trimestre ?? [
            'id_trimestre' => $id,
            'nombre'       => $nombre,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin'    => $fechaFin,
            'activo'       => 1,
        ];
    }

    /**
     * Actualiza los datos de un trimestre existente (actualización parcial).
     *
     * Reglas de negocio:
     *   1. El trimestre debe existir.
     *   2. Si se cambia el nombre, no puede estar duplicado.
     *   3. Si se cambian fechas, deben ser válidas, coherentes y sin solapamiento.
     *
     * @param int                  $idTrimestre Identificador del trimestre.
     * @param array<string, mixed> $datos       Campos a actualizar.
     * @return array<string, mixed>
     * @throws \RuntimeException 404 si el trimestre no existe.
     * @throws \RuntimeException 409/422 si las validaciones fallan.
     */
    public function actualizar(int $idTrimestre, array $datos): array
    {
        $trimestre = $this->trimestreRepo->obtenerPorId($idTrimestre);

        if ($trimestre === null) {
            throw new \RuntimeException('Trimestre no encontrado.', 404);
        }

        if (isset($datos['nombre'])) {
            $datos['nombre'] = trim((string) $datos['nombre']);

            if ($this->trimestreRepo->existeNombre($datos['nombre'], $idTrimestre)) {
                throw new \RuntimeException('Ya existe un trimestre con ese nombre.', 409);
            }
        }

        if (isset($datos['fecha_inicio']) && !$this->esFechaValida((string) $datos['fecha_inicio'])) {
            throw new \RuntimeException("Fecha de inicio '{$datos['fecha_inicio']}' no tiene el formato Y-m-d.", 422);
        }

        if (isset($datos['fecha_fin']) && !$this->esFechaValida((string) $datos['fecha_fin'])) {
            throw new \RuntimeException("Fecha de fin '{$datos['fecha_fin']}' no tiene el formato Y-m-d.", 422);
        }

        if (isset($datos['fecha_inicio'], $datos['fecha_fin'])) {
            if (!$this->esPeriodoCoherente((string) $datos['fecha_inicio'], (string) $datos['fecha_fin'])) {
                throw new \RuntimeException('La fecha de fin debe ser posterior a la fecha de inicio.', 422);
            }

            if ($this->trimestreRepo->existeSolapamiento((string) $datos['fecha_inicio'], (string) $datos['fecha_fin'], $idTrimestre)) {
                throw new \RuntimeException('Las fechas se solapan con un trimestre existente.', 409);
            }
        }

        $this->trimestreRepo->actualizar($idTrimestre, $datos);

        return $this->trimestreRepo->obtenerPorId($idTrimestre) ?? $trimestre;
    }

    /**
     * Elimina un trimestre del sistema.
     *
     * Reglas de negocio:
     *   1. El trimestre debe existir.
     *   2. No puede tener sesiones asociadas.
     *
     * @param int $idTrimestre Identificador del trimestre a eliminar.
     * @return array<string, mixed>
     * @throws \RuntimeException 404 si el trimestre no existe.
     * @throws \RuntimeException 409 si tiene sesiones registradas.
     */
    public function eliminar(int $idTrimestre): array
    {
        $trimestre = $this->trimestreRepo->obtenerPorId($idTrimestre);

        if ($trimestre === null) {
            throw new \RuntimeException('Trimestre no encontrado.', 404);
        }

        if ($this->sesionRepo->contarPorTrimestre($idTrimestre) > 0) {
            throw new \RuntimeException('El trimestre tiene sesiones registradas. Elimínelas antes de continuar.', 409);
        }

        $this->trimestreRepo->eliminar($idTrimestre);

        return ['success' => true, 'message' => 'Trimestre eliminado correctamente.'];
    }

    // -------------------------------------------------------------------------
    // Métodos privados de apoyo
    // -------------------------------------------------------------------------

    private function esFechaValida(string $fecha): bool
    {
        $dt = \DateTime::createFromFormat('Y-m-d', $fecha);

        return $dt !== false
            && checkdate((int) $dt->format('m'), (int) $dt->format('d'), (int) $dt->format('Y'));
    }

    private function esPeriodoCoherente(string $fechaInicio, string $fechaFin): bool
    {
        return strtotime($fechaFin) > strtotime($fechaInicio);
    }
}