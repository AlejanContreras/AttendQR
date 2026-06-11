<?php

declare(strict_types=1);

/**
 * AttendQR – EstadisticaService
 *
 * Responsabilidad: calcular y agregar métricas del sistema.
 * Este servicio es de solo lectura: consulta datos de múltiples
 * Repositories y los transforma en reportes listos para el Controller.
 *
 * Esta clase NO debe:
 *   - Ejecutar SQL directamente.
 *   - Conocer el router ni los Controllers.
 *   - Acceder a $_POST, $_GET ni $_REQUEST.
 *   - Imprimir JSON, HTML ni usar header() o exit.
 *
 * Flujo esperado:
 *   EstadisticaController → EstadisticaService → [Múltiples Repositories] → Database
 *
 * Ubicación en el proyecto: Src/Services/EstadisticaService.php
 */
class EstadisticaService
{
    // -------------------------------------------------------------------------
    // Dependencias (se inyectarán cuando existan los Repositories)
    // -------------------------------------------------------------------------

    // ► AQUÍ: declarar dependencias (este servicio requiere múltiples Repositories)
    //
    // Ejemplo futuro:
    //   private AsistenciaRepository $asistenciaRepo;
    //   private SesionRepository     $sesionRepo;
    //   private AprendizRepository   $aprendizRepo;
    //   private FichaRepository      $fichaRepo;
    //
    //   public function __construct(
    //       AsistenciaRepository $asistenciaRepo,
    //       SesionRepository     $sesionRepo,
    //       AprendizRepository   $aprendizRepo,
    //       FichaRepository      $fichaRepo
    //   ) {
    //       $this->asistenciaRepo = $asistenciaRepo;
    //       $this->sesionRepo     = $sesionRepo;
    //       $this->aprendizRepo   = $aprendizRepo;
    //       $this->fichaRepo      = $fichaRepo;
    //   }

    // -------------------------------------------------------------------------
    // Métodos públicos
    // -------------------------------------------------------------------------

    /**
     * Genera un resumen general de actividad del sistema.
     *
     * Métricas que este método deberá calcular:
     *   - Total de aprendices activos.
     *   - Total de docentes activos.
     *   - Total de fichas activas.
     *   - Sesiones abiertas en este momento.
     *   - Asistencias registradas hoy.
     *
     * @return array<string, mixed>
     */
    public function resumen(): array
    {
        // ► AQUÍ: llamar a AprendizRepository->contarActivos()
        // ► AQUÍ: llamar a DocenteRepository->contarActivos()
        // ► AQUÍ: llamar a FichaRepository->contarActivas()
        // ► AQUÍ: llamar a SesionRepository->contarActivas()
        // ► AQUÍ: llamar a AsistenciaRepository->contarHoy()

        return [
            'success' => true,
            'message' => 'EstadisticaService::resumen() disponible. Pendiente de implementación.',
        ];
    }

    /**
     * Construye los datos del panel principal (dashboard).
     *
     * Métricas que este método deberá calcular:
     *   - Porcentaje de asistencia global de los últimos 7 días.
     *   - Fichas con mayor y menor porcentaje de asistencia.
     *   - Alertas de aprendices con asistencia por debajo del 80%.
     *   - Gráfica de asistencia diaria para el período indicado.
     *
     * @param int|null $idDocente  Filtro opcional por docente.
     * @param int|null $idFicha    Filtro opcional por ficha.
     * @param int|null $trimestre  Filtro opcional por trimestre.
     * @return array<string, mixed>
     */
    public function dashboard(?int $idDocente = null, ?int $idFicha = null, ?int $trimestre = null): array
    {
        // ► AQUÍ: construir el rango de fechas del trimestre si fue indicado
        // ► AQUÍ: llamar a AsistenciaRepository->porcentajeGlobal($idDocente, $idFicha, $fechaInicio, $fechaFin)
        // ► AQUÍ: llamar a AsistenciaRepository->fichasConMasYMenosAsistencia()
        // ► AQUÍ: llamar a AsistenciaRepository->aprendicesBajoUmbral(80)
        // ► AQUÍ: llamar a AsistenciaRepository->asistenciaDiaria($fechaInicio, $fechaFin)

        return [
            'success'          => true,
            'message'          => 'EstadisticaService::dashboard() disponible. Pendiente de implementación.',
            'filtro_docente'   => $idDocente,
            'filtro_ficha'     => $idFicha,
            'filtro_trimestre' => $trimestre,
        ];
    }

    /**
     * Calcula métricas de asistencia con filtros opcionales.
     *
     * Métricas que este método deberá calcular:
     *   - Porcentaje de asistencia por ficha dentro del rango de fechas.
     *   - Aprendices con más del 80% de asistencia.
     *   - Aprendices con menos del 80% (en riesgo de perder la formación).
     *   - Total de sesiones realizadas y total de asistencias en el período.
     *
     * @param int|null    $idFicha     Filtro opcional por ficha.
     * @param int|null    $idDocente   Filtro opcional por docente.
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
        // ► AQUÍ: llamar a AsistenciaRepository->metricasPorFicha($idFicha, $idDocente, $fechaInicio, $fechaFin)
        // ► AQUÍ: llamar a $this->calcularPorcentaje() por cada ficha del resultado

        return [
            'success'         => true,
            'message'         => 'EstadisticaService::asistencia() disponible. Pendiente de implementación.',
            'filtro_ficha'    => $idFicha,
            'filtro_docente'  => $idDocente,
            'fecha_inicio'    => $fechaInicio,
            'fecha_fin'       => $fechaFin,
        ];
    }

    /**
     * Obtiene estadísticas detalladas de una entidad específica.
     *
     * El tipo de entidad determina qué Repository se consulta:
     *   - 'aprendiz'  → historial y porcentaje de asistencia del aprendiz.
     *   - 'ficha'     → estadísticas globales de todos los aprendices de la ficha.
     *   - 'docente'   → resumen de sesiones y asistencias del docente.
     *
     * @param int    $idEntidad   Identificador único de la entidad.
     * @param string $tipoEntidad Tipo de entidad ('aprendiz' | 'ficha' | 'docente').
     * @return array<string, mixed>
     */
    public function consultar(int $idEntidad, string $tipoEntidad = 'aprendiz'): array
    {
        $tiposPermitidos = ['aprendiz', 'ficha', 'docente'];

        if (!in_array($tipoEntidad, $tiposPermitidos, true)) {
            return [
                'success' => false,
                'message' => "Tipo de entidad '{$tipoEntidad}' no válido. Valores permitidos: "
                    . implode(', ', $tiposPermitidos) . '.',
            ];
        }

        // ► AQUÍ: seleccionar el Repository adecuado según $tipoEntidad
        // ► AQUÍ: llamar al método correspondiente con $idEntidad
        //
        // Ejemplo futuro:
        //   return match ($tipoEntidad) {
        //       'aprendiz' => $this->asistenciaRepo->estadisticasAprendiz($idEntidad),
        //       'ficha'    => $this->asistenciaRepo->estadisticasFicha($idEntidad),
        //       'docente'  => $this->sesionRepo->estadisticasDocente($idEntidad),
        //   };

        return [
            'success'      => true,
            'message'      => 'EstadisticaService::consultar() disponible. Pendiente de implementación.',
            'id_entidad'   => $idEntidad,
            'tipo_entidad' => $tipoEntidad,
        ];
    }

    // -------------------------------------------------------------------------
    // Métodos privados de apoyo
    // -------------------------------------------------------------------------

    /**
     * Calcula el porcentaje de asistencia redondeado a dos decimales.
     * Protege contra división por cero cuando no hay sesiones registradas.
     *
     * @param int $sesionesAsistidas Número de sesiones a las que asistió.
     * @param int $totalSesiones     Total de sesiones realizadas en el período.
     * @return float Porcentaje de asistencia (0.00 – 100.00).
     */
    private function calcularPorcentaje(int $sesionesAsistidas, int $totalSesiones): float
    {
        if ($totalSesiones === 0) {
            return 0.0;
        }

        return round(($sesionesAsistidas / $totalSesiones) * 100, 2);
    }
}