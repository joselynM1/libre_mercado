<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'Venta.php';
$ventaControlador = new Venta();
$mensajeTransaccion = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idProducto = intval($_POST['id_producto']);
    $cantidad = intval($_POST['cantidad']);
    // ID de Sucursal = 3 (Sur)
    $mensajeTransaccion = $ventaControlador->procesarVentaACID(1, 3, $idProducto, $cantidad);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Sucursal Sur - Terminal de Ventas ACID</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 30px; background-color: #f4f4f9; }
        .container { max-width: 600px; margin: auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        h1, h2 { color: #1a6b37; }
        form { background: #e9f7ef; padding: 25px; border-radius: 5px; border: 1px solid #a9dfbf; }
        input, button { display: block; width: 95%; margin: 15px 0; padding: 12px; font-size: 16px; }
        button { background: #1a6b37; color: white; border: none; cursor: pointer; width: 100%; font-weight: bold; }
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 4px; font-weight: bold; }
        .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        a { display: inline-block; margin-top: 15px; color: #1a6b37; text-decoration: none; }
    </style>
</head>
<body>
<div class="container">
    <h1>🛒 Terminal de Ventas ACID — Sucursal Sur</h1>
    <p>Simulador de transacciones críticas sobre inventario.</p>
    <hr>
    <?php if ($mensajeTransaccion): ?>
        <div class="alert <?php echo $mensajeTransaccion['status']; ?>">
            <?php echo $mensajeTransaccion['message']; ?>
        </div>
    <?php endif; ?>
    <h2>Procesar Nueva Venta</h2>
    <form action="ventas.php" method="POST">
        <label for="id_producto">ID del Producto en Inventario:</label>
        <input type="number" name="id_producto" id="id_producto" value="1" required>
        <label for="cantidad">Cantidad a Comprar:</label>
        <input type="number" name="cantidad" placeholder="Cantidad de unidades" min="1" required>
        <button type="submit">Ejecutar Venta Atómica</button>
    </form>
    <p><a href="index.php">← Volver al catálogo</a> | <a href="../casa_matriz/index.php">🏢 Casa Matriz</a></p>
</div>
</body>
</html>
