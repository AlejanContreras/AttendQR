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
     * Lista fichas con filtros opcionales de programa, estado y jornada.
     *
     * @param int|null    $idPrograma Filtro por programa.
     * @param string|null $estado     Filtro por estado ('activa' | 'inactiva').
     * @param int|null    $idJornada  Filtro por jornada.
     * @return array<string, mixed>
     */
    public function listar(?int $idPrograma = null, ?string $estado = null, ?int $idJornada = null): array
    {
        $fichas = $this->fichaRepo->listar($idPrograma, $estado, $idJornada);

        return [
            'fichas' => $fichas,
            'total'  => count($fichas),
        ];
    }

    /**
     * Crea una nueva ficha de formación.
     *
     * Reglas de negocio:
     *   1. El número de ficha no puede estar duplicado.
     *
     * @param string   $numeroFicha Número único de la ficha SENA.
     * @param int      $idPrograma  Identificador del programa de formación.
     * @param int|null $idJornada   Identificador opcional de la jornada.
     * @return array<string, mixed> Datos de la ficha creada.
     * @throws \RuntimeException 422 si el número de ficha está vacío.
     * @throws \RuntimeException 409 si el número de ficha ya existe.
     */
    public function crear(string $numeroFicha, int $idPrograma, ?int $idJornada = null): array
    {
        $numeroFicha = trim($numeroFicha);

        if ($numeroFicha === '') {
            throw new \RuntimeException('El número de ficha no puede estar vacío.', 422);
        }

        if ($this->fichaRepo->existeNumero($numeroFicha)) {
            throw new \RuntimeException('El número de ficha ya está registrado en el sistema.', 409);
        }

        $id    = $this->fichaRepo->crear($numeroFicha, $idPrograma, $idJornada);
        $ficha = $this->fichaRepo->obtenerPorId($id);

        return $ficha ?? [
            'id'           => $id,
            'numero_ficha' => $numeroFicha,
            'id_programa'  => $idPrograma,
            'id_jornada'   => $idJornada,
            'estado'       => 'activa',
        ];
    }

    /**
     * Actualiza los datos de una ficha existente (actualización parcial).
     *
     * Reglas de negocio:
     *   1. La ficha debe existir.
     *   2. Si se cambia el número, no puede estar duplicado.
     *
     * @param int                  $idFicha Identificador de la ficha.
     * @param array<string, mixed> $datos   Campos a actualizar.
     * @return array<string, mixed> Datos actualizados de la ficha.
     * @throws \RuntimeException 404 si la ficha no existe.
     * @throws \RuntimeException 409 si el nuevo número ya está en uso.
     */
    public function actualizar(int $idFicha, array $datos): array
    {
        $ficha = $this->fichaRepo->obtenerPorId($idFicha);

        if ($ficha === null) {
            throw new \RuntimeException('Ficha no encontrada.', 404);
        }

        if (isset($datos['numero_ficha'])) {
            $datos['numero_ficha'] = trim((string) $datos['numero_ficha']);

            if ($this->fichaRepo->existeNumero($datos['numero_ficha'], $idFicha)) {
                throw new \RuntimeException('El número de ficha ya está en uso por otra ficha.', 409);
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