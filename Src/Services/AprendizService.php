<?php

declare(strict_types=1);

/**
 * AttendQR – AprendizService
 *
 * Responsabilidad: contener la lógica de negocio relacionada con los
 * aprendices del sistema (estudiantes inscritos en fichas de formación).
 *
 * Esta clase NO debe:
 *   - Ejecutar SQL directamente.
 *   - Conocer el router ni los Controllers.
 *   - Acceder a $_POST, $_GET ni $_REQUEST.
 *   - Imprimir JSON, HTML ni usar header() o exit.
 *
 * Flujo esperado:
 *   AprendizController → AprendizService → AprendizRepository → Modelo → Database
 *
 * Ubicación en el proyecto: Src/Services/AprendizService.php
 */
class AprendizService
{
    // -------------------------------------------------------------------------
    // Dependencias (se inyectarán cuando existan los Repositories)
    // -------------------------------------------------------------------------

    // ► AQUÍ: declarar dependencias
    //
    // Ejemplo futuro:
    //   private AprendizRepository $aprendizRepo;
    //   private FichaRepository    $fichaRepo;
    //
    //   public function __construct(AprendizRepository $aprendizRepo, FichaRepository $fichaRepo)
    //   {
    //       $this->aprendizRepo = $aprendizRepo;
    //       $this->fichaRepo    = $fichaRepo;
    //   }

    // -------------------------------------------------------------------------
    // Métodos públicos
    // -------------------------------------------------------------------------

    /**
     * Obtiene los datos completos de un aprendiz por su ID.
     *
     * Reglas de negocio:
     *   1. Verificar que el aprendiz existe.
     *   2. Si no existe, lanzar excepción → 404 en el Controller.
     *   3. Retornar datos del aprendiz incluyendo ficha y estado de asistencia.
     *
     * @param int $idAprendiz Identificador único del aprendiz.
     * @return array<string, mixed>
     */
    public function consultar(int $idAprendiz): array
    {
        // ► AQUÍ: llamar a AprendizRepository->obtenerPorId($idAprendiz)
        // ► AQUÍ: si no existe, lanzar new \RuntimeException('Aprendiz no encontrado.', 404)

        return [
            'success'     => true,
            'message'     => 'AprendizService::consultar() disponible. Pendiente de implementación.',
            'id_aprendiz' => $idAprendiz,
        ];
    }

    /**
     * Lista aprendices con filtros opcionales.
     *
     * Reglas de negocio:
     *   1. Aplicar filtros de ficha, estado y documento.
     *   2. Retornar listado paginado ordenado por apellido.
     *
     * @param int|null    $idFicha   Filtro opcional por ficha.
     * @param string|null $estado    Filtro opcional por estado ('activo' | 'inactivo').
     * @param string|null $documento Filtro opcional por número de documento.
     * @return array<string, mixed>
     */
    public function listar(?int $idFicha = null, ?string $estado = null, ?string $documento = null): array
    {
        // ► AQUÍ: llamar a AprendizRepository->listar($idFicha, $estado, $documento)

        return [
            'success'        => true,
            'message'        => 'AprendizService::listar() disponible. Pendiente de implementación.',
            'filtro_ficha'   => $idFicha,
            'filtro_estado'  => $estado,
            'filtro_doc'     => $documento,
        ];
    }

    /**
     * Registra un nuevo aprendiz y lo asocia a una ficha de formación.
     *
     * Reglas de negocio:
     *   1. Verificar que el documento no esté duplicado.
     *   2. Verificar que el correo no esté en uso.
     *   3. Verificar que la ficha existe y está activa.
     *   4. Crear el aprendiz con estado 'activo'.
     *   5. Retornar el aprendiz creado.
     *
     * @param string $documento Número de documento de identidad.
     * @param string $nombres   Nombres del aprendiz.
     * @param string $apellidos Apellidos del aprendiz.
     * @param string $correo    Correo electrónico institucional.
     * @param int    $idFicha   Ficha a la que se vincula.
     * @return array<string, mixed>
     */
    public function registrar(string $documento, string $nombres, string $apellidos, string $correo, int $idFicha): array
    {
        $documento = trim($documento);
        $nombres   = trim($nombres);
        $apellidos = trim($apellidos);
        $correo    = strtolower(trim($correo));

        if (!$this->esCorreoValido($correo)) {
            return ['success' => false, 'message' => "El correo '{$correo}' no tiene un formato válido."];
        }

        // ► AQUÍ: llamar a AprendizRepository->existeDocumento($documento)
        // ► AQUÍ: si existe, lanzar new \RuntimeException('El documento ya está registrado.', 409)
        // ► AQUÍ: llamar a AprendizRepository->existeCorreo($correo)
        // ► AQUÍ: si existe, lanzar new \RuntimeException('El correo ya está en uso.', 409)
        // ► AQUÍ: llamar a FichaRepository->obtenerPorId($idFicha)
        // ► AQUÍ: verificar que $ficha['estado'] === 'activa'
        // ► AQUÍ: llamar a AprendizRepository->crear($documento, $nombres, $apellidos, $correo, $idFicha)

        return [
            'success'    => true,
            'message'    => 'AprendizService::registrar() disponible. Pendiente de implementación.',
            'documento'  => $documento,
            'nombres'    => $nombres,
            'apellidos'  => $apellidos,
            'correo'     => $correo,
            'id_ficha'   => $idFicha,
        ];
    }

    /**
     * Actualiza los datos de un aprendiz existente (actualización parcial).
     *
     * Reglas de negocio:
     *   1. Verificar que el aprendiz existe.
     *   2. Si se actualiza el correo, verificar que no esté en uso por otro aprendiz.
     *   3. Aplicar solo los campos enviados, conservar el resto.
     *
     * @param int                  $idAprendiz Identificador único del aprendiz.
     * @param array<string, mixed> $datos      Campos a actualizar.
     * @return array<string, mixed>
     */
    public function actualizar(int $idAprendiz, array $datos): array
    {
        if (empty($datos)) {
            return ['success' => false, 'message' => 'No se recibieron datos para actualizar.'];
        }

        // Sanitizar el correo si fue enviado
        if (isset($datos['correo'])) {
            $datos['correo'] = strtolower(trim((string) $datos['correo']));
            if (!$this->esCorreoValido($datos['correo'])) {
                return ['success' => false, 'message' => "El correo '{$datos['correo']}' no tiene un formato válido."];
            }
        }

        // ► AQUÍ: llamar a AprendizRepository->obtenerPorId($idAprendiz)
        // ► AQUÍ: si no existe, lanzar new \RuntimeException('Aprendiz no encontrado.', 404)
        // ► AQUÍ: llamar a AprendizRepository->actualizar($idAprendiz, $datos)

        return [
            'success'     => true,
            'message'     => 'AprendizService::actualizar() disponible. Pendiente de implementación.',
            'id_aprendiz' => $idAprendiz,
            'datos'       => $datos,
        ];
    }

    /**
     * Elimina un aprendiz del sistema.
     *
     * Reglas de negocio:
     *   1. Verificar que el aprendiz existe.
     *   2. Verificar que no tiene asistencias activas registradas.
     *   3. Proceder con la eliminación.
     *
     * @param int $idAprendiz Identificador único del aprendiz a eliminar.
     * @return array<string, mixed>
     */
    public function eliminar(int $idAprendiz): array
    {
        // ► AQUÍ: llamar a AprendizRepository->obtenerPorId($idAprendiz)
        // ► AQUÍ: llamar a AsistenciaRepository->contarPorAprendiz($idAprendiz)
        // ► AQUÍ: si tiene registros, decidir si eliminar en cascada o lanzar excepción
        // ► AQUÍ: llamar a AprendizRepository->eliminar($idAprendiz)

        return [
            'success'     => true,
            'message'     => 'AprendizService::eliminar() disponible. Pendiente de implementación.',
            'id_aprendiz' => $idAprendiz,
        ];
    }

    // -------------------------------------------------------------------------
    // Métodos privados de apoyo
    // -------------------------------------------------------------------------

    /**
     * Valida el formato básico de un correo electrónico.
     *
     * @param string $correo Correo a validar.
     * @return bool true si el formato es válido.
     */
    private function esCorreoValido(string $correo): bool
    {
        return filter_var($correo, FILTER_VALIDATE_EMAIL) !== false;
    }
}