<?php
require_once 'Conexion.php';

class Producto {
    private $db;

    public function __construct() {
        $this->db = Conexion::conectar();
    }

    public function crear($nombre, $precio_unitario, $descripcion) {
        $sql = "INSERT INTO productos (producto, precio_unitario, descripcion) VALUES (:producto, :precio_unitario, :descripcion)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':producto'       => $nombre,
            ':precio_unitario' => $precio_unitario,
            ':descripcion'    => $descripcion
        ]);
    }

    public function listar() {
        $sql = "SELECT id_producto, producto, precio_unitario, descripcion FROM productos WHERE activo = 1";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    public function eliminarLogico($id) {
        $sql = "UPDATE productos SET activo = 0 WHERE id_producto = :id_producto";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id_producto' => $id]);
    }
}
