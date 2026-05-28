<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'Conexion.php';

$items_carrito = isset($_SESSION['carrito']) ? $_SESSION['carrito'] : [];
if (empty($items_carrito)) { header("Location: index.php"); exit; }

$db = Conexion::conectar();
$status  = "error";
$mensaje = "";

$db->beginTransaction();

try {
    $total_venta  = 0;
    $id_sucursal  = 3; // Sucursal Sur

    $nombre_ingresado = isset($_POST['nombre_cliente']) ? trim($_POST['nombre_cliente']) : 'Cliente Anónimo';
    $email_ingresado  = isset($_POST['email_cliente'])  ? trim($_POST['email_cliente'])  : '';

    if (empty($email_ingresado)) {
        throw new Exception("El correo electrónico es obligatorio para procesar la venta.");
    }

    // 1. Buscar o crear cliente en tabla clientes (separada de usuarios/personal)
    $sqlBuscaCliente = "SELECT id_cliente FROM clientes WHERE email = :email LIMIT 1";
    $stmtBusca = $db->prepare($sqlBuscaCliente);
    $stmtBusca->execute([':email' => $email_ingresado]);
    $clienteExistente = $stmtBusca->fetch();

    if ($clienteExistente) {
        $id_cliente = $clienteExistente['id_cliente'];
    } else {
        $sqlNuevoCliente = "INSERT INTO clientes (cliente, email) VALUES (:cliente, :email)";
        $stmtNuevo = $db->prepare($sqlNuevoCliente);
        $stmtNuevo->execute([':cliente' => $nombre_ingresado, ':email' => $email_ingresado]);
        $id_cliente = $db->lastInsertId();
    }

    // 2. Calcular total
    foreach ($items_carrito as $item) {
        $total_venta += ($item['precio_unitario'] * $item['cantidad']);
    }

    // 3. Cabecera de venta (con estado según diagrama ER)
    $sqlVenta = "INSERT INTO ventas (id_cliente, id_sucursal, total, estado) VALUES (:id_cliente, :id_sucursal, :total, 'completada')";
    $stmtVenta = $db->prepare($sqlVenta);
    $stmtVenta->execute([
        ':id_cliente'  => $id_cliente,
        ':id_sucursal' => $id_sucursal,
        ':total'       => $total_venta
    ]);
    $id_venta = $db->lastInsertId();

    // 4. Detalle por cada producto
    foreach ($items_carrito as $item) {
        $id_producto       = $item['id_producto'];
        $cantidad_comprada = $item['cantidad'];

        // Bloquear fila de stock (FOR UPDATE — propiedad ACID)
        $sqlStock = "SELECT cantidad FROM stock WHERE id_sucursal = :id_sucursal AND id_producto = :id_producto FOR UPDATE";
        $stmtStock = $db->prepare($sqlStock);
        $stmtStock->execute([':id_sucursal' => $id_sucursal, ':id_producto' => $id_producto]);
        $resultado = $stmtStock->fetch();

        if (!$resultado || $resultado['cantidad'] < $cantidad_comprada) {
            $nombre_p = $item['producto'];
            throw new Exception("Stock insuficiente para '$nombre_p'. Disponible: " . ($resultado ? $resultado['cantidad'] : 0));
        }

        $subtotal = $item['precio_unitario'] * $cantidad_comprada;

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

        // Descontar stock de forma atómica
        $sqlDescuento = "UPDATE stock SET cantidad = cantidad - :cant WHERE id_sucursal = :id_sucursal AND id_producto = :id_producto";
        $stmtDescuento = $db->prepare($sqlDescuento);
        $stmtDescuento->execute([
            ':cant'        => $cantidad_comprada,
            ':id_sucursal' => $id_sucursal,
            ':id_producto' => $id_producto
        ]);
    }

    $db->commit();
    $_SESSION['carrito'] = [];
    $status  = "success";
    $mensaje = "¡Tu compra ha sido procesada con éxito! El stock se descontó de forma atómica bajo propiedades ACID.";

} catch (Exception $e) {
    $db->rollBack();
    $status  = "error";
    $mensaje = "Error al procesar la compra (Se aplicó Rollback Global): " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Resultado del Pago - ML Mercado</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f5f5f5; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
        .card-resultado { background: white; padding: 40px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center; max-width: 500px; width: 100%; }
        .icon { font-size: 50px; margin-bottom: 20px; }
        .success { color: #00a650; }
        .error { color: #cc0000; }
        h1 { margin: 0 0 15px 0; font-size: 24px; }
        p { color: #666; font-size: 16px; line-height: 1.5; }
        .btn { display: inline-block; background-color: #3483fa; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold; margin-top: 25px; }
        .btn-matriz { display: inline-block; background-color: #555; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: bold; margin-top: 10px; margin-left: 10px; }
    </style>
</head>
<body>
    <div class="card-resultado">
        <div style="font-size:13px; color:#1a6b37; font-weight:bold; margin-bottom:15px;">🟢 SUCURSAL SUR</div>
        <?php if ($status === "success"): ?>
            <div class="icon success">✓</div>
            <h1 class="success">¡Muchas gracias por tu compra!</h1>
            <p><?php echo $mensaje; ?></p>
        <?php else: ?>
            <div class="icon error">✕</div>
            <h1 class="error">No pudimos procesar el pago</h1>
            <p><?php echo $mensaje; ?></p>
        <?php endif; ?>
        <a href="index.php" class="btn">Volver al catálogo</a>
        <a href="../casa_matriz/index.php" class="btn-matriz">🏢 Casa Matriz</a>
    </div>
</body>
</html>
