<?php

declare(strict_types=1);

/**
 * AttendQR – DocenteService
 *
 * Responsabilidad: contener la lógica de negocio relacionada con
 * los docentes / instructores del SENA.
 * Un "docente" es el instructor responsable de fichas de formación
 * y de abrir las sesiones de clase.
 *
 * Esta clase NO debe:
 *   - Ejecutar SQL directamente.
 *   - Conocer el router ni los Controllers.
 *   - Acceder a $_POST, $_GET ni $_REQUEST.
 *   - Imprimir JSON, HTML ni usar header() o exit.
 *
 * Flujo esperado:
 *   DocenteController → DocenteService → DocenteRepository → Modelo → Database
 *
 * Ubicación en el proyecto: Src/Services/DocenteService.php
 */
class DocenteService
{
    // -------------------------------------------------------------------------
    // Dependencias (se inyectarán cuando existan los Repositories)
    // -------------------------------------------------------------------------

    // ► AQUÍ: declarar dependencias
    //
    // Ejemplo futuro:
    //   private DocenteRepository $docenteRepo;
    //
    //   public function __construct(DocenteRepository $docenteRepo)
    //   {
    //       $this->docenteRepo = $docenteRepo;
    //   }

    // -------------------------------------------------------------------------
    // Métodos públicos
    // -------------------------------------------------------------------------

    /**
     * Obtiene los datos completos de un docente por su ID.
     *
     * Reglas de negocio:
     *   1. Verificar que el docente existe.
     *   2. Si no existe, lanzar excepción → 404 en el Controller.
     *   3. Retornar datos del docente con especialidad y fichas asignadas.
     *   4. Nunca incluir la contraseña hasheada en la respuesta.
     *
     * @param int $idDocente Identificador único del docente.
     * @return array<string, mixed>
     */
    public function consultar(int $idDocente): array
    {
        // ► AQUÍ: llamar a DocenteRepository->obtenerPorId($idDocente)
        // ► AQUÍ: si no existe, lanzar new \RuntimeException('Docente no encontrado.', 404)
        // ► AQUÍ: eliminar contrasena_hash antes de retornar

        return [
            'success'    => true,
            'message'    => 'DocenteService::consultar() disponible. Pendiente de implementación.',
            'id_docente' => $idDocente,
        ];
    }

    /**
     * Lista docentes con filtros opcionales.
     *
     * Reglas de negocio:
     *   1. Aplicar filtros de estado y especialidad.
     *   2. Retornar listado paginado ordenado por apellido.
     *   3. Nunca incluir contraseñas en el listado.
     *
     * @param string|null $estado       Filtro opcional por estado ('activo' | 'inactivo').
     * @param string|null $especialidad Filtro opcional por especialidad.
     * @return array<string, mixed>
     */
    public function listar(?string $estado = null, ?string $especialidad = null): array
    {
        // ► AQUÍ: llamar a DocenteRepository->listar($estado, $especialidad)

        return [
            'success'             => true,
            'message'             => 'DocenteService::listar() disponible. Pendiente de implementación.',
            'filtro_estado'       => $estado,
            'filtro_especialidad' => $especialidad,
        ];
    }

    /**
     * Registra un nuevo docente en el sistema.
     *
     * Reglas de negocio:
     *   1. Verificar que el documento no está duplicado.
     *   2. Verificar que el correo no está en uso.
     *   3. Hashear la contraseña inicial con password_hash().
     *   4. Crear el docente con estado 'activo'.
     *   5. Retornar los datos del docente sin la contraseña.
     *
     * @param string      $documento    Número de documento de identidad.
     * @param string      $nombres      Nombres del docente.
     * @param string      $apellidos    Apellidos del docente.
     * @param string      $correo       Correo electrónico institucional.
     * @param string|null $especialidad Especialidad o área de formación.
     * @return array<string, mixed>
     */
    public function registrar(
        string  $documento,
        string  $nombres,
        string  $apellidos,
        string  $correo,
        ?string $especialidad = null
    ): array {
        $documento = trim($documento);
        $nombres   = trim($nombres);
        $apellidos = trim($apellidos);
        $correo    = strtolower(trim($correo));

        if (!$this->esCorreoValido($correo)) {
            return ['success' => false, 'message' => "El correo '{$correo}' no tiene un formato válido."];
        }

        // ► AQUÍ: llamar a DocenteRepository->existeDocumento($documento)
        // ► AQUÍ: si existe, lanzar new \RuntimeException('El documento ya está registrado.', 409)
        // ► AQUÍ: llamar a DocenteRepository->existeCorreo($correo)
        // ► AQUÍ: si existe, lanzar new \RuntimeException('El correo ya está en uso.', 409)
        // ► AQUÍ: generar contraseña inicial y hashearla: password_hash($contrasenaInicial, PASSWORD_BCRYPT)
        // ► AQUÍ: llamar a DocenteRepository->crear($documento, $nombres, $apellidos, $correo, $hash, $especialidad)

        return [
            'success'      => true,
            'message'      => 'DocenteService::registrar() disponible. Pendiente de implementación.',
            'documento'    => $documento,
            'nombres'      => $nombres,
            'apellidos'    => $apellidos,
            'correo'       => $correo,
            'especialidad' => $especialidad,
        ];
    }

    /**
     * Actualiza los datos de un docente existente (actualización parcial).
     *
     * Reglas de negocio:
     *   1. Verificar que el docente existe.
     *   2. Si se actualiza el correo, verificar que no esté en uso por otro docente.
     *   3. Si se actualiza la contraseña, re-hashearla antes de persistir.
     *   4. Aplicar solo los campos enviados, conservar el resto.
     *
     * @param int                  $idDocente Identificador único del docente.
     * @param array<string, mixed> $datos     Campos a actualizar.
     * @return array<string, mixed>
     */
    public function actualizar(int $idDocente, array $datos): array
    {
        if (empty($datos)) {
            return ['success' => false, 'message' => 'No se recibieron datos para actualizar.'];
        }

        // Sanitizar correo si fue enviado
        if (isset($datos['correo'])) {
            $datos['correo'] = strtolower(trim((string) $datos['correo']));
            if (!$this->esCorreoValido($datos['correo'])) {
                return ['success' => false, 'message' => "El correo '{$datos['correo']}' no tiene un formato válido."];
            }
        }

        // Si viene una contraseña nueva, hashearla antes de persistir
        if (!empty($datos['contrasena'])) {
            // ► AQUÍ: $datos['contrasena_hash'] = password_hash($datos['contrasena'], PASSWORD_BCRYPT)
            // ► AQUÍ: unset($datos['contrasena']) para no persistir texto plano
        }

        // ► AQUÍ: llamar a DocenteRepository->obtenerPorId($idDocente)
        // ► AQUÍ: si no existe, lanzar new \RuntimeException('Docente no encontrado.', 404)
        // ► AQUÍ: llamar a DocenteRepository->actualizar($idDocente, $datos)

        return [
            'success'    => true,
            'message'    => 'DocenteService::actualizar() disponible. Pendiente de implementación.',
            'id_docente' => $idDocente,
            'datos'      => $datos,
        ];
    }

    /**
     * Elimina un docente del sistema.
     *
     * Reglas de negocio:
     *   1. Verificar que el docente existe.
     *   2. Verificar que no tiene sesiones activas abiertas.
     *   3. Verificar que no tiene fichas vigentes asignadas.
     *   4. Proceder con la eliminación.
     *
     * @param int $idDocente Identificador único del docente a eliminar.
     * @return array<string, mixed>
     */
    public function eliminar(int $idDocente): array
    {
        // ► AQUÍ: llamar a DocenteRepository->obtenerPorId($idDocente)
        // ► AQUÍ: llamar a SesionRepository->contarActivasPorDocente($idDocente)
        // ► AQUÍ: si tiene sesiones activas, lanzar new \RuntimeException('El docente tiene sesiones activas.', 409)
        // ► AQUÍ: llamar a DocenteRepository->eliminar($idDocente)

        return [
            'success'    => true,
            'message'    => 'DocenteService::eliminar() disponible. Pendiente de implementación.',
            'id_docente' => $idDocente,
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