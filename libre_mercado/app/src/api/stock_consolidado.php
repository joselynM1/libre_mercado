<?php
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/../ConexionNodos.php';

$productos = []; // id_producto => ['producto'=>.., 'precio'=>.., 'stock'=>[1=>x,2=>y,3=>z]]
$estadoNodos = [];

foreach (ConexionNodos::ids() as $idSuc) {
    try {
        $pdo = ConexionNodos::get($idSuc);

        $stmt = $pdo->query("
            SELECT p.id_producto, p.producto, p.precio_unitario, p.descripcion, p.categoria, s.cantidad
            FROM productos p
            JOIN stock s ON s.id_producto = p.id_producto
            WHERE p.activo = 1
            ORDER BY p.id_producto
        ");

        foreach ($stmt as $row) {
            $id = (int)$row['id_producto'];
            if (!isset($productos[$id])) {
                $productos[$id] = [
                    'id_producto' => $id,
                    'producto'    => $row['producto'],
                    'descripcion' => $row['descripcion'],
                    'categoria'   => $row['categoria'],
                    'precio'      => (float)$row['precio_unitario'],
                    'stock'       => [],
                ];
            }
            $productos[$id]['stock'][$idSuc] = (int)$row['cantidad'];
        }

        $estadoNodos[$idSuc] = 'ok';
    } catch (Throwable $e) {
        // Nodo no disponible: no rompemos toda la respuesta (lectura parcial)
        $estadoNodos[$idSuc] = 'caido';
    }
}

echo json_encode([
    'sucursales' => array_map(fn($id) => ['id' => $id, 'nombre' => ConexionNodos::nombre($id)], ConexionNodos::ids()),
    'estado_nodos' => $estadoNodos,
    'productos' => array_values($productos),
]);
