<?php
session_start();
require_once 'Conexion.php';

// Validar que el carrito no esté vacío y que vengan los datos del cliente
$items_carrito = isset($_SESSION['carrito']) ? $_SESSION['carrito'] : [];
if (empty($items_carrito)) {
    header("Location: index.php");
    exit;
}

// Guardar temporalmente los datos del comprador en la sesión para que lleguen al final
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['comprador_nombre'] = trim($_POST['nombre_cliente'] ?? 'Cliente Anónimo');
    $_SESSION['comprador_email']  = trim($_POST['email_cliente'] ?? '');
}

// Si por alguna razón se recarga la página y se pierden los datos, redirigir
if (empty($_SESSION['comprador_email'])) {
    header("Location: ver_carrito.php");
    exit;
}

$total_compra = 0;
foreach ($items_carrito as $item) {
    $total_compra += ($item['precio_unitario'] * $item['cantidad']);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Método de Pago — Libre Mercado</title>
    <link rel="stylesheet" href="assets/metodo_pago.css">
</head>
<body>

    <div class="card-panel">
        <span class="header-badge">📍 Sucursal Centro — Pasarela Segura</span>
        <h1>Selecciona tu método de pago</h1>
        <p class="subtitle">Estás a un paso de completar tu transacción distribuida mediante aislamiento ACID.</p>

        <div class="amount-box">
            <span>Total a pagar de forma síncrona:</span>
            <strong>$<?php echo number_format($total_compra, 0, ',', '.'); ?></strong>
        </div>

        <!-- Formulario definitivo que gatilla el backend ACID -->
        <form action="finalizar_compra.php" method="POST">
            <div class="payment-options">
                <label class="option-label">
                    <input type="radio" name="metodo_pago" value="debito" required checked>
                    <span>💳 Tarjeta de Débito (Webpay Redcompra)</span>
                </label>
                <label class="option-label">
                    <input type="radio" name="metodo_pago" value="credito">
                    <span>💳 Tarjeta de Crédito (Visa / Mastercard)</span>
                </label>
                <label class="option-label">
                    <input type="radio" name="metodo_pago" value="transferencia">
                    <span>🏦 Transferencia Electrónica Directa</span>
                </label>
            </div>

            <button type="submit" class="btn-pay">⚡ Procesar Pago y Confirmar Stock</button>
            <a href="ver_carrito.php" class="btn-cancel">← Volver al resumen del carrito</a>
        </form>
    </div>

</body>
</html>
