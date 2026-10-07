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
 *   POST   /api/aprendices/continuidad        → confirmar quiénes siguen en el nuevo trimestre (docente)
 *
 * Ubicación en el proyecto: Src/Controllers/AprendizController.php
 */
class AprendizController
{
    private AprendizService $servicio;
    private AccesoService   $acceso;   // [Aislamiento entre docentes]

    public function __construct()
    {
        $this->servicio = new AprendizService();
        $this->acceso   = new AccesoService();
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
            'continuidad' => $this->despacharConMetodo($metodo, 'POST',
                fn() => $this->confirmarContinuidad()
            ),
            'restablecer-contrasena' => $this->despacharConMetodo($metodo, 'POST',
                fn() => $this->restablecerContrasena($this->extraerIdRequerido($params, 'aprendiz'))
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
            // [Aislamiento entre docentes] Solo docentes, y solo aprendices de SUS fichas.
            // Antes cualquier usuario (incluso un aprendiz) recibía la lista completa.
            $usuario = AccesoService::usuario();
            AccesoService::exigirDocente($usuario, 'Solo los instructores pueden listar aprendices.');

            $resultado = $this->servicio->listar(
                $idFicha, $estado, $documento, $cuentaEstado, (int) $usuario['id']
            );
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
        try {
            // [Aislamiento] aprendiz → solo él mismo; docente → solo aprendices de sus fichas
            $this->acceso->exigirAprendizAccesible($idAprendiz, AccesoService::usuario());

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
            // [Aislamiento] la ficha debe ser del docente de la sesión
            $this->acceso->exigirFichaPropia($idFicha, AccesoService::usuario());

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
            // [Aislamiento] solo un docente, y solo en una de sus fichas
            $this->acceso->exigirFichaPropia((int) $cuerpo['id_ficha'], AccesoService::usuario());

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
        $cuerpo = $this->leerCuerpoJson();

        if (empty($cuerpo)) {
            $this->responderError('No se recibieron datos para actualizar.', 422);
        }

        try {
            // [Aislamiento] aprendiz → solo su perfil; docente → solo aprendices de sus fichas
            $usuario = AccesoService::usuario();
            $this->acceso->exigirAprendizAccesible($idAprendiz, $usuario);

            // [Aislamiento] Campos permitidos según el rol. Antes un aprendiz podía
            // cambiarse a sí mismo id_ficha, activo o cuenta_activada.
            $permitidos = AccesoService::esDocente($usuario)
                ? ['nombres', 'apellidos', 'activo', 'id_ficha']
                : ['nombres', 'apellidos', 'password_actual', 'password_nueva'];
            $cuerpo = array_intersect_key($cuerpo, array_flip($permitidos));

            if (empty($cuerpo)) {
                $this->responderError('No se recibieron campos que se puedan actualizar.', 422);
            }

            // Mover un aprendiz de ficha: solo a otra ficha del mismo docente
            if (isset($cuerpo['id_ficha'])) {
                $this->acceso->exigirFichaPropia((int) $cuerpo['id_ficha'], $usuario);
            }

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
            // [Aislamiento] antes cualquier usuario autenticado (incluso un aprendiz)
            // podía eliminar cualquier aprendiz.
            $usuario = AccesoService::usuario();
            AccesoService::exigirDocente($usuario, 'Solo los instructores pueden eliminar aprendices.');
            $this->acceso->exigirAprendizAccesible($idAprendiz, $usuario);

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

            // Leer contenido completo y detectar separador
            $contenido = file_get_contents($archivo['tmp_name']);
            if ($contenido === false || trim($contenido) === '') {
                $this->responderError('El archivo CSV está vacío.', 422);
            }

            // Eliminar BOM UTF-8 si existe
            $contenido = ltrim($contenido, "\xEF\xBB\xBF");

            // Normalizar saltos de línea
            $contenido = str_replace(["\r\n", "\r"], "\n", $contenido);

            // Detectar separador por la primera línea
            $primeraLinea = strtok($contenido, "\n");
            $separador = substr_count($primeraLinea, ';') >= substr_count($primeraLinea, ',') ? ';' : ',';

            // Procesar líneas
            $lineas = explode("\n", trim($contenido));
            $cabeceraRaw = str_getcsv(array_shift($lineas), $separador);
            $cabecera = array_map(fn($c) => strtolower(trim($c)), $cabeceraRaw);

            $requeridas = ['numero_documento', 'nombres', 'apellidos', 'codigo_ficha'];
            foreach ($requeridas as $col) {
                if (!in_array($col, $cabecera, true)) {
                    $this->responderError("La columna '{$col}' es obligatoria en el CSV.", 422);
                }
            }

            foreach ($lineas as $linea) {
                $linea = trim($linea);
                if ($linea === '') continue;
                $fila = str_getcsv($linea, $separador);
                if (count($fila) === count($cabecera)) {
                    $filas[] = array_combine($cabecera, $fila);
                }
            }

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
            // [Aislamiento] solo se importa a fichas del docente de la sesión
            $resultado = $this->servicio->importar($filas, (int) $usuario['id']);
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

    /**
     * POST /api/aprendices/restablecer-contrasena/{idAprendiz}  — solo docente
     * Genera contraseña temporal, la hashea y elimina la solicitud pendiente.
     */
    private function restablecerContrasena(int $idAprendiz): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        $usuario = $_SESSION['usuario'] ?? null;
        if (!$usuario || ($usuario['rol'] ?? '') !== 'docente') {
            $this->responderError('Solo los instructores pueden restablecer contraseñas.', 403);
        }

        try {
            // [Aislamiento] solo aprendices de las fichas del docente
            $this->acceso->exigirAprendizAccesible($idAprendiz, $usuario);

            $resultado = $this->servicio->restablecerContrasena($idAprendiz);
            $this->responderExito('Contraseña restablecida correctamente.', $resultado);

        } catch (\RuntimeException $e) {
            $this->responderError($e->getMessage(), $e->getCode() ?: 400);
        } catch (\Throwable $e) {
            $this->responderError('Error interno al restablecer la contraseña.', 500);
        }
    }

    /**
     * POST /api/aprendices/continuidad  — solo docente
     * Body JSON: { "id_ficha": 4, "continuan": [12, 15, 18] }
     *
     * [Trimestres] Los aprendices activos de la ficha que no estén en "continuan"
     * quedan retirados (activo = 0). No se borra ni se duplica nada.
     */
    private function confirmarContinuidad(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) session_start();
        $usuario = $_SESSION['usuario'] ?? null;
        if (!$usuario || ($usuario['rol'] ?? '') !== 'docente') {
            $this->responderError('Solo los instructores pueden confirmar la continuidad.', 403);
        }

        $cuerpo = $this->leerCuerpoJson();

        if (empty($cuerpo['id_ficha']) || !ctype_digit((string) $cuerpo['id_ficha'])) {
            $this->responderError('El campo id_ficha es obligatorio.', 422);
        }
        if (!isset($cuerpo['continuan']) || !is_array($cuerpo['continuan'])) {
            $this->responderError('El campo continuan debe ser una lista de aprendices.', 422);
        }
        foreach ($cuerpo['continuan'] as $id) {
            if (!ctype_digit((string) $id)) {
                $this->responderError('La lista continuan solo admite IDs numéricos.', 422);
            }
        }

        try {
            $resultado = $this->servicio->confirmarContinuidad(
                (int) $cuerpo['id_ficha'],
                $cuerpo['continuan'],
                $usuario
            );
            $this->responderExito('Continuidad confirmada correctamente.', $resultado);

        } catch (\RuntimeException $e) {
            $this->responderError($e->getMessage(), $e->getCode() ?: 400);
        } catch (\Throwable $e) {
            $this->responderError('Error interno al confirmar la continuidad.', 500);
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