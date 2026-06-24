<?php

declare(strict_types=1);

/**
 * AttendQR – AuthRepository
 *
 * Responsabilidad: acceder a la tabla `usuarios` para las operaciones
 * de autenticación: buscar por credenciales y registrar accesos.
 *
 * NO contiene lógica de negocio. La verificación de contraseñas
 * (password_verify) corresponde a AuthService.
 *
 * Flujo: AuthService → AuthRepository → BaseRepository → Database → MySQL
 *
 * Ubicación en el proyecto: Src/Repositories/AuthRepository.php
 */
class AuthRepository extends BaseRepository
{
    /**
     * Busca un usuario por correo electrónico.
     * Incluye contrasena_hash para que AuthService ejecute password_verify().
     *
     * @param string $correo Correo del usuario.
     * @return array<string, mixed>|null Fila completa o null si no existe.
     */
    public function buscarPorCorreo(string $correo): ?array
    {
        return $this->consultarUno(
            'SELECT id, correo, contrasena_hash, rol, estado
             FROM usuarios
             WHERE correo = :correo
             LIMIT 1',
            [':correo' => $correo]
        );
    }

    /**
     * Busca un usuario por su ID sin incluir la contraseña.
     *
     * @param int $idUsuario Identificador del usuario.
     * @return array<string, mixed>|null Datos públicos o null.
     */
    public function buscarPorId(int $idUsuario): ?array
    {
        return $this->consultarUno(
            'SELECT id, correo, rol, estado, created_at
             FROM usuarios
             WHERE id = :id
             LIMIT 1',
            [':id' => $idUsuario]
        );
    }

    /**
     * Actualiza el campo ultimo_acceso al momento actual.
     * Se invoca después de un login exitoso.
     *
     * @param int $idUsuario Identificador del usuario.
     * @return int Filas afectadas.
     */
    public function registrarAcceso(int $idUsuario): int
    {
        return $this->ejecutar(
            'UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = :id',
            [':id' => $idUsuario]
        );
    }

    /**
     * Actualiza el estado de un usuario (activo / inactivo / suspendido).
     *
     * @param int    $idUsuario Identificador del usuario.
     * @param string $estado    Nuevo estado.
     * @return int Filas afectadas.
     */
    public function actualizarEstado(int $idUsuario, string $estado): int
    {
        return $this->ejecutar(
            'UPDATE usuarios SET estado = :estado WHERE id = :id',
            [':estado' => $estado, ':id' => $idUsuario]
        );
    }
}