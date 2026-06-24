<?php

declare(strict_types=1);

/**
 * AttendQR – EstadisticaService
 *
 * Responsabilidad: calcular y agregar métricas del sistema consultando
 * múltiples Repositories. Servicio de solo lectura.
 *
 * Flujo: EstadisticaController → EstadisticaService → [múltiples Repositories] → Database
 *
 * Ubicación en el proyecto: Src/Services/EstadisticaService.php
 */
class EstadisticaService
{
    private AsistenciaRepository $asistenciaRepo;
    private SesionRepository     $sesionRepo;
    private AprendizRepository   $aprendizRepo;
    private FichaRepository      $fichaRepo;
    private DocenteRepository    $docenteRepo;

    public function __construct()
    {
        $this->asistenciaRepo = new AsistenciaRepository();
        $this->sesionRepo     = new SesionRepository();
        $this->aprendizRepo   = new AprendizRepository();
        $this->fichaRepo      = new FichaRepository();
        $this->docenteRepo    = new DocenteRepository();
    }

    /**
     * Genera un resumen general de actividad del sistema.
     * Agrega contadores globales de las principales entidades.
     *
     * @return array<string, mixed>
     */
    public function resumen(): array
    {
        return [
            'aprendices_activos' => $this->aprendizRepo->contarActivosPorFicha(0) === 0
                ? $this->contarAprendicesActivos()
                : 0,
            'docentes_activos'   => $this->docenteRepo->contarActivos(),
            'fichas_activas'     => $this->fichaRepo->contarActivas(),
            'sesiones_activas'   => $this->sesionRepo->contarActivas(),
            'asistencias_hoy'    => $this->asistenciaRepo->contarHoy(),
        ];
    }

    /**
     * Construye los datos del panel principal (dashboard) con filtros opcionales.
     *
     * @param int|null $idDocente  Filtro por docente.
     * @param int|null $idFicha    Filtro por ficha.
     * @param int|null $trimestre  Filtro por trimestre (no usado aún en los Repos).
     * @return array<string, mixed>
     */
    public function dashboard(?int $idDocente = null, ?int $idFicha = null, ?int $trimestre = null): array
    {
        $sesionesActivas  = $this->sesionRepo->listar($idDocente, null, 'activa');
        $sesionesCerradas = $this->sesionRepo->listar($idDocente, null, 'cerrada');
        $asistenciasHoy   = $this->asistenciaRepo->contarHoy();

        return [
            'sesiones_activas'   => count($sesionesActivas),
            'sesiones_cerradas'  => count($sesionesCerradas),
            'asistencias_hoy'    => $asistenciasHoy,
            'filtros'            => [
                'id_docente' => $idDocente,
                'id_ficha'   => $idFicha,
                'trimestre'  => $trimestre,
            ],
        ];
    }

    /**
     * Retorna métricas de asistencia con filtros opcionales.
     *
     * @param int|null    $idFicha     Filtro por ficha.
     * @param int|null    $idDocente   Filtro por docente.
     * @param string|null $fechaInicio Inicio del período (Y-m-d).
     * @param string|null $fechaFin    Fin del período (Y-m-d).
     * @return array<string, mixed>
     */
    public function asistencia(
        ?int    $idFicha     = null,
        ?int    $idDocente   = null,
        ?string $fechaInicio = null,
        ?string $fechaFin    = null
    ): array {
        $sesiones    = $this->sesionRepo->listar($idDocente, null, 'cerrada');
        $totalSesiones = count($sesiones);

        return [
            'total_sesiones_cerradas' => $totalSesiones,
            'asistencias_hoy'         => $this->asistenciaRepo->contarHoy(),
            'filtros' => [
                'id_ficha'    => $idFicha,
                'id_docente'  => $idDocente,
                'fecha_inicio' => $fechaInicio,
                'fecha_fin'   => $fechaFin,
            ],
        ];
    }

    /**
     * Obtiene estadísticas detalladas de una entidad específica.
     *
     * @param int    $idEntidad   Identificador de la entidad.
     * @param string $tipoEntidad Tipo ('aprendiz' | 'ficha' | 'docente').
     * @return array<string, mixed>
     * @throws \RuntimeException 422 si el tipo de entidad no es válido.
     * @throws \RuntimeException 404 si la entidad no existe.
     */
    public function consultar(int $idEntidad, string $tipoEntidad = 'aprendiz'): array
    {
        $tiposPermitidos = ['aprendiz', 'ficha', 'docente'];

        if (!in_array($tipoEntidad, $tiposPermitidos, true)) {
            throw new \RuntimeException(
                "Tipo de entidad '{$tipoEntidad}' no válido. Valores permitidos: " . implode(', ', $tiposPermitidos) . '.',
                422
            );
        }

        switch ($tipoEntidad) {
            case 'aprendiz':
                $entidad = $this->aprendizRepo->obtenerPorId($idEntidad);
                if ($entidad === null) {
                    throw new \RuntimeException('Aprendiz no encontrado.', 404);
                }
                $historial = $this->asistenciaRepo->historialAprendiz($idEntidad);
                return [
                    'entidad'    => $entidad,
                    'historial'  => $historial,
                    'total'      => count($historial),
                ];

            case 'ficha':
                $entidad = $this->fichaRepo->obtenerPorId($idEntidad);
                if ($entidad === null) {
                    throw new \RuntimeException('Ficha no encontrada.', 404);
                }
                $aprendices = $this->aprendizRepo->listar($idEntidad, 'activo');
                return [
                    'entidad'    => $entidad,
                    'aprendices' => $aprendices,
                    'total'      => count($aprendices),
                ];

            case 'docente':
                $entidad = $this->docenteRepo->obtenerPorId($idEntidad);
                if ($entidad === null) {
                    throw new \RuntimeException('Docente no encontrado.', 404);
                }
                $sesiones = $this->sesionRepo->listar($idEntidad);
                return [
                    'entidad'  => $entidad,
                    'sesiones' => $sesiones,
                    'total'    => count($sesiones),
                ];
        }

        return [];
    }

    // -------------------------------------------------------------------------
    // Métodos privados de apoyo
    // -------------------------------------------------------------------------

    /**
     * Cuenta el total de aprendices activos en el sistema.
     * Consulta sin filtro de ficha listando todos y filtrando por estado.
     *
     * @return int
     */
    private function contarAprendicesActivos(): int
    {
        return count($this->aprendizRepo->listar(null, 'activo'));
    }

    /**
     * Calcula el porcentaje de asistencia con protección contra división por cero.
     *
     * @param int $asistidas Total de sesiones asistidas.
     * @param int $total     Total de sesiones realizadas.
     * @return float
     */
    private function calcularPorcentaje(int $asistidas, int $total): float
    {
        if ($total === 0) {
            return 0.0;
        }

        return round(($asistidas / $total) * 100, 2);
    }
}