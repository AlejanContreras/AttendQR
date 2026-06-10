<?php

declare(strict_types=1);

/**
 * AttendQR – JornadaController
 *
 * Responsabilidad: gestionar las jornadas académicas del sistema.
 * Una "jornada" define el turno en que se desarrolla la formación
 * (mañana, tarde, noche, madrugada, fines de semana, etc.).
 *
 * Arquitectura:
 *   api.php → JornadaController → [JornadaService] → [JornadaRepository] → [Modelo]
 *
 * Rutas que maneja este controlador:
 *   GET    /api/jornadas/listar             → listar todas las jornadas
 *   GET    /api/jornadas/consultar/{id}     → consultar una jornada por su ID
 *   POST   /api/jornadas/crear             → registrar una nueva jornada
 *   PUT    /api/jornadas/actualizar/{id}   → actualizar los datos de una jornada
 *   DELETE /api/jornadas/eliminar/{id}     → eliminar una jornada del sistema
 *
 * Convención de parámetros posicionales:
 *   $params[0] → ID de la jornada sobre la que opera la acción (cuando aplica)
 *
 * Ubicación en el proyecto: Src/Controllers/JornadaController.php
 */
class JornadaController
{
    // -------------------------------------------------------------------------
    // Punto de entrada principal
    // -------------------------------------------------------------------------

    /**
     * Recibe la petición del router y la despacha a la acción correspondiente.
     *
     * No contiene lógica de negocio: únicamente enruta y delega.
     *
     * @param string   $metodo  Método HTTP recibido (GET, POST, PUT, DELETE, etc.)
     * @param string   $accion  Segundo segmento de la URL (/api/jornadas/{accion})
     * @param string[] $params  Parámetros posicionales adicionales (p. ej. IDs)
     */
    public function handle(string $metodo, string $accion, array $params): void
    {
        match ($accion) {

            // Listar todas las jornadas registradas — solo GET
            'listar' => $this->despacharConMetodo($metodo, 'GET',
                fn() => $this->listar()
            ),

            // Consultar una jornada específica por su ID — solo GET
            'consultar' => $this->despacharConMetodo($metodo, 'GET',
                fn() => $this->consultar(
                    $this->extraerIdRequerido($params, 'jornada')
                )
            ),

            // Registrar una nueva jornada en el sistema — solo POST
            'crear' => $this->despacharConMetodo($metodo, 'POST',
                fn() => $this->crear()
            ),

            // Actualizar los datos de una jornada existente — solo PUT
            'actualizar' => $this->despacharConMetodo($metodo, 'PUT',
                fn() => $this->actualizar(
                    $this->extraerIdRequerido($params, 'jornada')
                )
            ),

            // Eliminar una jornada del sistema — solo DELETE
            'eliminar' => $this->despacharConMetodo($metodo, 'DELETE',
                fn() => $this->eliminar(
                    $this->extraerIdRequerido($params, 'jornada')
                )
            ),

            // Acción no reconocida → 404
            default => $this->responderError(
                "Acción '{$accion}' no encontrada en JornadaController.",
                404
            ),
        };
    }

    // -------------------------------------------------------------------------
    // Acciones del controlador
    // -------------------------------------------------------------------------

    /**
     * Acción: listar todas las jornadas disponibles en el sistema.
     *
     * Devuelve el catálogo completo de jornadas. Al ser un catálogo pequeño
     * y relativamente estático, generalmente no requiere paginación.
     * Admitirá filtro por estado cuando se integre el Service.
     *
     * GET /api/jornadas/listar
     * Query params opcionales: ?estado=activa
     */
    private function listar(): void
    {
        // Capturar filtro opcional desde la query string
        $filtroEstado = $_GET['estado'] ?? null;

        // ► AQUÍ: llamar a JornadaService->listar($filtroEstado)
        // El servicio consultará el JornadaRepository y retornará
        // el listado de jornadas, opcionalmente filtrado por estado.
        //
        // Ejemplo futuro:
        //   $servicio = new JornadaService();
        //   $jornadas = $servicio->listar($filtroEstado);
        //   $this->responderExito('Jornadas obtenidas correctamente.', $jornadas);

        $this->responderExito('Endpoint listar jornadas disponible.', [
            'filtro_estado' => $filtroEstado,
        ]);
    }

    /**
     * Acción: consultar una jornada específica por su ID.
     *
     * Devuelve los datos completos de la jornada: nombre, horario de inicio,
     * horario de fin, estado y fichas asociadas.
     *
     * GET /api/jornadas/consultar/{idJornada}
     *
     * @param int $idJornada Identificador único de la jornada.
     */
    private function consultar(int $idJornada): void
    {
        // ► AQUÍ: llamar a JornadaService->obtenerPorId($idJornada)
        // El servicio buscará la jornada en la base de datos mediante
        // JornadaRepository. Si no existe, lanzará una excepción → 404.
        //
        // Ejemplo futuro:
        //   $servicio = new JornadaService();
        //   $jornada  = $servicio->obtenerPorId($idJornada);
        //   $this->responderExito('Jornada encontrada.', $jornada);

        $this->responderExito('Endpoint consultar jornada disponible.', [
            'id_jornada' => $idJornada,
        ]);
    }

    /**
     * Acción: registrar una nueva jornada en el sistema.
     *
     * Crea una jornada con su nombre, horario de inicio y horario de fin.
     * Ejemplos de jornadas: Mañana (06:00–12:00), Tarde (12:00–18:00),
     * Noche (18:00–22:00), Madrugada (22:00–06:00).
     *
     * POST /api/jornadas/crear
     * Body esperado: {
     *   "nombre":         "Mañana",
     *   "hora_inicio":    "06:00",
     *   "hora_fin":       "12:00"
     * }
     */
    private function crear(): void
    {
        $cuerpo = $this->leerCuerpoJson();

        // Validar campos mínimos obligatorios para crear la jornada
        if (empty($cuerpo['nombre'])) {
            $this->responderError('El campo nombre es obligatorio.', 422);
        }

        $nombre      = trim((string) $cuerpo['nombre']);
        $horaInicio  = isset($cuerpo['hora_inicio']) ? trim((string) $cuerpo['hora_inicio']) : null;
        $horaFin     = isset($cuerpo['hora_fin'])    ? trim((string) $cuerpo['hora_fin'])    : null;

        // ► AQUÍ: llamar a JornadaService->crear($nombre, $horaInicio, $horaFin)
        // El servicio verificará que no exista una jornada con el mismo nombre,
        // validará el formato de las horas y guardará el registro
        // mediante JornadaRepository.
        //
        // Ejemplo futuro:
        //   $servicio = new JornadaService();
        //   $jornada  = $servicio->crear($nombre, $horaInicio, $horaFin);
        //   $this->responderExito('Jornada creada correctamente.', $jornada, 201);

        $this->responderExito('Endpoint crear jornada disponible.', [
            'nombre'      => $nombre,
            'hora_inicio' => $horaInicio,
            'hora_fin'    => $horaFin,
        ], 201);
    }

    /**
     * Acción: actualizar los datos de una jornada existente.
     *
     * Permite modificar el nombre, los horarios o el estado de la jornada.
     * Los campos no enviados en el body se conservan sin cambios.
     *
     * PUT /api/jornadas/actualizar/{idJornada}
     * Body esperado (campos opcionales): { "nombre": "Tarde", "hora_inicio": "12:00" }
     *
     * @param int $idJornada Identificador único de la jornada a actualizar.
     */
    private function actualizar(int $idJornada): void
    {
        $cuerpo = $this->leerCuerpoJson();

        if (empty($cuerpo)) {
            $this->responderError('No se recibieron datos para actualizar.', 422);
        }

        // ► AQUÍ: llamar a JornadaService->actualizar($idJornada, $cuerpo)
        // El servicio validará los campos enviados, verificará que la jornada
        // exista y que el nuevo nombre no esté duplicado, y aplicará los
        // cambios mediante JornadaRepository.
        //
        // Ejemplo futuro:
        //   $servicio = new JornadaService();
        //   $jornada  = $servicio->actualizar($idJornada, $cuerpo);
        //   $this->responderExito('Jornada actualizada correctamente.', $jornada);

        $this->responderExito('Endpoint actualizar jornada disponible.', [
            'id_jornada' => $idJornada,
            'datos'      => $cuerpo,
        ]);
    }

    /**
     * Acción: eliminar una jornada del sistema.
     *
     * Operación reservada para roles administrativos.
     * La verificación de permisos se implementará en el Middleware de autenticación.
     * El Service verificará que no existan fichas activas vinculadas antes de eliminar.
     *
     * DELETE /api/jornadas/eliminar/{idJornada}
     *
     * @param int $idJornada Identificador único de la jornada a eliminar.
     */
    private function eliminar(int $idJornada): void
    {
        // ► AQUÍ: llamar a JornadaService->eliminar($idJornada)
        // El servicio verificará que la jornada exista y que no tenga
        // fichas o sesiones activas vinculadas antes de proceder.
        // La autorización por rol corresponde al Middleware (capa superior).
        //
        // Ejemplo futuro:
        //   $servicio = new JornadaService();
        //   $servicio->eliminar($idJornada);
        //   $this->responderExito('Jornada eliminada correctamente.', []);

        $this->responderExito('Endpoint eliminar jornada disponible.', [
            'id_jornada' => $idJornada,
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
     * Extrae y valida un ID numérico desde los parámetros posicionales.
     * Si no existe o no es un entero positivo, responde con 400.
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
