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
     *   3. No puede existir otra sesión abierta para la misma ficha hoy.
     *   4. hora_inicio_clase se copia desde jornadas.hora_inicio.
     *   5. limite_retardo_minutos se copia desde jornadas.minutos_gracia.
     *   6. Se genera automáticamente el primer token QR al crear la sesión.
     *
     * @param int                  $idFicha       Identificador de la ficha.
     * @param array<string, mixed> $usuarioActual Datos del docente autenticado.
     * @return array<string, mixed> Datos de la sesión creada.
     * @throws \RuntimeException 404 si la ficha no existe.
     * @throws \RuntimeException 403 si el docente no es el propietario de la ficha.
     * @throws \RuntimeException 409 si la ficha está inactiva o ya tiene sesión abierta hoy.
     */
    public function crear(int $idFicha, array $usuarioActual): array
    {
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
            throw new \RuntimeException('Ya existe una sesión abierta para esta ficha hoy.', 409);
        }

        $horaInicioClase      = (string) $ficha['hora_inicio'];
        $limiteRetardoMinutos = (int)    $ficha['minutos_gracia'];
        $duracionMaximaMinutos = 240;

        $idSesion = $this->sesionRepo->crear(
            $idFicha,
            $fecha,
            $horaInicioClase,
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
        $token    = bin2hex(random_bytes(32));
        $expiraEn = date('Y-m-d H:i:s', time() + $rotacionSegundos);
        $this->qrRepo->crear($idSesion, $token, $expiraEn);
    }
}
