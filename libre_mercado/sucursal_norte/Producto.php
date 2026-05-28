<?php
// C:\xampp\htdocs\sucursal_norte\Producto.php
require_once 'Conexion.php';

class Producto {
    private $db;

    public function __construct() {
        // Obtenemos la conexión PDO compartida
        $this->db = Conexion::conectar();
    }

    // C - CREAR (Insertar un producto)
    public function crear($nombre, $precio, $descripcion) {
        $sql = "INSERT INTO productos (producto, precio, descripcion) VALUES (:producto, :precio, :descripcion)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':producto'    => $nombre,
            ':precio'      => $precio,
            ':descripcion' => $descripcion
        ]);
    }

    // R - LEER (Listar solo los productos que NO estén borrados lógicamente)
    public function listar() {
        $sql = "SELECT id_prod, producto, precio, descripcion FROM productos WHERE activo = 1";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    // D - ELIMINAR (Borrado Lógico requerido por el taller)
    public function eliminarLogico($id) {
        // En vez de un DELETE, hacemos un UPDATE a la bandera 'activo'
        $sql = "UPDATE productos SET activo = 0 WHERE id_prod = :id_prod";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id_prod' => $id]);
    }
}
