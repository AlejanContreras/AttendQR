<?php

declare(strict_types=1);

/**
 * AttendQR – SesionAsistenciaModel
 *
 * Contenedor de datos de la entidad Sesión de Asistencia.
 * Tabla: sesiones_asistencia
 *
 * Columnas del schema:
 *   id_sesion, id_ficha, fecha_sesion, estado_sesion,
 *   hora_apertura, hora_inicio_clase, hora_cierre,
 *   limite_retardo_minutos, duracion_maxima_minutos,
 *   rotacion_qr_segundos
 *
 * Ubicación en el proyecto: Src/Models/SesionAsistenciaModel.php
 */
class SesionAsistenciaModel
{
    private ?int    $idSesion;
    private int     $idFicha;
    private string  $fechaSesion;
    private string  $estadoSesion;
    private string  $horaApertura;
    private string  $horaInicioClase;
    private ?string $horaCierre;
    private int     $limiteRetardoMinutos;
    private int     $duracionMaximaMinutos;
    private int     $rotacionQrSegundos;

    public function __construct(
        ?int    $idSesion              = null,
        int     $idFicha               = 0,
        string  $fechaSesion           = '',
        string  $estadoSesion          = 'abierta',
        string  $horaApertura          = '',
        string  $horaInicioClase       = '',
        ?string $horaCierre            = null,
        int     $limiteRetardoMinutos  = 10,
        int     $duracionMaximaMinutos = 240,
        int     $rotacionQrSegundos    = 30
    ) {
        $this->idSesion              = $idSesion;
        $this->idFicha               = $idFicha;
        $this->fechaSesion           = $fechaSesion;
        $this->estadoSesion          = $estadoSesion;
        $this->horaApertura          = $horaApertura;
        $this->horaInicioClase       = $horaInicioClase;
        $this->horaCierre            = $horaCierre;
        $this->limiteRetardoMinutos  = $limiteRetardoMinutos;
        $this->duracionMaximaMinutos = $duracionMaximaMinutos;
        $this->rotacionQrSegundos    = $rotacionQrSegundos;
    }

    // -------------------------------------------------------------------------
    // Getters
    // -------------------------------------------------------------------------

    public function getIdSesion(): ?int              { return $this->idSesion; }
    public function getIdFicha(): int                { return $this->idFicha; }
    public function getFechaSesion(): string         { return $this->fechaSesion; }
    public function getEstadoSesion(): string        { return $this->estadoSesion; }
    public function getHoraApertura(): string        { return $this->horaApertura; }
    public function getHoraInicioClase(): string     { return $this->horaInicioClase; }
    public function getHoraCierre(): ?string         { return $this->horaCierre; }
    public function getLimiteRetardoMinutos(): int   { return $this->limiteRetardoMinutos; }
    public function getDuracionMaximaMinutos(): int  { return $this->duracionMaximaMinutos; }
    public function getRotacionQrSegundos(): int     { return $this->rotacionQrSegundos; }

    // -------------------------------------------------------------------------
    // Setters
    // -------------------------------------------------------------------------

    public function setIdSesion(?int $idSesion): void                      { $this->idSesion              = $idSesion; }
    public function setIdFicha(int $idFicha): void                         { $this->idFicha               = $idFicha; }
    public function setFechaSesion(string $fechaSesion): void              { $this->fechaSesion           = $fechaSesion; }
    public function setEstadoSesion(string $estadoSesion): void            { $this->estadoSesion          = $estadoSesion; }
    public function setHoraApertura(string $horaApertura): void            { $this->horaApertura          = $horaApertura; }
    public function setHoraInicioClase(string $horaInicioClase): void      { $this->horaInicioClase       = $horaInicioClase; }
    public function setHoraCierre(?string $horaCierre): void               { $this->horaCierre            = $horaCierre; }
    public function setLimiteRetardoMinutos(int $minutos): void            { $this->limiteRetardoMinutos  = $minutos; }
    public function setDuracionMaximaMinutos(int $minutos): void           { $this->duracionMaximaMinutos = $minutos; }
    public function setRotacionQrSegundos(int $segundos): void             { $this->rotacionQrSegundos    = $segundos; }

    // -------------------------------------------------------------------------
    // Conversión
    // -------------------------------------------------------------------------

    public function toArray(): array
    {
        return [
            'id_sesion'               => $this->idSesion,
            'id_ficha'                => $this->idFicha,
            'fecha_sesion'            => $this->fechaSesion,
            'estado_sesion'           => $this->estadoSesion,
            'hora_apertura'           => $this->horaApertura,
            'hora_inicio_clase'       => $this->horaInicioClase,
            'hora_cierre'             => $this->horaCierre,
            'limite_retardo_minutos'  => $this->limiteRetardoMinutos,
            'duracion_maxima_minutos' => $this->duracionMaximaMinutos,
            'rotacion_qr_segundos'    => $this->rotacionQrSegundos,
        ];
    }

    /**
     * Crea una instancia a partir de un array (p. ej. fila del Repository).
     *
     * @param array<string, mixed> $datos
     */
    public static function fromArray(array $datos): self
    {
        return new self(
            isset($datos['id_sesion'])               ? (int)    $datos['id_sesion']               : null,
            (int)    ($datos['id_ficha']              ?? 0),
            (string) ($datos['fecha_sesion']          ?? ''),
            (string) ($datos['estado_sesion']         ?? 'abierta'),
            (string) ($datos['hora_apertura']         ?? ''),
            (string) ($datos['hora_inicio_clase']     ?? ''),
            isset($datos['hora_cierre'])             ? (string) $datos['hora_cierre']             : null,
            (int)    ($datos['limite_retardo_minutos']  ?? 10),
            (int)    ($datos['duracion_maxima_minutos'] ?? 240),
            (int)    ($datos['rotacion_qr_segundos']    ?? 30),
        );
    }
}