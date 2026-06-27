<?php

declare(strict_types=1);

/**
 * AttendQR – TrimestreModel
 *
 * Contenedor de datos de la entidad Trimestre.
 * Tabla: trimestres
 * Ubicación en el proyecto: Src/Models/TrimestreModel.php
 */
class TrimestreModel
{
    private ?int    $idTrimestre;
    private string  $nombre;
    private string  $fechaInicio;
    private string  $fechaFin;
    private int     $activo;

    public function __construct(
        ?int   $idTrimestre = null,
        string $nombre      = '',
        string $fechaInicio = '',
        string $fechaFin    = '',
        int    $activo      = 1
    ) {
        $this->idTrimestre = $idTrimestre;
        $this->nombre      = $nombre;
        $this->fechaInicio = $fechaInicio;
        $this->fechaFin    = $fechaFin;
        $this->activo      = $activo;
    }

    // -------------------------------------------------------------------------
    // Getters
    // -------------------------------------------------------------------------

    public function getIdTrimestre(): ?int { return $this->idTrimestre; }
    public function getNombre(): string    { return $this->nombre; }
    public function getFechaInicio(): string { return $this->fechaInicio; }
    public function getFechaFin(): string    { return $this->fechaFin; }
    public function getActivo(): int       { return $this->activo; }

    // -------------------------------------------------------------------------
    // Setters
    // -------------------------------------------------------------------------

    public function setIdTrimestre(?int $idTrimestre): void  { $this->idTrimestre = $idTrimestre; }
    public function setNombre(string $nombre): void          { $this->nombre      = $nombre; }
    public function setFechaInicio(string $fechaInicio): void { $this->fechaInicio = $fechaInicio; }
    public function setFechaFin(string $fechaFin): void       { $this->fechaFin    = $fechaFin; }
    public function setActivo(int $activo): void             { $this->activo      = $activo; }

    // -------------------------------------------------------------------------
    // Conversión
    // -------------------------------------------------------------------------

    public function toArray(): array
    {
        return [
            'id_trimestre' => $this->idTrimestre,
            'nombre'       => $this->nombre,
            'fecha_inicio' => $this->fechaInicio,
            'fecha_fin'    => $this->fechaFin,
            'activo'       => $this->activo,
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
            isset($datos['id_trimestre']) ? (int)    $datos['id_trimestre'] : null,
            (string) ($datos['nombre']       ?? ''),
            (string) ($datos['fecha_inicio'] ?? ''),
            (string) ($datos['fecha_fin']    ?? ''),
            (int)    ($datos['activo']       ?? 1),
        );
    }
}