<?php

declare(strict_types=1);

/**
 * AttendQR – AprendizController
 *
 * Responsabilidad: gestionar los aprendices del sistema.
 * Delega toda la lógica a AprendizService.
 *
 * Rutas:
 *   GET    /api/aprendices/listar             → listar aprendices (con filtros)
 *   GET    /api/aprendices/consultar/{id}     → consultar por ID
 *   GET    /api/aprendices/ficha/{id}         → listar por ficha
 *   POST   /api/aprendices/registrar          → registrar nuevo aprendiz (con contraseña)
 *   POST   /api/aprendices/importar           → importar lote (CSV o JSON) — solo docente
 *   PUT    /api/aprendices/actualizar/{id}    → actualizar datos
 *   DELETE /api/aprendices/eliminar/{id}      → eliminar aprendiz
 *
 * Ubicación en el proyecto: Src/Controllers/AprendizController.php
 */
class AprendizController
{
    private AprendizService $servicio;

    public function __construct()
    {
        $this->servicio = new AprendizService();
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
                fn() => $this->consultar($this->extraerIdRequerido($params, 'aprendiz'))
            ),
            'ficha' => $this->despacharConMetodo($metodo, 'GET',
                fn() => $this->listarPorFicha($this->extraerIdRequerido($params, 'ficha'))
            ),
            'registrar' => $this->despacharConMetodo($metodo, 'POST',
                fn() => $this->registrar()
            ),
            'importar' => $this->despacharConMetodo($metodo, 'POST',
                fn() => $this->importar()
            ),
            'actualizar' => $this->despacharConMetodo($metodo, 'PUT',
                fn() => $this->actualizar($this->extraerIdRequerido($params, 'aprendiz'))
            ),
            'eliminar' => $this->despacharConMetodo($metodo, 'DELETE',
                fn() => $this->eliminar($this->extraerIdRequerido($params, 'aprendiz'))
            ),
            default => $this->responderError(
                "Acción '{$accion}' no encontrada en AprendizController.", 404
            ),
        };
    }

    // -------------------------------------------------------------------------
    // Acciones
    // -------------------------------------------------------------------------

    /**
     * GET /api/aprendices/listar
     * Query params opcionales: ?id_ficha=15&estado=activo&documento=1234567&cuenta=pendiente
     */
    private function listar(): void
    {
        $idFicha      = isset($_GET['id_ficha']) ? (int) $_GET['id_ficha'] : null;
        $estado       = $_GET['estado']   ?? null;
        $documento    = $_GET['documento'] ?? null;
        $cuentaEstado = $_GET['cuenta']    ?? null;

        try {
            $resultado = $this->servicio->listar($idFicha, $estado, $documento, $cuentaEstado);
            $this->responderExito('Aprendices obtenidos correctamente.', $resultado);

        } catch (\RuntimeException $e) {
            $this->responderError($e->getMessage(), $e->getCode() ?: 400);
        } catch (\Throwable $e) {
            $this->responderError('Error interno al listar aprendices.', 500);
        }
    }

    /**
     * GET /api/aprendices/consultar/{idAprendiz}
     */
    private function consultar(int $idAprendiz): void
    {
        $this->verificarAccesoAprendiz($idAprendiz);

        try {
            $aprendiz = $this->servicio->consultar($idAprendiz);
            $this->responderExito('Aprendiz encontrado.', $aprendiz);

        } catch (\RuntimeException $e) {
            $this->responderError($e->getMessage(), $e->getCode() ?: 404);
        } catch (\Throwable $e) {
            $this->responderError('Error interno al consultar el aprendiz.', 500);
        }
    }

    /**
     * GET /api/aprendices/ficha/{idFicha}
     * Lista los aprendices de una ficha específica.
     */
    private function listarPorFicha(int $idFicha): void
    {
        try {
            $resultado = $this->servicio->listar($idFicha);
            $this->responderExito('Aprendices de la ficha obtenidos correctamente.', $resultado);

        } catch (\RuntimeException $e) {
            $this->responderError($e->getMessage(), $e->getCode() ?: 400);
        } catch (\Throwable $e) {
            $this->responderError('Error interno al listar aprendices de la ficha.', 500);
        }
    }

    /**
     * POST /api/aprendices/registrar
     * Body: { "numero_documento": "...", "nombres": "...", "apellidos": "...", "password": "...", "id_ficha": 15 }
     */
    private function registrar(): void
    {
        $cuerpo = $this->leerCuerpoJson();

        foreach (['numero_documento', 'nombres', 'apellidos', 'password', 'id_ficha'] as $campo) {
            if (empty($cuerpo[$campo])) {
                $this->responderError("El campo '{$campo}' es obligatorio.", 422);
            }
        }

        try {
            $aprendiz = $this->servicio->registrar(
                (string) $cuerpo['numero_documento'],
                (string) $cuerpo['nombres'],
                (string) $cuerpo['apellidos'],
                (string) $cuerpo['password'],
                (int)    $cuerpo['id_ficha']
            );
            $this->responderExito('Aprendiz registrado correctamente.', $aprendiz, 201);

        } catch (\RuntimeException $e) {
            $this->responderError($e->getMessage(), $e->getCode() ?: 400);
        } catch (\Throwable $e) {
            $this->responderError('Error interno al registrar el aprendiz.', 500);
        }
    }

    /**
     * PUT /api/aprendices/actualizar/{idAprendiz}
     * Body: campos a actualizar (parcial)
     */
    private function actualizar(int $idAprendiz): void
    {
        $this->verificarAccesoAprendiz($idAprendiz);

        $cuerpo = $this->leerCuerpoJson();

        if (empty($cuerpo)) {
            $this->responderError('No se recibieron datos para actualizar.', 422);
        }

        try {
            $aprendiz = $this->servicio->actualizar($idAprendiz, $cuerpo);

            // Sincronizar sesión PHP si el aprendiz actualizó su propio perfil
            if (session_status() !== PHP_SESSION_ACTIVE) session_start();
            $usuarioSesion = $_SESSION['usuario'] ?? null;
            if ($usuarioSesion && (int) $usuarioSesion['id'] === $idAprendiz) {
                $nombreCompleto = trim(($aprendiz['nombres'] ?? '') . ' ' . ($aprendiz['apellidos'] ?? ''));
                if ($nombreCompleto !== '') {
                    $_SESSION['usuario']['nombre'] = $nombreCompleto;
                }
            }

            $this->responderExito('Aprendiz actualizado correctamente.', $aprendiz);

        } catch (\RuntimeException $e) {
            $this->responderError($e->getMessage(), $e->getCode() ?: 400);
        } catch (\Throwable $e) {
            $this->responderError('Error interno al actualizar el aprendiz.', 500);
        }
    }

    /**
     * DELETE /api/aprendices/eliminar/{idAprendiz}
     */
    private function eliminar(int $idAprendiz): void
    {
        try {
            $resultado = $this->servicio->eliminar($idAprendiz);
            $this->responderExito($resultado['message'] ?? 'Aprendiz eliminado correctamente.', []);

        } catch (\RuntimeException $e) {
            $this->responderError($e->getMessage(), $e->getCode() ?: 404);
        } catch (\Throwable $e) {
            $this->responderError('Error interno al eliminar el aprendiz.', 500);
        }
    }

    /**
     * POST /api/aprendices/importar  — solo docente
     *
     * Acepta dos formatos:
     *   A) multipart/form-data con campo 'archivo' (CSV)
     *   B) application/json con array 'aprendices' (compatible con GAS futuro)
     *
     * CSV esperado (con cabecera):
     *   numero_documento,nombres,apellidos,codigo_ficha
     */
    private function importar(): void
    {
        // Solo docentes pueden importar
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        $usuario = $_SESSION['usuario'] ?? null;
        if (!$usuario || ($usuario['rol'] ?? '') !== 'docente') {
            $this->responderError('Solo los docentes pueden importar aprendices.', 403);
        }

        $filas = [];
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

        if (str_contains($contentType, 'multipart/form-data')) {
            // ── Modo A: CSV upload ───────────────────────────────────────────
            $archivo = $_FILES['archivo'] ?? null;

            if (!$archivo || $archivo['error'] !== UPLOAD_ERR_OK) {
                $this->responderError('No se recibió ningún archivo válido.', 422);
            }

            $ext = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['csv', 'txt'], true)) {
                $this->responderError('Solo se aceptan archivos CSV (.csv).', 422);
            }

            $handle = fopen($archivo['tmp_name'], 'r');
            if ($handle === false) {
                $this->responderError('No se pudo leer el archivo.', 500);
            }

            // Primera fila = cabecera
            $cabecera = fgetcsv($handle);
            if ($cabecera === false) {
                fclose($handle);
                $this->responderError('El archivo CSV está vacío.', 422);
            }

            // Normalizar nombres de columna
            $cabecera = array_map(fn($c) => strtolower(trim($c)), $cabecera);
            $requeridas = ['numero_documento', 'nombres', 'apellidos', 'codigo_ficha'];

            foreach ($requeridas as $col) {
                if (!in_array($col, $cabecera, true)) {
                    fclose($handle);
                    $this->responderError("La columna '{$col}' es obligatoria en el CSV.", 422);
                }
            }

            while (($fila = fgetcsv($handle)) !== false) {
                if (count($fila) === count($cabecera)) {
                    $filas[] = array_combine($cabecera, $fila);
                }
            }
            fclose($handle);

        } else {
            // ── Modo B: JSON (GAS compatible) ────────────────────────────────
            $cuerpo = $this->leerCuerpoJson();
            $filas  = $cuerpo['aprendices'] ?? [];

            if (!is_array($filas) || empty($filas)) {
                $this->responderError("Se requiere un array 'aprendices' con al menos una fila.", 422);
            }
        }

        if (empty($filas)) {
            $this->responderError('El archivo no contiene registros de aprendices.', 422);
        }

        try {
            $resultado = $this->servicio->importar($filas);
            $this->responderExito(
                "Importación completada: {$resultado['exitosos']} registrados, " . count($resultado['errores']) . " con errores.",
                $resultado
            );

        } catch (\RuntimeException $e) {
            $this->responderError($e->getMessage(), $e->getCode() ?: 400);
        } catch (\Throwable $e) {
            $this->responderError('Error interno durante la importación.', 500);
        }
    }

    // -------------------------------------------------------------------------
    // Auxiliares
    // -------------------------------------------------------------------------

    /**
     * Si el usuario autenticado es aprendiz, verifica que solo acceda a su propio registro.
     * Docentes pasan sin restricción.
     */
    private function verificarAccesoAprendiz(int $idAprendiz): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $usuario = $_SESSION['usuario'] ?? null;
        if ($usuario && ($usuario['rol'] ?? '') === 'aprendiz' && (int) $usuario['id'] !== $idAprendiz) {
            $this->responderError('Acceso denegado.', 403);
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