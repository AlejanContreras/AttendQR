<?php

declare(strict_types=1);

/**
 * AttendQR – SesionService
 *
 * Responsabilidad: gestionar el ciclo de vida de las sesiones de clase.
 * Una "sesión" representa una clase en curso a la que los aprendices
 * pueden registrar asistencia mediante el código QR generado para ella.
 *
 * Esta clase NO debe:
 *   - Ejecutar SQL directamente.
 *   - Conocer el router ni los Controllers.
 *   - Acceder a $_POST, $_GET ni $_REQUEST.
 *   - Imprimir JSON ni HTML.
 *
 * Flujo esperado cuando se integren las capas inferiores:
 *   SesionController → SesionService → SesionRepository → Modelo → Database
 *
 * Ubicación en el proyecto: Src/Services/SesionService.php
 */
class SesionService
{
    // -------------------------------------------------------------------------
    // Dependencias (se inyectarán cuando se creen los Repositories)
    // -------------------------------------------------------------------------

    // ► AQUÍ: declarar dependencias cuando existan los Repositories
    //
    // Ejemplo futuro:
    //   private SesionRepository  $sesionRepo;
    //   private FichaRepository   $fichaRepo;
    //   private DocenteRepository $docenteRepo;
    //
    //   public function __construct(
    //       SesionRepository  $sesionRepo,
    //       FichaRepository   $fichaRepo,
    //       DocenteRepository $docenteRepo
    //   ) {
    //       $this->sesionRepo  = $sesionRepo;
    //       $this->fichaRepo   = $fichaRepo;
    //       $this->docenteRepo = $docenteRepo;
    //   }

    // -------------------------------------------------------------------------
    // Métodos públicos del servicio
    // -------------------------------------------------------------------------

    /**
     * Crea una nueva sesión de clase y la deja en estado 'activa'.
     *
     * Reglas de negocio que este método deberá aplicar:
     *   1. Verificar que la materia y el docente existen y están activos.
     *   2. Verificar que el docente no tiene ya una sesión activa abierta
     *      para la misma ficha en la misma fecha (evitar duplicados).
     *   3. Registrar la sesión con estado 'activa' y la hora de apertura.
     *   4. Retornar el objeto sesión creada.
     *
     * @param int    $idMateria  Identificador único de la materia.
     * @param int    $idDocente  Identificador único del docente que abre la sesión.
     * @param string $fecha      Fecha de la sesión en formato 'Y-m-d'.
     * @return array<string, mixed> Datos de la sesión creada.
     */
    public function crear(int $idMateria, int $idDocente, string $fecha): array
    {
        // Validar que la fecha tenga el formato correcto antes de persistir
        if (!$this->esFechaValida($fecha)) {
            // ► AQUÍ: lanzar excepción de validación cuando se implemente el manejo de excepciones
            return [
                'success' => false,
                'message' => "El formato de fecha '{$fecha}' no es válido. Se esperaba Y-m-d.",
            ];
        }

        // ► AQUÍ: llamar a DocenteRepository->obtenerPorId($idDocente)
        // ► AQUÍ: verificar que el docente está activo
        // ► AQUÍ: llamar a SesionRepository->existeActivaParaDocente($idDocente, $fecha)
        // ► AQUÍ: si ya existe, lanzar new \RuntimeException('El docente ya tiene una sesión activa hoy.', 409)
        // ► AQUÍ: llamar a SesionRepository->crear($idMateria, $idDocente, $fecha, 'activa', now())
        //
        // Ejemplo futuro:
        //   $docente = $this->docenteRepo->obtenerPorId($idDocente);
        //   if ($docente['estado'] !== 'activo') {
        //       throw new \RuntimeException('El docente no está activo.', 422);
        //   }
        //   if ($this->sesionRepo->existeActivaParaDocente($idDocente, $fecha)) {
        //       throw new \RuntimeException('El docente ya tiene una sesión activa en esta fecha.', 409);
        //   }
        //   $sesion = $this->sesionRepo->crear($idMateria, $idDocente, $fecha);
        //   return $sesion;

        return [
            'success'    => true,
            'message'    => 'SesionService::crear() disponible. Pendiente de implementación.',
            'id_materia' => $idMateria,
            'id_docente' => $idDocente,
            'fecha'      => $fecha,
        ];
    }

    /**
     * Obtiene los datos completos de una sesión por su ID.
     *
     * Reglas de negocio que este método deberá aplicar:
     *   1. Buscar la sesión en la base de datos.
     *   2. Si no existe, lanzar una excepción (→ 404 en el Controller).
     *   3. Retornar los datos de la sesión enriquecidos con la información
     *      del docente, la materia y el conteo de asistencias registradas.
     *
     * @param int $idSesion Identificador único de la sesión.
     * @return array<string, mixed> Datos completos de la sesión.
     */
    public function consultar(int $idSesion): array
    {
        // ► AQUÍ: llamar a SesionRepository->obtenerDetalle($idSesion)
        // ► AQUÍ: si no existe, lanzar new \RuntimeException('Sesión no encontrada.', 404)
        //
        // Ejemplo futuro:
        //   $sesion = $this->sesionRepo->obtenerDetalle($idSesion);
        //   if (!$sesion) {
        //       throw new \RuntimeException('Sesión no encontrada.', 404);
        //   }
        //   return $sesion;

        return [
            'success'   => true,
            'message'   => 'SesionService::consultar() disponible. Pendiente de implementación.',
            'id_sesion' => $idSesion,
        ];
    }

    /**
     * Lista las sesiones del sistema aplicando filtros opcionales.
     *
     * Reglas de negocio que este método deberá aplicar:
     *   1. Aplicar los filtros recibidos (docente, fecha, estado).
     *   2. Retornar el listado paginado ordenado por fecha y hora descendentes.
     *   3. Incluir el conteo de asistencias por sesión en el listado.
     *
     * @param int|null    $idDocente Filtro opcional por docente.
     * @param string|null $fecha     Filtro opcional por fecha (Y-m-d).
     * @param string|null $estado    Filtro opcional por estado ('activa' | 'cerrada').
     * @return array<string, mixed> Listado de sesiones que cumplen los filtros.
     */
    public function listar(
        ?int    $idDocente = null,
        ?string $fecha     = null,
        ?string $estado    = null
    ): array {
        // Validar el estado si fue enviado
        $estadosPermitidos = ['activa', 'cerrada'];
        if ($estado !== null && !in_array($estado, $estadosPermitidos, true)) {
            return [
                'success' => false,
                'message' => "Estado '{$estado}' no válido. Valores permitidos: " . implode(', ', $estadosPermitidos) . '.',
            ];
        }

        // ► AQUÍ: llamar a SesionRepository->listar($idDocente, $fecha, $estado)
        //
        // Ejemplo futuro:
        //   $sesiones = $this->sesionRepo->listar($idDocente, $fecha, $estado);
        //   return ['sesiones' => $sesiones, 'total' => count($sesiones)];

        return [
            'success'          => true,
            'message'          => 'SesionService::listar() disponible. Pendiente de implementación.',
            'filtro_docente'   => $idDocente,
            'filtro_fecha'     => $fecha,
            'filtro_estado'    => $estado,
        ];
    }

    /**
     * Cierra una sesión activa e invalida su código QR asociado.
     *
     * Reglas de negocio que este método deberá aplicar:
     *   1. Verificar que la sesión existe.
     *   2. Verificar que la sesión está en estado 'activa' (no se puede cerrar dos veces).
     *   3. Registrar la hora de cierre.
     *   4. Cambiar el estado de la sesión a 'cerrada'.
     *   5. Invalidar todos los tokens QR asociados a esa sesión.
     *   6. Retornar la sesión actualizada con el resumen de asistencias.
     *
     * @param int $idSesion Identificador único de la sesión a cerrar.
     * @return array<string, mixed> Datos de la sesión cerrada con resumen de asistencias.
     */
    public function cerrar(int $idSesion): array
    {
        // ► AQUÍ: llamar a SesionRepository->obtenerPorId($idSesion)
        // ► AQUÍ: verificar que $sesion['estado'] === 'activa'
        // ► AQUÍ: si ya está cerrada, lanzar new \RuntimeException('La sesión ya fue cerrada.', 409)
        // ► AQUÍ: llamar a SesionRepository->cerrar($idSesion, date('Y-m-d H:i:s'))
        // ► AQUÍ: llamar a QrRepository->invalidarPorSesion($idSesion)
        //
        // Ejemplo futuro:
        //   $sesion = $this->sesionRepo->obtenerPorId($idSesion);
        //   if (!$sesion) {
        //       throw new \RuntimeException('Sesión no encontrada.', 404);
        //   }
        //   if ($sesion['estado'] !== 'activa') {
        //       throw new \RuntimeException('La sesión ya fue cerrada.', 409);
        //   }
        //   $sesionCerrada = $this->sesionRepo->cerrar($idSesion, date('Y-m-d H:i:s'));
        //   $this->qrRepo->invalidarPorSesion($idSesion);
        //   return $sesionCerrada;

        return [
            'success'   => true,
            'message'   => 'SesionService::cerrar() disponible. Pendiente de implementación.',
            'id_sesion' => $idSesion,
        ];
    }

    // -------------------------------------------------------------------------
    // Métodos privados de apoyo
    // -------------------------------------------------------------------------

    /**
     * Valida que una cadena tenga el formato de fecha 'Y-m-d' y sea una fecha real.
     *
     * @param string $fecha Cadena a validar.
     * @return bool true si el formato y el valor son válidos, false en caso contrario.
     */
    private function esFechaValida(string $fecha): bool
    {
        $dt = \DateTime::createFromFormat('Y-m-d', $fecha);

        // createFromFormat devuelve false si el formato no coincide.
        // checkdate verifica que la fecha sea real (p. ej. rechaza 2025-02-30).
        return $dt !== false
            && checkdate((int) $dt->format('m'), (int) $dt->format('d'), (int) $dt->format('Y'));
    }
}