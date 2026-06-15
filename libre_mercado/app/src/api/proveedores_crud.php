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
                SELECT id_proveedor, proveedor, rut, email, telefono, activo
                FROM proveedores
                ORDER BY id_proveedor
            ");
            echo json_encode(['ok' => true, 'proveedores' => $stmt->fetchAll(), 'leido_de' => ConexionNodos::nombre($idNodo)]);
            return;
        } catch (Throwable $e) {
            continue;
        }
    }
    throw new RuntimeException('Ningún nodo disponible para leer el catálogo de proveedores.');
}

function crear(): void
{
    $in = json_decode(file_get_contents('php://input'), true) ?? [];

    $proveedor = trim($in['proveedor'] ?? '');
    $rut       = trim($in['rut'] ?? '') ?: null;
    $email     = trim($in['email'] ?? '') ?: null;
    $telefono  = trim($in['telefono'] ?? '') ?: null;

    if ($proveedor === '') {
        http_response_code(400);
        echo json_encode(['ok' => false, 'mensaje' => 'El nombre del proveedor es obligatorio.']);
        return;
    }

    $nuevoId = MultiNodo::siguienteId('proveedores', 'id_proveedor');

    MultiNodo::ejecutar(function (PDO $pdo) use ($nuevoId, $proveedor, $rut, $email, $telefono) {
        try {
            $pdo->prepare("
                INSERT INTO proveedores (id_proveedor, proveedor, rut, email, telefono, activo)
                VALUES (?, ?, ?, ?, ?, 1)
            ")->execute([$nuevoId, $proveedor, $rut, $email, $telefono]);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                throw new RuntimeException('Ya existe un proveedor con ese RUT.');
            }
            throw $e;
        }
    });

    echo json_encode(['ok' => true, 'mensaje' => "Proveedor '$proveedor' creado en las 3 sucursales (ID $nuevoId).", 'id_proveedor' => $nuevoId]);
}

function actualizar(): void
{
    $in = json_decode(file_get_contents('php://input'), true) ?? [];

    $id        = (int)($in['id_proveedor'] ?? 0);
    $proveedor = trim($in['proveedor'] ?? '');
    $rut       = trim($in['rut'] ?? '') ?: null;
    $email     = trim($in['email'] ?? '') ?: null;
    $telefono  = trim($in['telefono'] ?? '') ?: null;

    if (!$id || $proveedor === '') {
        http_response_code(400);
        echo json_encode(['ok' => false, 'mensaje' => 'Datos inválidos.']);
        return;
    }

    MultiNodo::ejecutar(function (PDO $pdo, int $idNodo) use ($id, $proveedor, $rut, $email, $telefono) {
        $existe = $pdo->prepare("SELECT 1 FROM proveedores WHERE id_proveedor = ?");
        $existe->execute([$id]);
        if (!$existe->fetchColumn()) {
            throw new RuntimeException("El proveedor #$id no existe en " . ConexionNodos::nombre($idNodo) . ".");
        }

        $pdo->prepare("UPDATE proveedores SET proveedor = ?, rut = ?, email = ?, telefono = ? WHERE id_proveedor = ?")
            ->execute([$proveedor, $rut, $email, $telefono, $id]);
    });

    echo json_encode(['ok' => true, 'mensaje' => "Proveedor #$id actualizado en las 3 sucursales."]);
}

function cambiarEstado(): void
{
    $in = json_decode(file_get_contents('php://input'), true) ?? [];

    $id     = (int)($in['id_proveedor'] ?? 0);
    $activo = (int)($in['activo'] ?? 0) ? 1 : 0;

    if (!$id) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'mensaje' => 'ID inválido.']);
        return;
    }

    MultiNodo::ejecutar(function (PDO $pdo) use ($id, $activo) {
        $pdo->prepare("UPDATE proveedores SET activo = ? WHERE id_proveedor = ?")
            ->execute([$activo, $id]);
    });

    $accion = $activo ? 'reactivado' : 'dado de baja (borrado lógico)';
    echo json_encode(['ok' => true, 'mensaje' => "Proveedor #$id $accion en las 3 sucursales."]);
}
