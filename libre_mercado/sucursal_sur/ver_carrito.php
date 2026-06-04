<?php
session_start();
require_once 'Conexion.php';

// Identificar dinámicamente el nodo actual (Sucursal Sur = ID 3)
$id_sucursal_actual = 3; 
$nombre_sucursal = "Sucursal Sur";

$items_carrito = isset($_SESSION['carrito']) ? $_SESSION['carrito'] : [];
$total_compra = 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Carrito — Libre Mercado</title>
    <link rel="stylesheet" href="assets/ver_carrito.css">
</head>
<body>

    <!-- NAVBAR -->
    <header class="header">
        <div class="brand">
            <a href="index.php" class="brand-logo">Libre<span>Mercado</span></a>
            <span class="checkout-badge">🛒 Pasarela de pago</span>
        </div>
        <div style="font-size: 13px; font-weight: 600; color: #64748b;">
            Nodo: <?php echo $nombre_sucursal; ?>
        </div>
    </header>

    <!-- FORMULARIO & RESUMEN -->
    <main class="container">
        <h1>Tu Carrito de Compras</h1>
        
        <div class="grid-layout">
            <?php if (empty($items_carrito)): ?>
                <div class="card-panel" style="text-align:center; padding: 40px 20px;">
                    <p style="color:#64748b; margin-bottom: 20px; font-size: 15px;">Tu carrito no tiene artículos registrados en esta sucursal.</p>
                    <a href="index.php" class="btn-submit" style="display:inline-block; width:auto; padding:12px 24px; box-shadow: none;">Volver al Catálogo</a>
                </div>
            <?php else: ?>
                <!-- SE CONECTA DIRECTAMENTE CON EL PASO DE MÉTODO DE PAGO -->
                <form action="metodo_pago.php" method="POST">
                    
                    <!-- PANEL 1: RESUMEN DE ARTÍCULOS -->
                    <div class="card-panel">
                        <h3 style="font-size:15px; margin-bottom:20px; color:#475569; text-transform:uppercase; letter-spacing:0.5px;">1. Resumen de Artículos</h3>
                        
                        <?php foreach ($items_carrito as $item): 
                            $subtotal = $item['precio_unitario'] * $item['cantidad'];
                            $total_compra += $subtotal;
                        ?>
                            <div class="cart-item">
                                <div class="item-details">
                                    <h4><?php echo htmlspecialchars($item['producto']); ?></h4>
                                    <p>Cantidad: <?php echo $item['cantidad']; ?> × $<?php echo number_format($item['precio_unitario'], 0, ',', '.'); ?></p>
                                </div>
                                <div class="item-actions">
                                <div class="item-price">$<?php echo number_format($subtotal, 0, ',', '.'); ?></div>
                                    <a href="carrito_accion.php?accion=eliminar&id_producto=<?php echo $item['id_producto']; ?>" class="btn-eliminar">✕ Eliminar</a>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <div class="total-box">
                            <span>Total General:</span>
                            <strong>$<?php echo number_format($total_compra, 0, ',', '.'); ?></strong>
                        </div>
                    </div>

                    <!-- PANEL 2: VALIDACIÓN CONTRATANTE TRANSACCIONAL -->
                    <div class="card-panel">
                        <h3 style="font-size:15px; margin-bottom:20px; color:#475569; text-transform:uppercase; letter-spacing:0.5px;">2. Información de Facturación (Validación ACID)</h3>
                        
                        <div class="form-group">
                            <label>Nombre Completo del Comprador</label>
                            <input type="text" name="nombre_cliente" class="form-control" placeholder="Ej. Juan Pérez" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Correo Electrónico</label>
                            <input type="email" name="email_cliente" class="form-control" placeholder="ejemplo@correo.com" required>
                        </div>
                        
                        <!-- El botón envía los datos del cliente hacia metodo_pago.php -->
                        <button type="submit" class="btn-submit">🔒 Continuar al Método de Pago</button>
                        <a href="index.php" class="btn-back">← Volver al catálogo principal</a>
                    </div>
                    
                </form>
            <?php endif; ?>
        </div>
    </main>

</body>
</html>
