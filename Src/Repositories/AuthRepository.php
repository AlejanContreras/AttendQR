<?php

declare(strict_types=1);

/**
 * AttendQR – AuthRepository
 *
 * Responsabilidad: acceder a la tabla `usuarios` para las operaciones
 * de autenticación: buscar por credenciales y gestionar sesiones activas.
 *
 * Esta clase NO debe:
 *   - Contener lógica de negocio ni validaciones de negocio.
 *   - Verificar contraseñas (eso lo hace AuthService con password_verify()).
 *   - Conocer Controllers ni Services.
 *   - Generar HTML, JSON ni usar header() o exit.
 *
 * Flujo: AuthService → AuthRepository → Database → MySQL (tabla: usuarios)
 *
 * Ubicación en el proyecto: Src/Repositories/AuthRepository.php
 */
class AuthRepository extends BaseRepository
{
    // -------------------------------------------------------------------------
    // Consultas de lectura
    // -------------------------------------------------------------------------

    /**
     * Busca un usuario por su correo electrónico.
     * Retorna todos los campos incluida la contraseña hasheada,
     * para que AuthService pueda ejecutar password_verify().
     *
     * @param string $correo Correo electrónico del usuario.
     * @return array<string, mixed>|null Fila del usuario o null si no existe.
     */
    public function buscarPorCorreo(string $correo): ?array
    {
        // ► AQUÍ: implementar cuando la tabla `usuarios` esté creada
        //
        // return $this->consultarUno(
        //     'SELECT id, correo, contrasena_hash, rol, estado
        //      FROM usuarios
        //      WHERE correo = :correo
        //      LIMIT 1',
        //     [':correo' => $correo]
        // );

        return null;
    }

    /**
     * Busca un usuario por su ID.
     * Excluye la contraseña hasheada de la respuesta.
     *
     * @param int $idUsuario Identificador único del usuario.
     * @return array<string, mixed>|null Datos públicos del usuario o null.
     */
    public function buscarPorId(int $idUsuario): ?array
    {
        // ► AQUÍ: implementar
        //
        // return $this->consultarUno(
        //     'SELECT id, correo, rol, estado, created_at
        //      FROM usuarios
        //      WHERE id = :id
        //      LIMIT 1',
        //     [':id' => $idUsuario]
        // );

        return null;
    }

    // -------------------------------------------------------------------------
    // Consultas de escritura
    // -------------------------------------------------------------------------

    /**
     * Actualiza el campo `ultimo_acceso` del usuario al momento actual.
     * Se llama después de un login exitoso.
     *
     * @param int $idUsuario Identificador único del usuario.
     * @return int Número de filas afectadas.
     */
    public function registrarAcceso(int $idUsuario): int
    {
        // ► AQUÍ: implementar
        //
        // return $this->ejecutar(
        //     'UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = :id',
        //     [':id' => $idUsuario]
        // );

        return 0;
    }

    /**
     * Actualiza el estado de un usuario (activo / inactivo / suspendido).
     *
     * @param int    $idUsuario Identificador único del usuario.
     * @param string $estado    Nuevo estado del usuario.
     * @return int Número de filas afectadas.
     */
    public function actualizarEstado(int $idUsuario, string $estado): int
    {
        // ► AQUÍ: implementar
        //
        // return $this->ejecutar(
        //     'UPDATE usuarios SET estado = :estado WHERE id = :id',
        //     [':estado' => $estado, ':id' => $idUsuario]
        // );

        return 0;
    }
}