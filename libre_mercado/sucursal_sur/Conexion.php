<?php
class Conexion {
    private static $instance = null;
 
    public static function conectar() {
        if (self::$instance === null) {
            $host    = 'localhost';
            $db      = 'libre_mercado_db';
            $user    = 'root';
            $pass    = '';
            $charset = 'utf8mb4';
 
            $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];
 
            // Lanza PDOException si no puede conectar — cada archivo la captura con try/catch
            self::$instance = new PDO($dsn, $user, $pass, $options);
        }
        return self::$instance;
    }
}
 
