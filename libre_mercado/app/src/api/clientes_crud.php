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
                SELECT id_usuario, nombre, email, rol, activo
                FROM usuarios
                ORDER BY id_usuario
            ");
            echo json_encode(['ok' => true, 'usuarios' => $stmt->fetchAll(), 'leido_de' => ConexionNodos::nombre($idNodo)]);
            return;
        } catch (Throwable $e) {
            continue;
        }
    }
    throw new RuntimeException('Ningún nodo disponible para leer el catálogo de usuarios.');
}

function crear(): void
{
    $in = json_decode(file_get_contents('php://input'), true) ?? [];

    $nombre   = trim($in['nombre'] ?? '');
    $email    = trim($in['email'] ?? '');
    $rol      = $in['rol'] ?? 'cliente';
    $password = $in['password'] ?? '';

    if (!in_array($rol, ['administrador', 'cliente', 'operador'], true)) {
        $rol = 'cliente';
    }
    if ($nombre === '' || $email === '') {
        http_response_code(400);
        echo json_encode(['ok' => false, 'mensaje' => 'Nombre y email son obligatorios.']);
        return;
    }
    if ($password === '') {
        $password = 'cambiar123'; // contraseña por defecto para la demo
    }
    $hash = password_hash($password, PASSWORD_DEFAULT);

    $nuevoId = MultiNodo::siguienteId('usuarios', 'id_usuario');

    MultiNodo::ejecutar(function (PDO $pdo) use ($nuevoId, $nombre, $email, $hash, $rol) {
        try {
            $pdo->prepare("
                INSERT INTO usuarios (id_usuario, nombre, email, password_hash, rol, activo)
                VALUES (?, ?, ?, ?, ?, 1)
            ")->execute([$nuevoId, $nombre, $email, $hash, $rol]);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                throw new RuntimeException('Ya existe un usuario con ese email.');
            }
            throw $e;
        }
    });

    echo json_encode(['ok' => true, 'mensaje' => "Usuario '$nombre' creado en las 3 sucursales (ID $nuevoId).", 'id_usuario' => $nuevoId]);
}

function actualizar(): void
{
    $in = json_decode(file_get_contents('php://input'), true) ?? [];

    $id     = (int)($in['id_usuario'] ?? 0);
    $nombre = trim($in['nombre'] ?? '');
    $email  = trim($in['email'] ?? '');
    $rol    = $in['rol'] ?? 'cliente';

    if (!in_array($rol, ['administrador', 'cliente', 'operador'], true)) {
        $rol = 'cliente';
    }
    if (!$id || $nombre === '' || $email === '') {
        http_response_code(400);
        echo json_encode(['ok' => false, 'mensaje' => 'Datos inválidos.']);
        return;
    }

    MultiNodo::ejecutar(function (PDO $pdo, int $idNodo) use ($id, $nombre, $email, $rol) {
        $existe = $pdo->prepare("SELECT 1 FROM usuarios WHERE id_usuario = ?");
        $existe->execute([$id]);
        if (!$existe->fetchColumn()) {
            throw new RuntimeException("El usuario #$id no existe en " . ConexionNodos::nombre($idNodo) . ".");
        }

        $pdo->prepare("UPDATE usuarios SET nombre = ?, email = ?, rol = ? WHERE id_usuario = ?")
            ->execute([$nombre, $email, $rol, $id]);
    });

    echo json_encode(['ok' => true, 'mensaje' => "Usuario #$id actualizado en las 3 sucursales."]);
}

function cambiarEstado(): void
{
    $in = json_decode(file_get_contents('php://input'), true) ?? [];

    $id     = (int)($in['id_usuario'] ?? 0);
    $activo = (int)($in['activo'] ?? 0) ? 1 : 0;

    if (!$id) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'mensaje' => 'ID inválido.']);
        return;
    }

    MultiNodo::ejecutar(function (PDO $pdo) use ($id, $activo) {
        $pdo->prepare("UPDATE usuarios SET activo = ? WHERE id_usuario = ?")
            ->execute([$activo, $id]);
    });

    $accion = $activo ? 'reactivado' : 'dado de baja (borrado lógico)';
    echo json_encode(['ok' => true, 'mensaje' => "Usuario #$id $accion en las 3 sucursales."]);
}
