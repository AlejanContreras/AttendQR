<?php

declare(strict_types=1);

class TokenController
{
    private TokenService $servicio;

    public function __construct()
    {
        $this->servicio = new TokenService();
    }

    public function handle(string $metodo, string $accion, array $params): void
    {
        match ($accion) {
            'generar' => $this->despacharConMetodo($metodo, 'POST',
                fn() => $this->generar()
            ),
            'validar' => $this->despacharConMetodo($metodo, 'POST',
                fn() => $this->validar()
            ),
            'renovar' => $this->despacharConMetodo($metodo, 'POST',
                fn() => $this->renovar()
            ),
            'eliminar' => $this->despacharConMetodo($metodo, 'DELETE',
                fn() => $this->eliminar()
            ),
            default => $this->responderError(
                "Acción '{$accion}' no encontrada en TokenController.", 404
            ),
        };
    }

    // -------------------------------------------------------------------------
    // Acciones
    // -------------------------------------------------------------------------

    /**
     * POST /api/tokens/generar
     * Body: { "id_usuario": 5, "rol": "docente" }
     */
    private function generar(): void
    {
        $cuerpo = $this->leerCuerpoJson();

        if (empty($cuerpo['id_usuario']) || empty($cuerpo['rol'])) {
            $this->responderError('Los campos id_usuario y rol son obligatorios.', 422);
        }

        try {
            $tokens = $this->servicio->generar(
                (int)    $cuerpo['id_usuario'],
                (string) $cuerpo['rol']
            );
            $this->responderExito('Tokens generados correctamente.', $tokens, 201);
        } catch (\RuntimeException $e) {
            $this->responderError($e->getMessage(), $e->getCode() ?: 400);
        } catch (\Throwable $e) {
            $this->responderError('Error interno al generar los tokens.', 500);
        }
    }

    /**
     * POST /api/tokens/validar
     * Body: { "token": "eyJ..." }
     */
    private function validar(): void
    {
        $cuerpo = $this->leerCuerpoJson();

        if (empty($cuerpo['token'])) {
            $this->responderError('El campo token es obligatorio.', 422);
        }

        try {
            $payload = $this->servicio->validar((string) $cuerpo['token']);
            $this->responderExito('Token válido.', $payload);
        } catch (\RuntimeException $e) {
            $this->responderError($e->getMessage(), $e->getCode() ?: 401);
        } catch (\Throwable $e) {
            $this->responderError('Error interno al validar el token.', 500);
        }
    }

    /**
     * POST /api/tokens/renovar
     * Body: { "token_refresco": "eyJ..." }
     */
    private function renovar(): void
    {
        $cuerpo = $this->leerCuerpoJson();

        if (empty($cuerpo['token_refresco'])) {
            $this->responderError('El campo token_refresco es obligatorio.', 422);
        }

        try {
            $resultado = $this->servicio->renovar((string) $cuerpo['token_refresco']);
            $this->responderExito('Token renovado correctamente.', $resultado);
        } catch (\RuntimeException $e) {
            $this->responderError($e->getMessage(), $e->getCode() ?: 401);
        } catch (\Throwable $e) {
            $this->responderError('Error interno al renovar el token.', 500);
        }
    }

    /**
     * DELETE /api/tokens/eliminar
     * Body: { "token": "eyJ..." }
     */
    private function eliminar(): void
    {
        $cuerpo = $this->leerCuerpoJson();

        if (empty($cuerpo['token'])) {
            $this->responderError('El campo token es obligatorio.', 422);
        }

        try {
            $resultado = $this->servicio->eliminar((string) $cuerpo['token']);
            $this->responderExito($resultado['message'] ?? 'Token revocado correctamente.', []);
        } catch (\RuntimeException $e) {
            $this->responderError($e->getMessage(), $e->getCode() ?: 404);
        } catch (\Throwable $e) {
            $this->responderError('Error interno al revocar el token.', 500);
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