<?php

declare(strict_types=1);

/**
 * AttendQR – AprendizRepository
 *
 * Tabla: aprendices
 * Columnas reales: id_aprendiz, numero_documento, nombres, apellidos,
 *                  password_hash, id_ficha, activo (TINYINT 1=activo)
 *
 * NO contiene lógica de negocio.
 * Flujo: AprendizService → AprendizRepository → BaseRepository → Database → MySQL
 *
 * Ubicación en el proyecto: Src/Repositories/AprendizRepository.php
 */
class AprendizRepository extends BaseRepository
{
    /**
     * Busca un aprendiz por su ID con datos básicos de su ficha.
     *
     * @param int $idAprendiz Identificador del aprendiz.
     * @return array<string, mixed>|null Datos del aprendiz o null.
     */
    public function obtenerPorId(int $idAprendiz): ?array
    {
        return $this->consultarUno(
            'SELECT a.id_aprendiz, a.numero_documento, a.nombres, a.apellidos,
                    a.activo, a.id_ficha,
                    f.codigo_ficha, f.nombre_programa
             FROM aprendices a
             JOIN fichas f ON f.id_ficha = a.id_ficha
             WHERE a.id_aprendiz = :id',
            [':id' => $idAprendiz]
        );
    }

    /**
     * Lista aprendices con filtros opcionales.
     *
     * @param int|null    $idFicha  Filtro por ficha.
     * @param int|null    $activo   Filtro por estado (1 = activo, 0 = inactivo).
     * @param string|null $documento Filtro por numero_documento.
     * @return array<int, array<string, mixed>>
     */
    public function listar(?int $idFicha = null, ?int $activo = null, ?string $documento = null): array
    {
        $sql    = 'SELECT a.id_aprendiz, a.numero_documento, a.nombres, a.apellidos,
                          a.activo, a.id_ficha, f.codigo_ficha
                   FROM aprendices a
                   JOIN fichas f ON f.id_ficha = a.id_ficha
                   WHERE 1=1';
        $params = [];

        if ($idFicha !== null) {
            $sql .= ' AND a.id_ficha         = :id_ficha';
            $params[':id_ficha']  = $idFicha;
        }

        if ($activo !== null) {
            $sql .= ' AND a.activo           = :activo';
            $params[':activo']    = $activo;
        }

        if ($documento !== null) {
            $sql .= ' AND a.numero_documento = :documento';
            $params[':documento'] = $documento;
        }

        $sql .= ' ORDER BY a.apellidos, a.nombres';

        return $this->consultar($sql, $params);
    }

    /**
     * Verifica si ya existe un aprendiz con el número de documento indicado.
     *
     * @param string   $numeroDocumento Número de documento.
     * @param int|null $excluirId       ID a excluir (para actualizaciones).
     * @return bool true si existe.
     */
    public function existeDocumento(string $numeroDocumento, ?int $excluirId = null): bool
    {
        $sql    = 'SELECT COUNT(*) FROM aprendices WHERE numero_documento = :doc';
        $params = [':doc' => $numeroDocumento];

        if ($excluirId !== null) {
            $sql              .= ' AND id_aprendiz != :excluir';
            $params[':excluir'] = $excluirId;
        }

        return $this->existe($sql, $params);
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
        return $this->contar(
            'SELECT COUNT(*) FROM aprendices WHERE id_ficha = :id AND activo = 1',
            [':id' => $idFicha]
        );
    }

    /**
     * Inserta un nuevo aprendiz. Recibe la contraseña ya hasheada.
     *
     * @param string $numeroDocumento Número de documento único.
     * @param string $nombres         Nombres.
     * @param string $apellidos       Apellidos.
     * @param string $passwordHash    Hash generado por password_hash().
     * @param int    $idFicha         Ficha a la que pertenece.
     * @return int ID del aprendiz creado.
     */
    public function crear(
        string $numeroDocumento,
        string $nombres,
        string $apellidos,
        string $passwordHash,
        int    $idFicha
    ): int {
        return $this->insertar(
            'INSERT INTO aprendices (numero_documento, nombres, apellidos, password_hash, id_ficha, activo)
             VALUES (:doc, :nombres, :apellidos, :hash, :id_ficha, 1)',
            [
                ':doc'      => $numeroDocumento,
                ':nombres'  => $nombres,
                ':apellidos' => $apellidos,
                ':hash'     => $passwordHash,
                ':id_ficha' => $idFicha,
            ]
        );
    }

    /**
     * Actualiza únicamente los campos enviados de un aprendiz existente.
     *
     * @param int                  $idAprendiz Identificador del aprendiz.
     * @param array<string, mixed> $datos      Campos a actualizar.
     * @return int Filas afectadas.
     */
    public function actualizar(int $idAprendiz, array $datos): int
    {
        $camposPermitidos = ['nombres', 'apellidos', 'password_hash', 'id_ficha', 'activo'];
        $set    = [];
        $params = [':id' => $idAprendiz];

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
            'UPDATE aprendices SET ' . implode(', ', $set) . ' WHERE id_aprendiz = :id',
            $params
        );
    }

    /**
     * Elimina un aprendiz por su ID.
     *
     * @param int $idAprendiz Identificador del aprendiz.
     * @return int Filas afectadas.
     */
    public function eliminar(int $idAprendiz): int
    {
        return $this->ejecutar(
            'DELETE FROM aprendices WHERE id_aprendiz = :id',
            [':id' => $idAprendiz]
        );
    }
}
