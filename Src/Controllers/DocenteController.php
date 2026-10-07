<?php

declare(strict_types=1);

/**
 * AttendQR – DocenteController
 *
 * Responsabilidad: gestionar los docentes del sistema.
 * Delega toda la lógica a DocenteService.
 *
 * Rutas:
 *   GET    /api/docentes/listar             → listar docentes
 *   GET    /api/docentes/consultar/{id}     → consultar por ID
 *   POST   /api/docentes/registrar         → registrar nuevo docente
 *   PUT    /api/docentes/actualizar/{id}   → actualizar datos
 *   DELETE /api/docentes/eliminar/{id}     → eliminar docente
 *
 * Ubicación en el proyecto: Src/Controllers/DocenteController.php
 */
class DocenteController
{
    private DocenteService $servicio;

    public function __construct()
    {
        $this->servicio = new DocenteService();
    }

    /**
     * Punto de entrada del router.
     *
     * @param string   $metodo Método HTTP
     * @param string   $accion Segundo segmento de la URL
     * @param string[] $params Parámetros posicionales
     */
    public function handle(string $metodo, string $accion, array $params): void
    {
        match ($accion) {
            'listar' => $this->despacharConMetodo($metodo, 'GET',
                fn() => $this->listar()
            ),
            'consultar' => $this->despacharConMetodo($metodo, 'GET',
                fn() => $this->consultar($this->extraerIdRequerido($params, 'docente'))
            ),
            // [Aislamiento] las cuentas de docente se crean desde la base de datos;
            // un docente no puede crear otros docentes desde la consola (F12).
            'registrar' => $this->responderError('La creación de docentes no está habilitada por la API.', 403),
            'actualizar' => $this->despacharConMetodo($metodo, 'PUT',
                fn() => $this->actualizar($this->extraerIdRequerido($params, 'docente'))
            ),
            // [Aislamiento] ninguna cuenta de docente se elimina por la API
            'eliminar' => $this->responderError('La eliminación de docentes no está habilitada por la API.', 403),
            default => $this->responderError(
                "Acción '{$accion}' no encontrada en DocenteController.", 404
            ),
        };
    }

    // -------------------------------------------------------------------------
    // Acciones
    // -------------------------------------------------------------------------

    /**
     * GET /api/docentes/listar
     * Query params opcionales: ?estado=activo
     */
    private function listar(): void
    {
        $estado = $_GET['estado'] ?? null;

        try {
            // [Aislamiento entre docentes] un docente no necesita ver nombres y correos
            // de los demás docentes: se devuelve solo su propio registro.
            $usuario   = AccesoService::usuario();
            $resultado = $this->servicio->listar($estado);
            if (isset($resultado['docentes']) && is_array($resultado['docentes'])) {
                $resultado['docentes'] = array_values(array_filter(
                    $resultado['docentes'],
                    fn($d) => (int) ($d['id_docente'] ?? 0) === (int) $usuario['id']
                ));
                $resultado['total'] = count($resultado['docentes']);
            }
            $this->responderExito('Docentes obtenidos correctamente.', $resultado);

        } catch (\RuntimeException $e) {
            $this->responderError($e->getMessage(), $e->getCode() ?: 400);
        } catch (\Throwable $e) {
            $this->responderError('Error interno al listar docentes.', 500);
        }
    }

    /**
     * GET /api/docentes/consultar/{idDocente}
     */
    private function consultar(int $idDocente): void
    {
        try {
            $this->exigirSiMismo($idDocente); // [Aislamiento]
            $docente = $this->servicio->consultar($idDocente);
            $this->responderExito('Docente encontrado.', $docente);

        } catch (\RuntimeException $e) {
            $this->responderError($e->getMessage(), $e->getCode() ?: 404);
        } catch (\Throwable $e) {
            $this->responderError('Error interno al consultar el docente.', 500);
        }
    }

    /**
     * POST /api/docentes/registrar
     * Body: { "nombres": "...", "apellidos": "...", "correo": "...", "contrasena": "..." }
     */
    private function registrar(): void
    {
        $cuerpo = $this->leerCuerpoJson();

        foreach (['nombres', 'apellidos', 'correo', 'contrasena'] as $campo) {
            if (empty($cuerpo[$campo])) {
                $this->responderError("El campo '{$campo}' es obligatorio.", 422);
            }
        }

        try {
            $docente = $this->servicio->registrar(
                (string) $cuerpo['nombres'],
                (string) $cuerpo['apellidos'],
                (string) $cuerpo['correo'],
                (string) $cuerpo['contrasena']
            );
            $this->responderExito('Docente registrado correctamente.', $docente, 201);

        } catch (\RuntimeException $e) {
            $this->responderError($e->getMessage(), $e->getCode() ?: 400);
        } catch (\Throwable $e) {
            $this->responderError('Error interno al registrar el docente.', 500);
        }
    }

    /**
     * PUT /api/docentes/actualizar/{idDocente}
     * Body: campos a actualizar (parcial)
     */
    private function actualizar(int $idDocente): void
    {
        $cuerpo = $this->leerCuerpoJson();

        if (empty($cuerpo)) {
            $this->responderError('No se recibieron datos para actualizar.', 422);
        }

        try {
            // [Aislamiento] antes un docente podía cambiar correo/contraseña de otro docente
            $this->exigirSiMismo($idDocente);
            $docente = $this->servicio->actualizar($idDocente, $cuerpo);

            // Sincronizar nombre en la sesión PHP para que el shell lo refleje en la siguiente carga
            $nombreCompleto = trim(($docente['nombres'] ?? '') . ' ' . ($docente['apellidos'] ?? ''));
            if ($nombreCompleto !== '') {
                $_SESSION['usuario']['nombre'] = $nombreCompleto;
            }

            $this->responderExito('Docente actualizado correctamente.', $docente);

        } catch (\RuntimeException $e) {
            $this->responderError($e->getMessage(), $e->getCode() ?: 400);
        } catch (\Throwable $e) {
            $this->responderError('Error interno al actualizar el docente.', 500);
        }
    }

    /**
     * DELETE /api/docentes/eliminar/{idDocente}
     */
    private function eliminar(int $idDocente): void
    {
        try {
            // [Aislamiento] antes un docente podía eliminar a otro docente
            $this->exigirSiMismo($idDocente);
            $resultado = $this->servicio->eliminar($idDocente);
            $this->responderExito($resultado['message'] ?? 'Docente eliminado correctamente.', []);

        } catch (\RuntimeException $e) {
            $this->responderError($e->getMessage(), $e->getCode() ?: 404);
        } catch (\Throwable $e) {
            $this->responderError('Error interno al eliminar el docente.', 500);
        }
    }

    // -------------------------------------------------------------------------
    // Auxiliares
    // -------------------------------------------------------------------------

    /**
     * [Aislamiento entre docentes] El docente solo puede consultar, editar o
     * eliminar su propia cuenta.
     *
     * @throws \RuntimeException 403 si el ID no es el del docente de la sesión.
     */
    private function exigirSiMismo(int $idDocente): void
    {
        $usuario = AccesoService::usuario();
        AccesoService::exigirDocente($usuario);
        if ((int) $usuario['id'] !== $idDocente) {
            throw new \RuntimeException('Solo puedes gestionar tu propia cuenta.', 403);
        }
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