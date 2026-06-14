<?php

declare(strict_types=1);

/**
 * AttendQR – BaseRepository
 *
 * Clase abstracta de la que heredan todos los Repositories del sistema.
 *
 * Responsabilidad:
 *   - Obtener la conexión PDO desde Database::getConnection() (Singleton).
 *   - Proveer helpers privados reutilizables: consultar, insertar, actualizar,
 *     eliminar y contar registros mediante sentencias preparadas.
 *   - Centralizar el manejo de PDOException para que los Repositories concretos
 *     no repitan bloques try/catch.
 *
 * Los Repositories concretos heredan esta clase y agregan únicamente
 * los métodos específicos de su entidad (findByCorreo, existeDocumento, etc.).
 *
 * Esta clase NO debe:
 *   - Contener lógica de negocio.
 *   - Conocer Controllers ni Services.
 *   - Generar HTML, JSON ni usar header() o exit.
 *
 * Ubicación en el proyecto: Src/Repositories/BaseRepository.php
 */
abstract class BaseRepository
{
    /** Conexión PDO compartida entre todos los Repositories (Singleton). */
    protected PDO $db;

    /**
     * Obtiene la conexión centralizada desde Database::getConnection().
     * No crea una nueva conexión: reutiliza la instancia ya existente.
     */
    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    // -------------------------------------------------------------------------
    // Helpers protegidos reutilizables
    // -------------------------------------------------------------------------

    /**
     * Ejecuta una consulta SELECT y retorna todas las filas encontradas.
     *
     * @param string               $sql    Consulta SQL con placeholders (:param o ?).
     * @param array<string, mixed> $params Parámetros para los placeholders.
     * @return array<int, array<string, mixed>> Filas resultantes (vacío si no hay).
     */
    protected function consultar(string $sql, array $params = []): array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    /**
     * Ejecuta una consulta SELECT y retorna únicamente la primera fila.
     * Retorna null si no se encontró ningún registro.
     *
     * @param string               $sql    Consulta SQL con placeholders.
     * @param array<string, mixed> $params Parámetros para los placeholders.
     * @return array<string, mixed>|null Primera fila encontrada o null.
     */
    protected function consultarUno(string $sql, array $params = []): ?array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $fila = $stmt->fetch();

        return $fila !== false ? $fila : null;
    }

    /**
     * Ejecuta una sentencia INSERT y retorna el ID del registro creado.
     *
     * @param string               $sql    Sentencia INSERT con placeholders.
     * @param array<string, mixed> $params Parámetros para los placeholders.
     * @return int ID del último registro insertado.
     */
    protected function insertar(string $sql, array $params = []): int
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int) $this->db->lastInsertId();
    }

    /**
     * Ejecuta una sentencia UPDATE o DELETE y retorna el número de filas afectadas.
     *
     * @param string               $sql    Sentencia SQL con placeholders.
     * @param array<string, mixed> $params Parámetros para los placeholders.
     * @return int Número de filas afectadas.
     */
    protected function ejecutar(string $sql, array $params = []): int
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->rowCount();
    }

    /**
     * Ejecuta una consulta COUNT y retorna el valor entero resultante.
     * Útil para verificar existencia sin traer registros completos.
     *
     * @param string               $sql    Consulta SQL que retorna una columna numérica.
     * @param array<string, mixed> $params Parámetros para los placeholders.
     * @return int Valor numérico de la primera columna de la primera fila.
     */
    protected function contar(string $sql, array $params = []): int
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $resultado = $stmt->fetchColumn();

        return $resultado !== false ? (int) $resultado : 0;
    }

    /**
     * Verifica si existe al menos un registro que cumpla la condición.
     * Wrapper semántico sobre contar() para mejorar la legibilidad del código.
     *
     * @param string               $sql    Consulta COUNT con placeholders.
     * @param array<string, mixed> $params Parámetros para los placeholders.
     * @return bool true si existe al menos un registro.
     */
    protected function existe(string $sql, array $params = []): bool
    {
        return $this->contar($sql, $params) > 0;
    }
}