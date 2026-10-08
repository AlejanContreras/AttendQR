<?php

declare(strict_types=1);

/**
 * AttendQR – AuthRepository
 *
 * Responsabilidad: acceder a las tablas `docentes` y `aprendices`
 * para las operaciones de autenticación.
 *
 * El schema MVP NO incluye contraseñas en docentes ni aprendices.
 * La autenticación se realiza por correo (docentes) o
 * número de documento (aprendices).
 *
 * Flujo: AuthService → AuthRepository → BaseRepository → Database → MySQL
 *
 * Tablas reales: docentes, aprendices
 * Ubicación en el proyecto: Src/Repositories/AuthRepository.php
 */
class AuthRepository extends BaseRepository
{
    /**
     * Busca un docente por su correo electrónico.
     * Retorna los datos del docente incluyendo su estado activo.
     *
     * @param string $correo Correo del docente.
     * @return array<string, mixed>|null Fila del docente o null si no existe.
     */
    public function buscarDocentePorCorreo(string $correo): ?array
    {
        return $this->consultarUno(
            'SELECT id_docente, nombres, apellidos, correo, password_hash, activo
             FROM docentes
             WHERE correo = :correo
             LIMIT 1',
            [':correo' => $correo]
        );
    }

    /**
     * Busca un aprendiz por su número de documento.
     * Retorna los datos del aprendiz incluyendo su ficha y estado activo.
     *
     * @param string $documento Número de documento del aprendiz.
     * @return array<string, mixed>|null Fila del aprendiz o null si no existe.
     */
    public function buscarAprendizPorDocumento(string $documento): ?array
    {
        return $this->consultarUno(
            'SELECT ap.id_aprendiz, ap.nombres, ap.apellidos,
                    ap.numero_documento, ap.password_hash, ap.id_ficha,
                    ap.activo, ap.cuenta_activada,
                    f.codigo_ficha, f.nombre_programa
             FROM aprendices ap
             JOIN fichas f ON f.id_ficha = ap.id_ficha
             WHERE ap.numero_documento = :documento
             LIMIT 1',
            [':documento' => $documento]
        );
    }

    /**
     * Busca un docente por su ID.
     *
     * @param int $idDocente Identificador del docente.
     * @return array<string, mixed>|null Datos del docente o null.
     */
    public function buscarDocentePorId(int $idDocente): ?array
    {
        return $this->consultarUno(
            'SELECT id_docente, nombres, apellidos, correo, activo, creado_en
             FROM docentes
             WHERE id_docente = :id
             LIMIT 1',
            [':id' => $idDocente]
        );
    }

    /**
     * Busca un aprendiz por su ID.
     *
     * @param int $idAprendiz Identificador del aprendiz.
     * @return array<string, mixed>|null Datos del aprendiz o null.
     */
    public function buscarAprendizPorId(int $idAprendiz): ?array
    {
        return $this->consultarUno(
            'SELECT id_aprendiz, nombres, apellidos, numero_documento, id_ficha, activo
             FROM aprendices
             WHERE id_aprendiz = :id
             LIMIT 1',
            [':id' => $idAprendiz]
        );
    }

    // ─── [Recuperación de cuenta por correo: docentes y aprendices] ─────────
    // Columnas recuperacion_hash / recuperacion_expira:
    //   docentes   → Migracion_Recuperacion_Docente.sql
    //   aprendices → Migracion_Correo_Aprendiz.sql
    // Si la migración aún no se corrió, estos métodos no rompen el login:
    // devuelven null / false y la recuperación por correo no está disponible.

    /** Tabla y columna id permitidas (nunca vienen del navegador). */
    private const TABLAS_RECUPERACION = [
        'docente'  => ['docentes',   'id_docente'],
        'aprendiz' => ['aprendices', 'id_aprendiz'],
    ];

    /** @return array{0:string,1:string} */
    private function tablaRecuperacion(string $tipo): array
    {
        if (!isset(self::TABLAS_RECUPERACION[$tipo])) {
            throw new \InvalidArgumentException("Tipo de usuario no válido: {$tipo}");
        }
        return self::TABLAS_RECUPERACION[$tipo];
    }

    /**
     * Contraseña temporal vigente de un usuario, o null si no hay columnas.
     *
     * @return array{recuperacion_hash: ?string, recuperacion_expira: ?string}|null
     */
    public function obtenerRecuperacion(string $tipo, int $id): ?array
    {
        [$tabla, $col] = $this->tablaRecuperacion($tipo);
        try {
            return $this->consultarUno(
                "SELECT recuperacion_hash, recuperacion_expira FROM {$tabla} WHERE {$col} = :id",
                [':id' => $id]
            );
        } catch (\PDOException $e) {
            return null; // columnas aún no migradas
        }
    }

    /** Guarda una contraseña temporal (hash) y su vencimiento. */
    public function guardarRecuperacion(string $tipo, int $id, string $hash, string $expira): bool
    {
        [$tabla, $col] = $this->tablaRecuperacion($tipo);
        try {
            $this->ejecutar(
                "UPDATE {$tabla} SET recuperacion_hash = :hash, recuperacion_expira = :expira WHERE {$col} = :id",
                [':hash' => $hash, ':expira' => $expira, ':id' => $id]
            );
            return true;
        } catch (\PDOException $e) {
            return false;
        }
    }

    /** La temporal pasa a ser la contraseña y se limpian los campos de recuperación. */
    public function consumirRecuperacion(string $tipo, int $id): void
    {
        [$tabla, $col] = $this->tablaRecuperacion($tipo);
        $this->ejecutar(
            "UPDATE {$tabla}
             SET password_hash = recuperacion_hash, recuperacion_hash = NULL, recuperacion_expira = NULL
             WHERE {$col} = :id AND recuperacion_hash IS NOT NULL",
            [':id' => $id]
        );
    }

    /** Borra una contraseña temporal (p. ej. al entrar con la contraseña normal). */
    public function limpiarRecuperacion(string $tipo, int $id): void
    {
        [$tabla, $col] = $this->tablaRecuperacion($tipo);
        try {
            $this->ejecutar(
                "UPDATE {$tabla} SET recuperacion_hash = NULL, recuperacion_expira = NULL
                 WHERE {$col} = :id AND recuperacion_hash IS NOT NULL",
                [':id' => $id]
            );
        } catch (\PDOException $e) {
            // columnas aún no migradas: nada que limpiar
        }
    }

    /** Correo registrado del aprendiz (null si no tiene o si la columna no existe aún). */
    public function obtenerCorreoAprendiz(int $idAprendiz): ?string
    {
        try {
            $fila = $this->consultarUno(
                'SELECT correo FROM aprendices WHERE id_aprendiz = :id',
                [':id' => $idAprendiz]
            );
        } catch (\PDOException $e) {
            return null;
        }
        $correo = trim((string) ($fila['correo'] ?? ''));
        return $correo !== '' ? $correo : null;
    }

    /** ¿Existen ya las columnas del correo del aprendiz? */
    public function correoAprendizDisponible(): bool
    {
        try {
            $this->consultar('SELECT correo FROM aprendices LIMIT 1');
            return true;
        } catch (\PDOException $e) {
            return false;
        }
    }

    // ── Atajos del docente (nombres usados por AuthService desde la versión anterior)
    public function obtenerRecuperacionDocente(int $id): ?array { return $this->obtenerRecuperacion('docente', $id); }
    public function guardarRecuperacionDocente(int $id, string $hash, string $expira): bool { return $this->guardarRecuperacion('docente', $id, $hash, $expira); }
    public function consumirRecuperacionDocente(int $id): void { $this->consumirRecuperacion('docente', $id); }
    public function limpiarRecuperacionDocente(int $id): void { $this->limpiarRecuperacion('docente', $id); }
}
