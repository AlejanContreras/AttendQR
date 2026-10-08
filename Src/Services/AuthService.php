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
        } elseif ($this->temporalValida('docente', $idDocente, $password)) {
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

    // ─── [Recuperación de cuenta por correo: docentes y aprendices] ─────────

    /** Minutos que dura la contraseña temporal. */
    private const RECUPERACION_MINUTOS = 30;
    /** Minutos mínimos entre dos solicitudes para el mismo usuario. */
    private const RECUPERACION_ESPERA_MINUTOS = 5;

    /**
     * Docente: genera una contraseña temporal y la envía a su correo.
     *
     * Seguridad:
     *   - El controlador responde SIEMPRE lo mismo (no revela qué correos existen).
     *   - La contraseña actual NO cambia: la temporal convive con ella 30 minutos.
     *   - Máximo una solicitud cada 5 minutos. En la BD solo va el hash.
     *
     * @return bool true si el correo salió.
     */
    public function solicitarRecuperacionDocente(string $correo): bool
    {
        $correo  = strtolower(trim($correo));
        $docente = $this->authRepo->buscarDocentePorCorreo($correo);

        if ($docente === null || (int) $docente['activo'] !== 1) {
            return false;
        }

        return $this->enviarTemporal(
            'docente',
            (int) $docente['id_docente'],
            (string) $docente['correo'],
            trim($docente['nombres'] . ' ' . $docente['apellidos'])
        ) === 'enviado';
    }

    /**
     * [Correo del aprendiz] Aprendiz: si tiene correo, se le envía la temporal;
     * si no tiene (o el correo falla), sigue el método de siempre: la solicitud
     * le llega al instructor.
     *
     * @return array{via: string, correo?: string}
     *         via = 'correo'     → se envió (o se envió hace menos de 5 min)
     *         via = 'instructor' → se creó la solicitud para el instructor
     * @throws \RuntimeException 404 documento inexistente, 409 cuenta inactiva / sin activar.
     */
    public function solicitarRecuperacionAprendiz(string $documento): array
    {
        $aprendiz = $this->authRepo->buscarAprendizPorDocumento(trim($documento));

        if ($aprendiz === null) {
            throw new \RuntimeException('Documento no encontrado en el sistema.', 404);
        }
        if ((int) $aprendiz['activo'] !== 1) {
            throw new \RuntimeException('La cuenta está inactiva. Contacta al instructor.', 409);
        }
        if ((int) ($aprendiz['cuenta_activada'] ?? 1) === 0) {
            throw new \RuntimeException(
                'Tu cuenta aún no está activada. Ingresa a "Crear mi cuenta" con tu número de documento.', 409
            );
        }

        $idAprendiz = (int) $aprendiz['id_aprendiz'];
        $correo     = $this->authRepo->obtenerCorreoAprendiz($idAprendiz);

        if ($correo !== null) {
            $resultado = $this->enviarTemporal(
                'aprendiz',
                $idAprendiz,
                $correo,
                trim($aprendiz['nombres'] . ' ' . $aprendiz['apellidos'])
            );
            if ($resultado === 'enviado' || $resultado === 'reciente') {
                return ['via' => 'correo', 'correo' => self::enmascararCorreo($correo)];
            }
            // 'error' o 'sin_config' → se cae al método del instructor
        }

        // Método de siempre: solicitud para el instructor
        (new AprendizService())->solicitarRecuperacion((string) $aprendiz['numero_documento']);
        return ['via' => 'instructor'];
    }

    /**
     * Genera, guarda (hash) y envía una contraseña temporal.
     *
     * @return string 'enviado' | 'reciente' (ya se envió hace < 5 min) |
     *                'sin_migracion' | 'sin_config' | 'error'
     */
    private function enviarTemporal(string $tipo, int $id, string $correoDestino, string $nombre): string
    {
        $actual = $this->authRepo->obtenerRecuperacion($tipo, $id);
        if ($actual === null) {
            error_log("[AttendQR][Recuperación] Falta la migración de recuperación para {$tipo}.");
            return 'sin_migracion';
        }

        // Límite de frecuencia: si la temporal vigente se creó hace menos de 5 min, no se reenvía.
        if (!empty($actual['recuperacion_expira'])) {
            $creada = strtotime((string) $actual['recuperacion_expira']) - self::RECUPERACION_MINUTOS * 60;
            if (time() - $creada < self::RECUPERACION_ESPERA_MINUTOS * 60) {
                return 'reciente';
            }
        }

        $correoSrv = new CorreoService();
        if (!$correoSrv->estaConfigurado()) {
            error_log('[AttendQR][Recuperación] Correo no configurado (Src/Config/correo.php).');
            return 'sin_config';
        }

        $temporal = $this->generarTemporal();
        $expira   = date('Y-m-d H:i:s', time() + self::RECUPERACION_MINUTOS * 60);

        if (!$this->authRepo->guardarRecuperacion($tipo, $id, password_hash($temporal, PASSWORD_BCRYPT), $expira)) {
            return 'sin_migracion';
        }

        [$asunto, $html, $texto] = $this->plantillaCorreo($nombre, $temporal, $correoSrv->urlLogin(), $tipo);
        $enviado = $correoSrv->enviar($correoDestino, $nombre, $asunto, $html, $texto);

        // Si el correo no salió, se descarta la temporal para poder reintentar de inmediato.
        if (!$enviado) {
            $this->authRepo->limpiarRecuperacion($tipo, $id);
            return 'error';
        }
        return 'enviado';
    }

    /** @return array{0:string,1:string,2:string} asunto, html, texto */
    private function plantillaCorreo(string $nombre, string $temporal, string $url, string $tipo): array
    {
        $minutos = self::RECUPERACION_MINUTOS;
        $usuario = $tipo === 'aprendiz' ? 'tu número de documento' : 'tu correo';

        $texto = "Hola {$nombre},\n\n"
            . "Recibimos una solicitud para recuperar tu cuenta de AttendQR.\n\n"
            . "Tu contraseña temporal es: {$temporal}\n\n"
            . "Sirve durante {$minutos} minutos. Inicia sesión con {$usuario} y esta contraseña, y cámbiala en \"Mi Perfil\".\n"
            . ($url !== '' ? "Iniciar sesión: {$url}\n" : '')
            . "\nSi no fuiste tú, ignora este correo: tu contraseña actual sigue funcionando.\n";

        $nombreH  = htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8');
        $urlH     = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
        $usuarioH = htmlspecialchars($usuario, ENT_QUOTES, 'UTF-8');
        $html = "<div style=\"font-family:Arial,sans-serif;font-size:15px;color:#1f2937;max-width:520px\">"
            . "<h2 style=\"color:#39A900;margin-bottom:4px\">AttendQR</h2>"
            . "<p>Hola <strong>{$nombreH}</strong>,</p>"
            . "<p>Recibimos una solicitud para recuperar tu cuenta.</p>"
            . "<p>Tu contraseña temporal es:</p>"
            . "<p style=\"font-size:24px;font-weight:bold;letter-spacing:3px;background:#f3f4f6;padding:12px 16px;border-radius:8px;display:inline-block\">{$temporal}</p>"
            . "<p>Sirve durante <strong>{$minutos} minutos</strong>. Inicia sesión con {$usuarioH} y esta contraseña, y cámbiala en <em>Mi Perfil</em>.</p>"
            . ($url !== '' ? "<p><a href=\"{$urlH}\" style=\"color:#39A900\">Iniciar sesión en AttendQR</a></p>" : '')
            . "<p style=\"color:#6b7280;font-size:13px\">Si no fuiste tú, ignora este correo: tu contraseña actual sigue funcionando.</p>"
            . "</div>";

        return ['AttendQR · Recuperación de contraseña', $html, $texto];
    }

    /** ¿La contraseña coincide con una temporal vigente? */
    private function temporalValida(string $tipo, int $id, string $password): bool
    {
        $rec = $this->authRepo->obtenerRecuperacion($tipo, $id);
        if ($rec === null || empty($rec['recuperacion_hash']) || empty($rec['recuperacion_expira'])) {
            return false;
        }
        // Hora de PHP (America/Bogota), no NOW() de MySQL: el hosting puede tener otra zona horaria.
        if (strtotime((string) $rec['recuperacion_expira']) < time()) {
            return false;
        }
        return password_verify($password, (string) $rec['recuperacion_hash']);
    }

    /** a***z@gmail.com → muestra solo lo justo para que el aprendiz reconozca su correo. */
    public static function enmascararCorreo(string $correo): string
    {
        [$usuario, $dominio] = array_pad(explode('@', $correo, 2), 2, '');
        $visible = mb_substr($usuario, 0, 2);
        return $visible . str_repeat('*', max(3, mb_strlen($usuario) - 2)) . '@' . $dominio;
    }

    /** 8 caracteres sin 0/O/1/I (mismo alfabeto que la recuperación por instructor). */
    private function generarTemporal(): string
    {
        $chars    = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $temporal = '';
        for ($i = 0; $i < 8; $i++) {
            $temporal .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $temporal;
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
    public function loginAprendiz(string $documento, string $password): array
    {
        $documento = trim($documento);
        $aprendiz  = $this->authRepo->buscarAprendizPorDocumento($documento);

        if ($aprendiz === null) {
            throw new \RuntimeException('Credenciales inválidas.', 401);
        }

        $idAprendiz  = (int) $aprendiz['id_aprendiz'];
        $usoTemporal = false;

        if (password_verify($password, $aprendiz['password_hash'])) {
            $this->authRepo->limpiarRecuperacion('aprendiz', $idAprendiz);
        } elseif ($this->temporalValida('aprendiz', $idAprendiz, $password)) {
            // [Correo del aprendiz] Entró con la contraseña temporal enviada a su correo
            $usoTemporal = true;
        } else {
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

        if ($usoTemporal) {
            $this->authRepo->consumirRecuperacion('aprendiz', $idAprendiz);
            // Ya recuperó su cuenta: la solicitud al instructor (si había) sobra.
            (new AprendizRepository())->eliminarSolicitudRecuperacion($idAprendiz);
        }

        // [Correo del aprendiz] Si aún no registra correo, la interfaz se lo pide.
        // Solo se exige cuando la columna ya existe (migración aplicada).
        $requiereCorreo = $this->authRepo->correoAprendizDisponible()
            && $this->authRepo->obtenerCorreoAprendiz($idAprendiz) === null;

        return [
            'id'                  => $idAprendiz,
            'nombres'             => $aprendiz['nombres'],
            'apellidos'           => $aprendiz['apellidos'],
            'numero_documento'    => $aprendiz['numero_documento'],
            'id_ficha'            => (int) $aprendiz['id_ficha'],
            'codigo_ficha'        => $aprendiz['codigo_ficha'],
            'nombre_programa'     => $aprendiz['nombre_programa'],
            'rol'                 => 'aprendiz',
            'contrasena_temporal' => $usoTemporal,
            'requiere_correo'     => $requiereCorreo,
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