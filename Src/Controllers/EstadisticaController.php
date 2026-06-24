<?php

declare(strict_types=1);

class EstadisticaController
{
    private EstadisticaService $servicio;

    public function __construct()
    {
        $this->servicio = new EstadisticaService();
    }

    public function handle(string $metodo, string $accion, array $params): void
    {
        match ($accion) {
            'resumen' => $this->despacharConMetodo($metodo, 'GET',
                fn() => $this->resumen()
            ),
            'dashboard' => $this->despacharConMetodo($metodo, 'GET',
                fn() => $this->dashboard()
            ),
            'asistencia' => $this->despacharConMetodo($metodo, 'GET',
                fn() => $this->asistencia()
            ),
            'consultar' => $this->despacharConMetodo($metodo, 'GET',
                fn() => $this->consultar($this->extraerIdRequerido($params, 'entidad'))
            ),
            default => $this->responderError(
                "Acción '{$accion}' no encontrada en EstadisticaController.", 404
            ),
        };
    }

    // -------------------------------------------------------------------------
    // Acciones
    // -------------------------------------------------------------------------

    /**
     * GET /api/estadisticas/resumen
     */
    private function resumen(): void
    {
        try {
            $resultado = $this->servicio->resumen();
            $this->responderExito('Resumen obtenido correctamente.', $resultado);
        } catch (\RuntimeException $e) {
            $this->responderError($e->getMessage(), $e->getCode() ?: 400);
        } catch (\Throwable $e) {
            $this->responderError('Error interno al obtener el resumen.', 500);
        }
    }

    /**
     * GET /api/estadisticas/dashboard
     * Query params opcionales: ?id_docente=3&id_ficha=15&trimestre=1
     */
    private function dashboard(): void
    {
        $idDocente = isset($_GET['id_docente']) ? (int) $_GET['id_docente'] : null;
        $idFicha   = isset($_GET['id_ficha'])   ? (int) $_GET['id_ficha']   : null;
        $trimestre = isset($_GET['trimestre'])  ? (int) $_GET['trimestre']  : null;

        try {
            $resultado = $this->servicio->dashboard($idDocente, $idFicha, $trimestre);
            $this->responderExito('Dashboard obtenido correctamente.', $resultado);
        } catch (\RuntimeException $e) {
            $this->responderError($e->getMessage(), $e->getCode() ?: 400);
        } catch (\Throwable $e) {
            $this->responderError('Error interno al obtener el dashboard.', 500);
        }
    }

    /**
     * GET /api/estadisticas/asistencia
     * Query params opcionales: ?id_ficha=15&id_docente=3&fecha_inicio=2025-03-01&fecha_fin=2025-06-30
     */
    private function asistencia(): void
    {
        $idFicha     = isset($_GET['id_ficha'])    ? (int) $_GET['id_ficha']    : null;
        $idDocente   = isset($_GET['id_docente'])  ? (int) $_GET['id_docente']  : null;
        $fechaInicio = $_GET['fecha_inicio'] ?? null;
        $fechaFin    = $_GET['fecha_fin']    ?? null;

        try {
            $resultado = $this->servicio->asistencia($idFicha, $idDocente, $fechaInicio, $fechaFin);
            $this->responderExito('Métricas de asistencia obtenidas correctamente.', $resultado);
        } catch (\RuntimeException $e) {
            $this->responderError($e->getMessage(), $e->getCode() ?: 400);
        } catch (\Throwable $e) {
            $this->responderError('Error interno al obtener métricas de asistencia.', 500);
        }
    }

    /**
     * GET /api/estadisticas/consultar/{idEntidad}
     * Query params opcionales: ?tipo=aprendiz  (aprendiz | ficha | docente)
     */
    private function consultar(int $idEntidad): void
    {
        $tipo = $_GET['tipo'] ?? 'aprendiz';

        try {
            $resultado = $this->servicio->consultar($idEntidad, $tipo);
            $this->responderExito('Estadísticas obtenidas correctamente.', $resultado);
        } catch (\RuntimeException $e) {
            $this->responderError($e->getMessage(), $e->getCode() ?: 400);
        } catch (\Throwable $e) {
            $this->responderError('Error interno al consultar estadísticas.', 500);
        }
    }

    // -------------------------------------------------------------------------
    // Auxiliares
    // -------------------------------------------------------------------------

    private function despacharConMetodo(string $metodoRecibido, string $metodoEsperado, callable $callback): void
    {
        if ($metodoRecibido !== $metodoEsperado) {
            header('Allow: ' . $metodoEsperado);
            $this->responderError(
                "Este endpoint solo acepta {$metodoEsperado}, se recibió {$metodoRecibido}.", 405
            );
        }
        $callback();
    }

    private function extraerIdRequerido(array $params, string $nombreEntidad): int
    {
        if (empty($params[0]) || !ctype_digit((string) $params[0])) {
            $this->responderError("Se requiere un ID numérico válido para {$nombreEntidad}.", 400);
        }
        return (int) $params[0];
    }

    private function responderExito(string $mensaje, array $datos = [], int $codigo = 200): never
    {
        http_response_code($codigo);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(
            ['success' => true, 'message' => $mensaje, 'data' => $datos],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
        exit;
    }

    private function responderError(string $mensaje, int $codigo): never
    {
        http_response_code($codigo);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(
            ['success' => false, 'message' => $mensaje],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
        exit;
    }
}