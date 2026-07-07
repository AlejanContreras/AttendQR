<?php

declare(strict_types=1);

/**
 * AttendQR – AsistenciaService
 *
 * Responsabilidad: lógica de negocio del módulo de asistencias.
 *
 * Flujo principal (registro QR):
 *   AsistenciaController
 *     → AsistenciaService::registrarPorQr()
 *       → QrService::validar()          (valida token, incrementa uso)
 *       → SesionRepository::obtenerPorId()  (obtiene sesión y parámetros temporales)
 *       → AsistenciaRepository::aprendizPerteneceAFicha()
 *       → AsistenciaRepository::existeEnSesion()
 *       → AsistenciaService::clasificar()  (PRESENTE / RETARDO)
 *       → AsistenciaRepository::crear()
 *
 * Ubicación en el proyecto: Src/Services/AsistenciaService.php
 */
class AsistenciaService
{
    private const RADIO_VALIDACION_METROS = 30;
    private const MAX_ACCURACY_METROS     = 50;

    private AsistenciaRepository $asistenciaRepo;
    private SesionRepository     $sesionRepo;
    private QrService            $qrService;

    public function __construct()
    {
        $this->asistenciaRepo = new AsistenciaRepository();
        $this->sesionRepo     = new SesionRepository();
        $this->qrService      = new QrService();
    }

    /**
     * Registra la asistencia de un aprendiz mediante un token QR.
     *
     * Flujo oficial:
     *   1. Validar token QR (existencia, activo, expiración, sesión abierta).
     *   2. Obtener la sesión vinculada al token.
     *   3. Verificar que el aprendiz pertenezca a la ficha de la sesión.
     *   4. Verificar que no haya registrado asistencia previa en esta sesión.
     *   5. Clasificar automáticamente: PRESENTE o RETARDO.
     *   6. Calcular minutos_retardo desde hora_inicio_clase.
     *   7. Insertar el registro con todos los campos requeridos.
     *
     * Nunca se confía en hora ni estado enviados por el cliente.
     *
     * @param string               $tokenValor    Token QR escaneado por el aprendiz.
     * @param array<string, mixed> $usuarioActual Datos del aprendiz desde $_SESSION['usuario'].
     * @return array<string, mixed> Registro de asistencia creado.
     * @throws \RuntimeException 404 si el token no existe o la sesión no existe.
     * @throws \RuntimeException 403 si el aprendiz no pertenece a la ficha.
     * @throws \RuntimeException 409 si ya registró asistencia en esta sesión.
     * @throws \RuntimeException 410 si el token expiró o fue rotado.
     * @throws \RuntimeException 422 si la sesión no está abierta.
     */
    public function registrarPorQr(
        string $tokenValor,
        array  $usuarioActual,
        ?float $latitud  = null,
        ?float $longitud = null,
        ?float $accuracy = null
    ): array {
        $idAprendiz = (int) $usuarioActual['id'];

        // Paso 1 — Validar token (reutiliza QrService: activo, expiración, sesión abierta)
        $tokenData = $this->qrService->validar($tokenValor, $idAprendiz);

        $idSesion = $tokenData['id_sesion'];
        $idToken  = $tokenData['id_token'];

        // Paso 2 — Obtener sesión con parámetros temporales para clasificar
        $sesion = $this->sesionRepo->obtenerPorId($idSesion);

        if ($sesion === null) {
            throw new \RuntimeException('Sesión no encontrada.', 404);
        }

        // Paso 3 — Verificar que el aprendiz pertenece a la ficha de esta sesión
        if (!$this->asistenciaRepo->aprendizPerteneceAFicha($idAprendiz, (int) $sesion['id_ficha'])) {
            throw new \RuntimeException(
                'No pertenece a la ficha de esta sesión y no puede registrar asistencia.',
                403
            );
        }

        // Paso 4 — Prevenir doble registro
        if ($this->asistenciaRepo->existeEnSesion($idAprendiz, $idSesion)) {
            throw new \RuntimeException('Ya registró su asistencia en esta sesión.', 409);
        }

        // Paso 4b — Validar geolocalización si la sesión lo exige
        $ubicacionValida = false;
        if ((int) ($sesion['ubicacion_activa'] ?? 0) === 1) {
            if ($latitud === null || $longitud === null) {
                throw new \RuntimeException('Esta sesión requiere validación de ubicación.', 428);
            }

            if ($accuracy !== null && $accuracy > self::MAX_ACCURACY_METROS) {
                throw new \RuntimeException(
                    sprintf(
                        'La precisión GPS es demasiado baja (%.0f m). Espera mejor señal e intenta de nuevo.',
                        $accuracy
                    ),
                    422
                );
            }

            $distancia = $this->haversine(
                (float) $sesion['lat_docente'],
                (float) $sesion['lng_docente'],
                $latitud,
                $longitud
            );

            if ($distancia > self::RADIO_VALIDACION_METROS) {
                throw new \RuntimeException(
                    sprintf(
                        'Estás a %.0f m del aula. Solo se acepta registro dentro de %d m.',
                        $distancia,
                        self::RADIO_VALIDACION_METROS
                    ),
                    451
                );
            }

            $ubicacionValida = true;
        }

        // Paso 5 y 6 — Clasificar y calcular minutos de retardo
        $horaRegistro   = date('Y-m-d H:i:s');
        [$estado, $minutosRetardo] = $this->clasificar($sesion, $horaRegistro);

        // Paso 7 — Insertar
        $idAsistencia = $this->asistenciaRepo->crear(
            $idAprendiz,
            $idSesion,
            $idToken,
            $estado,
            $horaRegistro,
            $minutosRetardo,
            $latitud,
            $longitud,
            $ubicacionValida
        );

        return [
            'id_asistencia'   => $idAsistencia,
            'id_aprendiz'     => $idAprendiz,
            'id_sesion'       => $idSesion,
            'estado'          => $estado,
            'hora_registro'   => $horaRegistro,
            'minutos_retardo' => $minutosRetardo,
        ];
    }

    /**
     * Obtiene los datos completos de un registro de asistencia por su ID.
     *
     * @param int $idAsistencia Identificador del registro.
     * @return array<string, mixed>
     * @throws \RuntimeException 404 si el registro no existe.
     */
    public function consultar(int $idAsistencia): array
    {
        $asistencia = $this->asistenciaRepo->obtenerPorId($idAsistencia);

        if ($asistencia === null) {
            throw new \RuntimeException('Registro de asistencia no encontrado.', 404);
        }

        return $asistencia;
    }

    /**
     * Retorna el historial de asistencias de un aprendiz con filtros opcionales de fecha.
     *
     * @param int         $idAprendiz  Identificador del aprendiz.
     * @param string|null $fechaInicio Inicio del rango (Y-m-d).
     * @param string|null $fechaFin    Fin del rango (Y-m-d).
     * @return array<string, mixed>
     */
    public function historial(
        int     $idAprendiz,
        ?string $fechaInicio = null,
        ?string $fechaFin    = null
    ): array {
        $registros = $this->asistenciaRepo->historialAprendiz($idAprendiz, $fechaInicio, $fechaFin);

        $presentes = 0;
        $retardos  = 0;
        $ausentes  = 0;
        $excusas   = 0;

        foreach ($registros as $r) {
            match ($r['estado'] ?? '') {
                'presente' => $presentes++,
                'retardo'  => $retardos++,
                'ausente'  => $ausentes++,
                'excusa'   => $excusas++,
                default    => null,
            };
        }

        return [
            'id_aprendiz' => $idAprendiz,
            'registros'   => $registros,
            'total'       => count($registros),
            'resumen'     => [
                'presentes' => $presentes,
                'retardos'  => $retardos,
                'ausentes'  => $ausentes,
                'excusas'   => $excusas,
            ],
            'filtros'     => [
                'fecha_inicio' => $fechaInicio,
                'fecha_fin'    => $fechaFin,
            ],
        ];
    }

    /**
     * Verifica si un aprendiz ya registró asistencia en una sesión determinada.
     * Endpoint de consulta rápida para el docente.
     *
     * @param int $idAprendiz Identificador del aprendiz.
     * @param int $idSesion   Identificador de la sesión.
     * @return array<string, mixed>
     */
    public function validar(int $idAprendiz, int $idSesion): array
    {
        $yaRegistrado = $this->asistenciaRepo->existeEnSesion($idAprendiz, $idSesion);

        return [
            'id_aprendiz'   => $idAprendiz,
            'id_sesion'     => $idSesion,
            'ya_registrado' => $yaRegistrado,
        ];
    }

    /**
     * Cambia el estado de un registro de asistencia.
     * Solo docentes pueden ejecutar esta acción.
     * Únicamente se permiten los cambios coherentes: ausente ↔ excusa.
     * No se puede cambiar desde presente o retardo.
     *
     * @param int    $idAsistencia Identificador del registro.
     * @param string $nuevoEstado  Estado destino ('ausente' | 'excusa').
     * @param string $observacion  Observación opcional.
     * @param array<string, mixed> $usuario Usuario autenticado desde $_SESSION.
     * @return array<string, mixed> Registro actualizado.
     * @throws \RuntimeException 403 si no es docente.
     * @throws \RuntimeException 404 si el registro no existe.
     * @throws \RuntimeException 422 si el cambio no está permitido.
     */
    public function cambiarEstado(int $idAsistencia, string $nuevoEstado, string $observacion, array $usuario): array
    {
        if (($usuario['rol'] ?? '') !== 'docente') {
            throw new \RuntimeException('Solo los docentes pueden cambiar el estado de asistencia.', 403);
        }

        $estadosValidos = ['ausente', 'excusa'];
        if (!in_array($nuevoEstado, $estadosValidos, true)) {
            throw new \RuntimeException(
                "Estado '{$nuevoEstado}' no válido. Solo se permite: ausente, excusa.",
                422
            );
        }

        $asistencia = $this->asistenciaRepo->obtenerPorId($idAsistencia);
        if ($asistencia === null) {
            throw new \RuntimeException('Registro de asistencia no encontrado.', 404);
        }

        $estadoActual = $asistencia['estado'] ?? '';

        // Reglas de negocio: solo cambios coherentes
        $transicionesPermitidas = [
            'ausente' => 'excusa',
            'excusa'  => 'ausente',
        ];

        if (!isset($transicionesPermitidas[$estadoActual]) || $transicionesPermitidas[$estadoActual] !== $nuevoEstado) {
            throw new \RuntimeException(
                "No se puede cambiar de '{$estadoActual}' a '{$nuevoEstado}'. Solo se permite: ausente → excusa y excusa → ausente.",
                422
            );
        }

        $this->asistenciaRepo->actualizarEstado(
            $idAsistencia,
            $nuevoEstado,
            $observacion !== '' ? $observacion : null
        );

        return $this->asistenciaRepo->obtenerPorId($idAsistencia) ?? $asistencia;
    }

    /**
     * Genera los datos del reporte de asistencia para exportación.
     * Respeta el rol del usuario: docente → sus sesiones, aprendiz → su historial.
     *
     * @param array<string, mixed> $usuario     Usuario autenticado.
     * @param int|null             $idFicha     Filtro por ficha (solo docente).
     * @param string|null          $fechaInicio Inicio del rango (Y-m-d).
     * @param string|null          $fechaFin    Fin del rango (Y-m-d).
     * @return array<int, array<string, mixed>>
     */
    public function generarReporte(array $usuario, ?int $idFicha, ?string $fechaInicio, ?string $fechaFin): array
    {
        $rol        = $usuario['rol'] ?? '';
        $idDocente  = ($rol === 'docente')  ? (int) $usuario['id'] : null;
        $idAprendiz = ($rol === 'aprendiz') ? (int) $usuario['id'] : null;

        return $this->asistenciaRepo->listarParaExportar(
            $idDocente,
            $idAprendiz,
            $idFicha,
            $fechaInicio,
            $fechaFin
        );
    }

    /**
     * Elimina un registro de asistencia por su ID.
     *
     * @param int $idAsistencia Identificador del registro a eliminar.
     * @return array<string, mixed>
     * @throws \RuntimeException 404 si el registro no existe.
     */
    public function eliminar(int $idAsistencia): array
    {
        $asistencia = $this->asistenciaRepo->obtenerPorId($idAsistencia);

        if ($asistencia === null) {
            throw new \RuntimeException('Registro de asistencia no encontrado.', 404);
        }

        $this->asistenciaRepo->eliminar($idAsistencia);

        return ['message' => 'Asistencia eliminada correctamente.'];
    }

    // -------------------------------------------------------------------------
    // Métodos privados de apoyo
    // -------------------------------------------------------------------------

    /**
     * Calcula la distancia en metros entre dos coordenadas usando la fórmula de Haversine.
     * Toda la validación geográfica ocurre en el backend; el cliente solo envía coordenadas.
     *
     * @param float $lat1 Latitud punto A (docente).
     * @param float $lng1 Longitud punto A (docente).
     * @param float $lat2 Latitud punto B (aprendiz).
     * @param float $lng2 Longitud punto B (aprendiz).
     * @return float Distancia en metros.
     */
    private function haversine(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $r    = 6371000.0; // Radio medio de la Tierra en metros
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a    = sin($dLat / 2) ** 2
              + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return 2.0 * $r * asin(sqrt($a));
    }

    /**
     * Clasifica automáticamente la asistencia aplicando la regla temporal oficial.
     *
     * Regla oficial (H = hora_inicio_clase):
     *   PRESENTE  → llegada desde apertura hasta H + limite_retardo_minutos (5 min)
     *   RETARDO   → H + limite_retardo_minutos + 1 min hasta H + duracion_maxima_minutos (20 min)
     *   Rechazado → llegada después de H + duracion_maxima_minutos (lanza excepción 422)
     *
     *   minutos_retardo = minutos desde H. 0 si estado = 'presente'.
     *
     * La hora de referencia es siempre del servidor (parámetro $horaRegistro).
     * Nunca se usa información enviada por el cliente.
     *
     * @param array<string, mixed> $sesion       Datos de la sesión con fecha_sesion, hora_inicio_clase,
     *                                           limite_retardo_minutos y duracion_maxima_minutos.
     * @param string               $horaRegistro Timestamp del servidor en formato Y-m-d H:i:s.
     * @return array{string, int}  [estado, minutos_retardo]
     * @throws \RuntimeException 422 si el tiempo de registro ha expirado.
     */
    private function clasificar(array $sesion, string $horaRegistro): array
    {
        $fechaSesion     = trim((string) ($sesion['fecha_sesion']     ?? ''));
        $horaInicioClase = trim((string) ($sesion['hora_inicio_clase'] ?? ''));

        if ($fechaSesion === '' || $horaInicioClase === '') {
            error_log('[AsistenciaService] hora_inicio_clase o fecha_sesion vacíos. Sesión: ' . json_encode($sesion));
            throw new \RuntimeException('Datos de sesión incompletos para calcular asistencia.', 422);
        }

        // Normalizar hora a HH:MM:SS si viene como HH:MM
        if (strlen($horaInicioClase) === 5) {
            $horaInicioClase .= ':00';
        }

        $tsInicioClase = strtotime($fechaSesion . ' ' . $horaInicioClase);
        $tsRegistro    = strtotime($horaRegistro);

        if ($tsInicioClase === false || $tsRegistro === false) {
            error_log("[AsistenciaService] strtotime falló. fecha='$fechaSesion' hora='$horaInicioClase' registro='$horaRegistro'");
            throw new \RuntimeException('No se pudo calcular el tiempo de asistencia. Contacte al administrador.', 422);
        }

        $minutosDesdeH  = (int) floor(($tsRegistro - $tsInicioClase) / 60);

        $limitePresente = (int) ($sesion['limite_retardo_minutos']  ?? 5);
        $limiteRetardo  = (int) ($sesion['duracion_maxima_minutos'] ?? 20);

        if ($limitePresente <= 0) $limitePresente = 5;
        if ($limiteRetardo  <= 0) $limiteRetardo  = 20;

        // Después del límite de retardo ya no se acepta el registro
        if ($minutosDesdeH > $limiteRetardo) {
            throw new \RuntimeException(
                "El tiempo de registro ha expirado. Solo se acepta asistencia hasta H+{$limiteRetardo} minutos.",
                422
            );
        }

        // Dentro del rango PRESENTE (puede ser antes de H, que da minutosDesdeH negativo)
        if ($minutosDesdeH <= $limitePresente) {
            return ['presente', 0];
        }

        // Entre limite_presente+1 y limite_retardo → RETARDO
        return ['retardo', $minutosDesdeH];
    }
}
