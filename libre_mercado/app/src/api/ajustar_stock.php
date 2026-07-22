<?php
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/../ConexionNodos.php';

/**
 * Ajuste manual de stock (correcciones de inventario por parte de un
 * administrador), invocando directamente sp_actualizar_stock(). A
 * diferencia de procesar_venta.php / procesar_compra.php, aqui NO se abre
 * una transaccion PHP propia: el procedimiento ya maneja su propia
 * transaccion (START TRANSACTION / COMMIT / ROLLBACK internos).
 */

$in = json_decode(file_get_contents('php://input'), true) ?? [];

$idSucursal = (int)($in['id_sucursal'] ?? 0);
$idProducto = (int)($in['id_producto'] ?? 0);
$cantidad   = (int)($in['cantidad'] ?? 0);
$operacion  = $in['operacion'] ?? '';

if (!$idSucursal || !$idProducto || $cantidad <= 0 || !in_array($operacion, ['sumar', 'restar'], true)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'mensaje' => 'Debes indicar sucursal, producto, cantidad (mayor a 0) y operación (sumar/restar).']);
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
    $stmt = $pdo->prepare("CALL sp_actualizar_stock(:suc, :prod, :cant, :op, @resultado, @mensaje)");
    $stmt->execute([
        ':suc'  => $idSucursal,
        ':prod' => $idProducto,
        ':cant' => $cantidad,
        ':op'   => $operacion,
    ]);

    $out = $pdo->query("SELECT @resultado AS resultado, @mensaje AS mensaje")
                ->fetch(PDO::FETCH_ASSOC);

    if ((int)$out['resultado'] === 1) {
        echo json_encode(['ok' => true, 'mensaje' => $out['mensaje']]);
    } else {
        http_response_code(409);
        echo json_encode(['ok' => false, 'mensaje' => $out['mensaje']]);
    }

} catch (Throwable $e) {
    error_log('ajustar_stock: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'mensaje' => 'Ocurrió un error al ajustar el stock. Intenta nuevamente.']);
}
