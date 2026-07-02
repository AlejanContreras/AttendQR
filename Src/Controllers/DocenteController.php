<?php

declare(strict_types=1);

/**
 * AttendQR – DocenteController
 *
 * Responsabilidad: gestionar los docentes del sistema.
 * Delega toda la lógica a DocenteService.
 *
 * Rutas:
 *   GET    /api/docentes/listar             → listar docentes
 *   GET    /api/docentes/consultar/{id}     → consultar por ID
 *   POST   /api/docentes/registrar         → registrar nuevo docente
 *   PUT    /api/docentes/actualizar/{id}   → actualizar datos
 *   DELETE /api/docentes/eliminar/{id}     → eliminar docente
 *
 * Ubicación en el proyecto: Src/Controllers/DocenteController.php
 */
class DocenteController
{
    private DocenteService $servicio;

    public function __construct()
    {
        $this->servicio = new DocenteService();
    }

    /**
     * Punto de entrada del router.
     *
     * @param string   $metodo Método HTTP
     * @param string   $accion Segundo segmento de la URL
     * @param string[] $params Parámetros posicionales
     */
    public function handle(string $metodo, string $accion, array $params): void
    {
        match ($accion) {
            'listar' => $this->despacharConMetodo($metodo, 'GET',
                fn() => $this->listar()
            ),
            'consultar' => $this->despacharConMetodo($metodo, 'GET',
                fn() => $this->consultar($this->extraerIdRequerido($params, 'docente'))
            ),
            'registrar' => $this->despacharConMetodo($metodo, 'POST',
                fn() => $this->registrar()
            ),
            'actualizar' => $this->despacharConMetodo($metodo, 'PUT',
                fn() => $this->actualizar($this->extraerIdRequerido($params, 'docente'))
            ),
            'eliminar' => $this->despacharConMetodo($metodo, 'DELETE',
                fn() => $this->eliminar($this->extraerIdRequerido($params, 'docente'))
            ),
            default => $this->responderError(
                "Acción '{$accion}' no encontrada en DocenteController.", 404
            ),
        };
    }

    // -------------------------------------------------------------------------
    // Acciones
    // -------------------------------------------------------------------------

    /**
     * GET /api/docentes/listar
     * Query params opcionales: ?estado=activo
     */
    private function listar(): void
    {
        $estado = $_GET['estado'] ?? null;

        try {
            $resultado = $this->servicio->listar($estado);
            $this->responderExito('Docentes obtenidos correctamente.', $resultado);

        } catch (\RuntimeException $e) {
            $this->responderError($e->getMessage(), $e->getCode() ?: 400);
        } catch (\Throwable $e) {
            $this->responderError('Error interno al listar docentes.', 500);
        }
    }

    /**
     * GET /api/docentes/consultar/{idDocente}
     */
    private function consultar(int $idDocente): void
    {
        try {
            $docente = $this->servicio->consultar($idDocente);
            $this->responderExito('Docente encontrado.', $docente);

        } catch (\RuntimeException $e) {
            $this->responderError($e->getMessage(), $e->getCode() ?: 404);
        } catch (\Throwable $e) {
            $this->responderError('Error interno al consultar el docente.', 500);
        }
    }

    /**
     * POST /api/docentes/registrar
     * Body: { "nombres": "...", "apellidos": "...", "correo": "...", "contrasena": "..." }
     */
    private function registrar(): void
    {
        $cuerpo = $this->leerCuerpoJson();

        foreach (['nombres', 'apellidos', 'correo', 'contrasena'] as $campo) {
            if (empty($cuerpo[$campo])) {
                $this->responderError("El campo '{$campo}' es obligatorio.", 422);
            }
        }

        try {
            $docente = $this->servicio->registrar(
                (string) $cuerpo['nombres'],
                (string) $cuerpo['apellidos'],
                (string) $cuerpo['correo'],
                (string) $cuerpo['contrasena']
            );
            $this->responderExito('Docente registrado correctamente.', $docente, 201);

        } catch (\RuntimeException $e) {
            $this->responderError($e->getMessage(), $e->getCode() ?: 400);
        } catch (\Throwable $e) {
            $this->responderError('Error interno al registrar el docente.', 500);
        }
    }

    /**
     * PUT /api/docentes/actualizar/{idDocente}
     * Body: campos a actualizar (parcial)
     */
    private function actualizar(int $idDocente): void
    {
        $cuerpo = $this->leerCuerpoJson();

        if (empty($cuerpo)) {
            $this->responderError('No se recibieron datos para actualizar.', 422);
        }

        try {
            $docente = $this->servicio->actualizar($idDocente, $cuerpo);
            $this->responderExito('Docente actualizado correctamente.', $docente);

        } catch (\RuntimeException $e) {
            $this->responderError($e->getMessage(), $e->getCode() ?: 400);
        } catch (\Throwable $e) {
            $this->responderError('Error interno al actualizar el docente.', 500);
        }
    }

    /**
     * DELETE /api/docentes/eliminar/{idDocente}
     */
    private function eliminar(int $idDocente): void
    {
        try {
            $resultado = $this->servicio->eliminar($idDocente);
            $this->responderExito($resultado['message'] ?? 'Docente eliminado correctamente.', []);

        } catch (\RuntimeException $e) {
            $this->responderError($e->getMessage(), $e->getCode() ?: 404);
        } catch (\Throwable $e) {
            $this->responderError('Error interno al eliminar el docente.', 500);
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