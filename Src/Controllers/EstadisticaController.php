<?php

declare(strict_types=1);

/**
 * AttendQR – EstadisticaController
 *
 * Responsabilidad: exponer métricas, reportes y datos agregados del sistema.
 * Este controlador es de solo lectura: no crea ni modifica registros,
 * únicamente consulta y presenta información procesada por el Service.
 *
 * Arquitectura:
 *   api.php → EstadisticaController → [EstadisticaService] → [Repositories] → [Modelos]
 *
 * Rutas que maneja este controlador:
 *   GET /api/estadisticas/resumen          → resumen general del sistema
 *   GET /api/estadisticas/dashboard        → datos agregados para el panel principal
 *   GET /api/estadisticas/asistencia       → métricas de asistencia (por ficha, docente, etc.)
 *   GET /api/estadisticas/consultar/{id}   → estadísticas detalladas de una entidad específica
 *
 * Nota: todas las acciones son GET porque este controlador solo expone datos.
 *
 * Convención de parámetros posicionales:
 *   $params[0] → ID de la entidad consultada (cuando aplica)
 *
 * Ubicación en el proyecto: Src/Controllers/EstadisticaController.php
 */
class EstadisticaController
{
    // -------------------------------------------------------------------------
    // Punto de entrada principal
    // -------------------------------------------------------------------------

    /**
     * Recibe la petición del router y la despacha a la acción correspondiente.
     *
     * No contiene lógica de negocio: únicamente enruta y delega.
     *
     * @param string   $metodo  Método HTTP recibido (se requiere GET para todas las acciones)
     * @param string   $accion  Segundo segmento de la URL (/api/estadisticas/{accion})
     * @param string[] $params  Parámetros posicionales adicionales (p. ej. IDs)
     */
    public function handle(string $metodo, string $accion, array $params): void
    {
        match ($accion) {

            // Resumen general de actividad del sistema — solo GET
            'resumen' => $this->despacharConMetodo($metodo, 'GET',
                fn() => $this->resumen()
            ),

            // Datos agregados para el panel principal (dashboard) — solo GET
            'dashboard' => $this->despacharConMetodo($metodo, 'GET',
                fn() => $this->dashboard()
            ),

            // Métricas de asistencia con filtros opcionales — solo GET
            'asistencia' => $this->despacharConMetodo($metodo, 'GET',
                fn() => $this->asistencia()
            ),

            // Estadísticas detalladas de una entidad por ID — solo GET
            'consultar' => $this->despacharConMetodo($metodo, 'GET',
                fn() => $this->consultar(
                    $this->extraerIdRequerido($params, 'entidad')
                )
            ),

            // Acción no reconocida → 404
            default => $this->responderError(
                "Acción '{$accion}' no encontrada en EstadisticaController.",
                404
            ),
        };
    }

    // -------------------------------------------------------------------------
    // Acciones del controlador
    // -------------------------------------------------------------------------

    /**
     * Acción: resumen general de actividad del sistema.
     *
     * Devuelve contadores globales: total de aprendices activos,
     * sesiones abiertas hoy, asistencias registradas en el día, etc.
     * Útil como vista de alto nivel del estado del sistema.
     *
     * GET /api/estadisticas/resumen
     */
    private function resumen(): void
    {
        // ► AQUÍ: llamar a EstadisticaService->resumenGeneral()
        // El servicio consultará múltiples Repositories para agregar
        // contadores globales en una sola respuesta optimizada.
        //
        // Ejemplo futuro:
        //   $servicio = new EstadisticaService();
        //   $resumen  = $servicio->resumenGeneral();
        //   $this->responderExito('Resumen obtenido correctamente.', $resumen);

        $this->responderExito('Endpoint resumen disponible.', []);
    }

    /**
     * Acción: datos agregados para el panel principal.
     *
     * Devuelve la información necesaria para renderizar el dashboard:
     * sesiones activas, gráficas de asistencia de los últimos 7 días,
     * fichas con mayor y menor asistencia, alertas de inasistencia, etc.
     *
     * GET /api/estadisticas/dashboard
     * Query params opcionales: ?id_docente=3&id_ficha=15&trimestre=1
     */
    private function dashboard(): void
    {
        // Leer filtros de contexto opcionales desde la query string
        $idDocente   = isset($_GET['id_docente']) ? (int) $_GET['id_docente'] : null;
        $idFicha     = isset($_GET['id_ficha'])   ? (int) $_GET['id_ficha']   : null;
        $trimestre   = isset($_GET['trimestre'])  ? (int) $_GET['trimestre']  : null;

        // ► AQUÍ: llamar a EstadisticaService->dashboard($idDocente, $idFicha, $trimestre)
        // El servicio consultará AsistenciaRepository, SesionRepository y FichaRepository
        // para construir el objeto de respuesta del dashboard.
        //
        // Ejemplo futuro:
        //   $servicio   = new EstadisticaService();
        //   $dashboard  = $servicio->dashboard($idDocente, $idFicha, $trimestre);
        //   $this->responderExito('Dashboard obtenido correctamente.', $dashboard);

        $this->responderExito('Endpoint dashboard disponible.', [
            'filtro_docente'   => $idDocente,
            'filtro_ficha'     => $idFicha,
            'filtro_trimestre' => $trimestre,
        ]);
    }

    /**
     * Acción: métricas de asistencia con filtros opcionales.
     *
     * Devuelve indicadores de asistencia: porcentaje de asistencia por ficha,
     * aprendices con más del 80% de asistencia, alertas de inasistencia acumulada, etc.
     *
     * GET /api/estadisticas/asistencia
     * Query params opcionales: ?id_ficha=15&id_docente=3&fecha_inicio=2025-03-01&fecha_fin=2025-06-30
     */
    private function asistencia(): void
    {
        $idFicha       = isset($_GET['id_ficha'])    ? (int) $_GET['id_ficha']    : null;
        $idDocente     = isset($_GET['id_docente'])  ? (int) $_GET['id_docente']  : null;
        $fechaInicio   = $_GET['fecha_inicio'] ?? null;
        $fechaFin      = $_GET['fecha_fin']    ?? null;

        // ► AQUÍ: llamar a EstadisticaService->metricasAsistencia(...)
        // El servicio calculará los indicadores usando AsistenciaRepository
        // y SesionRepository con los filtros aplicados.
        //
        // Ejemplo futuro:
        //   $servicio  = new EstadisticaService();
        //   $metricas  = $servicio->metricasAsistencia($idFicha, $idDocente, $fechaInicio, $fechaFin);
        //   $this->responderExito('Métricas obtenidas correctamente.', $metricas);

        $this->responderExito('Endpoint asistencia disponible.', [
            'filtro_ficha'    => $idFicha,
            'filtro_docente'  => $idDocente,
            'fecha_inicio'    => $fechaInicio,
            'fecha_fin'       => $fechaFin,
        ]);
    }

    /**
     * Acción: estadísticas detalladas de una entidad específica.
     *
     * Devuelve el historial completo de asistencia asociado a una entidad
     * (aprendiz, ficha o docente) identificada por su ID.
     * El tipo de entidad se determina mediante un query param.
     *
     * GET /api/estadisticas/consultar/{id}
     * Query params opcionales: ?tipo=aprendiz   (aprendiz | ficha | docente)
     *
     * @param int $idEntidad Identificador único de la entidad consultada.
     */
    private function consultar(int $idEntidad): void
    {
        // El tipo de entidad define qué Repository consultará el Service
        $tipoEntidad = $_GET['tipo'] ?? 'aprendiz';

        // ► AQUÍ: llamar a EstadisticaService->detallePorEntidad($idEntidad, $tipoEntidad)
        // El servicio seleccionará el Repository adecuado según $tipoEntidad
        // y retornará las estadísticas completas de esa entidad.
        //
        // Ejemplo futuro:
        //   $servicio     = new EstadisticaService();
        //   $estadisticas = $servicio->detallePorEntidad($idEntidad, $tipoEntidad);
        //   $this->responderExito('Estadísticas obtenidas correctamente.', $estadisticas);

        $this->responderExito('Endpoint consultar estadísticas disponible.', [
            'id_entidad'   => $idEntidad,
            'tipo_entidad' => $tipoEntidad,
        ]);
    }

    // -------------------------------------------------------------------------
    // Métodos auxiliares internos
    // -------------------------------------------------------------------------

    /**
     * Verifica el método HTTP y ejecuta el callback si es correcto.
     * Si el método no coincide, responde con 405 y detiene la ejecución.
     *
     * @param string   $metodoRecibido  Método HTTP que llegó en la petición.
     * @param string   $metodoEsperado  Método HTTP que esta acción requiere.
     * @param callable $callback        Función a ejecutar si el método es válido.
     */
    private function despacharConMetodo(
        string   $metodoRecibido,
        string   $metodoEsperado,
        callable $callback
    ): void {
        if ($metodoRecibido !== $metodoEsperado) {
            header('Allow: ' . $metodoEsperado);
            $this->responderError(
                "Este endpoint solo acepta {$metodoEsperado}, se recibió {$metodoRecibido}.",
                405
            );
        }

        $callback();
    }

    /**
     * Extrae y valida un ID numérico desde los parámetros posicionales.
     * Si no existe o no es un entero positivo, responde con 400.
     *
     * @param string[] $params        Parámetros posicionales del router.
     * @param string   $nombreEntidad Nombre de la entidad (para el mensaje de error).
     * @return int ID validado y convertido a entero.
     */
    private function extraerIdRequerido(array $params, string $nombreEntidad): int
    {
        if (empty($params[0]) || !ctype_digit((string) $params[0])) {
            $this->responderError(
                "Se requiere un ID numérico válido para {$nombreEntidad}.",
                400
            );
        }

        return (int) $params[0];
    }

    /**
     * Envía una respuesta JSON de éxito y detiene la ejecución.
     *
     * Formato: { "success": true, "message": "...", "data": { ... } }
     *
     * @param string               $mensaje Descripción legible del resultado.
     * @param array<string, mixed> $datos   Payload de la respuesta.
     * @param int                  $codigo  Código HTTP de respuesta.
     */
    private function responderExito(string $mensaje, array $datos = [], int $codigo = 200): never
    {
        http_response_code($codigo);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'success' => true,
            'message' => $mensaje,
            'data'    => $datos,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Envía una respuesta JSON de error y detiene la ejecución.
     *
     * Formato: { "success": false, "message": "..." }
     *
     * @param string $mensaje Descripción legible del error.
     * @param int    $codigo  Código HTTP de error.
     */
    private function responderError(string $mensaje, int $codigo): never
    {
        http_response_code($codigo);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'success' => false,
            'message' => $mensaje,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}