<?php
/**
 * Maneja las conexiones PDO a los 3 nodos (sucursales).
 * Cada nodo es una base de datos MariaDB independiente,
 * corriendo en su propio contenedor Docker.
 */
class ConexionNodos
{
    private static array $config = [
        1 => ['host' => 'db_suc1', 'dbname' => 'libre_mercado_suc1', 'nombre' => 'Sucursal Norte'],
        2 => ['host' => 'db_suc2', 'dbname' => 'libre_mercado_suc2', 'nombre' => 'Sucursal Centro'],
        3 => ['host' => 'db_suc3', 'dbname' => 'libre_mercado_suc3', 'nombre' => 'Sucursal Sur'],
    ];

    private const USER = 'app';
    private const PASS = 'app123';

    /**
     * Devuelve una conexión PDO al nodo indicado.
     * Lanza PDOException si el nodo no responde (caído / partición de red).
     */
    public static function get(int $idSucursal): PDO
    {
        if (!isset(self::$config[$idSucursal])) {
            throw new InvalidArgumentException("Sucursal inválida: $idSucursal");
        }

        $cfg = self::$config[$idSucursal];
        $dsn = "mysql:host={$cfg['host']};dbname={$cfg['dbname']};charset=utf8mb4";

        return new PDO($dsn, self::USER, self::PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT            => 1, // si el nodo no responde en 1s, se considera caído
        ]);
    }

    public static function nombre(int $idSucursal): string
    {
        return self::$config[$idSucursal]['nombre'] ?? "Sucursal $idSucursal";
    }

    public static function ids(): array
    {
        return array_keys(self::$config);
    }
}
