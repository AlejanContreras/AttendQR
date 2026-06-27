<?php

declare(strict_types=1);

/**
 * AttendQR – DocenteModel
 *
 * Contenedor de datos de la entidad Docente.
 * Ubicación en el proyecto: Src/Models/DocenteModel.php
 */
class DocenteModel
{
    private ?int    $id;
    private string  $documento;
    private string  $nombres;
    private string  $apellidos;
    private string  $correo;
    private ?string $especialidad;
    private string  $estado;
    private ?string $createdAt;

    public function __construct(
        ?int    $id           = null,
        string  $documento    = '',
        string  $nombres      = '',
        string  $apellidos    = '',
        string  $correo       = '',
        ?string $especialidad = null,
        string  $estado       = 'activo',
        ?string $createdAt    = null
    ) {
        $this->id           = $id;
        $this->documento    = $documento;
        $this->nombres      = $nombres;
        $this->apellidos    = $apellidos;
        $this->correo       = $correo;
        $this->especialidad = $especialidad;
        $this->estado       = $estado;
        $this->createdAt    = $createdAt;
    }

    // -------------------------------------------------------------------------
    // Getters
    // -------------------------------------------------------------------------

    public function getId(): ?int { return $this->id; }
    public function getDocumento(): string { return $this->documento; }
    public function getNombres(): string { return $this->nombres; }
    public function getApellidos(): string { return $this->apellidos; }
    public function getCorreo(): string { return $this->correo; }
    public function getEspecialidad(): ?string { return $this->especialidad; }
    public function getEstado(): string { return $this->estado; }
    public function getCreatedAt(): ?string { return $this->createdAt; }

    // -------------------------------------------------------------------------
    // Setters
    // -------------------------------------------------------------------------

    public function setId(?int $id): void { $this->id = $id; }
    public function setDocumento(string $documento): void { $this->documento = $documento; }
    public function setNombres(string $nombres): void { $this->nombres = $nombres; }
    public function setApellidos(string $apellidos): void { $this->apellidos = $apellidos; }
    public function setCorreo(string $correo): void { $this->correo = $correo; }
    public function setEspecialidad(?string $especialidad): void { $this->especialidad = $especialidad; }
    public function setEstado(string $estado): void { $this->estado = $estado; }
    public function setCreatedAt(?string $createdAt): void { $this->createdAt = $createdAt; }

    // -------------------------------------------------------------------------
    // Conversión
    // -------------------------------------------------------------------------

    public function toArray(): array
    {
        return [
            'id'           => $this->id,
            'documento'    => $this->documento,
            'nombres'      => $this->nombres,
            'apellidos'    => $this->apellidos,
            'correo'       => $this->correo,
            'especialidad' => $this->especialidad,
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
            isset($datos['id'])           ? (int)    $datos['id']           : null,
            (string) ($datos['documento']    ?? ''),
            (string) ($datos['nombres']      ?? ''),
            (string) ($datos['apellidos']    ?? ''),
            (string) ($datos['correo']       ?? ''),
            isset($datos['especialidad'])  ? (string) $datos['especialidad'] : null,
            (string) ($datos['estado']       ?? 'activo'),
            isset($datos['created_at'])    ? (string) $datos['created_at']  : null,
        );
    }
}