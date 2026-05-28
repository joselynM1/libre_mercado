<?php
require_once 'Conexion.php';

class Venta {
    private $db;

    public function __construct() {
        $this->db = Conexion::conectar();
    }

    public function procesarVentaACID($idCliente, $idSucursal, $idProducto, $cantidadSolicitada) {
        $this->db->beginTransaction();

        try {
            // 1. Bloquear fila de stock (FOR UPDATE previene condición de carrera)
            $sqlStock = "SELECT cantidad FROM stock WHERE id_sucursal = :id_sucursal AND id_producto = :id_producto FOR UPDATE";
            $stmtStock = $this->db->prepare($sqlStock);
            $stmtStock->execute([':id_sucursal' => $idSucursal, ':id_producto' => $idProducto]);
            $resultadoStock = $stmtStock->fetch();

            if (!$resultadoStock) {
                throw new Exception("El producto no está registrado en el inventario de esta sucursal.");
            }

            if ($resultadoStock['cantidad'] < $cantidadSolicitada) {
                throw new Exception("Stock insuficiente. Solicitado: $cantidadSolicitada | Disponible: {$resultadoStock['cantidad']}.");
            }

            // 2. Obtener precio unitario
            $sqlPrecio = "SELECT precio_unitario FROM productos WHERE id_producto = :id_producto";
            $stmtPrecio = $this->db->prepare($sqlPrecio);
            $stmtPrecio->execute([':id_producto' => $idProducto]);
            $producto = $stmtPrecio->fetch();
            $totalVenta = $producto['precio_unitario'] * $cantidadSolicitada;

            // 3. Cabecera de venta
            $sqlVenta = "INSERT INTO ventas (id_cliente, id_sucursal, total, estado) VALUES (:id_cliente, :id_sucursal, :total, 'completada')";
            $stmtVenta = $this->db->prepare($sqlVenta);
            $stmtVenta->execute([
                ':id_cliente'  => $idCliente,
                ':id_sucursal' => $idSucursal,
                ':total'       => $totalVenta
            ]);
            $idVentaGenerated = $this->db->lastInsertId();

            // 4. Detalle de venta
            $sqlDetalle = "INSERT INTO detalle_ventas (id_venta, id_producto, cantidad, precio_unitario, subtotal)
                           VALUES (:id_venta, :id_producto, :cantidad, :precio_unitario, :subtotal)";
            $stmtDetalle = $this->db->prepare($sqlDetalle);
            $stmtDetalle->execute([
                ':id_venta'        => $idVentaGenerated,
                ':id_producto'     => $idProducto,
                ':cantidad'        => $cantidadSolicitada,
                ':precio_unitario' => $producto['precio_unitario'],
                ':subtotal'        => $totalVenta
            ]);

            // 5. Descontar stock de forma atómica
            $sqlDescuento = "UPDATE stock SET cantidad = cantidad - :cantidad WHERE id_sucursal = :id_sucursal AND id_producto = :id_producto";
            $stmtDescuento = $this->db->prepare($sqlDescuento);
            $stmtDescuento->execute([
                ':cantidad'    => $cantidadSolicitada,
                ':id_sucursal' => $idSucursal,
                ':id_producto' => $idProducto
            ]);

            $this->db->commit();
            return ["status" => "success", "message" => "¡Venta procesada con éxito! Stock actualizado de forma atómica."];

        } catch (Exception $e) {
            $this->db->rollBack();
            return ["status" => "error", "message" => "Transacción Revertida (Rollback): " . $e->getMessage()];
        }
    }
}
