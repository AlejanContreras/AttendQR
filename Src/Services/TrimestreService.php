<?php

declare(strict_types=1);

/**
 * AttendQR – TrimestreService
 *
 * Responsabilidad: contener la lógica de negocio relacionada con
 * los trimestres académicos del sistema.
 * Un "trimestre" define el período lectivo dentro del cual se agrupan
 * las sesiones y se calculan los porcentajes de asistencia.
 *
 * Esta clase NO debe:
 *   - Ejecutar SQL directamente.
 *   - Conocer el router ni los Controllers.
 *   - Acceder a $_POST, $_GET ni $_REQUEST.
 *   - Imprimir JSON, HTML ni usar header() o exit.
 *
 * Flujo esperado:
 *   TrimestreController → TrimestreService → TrimestreRepository → Modelo → Database
 *
 * Ubicación en el proyecto: Src/Services/TrimestreService.php
 */
class TrimestreService
{
    // -------------------------------------------------------------------------
    // Dependencias (se inyectarán cuando existan los Repositories)
    // -------------------------------------------------------------------------

    // ► AQUÍ: declarar dependencias
    //
    // Ejemplo futuro:
    //   private TrimestreRepository $trimestreRepo;
    //
    //   public function __construct(TrimestreRepository $trimestreRepo)
    //   {
    //       $this->trimestreRepo = $trimestreRepo;
    //   }

    // -------------------------------------------------------------------------
    // Métodos públicos
    // -------------------------------------------------------------------------

    /**
     * Obtiene los datos completos de un trimestre por su ID.
     *
     * Reglas de negocio:
     *   1. Verificar que el trimestre existe.
     *   2. Si no existe, lanzar excepción → 404 en el Controller.
     *   3. Retornar el trimestre con nombre, fechas, estado y conteo de sesiones.
     *
     * @param int $idTrimestre Identificador único del trimestre.
     * @return array<string, mixed>
     */
    public function consultar(int $idTrimestre): array
    {
        // ► AQUÍ: llamar a TrimestreRepository->obtenerPorId($idTrimestre)
        // ► AQUÍ: si no existe, lanzar new \RuntimeException('Trimestre no encontrado.', 404)

        return [
            'success'      => true,
            'message'      => 'TrimestreService::consultar() disponible. Pendiente de implementación.',
            'id_trimestre' => $idTrimestre,
        ];
    }

    /**
     * Lista trimestres con filtros opcionales de año y estado.
     *
     * Reglas de negocio:
     *   1. Aplicar filtros de año y estado.
     *   2. Retornar listado ordenado por fecha de inicio descendente.
     *
     * @param int|null    $anio   Filtro opcional por año (p. ej. 2025).
     * @param string|null $estado Filtro opcional ('activo' | 'cerrado').
     * @return array<string, mixed>
     */
    public function listar(?int $anio = null, ?string $estado = null): array
    {
        // ► AQUÍ: llamar a TrimestreRepository->listar($anio, $estado)

        return [
            'success'       => true,
            'message'       => 'TrimestreService::listar() disponible. Pendiente de implementación.',
            'filtro_anio'   => $anio,
            'filtro_estado' => $estado,
        ];
    }

    /**
     * Crea un nuevo trimestre académico.
     *
     * Reglas de negocio:
     *   1. Verificar que el nombre no está duplicado.
     *   2. Verificar que fecha_fin es posterior a fecha_inicio.
     *   3. Verificar que las fechas no se solapan con trimestres existentes.
     *   4. Persistir el trimestre con estado 'activo'.
     *   5. Retornar el trimestre creado.
     *
     * @param string $nombre      Nombre del trimestre (p. ej. 'Trimestre I – 2025').
     * @param string $fechaInicio Fecha de inicio en formato 'Y-m-d'.
     * @param string $fechaFin    Fecha de fin en formato 'Y-m-d'.
     * @return array<string, mixed>
     */
    public function crear(string $nombre, string $fechaInicio, string $fechaFin): array
    {
        $nombre      = trim($nombre);
        $fechaInicio = trim($fechaInicio);
        $fechaFin    = trim($fechaFin);

        if ($nombre === '') {
            return ['success' => false, 'message' => 'El nombre del trimestre no puede estar vacío.'];
        }

        if (!$this->esFechaValida($fechaInicio)) {
            return ['success' => false, 'message' => "Fecha de inicio '{$fechaInicio}' no tiene el formato Y-m-d."];
        }

        if (!$this->esFechaValida($fechaFin)) {
            return ['success' => false, 'message' => "Fecha de fin '{$fechaFin}' no tiene el formato Y-m-d."];
        }

        if (!$this->esPeriodoCoherente($fechaInicio, $fechaFin)) {
            return ['success' => false, 'message' => 'La fecha de fin debe ser posterior a la fecha de inicio.'];
        }

        // ► AQUÍ: llamar a TrimestreRepository->existeNombre($nombre)
        // ► AQUÍ: si existe, lanzar new \RuntimeException('Ya existe un trimestre con ese nombre.', 409)
        // ► AQUÍ: llamar a TrimestreRepository->existeSolapamiento($fechaInicio, $fechaFin)
        // ► AQUÍ: si se solapa, lanzar new \RuntimeException('Las fechas se solapan con otro trimestre.', 409)
        // ► AQUÍ: llamar a TrimestreRepository->crear($nombre, $fechaInicio, $fechaFin)

        return [
            'success'      => true,
            'message'      => 'TrimestreService::crear() disponible. Pendiente de implementación.',
            'nombre'       => $nombre,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin'    => $fechaFin,
        ];
    }

    /**
     * Actualiza los datos de un trimestre existente (actualización parcial).
     *
     * Reglas de negocio:
     *   1. Verificar que el trimestre existe.
     *   2. Si se cambia el nombre, verificar que no esté duplicado.
     *   3. Si se cambian las fechas, re-validar coherencia y solapamiento.
     *   4. Aplicar solo los campos enviados, conservar el resto.
     *
     * @param int                  $idTrimestre Identificador único del trimestre.
     * @param array<string, mixed> $datos       Campos a actualizar.
     * @return array<string, mixed>
     */
    public function actualizar(int $idTrimestre, array $datos): array
    {
        if (empty($datos)) {
            return ['success' => false, 'message' => 'No se recibieron datos para actualizar.'];
        }

        // Validar fechas si fueron enviadas
        if (isset($datos['fecha_inicio']) && !$this->esFechaValida((string) $datos['fecha_inicio'])) {
            return ['success' => false, 'message' => "Fecha de inicio '{$datos['fecha_inicio']}' no tiene el formato Y-m-d."];
        }

        if (isset($datos['fecha_fin']) && !$this->esFechaValida((string) $datos['fecha_fin'])) {
            return ['success' => false, 'message' => "Fecha de fin '{$datos['fecha_fin']}' no tiene el formato Y-m-d."];
        }

        if (isset($datos['fecha_inicio'], $datos['fecha_fin'])) {
            if (!$this->esPeriodoCoherente((string) $datos['fecha_inicio'], (string) $datos['fecha_fin'])) {
                return ['success' => false, 'message' => 'La fecha de fin debe ser posterior a la fecha de inicio.'];
            }
        }

        // ► AQUÍ: llamar a TrimestreRepository->obtenerPorId($idTrimestre)
        // ► AQUÍ: si no existe, lanzar new \RuntimeException('Trimestre no encontrado.', 404)
        // ► AQUÍ: llamar a TrimestreRepository->actualizar($idTrimestre, $datos)

        return [
            'success'      => true,
            'message'      => 'TrimestreService::actualizar() disponible. Pendiente de implementación.',
            'id_trimestre' => $idTrimestre,
            'datos'        => $datos,
        ];
    }

    /**
     * Elimina un trimestre del sistema.
     *
     * Reglas de negocio:
     *   1. Verificar que el trimestre existe.
     *   2. Verificar que no tiene sesiones o asistencias registradas.
     *   3. Proceder con la eliminación.
     *
     * @param int $idTrimestre Identificador único del trimestre a eliminar.
     * @return array<string, mixed>
     */
    public function eliminar(int $idTrimestre): array
    {
        // ► AQUÍ: llamar a TrimestreRepository->obtenerPorId($idTrimestre)
        // ► AQUÍ: llamar a SesionRepository->contarPorTrimestre($idTrimestre)
        // ► AQUÍ: si tiene sesiones, lanzar new \RuntimeException('El trimestre tiene sesiones registradas.', 409)
        // ► AQUÍ: llamar a TrimestreRepository->eliminar($idTrimestre)

        return [
            'success'      => true,
            'message'      => 'TrimestreService::eliminar() disponible. Pendiente de implementación.',
            'id_trimestre' => $idTrimestre,
        ];
    }

    // -------------------------------------------------------------------------
    // Métodos privados de apoyo
    // -------------------------------------------------------------------------

    /**
     * Valida que una cadena tenga el formato 'Y-m-d' y represente una fecha real.
     *
     * @param string $fecha Cadena a validar.
     * @return bool true si el formato y la fecha son válidos.
     */
    private function esFechaValida(string $fecha): bool
    {
        $dt = \DateTime::createFromFormat('Y-m-d', $fecha);

        return $dt !== false
            && checkdate((int) $dt->format('m'), (int) $dt->format('d'), (int) $dt->format('Y'));
    }

    /**
     * Verifica que la fecha de fin sea estrictamente posterior a la de inicio.
     * Ambas fechas deben haber sido validadas con esFechaValida() antes.
     *
     * @param string $fechaInicio Fecha de inicio en formato 'Y-m-d'.
     * @param string $fechaFin    Fecha de fin en formato 'Y-m-d'.
     * @return bool true si el período es coherente.
     */
    private function esPeriodoCoherente(string $fechaInicio, string $fechaFin): bool
    {
        return strtotime($fechaFin) > strtotime($fechaInicio);
    }
}