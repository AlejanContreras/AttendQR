<?php

declare(strict_types=1);

/**
 * AttendQR – HealthService
 *
 * Responsabilidad: verificar el estado operativo de la API y sus dependencias.
 * Este servicio es de solo lectura y no modifica ningún dato del sistema.
 * Sus endpoints son públicos y no requieren autenticación.
 *
 * Esta clase NO debe:
 *   - Ejecutar SQL de negocio (solo pings de conectividad).
 *   - Conocer el router ni los Controllers.
 *   - Acceder a $_POST, $_GET ni $_REQUEST.
 *   - Imprimir JSON, HTML ni usar header() o exit.
 *
 * Flujo esperado:
 *   HealthController → HealthService → [Database / dependencias externas]
 *
 * Ubicación en el proyecto: Src/Services/HealthService.php
 */
class HealthService
{
    // -------------------------------------------------------------------------
    // Configuración del servicio
    // -------------------------------------------------------------------------

    /** Versión actual del backend. ► ACTUALIZAR en cada despliegue. */
    private const VERSION = '0.1.0';

    /** Nombre del proyecto. */
    private const PROYECTO = 'AttendQR';

    // -------------------------------------------------------------------------
    // Dependencias (se inyectarán cuando existan los Repositories)
    // -------------------------------------------------------------------------

    // ► AQUÍ: declarar dependencias
    //
    // Ejemplo futuro:
    //   private \PDO $pdo;
    //
    //   public function __construct(\PDO $pdo)
    //   {
    //       $this->pdo = $pdo;
    //   }

    // -------------------------------------------------------------------------
    // Métodos públicos
    // -------------------------------------------------------------------------

    /**
     * Verifica el estado general de la API y sus dependencias críticas.
     *
     * Dependencias que este método deberá verificar:
     *   - Base de datos: ejecutar un SELECT 1 para confirmar conectividad.
     *   - (Futuro) Servicio de correo: verificar que el SMTP responde.
     *   - (Futuro) Sistema de archivos: verificar escritura en Storage/.
     *
     * Reglas de negocio:
     *   1. Verificar cada dependencia de forma independiente.
     *   2. Si alguna dependencia crítica falla, el estado global es 'degradado'.
     *   3. El Controller debe responder con 503 si el estado es 'degradado'.
     *   4. Retornar el estado de cada componente individualmente.
     *
     * @return array<string, mixed> Estado de cada dependencia y estado global.
     */
    public function status(): array
    {
        // ► AQUÍ: verificar base de datos con $this->verificarBaseDatos()
        // ► AQUÍ: agregar verificaciones de otros servicios externos
        //
        // Ejemplo futuro:
        //   $estadoBD      = $this->verificarBaseDatos();
        //   $todoOperativo = $estadoBD === 'operativa';
        //   return [
        //       'estado_global' => $todoOperativo ? 'operativo' : 'degradado',
        //       'dependencias'  => [
        //           'base_de_datos' => $estadoBD,
        //       ],
        //       'version'   => self::VERSION,
        //       'timestamp' => date('Y-m-d H:i:s'),
        //   ];

        return [
            'success' => true,
            'message' => 'HealthService::status() disponible. Pendiente de implementación.',
            'data'    => [
                'estado_global' => 'pendiente',
                'dependencias'  => [
                    'base_de_datos' => 'pendiente de verificar',
                ],
                'version'   => self::VERSION,
                'timestamp' => date('Y-m-d H:i:s'),
            ],
        ];
    }

    /**
     * Respuesta mínima de vida del servidor (ping).
     *
     * No consulta la base de datos ni ninguna dependencia externa.
     * Es intencionalmente el endpoint más rápido del sistema:
     * sirve para verificar que Apache y PHP están respondiendo
     * antes de ejecutar pruebas más costosas.
     *
     * @return array<string, mixed> Confirmación inmediata de disponibilidad.
     */
    public function ping(): array
    {
        // Esta respuesta es completamente estática e inmediata.
        // No requiere Repository ni ninguna dependencia externa.
        return [
            'success'   => true,
            'respuesta' => 'pong',
            'timestamp' => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * Retorna la información de versión del backend desplegado.
     *
     * Útil para verificar que el despliegue correcto está activo
     * y para que el equipo de desarrollo sepa qué versión corre
     * en cada entorno (local, staging, producción).
     *
     * @return array<string, mixed> Versión y metadatos del backend.
     */
    public function version(): array
    {
        // ► AQUÍ (opcional): leer el hash del último commit de Git
        //   o el contenido de un archivo VERSION para mayor precisión.
        //
        // Ejemplo futuro:
        //   $hashCommit = trim(shell_exec('git rev-parse --short HEAD') ?? '');
        //   return ['version' => self::VERSION, 'commit' => $hashCommit, ...];

        return [
            'success'   => true,
            'version'   => self::VERSION,
            'proyecto'  => self::PROYECTO,
            'timestamp' => date('Y-m-d H:i:s'),
        ];
    }

    // -------------------------------------------------------------------------
    // Métodos privados de apoyo
    // -------------------------------------------------------------------------

    /**
     * Verifica la conectividad con la base de datos ejecutando un SELECT 1.
     *
     * Retorna 'operativa' si la conexión es exitosa, 'no disponible' si falla.
     * Nunca lanza excepciones: captura todos los errores internamente
     * para que el estado degradado sea informativo, no fatal.
     *
     * ► AQUÍ: implementar cuando se inyecte la conexión PDO.
     *
     * @return string Estado de la base de datos ('operativa' | 'no disponible').
     */
    private function verificarBaseDatos(): string
    {
        // Ejemplo futuro:
        //   try {
        //       $this->pdo->query('SELECT 1');
        //       return 'operativa';
        //   } catch (\Throwable $e) {
        //       return 'no disponible';
        //   }

        return 'pendiente de verificar';
    }
}