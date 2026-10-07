<?php

declare(strict_types=1);

/**
 * AttendQR – PropiedadRepository
 *
 * [Aislamiento entre docentes] Responde una sola pregunta para cada entidad:
 * "¿de qué docente es esto?". Todas las consultas suben por la cadena
 *
 *     asistencia → sesión → ficha → docente
 *     aprendiz   → ficha  → docente
 *
 * Cada método devuelve el id_docente dueño, o null si el registro no existe.
 * AccesoService usa estas respuestas para decidir 403 (ajeno) o 404 (no existe).
 *
 * Flujo: AccesoService → PropiedadRepository → BaseRepository → Database → MySQL
 *
 * Ubicación en el proyecto: Src/Repositories/PropiedadRepository.php
 */
class PropiedadRepository extends BaseRepository
{
    /** Dueño de una ficha (clase). */
    public function docenteDeFicha(int $idFicha): ?int
    {
        return $this->docente(
            'SELECT id_docente FROM fichas WHERE id_ficha = :id',
            $idFicha
        );
    }

    /** Dueño de la ficha a la que pertenece un aprendiz. */
    public function docenteDeAprendiz(int $idAprendiz): ?int
    {
        return $this->docente(
            'SELECT f.id_docente
               FROM aprendices a
               JOIN fichas f ON f.id_ficha = a.id_ficha
              WHERE a.id_aprendiz = :id',
            $idAprendiz
        );
    }

    /** Dueño de la ficha de una sesión de asistencia. */
    public function docenteDeSesion(int $idSesion): ?int
    {
        return $this->docente(
            'SELECT f.id_docente
               FROM sesiones_asistencia s
               JOIN fichas f ON f.id_ficha = s.id_ficha
              WHERE s.id_sesion = :id',
            $idSesion
        );
    }

    /** Dueño de la ficha de la sesión en la que se registró una asistencia. */
    public function docenteDeAsistencia(int $idAsistencia): ?int
    {
        return $this->docente(
            'SELECT f.id_docente
               FROM asistencias a
               JOIN sesiones_asistencia s ON s.id_sesion = a.id_sesion
               JOIN fichas f              ON f.id_ficha  = s.id_ficha
              WHERE a.id_asistencia = :id',
            $idAsistencia
        );
    }

    /** Aprendiz al que pertenece un registro de asistencia (null si no existe). */
    public function aprendizDeAsistencia(int $idAsistencia): ?int
    {
        $fila = $this->consultarUno(
            'SELECT id_aprendiz FROM asistencias WHERE id_asistencia = :id',
            [':id' => $idAsistencia]
        );
        return $fila !== null ? (int) $fila['id_aprendiz'] : null;
    }

    private function docente(string $sql, int $id): ?int
    {
        $fila = $this->consultarUno($sql, [':id' => $id]);
        return $fila !== null ? (int) $fila['id_docente'] : null;
    }
}
