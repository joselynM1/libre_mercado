<?php
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/../ConexionNodos.php';

$idCliente = (int)($_GET['id_cliente'] ?? 0);

if (!$idCliente) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'mensaje' => 'id_cliente requerido.']);
    exit;
}

$ventas  = [];
$errores = [];

foreach (ConexionNodos::ids() as $idSucursal) {
    try {
        $pdo  = ConexionNodos::get($idSucursal);
        $stmt = $pdo->prepare("
            SELECT v.id_venta,
                   v.fecha,
                   v.total,
                   v.estado,
                   GROUP_CONCAT(p.producto, ' x', dv.cantidad ORDER BY p.producto SEPARATOR ', ') AS detalle
            FROM ventas v
            JOIN detalle_ventas dv ON dv.id_venta = v.id_venta
            JOIN productos      p  ON p.id_producto = dv.id_producto
            WHERE v.id_cliente = ?
            GROUP BY v.id_venta, v.fecha, v.total, v.estado
            ORDER BY v.fecha DESC
            LIMIT 30
        ");
        $stmt->execute([$idCliente]);

        $nombre = ConexionNodos::nombre($idSucursal);
        foreach ($stmt->fetchAll() as $row) {
            $row['sucursal'] = $nombre;
            $ventas[] = $row;
        }
    } catch (Throwable $e) {
        $errores[] = ConexionNodos::nombre($idSucursal) . ' no disponible';
    }
}

usort($ventas, fn($a, $b) => strcmp($b['fecha'], $a['fecha']));

echo json_encode(['ok' => true, 'ventas' => $ventas, 'errores' => $errores]);
