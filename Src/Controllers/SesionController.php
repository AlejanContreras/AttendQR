<?php

declare(strict_types=1);

/**
 * AttendQR – SesionController
 *
 * Responsabilidad: gestionar las sesiones de clase (crear, consultar, cerrar).
 * Una "sesión" representa una clase activa en la que los estudiantes
 * pueden registrar su asistencia mediante código QR.
 *
 * Rutas que maneja este controlador:
 *   POST /api/sesiones/crear          → abrir una nueva sesión de clase
 *   GET  /api/sesiones/listar         → listar sesiones (con filtros opcionales)
 *   GET  /api/sesiones/detalle/{id}   → ver detalle de una sesión específica
 *   POST /api/sesiones/cerrar/{id}    → cerrar una sesión activa
 *
 * Ubicación en el proyecto: Src/Controllers/SesionController.php
 */
class SesionController
{
    // -------------------------------------------------------------------------
    // Punto de entrada principal
    // -------------------------------------------------------------------------

    /**
     * Recibe la petición del router y la despacha a la acción correspondiente.
     *
     * @param string   $metodo  Método HTTP (GET, POST, etc.)
     * @param string   $accion  Segundo segmento de la URL (/api/sesiones/{accion})
     * @param string[] $params  Parámetros posicionales adicionales (ej. ID de sesión)
     */
    public function handle(string $metodo, string $accion, array $params): void
    {
        switch ($accion) {

            case 'crear':
                $this->verificarMetodo($metodo, 'POST');
                $this->crear();
                break;

            case 'listar':
                $this->verificarMetodo($metodo, 'GET');
                $this->listar();
                break;

            case 'detalle':
                // El ID de la sesión llega como primer parámetro posicional
                // Ejemplo: /api/sesiones/detalle/15  → $params[0] = "15"
                $this->verificarMetodo($metodo, 'GET');
                $idSesion = $this->extraerIdRequerido($params, 'sesión');
                $this->detalle($idSesion);
                break;

            case 'cerrar':
                // El ID de la sesión llega como primer parámetro posicional
                // Ejemplo: /api/sesiones/cerrar/15  → $params[0] = "15"
                $this->verificarMetodo($metodo, 'POST');
                $idSesion = $this->extraerIdRequerido($params, 'sesión');
                $this->cerrar($idSesion);
                break;

            default:
                $this->responderError("Acción '{$accion}' no encontrada en SesionController.", 404);
        }
    }

    // -------------------------------------------------------------------------
    // Acciones del controlador
    // -------------------------------------------------------------------------

    /**
     * Acción: crear una nueva sesión de clase.
     *
     * Abre una sesión activa a la que se asociará un QR para registrar asistencia.
     *
     * POST /api/sesiones/crear
     * Body esperado: { "id_materia": 1, "id_docente": 3, "fecha": "2025-06-10" }
     */
    private function crear(): void
    {
        $cuerpo = $this->leerCuerpoJson();

        // Validar campos mínimos requeridos para crear la sesión
        if (empty($cuerpo['id_materia']) || empty($cuerpo['id_docente'])) {
            $this->responderError('Los campos id_materia e id_docente son obligatorios.', 422);
        }

        $idMateria = (int) $cuerpo['id_materia'];
        $idDocente = (int) $cuerpo['id_docente'];
        $fecha     = $cuerpo['fecha'] ?? date('Y-m-d'); // Si no se envía, se usa la fecha actual

        // ► AQUÍ: llamar a SesionService->crear($idMateria, $idDocente, $fecha)
        // El servicio creará el registro en la tabla `sesiones`,
        // asignará el estado "activa" y retornará el objeto sesión creado.
        //
        // Ejemplo futuro:
        //   $servicio = new SesionService();
        //   $sesion = $servicio->crear($idMateria, $idDocente, $fecha);
        //   $this->responderJson($sesion, 201);

        $this->responderJson([
            'mensaje'    => 'Acción crear sesión recibida correctamente. Lógica pendiente de implementar.',
            'id_materia' => $idMateria,
            'id_docente' => $idDocente,
            'fecha'      => $fecha,
        ], 201);
    }

    /**
     * Acción: listar sesiones.
     *
     * Devuelve el listado de sesiones. Se podrán aplicar filtros
     * por docente, materia, fecha o estado (activa/cerrada) en el futuro.
     *
     * GET /api/sesiones/listar
     * Query params opcionales: ?id_docente=3&fecha=2025-06-10&estado=activa
     */
    private function listar(): void
    {
        // Leer filtros opcionales desde la query string
        $filtroDocente  = isset($_GET['id_docente']) ? (int) $_GET['id_docente'] : null;
        $filtroFecha    = $_GET['fecha']   ?? null;
        $filtroEstado   = $_GET['estado']  ?? null;

        // ► AQUÍ: llamar a SesionService->listar($filtroDocente, $filtroFecha, $filtroEstado)
        // El servicio consultará el SesionRepository con los filtros recibidos
        // y devolverá un array de sesiones formateadas.
        //
        // Ejemplo futuro:
        //   $servicio = new SesionService();
        //   $sesiones = $servicio->listar($filtroDocente, $filtroFecha, $filtroEstado);
        //   $this->responderJson(['sesiones' => $sesiones], 200);

        $this->responderJson([
            'mensaje'        => 'Acción listar sesiones recibida correctamente. Lógica pendiente de implementar.',
            'filtro_docente' => $filtroDocente,
            'filtro_fecha'   => $filtroFecha,
            'filtro_estado'  => $filtroEstado,
        ], 200);
    }

    /**
     * Acción: obtener el detalle de una sesión específica.
     *
     * Devuelve los datos completos de la sesión, incluyendo
     * el listado de asistencias registradas en ella.
     *
     * GET /api/sesiones/detalle/{idSesion}
     *
     * @param int $idSesion Identificador único de la sesión.
     */
    private function detalle(int $idSesion): void
    {
        // ► AQUÍ: llamar a SesionService->obtenerDetalle($idSesion)
        // El servicio buscará la sesión en base de datos junto con
        // sus asistencias relacionadas y retornará el objeto completo.
        // Si no existe, el servicio lanzará una excepción → 404.
        //
        // Ejemplo futuro:
        //   $servicio = new SesionService();
        //   $sesion = $servicio->obtenerDetalle($idSesion);
        //   $this->responderJson($sesion, 200);

        $this->responderJson([
            'mensaje'   => 'Acción detalle de sesión recibida correctamente. Lógica pendiente de implementar.',
            'id_sesion' => $idSesion,
        ], 200);
    }

    /**
     * Acción: cerrar una sesión activa.
     *
     * Marca la sesión como cerrada e invalida su QR asociado.
     * Después de cerrada, no se pueden registrar nuevas asistencias.
     *
     * POST /api/sesiones/cerrar/{idSesion}
     *
     * @param int $idSesion Identificador único de la sesión a cerrar.
     */
    private function cerrar(int $idSesion): void
    {
        // ► AQUÍ: llamar a SesionService->cerrar($idSesion)
        // El servicio actualizará el estado de la sesión a "cerrada",
        // registrará la hora de cierre y desactivará el QR vinculado.
        // Si la sesión ya está cerrada, el servicio lanzará una excepción.
        //
        // Ejemplo futuro:
        //   $servicio = new SesionService();
        //   $servicio->cerrar($idSesion);
        //   $this->responderJson(['mensaje' => 'Sesión cerrada correctamente.'], 200);

        $this->responderJson([
            'mensaje'   => 'Acción cerrar sesión recibida correctamente. Lógica pendiente de implementar.',
            'id_sesion' => $idSesion,
        ], 200);
    }

    // -------------------------------------------------------------------------
    // Métodos auxiliares internos
    // -------------------------------------------------------------------------

    /**
     * Extrae y valida un ID numérico desde los parámetros posicionales.
     * Si no existe o no es numérico, responde con 400 y detiene la ejecución.
     *
     * @param string[] $params        Parámetros posicionales del router.
     * @param string   $nombreEntidad Nombre de la entidad (para el mensaje de error).
     * @return int ID validado y convertido a entero.
     */
    private function extraerIdRequerido(array $params, string $nombreEntidad): int
    {
        if (empty($params[0]) || !ctype_digit((string) $params[0])) {
            $this->responderError("Se requiere un ID numérico válido para la {$nombreEntidad}.", 400);
        }

        return (int) $params[0];
    }

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