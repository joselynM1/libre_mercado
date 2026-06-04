<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'Conexion.php';

// Validar que el carrito no esté vacío
$items_carrito = isset($_SESSION['carrito']) ? $_SESSION['carrito'] : [];
if (empty($items_carrito)) { 
    header("Location: index.php"); 
    exit; 
}

$db = Conexion::conectar();
$status  = "error";
$mensaje = "";

try {
    // 1. INICIAR TRANSACCIÓN DENTRO DEL TRY (Propiedad ACID: Atomicidad)
    $db->beginTransaction();

    $total_venta  = 0;
    //ID 1 para registrar que la venta pertenece a la Sucursal Norte
    $id_sucursal  = 1; 

    $nombre_ingresado = isset($_SESSION['comprador_nombre']) ? trim($_SESSION['comprador_nombre']) : 'Cliente Anónimo';
    $email_ingresado  = isset($_SESSION['comprador_email'])  ? trim($_SESSION['comprador_email'])  : '';

    if (empty($email_ingresado)) {
        throw new Exception("El correo electrónico es obligatorio para procesar la venta.");
    }

    // 2. BUSCAR O CREAR EN LA TABLA DE USUARIOS
    $sqlBuscaCliente = "SELECT id_usuario FROM usuarios WHERE email = :email LIMIT 1";
    $stmtBusca = $db->prepare($sqlBuscaCliente);
    $stmtBusca->execute([':email' => $email_ingresado]);
    $clienteExistente = $stmtBusca->fetch();

    if ($clienteExistente) {
        $id_cliente = $clienteExistente['id_usuario'];
    } else {
        // Insertamos usando exactamente tus campos de base de datos
        $sqlNuevoCliente = "INSERT INTO usuarios (nombre, email, password_hash, rol, activo) 
                            VALUES (:nombre, :email, 'hash_simulado_pago', 'cliente', 1)";
        $stmtNuevo = $db->prepare($sqlNuevoCliente);
        $stmtNuevo->execute([
            ':nombre' => $nombre_ingresado, 
            ':email'  => $email_ingresado
        ]);
        $id_cliente = $db->lastInsertId();
    }

    // 3. CALCULAR EL TOTAL DE LA OPERACIÓN
    foreach ($items_carrito as $item) {
        $total_venta += ($item['precio_unitario'] * $item['cantidad']);
    }

    // 4. REGISTRAR CABECERA DE LA VENTA (Utiliza id_cliente que apunta a id_usuario)
    $sqlVenta = "INSERT INTO ventas (id_cliente, id_sucursal, total, estado) VALUES (:id_cliente, :id_sucursal, :total, 'completada')";
    $stmtVenta = $db->prepare($sqlVenta);
    $stmtVenta->execute([
        ':id_cliente'  => $id_cliente,
        ':id_sucursal' => $id_sucursal,
        ':total'       => $total_venta
    ]);
    $id_venta = $db->lastInsertId();

    // 5. PROCESAR CADA PRODUCTO DEL CARRITO DE FORMA INDIVISIBLE
    foreach ($items_carrito as $item) {
        $id_producto       = $item['id_producto'];
        $cantidad_comprada = $item['cantidad'];

        // [AISLAMIENTO CONCURRENTE] Bloquear fila de stock con FOR UPDATE
        $sqlStock = "SELECT cantidad FROM stock WHERE id_sucursal = :id_sucursal AND id_producto = :id_producto FOR UPDATE";
        $stmtStock = $db->prepare($sqlStock);
        $stmtStock->execute([':id_sucursal' => $id_sucursal, ':id_producto' => $id_producto]);
        $resultado = $stmtStock->fetch();

        // Validación crítica de inventario
        if (!$resultado || $resultado['cantidad'] < $cantidad_comprada) {
            $nombre_p = $item['producto'];
            throw new Exception("Stock insuficiente para '$nombre_p'. Disponible: " . ($resultado ? $resultado['cantidad'] : 0));
        }

        $subtotal = $item['precio_unitario'] * $cantidad_comprada;

        // Registrar en detalle_ventas
        $sqlDetalle = "INSERT INTO detalle_ventas (id_venta, id_producto, cantidad, precio_unitario, subtotal)
                       VALUES (:id_v, :id_p, :cant, :precio, :subtotal)";
        $stmtDetalle = $db->prepare($sqlDetalle);
        $stmtDetalle->execute([
            ':id_v'     => $id_venta,
            ':id_p'     => $id_producto,
            ':cant'     => $cantidad_comprada,
            ':precio'   => $item['precio_unitario'],
            ':subtotal' => $subtotal
        ]);

        // Descontar del inventario maestro de la sucursal Norte
        $sqlDescuento = "UPDATE stock SET cantidad = cantidad - :cant WHERE id_sucursal = :id_sucursal AND id_producto = :id_producto";
        $stmtDescuento = $db->prepare($sqlDescuento);
        $stmtDescuento->execute([
            ':cant'        => $cantidad_comprada,
            ':id_sucursal' => $id_sucursal,
            ':id_producto' => $id_producto
        ]);
    }

    // [DURABILIDAD] Confirmar todos los cambios si no hubo excepciones
    $db->commit();
    $_SESSION['carrito'] = []; // Vaciar el carrito de la sesión
    unset($_SESSION['comprador_nombre'], $_SESSION['comprador_email']); // <- agregar esta línea
    $status  = "success";
    $mensaje = "¡Tu compra ha sido procesada con éxito! El stock se descontó de forma atómica bajo propiedades ACID.";

} catch (Exception $e) {
    // Si algo falla, se ejecuta un Rollback absoluto anulando inserciones y perfiles nuevos creados
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    $status  = "error";
    $mensaje = "Error al procesar la compra (Se aplicó Rollback Global): " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Resultado del Pago - ML Mercado</title>
    <link rel="stylesheet" href="assets/finalizar_compra.css">
</head>
<body>
    <div class="card-resultado">
        <div style="font-size:12px; color:#b7950b; font-weight:bold; margin-bottom:15px; letter-spacing: 0.5px;">🟡 SUCURSAL NORTE — ENTORNO CENTRALIZADO</div>
        
        <?php if ($status === "success"): ?>
            <div class="icon success">✓</div>
            <h1 class="success">¡Muchas gracias por tu compra!</h1>
            <p><?php echo htmlspecialchars($mensaje); ?></p>
        <?php else: ?>
            <div class="icon error">✕</div>
            <h1 class="error">No pudimos procesar el pago</h1>
            <p><?php echo htmlspecialchars($mensaje); ?></p>
        <?php endif; ?>
        
        <a href="index.php" class="btn">Volver al catálogo</a>
        <a href="http://casa_matriz.local" class="btn-matriz">🏢 Casa Matriz</a>
    </div>
</body>
</html>
