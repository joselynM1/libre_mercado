<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once 'Conexion.php';
require_once 'Producto.php';

$host = $_SERVER['HTTP_HOST'];

switch (true) {
    case strpos($host, 'sucursal_norte.local') !== false:
        $id_sucursal_actual = 1;
        $nombre_sucursal = 'Sucursal Norte';
        break;

    case strpos($host, 'sucursal_centro.local') !== false:
        $id_sucursal_actual = 2;
        $nombre_sucursal = 'Sucursal Centro';
        break;

    case strpos($host, 'sucursal_sur.local') !== false:
        $id_sucursal_actual = 3;
        $nombre_sucursal = 'Sucursal Sur';
        break;

    default:
        $id_sucursal_actual = 1;
        $nombre_sucursal = 'Sucursal Norte';
}

// Contar ítems en carrito para la Navbar
$total_items_carrito = 0;
if (isset($_SESSION['carrito']) && is_array($_SESSION['carrito'])) {
    foreach ($_SESSION['carrito'] as $item) {
        $total_items_carrito += $item['cantidad'];
    }
}

// Consultar productos usando vista SQL
$productos       = [];
$nodo_disponible = true;
try {
    $producto = new Producto();
    $productos = $producto->listarPorSucursal($id_sucursal_actual);
} catch (Exception $e) {
    $nodo_disponible = false;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Libre Mercado — <?php echo $nombre_sucursal; ?></title>
    <link rel="stylesheet" href="assets/index.css">
</head>
<body>

    <header class="header">
        <div class="brand">
            <a href="index.php" class="brand-logo">Libre<span>Mercado</span></a>
            <span class="sucursal-tag">📍 <?php echo $nombre_sucursal; ?></span>
        </div>
        <div class="nav-actions">
            <a href="ver_carrito.php" class="btn-cart">
                🛒 Mi Carrito <span class="cart-count"><?php echo $total_items_carrito; ?></span>
            </a>
            <a href="http://casa_matriz.local" class="btn-matriz">🏢 Casa Matriz</a>
        </div>
    </header>

    <main class="container">

        <div class="hero-banner">
            <div class="hero-text">
                <span class="hero-badge">Entorno transaccional ACID</span>
                <h2>Conectividad Local en <span><?php echo $nombre_sucursal; ?></span></h2>
                <p>Explora nuestro catálogo exclusivo. Los valores y existencias de stock se encuentran validados de manera unificada y en tiempo real con nuestra Casa Matriz.</p>
            </div>
            <div class="hero-icon">💻</div>
        </div>

        <div class="search-filter-bar">
            <input type="text" class="search-input" placeholder="Buscar productos en esta sucursal...">
            <button class="filter-btn active">Todos los productos</button>
            <button class="filter-btn">Electrónica</button>
            <button class="filter-btn">Computación</button>
        </div>

        <div class="products-grid">
            <?php if (!$nodo_disponible): ?>
                <div style="grid-column:1/-1; background:#fef2f2; border:2px solid #dc2626; padding:30px; text-align:center; border-radius:12px;">
                    <div style="font-size:32px; margin-bottom:10px;">⚠️</div>
                    <h3 style="color:#991b1b; margin-bottom:8px;">Nodo de Base de Datos No Disponible</h3>
                    <p style="color:#7f1d1d; font-size:14px; line-height:1.6;">El catálogo no puede cargarse porque el nodo de inventario está fuera de línea.<br>
                    <strong>Comportamiento CP activo:</strong> El sistema protege la consistencia rechazando operaciones sin conexión verificada.</p>
                </div>
            <?php elseif (empty($productos)): ?>
                <div style="grid-column:1/-1; background:white; border:1px dashed #cbd5e1; padding:40px; text-align:center; color:#64748b; border-radius:12px;">
                    No hay productos disponibles en esta sucursal actualmente.
                </div>
            <?php else: ?>
                <?php foreach ($productos as $prod): ?>
                    <div class="product-card">
                        <div class="product-info">
                            <h3><?php echo htmlspecialchars($prod['producto']); ?></h3>
                            <p><?php echo htmlspecialchars($prod['descripcion'] ?? 'Sin descripción disponible.'); ?></p>
                        </div>
                        <div>
                            <div class="product-meta">
                                <span class="price">$<?php echo number_format($prod['precio_unitario'], 0, ',', '.'); ?></span>
                                <?php if ($prod['stock'] > 0): ?>
                                    <span class="stock-badge stock-in">Stock: <?php echo $prod['stock']; ?></span>
                                <?php else: ?>
                                    <span class="stock-badge stock-out">Agotado</span>
                                <?php endif; ?>
                            </div>
                            <form action="carrito_accion.php" method="POST">
                                <input type="hidden" name="id_producto" value="<?php echo $prod['id_producto']; ?>">
                                <input type="hidden" name="producto" value="<?php echo htmlspecialchars($prod['producto']); ?>">
                                <input type="hidden" name="precio" value="<?php echo $prod['precio_unitario']; ?>">
                                <button type="submit" class="btn-add" <?php echo ($prod['stock'] <= 0) ? 'disabled' : ''; ?>>
                                    🛍️ Agregar al Carrito
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <footer class="footer">
        &copy; <?php echo date('Y'); ?> Libre Mercado Distributed Platforms — Todos los derechos reservados.
    </footer>

</body>
</html>