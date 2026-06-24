<?php

declare(strict_types=1);

/**
 * AttendQR – AprendizService
 *
 * Responsabilidad: lógica de negocio del módulo de aprendices.
 * Flujo: AprendizController → AprendizService → AprendizRepository / FichaRepository → Database
 *
 * Ubicación en el proyecto: Src/Services/AprendizService.php
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

    /**
     * Obtiene los datos completos de un aprendiz por su ID.
     *
     * @param int $idAprendiz Identificador del aprendiz.
     * @return array<string, mixed>
     * @throws \RuntimeException 404 si el aprendiz no existe.
     */
    public function consultar(int $idAprendiz): array
    {
        $aprendiz = $this->aprendizRepo->obtenerPorId($idAprendiz);

        if ($aprendiz === null) {
            throw new \RuntimeException('Aprendiz no encontrado.', 404);
        }

        return $aprendiz;
    }

    /**
     * Lista aprendices con filtros opcionales de ficha, estado y documento.
     *
     * @param int|null    $idFicha   Filtro por ficha.
     * @param string|null $estado    Filtro por estado ('activo' | 'inactivo').
     * @param string|null $documento Filtro por número de documento.
     * @return array<string, mixed>
     */
    public function listar(?int $idFicha = null, ?string $estado = null, ?string $documento = null): array
    {
        $aprendices = $this->aprendizRepo->listar($idFicha, $estado, $documento);

        return [
            'aprendices' => $aprendices,
            'total'      => count($aprendices),
        ];
    }

    /**
     * Registra un nuevo aprendiz y lo asocia a una ficha de formación.
     *
     * Reglas de negocio:
     *   1. El correo debe tener formato válido.
     *   2. El documento no puede estar duplicado.
     *   3. El correo no puede estar en uso.
     *   4. La ficha debe existir y estar activa.
     *
     * @param string $documento Número de documento de identidad.
     * @param string $nombres   Nombres del aprendiz.
     * @param string $apellidos Apellidos del aprendiz.
     * @param string $correo    Correo electrónico institucional.
     * @param int    $idFicha   Ficha a la que se vincula.
     * @return array<string, mixed> Datos del aprendiz creado.
     * @throws \RuntimeException 422 si el correo no es válido.
     * @throws \RuntimeException 409 si el documento ya está registrado.
     * @throws \RuntimeException 409 si el correo ya está en uso.
     * @throws \RuntimeException 404 si la ficha no existe.
     * @throws \RuntimeException 422 si la ficha no está activa.
     */
    public function registrar(string $documento, string $nombres, string $apellidos, string $correo, int $idFicha): array
    {
        $documento = trim($documento);
        $nombres   = trim($nombres);
        $apellidos = trim($apellidos);
        $correo    = strtolower(trim($correo));

        if (!$this->esCorreoValido($correo)) {
            throw new \RuntimeException("El correo '{$correo}' no tiene un formato válido.", 422);
        }

        if ($this->aprendizRepo->existeDocumento($documento)) {
            throw new \RuntimeException('El documento ya está registrado en el sistema.', 409);
        }

        if ($this->aprendizRepo->existeCorreo($correo)) {
            throw new \RuntimeException('El correo ya está en uso por otro aprendiz.', 409);
        }

        $ficha = $this->fichaRepo->obtenerPorId($idFicha);

        if ($ficha === null) {
            throw new \RuntimeException('Ficha no encontrada.', 404);
        }

        if ((string) $ficha['estado'] !== 'activa') {
            throw new \RuntimeException('La ficha no está activa.', 422);
        }

        $id       = $this->aprendizRepo->crear($documento, $nombres, $apellidos, $correo, $idFicha);
        $aprendiz = $this->aprendizRepo->obtenerPorId($id);

        return $aprendiz ?? [
            'id'        => $id,
            'documento' => $documento,
            'nombres'   => $nombres,
            'apellidos' => $apellidos,
            'correo'    => $correo,
            'id_ficha'  => $idFicha,
            'estado'    => 'activo',
        ];
    }

    /**
     * Actualiza los datos de un aprendiz existente (actualización parcial).
     *
     * Reglas de negocio:
     *   1. El aprendiz debe existir.
     *   2. Si se actualiza el correo, debe ser válido y no estar en uso por otro aprendiz.
     *
     * @param int                  $idAprendiz Identificador del aprendiz.
     * @param array<string, mixed> $datos      Campos a actualizar.
     * @return array<string, mixed> Datos actualizados del aprendiz.
     * @throws \RuntimeException 404 si el aprendiz no existe.
     * @throws \RuntimeException 422 si el correo enviado no es válido.
     * @throws \RuntimeException 409 si el correo ya está en uso por otro aprendiz.
     */
    public function actualizar(int $idAprendiz, array $datos): array
    {
        $aprendiz = $this->aprendizRepo->obtenerPorId($idAprendiz);

        if ($aprendiz === null) {
            throw new \RuntimeException('Aprendiz no encontrado.', 404);
        }

        if (isset($datos['correo'])) {
            $datos['correo'] = strtolower(trim((string) $datos['correo']));

            if (!$this->esCorreoValido($datos['correo'])) {
                throw new \RuntimeException("El correo '{$datos['correo']}' no tiene un formato válido.", 422);
            }

            if ($this->aprendizRepo->existeCorreo($datos['correo'], $idAprendiz)) {
                throw new \RuntimeException('El correo ya está en uso por otro aprendiz.', 409);
            }
        }

        $this->aprendizRepo->actualizar($idAprendiz, $datos);

        return $this->aprendizRepo->obtenerPorId($idAprendiz) ?? $aprendiz;
    }

    /**
     * Elimina un aprendiz del sistema.
     *
     * Reglas de negocio:
     *   1. El aprendiz debe existir.
     *
     * @param int $idAprendiz Identificador del aprendiz a eliminar.
     * @return array<string, mixed>
     * @throws \RuntimeException 404 si el aprendiz no existe.
     */
    public function eliminar(int $idAprendiz): array
    {
        $aprendiz = $this->aprendizRepo->obtenerPorId($idAprendiz);

        if ($aprendiz === null) {
            throw new \RuntimeException('Aprendiz no encontrado.', 404);
        }

        $this->aprendizRepo->eliminar($idAprendiz);

        return ['success' => true, 'message' => 'Aprendiz eliminado correctamente.'];
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