<?php

declare(strict_types=1);

/**
 * AttendQR – QrController
 *
 * Responsabilidad: gestionar la generación, consulta y validación de códigos QR.
 * Cada sesión activa tiene un QR asociado con un token único y con expiración.
 * Los estudiantes escanean ese QR para registrar su asistencia.
 *
 * Rutas que maneja este controlador:
 *   POST /api/qr/generar/{idSesion}   → generar un nuevo QR para una sesión
 *   GET  /api/qr/token-activo/{id}    → consultar el token QR vigente de una sesión
 *   POST /api/qr/validar              → validar un token QR escaneado por un estudiante
 *
 * Ubicación en el proyecto: Src/Controllers/QrController.php
 */
class QrController
{
    // -------------------------------------------------------------------------
    // Punto de entrada principal
    // -------------------------------------------------------------------------

    /**
     * Recibe la petición del router y la despacha a la acción correspondiente.
     *
     * @param string   $metodo  Método HTTP (GET, POST, etc.)
     * @param string   $accion  Segundo segmento de la URL (/api/qr/{accion})
     * @param string[] $params  Parámetros posicionales adicionales (ej. ID de sesión)
     */
    public function handle(string $metodo, string $accion, array $params): void
    {
        switch ($accion) {

            case 'generar':
                // El ID de la sesión llega como primer parámetro posicional
                // Ejemplo: /api/qr/generar/15  → $params[0] = "15"
                $this->verificarMetodo($metodo, 'POST');
                $idSesion = $this->extraerIdRequerido($params, 'sesión');
                $this->generar($idSesion);
                break;

            case 'token-activo':
                // El ID de la sesión llega como primer parámetro posicional
                // Ejemplo: /api/qr/token-activo/15  → $params[0] = "15"
                $this->verificarMetodo($metodo, 'GET');
                $idSesion = $this->extraerIdRequerido($params, 'sesión');
                $this->tokenActivo($idSesion);
                break;

            case 'validar':
                // El token llega en el cuerpo JSON, no como parámetro de ruta
                $this->verificarMetodo($metodo, 'POST');
                $this->validar();
                break;

            default:
                $this->responderError("Acción '{$accion}' no encontrada en QrController.", 404);
        }
    }

    // -------------------------------------------------------------------------
    // Acciones del controlador
    // -------------------------------------------------------------------------

    /**
     * Acción: generar un nuevo código QR para una sesión activa.
     *
     * Crea un token único con tiempo de expiración configurable y lo asocia
     * a la sesión indicada. Si ya existía un QR previo, lo invalida.
     *
     * POST /api/qr/generar/{idSesion}
     *
     * @param int $idSesion Identificador de la sesión a la que se vincula el QR.
     */
    private function generar(int $idSesion): void
    {
        // ► AQUÍ: llamar a QrService->generar($idSesion)
        // El servicio:
        //   1. Verificará que la sesión existe y está activa.
        //   2. Invalidará el QR anterior si lo hubiera.
        //   3. Generará un token único (UUID v4 o hash seguro).
        //   4. Calculará la fecha/hora de expiración (p. ej. +15 minutos).
        //   5. Guardará el registro en la tabla `qr_tokens` vía QrRepository.
        //   6. Retornará el token y su URL de escaneo lista para mostrar.
        //
        // Ejemplo futuro:
        //   $servicio = new QrService();
        //   $qr = $servicio->generar($idSesion);
        //   $this->responderJson($qr, 201);

        $this->responderJson([
            'mensaje'   => 'Acción generar QR recibida correctamente. Lógica pendiente de implementar.',
            'id_sesion' => $idSesion,
        ], 201);
    }

    /**
     * Acción: consultar el token QR activo de una sesión.
     *
     * Devuelve el token vigente y su tiempo restante de expiración.
     * Permite al docente mostrar el QR en pantalla sin regenerarlo.
     *
     * GET /api/qr/token-activo/{idSesion}
     *
     * @param int $idSesion Identificador de la sesión consultada.
     */
    private function tokenActivo(int $idSesion): void
    {
        // ► AQUÍ: llamar a QrService->obtenerTokenActivo($idSesion)
        // El servicio consultará el QrRepository para encontrar el token
        // no expirado vinculado a esa sesión.
        // Si no existe o ya expiró, lanzará una excepción → 404.
        // Si existe, devolverá el token y los segundos restantes antes de expirar.
        //
        // Ejemplo futuro:
        //   $servicio = new QrService();
        //   $tokenData = $servicio->obtenerTokenActivo($idSesion);
        //   $this->responderJson($tokenData, 200);

        $this->responderJson([
            'mensaje'   => 'Acción token-activo recibida correctamente. Lógica pendiente de implementar.',
            'id_sesion' => $idSesion,
        ], 200);
    }

    /**
     * Acción: validar un token QR escaneado por un estudiante.
     *
     * Recibe el token escaneado, verifica que sea válido y no haya expirado,
     * y autoriza el registro de asistencia del estudiante en esa sesión.
     *
     * POST /api/qr/validar
     * Body esperado: { "token": "abc123...", "id_estudiante": 42 }
     */
    private function validar(): void
    {
        $cuerpo = $this->leerCuerpoJson();

        // Validar que los campos obligatorios estén presentes
        if (empty($cuerpo['token']) || empty($cuerpo['id_estudiante'])) {
            $this->responderError('Los campos token e id_estudiante son obligatorios.', 422);
        }

        $token         = trim($cuerpo['token']);
        $idEstudiante  = (int) $cuerpo['id_estudiante'];

        // ► AQUÍ: llamar a QrService->validar($token, $idEstudiante)
        // El servicio:
        //   1. Buscará el token en la tabla `qr_tokens`.
        //   2. Verificará que no haya expirado (comparando con NOW()).
        //   3. Verificará que la sesión asociada siga activa.
        //   4. Comprobará que el estudiante no haya registrado asistencia ya.
        //   5. Si todo es válido, notificará a AsistenciaService para registrarla.
        //   6. Retornará confirmación con los datos de la asistencia registrada.
        //
        // Ejemplo futuro:
        //   $servicio = new QrService();
        //   $resultado = $servicio->validar($token, $idEstudiante);
        //   $this->responderJson($resultado, 200);

        $this->responderJson([
            'mensaje'       => 'Acción validar QR recibida correctamente. Lógica pendiente de implementar.',
            'token'         => $token,
            'id_estudiante' => $idEstudiante,
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