<?php

declare(strict_types=1);

/**
 * AttendQR – QrService
 *
 * Responsabilidad: gestionar el ciclo de vida de los tokens QR.
 * Flujo: QrController → QrService → QrRepository / SesionRepository → Database
 *
 * Rotación automática:
 *   El endpoint tokenActivo() detecta si el token vigente expiró y,
 *   si la sesión sigue abierta, rota automáticamente antes de responder.
 *   La duración de cada token se lee de sesiones_asistencia.rotacion_qr_segundos.
 *
 * Ubicación en el proyecto: Src/Services/QrService.php
 */
class QrService
{
    private const SEGUNDOS_FALLBACK = 30;

    private QrRepository     $qrRepo;
    private SesionRepository $sesionRepo;

    public function __construct()
    {
        $this->qrRepo     = new QrRepository();
        $this->sesionRepo = new SesionRepository();
    }

    /**
     * Genera un nuevo token QR para una sesión abierta.
     * Invalida el token activo previo antes de crear el nuevo.
     * La duración se toma de sesiones_asistencia.rotacion_qr_segundos.
     *
     * @param int $idSesion Identificador de la sesión.
     * @return array<string, mixed> Token generado con tiempo de expiración.
     * @throws \RuntimeException 404 si la sesión no existe.
     * @throws \RuntimeException 422 si la sesión no está abierta.
     */
    public function generar(int $idSesion): array
    {
        $sesion = $this->sesionRepo->obtenerPorId($idSesion);

        if ($sesion === null) {
            throw new \RuntimeException('Sesión no encontrada.', 404);
        }

        if ((string) $sesion['estado_sesion'] !== 'abierta') {
            throw new \RuntimeException('No se puede generar QR para una sesión que no está abierta.', 422);
        }

        $segundos = (int) ($sesion['rotacion_qr_segundos'] ?? self::SEGUNDOS_FALLBACK);
        if ($segundos <= 0) {
            $segundos = self::SEGUNDOS_FALLBACK;
        }

        $this->qrRepo->invalidarPrevios($idSesion);

        $tokenValor = strtoupper(bin2hex(random_bytes(3)));
        $expiraEn   = date('Y-m-d H:i:s', time() + $segundos);

        $idToken = $this->qrRepo->crear($idSesion, $tokenValor, $expiraEn);

        return [
            'id_token'          => $idToken,
            'id_sesion'         => $idSesion,
            'token_valor'       => $tokenValor,
            'expira_en'         => $expiraEn,
            'segundos_vigencia' => $segundos,
        ];
    }

    /**
     * Retorna el token QR activo y vigente de una sesión.
     *
     * Si el token activo expiró y la sesión sigue abierta, rota
     * automáticamente y devuelve el nuevo token sin necesidad de
     * una petición adicional del cliente.
     *
     * @param int $idSesion Identificador de la sesión.
     * @return array<string, mixed> Token activo y segundos restantes.
     * @throws \RuntimeException 404 si la sesión no existe o está cerrada.
     */
    public function tokenActivo(int $idSesion): array
    {
        $this->sesionRepo->cerrarVencidas();

        $qr = $this->qrRepo->obtenerActivoPorSesion($idSesion);

        if ($qr === null) {
            // Token expirado o nunca creado: verificar sesión y rotar
            $sesion = $this->sesionRepo->obtenerPorId($idSesion);

            if ($sesion === null) {
                throw new \RuntimeException('Sesión no encontrada.', 404);
            }

            if ((string) $sesion['estado_sesion'] !== 'abierta') {
                throw new \RuntimeException('La sesión no está abierta; no hay token QR activo.', 404);
            }

            // Rotación automática
            $qr = $this->rotarYCrear($idSesion, (int) ($sesion['rotacion_qr_segundos'] ?? self::SEGUNDOS_FALLBACK));
        }

        $segundosRestantes = max(0, strtotime((string) $qr['expira_en']) - time());

        return [
            'id_sesion'          => $idSesion,
            'token_valor'        => $qr['token_valor'],
            'expira_en'          => $qr['expira_en'],
            'segundos_restantes' => $segundosRestantes,
        ];
    }

    /**
     * Valida un token QR escaneado por un aprendiz.
     *
     * Verificaciones en orden:
     *   1. El token existe en la BD.
     *   2. El token sigue activo (activo = 1, no rotado).
     *   3. El token no ha expirado por tiempo.
     *   4. La sesión vinculada está abierta.
     *
     * Tras una validación exitosa incrementa el contador de uso del token.
     * El registro de asistencia lo ejecuta AsistenciaService en el módulo siguiente.
     *
     * @param string $tokenValor Valor del token QR escaneado.
     * @param int    $idAprendiz Identificador del aprendiz autenticado.
     * @return array<string, mixed> ID de la sesión y del aprendiz si el token es válido.
     * @throws \RuntimeException 404 si el token no existe.
     * @throws \RuntimeException 410 si el token ha sido rotado o expirado.
     * @throws \RuntimeException 422 si la sesión ya no está abierta.
     */
    public function validar(string $tokenValor, int $idAprendiz): array
    {
        $this->sesionRepo->cerrarVencidas();

        $tokenValor = trim($tokenValor);
        $qr         = $this->qrRepo->buscarToken($tokenValor);

        if ($qr === null) {
            throw new \RuntimeException('Token QR no encontrado.', 404);
        }

        // Token rotado antes de expirar (p.ej. al cerrar sesión manualmente)
        if ((int) $qr['activo'] !== 1) {
            throw new \RuntimeException('El token QR ya no está activo.', 410);
        }

        if (strtotime((string) $qr['expira_en']) < time()) {
            throw new \RuntimeException('El token QR ha expirado.', 410);
        }

        if ((string) $qr['estado_sesion'] !== 'abierta') {
            throw new \RuntimeException('La sesión vinculada al QR ya no está abierta.', 422);
        }

        $this->qrRepo->incrementarUso((int) $qr['id_token']);

        return [
            'token_valido' => true,
            'id_token'     => (int) $qr['id_token'],
            'id_sesion'    => (int) $qr['id_sesion'],
            'id_aprendiz'  => $idAprendiz,
        ];
    }

    // -------------------------------------------------------------------------
    // Métodos privados de apoyo
    // -------------------------------------------------------------------------

    /**
     * Invalida el token activo actual y crea uno nuevo.
     * Devuelve los datos del token recién creado tal como los almacena la BD.
     *
     * @param int $idSesion  Identificador de la sesión.
     * @param int $segundos  Duración del nuevo token en segundos.
     * @return array<string, mixed> Datos del nuevo token activo.
     */
    private function rotarYCrear(int $idSesion, int $segundos): array
    {
        if ($segundos <= 0) {
            $segundos = self::SEGUNDOS_FALLBACK;
        }

        $this->qrRepo->invalidarPrevios($idSesion);

        $tokenValor = strtoupper(bin2hex(random_bytes(3)));
        $expiraEn   = date('Y-m-d H:i:s', time() + $segundos);

        $this->qrRepo->crear($idSesion, $tokenValor, $expiraEn);

        return [
            'token_valor' => $tokenValor,
            'expira_en'   => $expiraEn,
        ];
    }
}
