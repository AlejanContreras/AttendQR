<?php

declare(strict_types=1);

/**
 * AttendQR – AsistenciaService
 *
 * Responsabilidad: contener toda la lógica de negocio relacionada
 * con el registro y consulta de asistencias de los aprendices.
 *
 * Esta clase NO debe:
 *   - Ejecutar SQL directamente.
 *   - Conocer el router ni los Controllers.
 *   - Acceder a $_POST, $_GET ni $_REQUEST.
 *   - Imprimir JSON ni HTML.
 *
 * Flujo esperado cuando se integren las capas inferiores:
 *   AsistenciaController → AsistenciaService → AsistenciaRepository → Modelo → Database
 *
 * Ubicación en el proyecto: Src/Services/AsistenciaService.php
 */
class AsistenciaService
{
    // -------------------------------------------------------------------------
    // Dependencias (se inyectarán cuando se creen los Repositories)
    // -------------------------------------------------------------------------

    // ► AQUÍ: declarar dependencias cuando existan los Repositories
    //
    // Ejemplo futuro:
    //   private AsistenciaRepository $asistenciaRepo;
    //   private SesionRepository     $sesionRepo;
    //   private AprendizRepository   $aprendizRepo;
    //
    //   public function __construct(
    //       AsistenciaRepository $asistenciaRepo,
    //       SesionRepository     $sesionRepo,
    //       AprendizRepository   $aprendizRepo
    //   ) {
    //       $this->asistenciaRepo = $asistenciaRepo;
    //       $this->sesionRepo     = $sesionRepo;
    //       $this->aprendizRepo   = $aprendizRepo;
    //   }

    // -------------------------------------------------------------------------
    // Métodos públicos del servicio
    // -------------------------------------------------------------------------

    /**
     * Registra la asistencia de un aprendiz en una sesión activa.
     *
     * Reglas de negocio que este método deberá aplicar:
     *   1. Verificar que la sesión existe y su estado es 'activa'.
     *   2. Verificar que el aprendiz existe y está activo en el sistema.
     *   3. Verificar que el aprendiz pertenece a la ficha vinculada a la sesión.
     *   4. Verificar que el aprendiz no haya registrado asistencia ya en esta sesión.
     *   5. Crear el registro de asistencia con la fecha y hora actuales.
     *   6. Retornar el objeto asistencia creado.
     *
     * @param int $idAprendiz Identificador único del aprendiz.
     * @param int $idSesion   Identificador único de la sesión activa.
     * @return array<string, mixed> Datos del registro de asistencia creado.
     */
    public function registrar(int $idAprendiz, int $idSesion): array
    {
        // ► AQUÍ: llamar a SesionRepository->obtenerPorId($idSesion)
        // ► AQUÍ: verificar que $sesion['estado'] === 'activa'
        // ► AQUÍ: llamar a AprendizRepository->obtenerPorId($idAprendiz)
        // ► AQUÍ: verificar que el aprendiz pertenece a la ficha de la sesión
        // ► AQUÍ: llamar a AsistenciaRepository->existeEnSesion($idAprendiz, $idSesion)
        // ► AQUÍ: si ya existe, lanzar excepción 'El aprendiz ya registró asistencia.'
        // ► AQUÍ: llamar a AsistenciaRepository->crear($idAprendiz, $idSesion, now())
        //
        // Ejemplo futuro:
        //   $sesion = $this->sesionRepo->obtenerPorId($idSesion);
        //   if ($sesion['estado'] !== 'activa') {
        //       throw new \RuntimeException('La sesión no está activa.', 422);
        //   }
        //   if ($this->asistenciaRepo->existeEnSesion($idAprendiz, $idSesion)) {
        //       throw new \RuntimeException('El aprendiz ya registró asistencia en esta sesión.', 409);
        //   }
        //   return $this->asistenciaRepo->crear($idAprendiz, $idSesion, date('Y-m-d H:i:s'));

        return [
            'success'     => true,
            'message'     => 'AsistenciaService::registrar() disponible. Pendiente de implementación.',
            'id_aprendiz' => $idAprendiz,
            'id_sesion'   => $idSesion,
        ];
    }

    /**
     * Obtiene los datos completos de un registro de asistencia por su ID.
     *
     * Reglas de negocio que este método deberá aplicar:
     *   1. Buscar el registro en la base de datos.
     *   2. Si no existe, lanzar una excepción (→ 404 en el Controller).
     *   3. Retornar los datos del registro con la información relacionada
     *      (nombre del aprendiz, nombre de la sesión, materia, etc.).
     *
     * @param int $idAsistencia Identificador único del registro de asistencia.
     * @return array<string, mixed> Datos completos del registro.
     */
    public function consultar(int $idAsistencia): array
    {
        // ► AQUÍ: llamar a AsistenciaRepository->obtenerPorId($idAsistencia)
        // ► AQUÍ: si no existe, lanzar new \RuntimeException('Asistencia no encontrada.', 404)
        //
        // Ejemplo futuro:
        //   $asistencia = $this->asistenciaRepo->obtenerPorId($idAsistencia);
        //   if (!$asistencia) {
        //       throw new \RuntimeException('Registro de asistencia no encontrado.', 404);
        //   }
        //   return $asistencia;

        return [
            'success'        => true,
            'message'        => 'AsistenciaService::consultar() disponible. Pendiente de implementación.',
            'id_asistencia'  => $idAsistencia,
        ];
    }

    /**
     * Obtiene el historial de asistencias de un aprendiz con filtros opcionales.
     *
     * Reglas de negocio que este método deberá aplicar:
     *   1. Verificar que el aprendiz existe.
     *   2. Aplicar los filtros recibidos (materia, rango de fechas).
     *   3. Calcular el porcentaje de asistencia dentro del rango indicado.
     *   4. Retornar el listado ordenado por fecha descendente.
     *
     * @param int         $idAprendiz   Identificador único del aprendiz.
     * @param int|null    $idMateria    Filtro opcional por materia.
     * @param string|null $fechaInicio  Filtro opcional de fecha de inicio (Y-m-d).
     * @param string|null $fechaFin     Filtro opcional de fecha de fin (Y-m-d).
     * @return array<string, mixed> Historial de asistencias y porcentaje calculado.
     */
    public function historial(
        int     $idAprendiz,
        ?int    $idMateria   = null,
        ?string $fechaInicio = null,
        ?string $fechaFin    = null
    ): array {
        // ► AQUÍ: llamar a AprendizRepository->obtenerPorId($idAprendiz)
        // ► AQUÍ: llamar a AsistenciaRepository->historialAprendiz(...)
        // ► AQUÍ: calcular el porcentaje de asistencia sobre el total de sesiones
        //
        // Ejemplo futuro:
        //   $registros   = $this->asistenciaRepo->historialAprendiz($idAprendiz, $idMateria, $fechaInicio, $fechaFin);
        //   $porcentaje  = $this->calcularPorcentaje($registros['asistidas'], $registros['totales']);
        //   return ['registros' => $registros['lista'], 'porcentaje' => $porcentaje];

        return [
            'success'      => true,
            'message'      => 'AsistenciaService::historial() disponible. Pendiente de implementación.',
            'id_aprendiz'  => $idAprendiz,
            'filtros'      => [
                'id_materia'   => $idMateria,
                'fecha_inicio' => $fechaInicio,
                'fecha_fin'    => $fechaFin,
            ],
        ];
    }

    /**
     * Verifica si un aprendiz ya registró asistencia en una sesión determinada.
     *
     * Reglas de negocio que este método deberá aplicar:
     *   1. Consultar si existe un registro de asistencia para la combinación
     *      aprendiz + sesión.
     *   2. Retornar true si ya existe, false si no.
     *   3. No lanzar excepciones: esta consulta siempre debe responder.
     *
     * @param int $idAprendiz Identificador único del aprendiz.
     * @param int $idSesion   Identificador único de la sesión.
     * @return array<string, mixed> Indicador de si ya existe el registro.
     */
    public function validar(int $idAprendiz, int $idSesion): array
    {
        // ► AQUÍ: llamar a AsistenciaRepository->existeEnSesion($idAprendiz, $idSesion)
        //
        // Ejemplo futuro:
        //   $yaRegistrado = $this->asistenciaRepo->existeEnSesion($idAprendiz, $idSesion);
        //   return ['ya_registrado' => $yaRegistrado];

        return [
            'success'     => true,
            'message'     => 'AsistenciaService::validar() disponible. Pendiente de implementación.',
            'id_aprendiz' => $idAprendiz,
            'id_sesion'   => $idSesion,
        ];
    }

    // -------------------------------------------------------------------------
    // Métodos privados de apoyo
    // -------------------------------------------------------------------------

    /**
     * Calcula el porcentaje de asistencia sobre el total de sesiones realizadas.
     *
     * ► AQUÍ: implementar cuando los datos reales estén disponibles.
     *
     * @param int $sesionesAsistidas Número de sesiones a las que asistió el aprendiz.
     * @param int $totalSesiones     Total de sesiones realizadas en el período.
     * @return float Porcentaje de asistencia redondeado a dos decimales.
     */
    private function calcularPorcentaje(int $sesionesAsistidas, int $totalSesiones): float
    {
        if ($totalSesiones === 0) {
            return 0.0;
        }

        return round(($sesionesAsistidas / $totalSesiones) * 100, 2);
    }
}