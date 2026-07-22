<?php
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/../ConexionNodos.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    echo json_encode(['ok' => true, 'estados' => ConexionNodos::estadosTodos()]);
    exit;
}

if ($method === 'POST') {
    $input      = json_decode(file_get_contents('php://input'), true) ?? [];
    $idSucursal = (int)($input['id_sucursal'] ?? 0);
    $estado     = $input['estado'] ?? '';

    if (!in_array($idSucursal, [1, 2, 3], true) || !in_array($estado, ['online', 'offline'], true)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'mensaje' => 'Parámetros inválidos. id_sucursal: 1-3, estado: online|offline']);
        exit;
    }

    $estadoFile = '/tmp/libre_mercado_nodos.json';
    $estados    = ConexionNodos::estadosTodos();
    $estados[$idSucursal] = $estado;

    if (file_put_contents($estadoFile, json_encode($estados)) === false) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'mensaje' => 'No se pudo escribir el archivo de estados.']);
        exit;
    }

    $nombre  = match ($idSucursal) { 1 => 'Sucursal Norte', 2 => 'Sucursal Centro', 3 => 'Sucursal Sur' };
    $accion  = $estado === 'offline' ? 'OFFLINE — falla simulada activa' : 'ONLINE — nodo recuperado';

    echo json_encode([
        'ok'      => true,
        'mensaje' => "$nombre → $accion",
        'estados' => $estados,
    ]);
    exit;
}

http_response_code(405);
echo json_encode(['ok' => false, 'mensaje' => 'Método no permitido.']);
