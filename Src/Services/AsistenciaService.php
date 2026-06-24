<?php

declare(strict_types=1);

/**
 * AttendQR – AsistenciaService
 *
 * Responsabilidad: lógica de negocio del módulo de asistencias.
 * Flujo: AsistenciaController → AsistenciaService → AsistenciaRepository / SesionRepository / AprendizRepository → Database
 *
 * Ubicación en el proyecto: Src/Services/AsistenciaService.php
 */
class AsistenciaService
{
    private AsistenciaRepository $asistenciaRepo;
    private SesionRepository     $sesionRepo;
    private AprendizRepository   $aprendizRepo;

    public function __construct()
    {
        $this->asistenciaRepo = new AsistenciaRepository();
        $this->sesionRepo     = new SesionRepository();
        $this->aprendizRepo   = new AprendizRepository();
    }

    /**
     * Registra la asistencia de un aprendiz en una sesión activa.
     *
     * Reglas de negocio:
     *   1. La sesión debe existir y estar en estado 'activa'.
     *   2. El aprendiz debe existir en el sistema.
     *   3. El aprendiz no debe haber registrado asistencia ya en esta sesión.
     *
     * @param int $idAprendiz Identificador del aprendiz.
     * @param int $idSesion   Identificador de la sesión activa.
     * @return array<string, mixed> Datos del registro de asistencia creado.
     * @throws \RuntimeException 404 si la sesión no existe.
     * @throws \RuntimeException 422 si la sesión no está activa.
     * @throws \RuntimeException 404 si el aprendiz no existe.
     * @throws \RuntimeException 409 si el aprendiz ya registró asistencia.
     */
    public function registrar(int $idAprendiz, int $idSesion): array
    {
        $sesion = $this->sesionRepo->obtenerPorId($idSesion);

        if ($sesion === null) {
            throw new \RuntimeException('Sesión no encontrada.', 404);
        }

        if ((string) $sesion['estado'] !== 'activa') {
            throw new \RuntimeException('La sesión no está activa.', 422);
        }

        $aprendiz = $this->aprendizRepo->obtenerPorId($idAprendiz);

        if ($aprendiz === null) {
            throw new \RuntimeException('Aprendiz no encontrado.', 404);
        }

        if ($this->asistenciaRepo->existeEnSesion($idAprendiz, $idSesion)) {
            throw new \RuntimeException('El aprendiz ya registró asistencia en esta sesión.', 409);
        }

        $fechaHora = date('Y-m-d H:i:s');
        $id        = $this->asistenciaRepo->crear($idAprendiz, $idSesion, $fechaHora);

        return [
            'id'          => $id,
            'id_aprendiz' => $idAprendiz,
            'id_sesion'   => $idSesion,
            'fecha_hora'  => $fechaHora,
        ];
    }

    /**
     * Obtiene los datos completos de un registro de asistencia por su ID.
     *
     * @param int $idAsistencia Identificador del registro.
     * @return array<string, mixed>
     * @throws \RuntimeException 404 si el registro no existe.
     */
    public function consultar(int $idAsistencia): array
    {
        $asistencia = $this->asistenciaRepo->obtenerPorId($idAsistencia);

        if ($asistencia === null) {
            throw new \RuntimeException('Registro de asistencia no encontrado.', 404);
        }

        return $asistencia;
    }

    /**
     * Retorna el historial de asistencias de un aprendiz con filtros opcionales.
     * Incluye el porcentaje de asistencia calculado sobre el total de sesiones.
     *
     * @param int         $idAprendiz  Identificador del aprendiz.
     * @param int|null    $idMateria   Filtro opcional por materia.
     * @param string|null $fechaInicio Inicio del rango (Y-m-d).
     * @param string|null $fechaFin    Fin del rango (Y-m-d).
     * @return array<string, mixed>
     * @throws \RuntimeException 404 si el aprendiz no existe.
     */
    public function historial(
        int     $idAprendiz,
        ?int    $idMateria   = null,
        ?string $fechaInicio = null,
        ?string $fechaFin    = null
    ): array {
        $aprendiz = $this->aprendizRepo->obtenerPorId($idAprendiz);

        if ($aprendiz === null) {
            throw new \RuntimeException('Aprendiz no encontrado.', 404);
        }

        $registros = $this->asistenciaRepo->historialAprendiz($idAprendiz, $idMateria, $fechaInicio, $fechaFin);
        $total     = count($registros);

        return [
            'aprendiz'   => $aprendiz,
            'registros'  => $registros,
            'total'      => $total,
            'filtros'    => [
                'id_materia'   => $idMateria,
                'fecha_inicio' => $fechaInicio,
                'fecha_fin'    => $fechaFin,
            ],
        ];
    }

    /**
     * Verifica si un aprendiz ya registró asistencia en una sesión determinada.
     *
     * @param int $idAprendiz Identificador del aprendiz.
     * @param int $idSesion   Identificador de la sesión.
     * @return array<string, mixed>
     */
    public function validar(int $idAprendiz, int $idSesion): array
    {
        $yaRegistrado = $this->asistenciaRepo->existeEnSesion($idAprendiz, $idSesion);

        return [
            'id_aprendiz'  => $idAprendiz,
            'id_sesion'    => $idSesion,
            'ya_registrado' => $yaRegistrado,
        ];
    }

    /**
     * Elimina un registro de asistencia por su ID.
     *
     * @param int $idAsistencia Identificador del registro a eliminar.
     * @return array<string, mixed>
     * @throws \RuntimeException 404 si el registro no existe.
     */
    public function eliminar(int $idAsistencia): array
    {
        $asistencia = $this->asistenciaRepo->obtenerPorId($idAsistencia);

        if ($asistencia === null) {
            throw new \RuntimeException('Registro de asistencia no encontrado.', 404);
        }

        $this->asistenciaRepo->eliminar($idAsistencia);

        return ['success' => true, 'message' => 'Asistencia eliminada correctamente.'];
    }
}