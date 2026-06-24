<?php

declare(strict_types=1);

/**
 * AttendQR – SesionService
 *
 * Responsabilidad: lógica de negocio del ciclo de vida de las sesiones de clase.
 * Flujo: SesionController → SesionService → SesionRepository / QrRepository → Database
 *
 * Ubicación en el proyecto: Src/Services/SesionService.php
 */
class SesionService
{
    private SesionRepository $sesionRepo;
    private QrRepository     $qrRepo;

    public function __construct()
    {
        $this->sesionRepo = new SesionRepository();
        $this->qrRepo     = new QrRepository();
    }

    /**
     * Crea una nueva sesión de clase y la deja en estado 'activa'.
     *
     * Reglas de negocio:
     *   1. La fecha debe tener formato Y-m-d válido.
     *   2. El docente no puede tener ya una sesión activa en la misma fecha.
     *
     * @param int    $idMateria  Identificador de la materia.
     * @param int    $idDocente  Identificador del docente.
     * @param string $fecha      Fecha de la sesión (Y-m-d).
     * @return array<string, mixed> Datos de la sesión creada.
     * @throws \RuntimeException 422 si la fecha no es válida.
     * @throws \RuntimeException 409 si el docente ya tiene una sesión activa ese día.
     */
    public function crear(int $idMateria, int $idDocente, string $fecha): array
    {
        if (!$this->esFechaValida($fecha)) {
            throw new \RuntimeException("El formato de fecha '{$fecha}' no es válido. Se esperaba Y-m-d.", 422);
        }

        if ($this->sesionRepo->existeActivaParaDocente($idDocente, $fecha)) {
            throw new \RuntimeException('El docente ya tiene una sesión activa en esta fecha.', 409);
        }

        $id     = $this->sesionRepo->crear($idMateria, $idDocente, $fecha);
        $sesion = $this->sesionRepo->obtenerPorId($id);

        return $sesion ?? ['id' => $id, 'id_materia' => $idMateria, 'id_docente' => $idDocente, 'fecha' => $fecha, 'estado' => 'activa'];
    }

    /**
     * Obtiene los datos completos de una sesión por su ID.
     *
     * @param int $idSesion Identificador de la sesión.
     * @return array<string, mixed>
     * @throws \RuntimeException 404 si la sesión no existe.
     */
    public function consultar(int $idSesion): array
    {
        $sesion = $this->sesionRepo->obtenerDetalle($idSesion);

        if ($sesion === null) {
            throw new \RuntimeException('Sesión no encontrada.', 404);
        }

        return $sesion;
    }

    /**
     * Lista sesiones con filtros opcionales de docente, fecha y estado.
     *
     * @param int|null    $idDocente Filtro por docente.
     * @param string|null $fecha     Filtro por fecha (Y-m-d).
     * @param string|null $estado    Filtro por estado ('activa' | 'cerrada').
     * @return array<string, mixed>
     * @throws \RuntimeException 422 si el estado no es válido.
     */
    public function listar(?int $idDocente = null, ?string $fecha = null, ?string $estado = null): array
    {
        $estadosPermitidos = ['activa', 'cerrada'];

        if ($estado !== null && !in_array($estado, $estadosPermitidos, true)) {
            throw new \RuntimeException(
                "Estado '{$estado}' no válido. Valores permitidos: " . implode(', ', $estadosPermitidos) . '.',
                422
            );
        }

        $sesiones = $this->sesionRepo->listar($idDocente, $fecha, $estado);

        return [
            'sesiones' => $sesiones,
            'total'    => count($sesiones),
        ];
    }

    /**
     * Cierra una sesión activa e invalida todos sus tokens QR asociados.
     *
     * Reglas de negocio:
     *   1. La sesión debe existir.
     *   2. La sesión debe estar en estado 'activa'.
     *   3. Se invalidan los QR vinculados antes de cerrar.
     *
     * @param int $idSesion Identificador de la sesión a cerrar.
     * @return array<string, mixed>
     * @throws \RuntimeException 404 si la sesión no existe.
     * @throws \RuntimeException 409 si la sesión ya está cerrada.
     */
    public function cerrar(int $idSesion): array
    {
        $sesion = $this->sesionRepo->obtenerPorId($idSesion);

        if ($sesion === null) {
            throw new \RuntimeException('Sesión no encontrada.', 404);
        }

        if ((string) $sesion['estado'] !== 'activa') {
            throw new \RuntimeException('La sesión ya fue cerrada.', 409);
        }

        $this->qrRepo->invalidarPorSesion($idSesion);

        $horaCierre = date('Y-m-d H:i:s');
        $this->sesionRepo->cerrar($idSesion, $horaCierre);

        return [
            'success'     => true,
            'id_sesion'   => $idSesion,
            'hora_cierre' => $horaCierre,
            'message'     => 'Sesión cerrada correctamente.',
        ];
    }

    // -------------------------------------------------------------------------
    // Métodos privados de apoyo
    // -------------------------------------------------------------------------

    /**
     * Valida que una cadena tenga el formato Y-m-d y represente una fecha real.
     *
     * @param string $fecha Cadena a validar.
     * @return bool
     */
    private function esFechaValida(string $fecha): bool
    {
        $dt = \DateTime::createFromFormat('Y-m-d', $fecha);

        return $dt !== false
            && checkdate((int) $dt->format('m'), (int) $dt->format('d'), (int) $dt->format('Y'));
    }
}