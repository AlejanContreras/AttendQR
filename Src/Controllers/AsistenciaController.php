<?php

declare(strict_types=1);

/**
 * AttendQR – AsistenciaController
 *
 * Responsabilidad: recibir peticiones HTTP del módulo de asistencias,
 * delegar al AsistenciaService y devolver respuestas JSON.
 *
 * Rutas:
 *   POST   /api/asistencias/registrar        → aprendiz registra asistencia por QR
 *   GET    /api/asistencias/consultar/{id}   → consultar un registro por ID
 *   GET    /api/asistencias/historial/{id}   → historial de asistencias de un aprendiz
 *   POST   /api/asistencias/validar          → verificar si ya existe registro (docente)
 *   DELETE /api/asistencias/eliminar/{id}    → eliminar un registro (docente)
 *
 * Ubicación en el proyecto: Src/Controllers/AsistenciaController.php
 */
class AsistenciaController
{
    private AsistenciaService $servicio;

    public function __construct()
    {
        $this->servicio = new AsistenciaService();
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
            'registrar' => $this->despacharConMetodo($metodo, 'POST',
                fn() => $this->registrar()
            ),
            'consultar' => $this->despacharConMetodo($metodo, 'GET',
                fn() => $this->consultar($this->extraerIdRequerido($params, 'asistencia'))
            ),
            'historial' => $this->despacharConMetodo($metodo, 'GET',
                fn() => $this->historial($this->extraerIdRequerido($params, 'aprendiz'))
            ),
            'validar' => $this->despacharConMetodo($metodo, 'POST',
                fn() => $this->validar()
            ),
            'eliminar' => $this->despacharConMetodo($metodo, 'DELETE',
                fn() => $this->eliminar($this->extraerIdRequerido($params, 'asistencia'))
            ),
            default => $this->responderError(
                "Acción '{$accion}' no encontrada en AsistenciaController.", 404
            ),
        };
    }

    // -------------------------------------------------------------------------
    // Acciones
    // -------------------------------------------------------------------------

    /**
     * POST /api/asistencias/registrar
     * Body: { "token": "abc123..." }
     *
     * El aprendiz autenticado escanea el QR y envía el token.
     * El sistema valida, clasifica y registra. No se confía en ningún dato del cliente
     * salvo el valor del token.
     */
    private function registrar(): void
    {
        $cuerpo  = $this->leerCuerpoJson();
        $usuario = $this->obtenerUsuarioAutenticado();

        if (empty($cuerpo['token'])) {
            $this->responderError('El campo token es obligatorio.', 422);
        }

        if (($usuario['rol'] ?? '') !== 'aprendiz') {
            $this->responderError('Solo un aprendiz puede registrar su asistencia por QR.', 403);
        }

        try {
            $asistencia = $this->servicio->registrarPorQr(
                (string) $cuerpo['token'],
                $usuario
            );
            $this->responderExito('Asistencia registrada correctamente.', $asistencia, 201);
        } catch (\RuntimeException $e) {
            $this->responderError($e->getMessage(), $e->getCode() ?: 400);
        } catch (\Throwable $e) {
            $this->responderError('Error interno al registrar la asistencia.', 500);
        }
    }

    /**
     * GET /api/asistencias/consultar/{idAsistencia}
     */
    private function consultar(int $idAsistencia): void
    {
        try {
            $asistencia = $this->servicio->consultar($idAsistencia);
            $this->responderExito('Asistencia encontrada.', $asistencia);
        } catch (\RuntimeException $e) {
            $this->responderError($e->getMessage(), $e->getCode() ?: 404);
        } catch (\Throwable $e) {
            $this->responderError('Error interno al consultar la asistencia.', 500);
        }
    }

    /**
     * GET /api/asistencias/historial/{idAprendiz}
     * Query params opcionales: ?fecha_inicio=2025-03-01&fecha_fin=2025-06-30
     */
    private function historial(int $idAprendiz): void
    {
        $fechaInicio = $_GET['fecha_inicio'] ?? null;
        $fechaFin    = $_GET['fecha_fin']    ?? null;

        try {
            $historial = $this->servicio->historial($idAprendiz, $fechaInicio, $fechaFin);
            $this->responderExito('Historial obtenido correctamente.', $historial);
        } catch (\RuntimeException $e) {
            $this->responderError($e->getMessage(), $e->getCode() ?: 404);
        } catch (\Throwable $e) {
            $this->responderError('Error interno al obtener el historial.', 500);
        }
    }

    /**
     * POST /api/asistencias/validar
     * Body: { "id_aprendiz": 42, "id_sesion": 15 }
     * Endpoint para que el docente verifique si un aprendiz ya registró asistencia.
     */
    private function validar(): void
    {
        $cuerpo = $this->leerCuerpoJson();

        if (empty($cuerpo['id_aprendiz']) || empty($cuerpo['id_sesion'])) {
            $this->responderError('Los campos id_aprendiz e id_sesion son obligatorios.', 422);
        }

        try {
            $resultado = $this->servicio->validar(
                (int) $cuerpo['id_aprendiz'],
                (int) $cuerpo['id_sesion']
            );
            $this->responderExito('Validación completada.', $resultado);
        } catch (\RuntimeException $e) {
            $this->responderError($e->getMessage(), $e->getCode() ?: 400);
        } catch (\Throwable $e) {
            $this->responderError('Error interno al validar la asistencia.', 500);
        }
    }

    /**
     * DELETE /api/asistencias/eliminar/{idAsistencia}
     */
    private function eliminar(int $idAsistencia): void
    {
        try {
            $resultado = $this->servicio->eliminar($idAsistencia);
            $this->responderExito($resultado['message'] ?? 'Asistencia eliminada correctamente.', []);
        } catch (\RuntimeException $e) {
            $this->responderError($e->getMessage(), $e->getCode() ?: 404);
        } catch (\Throwable $e) {
            $this->responderError('Error interno al eliminar la asistencia.', 500);
        }
    }

    // -------------------------------------------------------------------------
    // Auxiliares
    // -------------------------------------------------------------------------

    private function obtenerUsuarioAutenticado(): array
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (empty($_SESSION['usuario']) || !is_array($_SESSION['usuario'])) {
            $this->responderError('No autenticado.', 401);
        }

        return $_SESSION['usuario'];
    }

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
