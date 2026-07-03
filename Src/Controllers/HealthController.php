<?php

declare(strict_types=1);

class HealthController
{
    private HealthService $servicio;

    public function __construct()
    {
        $this->servicio = new HealthService();
    }

    public function handle(string $metodo, string $accion, array $params): void
    {
        match ($accion) {
            '', 'status' => $this->despacharConMetodo($metodo, 'GET',
                fn() => $this->status()
            ),
            'ping' => $this->despacharConMetodo($metodo, 'GET',
                fn() => $this->ping()
            ),
            'version' => $this->despacharConMetodo($metodo, 'GET',
                fn() => $this->version()
            ),
            default => $this->responderError(
                "Acción '{$accion}' no encontrada en HealthController.", 404
            ),
        };
    }

    // -------------------------------------------------------------------------
    // Acciones
    // -------------------------------------------------------------------------

    /**
     * GET /api/health/status
     */
    private function status(): void
    {
        try {
            $resultado = $this->servicio->status();
            $codigo    = ($resultado['estado_global'] ?? 'operativo') === 'operativo' ? 200 : 503;
            $this->responderExito('Estado del sistema obtenido.', $resultado, $codigo);
        } catch (\Throwable $e) {
            $this->responderError('Error interno al verificar el estado del sistema.', 500);
        }
    }

    /**
     * GET /api/health/ping
     */
    private function ping(): void
    {
        try {
            $resultado = $this->servicio->ping();
            $this->responderExito('pong', $resultado);
        } catch (\Throwable $e) {
            $this->responderError('Error interno al responder el ping.', 500);
        }
    }

    /**
     * GET /api/health/version
     */
    private function version(): void
    {
        try {
            $resultado = $this->servicio->version();
            $this->responderExito('Versión del sistema obtenida.', $resultado);
        } catch (\Throwable $e) {
            $this->responderError('Error interno al obtener la versión.', 500);
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