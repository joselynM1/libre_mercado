<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once 'Conexion.php';
 
// Variables por defecto
$ventasPorSucursal = [];
$stockData = [];
$ultimasVentas = [];
$global = ['total_ventas' => 0, 'ingresos_globales' => 0];
$stockPorProducto = [];
$nodo_disponible = true;
 
try {
    $db = Conexion::conectar();
 
    // Consultas simplificadas usando vistas de la BD
    $ventasPorSucursal = $db->query("SELECT * FROM vista_ventas_por_sucursal")->fetchAll();
    $stockData         = $db->query("SELECT * FROM vista_stock_consolidado")->fetchAll();
    $ultimasVentas     = $db->query("SELECT * FROM vista_ultimas_ventas")->fetchAll();
    $global            = $db->query("SELECT * FROM vista_totales_globales")->fetch();
 
    foreach ($stockData as $row) {
        $stockPorProducto[$row['id_producto']]['producto']       = $row['producto'];
        $stockPorProducto[$row['id_producto']]['precio']         = $row['precio'];
        $stockPorProducto[$row['id_producto']][$row['nombre_suc']] = $row['stock'];
    }
 
} catch (Exception $e) {
    $nodo_disponible = false;
}
 
$iconos = ['Sucursal Norte' => '🟡', 'Sucursal Centro' => '🔵', 'Sucursal Sur' => '🟢'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Casa Matriz — ML Mercado</title>
        <link rel="stylesheet" href="assets/style.css">
</head>
<body>
 
<header class="navbar">
    <div class="navbar-brand">
        <a href="index.php" class="logo">ML Mercado</a>
        <span class="matriz-badge">🏢 CASA MATRIZ</span>
    </div>
    <nav class="nav-sucursales">
        <a href="http://sucursal_norte.local/index.php" class="nav-suc-btn btn-norte">🟡 Norte</a>
        <a href="http://sucursal_centro.local/index.php" class="nav-suc-btn btn-centro">🏢 Centro</a>
        <a href="http://sucursal_sur.local/index.php" class="nav-suc-btn btn-sur">🟢 Sur</a>
    </nav>
</header>
 
<main class="main">
 
    <?php if (!$nodo_disponible): ?>
        <!-- AVISO CP CUANDO MYSQL ESTÁ CAÍDO -->
        <div class="cp-alert">
            <div style="font-size:40px;">⚠️</div>
            <h2>Nodo Central No Disponible — Comportamiento CP</h2>
            <p>El dashboard no puede cargarse porque el nodo de base de datos central está fuera de línea.<br>
            Ningún dato parcial o inconsistente será mostrado.</p>
            <div class="cp-detail">
                <strong>Teorema CAP — Par CP activo:</strong> Ante una partición de red, Casa Matriz prioriza
                Consistencia sobre Disponibilidad. El sistema rechaza mostrar datos que no puede verificar en tiempo real.
            </div>
        </div>
    <?php else: ?>
 
    <!-- KPIs GLOBALES -->
    <div class="kpi-grid">
        <div class="kpi-card" style="border-left-color:#e67e22;">
            <div class="kpi-label">Red de Sucursales</div>
            <div class="kpi-value">3</div>
            <div class="kpi-sub">Norte · Centro · Sur</div>
        </div>
        <div class="kpi-card" style="border-left-color:#3498db;">
            <div class="kpi-label">Ventas Totales (Red)</div>
            <div class="kpi-value"><?php echo number_format($global['total_ventas']); ?></div>
            <div class="kpi-sub">transacciones procesadas</div>
        </div>
        <div class="kpi-card" style="border-left-color:#27ae60;">
            <div class="kpi-label">Ingresos Consolidados</div>
            <div class="kpi-value">$<?php echo number_format($global['ingresos_globales'], 0, ',', '.'); ?></div>
            <div class="kpi-sub">suma de las 3 sucursales</div>
        </div>
        <div class="kpi-card" style="border-left-color:#8e44ad;">
            <div class="kpi-label">Motor de Datos</div>
            <div class="kpi-value" style="font-size:18px;">ACID</div>
            <div class="kpi-sub">transacciones con rollback</div>
        </div>
    </div>
 
    <!-- TARJETAS DE SUCURSALES -->
    <div class="section-title">📍 Estado de Sucursales</div>
    <div class="sucursales-grid">
        <?php
        $btnColores = ['#b7950b', '#1a5276', '#1a6b37'];
        $i = 0;
        foreach ($ventasPorSucursal as $suc):
            $nombre = $suc['nombre_suc'];
            $icon   = $iconos[$nombre] ?? '⚪';
            $color  = $btnColores[$i++];
            $rutas  = [
                'Sucursal Norte'  => "http://sucursal_norte.local/index.php",
                'Sucursal Centro' => "http://sucursal_centro.local/index.php",
                'Sucursal Sur'    => "http://sucursal_sur.local/index.php",
            ];
            $ruta = $rutas[$nombre] ?? '#';
        ?>
        <div class="suc-card">
            <div class="suc-header">
                <span class="suc-icon"><?php echo $icon; ?></span>
                <div class="suc-name">
                    <?php echo htmlspecialchars($nombre); ?>
                    <?php if ($suc['id_sucursal'] == 1): ?>
                        <small>⭐ Sede Principal</small>
                    <?php else: ?>
                        <small>Sucursal operativa</small>
                    <?php endif; ?>
                </div>
            </div>
            <div class="suc-stats">
                <div class="stat-box">
                    <div class="stat-num"><?php echo number_format($suc['total_ventas']); ?></div>
                    <div class="stat-label">Ventas</div>
                </div>
                <div class="stat-box">
                    <div class="stat-num" style="font-size:16px;">$<?php echo number_format($suc['ingresos_totales'], 0, ',', '.'); ?></div>
                    <div class="stat-label">Ingresos</div>
                </div>
            </div>
            <a href="<?php echo $ruta; ?>" class="btn-ir" style="background-color:<?php echo $color; ?>;">
                Ir a Sucursal →
            </a>
        </div>
        <?php endforeach; ?>
    </div>
 
    <!-- STOCK CONSOLIDADO -->
    <div class="card-tabla">
        <div class="section-title">📦 Inventario Consolidado por Sucursal</div>
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Producto</th>
                    <th>Precio Unit.</th>
                    <th style="color:#b7950b;">🟡 Stock Norte</th>
                    <th style="color:#1a5276;">🔵 Stock Centro</th>
                    <th style="color:#1a6b37;">🟢 Stock Sur</th>
                    <th>Total Red</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($stockPorProducto as $idProd => $data):
                    $norte  = $data['Sucursal Norte']  ?? 0;
                    $centro = $data['Sucursal Centro'] ?? 0;
                    $sur    = $data['Sucursal Sur']    ?? 0;
                    $total  = $norte + $centro + $sur;
                    $claseStock = fn($s) => $s == 0 ? 'stock-cero' : ($s < 5 ? 'stock-bajo' : 'stock-ok');
                ?>
                <tr>
                    <td><?php echo $idProd; ?></td>
                    <td><strong><?php echo htmlspecialchars($data['producto']); ?></strong></td>
                    <td>$<?php echo number_format($data['precio'], 0, ',', '.'); ?></td>
                    <td class="<?php echo $claseStock($norte); ?>"><?php echo $norte; ?></td>
                    <td class="<?php echo $claseStock($centro); ?>"><?php echo $centro; ?></td>
                    <td class="<?php echo $claseStock($sur); ?>"><?php echo $sur; ?></td>
                    <td><strong><?php echo $total; ?></strong></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($stockPorProducto)): ?>
                    <tr><td colspan="7" style="text-align:center; color:#aaa; padding:30px;">No hay datos de inventario aún.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
 
    <!-- ULTIMAS VENTAS -->
    <div class="card-tabla">
        <div class="section-title">🧾 Últimas Ventas — Red Completa</div>
        <table>
            <thead>
                <tr>
                    <th>#Venta</th><th>Cliente</th><th>Sucursal</th><th>Total</th><th>Fecha</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($ultimasVentas)): ?>
                    <tr><td colspan="5" style="text-align:center; color:#aaa; padding:30px;">Aún no hay ventas registradas.</td></tr>
                <?php else: ?>
                    <?php foreach ($ultimasVentas as $v):
                        $claseRow = ['Sucursal Norte' => 'venta-row-norte', 'Sucursal Centro' => 'venta-row-centro', 'Sucursal Sur' => 'venta-row-sur'][$v['nombre_suc']] ?? '';
                    ?>
                    <tr class="<?php echo $claseRow; ?>">
                        <td>#<?php echo $v['id_venta']; ?></td>
                        <td><?php echo htmlspecialchars($v['cliente']); ?></td>
                        <td><?php echo $iconos[$v['nombre_suc']] ?? '⚪'; ?> <?php echo htmlspecialchars($v['nombre_suc']); ?></td>
                        <td><strong>$<?php echo number_format($v['total'], 0, ',', '.'); ?></strong></td>
                        <td style="color:#999; font-size:13px;"><?php echo $v['fecha'] ?? 'N/A'; ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
 
    <?php endif; ?>
 
</main>
 
<footer class="footer">
    ML Mercado — Sistema Distribuido con Transacciones ACID · Casa Matriz controla la red de 3 sucursales
</footer>
 
</body>
</html>