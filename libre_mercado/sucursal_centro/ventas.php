<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'Venta.php';
$ventaControlador = new Venta();
$mensajeTransaccion = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idProducto = intval($_POST['id_producto']);
    $cantidad = intval($_POST['cantidad']);
    // ID de Sucursal = 2 (Centro)
    $mensajeTransaccion = $ventaControlador->procesarVentaACID(1, 2, $idProducto, $cantidad);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Sucursal Centro - Terminal de Ventas ACID</title>
    <link rel="stylesheet" href="assets/ventas.css">
</head>
<body>
<div class="container">
    <h1>🛒 Terminal de Ventas ACID — Sucursal Centro</h1>
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
