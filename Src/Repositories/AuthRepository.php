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

    // ─── [Recuperación de cuenta del docente] ────────────────────────────────
    // Columnas recuperacion_hash / recuperacion_expira (Migracion_Recuperacion_Docente.sql).
    // Si la migración aún no se corrió, estos métodos no rompen el login:
    // devuelven null / false y la recuperación simplemente no está disponible.

    /**
     * Datos de la contraseña temporal vigente de un docente, o null.
     *
     * @return array{recuperacion_hash: ?string, recuperacion_expira: ?string}|null
     */
    public function obtenerRecuperacionDocente(int $idDocente): ?array
    {
        try {
            return $this->consultarUno(
                'SELECT recuperacion_hash, recuperacion_expira
                 FROM docentes
                 WHERE id_docente = :id',
                [':id' => $idDocente]
            );
        } catch (\PDOException $e) {
            return null; // columnas aún no migradas
        }
    }

    /** Guarda una contraseña temporal (hash) y su vencimiento. */
    public function guardarRecuperacionDocente(int $idDocente, string $hash, string $expira): bool
    {
        try {
            $this->ejecutar(
                'UPDATE docentes
                 SET recuperacion_hash = :hash, recuperacion_expira = :expira
                 WHERE id_docente = :id',
                [':hash' => $hash, ':expira' => $expira, ':id' => $idDocente]
            );
            return true;
        } catch (\PDOException $e) {
            return false; // columnas aún no migradas
        }
    }

    /**
     * La contraseña temporal pasa a ser la contraseña del docente
     * y se limpian los campos de recuperación.
     */
    public function consumirRecuperacionDocente(int $idDocente): void
    {
        $this->ejecutar(
            'UPDATE docentes
             SET password_hash = recuperacion_hash,
                 recuperacion_hash = NULL,
                 recuperacion_expira = NULL
             WHERE id_docente = :id
               AND recuperacion_hash IS NOT NULL',
            [':id' => $idDocente]
        );
    }

    /** Borra una contraseña temporal (p. ej. al entrar con la contraseña normal). */
    public function limpiarRecuperacionDocente(int $idDocente): void
    {
        try {
            $this->ejecutar(
                'UPDATE docentes
                 SET recuperacion_hash = NULL, recuperacion_expira = NULL
                 WHERE id_docente = :id AND recuperacion_hash IS NOT NULL',
                [':id' => $idDocente]
            );
        } catch (\PDOException $e) {
            // columnas aún no migradas: nada que limpiar
        }
    }
}
