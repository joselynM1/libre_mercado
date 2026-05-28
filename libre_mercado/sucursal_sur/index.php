<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'Producto.php';
$productoModel = new Producto();
$productos = $productoModel->listar();

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
    <title>ML Mercado - Sucursal Sur</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; margin: 0; padding: 0; background-color: #f5f5f5; }
        .navbar { background-color: #c8f5d4; padding: 12px 50px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 1px 2px rgba(0,0,0,0.1); }
        .logo { font-size: 22px; font-weight: bold; color: #1a6b37; text-decoration: none; }
        .sucursal-badge { background-color: #1a6b37; color: white; padding: 4px 12px; border-radius: 20px; font-size: 13px; font-weight: bold; }
        .search-bar { width: 40%; padding: 8px 15px; border: none; border-radius: 2px; box-shadow: 0 1px 2px rgba(0,0,0,0.2); font-size: 14px; }
        .cart-btn { background: none; border: 1px solid #1a6b37; padding: 8px 15px; border-radius: 5px; cursor: pointer; font-weight: bold; text-decoration: none; color: #1a6b37; display: inline-block; }
        .cart-count { background-color: #cc0000; color: white; border-radius: 50%; padding: 2px 6px; font-size: 11px; margin-left: 5px; vertical-align: top; display: inline-block; }
        .nav-links { display: flex; gap: 15px; align-items: center; }
        .nav-link { color: #1a6b37; text-decoration: none; font-size: 13px; }
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
        .add-to-cart { background-color: #1a6b37; color: white; border: none; width: 100%; padding: 10px; border-radius: 4px; font-weight: bold; cursor: pointer; transition: background 0.2s; }
        .add-to-cart:hover { background-color: #145228; }
    </style>
</head>
<body>

    <header class="navbar">
        <div style="display:flex; align-items:center; gap:15px;">
            <a href="index.php" class="logo">ML Mercado</a>
            <span class="sucursal-badge">Sucursal Sur</span>
        </div>
        <input type="text" class="search-bar" placeholder="Buscar productos, marcas y más...">
        <div class="nav-links">
            <a href="../casa_matriz/index.php" class="nav-link">🏢 Casa Matriz</a>
            <a href="ver_carrito.php" class="cart-btn">
                🛒 Carrito
                <?php if ($total_items_navbar > 0): ?>
                    <span class="cart-count"><?php echo $total_items_navbar; ?></span>
                <?php endif; ?>
            </a>
        </div>
    </header>

    <main class="main-container">
        <div class="title">Catálogo completo de productos disponibles — Sucursal Sur</div>
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
                                <div class="price">$ <?php echo number_format($p['precio_unitario'], 0, ',', '.'); ?></div>
                                <div class="stock-label">Disponible para entrega inmediata</div>
                            </div>
                        </div>
                        <div style="padding: 0 15px 15px 15px;">
                            <a href="carrito_accion.php?accion=agregar&id_producto=<?php echo $p['id_producto']; ?>" style="text-decoration: none;">
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
