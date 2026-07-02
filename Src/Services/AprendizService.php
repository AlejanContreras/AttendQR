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
        $activo = match ($estado) {
            'activo'   => 1,
            'inactivo' => 0,
            default    => null,
        };

        $aprendices = $this->aprendizRepo->listar($idFicha, $activo, $documento);

        return [
            'aprendices' => $aprendices,
            'total'      => count($aprendices),
        ];
    }

    /**
     * Registra un nuevo aprendiz y lo asocia a una ficha de formación.
     *
     * Reglas de negocio:
     *   1. El documento no puede estar duplicado.
     *   2. La ficha debe existir y estar activa.
     *   3. La contraseña se hashea con BCRYPT antes de persistir.
     *
     * @param string $numeroDocumento Número de documento de identidad.
     * @param string $nombres         Nombres del aprendiz.
     * @param string $apellidos       Apellidos del aprendiz.
     * @param string $password        Contraseña en texto plano.
     * @param int    $idFicha         Ficha a la que se vincula.
     * @return array<string, mixed> Datos del aprendiz creado.
     * @throws \RuntimeException 409 si el documento ya está registrado.
     * @throws \RuntimeException 404 si la ficha no existe.
     * @throws \RuntimeException 422 si la ficha no está activa.
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

        $id       = $this->aprendizRepo->crear($numeroDocumento, $nombres, $apellidos, $passwordHash, $idFicha);
        $aprendiz = $this->aprendizRepo->obtenerPorId($id);

        return $aprendiz ?? [
            'id_aprendiz'     => $id,
            'numero_documento' => $numeroDocumento,
            'nombres'         => $nombres,
            'apellidos'       => $apellidos,
            'id_ficha'        => $idFicha,
            'activo'          => 1,
        ];
    }

    /**
     * Actualiza los datos de un aprendiz existente (actualización parcial).
     *
     * Reglas de negocio:
     *   1. El aprendiz debe existir.
     *   2. Si se actualiza la contraseña, se re-hashea antes de persistir.
     *
     * @param int                  $idAprendiz Identificador del aprendiz.
     * @param array<string, mixed> $datos      Campos a actualizar.
     * @return array<string, mixed> Datos actualizados del aprendiz.
     * @throws \RuntimeException 404 si el aprendiz no existe.
     */
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
}
