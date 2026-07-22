<?php
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/../ConexionNodos.php';
require __DIR__ . '/../MultiNodo.php';

$input = json_decode(file_get_contents('php://input'), true);

$idSucursal = (int)($input['id_sucursal'] ?? 0);
$idProducto = (int)($input['id_producto'] ?? 0);
$cantidad   = (int)($input['cantidad']    ?? 0);
$nombre     = trim($input['nombre'] ?? '');
$email      = trim($input['email']  ?? '');

if (!$idSucursal || !$idProducto || $cantidad <= 0) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'mensaje' => 'Datos incompletos']);
    exit;
}

if (!$nombre || !$email) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'mensaje' => 'Nombre y correo son obligatorios.']);
    exit;
}

// Verificar estado simulado del nodo antes de cualquier operación
try {
    $pdo = ConexionNodos::get($idSucursal);
} catch (Throwable $e) {
    http_response_code(503);
    echo json_encode(['ok' => false, 'mensaje' => ConexionNodos::nombre($idSucursal) . ' no está disponible (nodo caído).']);
    exit;
}

// Buscar cliente por email en el nodo local; si no existe, crearlo en los 3 nodos
try {
    $stmt = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $idCliente = $stmt->fetchColumn();

    if (!$idCliente) {
        // Registrar cliente nuevo en los 3 nodos (catálogo replicado)
        MultiNodo::ejecutar(function (PDO $conn) use ($nombre, $email) {
            $existe = $conn->prepare("SELECT id_usuario FROM usuarios WHERE email = ?");
            $existe->execute([$email]);
            if (!$existe->fetchColumn()) {
                $conn->prepare(
                    "INSERT INTO usuarios (nombre, email, password_hash, rol, activo) VALUES (?, ?, '', 'cliente', 1)"
                )->execute([$nombre, $email]);
            }
        });

        // Leer el id recién creado desde el nodo local
        $stmt = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        $idCliente = (int)$stmt->fetchColumn();
    }
} catch (RuntimeException $e) {
    http_response_code(503);
    echo json_encode(['ok' => false, 'mensaje' => 'No se pudo registrar el cliente: ' . $e->getMessage()]);
    exit;
} catch (Throwable $e) {
    error_log('procesar_venta (registro cliente): ' . $e->getMessage());
    http_response_code(503);
    echo json_encode(['ok' => false, 'mensaje' => 'Ocurrió un error al registrar el cliente. Intenta nuevamente.']);
    exit;
}

// Ejecutar la venta mediante el stored procedure
try {
    $stmt = $pdo->prepare("CALL sp_realizar_compra(:suc, :cli, :prod, :cant, @resultado, @mensaje)");
    $stmt->execute([
        ':suc'  => $idSucursal,
        ':cli'  => $idCliente,
        ':prod' => $idProducto,
        ':cant' => $cantidad,
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
    error_log('procesar_venta (sp_realizar_compra): ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'mensaje' => 'Ocurrió un error al procesar la compra. Intenta nuevamente.']);
}
