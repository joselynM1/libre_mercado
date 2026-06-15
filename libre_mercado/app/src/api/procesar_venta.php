<?php
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/../ConexionNodos.php';

$input = json_decode(file_get_contents('php://input'), true);

$idSucursal = (int)($input['id_sucursal'] ?? 0);
$idCliente  = (int)($input['id_cliente'] ?? 0);
$idProducto = (int)($input['id_producto'] ?? 0);
$cantidad   = (int)($input['cantidad'] ?? 0);

if (!$idSucursal || !$idCliente || !$idProducto || $cantidad <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'mensaje' => 'Datos incompletos']);
    exit;
}

try {
    $pdo = ConexionNodos::get($idSucursal);
} catch (Throwable $e) {
    http_response_code(503);
    echo json_encode(['ok' => false, 'mensaje' => ConexionNodos::nombre($idSucursal) . ' no está disponible (nodo caído).']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Bloqueamos la fila de stock para evitar condiciones de carrera
    $stmt = $pdo->prepare("SELECT cantidad FROM stock WHERE id_producto = ? FOR UPDATE");
    $stmt->execute([$idProducto]);
    $stockActual = $stmt->fetchColumn();

    if ($stockActual === false) {
        throw new RuntimeException('El producto no tiene registro de stock en esta sucursal.');
    }
    if ($stockActual < $cantidad) {
        throw new RuntimeException("Stock insuficiente en " . ConexionNodos::nombre($idSucursal) . " (disponible: $stockActual).");
    }

    // Descontar stock
    $pdo->prepare("UPDATE stock SET cantidad = cantidad - ? WHERE id_producto = ?")
        ->execute([$cantidad, $idProducto]);

    // Precio del producto
    $stmt = $pdo->prepare("SELECT precio_unitario, producto FROM productos WHERE id_producto = ?");
    $stmt->execute([$idProducto]);
    $prod = $stmt->fetch();
    $precio = (float)$prod['precio_unitario'];
    $subtotal = $precio * $cantidad;

    // Registrar venta
    $pdo->prepare("INSERT INTO ventas (id_cliente, id_sucursal, total, fecha, estado) VALUES (?, ?, ?, NOW(), 'completada')")
        ->execute([$idCliente, $idSucursal, $subtotal]);
    $idVenta = (int)$pdo->lastInsertId();

    // Registrar detalle
    $pdo->prepare("INSERT INTO detalle_ventas (id_venta, id_producto, cantidad, precio_unitario, subtotal) VALUES (?, ?, ?, ?, ?)")
        ->execute([$idVenta, $idProducto, $cantidad, $precio, $subtotal]);

    $pdo->commit();

    echo json_encode([
        'ok' => true,
        'mensaje' => "Venta #$idVenta registrada en " . ConexionNodos::nombre($idSucursal) . ": $cantidad x {$prod['producto']}.",
        'id_venta' => $idVenta,
        'stock_restante' => $stockActual - $cantidad,
    ]);

} catch (Throwable $e) {
    $pdo->rollBack();
    http_response_code(409);
    echo json_encode(['ok' => false, 'mensaje' => $e->getMessage()]);
}
