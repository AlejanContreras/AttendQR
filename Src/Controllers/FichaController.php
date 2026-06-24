<?php

declare(strict_types=1);

class FichaController
{
    private FichaService $servicio;

    public function __construct()
    {
        $this->servicio = new FichaService();
    }

    public function handle(string $metodo, string $accion, array $params): void
    {
        match ($accion) {
            'listar' => $this->despacharConMetodo($metodo, 'GET',
                fn() => $this->listar()
            ),
            'consultar' => $this->despacharConMetodo($metodo, 'GET',
                fn() => $this->consultar($this->extraerIdRequerido($params, 'ficha'))
            ),
            'crear' => $this->despacharConMetodo($metodo, 'POST',
                fn() => $this->crear()
            ),
            'actualizar' => $this->despacharConMetodo($metodo, 'PUT',
                fn() => $this->actualizar($this->extraerIdRequerido($params, 'ficha'))
            ),
            'eliminar' => $this->despacharConMetodo($metodo, 'DELETE',
                fn() => $this->eliminar($this->extraerIdRequerido($params, 'ficha'))
            ),
            default => $this->responderError(
                "Acción '{$accion}' no encontrada en FichaController.", 404
            ),
        };
    }

    // -------------------------------------------------------------------------
    // Acciones
    // -------------------------------------------------------------------------

    /**
     * GET /api/fichas/listar
     * Query params opcionales: ?id_programa=3&estado=activa&id_jornada=2
     */
    private function listar(): void
    {
        $idPrograma = isset($_GET['id_programa']) ? (int) $_GET['id_programa'] : null;
        $estado     = $_GET['estado']    ?? null;
        $idJornada  = isset($_GET['id_jornada']) ? (int) $_GET['id_jornada'] : null;

        try {
            $resultado = $this->servicio->listar($idPrograma, $estado, $idJornada);
            $this->responderExito('Fichas obtenidas correctamente.', $resultado);
        } catch (\RuntimeException $e) {
            $this->responderError($e->getMessage(), $e->getCode() ?: 400);
        } catch (\Throwable $e) {
            $this->responderError('Error interno al listar fichas.', 500);
        }
    }

    /**
     * GET /api/fichas/consultar/{idFicha}
     */
    private function consultar(int $idFicha): void
    {
        try {
            $ficha = $this->servicio->consultar($idFicha);
            $this->responderExito('Ficha encontrada.', $ficha);
        } catch (\RuntimeException $e) {
            $this->responderError($e->getMessage(), $e->getCode() ?: 404);
        } catch (\Throwable $e) {
            $this->responderError('Error interno al consultar la ficha.', 500);
        }
    }

    /**
     * POST /api/fichas/crear
     * Body: { "numero_ficha": "2345678", "id_programa": 3, "id_jornada": 1 }
     */
    private function crear(): void
    {
        $cuerpo = $this->leerCuerpoJson();

        if (empty($cuerpo['numero_ficha']) || empty($cuerpo['id_programa'])) {
            $this->responderError('Los campos numero_ficha e id_programa son obligatorios.', 422);
        }

        try {
            $ficha = $this->servicio->crear(
                (string) $cuerpo['numero_ficha'],
                (int)    $cuerpo['id_programa'],
                isset($cuerpo['id_jornada']) ? (int) $cuerpo['id_jornada'] : null
            );
            $this->responderExito('Ficha creada correctamente.', $ficha, 201);
        } catch (\RuntimeException $e) {
            $this->responderError($e->getMessage(), $e->getCode() ?: 400);
        } catch (\Throwable $e) {
            $this->responderError('Error interno al crear la ficha.', 500);
        }
    }

    /**
     * PUT /api/fichas/actualizar/{idFicha}
     * Body: campos a actualizar (parcial)
     */
    private function actualizar(int $idFicha): void
    {
        $cuerpo = $this->leerCuerpoJson();

        if (empty($cuerpo)) {
            $this->responderError('No se recibieron datos para actualizar.', 422);
        }

        try {
            $ficha = $this->servicio->actualizar($idFicha, $cuerpo);
            $this->responderExito('Ficha actualizada correctamente.', $ficha);
        } catch (\RuntimeException $e) {
            $this->responderError($e->getMessage(), $e->getCode() ?: 400);
        } catch (\Throwable $e) {
            $this->responderError('Error interno al actualizar la ficha.', 500);
        }
    }

    /**
     * DELETE /api/fichas/eliminar/{idFicha}
     */
    private function eliminar(int $idFicha): void
    {
        try {
            $resultado = $this->servicio->eliminar($idFicha);
            $this->responderExito($resultado['message'] ?? 'Ficha eliminada correctamente.', []);
        } catch (\RuntimeException $e) {
            $this->responderError($e->getMessage(), $e->getCode() ?: 404);
        } catch (\Throwable $e) {
            $this->responderError('Error interno al eliminar la ficha.', 500);
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