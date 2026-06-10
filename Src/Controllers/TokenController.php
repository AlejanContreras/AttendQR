<?php

declare(strict_types=1);

/**
 * AttendQR – TokenController
 *
 * Responsabilidad: gestionar el ciclo de vida de los tokens de autenticación.
 * Mientras AuthController gestiona la identidad del usuario (quién eres),
 * TokenController gestiona las credenciales de acceso (con qué accedes).
 *
 * Casos de uso:
 *   - Generación de tokens de acceso y de refresco.
 *   - Validación de tokens recibidos en peticiones protegidas.
 *   - Renovación de tokens expirados usando el token de refresco.
 *   - Revocación explícita de tokens (cierre de sesión desde otro dispositivo).
 *
 * Arquitectura:
 *   api.php → TokenController → [TokenService] → [TokenRepository] → [Modelo]
 *
 * Rutas que maneja este controlador:
 *   POST   /api/tokens/generar         → generar un nuevo par de tokens (acceso + refresco)
 *   POST   /api/tokens/validar         → verificar que un token de acceso es válido
 *   POST   /api/tokens/renovar         → obtener un nuevo token de acceso usando el de refresco
 *   DELETE /api/tokens/eliminar        → revocar un token (cerrar sesión de un dispositivo)
 *
 * Ubicación en el proyecto: Src/Controllers/TokenController.php
 */
class TokenController
{
    // -------------------------------------------------------------------------
    // Punto de entrada principal
    // -------------------------------------------------------------------------

    /**
     * Recibe la petición del router y la despacha a la acción correspondiente.
     *
     * No contiene lógica de negocio: únicamente enruta y delega.
     *
     * @param string   $metodo  Método HTTP recibido (POST o DELETE)
     * @param string   $accion  Segundo segmento de la URL (/api/tokens/{accion})
     * @param string[] $params  Parámetros posicionales adicionales (no usados en esta versión)
     */
    public function handle(string $metodo, string $accion, array $params): void
    {
        match ($accion) {

            // Generar un nuevo par de tokens para un usuario autenticado — solo POST
            'generar' => $this->despacharConMetodo($metodo, 'POST',
                fn() => $this->generar()
            ),

            // Verificar la validez de un token de acceso — solo POST
            'validar' => $this->despacharConMetodo($metodo, 'POST',
                fn() => $this->validar()
            ),

            // Renovar el token de acceso usando el token de refresco — solo POST
            'renovar' => $this->despacharConMetodo($metodo, 'POST',
                fn() => $this->renovar()
            ),

            // Revocar un token existente (invalidarlo explícitamente) — solo DELETE
            'eliminar' => $this->despacharConMetodo($metodo, 'DELETE',
                fn() => $this->eliminar()
            ),

            // Acción no reconocida → 404
            default => $this->responderError(
                "Acción '{$accion}' no encontrada en TokenController.",
                404
            ),
        };
    }

    // -------------------------------------------------------------------------
    // Acciones del controlador
    // -------------------------------------------------------------------------

    /**
     * Acción: generar un nuevo par de tokens de autenticación.
     *
     * Recibe las credenciales del usuario, verifica su identidad y devuelve
     * un token de acceso (vida corta) y un token de refresco (vida larga).
     * Este endpoint es invocado internamente por AuthController->login().
     *
     * POST /api/tokens/generar
     * Body esperado: { "id_usuario": 5, "rol": "docente" }
     */
    private function generar(): void
    {
        $cuerpo = $this->leerCuerpoJson();

        if (empty($cuerpo['id_usuario']) || empty($cuerpo['rol'])) {
            $this->responderError('Los campos id_usuario y rol son obligatorios.', 422);
        }

        $idUsuario = (int)    $cuerpo['id_usuario'];
        $rol       = trim((string) $cuerpo['rol']);

        // ► AQUÍ: llamar a TokenService->generar($idUsuario, $rol)
        // El servicio:
        //   1. Generará un token de acceso con expiración corta (p. ej. 15 min).
        //   2. Generará un token de refresco con expiración larga (p. ej. 7 días).
        //   3. Guardará el token de refresco hasheado en TokenRepository.
        //   4. Retornará ambos tokens al controlador.
        //
        // Ejemplo futuro:
        //   $servicio = new TokenService();
        //   $tokens   = $servicio->generar($idUsuario, $rol);
        //   $this->responderExito('Tokens generados correctamente.', $tokens, 201);

        $this->responderExito('Endpoint generar token disponible.', [
            'id_usuario' => $idUsuario,
            'rol'        => $rol,
        ], 201);
    }

    /**
     * Acción: verificar que un token de acceso es válido y no ha expirado.
     *
     * Útil para que el Middleware de autenticación valide tokens
     * antes de permitir acceso a endpoints protegidos.
     *
     * POST /api/tokens/validar
     * Body esperado: { "token": "eyJ..." }
     */
    private function validar(): void
    {
        $cuerpo = $this->leerCuerpoJson();

        if (empty($cuerpo['token'])) {
            $this->responderError('El campo token es obligatorio.', 422);
        }

        $token = trim((string) $cuerpo['token']);

        // ► AQUÍ: llamar a TokenService->validar($token)
        // El servicio verificará la firma del token, su expiración
        // y que no haya sido revocado en TokenRepository.
        // Si es válido, retornará el payload decodificado (id_usuario, rol, etc.).
        // Si no es válido, lanzará una excepción → 401.
        //
        // Ejemplo futuro:
        //   $servicio = new TokenService();
        //   $payload  = $servicio->validar($token);
        //   $this->responderExito('Token válido.', $payload);

        $this->responderExito('Endpoint validar token disponible.', [
            'token_recibido' => substr($token, 0, 10) . '...', // No devolver el token completo
        ]);
    }

    /**
     * Acción: renovar el token de acceso usando el token de refresco.
     *
     * Permite al cliente obtener un nuevo token de acceso cuando el actual
     * ha expirado, sin necesidad de que el usuario vuelva a iniciar sesión.
     *
     * POST /api/tokens/renovar
     * Body esperado: { "token_refresco": "eyJ..." }
     */
    private function renovar(): void
    {
        $cuerpo = $this->leerCuerpoJson();

        if (empty($cuerpo['token_refresco'])) {
            $this->responderError('El campo token_refresco es obligatorio.', 422);
        }

        $tokenRefresco = trim((string) $cuerpo['token_refresco']);

        // ► AQUÍ: llamar a TokenService->renovar($tokenRefresco)
        // El servicio verificará que el token de refresco sea válido y no esté
        // revocado en TokenRepository, luego generará y devolverá un nuevo
        // token de acceso con expiración renovada.
        //
        // Ejemplo futuro:
        //   $servicio      = new TokenService();
        //   $nuevoToken    = $servicio->renovar($tokenRefresco);
        //   $this->responderExito('Token renovado correctamente.', $nuevoToken);

        $this->responderExito('Endpoint renovar token disponible.', [
            'token_refresco_recibido' => substr($tokenRefresco, 0, 10) . '...',
        ]);
    }

    /**
     * Acción: revocar un token explícitamente (cerrar sesión de un dispositivo).
     *
     * Invalida el token recibido para que no pueda volver a usarse,
     * aunque técnicamente aún no haya expirado.
     * Útil para implementar cierre de sesión remoto o en múltiples dispositivos.
     *
     * DELETE /api/tokens/eliminar
     * Body esperado: { "token": "eyJ..." }
     */
    private function eliminar(): void
    {
        $cuerpo = $this->leerCuerpoJson();

        if (empty($cuerpo['token'])) {
            $this->responderError('El campo token es obligatorio.', 422);
        }

        $token = trim((string) $cuerpo['token']);

        // ► AQUÍ: llamar a TokenService->revocar($token)
        // El servicio añadirá el token a la lista de revocados en TokenRepository
        // (lista negra) para que futuras validaciones lo rechacen.
        //
        // Ejemplo futuro:
        //   $servicio = new TokenService();
        //   $servicio->revocar($token);
        //   $this->responderExito('Token revocado correctamente.', []);

        $this->responderExito('Endpoint eliminar token disponible.', []);
    }

    // -------------------------------------------------------------------------
    // Métodos auxiliares internos
    // -------------------------------------------------------------------------

    /**
     * Verifica el método HTTP y ejecuta el callback si es correcto.
     * Si el método no coincide, responde con 405 y detiene la ejecución.
     *
     * @param string   $metodoRecibido  Método HTTP que llegó en la petición.
     * @param string   $metodoEsperado  Método HTTP que esta acción requiere.
     * @param callable $callback        Función a ejecutar si el método es válido.
     */
    private function despacharConMetodo(
        string   $metodoRecibido,
        string   $metodoEsperado,
        callable $callback
    ): void {
        if ($metodoRecibido !== $metodoEsperado) {
            header('Allow: ' . $metodoEsperado);
            $this->responderError(
                "Este endpoint solo acepta {$metodoEsperado}, se recibió {$metodoRecibido}.",
                405
            );
        }

        $callback();
    }

    /**
     * Lee y decodifica el cuerpo JSON de la petición entrante.
     * Si el cuerpo está vacío o el JSON es inválido, responde con 400.
     *
     * @return array<string, mixed> Datos decodificados del cuerpo.
     */
    private function leerCuerpoJson(): array
    {
        $contenidoCrudo = file_get_contents('php://input');

        if ($contenidoCrudo === false || $contenidoCrudo === '') {
            $this->responderError('El cuerpo de la petición está vacío.', 400);
        }

        $datos = json_decode($contenidoCrudo, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->responderError('El cuerpo de la petición no es JSON válido.', 400);
        }

        return $datos ?? [];
    }

    /**
     * Envía una respuesta JSON de éxito y detiene la ejecución.
     *
     * Formato: { "success": true, "message": "...", "data": { ... } }
     *
     * @param string               $mensaje Descripción legible del resultado.
     * @param array<string, mixed> $datos   Payload de la respuesta.
     * @param int                  $codigo  Código HTTP de respuesta.
     */
    private function responderExito(string $mensaje, array $datos = [], int $codigo = 200): never
    {
        http_response_code($codigo);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'success' => true,
            'message' => $mensaje,
            'data'    => $datos,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Envía una respuesta JSON de error y detiene la ejecución.
     *
     * Formato: { "success": false, "message": "..." }
     *
     * @param string $mensaje Descripción legible del error.
     * @param int    $codigo  Código HTTP de error.
     */
    private function responderError(string $mensaje, int $codigo): never
    {
        http_response_code($codigo);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'success' => false,
            'message' => $mensaje,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}