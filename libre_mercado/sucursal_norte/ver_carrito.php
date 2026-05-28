<?php
// C:\xampp\htdocs\sucursal_norte\ver_carrito.php
session_start();

// Obtener los productos que están guardados en la sesión (si no hay ninguno, inicializa un arreglo vacío)
$items_carrito = isset($_SESSION['carrito']) ? $_SESSION['carrito'] : [];
$subtotal = 0;
$total_items = 0;

// Calcular el total de la compra dinámicamente recorriendo los artículos agregados
foreach ($items_carrito as $item) {
    $subtotal += ($item['precio_unitario'] * $item['cantidad']);
    $total_items += $item['cantidad'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Carrito - ML Mercado</title>
    <style>
        body { 
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; 
            margin: 0; 
            background-color: #f5f5f5; 
            color: #333; 
        }
        /* Navbar superior Mercado Libre */
        .navbar { 
            background-color: #fff159; 
            padding: 12px 50px; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            box-shadow: 0 1px 2px 0 rgba(0,0,0,0.1);
        }
        .logo { 
            font-size: 22px; 
            font-weight: bold; 
            color: #333; 
            text-decoration: none; 
        }
        .search-bar { 
            width: 45%; 
            padding: 8px 15px; 
            border: none; 
            border-radius: 2px; 
            box-shadow: 0 1px 2px 0 rgba(0,0,0,0.2);
            font-size: 14px;
        }
        .cart-info {
            font-size: 14px;
            font-weight: bold;
        }
        
        /* Contenedor en dos columnas alineado a tu interfaz */
        .layout { 
            max-width: 1100px; 
            margin: 40px auto; 
            display: flex; 
            gap: 20px; 
            padding: 0 20px; 
        }
        .col-productos { 
            flex: 2; 
        }
        .col-resumen { 
            flex: 1; 
        }
        
        /* Tarjeta de Producto Individual */
        .card-producto { 
            background: white; 
            border-radius: 6px; 
            padding: 20px; 
            display: flex; 
            gap: 20px; 
            box-shadow: 0 1px 2px rgba(0,0,0,0.1); 
            margin-bottom: 15px; 
            border: 1px solid #ededed; 
        }
        .prod-img { 
            width: 100px; 
            height: 100px; 
            background-color: #f0f0f0; 
            border-radius: 4px; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            font-size: 30px; 
        }
        .prod-detalles { 
            flex: 1; 
        }
        .prod-titulo { 
            font-size: 16px; 
            margin: 0 0 5px 0; 
            font-weight: normal; 
            color: #333;
        }
        .prod-vendedor { 
            font-size: 12px; 
            color: #999; 
            margin-bottom: 15px; 
        }
        .prod-precio { 
            font-size: 20px; 
            font-weight: bold; 
            text-align: right; 
            min-width: 120px; 
        }
        .controles-cantidad { 
            display: flex; 
            align-items: center; 
            gap: 10px; 
            border: 1px solid #ddd; 
            width: fit-content; 
            padding: 4px 12px; 
            border-radius: 4px; 
            background: #fff;
        }
        .btn-cant { 
            background: none; 
            border: none; 
            font-size: 16px; 
            cursor: pointer; 
            font-weight: bold; 
            color: #3483fa;
        }
        .btn-borrar { 
            background: none; 
            border: none; 
            color: #cc0000; 
            cursor: pointer; 
            margin-left: 20px; 
            font-size: 18px; 
        }
        
        /* Tarjeta lateral del Resumen de Compra */
        .card-resumen { 
            background: white; 
            border-radius: 6px; 
            padding: 25px; 
            box-shadow: 0 1px 2px rgba(0,0,0,0.1); 
            border: 1px solid #ededed; 
        }
        .resumen-titulo { 
            font-size: 18px; 
            font-weight: bold; 
            margin-top: 0; 
            margin-bottom: 20px; 
        }
        .fila-resumen { 
            display: flex; 
            justify-content: space-between; 
            margin-bottom: 15px; 
            font-size: 15px; 
            color: #666;
        }
        .fila-total { 
            display: flex; 
            justify-content: space-between; 
            margin-top: 25px; 
            font-size: 20px; 
            font-weight: bold; 
            border-top: 1px solid #eee; 
            padding-top: 15px; 
            color: #333;
        }
        
        .btn-continuar { 
            background-color: #3483fa; 
            color: white; 
            border: none; 
            width: 100%; 
            padding: 14px; 
            border-radius: 6px; 
            font-weight: bold; 
            font-size: 16px; 
            cursor: pointer; 
            margin-top: 15px; 
            transition: background 0.2s;
        }
        .btn-continuar:hover { 
            background-color: #2969ce; 
        }
        .btn-seguir { 
            display: block; 
            text-align: center; 
            color: #3483fa; 
            text-decoration: none; 
            margin-top: 15px; 
            font-size: 14px; 
        }
        
        .alerta-envio { 
            background-color: #e6f7ee; 
            color: #00a650; 
            border: 1px solid #a3e6c2; 
            padding: 12px; 
            border-radius: 6px; 
            margin-top: 15px; 
            font-size: 13px; 
            font-weight: bold; 
        }
    </style>
</head>
<body>

    <!-- Navbar superior Mercado Libre -->
    <header class="navbar">
        <a href="index.php" class="logo">ML Mercado</a>
        <input type="text" class="search-bar" placeholder="Buscar productos, marcas y más...">
        <div class="cart-info">🛒 Carrito (<?php echo $total_items; ?>)</div>
    </header>

    <div class="layout">
        <!-- COLUMNA IZQUIERDA: LISTADO DINÁMICO DE PRODUCTOS -->
        <div class="col-productos">
            <?php if (empty($items_carrito)): ?>
                <!-- Estado cuando no hay elementos en la sesión -->
                <div class="card-producto" style="justify-content: center; padding: 40px;">
                    <div style="text-align: center;">
                        <p style="font-size: 18px; color: #666; margin-bottom: 20px;">Tu carrito está vacío.</p>
                        <a href="index.php" class="btn-continuar" style="text-decoration: none; display: inline-block; padding: 10px 20px;">Ir al catálogo</a>
                    </div>
                </div>
            <?php else: ?>
                <!-- Renderizado de productos desde la sesión -->
                <?php foreach ($items_carrito as $item): ?>
                    <div class="card-producto">
                        <div class="prod-img">💻</div>
                        <div class="prod-detalles">
                            <h3 class="prod-titulo"><?php echo htmlspecialchars($item['producto']); ?></h3>
                            <div class="prod-vendedor">Vendido por TechStore Pro</div>
                            
                            <div style="display: flex; align-items: center;">
                                <div class="controles-cantidad">
                                    <button class="btn-cant" onclick="alert('Funcionalidad opcional: Modificar cantidad en base de datos')">-</button>
                                    <span><strong><?php echo $item['cantidad']; ?></strong></span>
                                    <button class="btn-cant" onclick="alert('Funcionalidad opcional: Modificar cantidad en base de datos')">+</button>
                                </div>
                                <!-- Enlace al controlador que remueve este producto mediante su ID -->
                                <a href="carrito_accion.php?accion=eliminar&id_producto=<?php echo $item['id_producto']; ?>" onclick="return confirm('¿Remover este producto de la compra?')">
                                    <button class="btn-borrar" title="Eliminar artículo">🗑️</button>
                                </a>
                            </div>
                        </div>
                        <div class="prod-precio">
                            $<?php echo number_format($item['precio_unitario'], 0, ',', '.'); ?>
                            <div style="font-size: 12px; color: #00a650; font-weight: normal; margin-top: 5px;">Envío gratis</div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- COLUMNA DERECHA: RESUMEN DE COMPRA Y TOTALES -->
        <div class="col-resumen">
            <div class="card-resumen">
                <div class="resumen-titulo">Resumen de compra</div>
                
                <div class="fila-resumen">
                    <span>Productos (<?php echo $total_items; ?>)</span>
                    <span>$<?php echo number_format($subtotal, 0, ',', '.'); ?></span>
                </div>
                
                <div class="fila-total">
                    <span>Total</span>
                    <span>$<?php echo number_format($subtotal, 0, ',', '.'); ?></span>
                </div>

                <!-- Formulario que envía los datos hacia la pantalla del proceso de pago -->
                <form action="metodo_pago.php" method="POST">
                    <button type="submit" class="btn-continuar" <?php echo empty($items_carrito) ? 'disabled style="background-color:#ccc; cursor:not-allowed;"' : ''; ?>>
                        Continuar compra
                    </button>
                </form>
                
