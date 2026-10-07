<?php

declare(strict_types=1);

/**
 * AttendQR – AccesoService
 *
 * [Aislamiento entre docentes] Reglas de propiedad centralizadas.
 *
 * Regla de oro: el usuario SIEMPRE sale de la sesión PHP ($_SESSION['usuario']),
 * nunca de lo que envía el navegador (id_docente en la URL, en el JSON, etc.).
 *
 *   - Un docente solo ve y modifica SUS fichas y lo que cuelga de ellas
 *     (aprendices, sesiones, asistencias, QR).
 *   - Un aprendiz solo ve y modifica SUS propios datos.
 *
 * Todos los métodos lanzan \RuntimeException con el código HTTP correcto,
 * igual que el resto de Services, para que cada Controller responda con su
 * catch habitual:
 *   401 → no hay sesión
 *   403 → existe, pero no es suyo / rol no permitido
 *   404 → no existe
 *
 * Ubicación en el proyecto: Src/Services/AccesoService.php
 */
class AccesoService
{
    private PropiedadRepository $propiedadRepo;

    public function __construct()
    {
        $this->propiedadRepo = new PropiedadRepository();
    }

    // ─── Usuario de la sesión ────────────────────────────────────────────────

    /**
     * Usuario autenticado de la sesión PHP.
     *
     * @return array<string, mixed>
     * @throws \RuntimeException 401 si no hay sesión.
     */
    public static function usuario(): array
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $usuario = $_SESSION['usuario'] ?? null;
        if (!is_array($usuario) || empty($usuario['id']) || empty($usuario['rol'])) {
            throw new \RuntimeException('No autenticado. Debe iniciar sesión.', 401);
        }
        return $usuario;
    }

    public static function esDocente(array $usuario): bool
    {
        return ($usuario['rol'] ?? '') === 'docente';
    }

    public static function esAprendiz(array $usuario): bool
    {
        return ($usuario['rol'] ?? '') === 'aprendiz';
    }

    /** @throws \RuntimeException 403 si el usuario no es docente. */
    public static function exigirDocente(array $usuario, string $mensaje = 'Solo los instructores pueden realizar esta acción.'): void
    {
        if (!self::esDocente($usuario)) {
            throw new \RuntimeException($mensaje, 403);
        }
    }

    // ─── Propiedad ───────────────────────────────────────────────────────────

    /** La ficha debe ser del docente de la sesión. */
    public function exigirFichaPropia(int $idFicha, array $usuario): void
    {
        self::exigirDocente($usuario);
        $this->comparar($this->propiedadRepo->docenteDeFicha($idFicha), $usuario, 'La clase');
    }

    /**
     * El aprendiz debe ser accesible por el usuario de la sesión:
     *   - aprendiz → solo él mismo;
     *   - docente  → solo aprendices de sus fichas.
     */
    public function exigirAprendizAccesible(int $idAprendiz, array $usuario): void
    {
        if (self::esAprendiz($usuario)) {
            if ((int) $usuario['id'] !== $idAprendiz) {
                throw new \RuntimeException('Acceso denegado.', 403);
            }
            return;
        }
        self::exigirDocente($usuario);
        $this->comparar($this->propiedadRepo->docenteDeAprendiz($idAprendiz), $usuario, 'El aprendiz');
    }

    /** La sesión de asistencia debe ser de una ficha del docente de la sesión. */
    public function exigirSesionPropia(int $idSesion, array $usuario): void
    {
        self::exigirDocente($usuario);
        $this->comparar($this->propiedadRepo->docenteDeSesion($idSesion), $usuario, 'La sesión');
    }

    /**
     * El registro de asistencia debe ser accesible:
     *   - aprendiz → solo los suyos;
     *   - docente  → solo los de sesiones de sus fichas.
     */
    public function exigirAsistenciaAccesible(int $idAsistencia, array $usuario): void
    {
        if (self::esAprendiz($usuario)) {
            $dueno = $this->propiedadRepo->aprendizDeAsistencia($idAsistencia);
            if ($dueno === null) {
                throw new \RuntimeException('Registro de asistencia no encontrado.', 404);
            }
            if ($dueno !== (int) $usuario['id']) {
                throw new \RuntimeException('Acceso denegado.', 403);
            }
            return;
        }
        self::exigirDocente($usuario);
        $this->comparar($this->propiedadRepo->docenteDeAsistencia($idAsistencia), $usuario, 'El registro de asistencia');
    }

    /**
     * @param int|null $idDocenteDueno id_docente dueño (null = no existe)
     * @throws \RuntimeException 404 si no existe, 403 si es de otro docente.
     */
    private function comparar(?int $idDocenteDueno, array $usuario, string $entidad): void
    {
        if ($idDocenteDueno === null) {
            throw new \RuntimeException("{$entidad} no existe.", 404);
        }
        if ($idDocenteDueno !== (int) $usuario['id']) {
            throw new \RuntimeException("{$entidad} no pertenece a tus clases.", 403);
        }
    }
}
