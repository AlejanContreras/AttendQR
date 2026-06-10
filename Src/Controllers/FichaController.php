<?php

declare(strict_types=1);

/**
 * AttendQR – FichaController
 *
 * Responsabilidad: gestionar las fichas de formación del SENA.
 * Una "ficha" es el grupo de aprendices asignado a un programa de formación.
 *
 * Arquitectura:
 *   api.php → FichaController → [FichaService] → [FichaRepository] → [Modelo]
 *
 * Rutas que maneja este controlador:
 *   GET    /api/fichas/listar             → listar todas las fichas (con filtros opcionales)
 *   GET    /api/fichas/consultar/{id}     → consultar una ficha por su ID
 *   POST   /api/fichas/crear             → registrar una nueva ficha
 *   PUT    /api/fichas/actualizar/{id}   → actualizar los datos de una ficha
 *   DELETE /api/fichas/eliminar/{id}     → eliminar una ficha del sistema
 *
 * Convención de parámetros posicionales:
 *   $params[0] → ID de la ficha sobre la que opera la acción (cuando aplica)
 *
 * Ubicación en el proyecto: Src/Controllers/FichaController.php
 */
class FichaController
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
     * @param string   $accion  Segundo segmento de la URL (/api/fichas/{accion})
     * @param string[] $params  Parámetros posicionales adicionales (p. ej. IDs)
     */
    public function handle(string $metodo, string $accion, array $params): void
    {
        match ($accion) {

            // Listar todas las fichas disponibles — solo GET
            'listar' => $this->despacharConMetodo($metodo, 'GET',
                fn() => $this->listar()
            ),

            // Consultar una ficha específica por su ID — solo GET
            'consultar' => $this->despacharConMetodo($metodo, 'GET',
                fn() => $this->consultar(
                    $this->extraerIdRequerido($params, 'ficha')
                )
            ),

            // Registrar una nueva ficha — solo POST
            'crear' => $this->despacharConMetodo($metodo, 'POST',
                fn() => $this->crear()
            ),

            // Actualizar los datos de una ficha existente — solo PUT
            'actualizar' => $this->despacharConMetodo($metodo, 'PUT',
                fn() => $this->actualizar(
                    $this->extraerIdRequerido($params, 'ficha')
                )
            ),

            // Eliminar una ficha del sistema — solo DELETE
            'eliminar' => $this->despacharConMetodo($metodo, 'DELETE',
                fn() => $this->eliminar(
                    $this->extraerIdRequerido($params, 'ficha')
                )
            ),

            // Acción no reconocida → 404
            default => $this->responderError(
                "Acción '{$accion}' no encontrada en FichaController.",
                404
            ),
        };
    }

    // -------------------------------------------------------------------------
    // Acciones del controlador
    // -------------------------------------------------------------------------

    /**
     * Acción: listar fichas de formación.
     *
     * Devuelve el listado de fichas registradas en el sistema.
     * Admitirá filtros por programa, estado y jornada cuando se integre el Service.
     *
     * GET /api/fichas/listar
     * Query params opcionales: ?id_programa=3&estado=activa&id_jornada=2
     */
    private function listar(): void
    {
        // Capturar filtros opcionales desde la query string
        $filtroPrograma = isset($_GET['id_programa']) ? (int) $_GET['id_programa'] : null;
        $filtroEstado   = $_GET['estado']     ?? null;
        $filtroJornada  = isset($_GET['id_jornada']) ? (int) $_GET['id_jornada'] : null;

        // ► AQUÍ: llamar a FichaService->listar($filtroPrograma, $filtroEstado, $filtroJornada)
        // El servicio consultará el FichaRepository aplicando los filtros recibidos
        // y retornará el listado paginado de fichas.
        //
        // Ejemplo futuro:
        //   $servicio = new FichaService();
        //   $fichas   = $servicio->listar($filtroPrograma, $filtroEstado, $filtroJornada);
        //   $this->responderExito('Fichas obtenidas correctamente.', $fichas);

        $this->responderExito('Endpoint listar fichas disponible.', [
            'filtro_programa' => $filtroPrograma,
            'filtro_estado'   => $filtroEstado,
            'filtro_jornada'  => $filtroJornada,
        ]);
    }

    /**
     * Acción: consultar una ficha de formación por su ID.
     *
     * Devuelve los datos completos de la ficha: programa, número de ficha,
     * jornada, aprendices inscritos y docente asignado.
     *
     * GET /api/fichas/consultar/{idFicha}
     *
     * @param int $idFicha Identificador único de la ficha.
     */
    private function consultar(int $idFicha): void
    {
        // ► AQUÍ: llamar a FichaService->obtenerPorId($idFicha)
        // El servicio buscará la ficha en la base de datos mediante FichaRepository.
        // Si no existe, lanzará una excepción que se traduce en respuesta 404.
        //
        // Ejemplo futuro:
        //   $servicio = new FichaService();
        //   $ficha    = $servicio->obtenerPorId($idFicha);
        //   $this->responderExito('Ficha encontrada.', $ficha);

        $this->responderExito('Endpoint consultar ficha disponible.', [
            'id_ficha' => $idFicha,
        ]);
    }

    /**
     * Acción: crear una nueva ficha de formación.
     *
     * Registra una ficha en el sistema asociándola a un programa de formación,
     * una jornada y un docente responsable.
     *
     * POST /api/fichas/crear
     * Body esperado: { "numero_ficha": "2345678", "id_programa": 3, "id_jornada": 1 }
     */
    private function crear(): void
    {
        $cuerpo = $this->leerCuerpoJson();

        // Validar campos mínimos obligatorios para crear la ficha
        if (empty($cuerpo['numero_ficha']) || empty($cuerpo['id_programa'])) {
            $this->responderError('Los campos numero_ficha e id_programa son obligatorios.', 422);
        }

        $numeroFicha = trim((string) $cuerpo['numero_ficha']);
        $idPrograma  = (int) $cuerpo['id_programa'];
        $idJornada   = isset($cuerpo['id_jornada']) ? (int) $cuerpo['id_jornada'] : null;

        // ► AQUÍ: llamar a FichaService->crear($numeroFicha, $idPrograma, $idJornada)
        // El servicio verificará que el número de ficha no esté duplicado,
        // que el programa y la jornada existan, y guardará el registro
        // mediante FichaRepository.
        //
        // Ejemplo futuro:
        //   $servicio = new FichaService();
        //   $ficha    = $servicio->crear($numeroFicha, $idPrograma, $idJornada);
        //   $this->responderExito('Ficha creada correctamente.', $ficha, 201);

        $this->responderExito('Endpoint crear ficha disponible.', [
            'numero_ficha' => $numeroFicha,
            'id_programa'  => $idPrograma,
            'id_jornada'   => $idJornada,
        ], 201);
    }

    /**
     * Acción: actualizar los datos de una ficha existente.
     *
     * Permite modificar el número de ficha, el programa, la jornada
     * o el estado de la ficha (activa / inactiva).
     *
     * PUT /api/fichas/actualizar/{idFicha}
     * Body esperado: { "numero_ficha": "2345678", "id_jornada": 2, "estado": "activa" }
     *
     * @param int $idFicha Identificador único de la ficha a actualizar.
     */
    private function actualizar(int $idFicha): void
    {
        $cuerpo = $this->leerCuerpoJson();

        if (empty($cuerpo)) {
            $this->responderError('No se recibieron datos para actualizar.', 422);
        }

        // ► AQUÍ: llamar a FichaService->actualizar($idFicha, $cuerpo)
        // El servicio validará los campos enviados, verificará que la ficha
        // exista y aplicará los cambios mediante FichaRepository.
        //
        // Ejemplo futuro:
        //   $servicio = new FichaService();
        //   $ficha    = $servicio->actualizar($idFicha, $cuerpo);
        //   $this->responderExito('Ficha actualizada correctamente.', $ficha);

        $this->responderExito('Endpoint actualizar ficha disponible.', [
            'id_ficha' => $idFicha,
            'datos'    => $cuerpo,
        ]);
    }

    /**
     * Acción: eliminar una ficha del sistema.
     *
     * Operación reservada para roles administrativos.
     * La verificación de permisos se implementará en el Middleware de autenticación.
     *
     * DELETE /api/fichas/eliminar/{idFicha}
     *
     * @param int $idFicha Identificador único de la ficha a eliminar.
     */
    private function eliminar(int $idFicha): void
    {
        // ► AQUÍ: llamar a FichaService->eliminar($idFicha)
        // El servicio verificará que la ficha exista y que no tenga
        // aprendices o sesiones activas vinculadas antes de eliminarla.
        // La autorización por rol corresponde al Middleware (capa superior).
        //
        // Ejemplo futuro:
        //   $servicio = new FichaService();
        //   $servicio->eliminar($idFicha);
        //   $this->responderExito('Ficha eliminada correctamente.', []);

        $this->responderExito('Endpoint eliminar ficha disponible.', [
            'id_ficha' => $idFicha,
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
