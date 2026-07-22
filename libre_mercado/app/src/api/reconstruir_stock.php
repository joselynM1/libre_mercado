<?php
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/../ConexionNodos.php';

$input      = json_decode(file_get_contents('php://input'), true) ?? [];
$idSucursal = (int)($input['id_sucursal'] ?? 0);

if (!$idSucursal) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'mensaje' => 'Debes indicar id_sucursal.']);
    exit;
}

try {
    $pdo = ConexionNodos::get($idSucursal);
} catch (Throwable $e) {
    http_response_code(503);
    echo json_encode(['ok' => false, 'mensaje' => ConexionNodos::nombre($idSucursal) . ' no está disponible.']);
    exit;
}

try {
    // Llama al stored procedure que recalcula el stock de la sucursal
    // sumando todas las compras recibidas y restando todas las ventas completadas.
    $stmt = $pdo->prepare("CALL sp_reconstruir_stock(:suc, @resultado, @mensaje)");
    $stmt->execute([':suc' => $idSucursal]);

    $out = $pdo->query("SELECT @resultado AS resultado, @mensaje AS mensaje")
                ->fetch(PDO::FETCH_ASSOC);

    if ((int)$out['resultado'] === 1) {
        echo json_encode([
            'ok'       => true,
            'sucursal' => ConexionNodos::nombre($idSucursal),
            'mensaje'  => $out['mensaje'],
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['ok' => false, 'mensaje' => $out['mensaje']]);
    }

} catch (Throwable $e) {
    error_log('reconstruir_stock: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'mensaje' => 'Ocurrió un error al reconstruir el stock. Intenta nuevamente.']);
}
