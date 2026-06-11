<?php

declare(strict_types=1);

/**
 * AttendQR – FichaService
 *
 * Responsabilidad: contener la lógica de negocio relacionada con
 * las fichas de formación del SENA.
 * Una "ficha" es el grupo de aprendices asignado a un programa.
 *
 * Esta clase NO debe:
 *   - Ejecutar SQL directamente.
 *   - Conocer el router ni los Controllers.
 *   - Acceder a $_POST, $_GET ni $_REQUEST.
 *   - Imprimir JSON, HTML ni usar header() o exit.
 *
 * Flujo esperado:
 *   FichaController → FichaService → FichaRepository → Modelo → Database
 *
 * Ubicación en el proyecto: Src/Services/FichaService.php
 */
class FichaService
{
    // -------------------------------------------------------------------------
    // Dependencias (se inyectarán cuando existan los Repositories)
    // -------------------------------------------------------------------------

    // ► AQUÍ: declarar dependencias
    //
    // Ejemplo futuro:
    //   private FichaRepository   $fichaRepo;
    //   private JornadaRepository $jornadaRepo;
    //
    //   public function __construct(FichaRepository $fichaRepo, JornadaRepository $jornadaRepo)
    //   {
    //       $this->fichaRepo   = $fichaRepo;
    //       $this->jornadaRepo = $jornadaRepo;
    //   }

    // -------------------------------------------------------------------------
    // Métodos públicos
    // -------------------------------------------------------------------------

    /**
     * Obtiene los datos completos de una ficha por su ID.
     *
     * Reglas de negocio:
     *   1. Verificar que la ficha existe.
     *   2. Si no existe, lanzar excepción → 404 en el Controller.
     *   3. Retornar la ficha enriquecida con programa, jornada y conteo de aprendices.
     *
     * @param int $idFicha Identificador único de la ficha.
     * @return array<string, mixed>
     */
    public function consultar(int $idFicha): array
    {
        // ► AQUÍ: llamar a FichaRepository->obtenerPorId($idFicha)
        // ► AQUÍ: si no existe, lanzar new \RuntimeException('Ficha no encontrada.', 404)

        return [
            'success'  => true,
            'message'  => 'FichaService::consultar() disponible. Pendiente de implementación.',
            'id_ficha' => $idFicha,
        ];
    }

    /**
     * Lista fichas con filtros opcionales.
     *
     * Reglas de negocio:
     *   1. Aplicar filtros de programa, estado y jornada.
     *   2. Retornar listado paginado ordenado por número de ficha.
     *
     * @param int|null    $idPrograma Filtro opcional por programa.
     * @param string|null $estado     Filtro opcional por estado ('activa' | 'inactiva').
     * @param int|null    $idJornada  Filtro opcional por jornada.
     * @return array<string, mixed>
     */
    public function listar(?int $idPrograma = null, ?string $estado = null, ?int $idJornada = null): array
    {
        // ► AQUÍ: llamar a FichaRepository->listar($idPrograma, $estado, $idJornada)

        return [
            'success'         => true,
            'message'         => 'FichaService::listar() disponible. Pendiente de implementación.',
            'filtro_programa' => $idPrograma,
            'filtro_estado'   => $estado,
            'filtro_jornada'  => $idJornada,
        ];
    }

    /**
     * Crea una nueva ficha de formación.
     *
     * Reglas de negocio:
     *   1. Verificar que el número de ficha no está duplicado.
     *   2. Verificar que el programa y la jornada existen.
     *   3. Persistir la ficha con estado 'activa'.
     *   4. Retornar la ficha creada.
     *
     * @param string   $numeroFicha Número único de la ficha SENA.
     * @param int      $idPrograma  Identificador del programa de formación.
     * @param int|null $idJornada   Identificador opcional de la jornada.
     * @return array<string, mixed>
     */
    public function crear(string $numeroFicha, int $idPrograma, ?int $idJornada = null): array
    {
        $numeroFicha = trim($numeroFicha);

        if ($numeroFicha === '') {
            return ['success' => false, 'message' => 'El número de ficha no puede estar vacío.'];
        }

        // ► AQUÍ: llamar a FichaRepository->existeNumero($numeroFicha)
        // ► AQUÍ: si existe, lanzar new \RuntimeException('El número de ficha ya está registrado.', 409)
        // ► AQUÍ: llamar a FichaRepository->crear($numeroFicha, $idPrograma, $idJornada)

        return [
            'success'      => true,
            'message'      => 'FichaService::crear() disponible. Pendiente de implementación.',
            'numero_ficha' => $numeroFicha,
            'id_programa'  => $idPrograma,
            'id_jornada'   => $idJornada,
        ];
    }

    /**
     * Actualiza los datos de una ficha existente (actualización parcial).
     *
     * Reglas de negocio:
     *   1. Verificar que la ficha existe.
     *   2. Si se cambia el número, verificar que no esté duplicado.
     *   3. Aplicar solo los campos enviados, conservar el resto.
     *
     * @param int                  $idFicha Identificador único de la ficha.
     * @param array<string, mixed> $datos   Campos a actualizar.
     * @return array<string, mixed>
     */
    public function actualizar(int $idFicha, array $datos): array
    {
        if (empty($datos)) {
            return ['success' => false, 'message' => 'No se recibieron datos para actualizar.'];
        }

        // ► AQUÍ: llamar a FichaRepository->obtenerPorId($idFicha)
        // ► AQUÍ: si no existe, lanzar new \RuntimeException('Ficha no encontrada.', 404)
        // ► AQUÍ: si viene numero_ficha, verificar que no esté duplicado
        // ► AQUÍ: llamar a FichaRepository->actualizar($idFicha, $datos)

        return [
            'success'  => true,
            'message'  => 'FichaService::actualizar() disponible. Pendiente de implementación.',
            'id_ficha' => $idFicha,
            'datos'    => $datos,
        ];
    }

    /**
     * Elimina una ficha del sistema.
     *
     * Reglas de negocio:
     *   1. Verificar que la ficha existe.
     *   2. Verificar que no tiene aprendices activos vinculados.
     *   3. Verificar que no tiene sesiones activas abiertas.
     *   4. Proceder con la eliminación.
     *
     * @param int $idFicha Identificador único de la ficha a eliminar.
     * @return array<string, mixed>
     */
    public function eliminar(int $idFicha): array
    {
        // ► AQUÍ: llamar a FichaRepository->obtenerPorId($idFicha)
        // ► AQUÍ: llamar a AprendizRepository->contarActivosPorFicha($idFicha)
        // ► AQUÍ: si tiene aprendices activos, lanzar excepción 409
        // ► AQUÍ: llamar a FichaRepository->eliminar($idFicha)

        return [
            'success'  => true,
            'message'  => 'FichaService::eliminar() disponible. Pendiente de implementación.',
            'id_ficha' => $idFicha,
        ];
    }
}