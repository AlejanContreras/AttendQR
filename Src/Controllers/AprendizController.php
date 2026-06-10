<?php

declare(strict_types=1);

/**
 * AttendQR – AprendizController
 *
 * Responsabilidad: gestionar los aprendices del sistema.
 * Un "aprendiz" es el estudiante inscrito en una ficha de formación del SENA.
 *
 * Arquitectura:
 *   api.php → AprendizController → [AprendizService] → [AprendizRepository] → [Modelo]
 *
 * Rutas que maneja este controlador:
 *   GET    /api/aprendices/listar              → listar aprendices (con filtros opcionales)
 *   GET    /api/aprendices/consultar/{id}      → consultar un aprendiz por su ID
 *   POST   /api/aprendices/registrar          → registrar un nuevo aprendiz
 *   PUT    /api/aprendices/actualizar/{id}    → actualizar los datos de un aprendiz
 *   DELETE /api/aprendices/eliminar/{id}      → eliminar un aprendiz del sistema
 *
 * Convención de parámetros posicionales:
 *   $params[0] → ID del aprendiz sobre el que opera la acción (cuando aplica)
 *
 * Ubicación en el proyecto: Src/Controllers/AprendizController.php
 */
class AprendizController
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
     * @param string   $accion  Segundo segmento de la URL (/api/aprendices/{accion})
     * @param string[] $params  Parámetros posicionales adicionales (p. ej. IDs)
     */
    public function handle(string $metodo, string $accion, array $params): void
    {
        match ($accion) {

            // Listar todos los aprendices — solo GET
            'listar' => $this->despacharConMetodo($metodo, 'GET',
                fn() => $this->listar()
            ),

            // Consultar un aprendiz específico por su ID — solo GET
            'consultar' => $this->despacharConMetodo($metodo, 'GET',
                fn() => $this->consultar(
                    $this->extraerIdRequerido($params, 'aprendiz')
                )
            ),

            // Registrar un nuevo aprendiz en el sistema — solo POST
            'registrar' => $this->despacharConMetodo($metodo, 'POST',
                fn() => $this->registrar()
            ),

            // Actualizar los datos personales o académicos de un aprendiz — solo PUT
            'actualizar' => $this->despacharConMetodo($metodo, 'PUT',
                fn() => $this->actualizar(
                    $this->extraerIdRequerido($params, 'aprendiz')
                )
            ),

            // Eliminar un aprendiz del sistema — solo DELETE
            'eliminar' => $this->despacharConMetodo($metodo, 'DELETE',
                fn() => $this->eliminar(
                    $this->extraerIdRequerido($params, 'aprendiz')
                )
            ),

            // Acción no reconocida → 404
            default => $this->responderError(
                "Acción '{$accion}' no encontrada en AprendizController.",
                404
            ),
        };
    }

    // -------------------------------------------------------------------------
    // Acciones del controlador
    // -------------------------------------------------------------------------

    /**
     * Acción: listar aprendices registrados en el sistema.
     *
     * Admitirá filtros por ficha, estado y documento cuando se integre el Service.
     *
     * GET /api/aprendices/listar
     * Query params opcionales: ?id_ficha=15&estado=activo&documento=1234567
     */
    private function listar(): void
    {
        // Capturar filtros opcionales desde la query string
        $filtroFicha    = isset($_GET['id_ficha']) ? (int) $_GET['id_ficha'] : null;
        $filtroEstado   = $_GET['estado']    ?? null;
        $filtroDoc      = $_GET['documento'] ?? null;

        // ► AQUÍ: llamar a AprendizService->listar($filtroFicha, $filtroEstado, $filtroDoc)
        // El servicio consultará el AprendizRepository con los filtros recibidos
        // y retornará el listado paginado de aprendices.
        //
        // Ejemplo futuro:
        //   $servicio   = new AprendizService();
        //   $aprendices = $servicio->listar($filtroFicha, $filtroEstado, $filtroDoc);
        //   $this->responderExito('Aprendices obtenidos correctamente.', $aprendices);

        $this->responderExito('Endpoint listar aprendices disponible.', [
            'filtro_ficha'   => $filtroFicha,
            'filtro_estado'  => $filtroEstado,
            'filtro_doc'     => $filtroDoc,
        ]);
    }

    /**
     * Acción: consultar un aprendiz específico por su ID.
     *
     * Devuelve los datos completos del aprendiz: datos personales,
     * ficha de formación, historial de asistencias y estado actual.
     *
     * GET /api/aprendices/consultar/{idAprendiz}
     *
     * @param int $idAprendiz Identificador único del aprendiz.
     */
    private function consultar(int $idAprendiz): void
    {
        // ► AQUÍ: llamar a AprendizService->obtenerPorId($idAprendiz)
        // El servicio buscará el aprendiz en la base de datos mediante
        // AprendizRepository. Si no existe, lanzará una excepción → 404.
        //
        // Ejemplo futuro:
        //   $servicio  = new AprendizService();
        //   $aprendiz  = $servicio->obtenerPorId($idAprendiz);
        //   $this->responderExito('Aprendiz encontrado.', $aprendiz);

        $this->responderExito('Endpoint consultar aprendiz disponible.', [
            'id_aprendiz' => $idAprendiz,
        ]);
    }

    /**
     * Acción: registrar un nuevo aprendiz en el sistema.
     *
     * Crea el perfil del aprendiz y lo asocia a una ficha de formación.
     *
     * POST /api/aprendices/registrar
     * Body esperado: {
     *   "documento":   "1234567890",
     *   "nombres":     "Juan",
     *   "apellidos":   "Pérez",
     *   "correo":      "juan@example.com",
     *   "id_ficha":    15
     * }
     */
    private function registrar(): void
    {
        $cuerpo = $this->leerCuerpoJson();

        // Validar campos mínimos obligatorios para registrar el aprendiz
        $camposRequeridos = ['documento', 'nombres', 'apellidos', 'correo', 'id_ficha'];
        foreach ($camposRequeridos as $campo) {
            if (empty($cuerpo[$campo])) {
                $this->responderError("El campo '{$campo}' es obligatorio.", 422);
            }
        }

        $documento  = trim((string) $cuerpo['documento']);
        $nombres    = trim((string) $cuerpo['nombres']);
        $apellidos  = trim((string) $cuerpo['apellidos']);
        $correo     = trim((string) $cuerpo['correo']);
        $idFicha    = (int) $cuerpo['id_ficha'];

        // ► AQUÍ: llamar a AprendizService->registrar($documento, $nombres, $apellidos, $correo, $idFicha)
        // El servicio verificará:
        //   1. Que el documento no esté duplicado en el sistema.
        //   2. Que el correo no esté en uso.
        //   3. Que la ficha exista y esté activa.
        //   4. Creará el registro mediante AprendizRepository.
        //   5. Retornará el objeto aprendiz creado.
        //
        // Ejemplo futuro:
        //   $servicio  = new AprendizService();
        //   $aprendiz  = $servicio->registrar($documento, $nombres, $apellidos, $correo, $idFicha);
        //   $this->responderExito('Aprendiz registrado correctamente.', $aprendiz, 201);

        $this->responderExito('Endpoint registrar aprendiz disponible.', [
            'documento' => $documento,
            'nombres'   => $nombres,
            'apellidos' => $apellidos,
            'correo'    => $correo,
            'id_ficha'  => $idFicha,
        ], 201);
    }

    /**
     * Acción: actualizar los datos de un aprendiz existente.
     *
     * Permite modificar datos personales, correo o ficha de formación.
     * Los campos no enviados en el body se conservan sin cambios.
     *
     * PUT /api/aprendices/actualizar/{idAprendiz}
     * Body esperado (campos opcionales): { "correo": "nuevo@example.com", "id_ficha": 20 }
     *
     * @param int $idAprendiz Identificador único del aprendiz a actualizar.
     */
    private function actualizar(int $idAprendiz): void
    {
        $cuerpo = $this->leerCuerpoJson();

        if (empty($cuerpo)) {
            $this->responderError('No se recibieron datos para actualizar.', 422);
        }

        // ► AQUÍ: llamar a AprendizService->actualizar($idAprendiz, $cuerpo)
        // El servicio validará los campos enviados, verificará que el aprendiz
        // exista y aplicará únicamente los cambios recibidos (actualización parcial).
        //
        // Ejemplo futuro:
        //   $servicio  = new AprendizService();
        //   $aprendiz  = $servicio->actualizar($idAprendiz, $cuerpo);
        //   $this->responderExito('Aprendiz actualizado correctamente.', $aprendiz);

        $this->responderExito('Endpoint actualizar aprendiz disponible.', [
            'id_aprendiz' => $idAprendiz,
            'datos'       => $cuerpo,
        ]);
    }

    /**
     * Acción: eliminar un aprendiz del sistema.
     *
     * Operación reservada para roles administrativos.
     * La verificación de permisos se implementará en el Middleware de autenticación.
     * Antes de eliminar, el Service verificará que no existan asistencias activas.
     *
     * DELETE /api/aprendices/eliminar/{idAprendiz}
     *
     * @param int $idAprendiz Identificador único del aprendiz a eliminar.
     */
    private function eliminar(int $idAprendiz): void
    {
        // ► AQUÍ: llamar a AprendizService->eliminar($idAprendiz)
        // El servicio comprobará que el aprendiz exista y que su eliminación
        // no afecte la integridad referencial (asistencias, tokens, etc.).
        // La autorización por rol corresponde al Middleware (capa superior).
        //
        // Ejemplo futuro:
        //   $servicio = new AprendizService();
        //   $servicio->eliminar($idAprendiz);
        //   $this->responderExito('Aprendiz eliminado correctamente.', []);

        $this->responderExito('Endpoint eliminar aprendiz disponible.', [
            'id_aprendiz' => $idAprendiz,
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
