<?php
// C:\xampp\htdocs\sucursal_norte\Venta.php
require_once 'Conexion.php';

class Venta {
    private $db;

    public function __construct() {
        $this->db = Conexion::conectar();
    }

    public function procesarVentaACID($idCliente, $idSucursal, $idProducto, $cantidadSolicitada) {
        // !!! INICIO DE LA TRANSACCIÓN ACID !!!
        $this->db->beginTransaction();

        try {
            // 1. PASO CRÍTICO: Consultar stock actual bloqueando la fila (FOR UPDATE)
            // Esto evita que dos clientes compren el mismo stock al mismo milisegundo
            $sqlStock = "SELECT stock FROM sucursales_stock WHERE id_suc = :id_suc AND id_prod = :id_prod FOR UPDATE";
            $stmtStock = $this->db->prepare($sqlStock);
            $stmtStock->execute([':id_suc' => $idSucursal, ':id_prod' => $idProducto]);
            $resultadoStock = $stmtStock->fetch();

            // Validación de existencia de inventario
            if (!$resultadoStock) {
                throw new Exception("El producto no está registrado en el inventario de esta sucursal.");
            }

            $stockActual = $resultadoStock['stock'];

            // 2. VALIDACIÓN DE REQUISITO: Si el inventario falla, forzar el Rollback
            if ($stockActual < $cantidadSolicitada) {
                throw new Exception("Stock insuficiente. Solicitado: $cantidadSolicitada | Disponible: $stockActual.");
            }

            // 3. Obtener el precio del producto para registrar la venta
            $sqlPrecio = "SELECT precio FROM productos WHERE id_prod = :id_prod";
            $stmtPrecio = $this->db->prepare($sqlPrecio);
            $stmtPrecio->execute([':id_prod' => $idProducto]);
            $producto = $stmtPrecio->fetch();
            $totalVenta = $producto['precio'] * $cantidadSolicitada;

            // 4. Insertar la cabecera de la venta
            $sqlVenta = "INSERT INTO ventas (id_cli, id_suc, total) VALUES (:id_cli, :id_suc, :total)";
            $stmtVenta = $this->db->prepare($sqlVenta);
            $stmtVenta->execute([
                ':id_cli' => $idCliente,
                ':id_suc' => $idSucursal,
                ':total'  => $totalVenta
            ]);
            $idVentaGenerated = $this->db->lastInsertId();

            // 5. Insertar el detalle de la venta
            $sqlDetalle = "INSERT INTO detalle_ventas (id_venta, id_prod, cantidad, precio_unitario) 
                           VALUES (:id_venta, :id_prod, :cantidad, :precio_unitario)";
            $stmtDetalle = $this->db->prepare($sqlDetalle);
            $stmtDetalle->execute([
                ':id_venta'        => $idVentaGenerated,
                ':id_prod'         => $idProducto,
                ':cantidad'        => $cantidadSolicitada,
                ':precio_unitario' => $producto['precio']
            ]);

            // 6. Descontar el stock de la sucursal de forma atómica
            $sqlDescuento = "UPDATE sucursales_stock SET stock = stock - :cantidad WHERE id_suc = :id_suc AND id_prod = :id_prod";
            $stmtDescuento = $this->db->prepare($sqlDescuento);
            $stmtDescuento->execute([
                ':cantidad' => $cantidadSolicitada,
                ':id_suc'   => $idSucursal,
                ':id_prod'  => $idProducto
            ]);

            // !!! CIERRE EXITOSO DE LA TRANSACCIÓN (COMMIT) !!!
            // Los cambios se guardan permanentemente en la BD de forma simultánea
            $this->db->commit();
            return ["status" => "success", "message" => "¡Venta procesada con éxito! Stock actualizado de forma atómica."];

        } catch (Exception $e) {
            // !!! FALLO EN EL SISTEMA - ROLLBACK AUTOMÁTICO !!!
            // Si cualquier paso de arriba falla, la BD regresa a su estado original. 
            // Nada se guarda (Atonicidad estricta)
            $this->db->rollBack();
            return ["status" => "error", "message" => "Transacción Revertida (Rollback): " . $e->getMessage()];
        }
    }
}
