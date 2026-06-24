<?php

declare(strict_types=1);

class TrimestreController
{
    private TrimestreService $servicio;

    public function __construct()
    {
        $this->servicio = new TrimestreService();
    }

    public function handle(string $metodo, string $accion, array $params): void
    {
        match ($accion) {
            'listar' => $this->despacharConMetodo($metodo, 'GET',
                fn() => $this->listar()
            ),
            'consultar' => $this->despacharConMetodo($metodo, 'GET',
                fn() => $this->consultar($this->extraerIdRequerido($params, 'trimestre'))
            ),
            'crear' => $this->despacharConMetodo($metodo, 'POST',
                fn() => $this->crear()
            ),
            'actualizar' => $this->despacharConMetodo($metodo, 'PUT',
                fn() => $this->actualizar($this->extraerIdRequerido($params, 'trimestre'))
            ),
            'eliminar' => $this->despacharConMetodo($metodo, 'DELETE',
                fn() => $this->eliminar($this->extraerIdRequerido($params, 'trimestre'))
            ),
            default => $this->responderError(
                "Acción '{$accion}' no encontrada en TrimestreController.", 404
            ),
        };
    }

    // -------------------------------------------------------------------------
    // Acciones
    // -------------------------------------------------------------------------

    /**
     * GET /api/trimestres/listar
     * Query params opcionales: ?anio=2025&estado=activo
     */
    private function listar(): void
    {
        $anio   = isset($_GET['anio']) ? (int) $_GET['anio'] : null;
        $estado = $_GET['estado'] ?? null;

        try {
            $resultado = $this->servicio->listar($anio, $estado);
            $this->responderExito('Trimestres obtenidos correctamente.', $resultado);
        } catch (\RuntimeException $e) {
            $this->responderError($e->getMessage(), $e->getCode() ?: 400);
        } catch (\Throwable $e) {
            $this->responderError('Error interno al listar trimestres.', 500);
        }
    }

    /**
     * GET /api/trimestres/consultar/{idTrimestre}
     */
    private function consultar(int $idTrimestre): void
    {
        try {
            $trimestre = $this->servicio->consultar($idTrimestre);
            $this->responderExito('Trimestre encontrado.', $trimestre);
        } catch (\RuntimeException $e) {
            $this->responderError($e->getMessage(), $e->getCode() ?: 404);
        } catch (\Throwable $e) {
            $this->responderError('Error interno al consultar el trimestre.', 500);
        }
    }

    /**
     * POST /api/trimestres/crear
     * Body: { "nombre": "Trimestre I – 2025", "fecha_inicio": "2025-03-01", "fecha_fin": "2025-06-30" }
     */
    private function crear(): void
    {
        $cuerpo = $this->leerCuerpoJson();

        foreach (['nombre', 'fecha_inicio', 'fecha_fin'] as $campo) {
            if (empty($cuerpo[$campo])) {
                $this->responderError("El campo '{$campo}' es obligatorio.", 422);
            }
        }

        try {
            $trimestre = $this->servicio->crear(
                (string) $cuerpo['nombre'],
                (string) $cuerpo['fecha_inicio'],
                (string) $cuerpo['fecha_fin']
            );
            $this->responderExito('Trimestre creado correctamente.', $trimestre, 201);
        } catch (\RuntimeException $e) {
            $this->responderError($e->getMessage(), $e->getCode() ?: 400);
        } catch (\Throwable $e) {
            $this->responderError('Error interno al crear el trimestre.', 500);
        }
    }

    /**
     * PUT /api/trimestres/actualizar/{idTrimestre}
     * Body: campos a actualizar (parcial)
     */
    private function actualizar(int $idTrimestre): void
    {
        $cuerpo = $this->leerCuerpoJson();

        if (empty($cuerpo)) {
            $this->responderError('No se recibieron datos para actualizar.', 422);
        }

        try {
            $trimestre = $this->servicio->actualizar($idTrimestre, $cuerpo);
            $this->responderExito('Trimestre actualizado correctamente.', $trimestre);
        } catch (\RuntimeException $e) {
            $this->responderError($e->getMessage(), $e->getCode() ?: 400);
        } catch (\Throwable $e) {
            $this->responderError('Error interno al actualizar el trimestre.', 500);
        }
    }

    /**
     * DELETE /api/trimestres/eliminar/{idTrimestre}
     */
    private function eliminar(int $idTrimestre): void
    {
        try {
            $resultado = $this->servicio->eliminar($idTrimestre);
            $this->responderExito($resultado['message'] ?? 'Trimestre eliminado correctamente.', []);
        } catch (\RuntimeException $e) {
            $this->responderError($e->getMessage(), $e->getCode() ?: 404);
        } catch (\Throwable $e) {
            $this->responderError('Error interno al eliminar el trimestre.', 500);
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

    private function leerCuerpoJson(): array
    {
        $crudo = file_get_contents('php://input');

        if ($crudo === false || $crudo === '') {
            $this->responderError('El cuerpo de la petición está vacío.', 400);
        }

        $datos = json_decode($crudo, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->responderError('El cuerpo de la petición no es JSON válido.', 400);
        }

        return $datos ?? [];
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