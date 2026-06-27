<?php

declare(strict_types=1);

/**
 * AttendQR – TokenQRModel
 *
 * Contenedor de datos de la entidad Token QR.
 * Tabla: tokens_qr
 *
 * Columnas del schema:
 *   id_token, id_sesion, token_valor, creado_en,
 *   expira_en, activo (1=vigente / NULL=rotado), veces_usado
 *
 * Nota schema v1.2: activo es TINYINT(1) NULL.
 *   1    = token vigente
 *   NULL = token vencido o rotado
 *   Se representa como ?int para respetar el NULL del schema.
 *
 * Ubicación en el proyecto: Src/Models/TokenQRModel.php
 */
class TokenQRModel
{
    private ?int    $idToken;
    private int     $idSesion;
    private string  $tokenValor;
    private string  $creadoEn;
    private string  $expiraEn;
    private ?int    $activo;
    private int     $vecesUsado;

    public function __construct(
        ?int    $idToken    = null,
        int     $idSesion   = 0,
        string  $tokenValor = '',
        string  $creadoEn   = '',
        string  $expiraEn   = '',
        ?int    $activo     = 1,
        int     $vecesUsado = 0
    ) {
        $this->idToken    = $idToken;
        $this->idSesion   = $idSesion;
        $this->tokenValor = $tokenValor;
        $this->creadoEn   = $creadoEn;
        $this->expiraEn   = $expiraEn;
        $this->activo     = $activo;
        $this->vecesUsado = $vecesUsado;
    }

    // -------------------------------------------------------------------------
    // Getters
    // -------------------------------------------------------------------------

    public function getIdToken(): ?int    { return $this->idToken; }
    public function getIdSesion(): int    { return $this->idSesion; }
    public function getTokenValor(): string { return $this->tokenValor; }
    public function getCreadoEn(): string { return $this->creadoEn; }
    public function getExpiraEn(): string { return $this->expiraEn; }
    public function getActivo(): ?int     { return $this->activo; }
    public function getVecesUsado(): int  { return $this->vecesUsado; }

    // -------------------------------------------------------------------------
    // Setters
    // -------------------------------------------------------------------------

    public function setIdToken(?int $idToken): void      { $this->idToken    = $idToken; }
    public function setIdSesion(int $idSesion): void     { $this->idSesion   = $idSesion; }
    public function setTokenValor(string $tokenValor): void { $this->tokenValor = $tokenValor; }
    public function setCreadoEn(string $creadoEn): void  { $this->creadoEn   = $creadoEn; }
    public function setExpiraEn(string $expiraEn): void  { $this->expiraEn   = $expiraEn; }
    public function setActivo(?int $activo): void        { $this->activo     = $activo; }
    public function setVecesUsado(int $vecesUsado): void { $this->vecesUsado = $vecesUsado; }

    // -------------------------------------------------------------------------
    // Conversión
    // -------------------------------------------------------------------------

    public function toArray(): array
    {
        return [
            'id_token'    => $this->idToken,
            'id_sesion'   => $this->idSesion,
            'token_valor' => $this->tokenValor,
            'creado_en'   => $this->creadoEn,
            'expira_en'   => $this->expiraEn,
            'activo'      => $this->activo,
            'veces_usado' => $this->vecesUsado,
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
            isset($datos['id_token'])  ? (int)    $datos['id_token']  : null,
            (int)    ($datos['id_sesion']   ?? 0),
            (string) ($datos['token_valor'] ?? ''),
            (string) ($datos['creado_en']   ?? ''),
            (string) ($datos['expira_en']   ?? ''),
            array_key_exists('activo', $datos) && $datos['activo'] !== null
                ? (int) $datos['activo']
                : null,
            (int)    ($datos['veces_usado'] ?? 0),
        );
    }
}