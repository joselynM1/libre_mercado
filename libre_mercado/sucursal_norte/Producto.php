<?php
require_once 'Conexion.php';

class Producto {
    private $db;

    public function __construct() {
        $this->db = Conexion::conectar();
    }

    public function crear($nombre, $precio_unitario, $descripcion) {
        $stmt = $this->db->prepare(
            "INSERT INTO productos (producto, precio_unitario, descripcion) 
             VALUES (:producto, :precio_unitario, :descripcion)"
        );
        return $stmt->execute([
            ':producto'        => $nombre,
            ':precio_unitario' => $precio_unitario,
            ':descripcion'     => $descripcion
        ]);
    }

    public function listar() {
        $stmt = $this->db->query(
            "SELECT id_producto, producto, precio_unitario, descripcion 
             FROM productos WHERE activo = 1"
        );
        return $stmt->fetchAll();
    }

    // Usa la vista SQL para obtener productos con stock por sucursal
    public function listarPorSucursal($id_sucursal) {
        $stmt = $this->db->prepare(
            "SELECT id_producto, producto, precio_unitario, descripcion, stock 
             FROM vista_productos_sucursal 
             WHERE id_sucursal = :id_sucursal"
        );
        $stmt->execute([':id_sucursal' => $id_sucursal]);
        return $stmt->fetchAll();
    }

    public function eliminarLogico($id) {
        $stmt = $this->db->prepare(
            "UPDATE productos SET activo = 0 WHERE id_producto = :id_producto"
        );
        return $stmt->execute([':id_producto' => $id]);
    }
}