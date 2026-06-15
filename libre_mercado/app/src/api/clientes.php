<?php
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/../ConexionNodos.php';

// El catalogo de usuarios esta replicado en los 3 nodos, asi que basta
// con leerlo de uno (con fallback a los otros si el primero esta caido).
$resultado = ['ok' => false, 'usuarios' => []];

foreach (ConexionNodos::ids() as $idSuc) {
    try {
        $pdo = ConexionNodos::get($idSuc);
        $stmt = $pdo->query("SELECT id_usuario, nombre FROM usuarios WHERE activo = 1 ORDER BY nombre");
        $resultado['usuarios'] = $stmt->fetchAll();
        $resultado['ok'] = true;
        break;
    } catch (Throwable $e) {
        continue;
    }
}

echo json_encode($resultado);
