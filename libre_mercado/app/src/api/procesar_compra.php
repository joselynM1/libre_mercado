<?php
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/../ConexionNodos.php';

/**
 * Registra una compra de reabastecimiento en la sucursal indicada.
 * Es una transaccion ACID LOCAL a ese nodo: inserta la compra, su
 * detalle, y AUMENTA el stock de cada producto comprado. Si cualquier
 * paso falla, se hace rollback completo (no queda stock "fantasma" ni
 * compras sin detalle).
 */

$in = json_decode(file_get_contents('php://input'), true) ?? [];

$idSucursal  = (int)($in['id_sucursal'] ?? 0);
$idProveedor = (int)($in['id_proveedor'] ?? 0);
$items       = $in['items'] ?? [];

if (!$idSucursal || !$idProveedor || !is_array($items) || count($items) === 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'mensaje' => 'Debes indicar sucursal, proveedor y al menos un producto.']);
    exit;
}

try {
    $pdo = ConexionNodos::get($idSucursal);
} catch (Throwable $e) {
    http_response_code(503);
    echo json_encode(['ok' => false, 'mensaje' => ConexionNodos::nombre($idSucursal) . ' no está disponible (nodo caído). Compra cancelada.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Validar proveedor
    $stmt = $pdo->prepare("SELECT proveedor FROM proveedores WHERE id_proveedor = ? AND activo = 1");
    $stmt->execute([$idProveedor]);
    $nombreProveedor = $stmt->fetchColumn();
    if (!$nombreProveedor) {
        throw new RuntimeException("El proveedor #$idProveedor no existe o está inactivo.");
    }

    // Validar items y calcular total
    $total = 0;
    $detalles = [];
    foreach ($items as $item) {
        $idProd = (int)($item['id_producto'] ?? 0);
        $cant   = (int)($item['cantidad'] ?? 0);
        $precio = (float)($item['precio_compra'] ?? 0);

        if (!$idProd || $cant <= 0 || $precio <= 0) {
            throw new RuntimeException('Cada producto de la orden debe tener cantidad y precio de compra mayores a 0.');
        }

        $stmt = $pdo->prepare("SELECT producto FROM productos WHERE id_producto = ?");
        $stmt->execute([$idProd]);
        $nombreProd = $stmt->fetchColumn();
        if (!$nombreProd) {
            throw new RuntimeException("El producto #$idProd no existe en " . ConexionNodos::nombre($idSucursal) . ".");
        }

        $subtotal = $cant * $precio;
        $total += $subtotal;
        $detalles[] = ['id_producto' => $idProd, 'cantidad' => $cant, 'precio' => $precio, 'subtotal' => $subtotal];
    }

    // Registrar compra
    $pdo->prepare("INSERT INTO compras (id_proveedor, id_sucursal, fecha, total, estado) VALUES (?, ?, NOW(), ?, 'recibida')")
        ->execute([$idProveedor, $idSucursal, $total]);
    $idCompra = (int)$pdo->lastInsertId();

    // Registrar detalle y aumentar stock
    foreach ($detalles as $d) {
        $pdo->prepare("INSERT INTO detalle_compras (id_compra, id_producto, cantidad, precio_compra, subtotal) VALUES (?, ?, ?, ?, ?)")
            ->execute([$idCompra, $d['id_producto'], $d['cantidad'], $d['precio'], $d['subtotal']]);

        $stmt = $pdo->prepare("UPDATE stock SET cantidad = cantidad + ? WHERE id_producto = ?");
        $stmt->execute([$d['cantidad'], $d['id_producto']]);

        if ($stmt->rowCount() === 0) {
            // El producto aun no tenia fila de stock en este nodo
            $pdo->prepare("INSERT INTO stock (id_sucursal, id_producto, cantidad, stock_minimo) VALUES (?, ?, ?, 5)")
                ->execute([$idSucursal, $d['id_producto'], $d['cantidad']]);
        }
    }

    $pdo->commit();

    echo json_encode([
        'ok' => true,
        'mensaje' => "Compra #$idCompra registrada en " . ConexionNodos::nombre($idSucursal) . " (proveedor: $nombreProveedor). Total: $" . number_format($total, 0, ',', '.') . ". Stock actualizado.",
        'id_compra' => $idCompra,
    ]);

} catch (RuntimeException $e) {
    $pdo->rollBack();
    http_response_code(409);
    echo json_encode(['ok' => false, 'mensaje' => $e->getMessage()]);
} catch (Throwable $e) {
    $pdo->rollBack();
    error_log('procesar_compra: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'mensaje' => 'Ocurrió un error al registrar la compra. Intenta nuevamente.']);
}
