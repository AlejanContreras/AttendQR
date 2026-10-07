<?php

declare(strict_types=1);

/**
 * AttendQR – AuthService
 *
 * Responsabilidad: lógica de negocio de autenticación.
 *
 * El schema MVP no incluye contraseñas en docentes ni aprendices.
 * La autenticación del MVP se basa en:
 *   - Docentes  → correo electrónico + verificación de activo
 *   - Aprendices → número de documento + verificación de activo
 *
 * Los tokens de sesión del usuario se gestionan mediante $_SESSION
 * (sesiones PHP nativas) ya que el schema no incluye tabla de tokens
 * de autenticación separada. La tabla tokens_qr es exclusiva para
 * los QR de asistencia.
 *
 * Flujo: AuthController → AuthService → AuthRepository → Database
 *
 * Ubicación en el proyecto: Src/Services/AuthService.php
 */
class AuthService
{
    private AuthRepository $authRepo;

    public function __construct()
    {
        $this->authRepo = new AuthRepository();
    }

    /**
     * Autentica a un docente verificando correo, contraseña y estado activo.
     *
     * Reglas de negocio:
     *   1. El correo debe existir en la tabla docentes.
     *   2. La contraseña debe coincidir con el hash almacenado.
     *   3. El docente debe tener activo = 1.
     *   4. Se retornan datos públicos del docente sin exponer el hash.
     *
     * @param string $correo   Correo electrónico del docente.
     * @param string $password Contraseña en texto plano.
     * @return array<string, mixed> Datos públicos del docente autenticado.
     * @throws \RuntimeException 401 si el correo no existe o la contraseña es incorrecta.
     * @throws \RuntimeException 403 si el docente está inactivo.
     */
    public function loginDocente(string $correo, string $password): array
    {
        $correo  = strtolower(trim($correo));
        $docente = $this->authRepo->buscarDocentePorCorreo($correo);

        if ($docente === null) {
            throw new \RuntimeException('Credenciales inválidas.', 401);
        }

        $idDocente  = (int) $docente['id_docente'];
        $usoTemporal = false;

        if (password_verify($password, $docente['password_hash'])) {
            // Entró con su contraseña de siempre: si había pedido una temporal, se descarta.
            $this->authRepo->limpiarRecuperacionDocente($idDocente);
        } elseif ($this->temporalValida($idDocente, $password)) {
            // [Recuperación] Entró con la contraseña temporal del correo.
            $usoTemporal = true;
        } else {
            throw new \RuntimeException('Credenciales inválidas.', 401);
        }

        if ((int) $docente['activo'] !== 1) {
            throw new \RuntimeException('El docente se encuentra inactivo.', 403);
        }

        if ($usoTemporal) {
            // La temporal pasa a ser su contraseña (se le pide cambiarla en Mi Perfil).
            $this->authRepo->consumirRecuperacionDocente($idDocente);
        }

        return [
            'id'                  => $idDocente,
            'nombres'             => $docente['nombres'],
            'apellidos'           => $docente['apellidos'],
            'correo'              => $docente['correo'],
            'rol'                 => 'docente',
            'contrasena_temporal' => $usoTemporal,
        ];
    }

    /**
     * Autentica a un aprendiz verificando documento, contraseña y estado activo.
     *
     * Reglas de negocio:
     *   1. El documento debe existir en la tabla aprendices.
     *   2. La contraseña debe coincidir con el hash almacenado.
     *   3. El aprendiz debe tener activo = 1.
     *   4. Se retornan datos públicos del aprendiz sin exponer el hash.
     *
     * @param string $documento Número de documento del aprendiz.
     * @param string $password  Contraseña en texto plano.
     * @return array<string, mixed> Datos públicos del aprendiz autenticado.
     * @throws \RuntimeException 401 si el documento no existe o la contraseña es incorrecta.
     * @throws \RuntimeException 403 si el aprendiz está retirado.
     */
    // ─── [Recuperación de cuenta del docente por correo] ─────────────────────

    /** Minutos que dura la contraseña temporal. */
    private const RECUPERACION_MINUTOS = 30;
    /** Minutos mínimos entre dos solicitudes para el mismo correo. */
    private const RECUPERACION_ESPERA_MINUTOS = 5;

    /**
     * Genera una contraseña temporal de 8 caracteres y la envía al correo del docente.
     *
     * Seguridad:
     *   - La respuesta al navegador es SIEMPRE la misma (exista o no el correo),
     *     para que nadie pueda averiguar qué correos están registrados.
     *   - La contraseña actual NO cambia: la temporal convive con ella 30 minutos.
     *     Si alguien pide la recuperación con el correo de otro, ese docente
     *     sigue entrando normal.
     *   - Máximo una solicitud cada 5 minutos por correo.
     *   - En la BD solo se guarda el hash de la temporal.
     *
     * @return bool true si se envió el correo (solo para pruebas internas;
     *              el controlador no se lo dice al navegador).
     */
    public function solicitarRecuperacionDocente(string $correo): bool
    {
        $correo  = strtolower(trim($correo));
        $docente = $this->authRepo->buscarDocentePorCorreo($correo);

        if ($docente === null || (int) $docente['activo'] !== 1) {
            return false;
        }

        $idDocente = (int) $docente['id_docente'];
        $actual    = $this->authRepo->obtenerRecuperacionDocente($idDocente);

        if ($actual === null) {
            error_log('[AttendQR][Recuperación] Falta correr Migracion_Recuperacion_Docente.sql');
            return false;
        }

        // Límite de frecuencia: si la temporal vigente se creó hace menos de 5 min, no se reenvía.
        if (!empty($actual['recuperacion_expira'])) {
            $creada = strtotime((string) $actual['recuperacion_expira']) - self::RECUPERACION_MINUTOS * 60;
            if (time() - $creada < self::RECUPERACION_ESPERA_MINUTOS * 60) {
                return false;
            }
        }

        $correoSrv = new CorreoService();
        if (!$correoSrv->estaConfigurado()) {
            error_log('[AttendQR][Recuperación] Correo no configurado (Src/Config/correo.php).');
            return false;
        }

        $temporal = $this->generarTemporal();
        $expira   = date('Y-m-d H:i:s', time() + self::RECUPERACION_MINUTOS * 60);

        if (!$this->authRepo->guardarRecuperacionDocente($idDocente, password_hash($temporal, PASSWORD_BCRYPT), $expira)) {
            return false;
        }

        $nombre = trim($docente['nombres'] . ' ' . $docente['apellidos']);
        $url    = $correoSrv->urlLogin();
        $minutos = self::RECUPERACION_MINUTOS;

        $texto = "Hola {$nombre},\n\n"
            . "Recibimos una solicitud para recuperar tu cuenta de AttendQR.\n\n"
            . "Tu contraseña temporal es: {$temporal}\n\n"
            . "Sirve durante {$minutos} minutos. Inicia sesión con ella y cámbiala en \"Mi Perfil\".\n"
            . ($url !== '' ? "Iniciar sesión: {$url}\n" : '')
            . "\nSi no fuiste tú, ignora este correo: tu contraseña actual sigue funcionando.\n";

        $nombreH = htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8');
        $urlH    = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
        $html = "<div style=\"font-family:Arial,sans-serif;font-size:15px;color:#1f2937;max-width:520px\">"
            . "<h2 style=\"color:#39A900;margin-bottom:4px\">AttendQR</h2>"
            . "<p>Hola <strong>{$nombreH}</strong>,</p>"
            . "<p>Recibimos una solicitud para recuperar tu cuenta.</p>"
            . "<p>Tu contraseña temporal es:</p>"
            . "<p style=\"font-size:24px;font-weight:bold;letter-spacing:3px;background:#f3f4f6;padding:12px 16px;border-radius:8px;display:inline-block\">{$temporal}</p>"
            . "<p>Sirve durante <strong>{$minutos} minutos</strong>. Inicia sesión con ella y cámbiala en <em>Mi Perfil</em>.</p>"
            . ($url !== '' ? "<p><a href=\"{$urlH}\" style=\"color:#39A900\">Iniciar sesión en AttendQR</a></p>" : '')
            . "<p style=\"color:#6b7280;font-size:13px\">Si no fuiste tú, ignora este correo: tu contraseña actual sigue funcionando.</p>"
            . "</div>";

        $enviado = $correoSrv->enviar($docente['correo'], $nombre, 'AttendQR · Recuperación de contraseña', $html, $texto);

        // Si el correo no salió, se descarta la temporal para que pueda intentar de nuevo
        // de inmediato (sin esperar el límite de 5 minutos).
        if (!$enviado) {
            $this->authRepo->limpiarRecuperacionDocente($idDocente);
        }

        return $enviado;
    }

    /** ¿La contraseña coincide con una temporal vigente del docente? */
    private function temporalValida(int $idDocente, string $password): bool
    {
        $rec = $this->authRepo->obtenerRecuperacionDocente($idDocente);
        if ($rec === null || empty($rec['recuperacion_hash']) || empty($rec['recuperacion_expira'])) {
            return false;
        }
        // Se compara con la hora de PHP (America/Bogota), no con NOW() de MySQL,
        // porque el MySQL del hosting puede estar en otra zona horaria.
        if (strtotime((string) $rec['recuperacion_expira']) < time()) {
            return false;
        }
        return password_verify($password, (string) $rec['recuperacion_hash']);
    }

    /** 8 caracteres sin 0/O/1/I (mismo alfabeto que la recuperación de aprendices). */
    private function generarTemporal(): string
    {
        $chars    = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $temporal = '';
        for ($i = 0; $i < 8; $i++) {
            $temporal .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $temporal;
    }

    public function loginAprendiz(string $documento, string $password): array
    {
        $documento = trim($documento);
        $aprendiz  = $this->authRepo->buscarAprendizPorDocumento($documento);

        if ($aprendiz === null || !password_verify($password, $aprendiz['password_hash'])) {
            throw new \RuntimeException('Credenciales inválidas.', 401);
        }

        if ((int) $aprendiz['activo'] !== 1) {
            throw new \RuntimeException('El aprendiz se encuentra retirado del sistema.', 403);
        }

        if ((int) ($aprendiz['cuenta_activada'] ?? 1) === 0) {
            throw new \RuntimeException(
                'Debes activar tu cuenta primero. Ingresa a la página de registro con tu número de documento.',
                403
            );
        }

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

    /**
     * Método genérico de login que detecta el tipo de usuario
     * según los campos enviados.
     *
     * Si recibe 'correo'    → login como docente.
     * Si recibe 'documento' → login como aprendiz.
     * Siempre requiere 'password'.
     *
     * @param string $correo    Correo del docente (o vacío).
     * @param string $documento Documento del aprendiz (o vacío).
     * @param string $password  Contraseña en texto plano.
     * @return array<string, mixed>
     * @throws \RuntimeException 422 si faltan parámetros obligatorios.
     */
    public function login(string $correo = '', string $documento = '', string $password = ''): array
    {
        $correo    = trim($correo);
        $documento = trim($documento);
        $password  = trim($password);

        if ($password === '') {
            throw new \RuntimeException('La contraseña es obligatoria.', 422);
        }

        if ($correo !== '') {
            return $this->loginDocente($correo, $password);
        }

        if ($documento !== '') {
            return $this->loginAprendiz($documento, $password);
        }

        throw new \RuntimeException('Debe proporcionar correo (docente) o documento (aprendiz).', 422);
    }

    /**
     * Cierra la sesión destruyendo la sesión PHP activa.
     *
     * @return array<string, mixed>
     */
    public function logout(): array
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_unset();
            session_destroy();
        }

        return ['success' => true, 'message' => 'Sesión cerrada correctamente.'];
    }

    /**
     * Verifica si hay una sesión PHP activa y retorna los datos del usuario.
     *
     * @return array<string, mixed> Datos del usuario en sesión.
     * @throws \RuntimeException 401 si no hay sesión activa.
     */
    public function verificarToken(): array
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (empty($_SESSION['usuario'])) {
            throw new \RuntimeException('No hay sesión activa.', 401);
        }

        return $_SESSION['usuario'];
    }
}