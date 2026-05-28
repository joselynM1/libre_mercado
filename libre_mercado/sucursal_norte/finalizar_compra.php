<?php
// C:\xampp\htdocs\sucursal_norte\finalizar_compra.php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'Conexion.php';

$items_carrito = isset($_SESSION['carrito']) ? $_SESSION['carrito'] : [];

if (empty($items_carrito)) {
    header("Location: index.php");
    exit;
}

$db = Conexion::conectar();
$status = "error";
$mensaje = "";

// !!! COMIENZA LA TRANSACCIÓN GLOBAL ACID !!!
$db->beginTransaction();

try {
    $total_venta = 0;
    $id_sucursal = 1; // 1 = Sucursal Norte
    
    // Capturamos los datos reales enviados desde el formulario de pago
    $nombre_ingresado = isset($_POST['nombre_cliente']) ? trim($_POST['nombre_cliente']) : 'Cliente Anónimo';
    $email_ingresado  = isset($_POST['email_cliente']) ? trim($_POST['email_cliente']) : '';

    if (empty($email_ingresado)) {
        throw new Exception("El correo electrónico es obligatorio para procesar la venta.");
    }

    // 1. Verificar si el usuario ya existe buscando por su EMAIL
    $sqlBuscaUser = "SELECT id_cli FROM usuarios WHERE email = :email LIMIT 1";
    $stmtBusca = $db->prepare($sqlBuscaUser);
    $stmtBusca->execute([':email' => $email_ingresado]);
    $usuarioExistente = $stmtBusca->fetch();

    if ($usuarioExistente) {
        $id_cliente = $usuarioExistente['id_cli'];
    } else {
        // Si es un correo nuevo, insertamos el nuevo registro
        $sqlNuevoUser = "INSERT INTO usuarios (cliente, email, password_hash, rol, activo) 
                         VALUES (:nombre, :email, 'hash_simulado_pago', 'cliente', 1)";
        $stmtNuevoUser = $db->prepare($sqlNuevoUser);
        $stmtNuevoUser->execute([
            ':nombre' => $nombre_ingresado,
            ':email'  => $email_ingresado
        ]);
        $id_cliente = $db->lastInsertId();
    }

    // 2. Calcular el total global de la compra
    foreach ($items_carrito as $item) {
        $total_venta += ($item['precio'] * $item['cantidad']);
    }

    // 3. Insertar la Cabecera de la Venta obligatoria
    $sqlVenta = "INSERT INTO ventas (id_cli, id_suc, total) VALUES (:id_cli, :id_suc, :total)";
    $stmtVenta = $db->prepare($sqlVenta);
    $stmtVenta->execute([
        ':id_cli' => $id_cliente,
        ':id_suc' => $id_sucursal,
        ':total'  => $total_venta
    ]);
    $id_venta = $db->lastInsertId();

    // 4. Bucle para procesar, verificar y descontar stock por cada artículo
    foreach ($items_carrito as $item) {
        $id_prod = $item['id_prod'];
        $cantidad_comprada = $item['cantidad'];

        // Consultar stock bloqueando la fila (FOR UPDATE)
        $sqlStock = "SELECT stock FROM sucursales_stock WHERE id_suc = :id_suc AND id_prod = :id_prod FOR UPDATE";
        $stmtStock = $db->prepare($sqlStock);
        $stmtStock->execute([':id_suc' => $id_sucursal, ':id_prod' => $id_prod]);
        $resultado = $stmtStock->fetch();

        if (!$resultado || $resultado['stock'] < $cantidad_comprada) {
            $nombre_p = $item['producto'];
            throw new Exception("Stock insuficiente para '$nombre_p'. Disponible: " . ($resultado ? $resultado['stock'] : 0));
        }

        // Insertar Detalle de la Venta
        $sqlDetalle = "INSERT INTO detalle_ventas (id_venta, id_prod, cantidad, precio_unitario) 
                       VALUES (:id_v, :id_p, :cant, :precio)";
        $stmtDetalle = $db->prepare($sqlDetalle);
        $stmtDetalle->execute([
            ':id_v'   => $id_venta,
            ':id_p'   => $id_prod,
            ':cant'   => $cantidad_comprada,
            ':precio' => $item['precio']
        ]);

        // Descontar inventario de la sucursal de forma atómica
        $sqlDescuento = "UPDATE sucursales_stock SET stock = stock - :cant WHERE id_suc = :id_suc AND id_prod = :id_prod";
        $stmtDescuento = $db->prepare($sqlDescuento);
        $stmtDescuento->execute([
            ':cant'    => $cantidad_comprada,
            ':id_suc'  => $id_sucursal,
            ':id_prod' => $id_prod
        ]);
    }

    // !!! SI TODO SALIÓ BIEN, SE HACE COMMIT !!!
    $db->commit();
    
    // Vaciamos el carrito de la sesión
    $_SESSION['carrito'] = [];
    $status = "success";
    $mensaje = "¡Tu compra ha sido procesada con éxito! El stock se descontó de forma atómica bajo propiedades ACID.";

} catch (Exception $e) {
    // !!! PROTOCOLO DE FALLO: ROLLBACK AUTOMÁTICO !!!
    $db->rollBack();
    $status = "error";
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
    </style>
</head>
<body>

    <div class="card-resultado">
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
    </div>

</body>
</html>

