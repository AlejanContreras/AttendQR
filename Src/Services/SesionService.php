<?php

declare(strict_types=1);

/**
 * AttendQR – SesionService
 *
 * Responsabilidad: lógica de negocio del ciclo de vida de las sesiones de asistencia.
 *
 * Flujo: SesionController → SesionService → SesionRepository / QrRepository → Database
 *
 * Ubicación en el proyecto: Src/Services/SesionService.php
 */
class SesionService
{
    private SesionRepository $sesionRepo;
    private QrRepository     $qrRepo;

    public function __construct()
    {
        $this->sesionRepo = new SesionRepository();
        $this->qrRepo     = new QrRepository();
    }

    /**
     * Crea una nueva sesión de asistencia para una ficha.
     *
     * Reglas de negocio:
     *   1. La ficha debe existir y estar activa.
     *   2. El docente autenticado debe ser el propietario de la ficha.
     *   3. No puede existir otra sesión ABIERTA para la misma ficha ahora.
     *      (sí se permiten múltiples sesiones en el mismo día si las anteriores están cerradas)
     *   4. hora_inicio_clase la define el docente al crear la sesión.
     *   5. Regla temporal fija:
     *        PRESENTE  → llegada desde apertura hasta H + 5 min
     *        RETARDO   → H + 6 min hasta H + 20 min
     *        Rechazado → a partir de H + 21 min
     *   6. Se genera automáticamente el primer token QR al crear la sesión.
     *
     * @param int                  $idFicha         Identificador de la ficha.
     * @param string               $horaInicioClase Hora oficial H en formato HH:MM:SS.
     * @param string               $nombreMateria   Nombre de la materia (opcional).
     * @param array<string, mixed> $usuarioActual   Datos del docente autenticado.
     * @return array<string, mixed> Datos de la sesión creada.
     * @throws \RuntimeException 404 si la ficha no existe.
     * @throws \RuntimeException 403 si el docente no es el propietario de la ficha.
     * @throws \RuntimeException 409 si la ficha está inactiva o ya tiene sesión abierta.
     */
    public function crear(
        int    $idFicha,
        string $horaInicioClase,
        string $nombreMateria,
        array  $usuarioActual
    ): array {
        $ficha = $this->sesionRepo->obtenerFichaConJornada($idFicha);

        if ($ficha === null) {
            throw new \RuntimeException('La ficha indicada no existe.', 404);
        }

        if ((int) $ficha['activa'] !== 1) {
            throw new \RuntimeException('La ficha se encuentra inactiva.', 409);
        }

        if ((int) $ficha['id_docente'] !== (int) $usuarioActual['id']) {
            throw new \RuntimeException('No tiene permisos para crear sesiones en esta ficha.', 403);
        }

        $fecha = date('Y-m-d');

        if ($this->sesionRepo->existeAbiertaParaFicha($idFicha, $fecha)) {
            throw new \RuntimeException(
                'Ya existe una sesión abierta para esta ficha. Ciérrala antes de crear una nueva.', 409
            );
        }

        // Regla temporal oficial:
        //   limite_retardo_minutos  = 5  → PRESENTE: H a H+5
        //   duracion_maxima_minutos = 20 → RETARDO: H+6 a H+20 / rechazado H+21 en adelante
        $limiteRetardoMinutos  = 5;
        $duracionMaximaMinutos = 20;

        $idSesion = $this->sesionRepo->crear(
            $idFicha,
            $fecha,
            $horaInicioClase,
            $nombreMateria ?: null,
            $limiteRetardoMinutos,
            $duracionMaximaMinutos
        );

        $this->generarPrimerToken($idSesion, 30);

        return $this->sesionRepo->obtenerPorId($idSesion)
            ?? ['id_sesion' => $idSesion, 'id_ficha' => $idFicha, 'estado_sesion' => 'abierta'];
    }

    /**
     * Obtiene los datos completos de una sesión por su ID.
     *
     * @param int $idSesion Identificador de la sesión.
     * @return array<string, mixed>
     * @throws \RuntimeException 404 si la sesión no existe.
     */
    public function consultar(int $idSesion): array
    {
        $sesion = $this->sesionRepo->obtenerDetalle($idSesion);

        if ($sesion === null) {
            throw new \RuntimeException('Sesión no encontrada.', 404);
        }

        return $sesion;
    }

    /**
     * Obtiene la sesión actualmente abierta para una ficha.
     *
     * @param int $idFicha Identificador de la ficha.
     * @return array<string, mixed>
     * @throws \RuntimeException 404 si no hay sesión abierta para la ficha.
     */
    public function sesionActivaPorFicha(int $idFicha): array
    {
        $sesion = $this->sesionRepo->obtenerActivaPorFicha($idFicha);

        if ($sesion === null) {
            throw new \RuntimeException('No hay sesión abierta para esta ficha.', 404);
        }

        return $sesion;
    }

    /**
     * Lista sesiones con filtros opcionales de ficha y estado.
     *
     * @param int|null    $idFicha Filtro por ficha.
     * @param string|null $estado  Filtro por estado ('abierta' | 'cerrada' | 'cancelada').
     * @return array<string, mixed>
     * @throws \RuntimeException 422 si el estado no es válido.
     */
    public function listar(?int $idFicha = null, ?string $estado = null): array
    {
        $estadosPermitidos = ['abierta', 'cerrada', 'cancelada'];

        if ($estado !== null && !in_array($estado, $estadosPermitidos, true)) {
            throw new \RuntimeException(
                "Estado '{$estado}' no válido. Permitidos: " . implode(', ', $estadosPermitidos) . '.',
                422
            );
        }

        $this->sesionRepo->cerrarVencidas();

        $sesiones = $this->sesionRepo->listar($idFicha, $estado);

        return [
            'sesiones' => $sesiones,
            'total'    => count($sesiones),
        ];
    }

    /**
     * Cierra una sesión abierta e invalida todos sus tokens QR activos.
     *
     * Reglas de negocio:
     *   1. La sesión debe existir.
     *   2. La sesión debe estar en estado 'abierta'.
     *   3. El docente autenticado debe ser el propietario de la ficha.
     *   4. Se invalidan los tokens QR antes de cerrar.
     *
     * @param int                  $idSesion      Identificador de la sesión.
     * @param array<string, mixed> $usuarioActual Datos del docente autenticado.
     * @return array<string, mixed>
     * @throws \RuntimeException 404 si la sesión no existe.
     * @throws \RuntimeException 403 si el docente no es el propietario.
     * @throws \RuntimeException 409 si la sesión ya no está abierta.
     */
    public function cerrar(int $idSesion, array $usuarioActual): array
    {
        $sesion = $this->sesionRepo->obtenerPorId($idSesion);

        if ($sesion === null) {
            throw new \RuntimeException('Sesión no encontrada.', 404);
        }

        if ((int) $sesion['id_docente'] !== (int) $usuarioActual['id']) {
            throw new \RuntimeException('No tiene permisos para cerrar esta sesión.', 403);
        }

        if ((string) $sesion['estado_sesion'] !== 'abierta') {
            throw new \RuntimeException('La sesión ya no está abierta.', 409);
        }

        $this->qrRepo->invalidarPorSesion($idSesion);

        $horaCierre = date('Y-m-d H:i:s.') . substr((string) microtime(true), -3);
        $this->sesionRepo->cerrar($idSesion, $horaCierre);

        return [
            'id_sesion'   => $idSesion,
            'hora_cierre' => $horaCierre,
        ];
    }

    /**
     * Obtiene todos los registros de asistencia de una sesión.
     *
     * @param int $idSesion Identificador de la sesión.
     * @return array<string, mixed>
     * @throws \RuntimeException 404 si la sesión no existe.
     */
    public function asistenciasDeSesion(int $idSesion): array
    {
        $sesion = $this->sesionRepo->obtenerPorId($idSesion);

        if ($sesion === null) {
            throw new \RuntimeException('Sesión no encontrada.', 404);
        }

        $registros = $this->sesionRepo->obtenerAsistenciasDeSesion($idSesion);

        return [
            'id_sesion'  => $idSesion,
            'fecha_sesion' => $sesion['fecha_sesion'],
            'estado_sesion' => $sesion['estado_sesion'],
            'registros'  => $registros,
            'total'      => count($registros),
        ];
    }

    /**
     * Calcula estadísticas completas de asistencia para una sesión.
     * Los aprendices sin registro se derivan del total de la ficha menos los registrados.
     *
     * @param int $idSesion Identificador de la sesión.
     * @return array<string, mixed>
     * @throws \RuntimeException 404 si la sesión no existe.
     */
    public function estadisticasDeSesion(int $idSesion): array
    {
        $stats = $this->sesionRepo->obtenerEstadisticasDeSesion($idSesion);

        if ($stats === null) {
            throw new \RuntimeException('Sesión no encontrada.', 404);
        }

        $totalAprendices  = (int) $stats['total_aprendices'];
        $totalRegistrados = (int) $stats['total_registrados'];
        $presentes        = (int) $stats['presentes'];
        $retardos         = (int) $stats['retardos'];
        $ausentesMarcados = (int) $stats['ausentes_marcados'];
        $excusas          = (int) $stats['excusas'];
        $sinRegistro      = max(0, $totalAprendices - $totalRegistrados);

        $pctAsistencia = $totalAprendices > 0
            ? round(($presentes + $retardos) / $totalAprendices * 100, 1)
            : 0.0;

        return [
            'id_sesion'       => $idSesion,
            'fecha_sesion'    => $stats['fecha_sesion'],
            'estado_sesion'   => $stats['estado_sesion'],
            'hora_inicio_clase' => $stats['hora_inicio_clase'],
            'codigo_ficha'    => $stats['codigo_ficha'],
            'nombre_programa' => $stats['nombre_programa'],
            'estadisticas'    => [
                'total_aprendices'  => $totalAprendices,
                'total_registrados' => $totalRegistrados,
                'presentes'         => $presentes,
                'retardos'          => $retardos,
                'ausentes_marcados' => $ausentesMarcados,
                'excusas'           => $excusas,
                'sin_registro'      => $sinRegistro,
                'porcentaje_asistencia' => $pctAsistencia,
            ],
        ];
    }

    /**
     * Obtiene el historial de sesiones de una ficha con totales de asistencia.
     *
     * @param int         $idFicha     Identificador de la ficha.
     * @param string|null $fechaInicio Fecha inicio del rango (Y-m-d).
     * @param string|null $fechaFin    Fecha fin del rango (Y-m-d).
     * @param string|null $estado      Filtro por estado_sesion.
     * @return array<string, mixed>
     * @throws \RuntimeException 422 si el estado no es válido.
     */
    public function historialPorFicha(
        int     $idFicha,
        ?string $fechaInicio = null,
        ?string $fechaFin    = null,
        ?string $estado      = null
    ): array {
        $estadosPermitidos = ['abierta', 'cerrada', 'cancelada'];

        if ($estado !== null && !in_array($estado, $estadosPermitidos, true)) {
            throw new \RuntimeException(
                "Estado '{$estado}' no válido. Permitidos: " . implode(', ', $estadosPermitidos) . '.',
                422
            );
        }

        $sesiones = $this->sesionRepo->obtenerHistorialPorFicha($idFicha, $fechaInicio, $fechaFin, $estado);

        return [
            'id_ficha'    => $idFicha,
            'sesiones'    => $sesiones,
            'total'       => count($sesiones),
            'filtros'     => [
                'fecha_inicio' => $fechaInicio,
                'fecha_fin'    => $fechaFin,
                'estado'       => $estado,
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // Métodos privados de apoyo
    // -------------------------------------------------------------------------

    /**
     * Genera el primer token QR al crear una sesión.
     * La lógica completa de rotación y validación pertenece al Módulo 3 (QR).
     *
     * @param int $idSesion          Identificador de la sesión recién creada.
     * @param int $rotacionSegundos  Segundos de vida del token.
     */
    private function generarPrimerToken(int $idSesion, int $rotacionSegundos): void
    {
        $token    = strtoupper(bin2hex(random_bytes(3))); // 6 chars hex: e.g. "A83F12"
        $expiraEn = date('Y-m-d H:i:s', time() + $rotacionSegundos);
        $this->qrRepo->crear($idSesion, $token, $expiraEn);
    }
}
