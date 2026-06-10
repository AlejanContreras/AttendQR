<?php

declare(strict_types=1);

/**
 * AttendQR – AuthController
 *
 * Responsabilidad: gestionar todo lo relacionado con autenticación de usuarios.
 *
 * Rutas que maneja este controlador:
 *   POST /api/auth/login       → iniciar sesión
 *   POST /api/auth/logout      → cerrar sesión
 *   GET  /api/auth/verificar   → verificar si el token actual es válido
 *
 * Ubicación en el proyecto: Src/Controllers/AuthController.php
 */
class AuthController
{
    // -------------------------------------------------------------------------
    // Punto de entrada principal
    // -------------------------------------------------------------------------

    /**
     * Recibe la petición del router y la despacha a la acción correspondiente.
     *
     * @param string   $metodo  Método HTTP (GET, POST, etc.)
     * @param string   $accion  Segundo segmento de la URL (/api/auth/{accion})
     * @param string[] $params  Parámetros posicionales adicionales (ej. IDs)
     */
    public function handle(string $metodo, string $accion, array $params): void
    {
        // Seleccionar la acción según el segmento de la URL y el método HTTP
        switch ($accion) {

            case 'login':
                // Solo se permite POST para iniciar sesión
                $this->verificarMetodo($metodo, 'POST');
                $this->login();
                break;

            case 'logout':
                // Solo se permite POST para cerrar sesión
                $this->verificarMetodo($metodo, 'POST');
                $this->logout();
                break;

            case 'verificar':
                // Solo se permite GET para verificar el token
                $this->verificarMetodo($metodo, 'GET');
                $this->verificarToken();
                break;

            default:
                // Si la acción no existe, responder con 404
                $this->responderError("Acción '{$accion}' no encontrada en AuthController.", 404);
        }
    }

    // -------------------------------------------------------------------------
    // Acciones del controlador
    // -------------------------------------------------------------------------

    /**
     * Acción: iniciar sesión.
     *
     * Recibe las credenciales del usuario (correo y contraseña),
     * las valida y devuelve un token de autenticación si son correctas.
     *
     * POST /api/auth/login
     * Body esperado: { "correo": "...", "contrasena": "..." }
     */
    private function login(): void
    {
        // Leer y decodificar el cuerpo JSON de la petición
        $cuerpo = $this->leerCuerpoJson();

        // Validar que los campos obligatorios estén presentes
        if (empty($cuerpo['correo']) || empty($cuerpo['contrasena'])) {
            $this->responderError('Los campos correo y contrasena son obligatorios.', 422);
        }

        $correo     = trim($cuerpo['correo']);
        $contrasena = $cuerpo['contrasena'];

        // ► AQUÍ: llamar a AuthService->login($correo, $contrasena)
        // El servicio consultará el UserRepository, verificará la contraseña
        // con password_verify() y generará el token JWT o de sesión.
        //
        // Ejemplo futuro:
        //   $servicio = new AuthService();
        //   $resultado = $servicio->login($correo, $contrasena);
        //   $this->responderJson($resultado, 200);

        // Respuesta temporal mientras se implementa la lógica real
        $this->responderJson([
            'mensaje' => 'Acción login recibida correctamente. Lógica pendiente de implementar.',
            'correo'  => $correo,
        ], 200);
    }

    /**
     * Acción: cerrar sesión.
     *
     * Invalida el token activo del usuario autenticado.
     *
     * POST /api/auth/logout
     * Header esperado: Authorization: Bearer {token}
     */
    private function logout(): void
    {
        // ► AQUÍ: leer el token del header Authorization
        // ► AQUÍ: llamar a AuthService->logout($token)
        // El servicio invalidará el token en base de datos o en la lista negra.
        //
        // Ejemplo futuro:
        //   $token = $this->leerTokenHeader();
        //   $servicio = new AuthService();
        //   $servicio->logout($token);
        //   $this->responderJson(['mensaje' => 'Sesión cerrada correctamente.'], 200);

        $this->responderJson([
            'mensaje' => 'Acción logout recibida correctamente. Lógica pendiente de implementar.',
        ], 200);
    }

    /**
     * Acción: verificar token.
     *
     * Comprueba si el token enviado en el header es válido y no ha expirado.
     *
     * GET /api/auth/verificar
     * Header esperado: Authorization: Bearer {token}
     */
    private function verificarToken(): void
    {
        // ► AQUÍ: leer el token del header Authorization
        // ► AQUÍ: llamar a AuthService->verificarToken($token)
        // El servicio devolverá los datos del usuario si el token es válido,
        // o lanzará una excepción si no lo es.
        //
        // Ejemplo futuro:
        //   $token = $this->leerTokenHeader();
        //   $servicio = new AuthService();
        //   $usuario = $servicio->verificarToken($token);
        //   $this->responderJson(['valido' => true, 'usuario' => $usuario], 200);

        $this->responderJson([
            'mensaje' => 'Acción verificarToken recibida correctamente. Lógica pendiente de implementar.',
        ], 200);
    }

    // -------------------------------------------------------------------------
    // Métodos auxiliares internos
    // -------------------------------------------------------------------------

    /**
     * Verifica que el método HTTP de la petición sea el esperado.
     * Si no coincide, responde con 405 y detiene la ejecución.
     *
     * @param string $metodoRecibido  Método HTTP que llegó en la petición.
     * @param string $metodoEsperado  Método HTTP que esta acción requiere.
     */
    private function verificarMetodo(string $metodoRecibido, string $metodoEsperado): void
    {
        if ($metodoRecibido !== $metodoEsperado) {
            header('Allow: ' . $metodoEsperado);
            $this->responderError(
                "Este endpoint solo acepta {$metodoEsperado}, se recibió {$metodoRecibido}.",
                405
            );
        }
    }

    /**
     * Lee y decodifica el cuerpo JSON de la petición entrante.
     * Si el cuerpo está vacío o malformado, responde con 400.
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
     * Envía una respuesta JSON exitosa y detiene la ejecución.
     *
     * @param mixed $datos  Datos a serializar.
     * @param int   $codigo Código HTTP de respuesta.
     */
    private function responderJson(mixed $datos, int $codigo = 200): never
    {
        http_response_code($codigo);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Envía una respuesta JSON de error y detiene la ejecución.
     *
     * @param string $mensaje Descripción del error.
     * @param int    $codigo  Código HTTP de error.
     */
    private function responderError(string $mensaje, int $codigo): never
    {
        $this->responderJson(['error' => $mensaje, 'codigo' => $codigo], $codigo);
    }
}