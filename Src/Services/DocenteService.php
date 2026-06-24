<?php

declare(strict_types=1);

/**
 * AttendQR – DocenteService
 *
 * Responsabilidad: lógica de negocio del módulo de docentes.
 * Flujo: DocenteController → DocenteService → DocenteRepository / SesionRepository → Database
 *
 * Ubicación en el proyecto: Src/Services/DocenteService.php
 */
class DocenteService
{
    private DocenteRepository $docenteRepo;
    private SesionRepository  $sesionRepo;

    public function __construct()
    {
        $this->docenteRepo = new DocenteRepository();
        $this->sesionRepo  = new SesionRepository();
    }

    /**
     * Obtiene los datos públicos de un docente por su ID.
     * Nunca incluye la contraseña hasheada en la respuesta.
     *
     * @param int $idDocente Identificador del docente.
     * @return array<string, mixed>
     * @throws \RuntimeException 404 si el docente no existe.
     */
    public function consultar(int $idDocente): array
    {
        $docente = $this->docenteRepo->obtenerPorId($idDocente);

        if ($docente === null) {
            throw new \RuntimeException('Docente no encontrado.', 404);
        }

        return $docente;
    }

    /**
     * Lista docentes con filtros opcionales de estado y especialidad.
     *
     * @param string|null $estado       Filtro por estado ('activo' | 'inactivo').
     * @param string|null $especialidad Filtro por especialidad.
     * @return array<string, mixed>
     */
    public function listar(?string $estado = null, ?string $especialidad = null): array
    {
        $docentes = $this->docenteRepo->listar($estado, $especialidad);

        return [
            'docentes' => $docentes,
            'total'    => count($docentes),
        ];
    }

    /**
     * Registra un nuevo docente en el sistema.
     *
     * Reglas de negocio:
     *   1. El correo debe tener formato válido.
     *   2. El documento no puede estar duplicado.
     *   3. El correo no puede estar en uso.
     *   4. La contraseña se hashea con BCRYPT antes de persistir.
     *
     * @param string      $documento    Número de documento de identidad.
     * @param string      $nombres      Nombres del docente.
     * @param string      $apellidos    Apellidos del docente.
     * @param string      $correo       Correo electrónico institucional.
     * @param string      $contrasena   Contraseña en texto plano.
     * @param string|null $especialidad Especialidad o área de formación.
     * @return array<string, mixed> Datos públicos del docente creado.
     * @throws \RuntimeException 422 si el correo no es válido.
     * @throws \RuntimeException 409 si el documento ya está registrado.
     * @throws \RuntimeException 409 si el correo ya está en uso.
     */
    public function registrar(
        string  $documento,
        string  $nombres,
        string  $apellidos,
        string  $correo,
        string  $contrasena,
        ?string $especialidad = null
    ): array {
        $documento = trim($documento);
        $nombres   = trim($nombres);
        $apellidos = trim($apellidos);
        $correo    = strtolower(trim($correo));

        if (!$this->esCorreoValido($correo)) {
            throw new \RuntimeException("El correo '{$correo}' no tiene un formato válido.", 422);
        }

        if ($this->docenteRepo->existeDocumento($documento)) {
            throw new \RuntimeException('El documento ya está registrado en el sistema.', 409);
        }

        if ($this->docenteRepo->existeCorreo($correo)) {
            throw new \RuntimeException('El correo ya está en uso por otro docente.', 409);
        }

        $contrasenaHash = password_hash($contrasena, PASSWORD_BCRYPT);

        $id      = $this->docenteRepo->crear($documento, $nombres, $apellidos, $correo, $contrasenaHash, $especialidad);
        $docente = $this->docenteRepo->obtenerPorId($id);

        return $docente ?? [
            'id'           => $id,
            'documento'    => $documento,
            'nombres'      => $nombres,
            'apellidos'    => $apellidos,
            'correo'       => $correo,
            'especialidad' => $especialidad,
            'estado'       => 'activo',
        ];
    }

    /**
     * Actualiza los datos de un docente existente (actualización parcial).
     *
     * Reglas de negocio:
     *   1. El docente debe existir.
     *   2. Si se actualiza el correo, debe ser válido y no estar en uso.
     *   3. Si se actualiza la contraseña, se re-hashea antes de persistir.
     *
     * @param int                  $idDocente Identificador del docente.
     * @param array<string, mixed> $datos     Campos a actualizar.
     * @return array<string, mixed> Datos actualizados del docente.
     * @throws \RuntimeException 404 si el docente no existe.
     * @throws \RuntimeException 422 si el correo enviado no es válido.
     * @throws \RuntimeException 409 si el correo ya está en uso.
     */
    public function actualizar(int $idDocente, array $datos): array
    {
        $docente = $this->docenteRepo->obtenerPorId($idDocente);

        if ($docente === null) {
            throw new \RuntimeException('Docente no encontrado.', 404);
        }

        if (isset($datos['correo'])) {
            $datos['correo'] = strtolower(trim((string) $datos['correo']));

            if (!$this->esCorreoValido($datos['correo'])) {
                throw new \RuntimeException("El correo '{$datos['correo']}' no tiene un formato válido.", 422);
            }

            if ($this->docenteRepo->existeCorreo($datos['correo'], $idDocente)) {
                throw new \RuntimeException('El correo ya está en uso por otro docente.', 409);
            }
        }

        if (!empty($datos['contrasena'])) {
            $datos['contrasena_hash'] = password_hash((string) $datos['contrasena'], PASSWORD_BCRYPT);
            unset($datos['contrasena']);
        }

        $this->docenteRepo->actualizar($idDocente, $datos);

        return $this->docenteRepo->obtenerPorId($idDocente) ?? $docente;
    }

    /**
     * Elimina un docente del sistema.
     *
     * Reglas de negocio:
     *   1. El docente debe existir.
     *   2. El docente no puede tener sesiones activas abiertas.
     *
     * @param int $idDocente Identificador del docente a eliminar.
     * @return array<string, mixed>
     * @throws \RuntimeException 404 si el docente no existe.
     * @throws \RuntimeException 409 si tiene sesiones activas.
     */
    public function eliminar(int $idDocente): array
    {
        $docente = $this->docenteRepo->obtenerPorId($idDocente);

        if ($docente === null) {
            throw new \RuntimeException('Docente no encontrado.', 404);
        }

        if ($this->sesionRepo->contarActivasPorDocente($idDocente) > 0) {
            throw new \RuntimeException('El docente tiene sesiones activas. Ciérrelas antes de eliminarlo.', 409);
        }

        $this->docenteRepo->eliminar($idDocente);

        return ['success' => true, 'message' => 'Docente eliminado correctamente.'];
    }

    // -------------------------------------------------------------------------
    // Métodos privados de apoyo
    // -------------------------------------------------------------------------

    /**
     * Valida el formato de un correo electrónico.
     *
     * @param string $correo Correo a validar.
     * @return bool
     */
    private function esCorreoValido(string $correo): bool
    {
        return filter_var($correo, FILTER_VALIDATE_EMAIL) !== false;
    }
}