<?php

declare(strict_types=1);

/**
 * AttendQR – HealthController
 *
 * Responsabilidad: exponer el estado operativo de la API y sus dependencias.
 * Este controlador no requiere autenticación y no modifica ningún dato.
 * Es el único controller del sistema cuyas rutas son completamente públicas.
 *
 * Casos de uso:
 *   - Verificar que la API responde antes de ejecutar pruebas.
 *   - Monitorear el estado de la base de datos y servicios externos.
 *   - Obtener la versión desplegada del backend.
 *
 * Arquitectura:
 *   api.php → HealthController → [HealthService] → [Database / dependencias]
 *
 * Rutas que maneja este controlador:
 *   GET /api/health/status    → estado general de la API y sus dependencias
 *   GET /api/health/ping      → respuesta mínima para verificar que la API vive
 *   GET /api/health/version   → versión actual del backend desplegada
 *
 * Nota: todas las acciones son GET. Este controller nunca recibe body JSON.
 *
 * Ubicación en el proyecto: Src/Controllers/HealthController.php
 */
class HealthController
{
    // Versión actual del backend
    // ► ACTUALIZAR este valor en cada despliegue a producción ◄
    private const VERSION = '0.1.0';

    // -------------------------------------------------------------------------
    // Punto de entrada principal
    // -------------------------------------------------------------------------

    /**
     * Recibe la petición del router y la despacha a la acción correspondiente.
     *
     * No contiene lógica de negocio: únicamente enruta y delega.
     *
     * @param string   $metodo  Método HTTP recibido (solo GET es permitido)
     * @param string   $accion  Segundo segmento de la URL (/api/health/{accion})
     * @param string[] $params  Parámetros posicionales (no usados en este controller)
     */
    public function handle(string $metodo, string $accion, array $params): void
    {
        match ($accion) {

            // Estado general de la API y sus dependencias — solo GET
            'status' => $this->despacharConMetodo($metodo, 'GET',
                fn() => $this->status()
            ),

            // Respuesta mínima de vida de la API — solo GET
            'ping' => $this->despacharConMetodo($metodo, 'GET',
                fn() => $this->ping()
            ),

            // Información de versión del backend — solo GET
            'version' => $this->despacharConMetodo($metodo, 'GET',
                fn() => $this->version()
            ),

            // Acción no reconocida → 404
            default => $this->responderError(
                "Acción '{$accion}' no encontrada en HealthController.",
                404
            ),
        };
    }

    // -------------------------------------------------------------------------
    // Acciones del controlador
    // -------------------------------------------------------------------------

    /**
     * Acción: estado general de la API y sus dependencias.
     *
     * Devuelve el estado de cada componente del sistema:
     * API, base de datos y, en el futuro, servicios externos (correo, QR, etc.).
     * Si alguna dependencia crítica falla, responde con código 503.
     *
     * GET /api/health/status
     */
    private function status(): void
    {
        // ► AQUÍ: llamar a HealthService->verificarDependencias()
        // El servicio verificará la conexión a la base de datos (ping PDO),
        // el estado de servicios externos y construirá el objeto de respuesta.
        // Si la base de datos no responde, el código HTTP debe ser 503.
        //
        // Ejemplo futuro:
        //   $servicio      = new HealthService();
        //   $dependencias  = $servicio->verificarDependencias();
        //   $todoOk        = $servicio->todoOperativo($dependencias);
        //   $codigo        = $todoOk ? 200 : 503;
        //   $this->responderExito('Estado del sistema obtenido.', $dependencias, $codigo);

        $this->responderExito('Estado del sistema disponible.', [
            'api'           => 'operativa',
            'base_de_datos' => 'pendiente de verificar',
            'version'       => self::VERSION,
            'timestamp'     => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Acción: respuesta mínima de vida de la API (ping).
     *
     * Endpoint ultraligero: no consulta la base de datos ni ninguna dependencia.
     * Útil para verificar que el servidor Apache y PHP están respondiendo
     * antes de ejecutar pruebas más complejas.
     *
     * GET /api/health/ping
     */
    private function ping(): void
    {
        // Esta acción no requiere Service: responde inmediatamente con datos estáticos.
        // Es intencionalmente mínima para maximizar la velocidad de respuesta.
        $this->responderExito('pong', [
            'timestamp' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Acción: información de versión del backend.
     *
     * Devuelve la versión actual del backend y metadatos de la build.
     * Útil para verificar que el despliegue correcto está activo.
     *
     * GET /api/health/version
     */
    private function version(): void
    {
        // ► AQUÍ: opcionalmente llamar a HealthService->metadatosBuild()
        // El servicio puede leer el archivo VERSION o composer.json para
        // devolver información más detallada de la build (hash del commit, fecha).
        //
        // Ejemplo futuro:
        //   $servicio  = new HealthService();
        //   $metadatos = $servicio->metadatosBuild();
        //   $this->responderExito('Versión obtenida.', $metadatos);

        $this->responderExito('Versión del sistema obtenida.', [
            'version'   => self::VERSION,
            'proyecto'  => 'AttendQR',
            'timestamp' => date('Y-m-d H:i:s'),
        ]);
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