<?php
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/../ConexionNodos.php';

$idSucursal = (int)($_GET['id_sucursal'] ?? 0);

if (!in_array($idSucursal, ConexionNodos::ids(), true)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'mensaje' => 'Sucursal inválida.']);
    exit;
}

try {
    $pdo = ConexionNodos::get($idSucursal);

    $stmt = $pdo->query("
        SELECT c.id_compra, c.fecha, c.total, c.estado, p.proveedor
        FROM compras c
        JOIN proveedores p ON p.id_proveedor = c.id_proveedor
        ORDER BY c.fecha DESC, c.id_compra DESC
        LIMIT 25
    ");

    echo json_encode(['ok' => true, 'compras' => $stmt->fetchAll()]);

} catch (Throwable $e) {
    http_response_code(503);
    echo json_encode(['ok' => false, 'mensaje' => ConexionNodos::nombre($idSucursal) . ' no está disponible.']);
}
