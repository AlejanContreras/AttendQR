<?php

declare(strict_types=1);

/**
 * AttendQR – HealthService
 *
 * Responsabilidad: verificar el estado operativo de la API y sus dependencias.
 * Servicio de solo lectura. Sus endpoints son públicos y no requieren autenticación.
 *
 * Flujo: HealthController → HealthService → Database (solo ping de conectividad)
 *
 * Ubicación en el proyecto: Src/Services/HealthService.php
 */
class HealthService
{
    /** Versión actual del backend. Actualizar en cada despliegue. */
    private const VERSION = '0.1.0';

    /** Nombre del proyecto. */
    private const PROYECTO = 'AttendQR';

    /**
     * Verifica el estado general de la API y sus dependencias críticas.
     * Si la base de datos no responde, el estado global es 'degradado'.
     *
     * @return array<string, mixed>
     */
    public function status(): array
    {
        $estadoBD     = $this->verificarBaseDatos();
        $todoOperativo = $estadoBD === 'operativa';

        return [
            'estado_global' => $todoOperativo ? 'operativo' : 'degradado',
            'dependencias'  => [
                'base_de_datos' => $estadoBD,
            ],
            'version'   => self::VERSION,
            'proyecto'  => self::PROYECTO,
            'timestamp' => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * Respuesta mínima de vida del servidor.
     * No consulta la base de datos ni ninguna dependencia externa.
     *
     * @return array<string, mixed>
     */
    public function ping(): array
    {
        return [
            'respuesta' => 'pong',
            'proyecto'  => self::PROYECTO,
            'timestamp' => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * Retorna la información de versión del backend desplegado.
     *
     * @return array<string, mixed>
     */
    public function version(): array
    {
        return [
            'version'   => self::VERSION,
            'proyecto'  => self::PROYECTO,
            'timestamp' => date('Y-m-d H:i:s'),
        ];
    }

    // -------------------------------------------------------------------------
    // Métodos privados de apoyo
    // -------------------------------------------------------------------------

    /**
     * Verifica la conectividad con la base de datos ejecutando un ping PDO.
     * Nunca lanza excepciones: captura todos los errores internamente
     * para que un fallo de BD produzca un estado 'degradado', no un 500.
     *
     * @return string 'operativa' | 'no disponible'
     */
    private function verificarBaseDatos(): string
    {
        try {
            $pdo = Database::getConnection();
            $pdo->query('SELECT 1');
            return 'operativa';
        } catch (\Throwable $e) {
            return 'no disponible';
        }
    }
}