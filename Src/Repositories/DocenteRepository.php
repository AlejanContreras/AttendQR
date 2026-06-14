<?php

declare(strict_types=1);

/**
 * AttendQR – DocenteRepository
 *
 * Responsabilidad: acceder a la tabla `docentes` para todas las
 * operaciones CRUD y consultas de existencia relacionadas.
 *
 * Esta clase NO debe:
 *   - Hashear contraseñas (eso lo hace DocenteService).
 *   - Validar reglas de negocio.
 *   - Conocer Controllers ni Services.
 *   - Generar HTML, JSON ni usar header() o exit.
 *
 * Flujo: DocenteService → DocenteRepository → Database → MySQL (tabla: docentes)
 *
 * Ubicación en el proyecto: Src/Repositories/DocenteRepository.php
 */
class DocenteRepository extends BaseRepository
{
    // -------------------------------------------------------------------------
    // Consultas de lectura
    // -------------------------------------------------------------------------

    /**
     * Busca un docente por su ID. No incluye la contraseña hasheada.
     *
     * @param int $idDocente Identificador único del docente.
     * @return array<string, mixed>|null Datos del docente o null.
     */
    public function obtenerPorId(int $idDocente): ?array
    {
        // ► AQUÍ: implementar
        //
        // return $this->consultarUno(
        //     'SELECT id, documento, nombres, apellidos, correo, especialidad, estado, created_at
        //      FROM docentes WHERE id = :id',
        //     [':id' => $idDocente]
        // );

        return null;
    }

    /**
     * Busca un docente por correo incluyendo la contraseña hasheada.
     * Solo se usa desde AuthService para autenticar.
     *
     * @param string $correo Correo electrónico del docente.
     * @return array<string, mixed>|null Datos completos o null.
     */
    public function buscarPorCorreo(string $correo): ?array
    {
        // ► AQUÍ: implementar
        //
        // return $this->consultarUno(
        //     'SELECT id, correo, contrasena_hash, rol, estado
        //      FROM docentes WHERE correo = :correo LIMIT 1',
        //     [':correo' => $correo]
        // );

        return null;
    }

    /**
     * Lista docentes con filtros opcionales de estado y especialidad.
     *
     * @param string|null $estado       Filtro por estado.
     * @param string|null $especialidad Filtro por especialidad.
     * @return array<int, array<string, mixed>>
     */
    public function listar(?string $estado = null, ?string $especialidad = null): array
    {
        // ► AQUÍ: construir consulta dinámica
        //
        // $sql = 'SELECT id, documento, nombres, apellidos, correo, especialidad, estado FROM docentes WHERE 1=1';
        // $params = [];
        // if ($estado       !== null) { $sql .= ' AND estado       = :estado';       $params[':estado']       = $estado; }
        // if ($especialidad !== null) { $sql .= ' AND especialidad = :especialidad'; $params[':especialidad'] = $especialidad; }
        // $sql .= ' ORDER BY apellidos, nombres';
        // return $this->consultar($sql, $params);

        return [];
    }

    /**
     * Verifica si ya existe un docente con el documento indicado.
     *
     * @param string $documento Número de documento.
     * @return bool true si existe.
     */
    public function existeDocumento(string $documento): bool
    {
        // ► AQUÍ: implementar
        //
        // return $this->existe(
        //     'SELECT COUNT(*) FROM docentes WHERE documento = :doc',
        //     [':doc' => $documento]
        // );

        return false;
    }

    /**
     * Verifica si ya existe un docente con el correo indicado.
     *
     * @param string   $correo    Correo a verificar.
     * @param int|null $excluirId ID a excluir (para actualizaciones).
     * @return bool true si existe.
     */
    public function existeCorreo(string $correo, ?int $excluirId = null): bool
    {
        // ► AQUÍ: implementar
        //
        // $sql    = 'SELECT COUNT(*) FROM docentes WHERE correo = :correo';
        // $params = [':correo' => $correo];
        // if ($excluirId !== null) { $sql .= ' AND id != :excluir'; $params[':excluir'] = $excluirId; }
        // return $this->existe($sql, $params);

        return false;
    }

    /**
     * Cuenta docentes activos. Usado por EstadisticaService::resumen().
     *
     * @return int Total de docentes activos.
     */
    public function contarActivos(): int
    {
        // ► AQUÍ: implementar
        //
        // return $this->contar("SELECT COUNT(*) FROM docentes WHERE estado = 'activo'");

        return 0;
    }

    // -------------------------------------------------------------------------
    // Consultas de escritura
    // -------------------------------------------------------------------------

    /**
     * Inserta un nuevo docente. Recibe la contraseña ya hasheada.
     *
     * @param string      $documento      Número de documento.
     * @param string      $nombres        Nombres.
     * @param string      $apellidos      Apellidos.
     * @param string      $correo         Correo electrónico.
     * @param string      $contrasenaHash Contraseña ya procesada por password_hash().
     * @param string|null $especialidad   Especialidad o área de formación.
     * @return int ID del docente creado.
     */
    public function crear(
        string  $documento,
        string  $nombres,
        string  $apellidos,
        string  $correo,
        string  $contrasenaHash,
        ?string $especialidad = null
    ): int {
        // ► AQUÍ: implementar
        //
        // return $this->insertar(
        //     'INSERT INTO docentes (documento, nombres, apellidos, correo, contrasena_hash, especialidad, estado)
        //      VALUES (:doc, :nombres, :apellidos, :correo, :hash, :especialidad, "activo")',
        //     [':doc' => $documento, ':nombres' => $nombres, ':apellidos' => $apellidos,
        //      ':correo' => $correo, ':hash' => $contrasenaHash, ':especialidad' => $especialidad]
        // );

        return 0;
    }

    /**
     * Actualiza los campos enviados de un docente existente.
     *
     * @param int                  $idDocente Identificador del docente.
     * @param array<string, mixed> $datos     Campos a actualizar.
     * @return int Número de filas afectadas.
     */
    public function actualizar(int $idDocente, array $datos): int
    {
        // ► AQUÍ: construir el SET dinámico
        //
        // $camposPermitidos = ['nombres', 'apellidos', 'correo', 'especialidad', 'estado', 'contrasena_hash'];
        // $set = []; $params = [':id' => $idDocente];
        // foreach ($camposPermitidos as $campo) {
        //     if (array_key_exists($campo, $datos)) {
        //         $set[] = "{$campo} = :{$campo}";
        //         $params[":{$campo}"] = $datos[$campo];
        //     }
        // }
        // if (empty($set)) { return 0; }
        // return $this->ejecutar("UPDATE docentes SET " . implode(', ', $set) . " WHERE id = :id", $params);

        return 0;
    }

    /**
     * Elimina un docente por su ID.
     *
     * @param int $idDocente Identificador del docente.
     * @return int Número de filas afectadas.
     */
    public function eliminar(int $idDocente): int
    {
        // ► AQUÍ: implementar
        //
        // return $this->ejecutar('DELETE FROM docentes WHERE id = :id', [':id' => $idDocente]);

        return 0;
    }
}