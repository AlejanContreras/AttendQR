<?php

declare(strict_types=1);

/**
 * AttendQR – AsistenciaController
 *
 * Responsabilidad: gestionar el módulo de asistencias.
 * Actúa exclusivamente como capa Controller dentro de la arquitectura:
 *
 *   api.php → AsistenciaController → [AsistenciaService] → [AsistenciaRepository] → [Modelo]
 *
 * Rutas que maneja este controlador:
 *   POST /api/asistencias/registrar          → registrar la asistencia de un estudiante
 *   GET  /api/asistencias/consultar/{id}     → consultar una asistencia por ID
 *   GET  /api/asistencias/historial/{id}     → historial de asistencias de un estudiante
 *   POST /api/asistencias/validar            → validar si una asistencia ya fue registrada
 *   DELETE /api/asistencias/eliminar/{id}    → eliminar un registro de asistencia
 *
 * Convención de parámetros posicionales:
 *   $params[0] → ID de la entidad sobre la que opera la acción (cuando aplica)
 *
 * Ubicación en el proyecto: Src/Controllers/AsistenciaController.php
 */
class AsistenciaController
{
    // -------------------------------------------------------------------------
    // Punto de entrada principal
    // -------------------------------------------------------------------------

    /**
     * Recibe la petición del router y la despacha a la acción correspondiente.
     *
     * El router de api.php garantiza que $metodo ya está en mayúsculas.
     * Este método no contiene lógica de negocio: solo enruta y delega.
     *
     * @param string   $metodo  Método HTTP recibido (GET, POST, DELETE, etc.)
     * @param string   $accion  Segundo segmento de la URL (/api/asistencias/{accion})
     * @param string[] $params  Parámetros posicionales adicionales (p. ej. IDs)
     */
    public function handle(string $metodo, string $accion, array $params): void
    {
        match ($accion) {
            // Registrar una nueva asistencia — solo POST
            'registrar' => $this->despacharConMetodo($metodo, 'POST',
                fn() => $this->registrar()
            ),

            // Consultar una asistencia por su ID — solo GET
            'consultar' => $this->despacharConMetodo($metodo, 'GET',
                fn() => $this->consultar(
                    $this->extraerIdRequerido($params, 'asistencia')
                )
            ),

            // Historial completo de asistencias de un estudiante — solo GET
            'historial' => $this->despacharConMetodo($metodo, 'GET',
                fn() => $this->historial(
                    $this->extraerIdRequerido($params, 'estudiante')
                )
            ),

            // Validar si una asistencia ya fue registrada — solo POST
            'validar' => $this->despacharConMetodo($metodo, 'POST',
                fn() => $this->validar()
            ),

            // Eliminar un registro de asistencia — solo DELETE
            'eliminar' => $this->despacharConMetodo($metodo, 'DELETE',
                fn() => $this->eliminar(
                    $this->extraerIdRequerido($params, 'asistencia')
                )
            ),

            // Acción no reconocida → 404
            default => $this->responderError(
                "Acción '{$accion}' no encontrada en AsistenciaController.",
                404
            ),
        };
    }

    // -------------------------------------------------------------------------
    // Acciones del controlador
    // -------------------------------------------------------------------------

    /**
     * Acción: registrar la asistencia de un estudiante en una sesión activa.
     *
     * Recibe los datos del estudiante y la sesión, delega la validación
     * y persistencia al AsistenciaService.
     *
     * POST /api/asistencias/registrar
     * Body esperado: { "id_estudiante": 42, "id_sesion": 15 }
     */
    private function registrar(): void
    {
        $cuerpo = $this->leerCuerpoJson();

        // Validar presencia de los campos mínimos obligatorios
        if (empty($cuerpo['id_estudiante']) || empty($cuerpo['id_sesion'])) {
            $this->responderError('Los campos id_estudiante e id_sesion son obligatorios.', 422);
        }

        $idEstudiante = (int) $cuerpo['id_estudiante'];
        $idSesion     = (int) $cuerpo['id_sesion'];

        // ► AQUÍ: llamar a AsistenciaService->registrar($idEstudiante, $idSesion)
        // El servicio verificará:
        //   1. Que la sesión exista y esté activa.
        //   2. Que el estudiante exista y esté inscrito en la materia.
        //   3. Que no haya registrado asistencia ya en esta sesión.
        //   4. Guardará el registro vía AsistenciaRepository.
        //   5. Retornará el objeto asistencia creado.
        //
        // Ejemplo futuro:
        //   $servicio    = new AsistenciaService();
        //   $asistencia  = $servicio->registrar($idEstudiante, $idSesion);
        //   $this->responderExito('Asistencia registrada correctamente.', $asistencia, 201);

        $this->responderExito('Endpoint registrar disponible.', [
            'id_estudiante' => $idEstudiante,
            'id_sesion'     => $idSesion,
        ], 200);
    }

    /**
     * Acción: consultar una asistencia específica por su ID.
     *
     * Devuelve los datos completos del registro: estudiante, sesión,
     * materia, fecha y hora de registro.
     *
     * GET /api/asistencias/consultar/{idAsistencia}
     *
     * @param int $idAsistencia Identificador único del registro de asistencia.
     */
    private function consultar(int $idAsistencia): void
    {
        // ► AQUÍ: llamar a AsistenciaService->obtenerPorId($idAsistencia)
        // El servicio consultará el AsistenciaRepository.
        // Si el registro no existe, lanzará una excepción → 404.
        //
        // Ejemplo futuro:
        //   $servicio   = new AsistenciaService();
        //   $asistencia = $servicio->obtenerPorId($idAsistencia);
        //   $this->responderExito('Asistencia encontrada.', $asistencia);

        $this->responderExito('Endpoint consultar disponible.', [
            'id_asistencia' => $idAsistencia,
        ]);
    }

    /**
     * Acción: obtener el historial completo de asistencias de un estudiante.
     *
     * Devuelve todas las sesiones a las que el estudiante ha asistido,
     * ordenadas por fecha descendente.
     *
     * GET /api/asistencias/historial/{idEstudiante}
     * Query params opcionales: ?id_materia=3&fecha_inicio=2025-03-01&fecha_fin=2025-06-30
     *
     * @param int $idEstudiante Identificador único del estudiante.
     */
    private function historial(int $idEstudiante): void
    {
        // Leer filtros opcionales desde la query string
        $filtroMateria    = isset($_GET['id_materia'])    ? (int)    $_GET['id_materia']    : null;
        $filtroFechaInicio = $_GET['fecha_inicio'] ?? null;
        $filtroFechaFin    = $_GET['fecha_fin']    ?? null;

        // ► AQUÍ: llamar a AsistenciaService->historialEstudiante(...)
        // El servicio aplicará los filtros en el AsistenciaRepository
        // y retornará el listado paginado de asistencias del estudiante.
        //
        // Ejemplo futuro:
        //   $servicio  = new AsistenciaService();
        //   $historial = $servicio->historialEstudiante(
        //       $idEstudiante, $filtroMateria, $filtroFechaInicio, $filtroFechaFin
        //   );
        //   $this->responderExito('Historial obtenido correctamente.', $historial);

        $this->responderExito('Endpoint historial disponible.', [
            'id_estudiante'  => $idEstudiante,
            'filtro_materia' => $filtroMateria,
            'fecha_inicio'   => $filtroFechaInicio,
            'fecha_fin'      => $filtroFechaFin,
        ]);
    }

    /**
     * Acción: validar si un estudiante ya registró asistencia en una sesión.
     *
     * Permite verificar duplicados antes de intentar un registro,
     * útil para el flujo de escaneo QR del lado del cliente.
     *
     * POST /api/asistencias/validar
     * Body esperado: { "id_estudiante": 42, "id_sesion": 15 }
     */
    private function validar(): void
    {
        $cuerpo = $this->leerCuerpoJson();

        if (empty($cuerpo['id_estudiante']) || empty($cuerpo['id_sesion'])) {
            $this->responderError('Los campos id_estudiante e id_sesion son obligatorios.', 422);
        }

        $idEstudiante = (int) $cuerpo['id_estudiante'];
        $idSesion     = (int) $cuerpo['id_sesion'];

        // ► AQUÍ: llamar a AsistenciaService->yaRegistrado($idEstudiante, $idSesion)
        // El servicio consultará si existe un registro previo en la sesión.
        // Retornará true/false sin lanzar excepción.
        //
        // Ejemplo futuro:
        //   $servicio     = new AsistenciaService();
        //   $yaRegistrado = $servicio->yaRegistrado($idEstudiante, $idSesion);
        //   $this->responderExito('Validación completada.', ['ya_registrado' => $yaRegistrado]);

        $this->responderExito('Endpoint validar disponible.', [
            'id_estudiante' => $idEstudiante,
            'id_sesion'     => $idSesion,
        ]);
    }

    /**
     * Acción: eliminar un registro de asistencia.
     *
     * Operación reservada para roles administrativos.
     * La autorización por rol se implementará en el Middleware de autenticación.
     *
     * DELETE /api/asistencias/eliminar/{idAsistencia}
     *
     * @param int $idAsistencia Identificador único del registro a eliminar.
     */
    private function eliminar(int $idAsistencia): void
    {
        // ► AQUÍ: llamar a AsistenciaService->eliminar($idAsistencia)
        // El servicio verificará que el registro existe antes de eliminarlo.
        // Si no existe, lanzará una excepción → 404.
        // La autorización por rol se verificará en el Middleware (capa superior).
        //
        // Ejemplo futuro:
        //   $servicio = new AsistenciaService();
        //   $servicio->eliminar($idAsistencia);
        //   $this->responderExito('Asistencia eliminada correctamente.', []);

        $this->responderExito('Endpoint eliminar disponible.', [
            'id_asistencia' => $idAsistencia,
        ]);
    }

    // -------------------------------------------------------------------------
    // Métodos auxiliares internos
    // -------------------------------------------------------------------------

    /**
     * Verifica el método HTTP y ejecuta el callback si es correcto.
     * Si el método no coincide, responde con 405 y detiene la ejecución.
     *
     * Este método centraliza la verificación para evitar repetirla
     * dentro de cada case del match() en handle().
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

        // El método es correcto: ejecutar la acción correspondiente
        $callback();
    }

    /**
     * Extrae y valida un ID numérico desde los parámetros posicionales.
     * Si no existe o no es un número entero positivo, responde con 400.
     *
     * @param string[] $params        Parámetros posicionales del router.
     * @param string   $nombreEntidad Nombre de la entidad (para el mensaje de error).
     * @return int ID validado y convertido a entero.
     */
    private function extraerIdRequerido(array $params, string $nombreEntidad): int
    {
        if (empty($params[0]) || !ctype_digit((string) $params[0])) {
            $this->responderError(
                "Se requiere un ID numérico válido para {$nombreEntidad}.",
                400
            );
        }

        return (int) $params[0];
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
     * Formato estándar:
     *   { "success": true, "message": "...", "data": { ... } }
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
     * Formato estándar:
     *   { "success": false, "message": "..." }
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