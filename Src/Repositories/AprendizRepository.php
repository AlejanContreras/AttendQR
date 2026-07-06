<?php

declare(strict_types=1);

/**
 * AttendQR – AprendizRepository
 *
 * Tabla: aprendices
 * Columnas: id_aprendiz, numero_documento, nombres, apellidos,
 *           password_hash, id_ficha, activo, cuenta_activada
 *
 * NO contiene lógica de negocio.
 * Flujo: AprendizService → AprendizRepository → BaseRepository → Database → MySQL
 */
class AprendizRepository extends BaseRepository
{
    /**
     * Busca un aprendiz por su ID con datos básicos de su ficha.
     */
    public function obtenerPorId(int $idAprendiz): ?array
    {
        return $this->consultarUno(
            'SELECT a.id_aprendiz, a.numero_documento, a.nombres, a.apellidos,
                    a.activo, a.cuenta_activada, a.id_ficha,
                    f.codigo_ficha, f.nombre_programa
             FROM aprendices a
             JOIN fichas f ON f.id_ficha = a.id_ficha
             WHERE a.id_aprendiz = :id',
            [':id' => $idAprendiz]
        );
    }

    /**
     * Busca un aprendiz por su número de documento.
     * Incluye cuenta_activada para el flujo de registro auto-servicio.
     */
    public function buscarPorDocumento(string $documento): ?array
    {
        return $this->consultarUno(
            'SELECT a.id_aprendiz, a.numero_documento, a.nombres, a.apellidos,
                    a.activo, a.cuenta_activada, a.id_ficha,
                    f.codigo_ficha, f.nombre_programa
             FROM aprendices a
             JOIN fichas f ON f.id_ficha = a.id_ficha
             WHERE a.numero_documento = :doc
             LIMIT 1',
            [':doc' => $documento]
        );
    }

    /**
     * Lista aprendices con filtros opcionales.
     *
     * @param int|null    $idFicha      Filtro por ficha.
     * @param int|null    $activo       Filtro por estado (1 = activo, 0 = inactivo).
     * @param string|null $documento    Filtro por numero_documento.
     * @param int|null    $cuentaActiva Filtro por cuenta_activada (1 = activada, 0 = pendiente).
     */
    public function listar(
        ?int    $idFicha      = null,
        ?int    $activo       = null,
        ?string $documento    = null,
        ?int    $cuentaActiva = null
    ): array {
        $sql    = 'SELECT a.id_aprendiz, a.numero_documento, a.nombres, a.apellidos,
                          a.activo, a.cuenta_activada, a.id_ficha, f.codigo_ficha, f.nombre_programa
                   FROM aprendices a
                   JOIN fichas f ON f.id_ficha = a.id_ficha
                   WHERE 1=1';
        $params = [];

        if ($idFicha !== null) {
            $sql .= ' AND a.id_ficha         = :id_ficha';
            $params[':id_ficha']      = $idFicha;
        }
        if ($activo !== null) {
            $sql .= ' AND a.activo           = :activo';
            $params[':activo']        = $activo;
        }
        if ($documento !== null) {
            $sql .= ' AND a.numero_documento = :documento';
            $params[':documento']     = $documento;
        }
        if ($cuentaActiva !== null) {
            $sql .= ' AND a.cuenta_activada  = :cuenta_activada';
            $params[':cuenta_activada'] = $cuentaActiva;
        }

        $sql .= ' ORDER BY a.apellidos, a.nombres';

        return $this->consultar($sql, $params);
    }

    /**
     * Verifica si ya existe un aprendiz con el número de documento indicado.
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
     * @param int $cuentaActivada 1 = cuenta lista; 0 = pre-registrado (sin contraseña real).
     */
    public function crear(
        string $numeroDocumento,
        string $nombres,
        string $apellidos,
        string $passwordHash,
        int    $idFicha,
        int    $cuentaActivada = 1
    ): int {
        return $this->insertar(
            'INSERT INTO aprendices
               (numero_documento, nombres, apellidos, password_hash, id_ficha, activo, cuenta_activada)
             VALUES (:doc, :nombres, :apellidos, :hash, :id_ficha, 1, :cuenta_activada)',
            [
                ':doc'              => $numeroDocumento,
                ':nombres'          => $nombres,
                ':apellidos'        => $apellidos,
                ':hash'             => $passwordHash,
                ':id_ficha'         => $idFicha,
                ':cuenta_activada'  => $cuentaActivada,
            ]
        );
    }

    /**
     * Activa la cuenta de un aprendiz pre-registrado: establece su contraseña real.
     * La cláusula AND cuenta_activada = 0 previene doble activación por concurrencia.
     */
    public function activarCuenta(int $idAprendiz, string $passwordHash): int
    {
        return $this->ejecutar(
            'UPDATE aprendices
             SET password_hash = :hash, cuenta_activada = 1
             WHERE id_aprendiz = :id AND cuenta_activada = 0',
            [':hash' => $passwordHash, ':id' => $idAprendiz]
        );
    }

    /**
     * Actualiza únicamente los campos enviados de un aprendiz existente.
     */
    public function actualizar(int $idAprendiz, array $datos): int
    {
        $camposPermitidos = ['nombres', 'apellidos', 'password_hash', 'id_ficha', 'activo', 'cuenta_activada'];
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
     */
    public function eliminar(int $idAprendiz): int
    {
        return $this->ejecutar(
            'DELETE FROM aprendices WHERE id_aprendiz = :id',
            [':id' => $idAprendiz]
        );
    }
}
