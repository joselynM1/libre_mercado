<?php
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/../ConexionNodos.php';

/**
 * Traspaso de stock entre dos sucursales (dos nodos / dos bases distintas).
 *
 * Implementa un Two-Phase Commit manual:
 *  - FASE 1 (prepare): se abre transaccion en AMBOS nodos y se ejecutan
 *    los cambios, pero SIN hacer commit todavia.
 *  - FASE 2 (commit):  solo si ambas transacciones se prepararon sin error,
 *    se confirma (commit) en ambas. Si una falla -> rollback en ambas.
 *
 * Esto demuestra la eleccion CP: ante una particion de red (un nodo no
 * responde), la operacion completa se cancela en lugar de dejar el
 * sistema en un estado inconsistente (stock "fantasma").
 */

$input = json_decode(file_get_contents('php://input'), true);

$idOrigen   = (int)($input['id_origen'] ?? 0);
$idDestino  = (int)($input['id_destino'] ?? 0);
$idProducto = (int)($input['id_producto'] ?? 0);
$cantidad   = (int)($input['cantidad'] ?? 0);

if (!$idOrigen || !$idDestino || !$idProducto || $cantidad <= 0 || $idOrigen === $idDestino) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'mensaje' => 'Datos incompletos o sucursales iguales.']);
    exit;
}

$pdoOrigen = null;
$pdoDestino = null;

try {
    // --- Conexion a ambos nodos ---
    try {
        $pdoOrigen = ConexionNodos::get($idOrigen);
    } catch (Throwable $e) {
        throw new RuntimeException(ConexionNodos::nombre($idOrigen) . ' no disponible (particion de red). Traspaso cancelado.');
    }

    try {
        $pdoDestino = ConexionNodos::get($idDestino);
    } catch (Throwable $e) {
        throw new RuntimeException(ConexionNodos::nombre($idDestino) . ' no disponible (particion de red). Traspaso cancelado.');
    }

    // --- FASE 1: PREPARE ---
    $pdoOrigen->beginTransaction();
    $pdoDestino->beginTransaction();

    $stmt = $pdoOrigen->prepare("SELECT cantidad FROM stock WHERE id_producto = ? FOR UPDATE");
    $stmt->execute([$idProducto]);
    $stockOrigen = $stmt->fetchColumn();

    if ($stockOrigen === false || $stockOrigen < $cantidad) {
        throw new RuntimeException(ConexionNodos::nombre($idOrigen) . " no tiene stock suficiente (disponible: " . ($stockOrigen === false ? 0 : $stockOrigen) . ").");
    }

    $pdoOrigen->prepare("UPDATE stock SET cantidad = cantidad - ? WHERE id_producto = ?")
              ->execute([$cantidad, $idProducto]);

    $stmt = $pdoDestino->prepare("SELECT cantidad FROM stock WHERE id_producto = ? FOR UPDATE");
    $stmt->execute([$idProducto]);
    $stockDestino = $stmt->fetchColumn();

    if ($stockDestino === false) {
        throw new RuntimeException("El producto no existe en el stock de " . ConexionNodos::nombre($idDestino) . ".");
    }

    $pdoDestino->prepare("UPDATE stock SET cantidad = cantidad + ? WHERE id_producto = ?")
               ->execute([$cantidad, $idProducto]);

    // --- FASE 2: COMMIT (solo si ambas fases 1 fueron exitosas) ---
    $pdoOrigen->commit();
    $pdoDestino->commit();

    echo json_encode([
        'ok' => true,
        'mensaje' => "Traspaso confirmado: $cantidad unidades de " . ConexionNodos::nombre($idOrigen) . " -> " . ConexionNodos::nombre($idDestino) . ".",
    ]);

} catch (RuntimeException $e) {
    // Rollback en cualquier nodo que haya alcanzado a abrir transaccion
    if ($pdoOrigen && $pdoOrigen->inTransaction())  $pdoOrigen->rollBack();
    if ($pdoDestino && $pdoDestino->inTransaction()) $pdoDestino->rollBack();

    http_response_code(409);
    echo json_encode(['ok' => false, 'mensaje' => 'Traspaso cancelado (rollback en ambos nodos): ' . $e->getMessage()]);
} catch (Throwable $e) {
    if ($pdoOrigen && $pdoOrigen->inTransaction())  $pdoOrigen->rollBack();
    if ($pdoDestino && $pdoDestino->inTransaction()) $pdoDestino->rollBack();

    error_log('traspaso_stock: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'mensaje' => 'Ocurrió un error al procesar el traspaso. Intenta nuevamente.']);
}
