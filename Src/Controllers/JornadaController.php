<?php

declare(strict_types=1);

class JornadaController
{
    private JornadaService $servicio;

    public function __construct()
    {
        $this->servicio = new JornadaService();
    }

    public function handle(string $metodo, string $accion, array $params): void
    {
        match ($accion) {
            'listar' => $this->despacharConMetodo($metodo, 'GET',
                fn() => $this->listar()
            ),
            'consultar' => $this->despacharConMetodo($metodo, 'GET',
                fn() => $this->consultar($this->extraerIdRequerido($params, 'jornada'))
            ),
            'crear' => $this->despacharConMetodo($metodo, 'POST',
                fn() => $this->crear()
            ),
            'actualizar' => $this->despacharConMetodo($metodo, 'PUT',
                fn() => $this->actualizar($this->extraerIdRequerido($params, 'jornada'))
            ),
            'eliminar' => $this->despacharConMetodo($metodo, 'DELETE',
                fn() => $this->eliminar($this->extraerIdRequerido($params, 'jornada'))
            ),
            default => $this->responderError(
                "Acción '{$accion}' no encontrada en JornadaController.", 404
            ),
        };
    }

    // -------------------------------------------------------------------------
    // Acciones
    // -------------------------------------------------------------------------

    /**
     * GET /api/jornadas/listar
     * Query params opcionales: ?estado=activa
     */
    private function listar(): void
    {
        $estado = $_GET['estado'] ?? null;

        try {
            $resultado = $this->servicio->listar($estado);
            $this->responderExito('Jornadas obtenidas correctamente.', $resultado);
        } catch (\RuntimeException $e) {
            $this->responderError($e->getMessage(), $e->getCode() ?: 400);
        } catch (\Throwable $e) {
            $this->responderError('Error interno al listar jornadas.', 500);
        }
    }

    /**
     * GET /api/jornadas/consultar/{idJornada}
     */
    private function consultar(int $idJornada): void
    {
        try {
            $jornada = $this->servicio->consultar($idJornada);
            $this->responderExito('Jornada encontrada.', $jornada);
        } catch (\RuntimeException $e) {
            $this->responderError($e->getMessage(), $e->getCode() ?: 404);
        } catch (\Throwable $e) {
            $this->responderError('Error interno al consultar la jornada.', 500);
        }
    }

    /**
     * POST /api/jornadas/crear
     * Body: { "nombre": "Mañana", "hora_inicio": "06:00", "hora_fin": "12:00" }
     */
    private function crear(): void
    {
        $cuerpo = $this->leerCuerpoJson();

        if (empty($cuerpo['nombre'])) {
            $this->responderError('El campo nombre es obligatorio.', 422);
        }

        try {
            $jornada = $this->servicio->crear(
                (string) $cuerpo['nombre'],
                isset($cuerpo['hora_inicio']) ? (string) $cuerpo['hora_inicio'] : null,
                isset($cuerpo['hora_fin'])    ? (string) $cuerpo['hora_fin']    : null
            );
            $this->responderExito('Jornada creada correctamente.', $jornada, 201);
        } catch (\RuntimeException $e) {
            $this->responderError($e->getMessage(), $e->getCode() ?: 400);
        } catch (\Throwable $e) {
            $this->responderError('Error interno al crear la jornada.', 500);
        }
    }

    /**
     * PUT /api/jornadas/actualizar/{idJornada}
     * Body: campos a actualizar (parcial)
     */
    private function actualizar(int $idJornada): void
    {
        $cuerpo = $this->leerCuerpoJson();

        if (empty($cuerpo)) {
            $this->responderError('No se recibieron datos para actualizar.', 422);
        }

        try {
            $jornada = $this->servicio->actualizar($idJornada, $cuerpo);
            $this->responderExito('Jornada actualizada correctamente.', $jornada);
        } catch (\RuntimeException $e) {
            $this->responderError($e->getMessage(), $e->getCode() ?: 400);
        } catch (\Throwable $e) {
            $this->responderError('Error interno al actualizar la jornada.', 500);
        }
    }

    /**
     * DELETE /api/jornadas/eliminar/{idJornada}
     */
    private function eliminar(int $idJornada): void
    {
        try {
            $resultado = $this->servicio->eliminar($idJornada);
            $this->responderExito($resultado['message'] ?? 'Jornada eliminada correctamente.', []);
        } catch (\RuntimeException $e) {
            $this->responderError($e->getMessage(), $e->getCode() ?: 404);
        } catch (\Throwable $e) {
            $this->responderError('Error interno al eliminar la jornada.', 500);
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