<?php

declare(strict_types=1);

/**
 * AttendQR – DocenteController
 *
 * Responsabilidad: gestionar los docentes / instructores del sistema.
 * Un "docente" es el instructor del SENA responsable de una o más fichas
 * de formación y de abrir las sesiones de clase.
 *
 * Arquitectura:
 *   api.php → DocenteController → [DocenteService] → [DocenteRepository] → [Modelo]
 *
 * Rutas que maneja este controlador:
 *   GET    /api/docentes/listar             → listar docentes (con filtros opcionales)
 *   GET    /api/docentes/consultar/{id}     → consultar un docente por su ID
 *   POST   /api/docentes/registrar         → registrar un nuevo docente
 *   PUT    /api/docentes/actualizar/{id}   → actualizar los datos de un docente
 *   DELETE /api/docentes/eliminar/{id}     → eliminar un docente del sistema
 *
 * Convención de parámetros posicionales:
 *   $params[0] → ID del docente sobre el que opera la acción (cuando aplica)
 *
 * Ubicación en el proyecto: Src/Controllers/DocenteController.php
 */
class DocenteController
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
     * @param string   $accion  Segundo segmento de la URL (/api/docentes/{accion})
     * @param string[] $params  Parámetros posicionales adicionales (p. ej. IDs)
     */
    public function handle(string $metodo, string $accion, array $params): void
    {
        match ($accion) {

            // Listar todos los docentes — solo GET
            'listar' => $this->despacharConMetodo($metodo, 'GET',
                fn() => $this->listar()
            ),

            // Consultar un docente específico por su ID — solo GET
            'consultar' => $this->despacharConMetodo($metodo, 'GET',
                fn() => $this->consultar(
                    $this->extraerIdRequerido($params, 'docente')
                )
            ),

            // Registrar un nuevo docente en el sistema — solo POST
            'registrar' => $this->despacharConMetodo($metodo, 'POST',
                fn() => $this->registrar()
            ),

            // Actualizar los datos de un docente existente — solo PUT
            'actualizar' => $this->despacharConMetodo($metodo, 'PUT',
                fn() => $this->actualizar(
                    $this->extraerIdRequerido($params, 'docente')
                )
            ),

            // Eliminar un docente del sistema — solo DELETE
            'eliminar' => $this->despacharConMetodo($metodo, 'DELETE',
                fn() => $this->eliminar(
                    $this->extraerIdRequerido($params, 'docente')
                )
            ),

            // Acción no reconocida → 404
            default => $this->responderError(
                "Acción '{$accion}' no encontrada en DocenteController.",
                404
            ),
        };
    }

    // -------------------------------------------------------------------------
    // Acciones del controlador
    // -------------------------------------------------------------------------

    /**
     * Acción: listar docentes registrados en el sistema.
     *
     * Admitirá filtros por estado y especialidad cuando se integre el Service.
     *
     * GET /api/docentes/listar
     * Query params opcionales: ?estado=activo&especialidad=sistemas
     */
    private function listar(): void
    {
        $filtroEstado       = $_GET['estado']       ?? null;
        $filtroEspecialidad = $_GET['especialidad'] ?? null;

        // ► AQUÍ: llamar a DocenteService->listar($filtroEstado, $filtroEspecialidad)
        // El servicio consultará DocenteRepository con los filtros recibidos
        // y retornará el listado paginado de docentes.
        //
        // Ejemplo futuro:
        //   $servicio  = new DocenteService();
        //   $docentes  = $servicio->listar($filtroEstado, $filtroEspecialidad);
        //   $this->responderExito('Docentes obtenidos correctamente.', $docentes);

        $this->responderExito('Endpoint listar docentes disponible.', [
            'filtro_estado'       => $filtroEstado,
            'filtro_especialidad' => $filtroEspecialidad,
        ]);
    }

    /**
     * Acción: consultar un docente específico por su ID.
     *
     * Devuelve los datos completos del docente: datos personales,
     * especialidad, fichas asignadas y sesiones activas.
     *
     * GET /api/docentes/consultar/{idDocente}
     *
     * @param int $idDocente Identificador único del docente.
     */
    private function consultar(int $idDocente): void
    {
        // ► AQUÍ: llamar a DocenteService->obtenerPorId($idDocente)
        // El servicio buscará el docente en la base de datos mediante
        // DocenteRepository. Si no existe, lanzará una excepción → 404.
        //
        // Ejemplo futuro:
        //   $servicio = new DocenteService();
        //   $docente  = $servicio->obtenerPorId($idDocente);
        //   $this->responderExito('Docente encontrado.', $docente);

        $this->responderExito('Endpoint consultar docente disponible.', [
            'id_docente' => $idDocente,
        ]);
    }

    /**
     * Acción: registrar un nuevo docente en el sistema.
     *
     * Crea el perfil del docente con sus datos personales y credenciales
     * de acceso. El Service se encargará de generar la contraseña inicial
     * y de enviar el correo de bienvenida.
     *
     * POST /api/docentes/registrar
     * Body esperado: {
     *   "documento":      "12345678",
     *   "nombres":        "Carlos",
     *   "apellidos":      "Gómez",
     *   "correo":         "carlos@sena.edu.co",
     *   "especialidad":   "Sistemas"
     * }
     */
    private function registrar(): void
    {
        $cuerpo = $this->leerCuerpoJson();

        // Validar campos mínimos obligatorios
        $camposRequeridos = ['documento', 'nombres', 'apellidos', 'correo'];
        foreach ($camposRequeridos as $campo) {
            if (empty($cuerpo[$campo])) {
                $this->responderError("El campo '{$campo}' es obligatorio.", 422);
            }
        }

        $documento    = trim((string) $cuerpo['documento']);
        $nombres      = trim((string) $cuerpo['nombres']);
        $apellidos    = trim((string) $cuerpo['apellidos']);
        $correo       = trim((string) $cuerpo['correo']);
        $especialidad = isset($cuerpo['especialidad']) ? trim((string) $cuerpo['especialidad']) : null;

        // ► AQUÍ: llamar a DocenteService->registrar($documento, $nombres, $apellidos, $correo, $especialidad)
        // El servicio verificará que el documento y el correo no estén duplicados,
        // generará la contraseña inicial, la hasheará con password_hash() y
        // guardará el registro mediante DocenteRepository.
        //
        // Ejemplo futuro:
        //   $servicio = new DocenteService();
        //   $docente  = $servicio->registrar($documento, $nombres, $apellidos, $correo, $especialidad);
        //   $this->responderExito('Docente registrado correctamente.', $docente, 201);

        $this->responderExito('Endpoint registrar docente disponible.', [
            'documento'   => $documento,
            'nombres'     => $nombres,
            'apellidos'   => $apellidos,
            'correo'      => $correo,
            'especialidad' => $especialidad,
        ], 201);
    }

    /**
     * Acción: actualizar los datos de un docente existente.
     *
     * Permite modificar datos personales, correo o especialidad.
     * Los campos no enviados en el body se conservan sin cambios.
     *
     * PUT /api/docentes/actualizar/{idDocente}
     * Body esperado (campos opcionales): { "especialidad": "Electrónica", "correo": "nuevo@sena.edu.co" }
     *
     * @param int $idDocente Identificador único del docente a actualizar.
     */
    private function actualizar(int $idDocente): void
    {
        $cuerpo = $this->leerCuerpoJson();

        if (empty($cuerpo)) {
            $this->responderError('No se recibieron datos para actualizar.', 422);
        }

        // ► AQUÍ: llamar a DocenteService->actualizar($idDocente, $cuerpo)
        // El servicio validará los campos enviados, verificará que el docente
        // exista y aplicará únicamente los cambios recibidos (actualización parcial).
        //
        // Ejemplo futuro:
        //   $servicio = new DocenteService();
        //   $docente  = $servicio->actualizar($idDocente, $cuerpo);
        //   $this->responderExito('Docente actualizado correctamente.', $docente);

        $this->responderExito('Endpoint actualizar docente disponible.', [
            'id_docente' => $idDocente,
            'datos'      => $cuerpo,
        ]);
    }

    /**
     * Acción: eliminar un docente del sistema.
     *
     * Operación reservada para roles administrativos.
     * El Service verificará que el docente no tenga sesiones activas
     * ni fichas vigentes antes de proceder con la eliminación.
     * La verificación de permisos corresponde al Middleware de autenticación.
     *
     * DELETE /api/docentes/eliminar/{idDocente}
     *
     * @param int $idDocente Identificador único del docente a eliminar.
     */
    private function eliminar(int $idDocente): void
    {
        // ► AQUÍ: llamar a DocenteService->eliminar($idDocente)
        // El servicio comprobará integridad referencial (sesiones, fichas asignadas)
        // antes de eliminar. La autorización por rol corresponde al Middleware.
        //
        // Ejemplo futuro:
        //   $servicio = new DocenteService();
        //   $servicio->eliminar($idDocente);
        //   $this->responderExito('Docente eliminado correctamente.', []);

        $this->responderExito('Endpoint eliminar docente disponible.', [
            'id_docente' => $idDocente,
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