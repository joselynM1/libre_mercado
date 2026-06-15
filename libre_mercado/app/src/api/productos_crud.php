<?php
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/../ConexionNodos.php';
require __DIR__ . '/../MultiNodo.php';

$metodo = $_SERVER['REQUEST_METHOD'];

try {
    switch ($metodo) {
        case 'GET':
            listar();
            break;
        case 'POST':
            crear();
            break;
        case 'PUT':
            actualizar();
            break;
        case 'DELETE':
            cambiarEstado();
            break;
        default:
            http_response_code(405);
            echo json_encode(['ok' => false, 'mensaje' => 'Método no soportado']);
    }
} catch (RuntimeException $e) {
    http_response_code(409);
    echo json_encode(['ok' => false, 'mensaje' => $e->getMessage()]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'mensaje' => 'Error inesperado: ' . $e->getMessage()]);
}

/* ------------------------------------------------------------------ */

function listar(): void
{
    foreach (ConexionNodos::ids() as $idNodo) {
        try {
            $pdo = ConexionNodos::get($idNodo);
            $stmt = $pdo->query("
                SELECT id_producto, producto, precio_unitario, descripcion, categoria, activo
                FROM productos
                ORDER BY id_producto
            ");
            echo json_encode(['ok' => true, 'productos' => $stmt->fetchAll(), 'leido_de' => ConexionNodos::nombre($idNodo)]);
            return;
        } catch (Throwable $e) {
            continue;
        }
    }
    throw new RuntimeException('Ningún nodo disponible para leer el catálogo de productos.');
}

function crear(): void
{
    $in = json_decode(file_get_contents('php://input'), true) ?? [];

    $producto    = trim($in['producto'] ?? '');
    $precio      = (float)($in['precio_unitario'] ?? 0);
    $descripcion = trim($in['descripcion'] ?? '');
    $categoria   = trim($in['categoria'] ?? '') ?: null;
    $stockInicial = [
        1 => (int)($in['stock'][1] ?? 0),
        2 => (int)($in['stock'][2] ?? 0),
        3 => (int)($in['stock'][3] ?? 0),
    ];

    if ($producto === '' || $precio <= 0) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'mensaje' => 'Nombre y precio (mayor a 0) son obligatorios.']);
        return;
    }

    $nuevoId = MultiNodo::siguienteId('productos', 'id_producto');

    MultiNodo::ejecutar(function (PDO $pdo, int $idNodo) use ($nuevoId, $producto, $precio, $descripcion, $categoria, $stockInicial) {
        $pdo->prepare("
            INSERT INTO productos (id_producto, producto, precio_unitario, descripcion, categoria, activo)
            VALUES (?, ?, ?, ?, ?, 1)
        ")->execute([$nuevoId, $producto, $precio, $descripcion, $categoria]);

        $pdo->prepare("
            INSERT INTO stock (id_sucursal, id_producto, cantidad, stock_minimo)
            VALUES (?, ?, ?, 5)
        ")->execute([$idNodo, $nuevoId, $stockInicial[$idNodo]]);
    });

    echo json_encode(['ok' => true, 'mensaje' => "Producto '$producto' creado en las 3 sucursales (ID $nuevoId).", 'id_producto' => $nuevoId]);
}

function actualizar(): void
{
    $in = json_decode(file_get_contents('php://input'), true) ?? [];

    $id          = (int)($in['id_producto'] ?? 0);
    $producto    = trim($in['producto'] ?? '');
    $precio      = (float)($in['precio_unitario'] ?? 0);
    $descripcion = trim($in['descripcion'] ?? '');
    $categoria   = trim($in['categoria'] ?? '') ?: null;

    if (!$id || $producto === '' || $precio <= 0) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'mensaje' => 'Datos inválidos.']);
        return;
    }

    MultiNodo::ejecutar(function (PDO $pdo, int $idNodo) use ($id, $producto, $precio, $descripcion, $categoria) {
        $existe = $pdo->prepare("SELECT 1 FROM productos WHERE id_producto = ?");
        $existe->execute([$id]);
        if (!$existe->fetchColumn()) {
            throw new RuntimeException("El producto #$id no existe en " . ConexionNodos::nombre($idNodo) . ".");
        }

        $pdo->prepare("
            UPDATE productos
            SET producto = ?, precio_unitario = ?, descripcion = ?, categoria = ?
            WHERE id_producto = ?
        ")->execute([$producto, $precio, $descripcion, $categoria, $id]);
    });

    echo json_encode(['ok' => true, 'mensaje' => "Producto #$id actualizado en las 3 sucursales."]);
}

function cambiarEstado(): void
{
    $in = json_decode(file_get_contents('php://input'), true) ?? [];

    $id     = (int)($in['id_producto'] ?? 0);
    $activo = (int)($in['activo'] ?? 0) ? 1 : 0;

    if (!$id) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'mensaje' => 'ID inválido.']);
        return;
    }

    MultiNodo::ejecutar(function (PDO $pdo) use ($id, $activo) {
        $pdo->prepare("UPDATE productos SET activo = ? WHERE id_producto = ?")
            ->execute([$activo, $id]);
    });

    $accion = $activo ? 'reactivado' : 'dado de baja (borrado lógico)';
    echo json_encode(['ok' => true, 'mensaje' => "Producto #$id $accion en las 3 sucursales."]);
}
