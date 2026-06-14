<?php

declare(strict_types=1);

/**
 * AttendQR – AprendizRepository
 *
 * Responsabilidad: acceder a la tabla `aprendices` para todas las
 * operaciones CRUD y consultas de existencia relacionadas.
 *
 * Esta clase NO debe:
 *   - Validar reglas de negocio (unicidad de correo, estado de la ficha, etc.).
 *   - Conocer Controllers ni Services.
 *   - Generar HTML, JSON ni usar header() o exit.
 *
 * Flujo: AprendizService → AprendizRepository → Database → MySQL (tabla: aprendices)
 *
 * Ubicación en el proyecto: Src/Repositories/AprendizRepository.php
 */
class AprendizRepository extends BaseRepository
{
    // -------------------------------------------------------------------------
    // Consultas de lectura
    // -------------------------------------------------------------------------

    /**
     * Busca un aprendiz por su ID.
     *
     * @param int $idAprendiz Identificador único del aprendiz.
     * @return array<string, mixed>|null Datos del aprendiz o null.
     */
    public function obtenerPorId(int $idAprendiz): ?array
    {
        // ► AQUÍ: implementar
        //
        // return $this->consultarUno(
        //     'SELECT a.*, f.numero_ficha
        //      FROM aprendices a
        //      JOIN fichas f ON f.id = a.id_ficha
        //      WHERE a.id = :id',
        //     [':id' => $idAprendiz]
        // );

        return null;
    }

    /**
     * Retorna el listado de aprendices con filtros opcionales.
     *
     * @param int|null    $idFicha   Filtro por ficha.
     * @param string|null $estado    Filtro por estado ('activo' | 'inactivo').
     * @param string|null $documento Filtro por número de documento.
     * @return array<int, array<string, mixed>>
     */
    public function listar(?int $idFicha = null, ?string $estado = null, ?string $documento = null): array
    {
        // ► AQUÍ: construir consulta dinámica según filtros
        //
        // $sql    = 'SELECT id, documento, nombres, apellidos, correo, estado, id_ficha FROM aprendices WHERE 1=1';
        // $params = [];
        // if ($idFicha    !== null) { $sql .= ' AND id_ficha  = :id_ficha';   $params[':id_ficha']   = $idFicha; }
        // if ($estado     !== null) { $sql .= ' AND estado    = :estado';     $params[':estado']     = $estado; }
        // if ($documento  !== null) { $sql .= ' AND documento = :documento';  $params[':documento']  = $documento; }
        // $sql .= ' ORDER BY apellidos, nombres';
        // return $this->consultar($sql, $params);

        return [];
    }

    /**
     * Verifica si ya existe un aprendiz con el documento indicado.
     *
     * @param string $documento Número de documento a verificar.
     * @return bool true si existe.
     */
    public function existeDocumento(string $documento): bool
    {
        // ► AQUÍ: implementar
        //
        // return $this->existe(
        //     'SELECT COUNT(*) FROM aprendices WHERE documento = :doc',
        //     [':doc' => $documento]
        // );

        return false;
    }

    /**
     * Verifica si ya existe un aprendiz con el correo indicado.
     * Excluye opcionalmente un ID para la validación al actualizar.
     *
     * @param string   $correo    Correo a verificar.
     * @param int|null $excluirId ID a excluir de la búsqueda (para actualizaciones).
     * @return bool true si existe.
     */
    public function existeCorreo(string $correo, ?int $excluirId = null): bool
    {
        // ► AQUÍ: implementar
        //
        // $sql    = 'SELECT COUNT(*) FROM aprendices WHERE correo = :correo';
        // $params = [':correo' => $correo];
        // if ($excluirId !== null) { $sql .= ' AND id != :excluir'; $params[':excluir'] = $excluirId; }
        // return $this->existe($sql, $params);

        return false;
    }

    /**
     * Cuenta los aprendices activos vinculados a una ficha.
     * Usado por FichaService antes de eliminar una ficha.
     *
     * @param int $idFicha Identificador de la ficha.
     * @return int Total de aprendices activos.
     */
    public function contarActivosPorFicha(int $idFicha): int
    {
        // ► AQUÍ: implementar
        //
        // return $this->contar(
        //     "SELECT COUNT(*) FROM aprendices WHERE id_ficha = :id AND estado = 'activo'",
        //     [':id' => $idFicha]
        // );

        return 0;
    }

    // -------------------------------------------------------------------------
    // Consultas de escritura
    // -------------------------------------------------------------------------

    /**
     * Inserta un nuevo aprendiz en la base de datos.
     *
     * @param string $documento Número de documento.
     * @param string $nombres   Nombres del aprendiz.
     * @param string $apellidos Apellidos del aprendiz.
     * @param string $correo    Correo electrónico.
     * @param int    $idFicha   Ficha a la que pertenece.
     * @return int ID del aprendiz creado.
     */
    public function crear(string $documento, string $nombres, string $apellidos, string $correo, int $idFicha): int
    {
        // ► AQUÍ: implementar
        //
        // return $this->insertar(
        //     'INSERT INTO aprendices (documento, nombres, apellidos, correo, id_ficha, estado)
        //      VALUES (:doc, :nombres, :apellidos, :correo, :id_ficha, "activo")',
        //     [':doc' => $documento, ':nombres' => $nombres, ':apellidos' => $apellidos,
        //      ':correo' => $correo, ':id_ficha' => $idFicha]
        // );

        return 0;
    }

    /**
     * Actualiza los campos enviados de un aprendiz existente.
     *
     * @param int                  $idAprendiz Identificador del aprendiz.
     * @param array<string, mixed> $datos      Campos a actualizar.
     * @return int Número de filas afectadas.
     */
    public function actualizar(int $idAprendiz, array $datos): int
    {
        // ► AQUÍ: construir el SET dinámico con solo los campos enviados
        //
        // $camposPermitidos = ['nombres', 'apellidos', 'correo', 'id_ficha', 'estado'];
        // $set = []; $params = [':id' => $idAprendiz];
        // foreach ($camposPermitidos as $campo) {
        //     if (array_key_exists($campo, $datos)) {
        //         $set[] = "{$campo} = :{$campo}";
        //         $params[":{$campo}"] = $datos[$campo];
        //     }
        // }
        // if (empty($set)) { return 0; }
        // return $this->ejecutar("UPDATE aprendices SET " . implode(', ', $set) . " WHERE id = :id", $params);

        return 0;
    }

    /**
     * Elimina un aprendiz por su ID.
     *
     * @param int $idAprendiz Identificador del aprendiz.
     * @return int Número de filas afectadas.
     */
    public function eliminar(int $idAprendiz): int
    {
        // ► AQUÍ: implementar
        //
        // return $this->ejecutar(
        //     'DELETE FROM aprendices WHERE id = :id',
        //     [':id' => $idAprendiz]
        // );

        return 0;
    }
}