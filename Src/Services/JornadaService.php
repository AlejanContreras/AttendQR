<?php

declare(strict_types=1);

/**
 * AttendQR – JornadaService
 *
 * Responsabilidad: contener la lógica de negocio relacionada con
 * las jornadas académicas del SENA (mañana, tarde, noche, etc.).
 * Una "jornada" define el turno en que se desarrolla la formación.
 *
 * Esta clase NO debe:
 *   - Ejecutar SQL directamente.
 *   - Conocer el router ni los Controllers.
 *   - Acceder a $_POST, $_GET ni $_REQUEST.
 *   - Imprimir JSON, HTML ni usar header() o exit.
 *
 * Flujo esperado:
 *   JornadaController → JornadaService → JornadaRepository → Modelo → Database
 *
 * Ubicación en el proyecto: Src/Services/JornadaService.php
 */
class JornadaService
{
    // -------------------------------------------------------------------------
    // Dependencias (se inyectarán cuando existan los Repositories)
    // -------------------------------------------------------------------------

    // ► AQUÍ: declarar dependencias
    //
    // Ejemplo futuro:
    //   private JornadaRepository $jornadaRepo;
    //
    //   public function __construct(JornadaRepository $jornadaRepo)
    //   {
    //       $this->jornadaRepo = $jornadaRepo;
    //   }

    // -------------------------------------------------------------------------
    // Métodos públicos
    // -------------------------------------------------------------------------

    /**
     * Obtiene los datos completos de una jornada por su ID.
     *
     * Reglas de negocio:
     *   1. Verificar que la jornada existe.
     *   2. Si no existe, lanzar excepción → 404 en el Controller.
     *   3. Retornar la jornada con nombre, horarios y estado.
     *
     * @param int $idJornada Identificador único de la jornada.
     * @return array<string, mixed>
     */
    public function consultar(int $idJornada): array
    {
        // ► AQUÍ: llamar a JornadaRepository->obtenerPorId($idJornada)
        // ► AQUÍ: si no existe, lanzar new \RuntimeException('Jornada no encontrada.', 404)

        return [
            'success'    => true,
            'message'    => 'JornadaService::consultar() disponible. Pendiente de implementación.',
            'id_jornada' => $idJornada,
        ];
    }

    /**
     * Lista jornadas con filtro opcional de estado.
     *
     * Reglas de negocio:
     *   1. Aplicar filtro de estado si fue enviado.
     *   2. Retornar listado ordenado por hora de inicio ascendente.
     *
     * @param string|null $estado Filtro opcional ('activa' | 'inactiva').
     * @return array<string, mixed>
     */
    public function listar(?string $estado = null): array
    {
        // ► AQUÍ: llamar a JornadaRepository->listar($estado)

        return [
            'success'       => true,
            'message'       => 'JornadaService::listar() disponible. Pendiente de implementación.',
            'filtro_estado' => $estado,
        ];
    }

    /**
     * Crea una nueva jornada académica.
     *
     * Reglas de negocio:
     *   1. Verificar que el nombre no está duplicado.
     *   2. Si se envían horarios, verificar que hora_fin > hora_inicio.
     *   3. Persistir la jornada con estado 'activa'.
     *   4. Retornar la jornada creada.
     *
     * @param string      $nombre      Nombre de la jornada (p. ej. 'Mañana').
     * @param string|null $horaInicio  Hora de inicio en formato 'HH:MM'.
     * @param string|null $horaFin     Hora de fin en formato 'HH:MM'.
     * @return array<string, mixed>
     */
    public function crear(string $nombre, ?string $horaInicio = null, ?string $horaFin = null): array
    {
        $nombre = trim($nombre);

        if ($nombre === '') {
            return ['success' => false, 'message' => 'El nombre de la jornada no puede estar vacío.'];
        }

        // Validar coherencia de horarios si ambos fueron enviados
        if ($horaInicio !== null && $horaFin !== null) {
            if (!$this->esHorarioCoherente($horaInicio, $horaFin)) {
                return [
                    'success' => false,
                    'message' => 'La hora de fin debe ser posterior a la hora de inicio.',
                ];
            }
        }

        // ► AQUÍ: llamar a JornadaRepository->existeNombre($nombre)
        // ► AQUÍ: si existe, lanzar new \RuntimeException('Ya existe una jornada con ese nombre.', 409)
        // ► AQUÍ: llamar a JornadaRepository->crear($nombre, $horaInicio, $horaFin)

        return [
            'success'     => true,
            'message'     => 'JornadaService::crear() disponible. Pendiente de implementación.',
            'nombre'      => $nombre,
            'hora_inicio' => $horaInicio,
            'hora_fin'    => $horaFin,
        ];
    }

    /**
     * Actualiza los datos de una jornada existente (actualización parcial).
     *
     * Reglas de negocio:
     *   1. Verificar que la jornada existe.
     *   2. Si se cambia el nombre, verificar que no esté duplicado.
     *   3. Si se actualizan horarios, verificar coherencia hora_fin > hora_inicio.
     *   4. Aplicar solo los campos enviados, conservar el resto.
     *
     * @param int                  $idJornada Identificador único de la jornada.
     * @param array<string, mixed> $datos     Campos a actualizar.
     * @return array<string, mixed>
     */
    public function actualizar(int $idJornada, array $datos): array
    {
        if (empty($datos)) {
            return ['success' => false, 'message' => 'No se recibieron datos para actualizar.'];
        }

        // Validar coherencia de horarios si ambos están presentes en la actualización
        if (isset($datos['hora_inicio'], $datos['hora_fin'])) {
            if (!$this->esHorarioCoherente((string) $datos['hora_inicio'], (string) $datos['hora_fin'])) {
                return [
                    'success' => false,
                    'message' => 'La hora de fin debe ser posterior a la hora de inicio.',
                ];
            }
        }

        // ► AQUÍ: llamar a JornadaRepository->obtenerPorId($idJornada)
        // ► AQUÍ: si no existe, lanzar new \RuntimeException('Jornada no encontrada.', 404)
        // ► AQUÍ: si viene nombre, llamar a JornadaRepository->existeNombre($datos['nombre'])
        // ► AQUÍ: llamar a JornadaRepository->actualizar($idJornada, $datos)

        return [
            'success'    => true,
            'message'    => 'JornadaService::actualizar() disponible. Pendiente de implementación.',
            'id_jornada' => $idJornada,
            'datos'      => $datos,
        ];
    }

    /**
     * Elimina una jornada del sistema.
     *
     * Reglas de negocio:
     *   1. Verificar que la jornada existe.
     *   2. Verificar que no tiene fichas activas vinculadas.
     *   3. Proceder con la eliminación.
     *
     * @param int $idJornada Identificador único de la jornada a eliminar.
     * @return array<string, mixed>
     */
    public function eliminar(int $idJornada): array
    {
        // ► AQUÍ: llamar a JornadaRepository->obtenerPorId($idJornada)
        // ► AQUÍ: llamar a FichaRepository->contarActivasPorJornada($idJornada)
        // ► AQUÍ: si tiene fichas activas, lanzar new \RuntimeException('La jornada tiene fichas activas.', 409)
        // ► AQUÍ: llamar a JornadaRepository->eliminar($idJornada)

        return [
            'success'    => true,
            'message'    => 'JornadaService::eliminar() disponible. Pendiente de implementación.',
            'id_jornada' => $idJornada,
        ];
    }

    // -------------------------------------------------------------------------
    // Métodos privados de apoyo
    // -------------------------------------------------------------------------

    /**
     * Verifica que la hora de fin sea posterior a la hora de inicio.
     * Acepta el formato 'HH:MM' (24 horas).
     *
     * @param string $horaInicio Hora de inicio ('HH:MM').
     * @param string $horaFin    Hora de fin ('HH:MM').
     * @return bool true si la hora de fin es posterior a la de inicio.
     */
    private function esHorarioCoherente(string $horaInicio, string $horaFin): bool
    {
        // Convertir a minutos desde medianoche para comparar de forma segura
        $minutosInicio = $this->horaAMinutos($horaInicio);
        $minutosFin    = $this->horaAMinutos($horaFin);

        // Si alguna hora no es válida, dejar que el Repository valide el formato
        if ($minutosInicio === null || $minutosFin === null) {
            return true;
        }

        return $minutosFin > $minutosInicio;
    }

    /**
     * Convierte una cadena 'HH:MM' a minutos desde medianoche.
     * Retorna null si el formato no es válido.
     *
     * @param string $hora Hora en formato 'HH:MM'.
     * @return int|null Minutos desde medianoche, o null si el formato es inválido.
     */
    private function horaAMinutos(string $hora): ?int
    {
        $partes = explode(':', trim($hora));

        if (count($partes) !== 2) {
            return null;
        }

        $horas   = (int) $partes[0];
        $minutos = (int) $partes[1];

        if ($horas < 0 || $horas > 23 || $minutos < 0 || $minutos > 59) {
            return null;
        }

        return $horas * 60 + $minutos;
    }
}