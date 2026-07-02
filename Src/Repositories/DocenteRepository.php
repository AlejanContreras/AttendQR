<?php

declare(strict_types=1);

/**
 * AttendQR – DocenteRepository
 *
 * Tabla: docentes
 * Columnas reales: id_docente, nombres, apellidos, correo, password_hash,
 *                  activo (TINYINT 1=activo), creado_en
 *
 * NO contiene lógica de negocio.
 * Flujo: DocenteService → DocenteRepository → BaseRepository → Database → MySQL
 *
 * Ubicación en el proyecto: Src/Repositories/DocenteRepository.php
 */
class DocenteRepository extends BaseRepository
{
    /**
     * Busca un docente por su ID. NO incluye password_hash en la respuesta.
     *
     * @param int $idDocente Identificador del docente.
     * @return array<string, mixed>|null Datos del docente o null.
     */
    public function obtenerPorId(int $idDocente): ?array
    {
        return $this->consultarUno(
            'SELECT id_docente, nombres, apellidos, correo, activo, creado_en
             FROM docentes
             WHERE id_docente = :id',
            [':id' => $idDocente]
        );
    }

    /**
     * Busca un docente por correo incluyendo password_hash. Usado en el módulo de Auth.
     *
     * @param string $correo Correo del docente.
     * @return array<string, mixed>|null Datos completos incluyendo hash.
     */
    public function buscarPorCorreo(string $correo): ?array
    {
        return $this->consultarUno(
            'SELECT id_docente, nombres, apellidos, correo, password_hash, activo
             FROM docentes
             WHERE correo = :correo',
            [':correo' => $correo]
        );
    }

    /**
     * Lista docentes con filtro opcional de estado.
     *
     * @param int|null $activo Filtro por estado (1 = activo, 0 = inactivo).
     * @return array<int, array<string, mixed>>
     */
    public function listar(?int $activo = null): array
    {
        $sql    = 'SELECT id_docente, nombres, apellidos, correo, activo, creado_en
                   FROM docentes
                   WHERE 1=1';
        $params = [];

        if ($activo !== null) {
            $sql .= ' AND activo = :activo';
            $params[':activo'] = $activo;
        }

        $sql .= ' ORDER BY apellidos, nombres';

        return $this->consultar($sql, $params);
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
        $sql    = 'SELECT COUNT(*) FROM docentes WHERE correo = :correo';
        $params = [':correo' => $correo];

        if ($excluirId !== null) {
            $sql              .= ' AND id_docente != :excluir';
            $params[':excluir'] = $excluirId;
        }

        return $this->existe($sql, $params);
    }

    /**
     * Cuenta los docentes activos. Usado por EstadisticaService.
     *
     * @return int Total de docentes activos.
     */
    public function contarActivos(): int
    {
        return $this->contar('SELECT COUNT(*) FROM docentes WHERE activo = 1');
    }

    /**
     * Inserta un nuevo docente. Recibe la contraseña ya hasheada.
     *
     * @param string $nombres      Nombres.
     * @param string $apellidos    Apellidos.
     * @param string $correo       Correo único.
     * @param string $passwordHash Hash generado por password_hash().
     * @return int ID del docente creado.
     */
    public function crear(
        string $nombres,
        string $apellidos,
        string $correo,
        string $passwordHash
    ): int {
        return $this->insertar(
            'INSERT INTO docentes (nombres, apellidos, correo, password_hash, activo)
             VALUES (:nombres, :apellidos, :correo, :hash, 1)',
            [
                ':nombres'   => $nombres,
                ':apellidos' => $apellidos,
                ':correo'    => $correo,
                ':hash'      => $passwordHash,
            ]
        );
    }

    /**
     * Actualiza únicamente los campos enviados de un docente existente.
     *
     * @param int                  $idDocente Identificador del docente.
     * @param array<string, mixed> $datos     Campos a actualizar.
     * @return int Filas afectadas.
     */
    public function actualizar(int $idDocente, array $datos): int
    {
        $camposPermitidos = ['nombres', 'apellidos', 'correo', 'password_hash', 'activo'];
        $set    = [];
        $params = [':id' => $idDocente];

        foreach ($camposPermitidos as $campo) {
            if (array_key_exists($campo, $datos)) {
                $set[]              = "{$campo} = :{$campo}";
                $params[":{$campo}"] = $datos[$campo];
            }
        }

        if (empty($set)) {
            return 0;
        }

        return $this->ejecutar(
            'UPDATE docentes SET ' . implode(', ', $set) . ' WHERE id_docente = :id',
            $params
        );
    }

    /**
     * Elimina un docente por su ID.
     *
     * @param int $idDocente Identificador del docente.
     * @return int Filas afectadas.
     */
    public function eliminar(int $idDocente): int
    {
        return $this->ejecutar(
            'DELETE FROM docentes WHERE id_docente = :id',
            [':id' => $idDocente]
        );
    }
}
