<?php

declare(strict_types=1);

class SesionController
{
    private SesionService $servicio;

    public function __construct()
    {
        $this->servicio = new SesionService();
    }

    public function handle(string $metodo, string $accion, array $params): void
    {
        match ($accion) {
            'crear' => $this->despacharConMetodo($metodo, 'POST',
                fn() => $this->crear()
            ),
            'listar' => $this->despacharConMetodo($metodo, 'GET',
                fn() => $this->listar()
            ),
            'detalle' => $this->despacharConMetodo($metodo, 'GET',
                fn() => $this->detalle($this->extraerIdRequerido($params, 'sesión'))
            ),
            'cerrar' => $this->despacharConMetodo($metodo, 'POST',
                fn() => $this->cerrar($this->extraerIdRequerido($params, 'sesión'))
            ),
            default => $this->responderError(
                "Acción '{$accion}' no encontrada en SesionController.", 404
            ),
        };
    }

    // -------------------------------------------------------------------------
    // Acciones
    // -------------------------------------------------------------------------

    /**
     * POST /api/sesiones/crear
     * Body: { "id_materia": 1, "id_docente": 3, "fecha": "2025-06-10" }
     */
    private function crear(): void
    {
        $cuerpo = $this->leerCuerpoJson();

        if (empty($cuerpo['id_materia']) || empty($cuerpo['id_docente'])) {
            $this->responderError('Los campos id_materia e id_docente son obligatorios.', 422);
        }

        $fecha = $cuerpo['fecha'] ?? date('Y-m-d');

        try {
            $sesion = $this->servicio->crear(
                (int)    $cuerpo['id_materia'],
                (int)    $cuerpo['id_docente'],
                (string) $fecha
            );
            $this->responderExito('Sesión creada correctamente.', $sesion, 201);
        } catch (\RuntimeException $e) {
            $this->responderError($e->getMessage(), $e->getCode() ?: 400);
        } catch (\Throwable $e) {
            $this->responderError('Error interno al crear la sesión.', 500);
        }
    }

    /**
     * GET /api/sesiones/listar
     * Query params opcionales: ?id_docente=3&fecha=2025-06-10&estado=activa
     */
    private function listar(): void
    {
        $idDocente = isset($_GET['id_docente']) ? (int) $_GET['id_docente'] : null;
        $fecha     = $_GET['fecha']  ?? null;
        $estado    = $_GET['estado'] ?? null;

        try {
            $resultado = $this->servicio->listar($idDocente, $fecha, $estado);
            $this->responderExito('Sesiones obtenidas correctamente.', $resultado);
        } catch (\RuntimeException $e) {
            $this->responderError($e->getMessage(), $e->getCode() ?: 400);
        } catch (\Throwable $e) {
            $this->responderError('Error interno al listar sesiones.', 500);
        }
    }

    /**
     * GET /api/sesiones/detalle/{idSesion}
     */
    private function detalle(int $idSesion): void
    {
        try {
            $sesion = $this->servicio->consultar($idSesion);
            $this->responderExito('Sesión encontrada.', $sesion);
        } catch (\RuntimeException $e) {
            $this->responderError($e->getMessage(), $e->getCode() ?: 404);
        } catch (\Throwable $e) {
            $this->responderError('Error interno al obtener el detalle de la sesión.', 500);
        }
    }

    /**
     * POST /api/sesiones/cerrar/{idSesion}
     */
    private function cerrar(int $idSesion): void
    {
        try {
            $resultado = $this->servicio->cerrar($idSesion);
            $this->responderExito($resultado['message'] ?? 'Sesión cerrada correctamente.', $resultado);
        } catch (\RuntimeException $e) {
            $this->responderError($e->getMessage(), $e->getCode() ?: 400);
        } catch (\Throwable $e) {
            $this->responderError('Error interno al cerrar la sesión.', 500);
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