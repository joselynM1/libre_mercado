<?php
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/../ConexionNodos.php';

$ventas  = [];
$errores = [];

foreach (ConexionNodos::ids() as $idSucursal) {
    try {
        $pdo  = ConexionNodos::get($idSucursal);
        $stmt = $pdo->query("
            SELECT v.id_venta,
                   v.fecha,
                   v.total,
                   v.estado,
                   u.nombre   AS cliente,
                   u.email    AS email_cliente,
                   GROUP_CONCAT(
                       p.producto, ' x', dv.cantidad,
                       ' ($', FORMAT(dv.precio_unitario, 0), ')'
                       ORDER BY p.producto
                       SEPARATOR ' | '
                   ) AS detalle
            FROM ventas v
            JOIN usuarios      u  ON u.id_usuario  = v.id_cliente
            JOIN detalle_ventas dv ON dv.id_venta   = v.id_venta
            JOIN productos      p  ON p.id_producto = dv.id_producto
            GROUP BY v.id_venta, v.fecha, v.total, v.estado, u.nombre, u.email
            ORDER BY v.fecha DESC
            LIMIT 50
        ");

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
