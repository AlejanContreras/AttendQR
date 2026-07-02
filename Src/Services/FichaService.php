<?php

declare(strict_types=1);

/**
 * AttendQR – FichaService
 *
 * Responsabilidad: lógica de negocio del módulo de fichas de formación.
 * Flujo: FichaController → FichaService → FichaRepository / AprendizRepository → Database
 *
 * Ubicación en el proyecto: Src/Services/FichaService.php
 */
class FichaService
{
    private FichaRepository    $fichaRepo;
    private AprendizRepository $aprendizRepo;

    public function __construct()
    {
        $this->fichaRepo    = new FichaRepository();
        $this->aprendizRepo = new AprendizRepository();
    }

    /**
     * Obtiene los datos completos de una ficha por su ID.
     *
     * @param int $idFicha Identificador de la ficha.
     * @return array<string, mixed>
     * @throws \RuntimeException 404 si la ficha no existe.
     */
    public function consultar(int $idFicha): array
    {
        $ficha = $this->fichaRepo->obtenerPorId($idFicha);

        if ($ficha === null) {
            throw new \RuntimeException('Ficha no encontrada.', 404);
        }

        return $ficha;
    }

    /**
     * Lista fichas con filtros opcionales.
     *
     * @param string|null $nombrePrograma Filtro parcial por nombre del programa.
     * @param string|null $estado         Filtro por estado ('activa' | 'inactiva').
     * @param int|null    $idJornada      Filtro por jornada.
     * @param int|null    $idDocente      Filtro por docente.
     * @return array<string, mixed>
     */
    public function listar(
        ?string $nombrePrograma = null,
        ?string $estado         = null,
        ?int    $idJornada      = null,
        ?int    $idDocente      = null
    ): array {
        $activa = match ($estado) {
            'activa'   => 1,
            'inactiva' => 0,
            default    => null,
        };

        $fichas = $this->fichaRepo->listar($nombrePrograma, $activa, $idJornada, $idDocente);

        return [
            'fichas' => $fichas,
            'total'  => count($fichas),
        ];
    }

    /**
     * Crea una nueva ficha de formación.
     * id_jornada, id_docente e id_trimestre son NOT NULL en el schema y requeridos aquí.
     *
     * @param string $codigoFicha    Código único de la ficha SENA.
     * @param string $nombrePrograma Nombre del programa de formación.
     * @param int    $idJornada      Identificador de la jornada.
     * @param int    $idDocente      Identificador del docente responsable.
     * @param int    $idTrimestre    Identificador del trimestre.
     * @return array<string, mixed> Datos de la ficha creada.
     * @throws \RuntimeException 422 si el código está vacío.
     * @throws \RuntimeException 409 si el código ya existe.
     */
    public function crear(
        string $codigoFicha,
        string $nombrePrograma,
        int    $idJornada,
        int    $idDocente,
        int    $idTrimestre
    ): array {
        $codigoFicha    = trim($codigoFicha);
        $nombrePrograma = trim($nombrePrograma);

        if ($codigoFicha === '') {
            throw new \RuntimeException('El código de ficha no puede estar vacío.', 422);
        }

        if ($this->fichaRepo->existeCodigo($codigoFicha)) {
            throw new \RuntimeException('El código de ficha ya está registrado en el sistema.', 409);
        }

        $id    = $this->fichaRepo->crear($codigoFicha, $nombrePrograma, $idJornada, $idDocente, $idTrimestre);
        $ficha = $this->fichaRepo->obtenerPorId($id);

        return $ficha ?? [
            'id_ficha'        => $id,
            'codigo_ficha'    => $codigoFicha,
            'nombre_programa' => $nombrePrograma,
            'id_jornada'      => $idJornada,
            'id_docente'      => $idDocente,
            'id_trimestre'    => $idTrimestre,
            'activa'          => 1,
        ];
    }

    /**
     * Actualiza los datos de una ficha existente (actualización parcial).
     *
     * Reglas de negocio:
     *   1. La ficha debe existir.
     *   2. Si se cambia el código, no puede estar duplicado.
     *
     * @param int                  $idFicha Identificador de la ficha.
     * @param array<string, mixed> $datos   Campos a actualizar.
     * @return array<string, mixed> Datos actualizados de la ficha.
     * @throws \RuntimeException 404 si la ficha no existe.
     * @throws \RuntimeException 409 si el nuevo código ya está en uso.
     */
    public function actualizar(int $idFicha, array $datos): array
    {
        $ficha = $this->fichaRepo->obtenerPorId($idFicha);

        if ($ficha === null) {
            throw new \RuntimeException('Ficha no encontrada.', 404);
        }

        if (isset($datos['codigo_ficha'])) {
            $datos['codigo_ficha'] = trim((string) $datos['codigo_ficha']);

            if ($this->fichaRepo->existeCodigo($datos['codigo_ficha'], $idFicha)) {
                throw new \RuntimeException('El código de ficha ya está en uso por otra ficha.', 409);
            }
        }

        $this->fichaRepo->actualizar($idFicha, $datos);

        return $this->fichaRepo->obtenerPorId($idFicha) ?? $ficha;
    }

    /**
     * Elimina una ficha del sistema.
     *
     * Reglas de negocio:
     *   1. La ficha debe existir.
     *   2. No puede tener aprendices activos vinculados.
     *
     * @param int $idFicha Identificador de la ficha a eliminar.
     * @return array<string, mixed>
     * @throws \RuntimeException 404 si la ficha no existe.
     * @throws \RuntimeException 409 si tiene aprendices activos.
     */
    public function eliminar(int $idFicha): array
    {
        $ficha = $this->fichaRepo->obtenerPorId($idFicha);

        if ($ficha === null) {
            throw new \RuntimeException('Ficha no encontrada.', 404);
        }

        if ($this->aprendizRepo->contarActivosPorFicha($idFicha) > 0) {
            throw new \RuntimeException('La ficha tiene aprendices activos. Desvincúlelos antes de eliminarla.', 409);
        }

        $this->fichaRepo->eliminar($idFicha);

        return ['success' => true, 'message' => 'Ficha eliminada correctamente.'];
    }
}
