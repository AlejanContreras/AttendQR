<?php

declare(strict_types=1);

/**
 * AttendQR – QrService
 *
 * Responsabilidad: gestionar el ciclo de vida de los tokens QR.
 * Flujo: QrController → QrService → QrRepository / SesionRepository → Database
 *
 * Ubicación en el proyecto: Src/Services/QrService.php
 */
class QrService
{
    /** Duración del token QR en segundos (15 minutos). */
    private const EXPIRACION_SEGUNDOS = 900;

    private QrRepository     $qrRepo;
    private SesionRepository $sesionRepo;

    public function __construct()
    {
        $this->qrRepo     = new QrRepository();
        $this->sesionRepo = new SesionRepository();
    }

    /**
     * Genera un nuevo token QR para una sesión activa.
     * Invalida cualquier token previo vinculado a la misma sesión.
     *
     * @param int $idSesion Identificador de la sesión.
     * @return array<string, mixed> Token generado y tiempo de expiración.
     * @throws \RuntimeException 404 si la sesión no existe.
     * @throws \RuntimeException 422 si la sesión no está activa.
     */
    public function generar(int $idSesion): array
    {
        $sesion = $this->sesionRepo->obtenerPorId($idSesion);

        if ($sesion === null) {
            throw new \RuntimeException('Sesión no encontrada.', 404);
        }

        if ((string) $sesion['estado'] !== 'activa') {
            throw new \RuntimeException('No se puede generar QR para una sesión inactiva.', 422);
        }

        $this->qrRepo->invalidarPrevios($idSesion);

        $token      = $this->generarTokenUnico();
        $expiracion = $this->calcularExpiracion();

        $id = $this->qrRepo->crear($idSesion, $token, $expiracion);

        return [
            'id'               => $id,
            'id_sesion'        => $idSesion,
            'token'            => $token,
            'expira_en'        => $expiracion,
            'segundos_vigencia' => self::EXPIRACION_SEGUNDOS,
        ];
    }

    /**
     * Retorna el token QR activo y vigente de una sesión.
     *
     * @param int $idSesion Identificador de la sesión.
     * @return array<string, mixed> Token activo y segundos restantes.
     * @throws \RuntimeException 404 si no hay token activo para la sesión.
     */
    public function tokenActivo(int $idSesion): array
    {
        $qr = $this->qrRepo->obtenerActivoPorSesion($idSesion);

        if ($qr === null) {
            throw new \RuntimeException('No hay token QR activo para esta sesión.', 404);
        }

        $segundosRestantes = max(0, strtotime((string) $qr['expiracion']) - time());

        return [
            'id_sesion'         => $idSesion,
            'token'             => $qr['token'],
            'expira_en'         => $qr['expiracion'],
            'segundos_restantes' => $segundosRestantes,
        ];
    }

    /**
     * Valida un token QR escaneado por un aprendiz.
     * Solo valida el token; el registro de asistencia lo ejecuta AsistenciaService.
     *
     * @param string $token      Token QR escaneado.
     * @param int    $idAprendiz Identificador del aprendiz que escanea.
     * @return array<string, mixed> ID de la sesión vinculada si el token es válido.
     * @throws \RuntimeException 404 si el token no existe.
     * @throws \RuntimeException 410 si el token ha expirado.
     * @throws \RuntimeException 422 si la sesión vinculada ya no está activa.
     */
    public function validar(string $token, int $idAprendiz): array
    {
        $token = trim($token);
        $qr    = $this->qrRepo->buscarToken($token);

        if ($qr === null) {
            throw new \RuntimeException('Token QR no encontrado.', 404);
        }

        if (strtotime((string) $qr['expiracion']) < time()) {
            throw new \RuntimeException('El token QR ha expirado.', 410);
        }

        if ((string) $qr['estado_sesion'] !== 'activa') {
            throw new \RuntimeException('La sesión vinculada al QR ya no está activa.', 422);
        }

        return [
            'token_valido' => true,
            'id_sesion'    => (int) $qr['id_sesion'],
            'id_aprendiz'  => $idAprendiz,
        ];
    }

    // -------------------------------------------------------------------------
    // Métodos privados de apoyo
    // -------------------------------------------------------------------------

    /**
     * Genera un token único usando entropía criptográfica.
     * Produce una cadena hexadecimal de 64 caracteres.
     *
     * @return string Token hexadecimal de 64 caracteres.
     */
    private function generarTokenUnico(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Calcula la fecha y hora de expiración sumando EXPIRACION_SEGUNDOS al momento actual.
     *
     * @return string Fecha y hora en formato 'Y-m-d H:i:s'.
     */
    private function calcularExpiracion(): string
    {
        return date('Y-m-d H:i:s', time() + self::EXPIRACION_SEGUNDOS);
    }
}