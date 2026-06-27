<?php

declare(strict_types=1);

/**
 * AttendQR – JornadaModel
 *
 * Contenedor de datos de la entidad Jornada académica.
 * Ubicación en el proyecto: Src/Models/JornadaModel.php
 */
class JornadaModel
{
    private ?int    $id;
    private string  $nombre;
    private ?string $horaInicio;
    private ?string $horaFin;
    private string  $estado;
    private ?string $createdAt;

    public function __construct(
        ?int    $id         = null,
        string  $nombre     = '',
        ?string $horaInicio = null,
        ?string $horaFin    = null,
        string  $estado     = 'activa',
        ?string $createdAt  = null
    ) {
        $this->id         = $id;
        $this->nombre     = $nombre;
        $this->horaInicio = $horaInicio;
        $this->horaFin    = $horaFin;
        $this->estado     = $estado;
        $this->createdAt  = $createdAt;
    }

    // -------------------------------------------------------------------------
    // Getters
    // -------------------------------------------------------------------------

    public function getId(): ?int { return $this->id; }
    public function getNombre(): string { return $this->nombre; }
    public function getHoraInicio(): ?string { return $this->horaInicio; }
    public function getHoraFin(): ?string { return $this->horaFin; }
    public function getEstado(): string { return $this->estado; }
    public function getCreatedAt(): ?string { return $this->createdAt; }

    // -------------------------------------------------------------------------
    // Setters
    // -------------------------------------------------------------------------

    public function setId(?int $id): void { $this->id = $id; }
    public function setNombre(string $nombre): void { $this->nombre = $nombre; }
    public function setHoraInicio(?string $horaInicio): void { $this->horaInicio = $horaInicio; }
    public function setHoraFin(?string $horaFin): void { $this->horaFin = $horaFin; }
    public function setEstado(string $estado): void { $this->estado = $estado; }
    public function setCreatedAt(?string $createdAt): void { $this->createdAt = $createdAt; }

    // -------------------------------------------------------------------------
    // Conversión
    // -------------------------------------------------------------------------

    public function toArray(): array
    {
        return [
            'id'          => $this->id,
            'nombre'      => $this->nombre,
            'hora_inicio' => $this->horaInicio,
            'hora_fin'    => $this->horaFin,
            'estado'      => $this->estado,
            'created_at'  => $this->createdAt,
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
            isset($datos['id'])           ? (int)    $datos['id']           : null,
            (string) ($datos['nombre']       ?? ''),
            isset($datos['hora_inicio'])  ? (string) $datos['hora_inicio']  : null,
            isset($datos['hora_fin'])     ? (string) $datos['hora_fin']     : null,
            (string) ($datos['estado']       ?? 'activa'),
            isset($datos['created_at'])   ? (string) $datos['created_at']   : null,
        );
    }
}