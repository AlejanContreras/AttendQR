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

        return $aprendiz;
    }

    public function listar(
        ?int    $idFicha      = null,
        ?string $estado       = null,
        ?string $documento    = null,
        ?string $cuentaEstado = null
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

        $aprendices = $this->aprendizRepo->listar($idFicha, $activo, $documento, $cuentaActiva);

        return [
            'aprendices' => $aprendices,
            'total'      => count($aprendices),
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
    public function activarCuenta(int $idAprendiz, string $password): array
    {
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
    public function importar(array $filas): array
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

        // password_actual es solo validación para cambio de contraseña; no se persiste
        if (isset($datos['password_actual'])) {
            if (!password_verify((string) $datos['password_actual'], $aprendiz['password_hash'] ?? '')) {
                throw new \RuntimeException('La contraseña actual es incorrecta.', 401);
            }
            unset($datos['password_actual']);
        }

        if (isset($datos['password_nueva'])) {
            $datos['password_hash'] = password_hash((string) $datos['password_nueva'], PASSWORD_BCRYPT);
            unset($datos['password_nueva']);
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
