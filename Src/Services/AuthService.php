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
     * Autentica a un docente verificando su correo y estado activo.
     *
     * Reglas de negocio:
     *   1. El correo debe existir en la tabla docentes.
     *   2. El docente debe tener activo = 1.
     *   3. Se retornan los datos públicos del docente con rol asignado.
     *
     * @param string $correo Correo electrónico del docente.
     * @return array<string, mixed> Datos públicos del docente autenticado.
     * @throws \RuntimeException 401 si el correo no existe.
     * @throws \RuntimeException 403 si el docente está inactivo.
     */
    public function loginDocente(string $correo): array
    {
        $correo  = strtolower(trim($correo));
        $docente = $this->authRepo->buscarDocentePorCorreo($correo);

        if ($docente === null) {
            throw new \RuntimeException('Credenciales inválidas.', 401);
        }

        if ((int) $docente['activo'] !== 1) {
            throw new \RuntimeException('El docente se encuentra inactivo.', 403);
        }

        return [
            'id'      => (int) $docente['id_docente'],
            'nombres' => $docente['nombres'],
            'apellidos' => $docente['apellidos'],
            'correo'  => $docente['correo'],
            'rol'     => 'docente',
        ];
    }

    /**
     * Autentica a un aprendiz verificando su número de documento y estado activo.
     *
     * Reglas de negocio:
     *   1. El documento debe existir en la tabla aprendices.
     *   2. El aprendiz debe tener activo = 1.
     *   3. Se retornan los datos públicos del aprendiz con rol asignado.
     *
     * @param string $documento Número de documento del aprendiz.
     * @return array<string, mixed> Datos públicos del aprendiz autenticado.
     * @throws \RuntimeException 401 si el documento no existe.
     * @throws \RuntimeException 403 si el aprendiz está inactivo (retirado).
     */
    public function loginAprendiz(string $documento): array
    {
        $documento = trim($documento);
        $aprendiz  = $this->authRepo->buscarAprendizPorDocumento($documento);

        if ($aprendiz === null) {
            throw new \RuntimeException('Credenciales inválidas.', 401);
        }

        if ((int) $aprendiz['activo'] !== 1) {
            throw new \RuntimeException('El aprendiz se encuentra retirado del sistema.', 403);
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
     * Si recibe 'correo'    → intenta login como docente.
     * Si recibe 'documento' → intenta login como aprendiz.
     *
     * @param string      $correo    Correo del docente (o vacío).
     * @param string      $documento Documento del aprendiz (o vacío).
     * @return array<string, mixed>
     * @throws \RuntimeException 422 si no se proporcionó correo ni documento.
     */
    public function login(string $correo = '', string $documento = ''): array
    {
        $correo    = trim($correo);
        $documento = trim($documento);

        if ($correo !== '') {
            return $this->loginDocente($correo);
        }

        if ($documento !== '') {
            return $this->loginAprendiz($documento);
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