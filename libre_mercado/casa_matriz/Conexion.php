<?php
// C:\xampp\htdocs\sucursal_norte\Conexion.php
class Conexion {
    private static $instance = null;

    public static function conectar() {
        if (self::$instance === null) {
            $host = 'localhost';
            $db   = 'libre_mercado_db';
            $user = 'root'; // Por defecto en XAMPP
            $pass = '';     // Por defecto en XAMPP viene vacío
            $charset = 'utf8mb4';

            $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            try {
                self::$instance = new PDO($dsn, $user, $pass, $options);
            } catch (PDOException $e) {
                die("Error crítico de conexión: " . $e->getMessage());
            }
        }
        return self::$instance;
    }
}
