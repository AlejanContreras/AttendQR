<?php

declare(strict_types=1);

/**
 * AttendQR – SesionController
 *
 * Responsabilidad: recibir peticiones HTTP del módulo de sesiones,
 * delegar al SesionService y devolver respuestas JSON.
 *
 * Rutas:
 *   POST /api/sesiones/crear            → abrir sesión (docente)
 *   GET  /api/sesiones/listar           → listar sesiones (?id_ficha=X&estado=Y)
 *   GET  /api/sesiones/detalle/{id}     → detalle de una sesión
 *   GET  /api/sesiones/activa/{idFicha} → sesión actualmente abierta de una ficha
 *   POST /api/sesiones/cerrar/{id}      → cerrar una sesión
 *
 * Ubicación en el proyecto: Src/Controllers/SesionController.php
 */
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
            'crear'       => $this->despacharConMetodo($metodo, 'POST',
                fn() => $this->crear()
            ),
            'listar'      => $this->despacharConMetodo($metodo, 'GET',
                fn() => $this->listar()
            ),
            'detalle'     => $this->despacharConMetodo($metodo, 'GET',
                fn() => $this->detalle($this->extraerIdRequerido($params, 'sesión'))
            ),
            'activa'      => $this->despacharConMetodo($metodo, 'GET',
                fn() => $this->activa($this->extraerIdRequerido($params, 'ficha'))
            ),
            'cerrar'      => $this->despacharConMetodo($metodo, 'POST',
                fn() => $this->cerrar($this->extraerIdRequerido($params, 'sesión'))
            ),
            'asistencias' => $this->despacharConMetodo($metodo, 'GET',
                fn() => $this->asistencias($this->extraerIdRequerido($params, 'sesión'))
            ),
            'estadisticas' => $this->despacharConMetodo($metodo, 'GET',
                fn() => $this->estadisticas($this->extraerIdRequerido($params, 'sesión'))
            ),
            default        => $this->responderError(
                "Acción '{$accion}' no encontrada en SesionController.", 404
            ),
        };
    }

    // -------------------------------------------------------------------------
    // Acciones
    // -------------------------------------------------------------------------

    /**
     * POST /api/sesiones/crear
     * Body: { "id_ficha": 1, "hora_inicio_clase": "10:00", "nombre_materia": "Bases de Datos" }
     */
    private function crear(): void
    {
        $cuerpo  = $this->leerCuerpoJson();
        $usuario = $this->obtenerUsuarioAutenticado();

        if (empty($cuerpo['id_ficha'])) {
            $this->responderError('El campo id_ficha es obligatorio.', 422);
        }

        if (empty($cuerpo['hora_inicio_clase'])) {
            $this->responderError('La hora de inicio de la clase es obligatoria.', 422);
        }

        $horaInicioClase = trim((string) $cuerpo['hora_inicio_clase']);
        $nombreMateria   = trim((string) ($cuerpo['nombre_materia'] ?? ''));

        // Validar formato HH:MM o HH:MM:SS
        if (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $horaInicioClase)) {
            $this->responderError('La hora de inicio debe tener formato HH:MM (ej: 10:00).', 422);
        }

        // Normalizar a HH:MM:SS para almacenar como TIME
        if (strlen($horaInicioClase) === 5) {
            $horaInicioClase .= ':00';
        }

        $ubicacionActiva = !empty($cuerpo['ubicacion_activa']) && (bool) $cuerpo['ubicacion_activa'];
        $latDocente      = isset($cuerpo['lat_docente'])      ? (float) $cuerpo['lat_docente']      : null;
        $lngDocente      = isset($cuerpo['lng_docente'])      ? (float) $cuerpo['lng_docente']      : null;
        $accuracyDocente = isset($cuerpo['accuracy_docente']) ? (float) $cuerpo['accuracy_docente'] : null;

        if ($ubicacionActiva && ($latDocente === null || $lngDocente === null)) {
            $this->responderError('Se activó validación de ubicación pero no se enviaron coordenadas del docente.', 422);
        }

        try {
            $sesion = $this->servicio->crear(
                (int) $cuerpo['id_ficha'],
                $horaInicioClase,
                $nombreMateria,
                $usuario,
                $ubicacionActiva,
                $latDocente,
                $lngDocente,
                $accuracyDocente
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
     * Query params opcionales: ?id_ficha=1&estado=abierta
     */
    private function listar(): void
    {
        $idFicha = isset($_GET['id_ficha']) ? (int) $_GET['id_ficha'] : null;
        $estado  = $_GET['estado'] ?? null;

        try {
            $resultado = $this->servicio->listar($idFicha, $estado);
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
     * GET /api/sesiones/activa/{idFicha}
     */
    private function activa(int $idFicha): void
    {
        try {
            $sesion = $this->servicio->sesionActivaPorFicha($idFicha);
            $this->responderExito('Sesión activa encontrada.', $sesion);
        } catch (\RuntimeException $e) {
            $this->responderError($e->getMessage(), $e->getCode() ?: 404);
        } catch (\Throwable $e) {
            $this->responderError('Error interno al obtener la sesión activa.', 500);
        }
    }

    /**
     * GET /api/sesiones/asistencias/{idSesion}
     * Lista todos los registros de asistencia de la sesión con datos del aprendiz.
     */
    private function asistencias(int $idSesion): void
    {
        try {
            $resultado = $this->servicio->asistenciasDeSesion($idSesion);
            $this->responderExito('Asistencias de la sesión obtenidas correctamente.', $resultado);
        } catch (\RuntimeException $e) {
            $this->responderError($e->getMessage(), $e->getCode() ?: 404);
        } catch (\Throwable $e) {
            $this->responderError('Error interno al obtener las asistencias de la sesión.', 500);
        }
    }

    /**
     * GET /api/sesiones/estadisticas/{idSesion}
     * Resumen estadístico: presentes, retardos, sin registro, porcentaje de asistencia.
     */
    private function estadisticas(int $idSesion): void
    {
        try {
            $resultado = $this->servicio->estadisticasDeSesion($idSesion);
            $this->responderExito('Estadísticas de la sesión obtenidas correctamente.', $resultado);
        } catch (\RuntimeException $e) {
            $this->responderError($e->getMessage(), $e->getCode() ?: 404);
        } catch (\Throwable $e) {
            $this->responderError('Error interno al calcular las estadísticas de la sesión.', 500);
        }
    }

    /**
     * POST /api/sesiones/cerrar/{idSesion}
     */
    private function cerrar(int $idSesion): void
    {
        $usuario = $this->obtenerUsuarioAutenticado();

        try {
            $resultado = $this->servicio->cerrar($idSesion, $usuario);
            $this->responderExito('Sesión cerrada correctamente.', $resultado);
        } catch (\RuntimeException $e) {
            $this->responderError($e->getMessage(), $e->getCode() ?: 400);
        } catch (\Throwable $e) {
            $this->responderError('Error interno al cerrar la sesión.', 500);
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
