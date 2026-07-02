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
    public function registrarPorQr(string $tokenValor, array $usuarioActual): array
    {
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
            $minutosRetardo
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

        foreach ($registros as $r) {
            match ($r['estado'] ?? '') {
                'presente' => $presentes++,
                'retardo'  => $retardos++,
                'ausente'  => $ausentes++,
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
     * Clasifica automáticamente la asistencia y calcula minutos_retardo.
     *
     * Reglas oficiales:
     *   PRESENTE → hora_registro <= hora_inicio_clase + limite_retardo_minutos
     *   RETARDO  → hora_registro >  hora_inicio_clase + limite_retardo_minutos
     *
     *   minutos_retardo = minutos transcurridos desde hora_inicio_clase.
     *                     0 si estado = 'presente'.
     *
     * La hora de referencia es siempre del servidor (parámetro $horaRegistro).
     * Nunca se usa información enviada por el cliente.
     *
     * @param array<string, mixed> $sesion       Datos de la sesión (fecha_sesion, hora_inicio_clase, limite_retardo_minutos).
     * @param string               $horaRegistro Timestamp del servidor en formato Y-m-d H:i:s.
     * @return array{string, int}  [estado, minutos_retardo]
     */
    private function clasificar(array $sesion, string $horaRegistro): array
    {
        $tsInicioClase = strtotime(
            $sesion['fecha_sesion'] . ' ' . $sesion['hora_inicio_clase']
        );
        $tsRegistro       = strtotime($horaRegistro);
        $limiteRetardoSeg = (int) $sesion['limite_retardo_minutos'] * 60;

        if ($tsRegistro <= $tsInicioClase + $limiteRetardoSeg) {
            return ['presente', 0];
        }

        // Minutos completos desde hora_inicio_clase (incluye los minutos de gracia)
        $minutosDesdeInicio = (int) floor(($tsRegistro - $tsInicioClase) / 60);

        return ['retardo', $minutosDesdeInicio];
    }
}
