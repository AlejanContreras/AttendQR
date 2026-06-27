<?php

declare(strict_types=1);

/**
 * AttendQR – FichaModel
 *
 * Contenedor de datos de la entidad Ficha de formación.
 * Ubicación en el proyecto: Src/Models/FichaModel.php
 */
class FichaModel
{
    private ?int    $id;
    private string  $numeroFicha;
    private int     $idPrograma;
    private ?int    $idJornada;
    private string  $estado;
    private ?string $createdAt;

    public function __construct(
        ?int    $id          = null,
        string  $numeroFicha = '',
        int     $idPrograma  = 0,
        ?int    $idJornada   = null,
        string  $estado      = 'activa',
        ?string $createdAt   = null
    ) {
        $this->id          = $id;
        $this->numeroFicha = $numeroFicha;
        $this->idPrograma  = $idPrograma;
        $this->idJornada   = $idJornada;
        $this->estado      = $estado;
        $this->createdAt   = $createdAt;
    }

    // -------------------------------------------------------------------------
    // Getters
    // -------------------------------------------------------------------------

    public function getId(): ?int { return $this->id; }
    public function getNumeroFicha(): string { return $this->numeroFicha; }
    public function getIdPrograma(): int { return $this->idPrograma; }
    public function getIdJornada(): ?int { return $this->idJornada; }
    public function getEstado(): string { return $this->estado; }
    public function getCreatedAt(): ?string { return $this->createdAt; }

    // -------------------------------------------------------------------------
    // Setters
    // -------------------------------------------------------------------------

    public function setId(?int $id): void { $this->id = $id; }
    public function setNumeroFicha(string $numeroFicha): void { $this->numeroFicha = $numeroFicha; }
    public function setIdPrograma(int $idPrograma): void { $this->idPrograma = $idPrograma; }
    public function setIdJornada(?int $idJornada): void { $this->idJornada = $idJornada; }
    public function setEstado(string $estado): void { $this->estado = $estado; }
    public function setCreatedAt(?string $createdAt): void { $this->createdAt = $createdAt; }

    // -------------------------------------------------------------------------
    // Conversión
    // -------------------------------------------------------------------------

    public function toArray(): array
    {
        return [
            'id'           => $this->id,
            'numero_ficha' => $this->numeroFicha,
            'id_programa'  => $this->idPrograma,
            'id_jornada'   => $this->idJornada,
            'estado'       => $this->estado,
            'created_at'   => $this->createdAt,
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
            isset($datos['id'])          ? (int)    $datos['id']          : null,
            (string) ($datos['numero_ficha'] ?? ''),
            (int)    ($datos['id_programa']  ?? 0),
            isset($datos['id_jornada'])  ? (int)    $datos['id_jornada']  : null,
            (string) ($datos['estado']       ?? 'activa'),
            isset($datos['created_at'])  ? (string) $datos['created_at']  : null,
        );
    }
}