<?php

declare(strict_types=1);

class QrController
{
    private QrService $servicio;

    public function __construct()
    {
        $this->servicio = new QrService();
    }

    public function handle(string $metodo, string $accion, array $params): void
    {
        match ($accion) {
            'generar' => $this->despacharConMetodo($metodo, 'POST',
                fn() => $this->generar($this->extraerIdRequerido($params, 'sesión'))
            ),
            'token-activo' => $this->despacharConMetodo($metodo, 'GET',
                fn() => $this->tokenActivo($this->extraerIdRequerido($params, 'sesión'))
            ),
            'validar' => $this->despacharConMetodo($metodo, 'POST',
                fn() => $this->validar()
            ),
            default => $this->responderError(
                "Acción '{$accion}' no encontrada en QrController.", 404
            ),
        };
    }

    // -------------------------------------------------------------------------
    // Acciones
    // -------------------------------------------------------------------------

    /**
     * POST /api/qr/generar/{idSesion}
     */
    private function generar(int $idSesion): void
    {
        try {
            $resultado = $this->servicio->generar($idSesion);
            $this->responderExito('QR generado correctamente.', $resultado, 201);
        } catch (\RuntimeException $e) {
            $this->responderError($e->getMessage(), $e->getCode() ?: 400);
        } catch (\Throwable $e) {
            $this->responderError('Error interno al generar el QR.', 500);
        }
    }

    /**
     * GET /api/qr/token-activo/{idSesion}
     */
    private function tokenActivo(int $idSesion): void
    {
        try {
            $resultado = $this->servicio->tokenActivo($idSesion);
            $this->responderExito('Token QR activo obtenido.', $resultado);
        } catch (\RuntimeException $e) {
            $this->responderError($e->getMessage(), $e->getCode() ?: 404);
        } catch (\Throwable $e) {
            $this->responderError('Error interno al obtener el token QR.', 500);
        }
    }

    /**
     * POST /api/qr/validar
     * Body: { "token": "abc123...", "id_estudiante": 42 }
     */
    private function validar(): void
    {
        $cuerpo = $this->leerCuerpoJson();

        if (empty($cuerpo['token']) || empty($cuerpo['id_estudiante'])) {
            $this->responderError('Los campos token e id_estudiante son obligatorios.', 422);
        }

        try {
            $resultado = $this->servicio->validar(
                (string) $cuerpo['token'],
                (int)    $cuerpo['id_estudiante']
            );
            $this->responderExito('Token QR válido.', $resultado);
        } catch (\RuntimeException $e) {
            $this->responderError($e->getMessage(), $e->getCode() ?: 400);
        } catch (\Throwable $e) {
            $this->responderError('Error interno al validar el token QR.', 500);
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