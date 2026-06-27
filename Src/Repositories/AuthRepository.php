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
            'SELECT id_docente, nombres, apellidos, correo, activo
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
                    ap.numero_documento, ap.id_ficha, ap.activo,
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
}