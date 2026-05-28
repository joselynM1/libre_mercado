<?php
// C:\xampp\htdocs\sucursal_norte\index.php
session_start(); // Permite leer los productos agregados en tiempo real
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'Producto.php';
$productoModel = new Producto();
$productos = $productoModel->listar();

// Calcular la cantidad total de artículos en el carrito para el contador flotante
$total_items_navbar = 0;
if (isset($_SESSION['carrito'])) {
    foreach ($_SESSION['carrito'] as $item) {
        $total_items_navbar += $item['cantidad'];
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>ML Mercado - Catálogo</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; margin: 0; padding: 0; background-color: #f5f5f5; }
        .navbar { background-color: #fff159; padding: 12px 50px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 1px 2px rgba(0,0,0,0.1); }
        .logo { font-size: 22px; font-weight: bold; color: #333; text-decoration: none; }
        .search-bar { width: 45%; padding: 8px 15px; border: none; border-radius: 2px; box-shadow: 0 1px 2px rgba(0,0,0,0.2); font-size: 14px; }
        
        /* Botón de carrito con estilo dinámico */
        .cart-btn { background: none; border: 1px solid #333; padding: 8px 15px; border-radius: 5px; cursor: pointer; font-weight: bold; text-decoration: none; color: #333; display: inline-block; }
        .cart-count { background-color: #cc0000; color: white; border-radius: 50%; padding: 2px 6px; font-size: 11px; margin-left: 5px; vertical-align: top; display: inline-block; }
        
        .main-container { max-width: 1200px; margin: 40px auto; padding: 0 20px; }
        .title { font-size: 14px; color: #666; margin-bottom: 20px; }
        .products-grid { display: flex; gap: 20px; flex-wrap: wrap; }
        .product-card { background: white; width: 240px; border-radius: 4px; box-shadow: 0 1px 2px rgba(0,0,0,0.1); overflow: hidden; display: flex; flex-direction: column; justify-content: space-between; border: 1px solid #ededed; }
        .product-image { background-color: #f0f0f0; height: 200px; display: flex; align-items: center; justify-content: center; color: #999; font-size: 12px; }
        .product-info { padding: 15px; }
        .shipping { color: #00a650; font-size: 12px; font-weight: bold; margin: 5px 0; }
        .product-name { font-size: 14px; color: #333; margin: 5px 0; font-weight: normal; }
        .price { font-size: 22px; color: #333; margin: 10px 0 5px 0; }
        .stock-label { font-size: 11px; color: #999; margin-bottom: 15px; }
        .add-to-cart { background-color: #3483fa; color: white; border: none; width: 100%; padding: 10px; border-radius: 4px; font-weight: bold; cursor: pointer; transition: background 0.2s; }
        .add-to-cart:hover { background-color: #2969ce; }
    </style>
</head>
<body>

    <header class="navbar">
        <a href="index.php" class="logo">ML Mercado</a>
        <input type="text" class="search-bar" placeholder="Buscar productos, marcas y más...">
        
        <!-- Enlace al archivo del carrito mostrando la cantidad de artículos acumulados -->
        <a href="ver_carrito.php" class="cart-btn">
            🛒 Carrito 
            <?php if ($total_items_navbar > 0): ?>
                <span class="cart-count"><?php echo $total_items_navbar; ?></span>
            <?php endif; ?>
        </a>
    </header>

    <main class="main-container">
        <div class="title">Catálogo completo de productos disponibles</div>
        
        <div class="products-grid">
            <?php if (empty($productos)): ?>
                <p>No hay productos disponibles en este momento.</p>
            <?php else: ?>
                <?php foreach ($productos as $p): ?>
                    <div class="product-card">
                        <div>
                            <div class="product-image">🖼️ Imagen de Ejemplo</div>
                            <div class="product-info">
                                <div class="shipping">🚚 Envío gratis</div>
                                <h2 class="product-name"><?php echo htmlspecialchars($p['producto']); ?></h2>
                                <div class="price">$ <?php echo number_format($p['precio'], 0, ',', '.'); ?></div>
                                <div class="stock-label">Disponible para entrega inmediata</div>
                            </div>
                        </div>
                        <div style="padding: 0 15px 15px 15px;">
                            <!-- Envía al controlador de sesión y regresa al index.php de inmediato -->
                            <a href="carrito_accion.php?accion=agregar&id_prod=<?php echo $p['id_prod']; ?>" style="text-decoration: none;">
                                <button class="add-to-cart">Añadir al Carrito</button>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

</body>
</html>
