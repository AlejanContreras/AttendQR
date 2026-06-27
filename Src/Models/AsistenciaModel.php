<?php

declare(strict_types=1);

/**
 * AttendQR – AsistenciaModel
 *
 * Contenedor de datos de la entidad Asistencia.
 * Tabla: asistencias
 *
 * Columnas del schema:
 *   id_asistencia, id_sesion, id_aprendiz, id_token_usado,
 *   estado (presente|ausente|excusa|retardo),
 *   metodo_registro (qr|manual), hora_registro,
 *   minutos_retardo, ubicacion_valida, latitud, longitud,
 *   observacion, registrado_en
 *
 * Regla schema v1.2:
 *   Si metodo_registro = 'qr', hora_registro NO puede ser NULL.
 *   Esta restricción la aplica el CHECK en BD; el Model solo transporta el dato.
 *
 * Ubicación en el proyecto: Src/Models/AsistenciaModel.php
 */
class AsistenciaModel
{
    private ?int    $idAsistencia;
    private int     $idSesion;
    private int     $idAprendiz;
    private ?int    $idTokenUsado;
    private string  $estado;
    private string  $metodoRegistro;
    private ?string $horaRegistro;
    private int     $minutosRetardo;
    private ?int    $ubicacionValida;
    private ?float  $latitud;
    private ?float  $longitud;
    private ?string $observacion;
    private string  $registradoEn;

    public function __construct(
        ?int    $idAsistencia   = null,
        int     $idSesion       = 0,
        int     $idAprendiz     = 0,
        ?int    $idTokenUsado   = null,
        string  $estado         = 'ausente',
        string  $metodoRegistro = 'manual',
        ?string $horaRegistro   = null,
        int     $minutosRetardo = 0,
        ?int    $ubicacionValida = null,
        ?float  $latitud        = null,
        ?float  $longitud       = null,
        ?string $observacion    = null,
        string  $registradoEn  = ''
    ) {
        $this->idAsistencia   = $idAsistencia;
        $this->idSesion       = $idSesion;
        $this->idAprendiz     = $idAprendiz;
        $this->idTokenUsado   = $idTokenUsado;
        $this->estado         = $estado;
        $this->metodoRegistro = $metodoRegistro;
        $this->horaRegistro   = $horaRegistro;
        $this->minutosRetardo = $minutosRetardo;
        $this->ubicacionValida = $ubicacionValida;
        $this->latitud        = $latitud;
        $this->longitud       = $longitud;
        $this->observacion    = $observacion;
        $this->registradoEn  = $registradoEn;
    }

    // -------------------------------------------------------------------------
    // Getters
    // -------------------------------------------------------------------------

    public function getIdAsistencia(): ?int    { return $this->idAsistencia; }
    public function getIdSesion(): int         { return $this->idSesion; }
    public function getIdAprendiz(): int       { return $this->idAprendiz; }
    public function getIdTokenUsado(): ?int    { return $this->idTokenUsado; }
    public function getEstado(): string        { return $this->estado; }
    public function getMetodoRegistro(): string { return $this->metodoRegistro; }
    public function getHoraRegistro(): ?string { return $this->horaRegistro; }
    public function getMinutosRetardo(): int   { return $this->minutosRetardo; }
    public function getUbicacionValida(): ?int { return $this->ubicacionValida; }
    public function getLatitud(): ?float       { return $this->latitud; }
    public function getLongitud(): ?float      { return $this->longitud; }
    public function getObservacion(): ?string  { return $this->observacion; }
    public function getRegistradoEn(): string  { return $this->registradoEn; }

    // -------------------------------------------------------------------------
    // Setters
    // -------------------------------------------------------------------------

    public function setIdAsistencia(?int $idAsistencia): void     { $this->idAsistencia   = $idAsistencia; }
    public function setIdSesion(int $idSesion): void              { $this->idSesion       = $idSesion; }
    public function setIdAprendiz(int $idAprendiz): void          { $this->idAprendiz     = $idAprendiz; }
    public function setIdTokenUsado(?int $idTokenUsado): void     { $this->idTokenUsado   = $idTokenUsado; }
    public function setEstado(string $estado): void               { $this->estado         = $estado; }
    public function setMetodoRegistro(string $metodo): void       { $this->metodoRegistro = $metodo; }
    public function setHoraRegistro(?string $horaRegistro): void  { $this->horaRegistro   = $horaRegistro; }
    public function setMinutosRetardo(int $minutos): void         { $this->minutosRetardo = $minutos; }
    public function setUbicacionValida(?int $valor): void         { $this->ubicacionValida = $valor; }
    public function setLatitud(?float $latitud): void             { $this->latitud        = $latitud; }
    public function setLongitud(?float $longitud): void           { $this->longitud       = $longitud; }
    public function setObservacion(?string $observacion): void    { $this->observacion    = $observacion; }
    public function setRegistradoEn(string $registradoEn): void   { $this->registradoEn  = $registradoEn; }

    // -------------------------------------------------------------------------
    // Conversión
    // -------------------------------------------------------------------------

    public function toArray(): array
    {
        return [
            'id_asistencia'   => $this->idAsistencia,
            'id_sesion'       => $this->idSesion,
            'id_aprendiz'     => $this->idAprendiz,
            'id_token_usado'  => $this->idTokenUsado,
            'estado'          => $this->estado,
            'metodo_registro' => $this->metodoRegistro,
            'hora_registro'   => $this->horaRegistro,
            'minutos_retardo' => $this->minutosRetardo,
            'ubicacion_valida' => $this->ubicacionValida,
            'latitud'         => $this->latitud,
            'longitud'        => $this->longitud,
            'observacion'     => $this->observacion,
            'registrado_en'   => $this->registradoEn,
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
            isset($datos['id_asistencia'])  ? (int)    $datos['id_asistencia']  : null,
            (int)    ($datos['id_sesion']       ?? 0),
            (int)    ($datos['id_aprendiz']     ?? 0),
            isset($datos['id_token_usado'])  ? (int)    $datos['id_token_usado']  : null,
            (string) ($datos['estado']          ?? 'ausente'),
            (string) ($datos['metodo_registro'] ?? 'manual'),
            isset($datos['hora_registro'])   ? (string) $datos['hora_registro']   : null,
            (int)    ($datos['minutos_retardo'] ?? 0),
            isset($datos['ubicacion_valida']) ? (int)   $datos['ubicacion_valida'] : null,
            isset($datos['latitud'])         ? (float)  $datos['latitud']          : null,
            isset($datos['longitud'])        ? (float)  $datos['longitud']         : null,
            isset($datos['observacion'])     ? (string) $datos['observacion']      : null,
            (string) ($datos['registrado_en']   ?? ''),
        );
    }
}