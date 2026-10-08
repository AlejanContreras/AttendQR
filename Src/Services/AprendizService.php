<?php

declare(strict_types=1);

/**
 * AttendQR – AprendizService
 *
 * Responsabilidad: lógica de negocio del módulo de aprendices.
 * Flujo: AprendizController → AprendizService → AprendizRepository / FichaRepository → Database
 *
 * MÉTODOS P4 (nuevos):
 *   verificarParaRegistro() — valida documento antes del auto-registro
 *   activarCuenta()         — completa el auto-registro (password + activación)
 *   preRegistrar()          — crea cuenta pendiente (sin contraseña real)
 *   importar()              — procesa lote de filas reutilizando preRegistrar()
 */
class AprendizService
{
    private AprendizRepository $aprendizRepo;
    private FichaRepository    $fichaRepo;

    public function __construct()
    {
        $this->aprendizRepo = new AprendizRepository();
        $this->fichaRepo    = new FichaRepository();
    }

    // ─── Consulta ────────────────────────────────────────────────────────────

    public function consultar(int $idAprendiz): array
    {
        $aprendiz = $this->aprendizRepo->obtenerPorId($idAprendiz);

        if ($aprendiz === null) {
            throw new \RuntimeException('Aprendiz no encontrado.', 404);
        }

        // [Correo del aprendiz] se agrega aparte (consulta protegida por si falta la migración)
        $aprendiz['correo'] = $this->aprendizRepo->obtenerCorreo($idAprendiz);

        return $aprendiz;
    }

    /**
     * [Correo del aprendiz] Normaliza y valida un correo.
     *
     * @throws \RuntimeException 422 si no es un correo válido.
     */
    public static function validarCorreo(string $correo): string
    {
        $correo = strtolower(trim($correo));
        if ($correo === '' || mb_strlen($correo) > 120 || !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            throw new \RuntimeException('Ingresa un correo electrónico válido.', 422);
        }
        return $correo;
    }

    public function listar(
        ?int    $idFicha      = null,
        ?string $estado       = null,
        ?string $documento    = null,
        ?string $cuentaEstado = null,
        ?int    $idDocente    = null
    ): array {
        $activo = match ($estado) {
            'activo'   => 1,
            'inactivo' => 0,
            default    => null,
        };

        $cuentaActiva = match ($cuentaEstado) {
            'activada'  => 1,
            'pendiente' => 0,
            default     => null,
        };

        // [Aislamiento entre docentes] $idDocente viene de la sesión (nunca del navegador)
        $aprendices = $this->aprendizRepo->listar($idFicha, $activo, $documento, $cuentaActiva, $idDocente);

        return [
            'aprendices' => $aprendices,
            'total'      => count($aprendices),
        ];
    }

    // ─── Recuperación de contraseña ──────────────────────────────────────────

    /**
     * Registra una solicitud presencial de recuperación de contraseña.
     * El aprendiz la inicia desde el login; el instructor la atiende desde su panel.
     */
    public function solicitarRecuperacion(string $documento): void
    {
        $aprendiz = $this->aprendizRepo->buscarPorDocumento(trim($documento));

        if ($aprendiz === null) {
            throw new \RuntimeException('Documento no encontrado en el sistema.', 404);
        }

        if ((int) $aprendiz['activo'] !== 1) {
            throw new \RuntimeException('La cuenta está inactiva. Contacta al instructor.', 409);
        }

        $this->aprendizRepo->crearSolicitudRecuperacion((int) $aprendiz['id_aprendiz']);
    }

    /**
     * Genera una contraseña temporal aleatoria de 8 caracteres (letras+dígitos),
     * la hashea y la guarda. Elimina la solicitud pendiente.
     * Retorna la contraseña en texto plano para que el instructor se la comunique.
     */
    public function restablecerContrasena(int $idAprendiz): array
    {
        $aprendiz = $this->aprendizRepo->obtenerPorId($idAprendiz);

        if ($aprendiz === null) {
            throw new \RuntimeException('Aprendiz no encontrado.', 404);
        }

        if (!$this->aprendizRepo->tieneSolicitudPendiente($idAprendiz)) {
            throw new \RuntimeException('Este aprendiz no tiene una solicitud de recuperación pendiente.', 409);
        }

        $chars    = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // sin 0/O/1/I para evitar confusiones
        $temporal = '';
        for ($i = 0; $i < 8; $i++) {
            $temporal .= $chars[random_int(0, strlen($chars) - 1)];
        }

        $this->aprendizRepo->actualizar($idAprendiz, [
            'password_hash'   => password_hash($temporal, PASSWORD_BCRYPT),
            'cuenta_activada' => 1,
        ]);
        $this->aprendizRepo->eliminarSolicitudRecuperacion($idAprendiz);

        return [
            'password_temporal' => $temporal,
            'nombres'           => trim($aprendiz['nombres'] . ' ' . $aprendiz['apellidos']),
        ];
    }

    // ─── Registro con contraseña (docente crea cuenta completa) ─────────────

    /**
     * Registra un aprendiz con contraseña completa (cuenta_activada = 1).
     * Usado por el docente desde el backend o panel admin.
     */
    public function registrar(
        string $numeroDocumento,
        string $nombres,
        string $apellidos,
        string $password,
        int    $idFicha
    ): array {
        $numeroDocumento = trim($numeroDocumento);
        $nombres         = trim($nombres);
        $apellidos       = trim($apellidos);

        if ($this->aprendizRepo->existeDocumento($numeroDocumento)) {
            throw new \RuntimeException('El documento ya está registrado en el sistema.', 409);
        }

        $ficha = $this->fichaRepo->obtenerPorId($idFicha);

        if ($ficha === null) {
            throw new \RuntimeException('Ficha no encontrada.', 404);
        }

        if ((int) $ficha['activa'] !== 1) {
            throw new \RuntimeException('La ficha no está activa.', 422);
        }

        $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        $id           = $this->aprendizRepo->crear($numeroDocumento, $nombres, $apellidos, $passwordHash, $idFicha, 1);

        return $this->aprendizRepo->obtenerPorId($id) ?? [
            'id_aprendiz'      => $id,
            'numero_documento' => $numeroDocumento,
            'nombres'          => $nombres,
            'apellidos'        => $apellidos,
            'id_ficha'         => $idFicha,
            'activo'           => 1,
            'cuenta_activada'  => 1,
        ];
    }

    // ─── Auto-registro en dos pasos (flujo aprendiz) ─────────────────────────

    /**
     * Paso 1: Verifica que el documento existe y está pendiente de activación.
     * No expone datos sensibles. No modifica ningún dato.
     *
     * @throws \RuntimeException 404 — documento no encontrado en ninguna ficha
     * @throws \RuntimeException 409 — la cuenta ya fue activada (debe iniciar sesión)
     */
    public function verificarParaRegistro(string $documento): array
    {
        $documento = trim($documento);
        $aprendiz  = $this->aprendizRepo->buscarPorDocumento($documento);

        if ($aprendiz === null) {
            throw new \RuntimeException(
                'Tu documento no está registrado en ninguna ficha. Contacta a tu instructor.',
                404
            );
        }

        if ((int) $aprendiz['activo'] !== 1) {
            throw new \RuntimeException(
                'Tu cuenta está desactivada. Contacta a tu instructor.',
                403
            );
        }

        if ((int) $aprendiz['cuenta_activada'] === 1) {
            throw new \RuntimeException(
                'Ya tienes una cuenta activa. Inicia sesión normalmente.',
                409
            );
        }

        // Solo devuelve datos públicos — nunca el hash ni el id completo para evitar enumeración
        return [
            'id_aprendiz'     => (int) $aprendiz['id_aprendiz'],
            'nombres'         => $aprendiz['nombres'],
            'apellidos'       => $aprendiz['apellidos'],
            'codigo_ficha'    => $aprendiz['codigo_ficha'],
            'nombre_programa' => $aprendiz['nombre_programa'],
        ];
    }

    /**
     * Paso 2: Activa la cuenta del aprendiz estableciendo su contraseña real.
     * Retorna los datos de sesión en el mismo formato que loginAprendiz.
     *
     * @throws \RuntimeException 404 — aprendiz no encontrado
     * @throws \RuntimeException 409 — cuenta ya activada
     * @throws \RuntimeException 422 — contraseña muy corta
     */
    public function activarCuenta(int $idAprendiz, string $password, string $correo = ''): array
    {
        // [Correo del aprendiz] Obligatorio al activar (si la migración ya se corrió).
        $exigeCorreo = $this->aprendizRepo->correoDisponible();
        if ($exigeCorreo) {
            $correo = self::validarCorreo($correo);
        }

        if (mb_strlen($password) < 8) {
            throw new \RuntimeException('La contraseña debe tener al menos 8 caracteres.', 422);
        }

        $aprendiz = $this->aprendizRepo->obtenerPorId($idAprendiz);

        if ($aprendiz === null) {
            throw new \RuntimeException('Aprendiz no encontrado.', 404);
        }

        if ((int) $aprendiz['cuenta_activada'] === 1) {
            throw new \RuntimeException('Esta cuenta ya fue activada. Inicia sesión normalmente.', 409);
        }

        $hash     = password_hash($password, PASSWORD_BCRYPT);
        $filasActualizadas = $this->aprendizRepo->activarCuenta($idAprendiz, $hash);

        if ($filasActualizadas === 0) {
            // Race condition — otra petición ya activó la cuenta
            throw new \RuntimeException('Esta cuenta ya fue activada. Inicia sesión normalmente.', 409);
        }

        if ($exigeCorreo) {
            $this->aprendizRepo->guardarCorreo($idAprendiz, $correo);
        }

        // Datos de sesión (mismo formato que AuthService::loginAprendiz)
        return [
            'id'               => (int) $aprendiz['id_aprendiz'],
            'nombres'          => $aprendiz['nombres'],
            'apellidos'        => $aprendiz['apellidos'],
            'numero_documento' => $aprendiz['numero_documento'],
            'id_ficha'         => (int) $aprendiz['id_ficha'],
            'codigo_ficha'     => $aprendiz['codigo_ficha'],
            'nombre_programa'  => $aprendiz['nombre_programa'],
            'rol'              => 'aprendiz',
            'requiere_correo'  => false,
        ];
    }

    // ─── Pre-registro para importación ───────────────────────────────────────

    /**
     * Crea un aprendiz pre-registrado (cuenta_activada = 0, contraseña placeholder).
     * El aprendiz deberá completar su registro vía /Views/registro.php.
     *
     * Reutilizable por importar() y por cualquier futura integración (GAS, etc.).
     *
     * @throws \RuntimeException 409 — documento ya existe
     * @throws \RuntimeException 404 — ficha no encontrada
     * @throws \RuntimeException 422 — ficha no activa
     */
    public function preRegistrar(
        string $numeroDocumento,
        string $nombres,
        string $apellidos,
        int    $idFicha
    ): array {
        $numeroDocumento = trim($numeroDocumento);
        $nombres         = trim($nombres);
        $apellidos       = trim($apellidos);

        if ($this->aprendizRepo->existeDocumento($numeroDocumento)) {
            throw new \RuntimeException("El documento '{$numeroDocumento}' ya está registrado.", 409);
        }

        $ficha = $this->fichaRepo->obtenerPorId($idFicha);

        if ($ficha === null) {
            throw new \RuntimeException("Ficha ID {$idFicha} no encontrada.", 404);
        }

        if ((int) $ficha['activa'] !== 1) {
            throw new \RuntimeException("La ficha '{$ficha['codigo_ficha']}' no está activa.", 422);
        }

        // Contraseña placeholder inaccesible — el aprendiz no puede iniciar sesión
        // hasta completar su auto-registro y establecer una contraseña real.
        $placeholderHash = password_hash(bin2hex(random_bytes(16)), PASSWORD_BCRYPT);

        $id = $this->aprendizRepo->crear(
            $numeroDocumento, $nombres, $apellidos, $placeholderHash, $idFicha, 0
        );

        return $this->aprendizRepo->obtenerPorId($id) ?? [
            'id_aprendiz'      => $id,
            'numero_documento' => $numeroDocumento,
            'nombres'          => $nombres,
            'apellidos'        => $apellidos,
            'id_ficha'         => $idFicha,
            'activo'           => 1,
            'cuenta_activada'  => 0,
        ];
    }

    /**
     * Importa un lote de aprendices reutilizando preRegistrar().
     * Mismo servicio para CSV hoy y para GAS mañana.
     *
     * @param array<int, array{numero_documento: string, nombres: string, apellidos: string, codigo_ficha: string}> $filas
     * @return array{ exitosos: int, errores: array<int, array{fila: int, documento: string, error: string}> }
     */
    public function importar(array $filas, ?int $idDocente = null): array
    {
        $exitosos = 0;
        $errores  = [];

        // Cache de fichas ya resueltas en esta importación (evita N consultas)
        $fichaCache = [];

        foreach ($filas as $numero => $fila) {
            $nFila     = $numero + 1;
            $documento = trim((string) ($fila['numero_documento'] ?? ''));

            if ($documento === '') {
                $errores[] = [
                    'fila'      => $nFila,
                    'documento' => '',
                    'error'     => 'El campo numero_documento está vacío.',
                ];
                continue;
            }

            $codigoFicha = trim((string) ($fila['codigo_ficha'] ?? ''));

            if ($codigoFicha === '') {
                $errores[] = [
                    'fila'      => $nFila,
                    'documento' => $documento,
                    'error'     => 'El campo codigo_ficha está vacío.',
                ];
                continue;
            }

            // Resolver id_ficha usando cache
            if (!isset($fichaCache[$codigoFicha])) {
                $ficha = $this->fichaRepo->obtenerPorCodigo($codigoFicha);
                $fichaCache[$codigoFicha] = $ficha;
            }

            $ficha = $fichaCache[$codigoFicha];

            if ($ficha === null) {
                $errores[] = [
                    'fila'      => $nFila,
                    'documento' => $documento,
                    'error'     => "Ficha '{$codigoFicha}' no encontrada.",
                ];
                continue;
            }

            // [Aislamiento entre docentes] no se puede cargar aprendices en la ficha de otro docente
            if ($idDocente !== null && (int) $ficha['id_docente'] !== $idDocente) {
                $errores[] = [
                    'fila'      => $nFila,
                    'documento' => $documento,
                    'error'     => "La ficha '{$codigoFicha}' no pertenece a tus clases.",
                ];
                continue;
            }

            try {
                $this->preRegistrar(
                    $documento,
                    trim((string) ($fila['nombres']   ?? '')),
                    trim((string) ($fila['apellidos'] ?? '')),
                    (int) $ficha['id_ficha']
                );
                $exitosos++;

            } catch (\RuntimeException $e) {
                $errores[] = [
                    'fila'      => $nFila,
                    'documento' => $documento,
                    'error'     => $e->getMessage(),
                ];
            }
        }

        return [
            'exitosos' => $exitosos,
            'errores'  => $errores,
            'total'    => count($filas),
        ];
    }

    // ─── Continuidad de trimestre ─────────────────────────────────────────────

    /**
     * [Trimestres] Confirma qué aprendices de una ficha continúan en el nuevo
     * trimestre. Los aprendices activos que NO vienen en $idsContinuan quedan
     * retirados (activo = 0).
     *
     * Qué NO hace (a propósito):
     *   - No crea ni duplica aprendices: el mismo id_aprendiz sigue en la ficha.
     *   - No borra asistencias ni sesiones: el trimestre anterior queda intacto
     *     (el trimestre se calcula desde fecha_sesion, no se guarda).
     *   - No cambia la ficha: las sesiones nuevas caen solas en el trimestre actual.
     *
     * @param int                  $idFicha      Ficha del docente.
     * @param int[]                $idsContinuan Aprendices que siguen.
     * @param array<string, mixed> $usuario      Docente autenticado.
     * @return array<string, mixed> Resumen: continúan, retirados, trimestre.
     * @throws \RuntimeException 404 ficha inexistente, 403 ficha ajena,
     *                            422 lista vacía o IDs que no pertenecen a la ficha.
     */
    public function confirmarContinuidad(int $idFicha, array $idsContinuan, array $usuario): array
    {
        $ficha = $this->fichaRepo->obtenerPorId($idFicha);
        if ($ficha === null) {
            throw new \RuntimeException('La ficha indicada no existe.', 404);
        }
        if ((int) $ficha['id_docente'] !== (int) ($usuario['id'] ?? 0)) {
            throw new \RuntimeException('No tiene permisos sobre esta ficha.', 403);
        }

        $idsContinuan = array_values(array_unique(array_map('intval', $idsContinuan)));
        if (empty($idsContinuan)) {
            throw new \RuntimeException(
                'Selecciona al menos un aprendiz que continúe. Si ninguno continúa, desactiva la clase.', 422
            );
        }

        $activos = $this->aprendizRepo->idsActivosPorFicha($idFicha);
        $ajenos  = array_diff($idsContinuan, $activos);
        if (!empty($ajenos)) {
            throw new \RuntimeException(
                'Algunos aprendices seleccionados no están activos en esta ficha. Recarga la página e inténtalo de nuevo.', 422
            );
        }

        $aRetirar  = array_values(array_diff($activos, $idsContinuan));
        $retirados = $this->aprendizRepo->retirarVarios($idFicha, $aRetirar);

        $mes       = (int) date('n');
        $trimestre = intdiv($mes - 1, 3) + 1;

        return [
            'id_ficha'     => $idFicha,
            'codigo_ficha' => $ficha['codigo_ficha'] ?? '',
            'continuan'    => count($idsContinuan),
            'retirados'    => $retirados,
            'trimestre'    => 'T' . $trimestre . ' ' . date('Y'),
        ];
    }

    // ─── Actualización y eliminación ─────────────────────────────────────────

    public function actualizar(int $idAprendiz, array $datos): array
    {
        $aprendiz = $this->aprendizRepo->obtenerPorId($idAprendiz);

        if ($aprendiz === null) {
            throw new \RuntimeException('Aprendiz no encontrado.', 404);
        }

        if (!empty($datos['password'])) {
            $datos['password_hash'] = password_hash((string) $datos['password'], PASSWORD_BCRYPT);
            unset($datos['password']);
        }

        // password_actual es solo validación; se compara contra el hash real de la BD
        if (isset($datos['password_actual'])) {
            $hashActual = $this->aprendizRepo->obtenerHashPorId($idAprendiz);
            if (!password_verify((string) $datos['password_actual'], (string) ($hashActual ?? ''))) {
                throw new \RuntimeException('La contraseña actual es incorrecta.', 401);
            }
            unset($datos['password_actual']);
        }

        if (isset($datos['password_nueva'])) {
            $datos['password_hash'] = password_hash((string) $datos['password_nueva'], PASSWORD_BCRYPT);
            unset($datos['password_nueva']);
        }

        // [Correo del aprendiz] va por su propio método (columna protegida por migración)
        if (array_key_exists('correo', $datos)) {
            if (!$this->aprendizRepo->correoDisponible()) {
                throw new \RuntimeException('El correo aún no está habilitado (falta la migración de la base de datos).', 503);
            }
            $this->aprendizRepo->guardarCorreo($idAprendiz, self::validarCorreo((string) $datos['correo']));
            unset($datos['correo']);
        }

        $this->aprendizRepo->actualizar($idAprendiz, $datos);

        return $this->aprendizRepo->obtenerPorId($idAprendiz) ?? $aprendiz;
    }

    public function eliminar(int $idAprendiz): array
    {
        $aprendiz = $this->aprendizRepo->obtenerPorId($idAprendiz);

        if ($aprendiz === null) {
            throw new \RuntimeException('Aprendiz no encontrado.', 404);
        }

        $this->aprendizRepo->eliminar($idAprendiz);

        return ['success' => true, 'message' => 'Aprendiz eliminado correctamente.'];
    }
}
