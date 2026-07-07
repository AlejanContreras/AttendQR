<?php

declare(strict_types=1);

class FichaController
{
    private FichaService  $servicio;
    private SesionService $sesionServicio;

    public function __construct()
    {
        $this->servicio       = new FichaService();
        $this->sesionServicio = new SesionService();
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
            'historial' => $this->despacharConMetodo($metodo, 'GET',
                fn() => $this->historial($this->extraerIdRequerido($params, 'ficha'))
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
     * Query params opcionales: ?nombre_programa=...&estado=activa&id_jornada=2&id_docente=3
     */
    private function listar(): void
    {
        $nombrePrograma = $_GET['nombre_programa'] ?? null;
        $estado         = $_GET['estado']          ?? null;
        $idJornada      = isset($_GET['id_jornada'])  ? (int) $_GET['id_jornada']  : null;
        $idDocente      = isset($_GET['id_docente'])  ? (int) $_GET['id_docente']  : null;

        try {
            $resultado = $this->servicio->listar($nombrePrograma, $estado, $idJornada, $idDocente);
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
     * GET /api/fichas/historial/{idFicha}
     * Query params opcionales: ?fecha_inicio=2025-03-01&fecha_fin=2025-06-30&estado=cerrada
     *
     * Historial de sesiones de la ficha con totales de asistencia por sesión.
     */
    private function historial(int $idFicha): void
    {
        $fechaInicio = $_GET['fecha_inicio'] ?? null;
        $fechaFin    = $_GET['fecha_fin']    ?? null;
        $estado      = $_GET['estado']       ?? null;

        try {
            $resultado = $this->sesionServicio->historialPorFicha($idFicha, $fechaInicio, $fechaFin, $estado);
            $this->responderExito('Historial de la ficha obtenido correctamente.', $resultado);
        } catch (\RuntimeException $e) {
            $this->responderError($e->getMessage(), $e->getCode() ?: 400);
        } catch (\Throwable $e) {
            $this->responderError('Error interno al obtener el historial de la ficha.', 500);
        }
    }

    /**
     * POST /api/fichas/crear
     * Body: { "codigo_ficha": "2345678", "nombre_programa": "...", "id_jornada": 1, "nombre_materia": "..." (opt.) }
     * id_docente se toma de la sesión activa del docente (no del body).
     */
    private function crear(): void
    {
        $cuerpo = $this->leerCuerpoJson();

        foreach (['codigo_ficha', 'nombre_programa', 'id_jornada'] as $campo) {
            if (empty($cuerpo[$campo])) {
                $this->responderError("El campo '{$campo}' es obligatorio.", 422);
            }
        }

        $idDocente = (int) ($_SESSION['usuario']['id'] ?? 0);
        if (!$idDocente) {
            $this->responderError('No se pudo identificar al docente de la sesión.', 401);
        }

        try {
            $ficha = $this->servicio->crear(
                (string) $cuerpo['codigo_ficha'],
                (string) $cuerpo['nombre_programa'],
                (int)    $cuerpo['id_jornada'],
                $idDocente,
                isset($cuerpo['nombre_materia']) && $cuerpo['nombre_materia'] !== ''
                    ? (string) $cuerpo['nombre_materia']
                    : null,
                null
            );
            $this->responderExito('Clase creada correctamente.', $ficha, 201);
        } catch (\RuntimeException $e) {
            $this->responderError($e->getMessage(), $e->getCode() ?: 400);
        } catch (\Throwable $e) {
            $this->responderError('Error interno al crear la clase.', 500);
        }
    }

    /**
     * PUT /api/fichas/actualizar/{idFicha}
     * Body: campos a actualizar (parcial) — solo campos de la clase propia del docente.
     */
    private function actualizar(int $idFicha): void
    {
        $cuerpo = $this->leerCuerpoJson();

        if (empty($cuerpo)) {
            $this->responderError('No se recibieron datos para actualizar.', 422);
        }

        $idDocente = (int) ($_SESSION['usuario']['id'] ?? 0);

        try {
            $fichaActual = $this->servicio->consultar($idFicha);
            if ((int) ($fichaActual['id_docente'] ?? 0) !== $idDocente) {
                $this->responderError('No tienes permiso para editar esta clase.', 403);
            }
        } catch (\RuntimeException $e) {
            $this->responderError($e->getMessage(), $e->getCode() ?: 404);
        }

        try {
            $ficha = $this->servicio->actualizar($idFicha, $cuerpo);
            $this->responderExito('Clase actualizada correctamente.', $ficha);
        } catch (\RuntimeException $e) {
            $this->responderError($e->getMessage(), $e->getCode() ?: 400);
        } catch (\Throwable $e) {
            $this->responderError('Error interno al actualizar la clase.', 500);
        }
    }

    /**
     * DELETE /api/fichas/eliminar/{idFicha}
     */
    private function eliminar(int $idFicha): void
    {
        $idDocente = (int) ($_SESSION['usuario']['id'] ?? 0);

        try {
            $fichaActual = $this->servicio->consultar($idFicha);
            if ((int) ($fichaActual['id_docente'] ?? 0) !== $idDocente) {
                $this->responderError('No tienes permiso para eliminar esta clase.', 403);
            }
        } catch (\RuntimeException $e) {
            $this->responderError($e->getMessage(), $e->getCode() ?: 404);
        }

        try {
            $resultado = $this->servicio->eliminar($idFicha);
            $this->responderExito($resultado['message'] ?? 'Clase eliminada correctamente.', []);
        } catch (\RuntimeException $e) {
            $this->responderError($e->getMessage(), $e->getCode() ?: 404);
        } catch (\Throwable $e) {
            $this->responderError('Error interno al eliminar la clase.', 500);
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