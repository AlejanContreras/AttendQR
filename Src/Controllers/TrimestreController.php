<?php

declare(strict_types=1);

/**
 * AttendQR – TrimestreController
 *
 * Responsabilidad: gestionar los trimestres académicos del sistema.
 * Un "trimestre" define el período lectivo dentro del cual se agrupan
 * las sesiones de clase y se calculan los porcentajes de asistencia.
 *
 * Arquitectura:
 *   api.php → TrimestreController → [TrimestreService] → [TrimestreRepository] → [Modelo]
 *
 * Rutas que maneja este controlador:
 *   GET    /api/trimestres/listar             → listar todos los trimestres
 *   GET    /api/trimestres/consultar/{id}     → consultar un trimestre por su ID
 *   POST   /api/trimestres/crear             → registrar un nuevo trimestre
 *   PUT    /api/trimestres/actualizar/{id}   → actualizar los datos de un trimestre
 *   DELETE /api/trimestres/eliminar/{id}     → eliminar un trimestre del sistema
 *
 * Convención de parámetros posicionales:
 *   $params[0] → ID del trimestre sobre el que opera la acción (cuando aplica)
 *
 * Ubicación en el proyecto: Src/Controllers/TrimestreController.php
 */
class TrimestreController
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
     * @param string   $accion  Segundo segmento de la URL (/api/trimestres/{accion})
     * @param string[] $params  Parámetros posicionales adicionales (p. ej. IDs)
     */
    public function handle(string $metodo, string $accion, array $params): void
    {
        match ($accion) {

            // Listar todos los trimestres registrados — solo GET
            'listar' => $this->despacharConMetodo($metodo, 'GET',
                fn() => $this->listar()
            ),

            // Consultar un trimestre específico por su ID — solo GET
            'consultar' => $this->despacharConMetodo($metodo, 'GET',
                fn() => $this->consultar(
                    $this->extraerIdRequerido($params, 'trimestre')
                )
            ),

            // Registrar un nuevo trimestre en el sistema — solo POST
            'crear' => $this->despacharConMetodo($metodo, 'POST',
                fn() => $this->crear()
            ),

            // Actualizar los datos de un trimestre existente — solo PUT
            'actualizar' => $this->despacharConMetodo($metodo, 'PUT',
                fn() => $this->actualizar(
                    $this->extraerIdRequerido($params, 'trimestre')
                )
            ),

            // Eliminar un trimestre del sistema — solo DELETE
            'eliminar' => $this->despacharConMetodo($metodo, 'DELETE',
                fn() => $this->eliminar(
                    $this->extraerIdRequerido($params, 'trimestre')
                )
            ),

            // Acción no reconocida → 404
            default => $this->responderError(
                "Acción '{$accion}' no encontrada en TrimestreController.",
                404
            ),
        };
    }

    // -------------------------------------------------------------------------
    // Acciones del controlador
    // -------------------------------------------------------------------------

    /**
     * Acción: listar todos los trimestres académicos.
     *
     * Devuelve el catálogo de trimestres con su estado (activo / cerrado).
     * Admitirá filtro por año y estado cuando se integre el Service.
     *
     * GET /api/trimestres/listar
     * Query params opcionales: ?anio=2025&estado=activo
     */
    private function listar(): void
    {
        $filtroAnio   = isset($_GET['anio']) ? (int) $_GET['anio'] : null;
        $filtroEstado = $_GET['estado'] ?? null;

        // ► AQUÍ: llamar a TrimestreService->listar($filtroAnio, $filtroEstado)
        // El servicio consultará TrimestreRepository con los filtros recibidos
        // y retornará el listado de trimestres ordenados por fecha de inicio.
        //
        // Ejemplo futuro:
        //   $servicio    = new TrimestreService();
        //   $trimestres  = $servicio->listar($filtroAnio, $filtroEstado);
        //   $this->responderExito('Trimestres obtenidos correctamente.', $trimestres);

        $this->responderExito('Endpoint listar trimestres disponible.', [
            'filtro_anio'   => $filtroAnio,
            'filtro_estado' => $filtroEstado,
        ]);
    }

    /**
     * Acción: consultar un trimestre específico por su ID.
     *
     * Devuelve los datos completos del trimestre: nombre, fecha de inicio,
     * fecha de fin, estado y número de sesiones registradas en él.
     *
     * GET /api/trimestres/consultar/{idTrimestre}
     *
     * @param int $idTrimestre Identificador único del trimestre.
     */
    private function consultar(int $idTrimestre): void
    {
        // ► AQUÍ: llamar a TrimestreService->obtenerPorId($idTrimestre)
        // El servicio buscará el trimestre en la base de datos mediante
        // TrimestreRepository. Si no existe, lanzará una excepción → 404.
        //
        // Ejemplo futuro:
        //   $servicio   = new TrimestreService();
        //   $trimestre  = $servicio->obtenerPorId($idTrimestre);
        //   $this->responderExito('Trimestre encontrado.', $trimestre);

        $this->responderExito('Endpoint consultar trimestre disponible.', [
            'id_trimestre' => $idTrimestre,
        ]);
    }

    /**
     * Acción: registrar un nuevo trimestre académico.
     *
     * Define el período lectivo con su nombre, fechas de inicio y fin.
     * Solo puede existir un trimestre activo a la vez (el Service lo validará).
     *
     * POST /api/trimestres/crear
     * Body esperado: {
     *   "nombre":       "Trimestre I – 2025",
     *   "fecha_inicio": "2025-03-01",
     *   "fecha_fin":    "2025-06-30"
     * }
     */
    private function crear(): void
    {
        $cuerpo = $this->leerCuerpoJson();

        // Validar campos mínimos obligatorios
        $camposRequeridos = ['nombre', 'fecha_inicio', 'fecha_fin'];
        foreach ($camposRequeridos as $campo) {
            if (empty($cuerpo[$campo])) {
                $this->responderError("El campo '{$campo}' es obligatorio.", 422);
            }
        }

        $nombre      = trim((string) $cuerpo['nombre']);
        $fechaInicio = trim((string) $cuerpo['fecha_inicio']);
        $fechaFin    = trim((string) $cuerpo['fecha_fin']);

        // ► AQUÍ: llamar a TrimestreService->crear($nombre, $fechaInicio, $fechaFin)
        // El servicio validará el formato de las fechas, verificará que no se
        // solapen con trimestres existentes y guardará el registro mediante
        // TrimestreRepository.
        //
        // Ejemplo futuro:
        //   $servicio   = new TrimestreService();
        //   $trimestre  = $servicio->crear($nombre, $fechaInicio, $fechaFin);
        //   $this->responderExito('Trimestre creado correctamente.', $trimestre, 201);

        $this->responderExito('Endpoint crear trimestre disponible.', [
            'nombre'       => $nombre,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin'    => $fechaFin,
        ], 201);
    }

    /**
     * Acción: actualizar los datos de un trimestre existente.
     *
     * Permite modificar el nombre, las fechas o el estado del trimestre.
     * Los campos no enviados en el body se conservan sin cambios.
     * El Service validará que el trimestre no tenga sesiones cerradas
     * que puedan verse afectadas por un cambio de fechas.
     *
     * PUT /api/trimestres/actualizar/{idTrimestre}
     * Body esperado (campos opcionales): { "nombre": "Trimestre I Actualizado", "estado": "cerrado" }
     *
     * @param int $idTrimestre Identificador único del trimestre a actualizar.
     */
    private function actualizar(int $idTrimestre): void
    {
        $cuerpo = $this->leerCuerpoJson();

        if (empty($cuerpo)) {
            $this->responderError('No se recibieron datos para actualizar.', 422);
        }

        // ► AQUÍ: llamar a TrimestreService->actualizar($idTrimestre, $cuerpo)
        // El servicio validará los campos enviados y aplicará únicamente
        // los cambios recibidos (actualización parcial) mediante TrimestreRepository.
        //
        // Ejemplo futuro:
        //   $servicio   = new TrimestreService();
        //   $trimestre  = $servicio->actualizar($idTrimestre, $cuerpo);
        //   $this->responderExito('Trimestre actualizado correctamente.', $trimestre);

        $this->responderExito('Endpoint actualizar trimestre disponible.', [
            'id_trimestre' => $idTrimestre,
            'datos'        => $cuerpo,
        ]);
    }

    /**
     * Acción: eliminar un trimestre del sistema.
     *
     * Operación reservada para roles administrativos.
     * El Service verificará que el trimestre no tenga sesiones o asistencias
     * asociadas antes de proceder con la eliminación.
     * La verificación de permisos corresponde al Middleware de autenticación.
     *
     * DELETE /api/trimestres/eliminar/{idTrimestre}
     *
     * @param int $idTrimestre Identificador único del trimestre a eliminar.
     */
    private function eliminar(int $idTrimestre): void
    {
        // ► AQUÍ: llamar a TrimestreService->eliminar($idTrimestre)
        // El servicio comprobará integridad referencial (sesiones, asistencias)
        // antes de eliminar. La autorización por rol corresponde al Middleware.
        //
        // Ejemplo futuro:
        //   $servicio = new TrimestreService();
        //   $servicio->eliminar($idTrimestre);
        //   $this->responderExito('Trimestre eliminado correctamente.', []);

        $this->responderExito('Endpoint eliminar trimestre disponible.', [
            'id_trimestre' => $idTrimestre,
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