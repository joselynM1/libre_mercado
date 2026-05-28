<?php
// C:\xampp\htdocs\sucursal_norte\metodo_pago.php
session_start();

$items_carrito = isset($_SESSION['carrito']) ? $_SESSION['carrito'] : [];

// Si intentan entrar al pago con el carrito vacío, los mandamos al catálogo
if (empty($items_carrito)) {
    header("Location: index.php");
    exit;
}

$subtotal = 0;
$total_items = 0;
foreach ($items_carrito as $item) {
    $subtotal += ($item['precio'] * $item['cantidad']);
    $total_items += $item['cantidad'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Método de Pago - ML Mercado</title>
    <style>
        body { font-family: 'Helvetica Neue', Arial, sans-serif; margin: 0; background-color: #f5f5f5; color: #333; }
        .navbar { background-color: #fff159; padding: 12px 50px; display: flex; justify-content: space-between; align-items: center; }
        .logo { font-size: 22px; font-weight: bold; color: #333; text-decoration: none; }
        
        /* Barra de progreso de Mercado Libre */
        .progress-bar { max-width: 400px; margin: 20px auto; display: flex; align-items: center; justify-content: center; font-size: 14px; gap: 10px; }
        .step { display: flex; align-items: center; gap: 5px; color: #999; }
        .step.active { color: #3483fa; font-weight: bold; }
        .step-circle { width: 24px; height: 24px; border-radius: 50%; background: #ccc; color: white; display: flex; align-items: center; justify-content: center; font-size: 12px; }
        .step.active .step-circle { background: #3483fa; }
        .line { flex: 1; height: 2px; background: #ccc; min-width: 50px; }

        .layout { max-width: 1100px; margin: 20px auto; display: flex; gap: 20px; padding: 0 20px; }
        .col-formulario { flex: 1.8; }
        .col-resumen { flex: 1.2; }

        .card { background: white; border-radius: 6px; padding: 25px; box-shadow: 0 1px 2px rgba(0,0,0,0.1); border: 1px solid #ededed; margin-bottom: 20px; }
        h2 { font-size: 20px; margin-top: 0; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        
        /* Menú Desplegable Estilizado */
        label { display: block; margin-top: 15px; margin-bottom: 10px; font-size: 14px; font-weight: bold; color: #444; }
        select { width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 6px; font-size: 15px; background-color: white; color: #333; cursor: pointer; }
        select:focus { border-color: #3483fa; outline: none; }

        /* Resumen Lateral */
        .resumen-titulo { font-size: 16px; font-weight: bold; margin-bottom: 15px; }
        .prod-mini { display: flex; gap: 15px; margin-bottom: 15px; font-size: 13px; }
        .prod-mini-img { width: 50px; height: 50px; background: #eee; border-radius: 4px; display: flex; align-items: center; justify-content: center; }
        .fila-resumen { display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 14px; color: #666; }
        .fila-total { display: flex; justify-content: space-between; font-size: 18px; font-weight: bold; margin-top: 15px; border-top: 1px solid #eee; padding-top: 15px; }
        
        .btn-pagar { background-color: #3483fa; color: white; border: none; width: 100%; padding: 14px; border-radius: 6px; font-weight: bold; font-size: 16px; cursor: pointer; margin-top: 20px; }
        .btn-pagar:hover { background-color: #2969ce; }
        .alerta-ssl { background-color: #e6f7ee; color: #00a650; padding: 10px; border-radius: 6px; font-size: 12px; margin-top: 20px; font-weight: bold; }
    </style>
</head>
<body>

    <header class="navbar">
        <a href="index.php" class="logo">ML Mercado</a>
    </header>

    <!-- Barra de progreso -->
    <div class="progress-bar">
        <div class="step" style="color: #00a650;"><span class="step-circle" style="background:#00a650;">✓</span> Envío</div>
        <div class="line" style="background:#3483fa;"></div>
        <div class="step active"><span class="step-circle">2</span> Pago</div>
    </div>

    <!-- Formulario simplificado -->
    <form action="finalizar_compra.php" method="POST">
        <div class="layout">
            
<!-- Reemplazo exacto en la columna izquierda de metodo_pago.php -->
<div class="card">
    <h2>💳 Método de pago</h2>
    
    <label for="tipo_pago">Selecciona tu medio de pago *</label>
    <select name="tipo_pago" id="tipo_pago" required>
        <option value="" disabled selected>-- Elige una opción --</option>
        <option value="debito">💳 Tarjeta de débito</option>
        <option value="credito">💳 Tarjeta de crédito</option>
    </select>

    <!-- Campo para el Nombre -->
    <label for="nombre_cliente">Nombre Completo *</label>
    <input type="text" name="nombre_cliente" id="nombre_cliente" placeholder="Ej: Joseln Navidad" required>

    <!-- NUEVO: Campo para el Correo requerido por tu base de datos -->
    <label for="email_cliente">Correo Electrónico *</label>
    <input type="email" name="email_cliente" id="email_cliente" placeholder="ejemplo@correo.com" required>

    <div class="alerta-ssl">🔒 Transacción protegida mediante encriptación SSL del sistema distribuido</div>
</div>


            </div>

            <!-- COLUMNA DERECHA: RESUMEN DINÁMICO SIN DIRECCIÓN DE ENVÍO -->
            <div class="col-resumen">
                <div class="card">
                    <div class="resumen-titulo">Resumen de compra</div>
                    
                    <!-- Listado dinámico de los artículos en la sesión -->
                    <?php foreach ($items_carrito as $item): ?>
                        <div class="prod-mini">
                            <div class="prod-mini-img">💻</div>
                            <div style="flex:1;">
                                <strong><?php echo htmlspecialchars($item['producto']); ?></strong><br>
                                <span style="color:#999;">Cantidad: <?php echo $item['cantidad']; ?></span>
                            </div>
                            <div>$<?php echo number_format($item['precio'], 0, ',', '.'); ?></div>
                        </div>
                    <?php endforeach; ?>

                    <hr style="border: 0; border-top: 1px solid #eee;">

                    <div class="fila-resumen">
                        <span>Productos (<?php echo $total_items; ?>)</span>
                        <span>$<?php echo number_format($subtotal, 0, ',', '.'); ?></span>
                    </div>
                    <div class="fila-resumen">
                        <span>Envío</span>
                        <span style="color:#00a650; font-weight:bold;">Gratis</span>
                    </div>

                    <div class="fila-total">
                        <span>Total</span>
                        <span>$<?php echo number_format($subtotal, 0, ',', '.'); ?></span>
                    </div>

                    <!-- Botón de Pago Transaccional -->
                    <button type="submit" class="btn-pagar">
                        Pagar $<?php echo number_format($subtotal, 0, ',', '.'); ?>
                    </button>
                </div>
            </div>

        </div>
    </form>

</body>
</html>
