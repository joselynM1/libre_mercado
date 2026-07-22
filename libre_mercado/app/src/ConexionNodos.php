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

    private const ESTADO_FILE = '/tmp/libre_mercado_nodos.json';

    /**
     * Devuelve una conexión PDO al nodo indicado.
     * Lanza RuntimeException si el nodo está marcado como OFFLINE (falla simulada).
     * Lanza PDOException si el nodo no responde (caído / partición de red real).
     */
    public static function get(int $idSucursal): PDO
    {
        if (!isset(self::$config[$idSucursal])) {
            throw new InvalidArgumentException("Sucursal inválida: $idSucursal");
        }

        // Verificar estado simulado antes de intentar la conexión real
        if (self::estaOffline($idSucursal)) {
            $nombre = self::$config[$idSucursal]['nombre'];
            throw new RuntimeException("$nombre está OFFLINE (falla simulada). Nodo no disponible.");
        }

        $cfg = self::$config[$idSucursal];
        $dsn = "mysql:host={$cfg['host']};dbname={$cfg['dbname']};charset=utf8mb4";

        return new PDO($dsn, self::USER, self::PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_TIMEOUT            => 1, // si el nodo no responde en 1s, se considera caído
        ]);
    }

    public static function estaOffline(int $idSucursal): bool
    {
        if (!file_exists(self::ESTADO_FILE)) {
            return false;
        }
        $estados = json_decode(file_get_contents(self::ESTADO_FILE), true) ?? [];
        return ($estados[$idSucursal] ?? 'online') === 'offline';
    }

    public static function estadosTodos(): array
    {
        $estados = [];
        if (file_exists(self::ESTADO_FILE)) {
            $estados = json_decode(file_get_contents(self::ESTADO_FILE), true) ?? [];
        }
        $result = [];
        foreach (array_keys(self::$config) as $id) {
            $result[$id] = ($estados[$id] ?? 'online');
        }
        return $result;
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
