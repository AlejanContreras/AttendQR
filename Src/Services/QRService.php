<?php

declare(strict_types=1);

/**
 * AttendQR – QrService
 *
 * Responsabilidad: gestionar el ciclo de vida de los tokens QR del sistema.
 * Un token QR tiene vida limitada y está vinculado a una sesión activa.
 * Los aprendices escanean el QR para que el sistema registre su asistencia.
 *
 * Esta clase NO debe:
 *   - Ejecutar SQL directamente.
 *   - Conocer el router ni los Controllers.
 *   - Acceder a $_POST, $_GET ni $_REQUEST.
 *   - Imprimir JSON ni HTML.
 *
 * Flujo esperado cuando se integren las capas inferiores:
 *   QrController → QrService → QrRepository → Modelo → Database
 *
 * Ubicación en el proyecto: Src/Services/QrService.php
 */
class QrService
{
    // -------------------------------------------------------------------------
    // Configuración del servicio
    // -------------------------------------------------------------------------

    /**
     * Tiempo de expiración del token QR en segundos.
     * ► AJUSTAR según los requisitos del MVP (por defecto: 15 minutos).
     */
    private const EXPIRACION_SEGUNDOS = 900; // 15 minutos

    // -------------------------------------------------------------------------
    // Dependencias (se inyectarán cuando se creen los Repositories)
    // -------------------------------------------------------------------------

    // ► AQUÍ: declarar dependencias cuando existan los Repositories
    //
    // Ejemplo futuro:
    //   private QrRepository      $qrRepo;
    //   private SesionRepository  $sesionRepo;
    //
    //   public function __construct(
    //       QrRepository     $qrRepo,
    //       SesionRepository $sesionRepo
    //   ) {
    //       $this->qrRepo     = $qrRepo;
    //       $this->sesionRepo = $sesionRepo;
    //   }

    // -------------------------------------------------------------------------
    // Métodos públicos del servicio
    // -------------------------------------------------------------------------

    /**
     * Genera un nuevo token QR para una sesión activa.
     *
     * Reglas de negocio que este método deberá aplicar:
     *   1. Verificar que la sesión existe y su estado es 'activa'.
     *   2. Invalidar cualquier token QR previo vinculado a esa sesión.
     *   3. Generar un token único e impredecible (bin2hex(random_bytes(32))).
     *   4. Calcular la fecha y hora de expiración (now + EXPIRACION_SEGUNDOS).
     *   5. Persistir el token en la base de datos mediante QrRepository.
     *   6. Retornar el token y su expiración para que el Controller lo entregue al docente.
     *
     * @param int $idSesion Identificador único de la sesión para la que se genera el QR.
     * @return array<string, mixed> Token generado, URL de escaneo y tiempo de expiración.
     */
    public function generar(int $idSesion): array
    {
        // ► AQUÍ: llamar a SesionRepository->obtenerPorId($idSesion)
        // ► AQUÍ: verificar que $sesion['estado'] === 'activa'
        // ► AQUÍ: llamar a QrRepository->invalidarPrevios($idSesion)
        // ► AQUÍ: generar el token con bin2hex(random_bytes(32))
        // ► AQUÍ: calcular $expiracion = date('Y-m-d H:i:s', time() + self::EXPIRACION_SEGUNDOS)
        // ► AQUÍ: llamar a QrRepository->crear($idSesion, $token, $expiracion)
        //
        // Ejemplo futuro:
        //   $sesion     = $this->sesionRepo->obtenerPorId($idSesion);
        //   if ($sesion['estado'] !== 'activa') {
        //       throw new \RuntimeException('No se puede generar QR para una sesión inactiva.', 422);
        //   }
        //   $this->qrRepo->invalidarPrevios($idSesion);
        //   $token      = bin2hex(random_bytes(32));
        //   $expiracion = date('Y-m-d H:i:s', time() + self::EXPIRACION_SEGUNDOS);
        //   $this->qrRepo->crear($idSesion, $token, $expiracion);
        //   return ['token' => $token, 'expira_en' => $expiracion, 'segundos' => self::EXPIRACION_SEGUNDOS];

        return [
            'success'    => true,
            'message'    => 'QrService::generar() disponible. Pendiente de implementación.',
            'id_sesion'  => $idSesion,
            'expiracion' => self::EXPIRACION_SEGUNDOS . ' segundos',
        ];
    }

    /**
     * Obtiene el token QR activo (vigente) de una sesión.
     *
     * Reglas de negocio que este método deberá aplicar:
     *   1. Buscar en la base de datos el token vinculado a la sesión
     *      que no haya expirado (expiracion > now()).
     *   2. Si no existe o ya expiró, lanzar una excepción (→ 404 en el Controller).
     *   3. Calcular los segundos restantes antes de que expire.
     *   4. Retornar el token y los segundos restantes.
     *
     * @param int $idSesion Identificador único de la sesión consultada.
     * @return array<string, mixed> Token activo y segundos restantes de validez.
     */
    public function tokenActivo(int $idSesion): array
    {
        // ► AQUÍ: llamar a QrRepository->obtenerActivoPorSesion($idSesion)
        // ► AQUÍ: si no existe, lanzar new \RuntimeException('No hay QR activo para esta sesión.', 404)
        // ► AQUÍ: calcular segundos restantes: strtotime($qr['expiracion']) - time()
        //
        // Ejemplo futuro:
        //   $qr = $this->qrRepo->obtenerActivoPorSesion($idSesion);
        //   if (!$qr) {
        //       throw new \RuntimeException('No hay token QR activo para esta sesión.', 404);
        //   }
        //   $segundosRestantes = strtotime($qr['expiracion']) - time();
        //   return ['token' => $qr['token'], 'segundos_restantes' => $segundosRestantes];

        return [
            'success'   => true,
            'message'   => 'QrService::tokenActivo() disponible. Pendiente de implementación.',
            'id_sesion' => $idSesion,
        ];
    }

    /**
     * Valida un token QR escaneado por un aprendiz y autoriza el registro de asistencia.
     *
     * Reglas de negocio que este método deberá aplicar:
     *   1. Buscar el token en la base de datos.
     *   2. Verificar que el token no haya expirado (expiracion > now()).
     *   3. Verificar que la sesión vinculada al token sigue activa.
     *   4. Verificar que el aprendiz no haya registrado asistencia ya en esta sesión.
     *   5. Retornar el ID de la sesión vinculada para que AsistenciaService pueda registrar.
     *
     * Nota: este método SOLO valida el token. El registro de asistencia
     * lo ejecuta AsistenciaService para respetar la separación de responsabilidades.
     *
     * @param string $token        Token QR escaneado por el aprendiz.
     * @param int    $idAprendiz   Identificador único del aprendiz que escanea.
     * @return array<string, mixed> ID de la sesión asociada al token, si es válido.
     */
    public function validar(string $token, int $idAprendiz): array
    {
        $token = trim($token);

        // ► AQUÍ: llamar a QrRepository->buscarToken($token)
        // ► AQUÍ: verificar que no haya expirado
        // ► AQUÍ: verificar que la sesión vinculada está activa
        // ► AQUÍ: verificar que el aprendiz no tiene asistencia en esa sesión
        //
        // Ejemplo futuro:
        //   $qr = $this->qrRepo->buscarToken($token);
        //   if (!$qr) {
        //       throw new \RuntimeException('Token QR no encontrado.', 404);
        //   }
        //   if (strtotime($qr['expiracion']) < time()) {
        //       throw new \RuntimeException('El token QR ha expirado.', 410);
        //   }
        //   if ($qr['sesion']['estado'] !== 'activa') {
        //       throw new \RuntimeException('La sesión ya no está activa.', 422);
        //   }
        //   return ['id_sesion' => $qr['id_sesion'], 'token_valido' => true];

        return [
            'success'     => true,
            'message'     => 'QrService::validar() disponible. Pendiente de implementación.',
            'id_aprendiz' => $idAprendiz,
        ];
    }

    // -------------------------------------------------------------------------
    // Métodos privados de apoyo
    // -------------------------------------------------------------------------

    /**
     * Genera un token único e impredecible para el código QR.
     *
     * Utiliza random_bytes() para garantizar entropía criptográfica.
     * El resultado es una cadena hexadecimal de 64 caracteres.
     *
     * ► AQUÍ: llamar desde generar() cuando se implemente la lógica real.
     *
     * @return string Token hexadecimal de 64 caracteres.
     */
    private function generarTokenUnico(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Calcula la fecha y hora de expiración a partir del momento actual.
     *
     * ► AQUÍ: llamar desde generar() cuando se implemente la lógica real.
     *
     * @return string Fecha y hora de expiración en formato 'Y-m-d H:i:s'.
     */
    private function calcularExpiracion(): string
    {
        return date('Y-m-d H:i:s', time() + self::EXPIRACION_SEGUNDOS);
    }
}