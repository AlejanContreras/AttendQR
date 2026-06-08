<?php

declare(strict_types=1);

/**
 * Database connection manager for AttendQR.
 *
 * Provides a centralized, reusable PDO connection.
 * Configuration is read from environment variables (via $_ENV / getenv()),
 * with temporary fallback values for local development.
 *
 * Usage:
 *   $pdo = Database::getConnection();
 */
final class Database
{
    private static ?PDO $instance = null;

    /**
     * Returns the shared PDO instance, creating it on first call.
     */
    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            self::$instance = self::createConnection();
        }

        return self::$instance;
    }

    /**
     * Builds and configures a new PDO connection.
     */
    private static function createConnection(): PDO
    {
        $host    = self::env('DB_HOST',    'localhost');
        $port    = self::env('DB_PORT',    '3306');
        $dbname  = self::env('DB_NAME',    'attendqr');
        $charset = self::env('DB_CHARSET', 'utf8mb4');
        $user    = self::env('DB_USER',    'root');
        $pass    = self::env('DB_PASS',    '');

        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            $host,
            $port,
            $dbname,
            $charset
        );

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$charset} COLLATE {$charset}_unicode_ci",
        ];

        return new PDO($dsn, $user, $pass, $options);
    }

    /**
     * Reads a value from the environment, falling back to a default.
     * Supports both $_ENV and getenv() for broad server compatibility.
     */
    private static function env(string $key, string $default = ''): string
    {
        $value = $_ENV[$key] ?? getenv($key);

        return ($value !== false && $value !== '') ? (string) $value : $default;
    }

    /** Prevent instantiation. */
    private function __construct() {}

    /** Prevent cloning. */
    private function __clone() {}
}