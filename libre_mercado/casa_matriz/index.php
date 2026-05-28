<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once 'Conexion.php';

$db = Conexion::conectar();

// Ventas totales por sucursal
$sqlVentasPorSucursal = "
    SELECT
        s.sucursal AS nombre_suc,
        s.id_sucursal,
        COUNT(v.id_venta) AS total_ventas,
        COALESCE(SUM(v.total), 0) AS ingresos_totales
    FROM sucursales s
    LEFT JOIN ventas v ON s.id_sucursal = v.id_sucursal
    GROUP BY s.id_sucursal, s.sucursal
    ORDER BY s.id_sucursal
";
$ventasPorSucursal = $db->query($sqlVentasPorSucursal)->fetchAll();

// Stock consolidado por producto y sucursal
$sqlStock = "
    SELECT
        p.id_producto,
        p.producto,
        p.precio_unitario AS precio,
        s.sucursal AS nombre_suc,
        st.cantidad AS stock
    FROM productos p
    JOIN stock st ON p.id_producto = st.id_producto
    JOIN sucursales s ON st.id_sucursal = s.id_sucursal
    WHERE p.activo = 1
    ORDER BY p.id_producto, s.id_sucursal
";
$stockData = $db->query($sqlStock)->fetchAll();

// Últimas 10 ventas consolidadas
$sqlUltimasVentas = "
    SELECT
        v.id_venta,
        c.cliente,
        s.sucursal AS nombre_suc,
        v.total,
        v.fecha
    FROM ventas v
    JOIN clientes c ON v.id_cliente = c.id_cliente
    JOIN sucursales s ON v.id_sucursal = s.id_sucursal
    ORDER BY v.id_venta DESC
    LIMIT 10
";
$ultimasVentas = $db->query($sqlUltimasVentas)->fetchAll();

// Totales globales
$sqlGlobal = "SELECT COUNT(*) AS total_ventas, COALESCE(SUM(total), 0) AS ingresos_globales FROM ventas";
// Alias id_suc para mantener compatibilidad con la lógica PHP más abajo
$global = $db->query($sqlGlobal)->fetch();

// Agrupar stock por producto para la tabla consolidada
$stockPorProducto = [];
foreach ($stockData as $row) {
    $stockPorProducto[$row['id_producto']]['producto'] = $row['producto'];
    $stockPorProducto[$row['id_producto']]['precio']   = $row['precio'];
    $stockPorProducto[$row['id_producto']][$row['nombre_suc']] = $row['stock'];
}

// Colores por sucursal
$colores = [
    'Sucursal Norte'  => ['bg' => '#fff9c4', 'accent' => '#b7950b', 'badge' => '#856404'],
    'Sucursal Centro' => ['bg' => '#d6eaf8', 'accent' => '#1a5276', 'badge' => '#1a5276'],
    'Sucursal Sur'    => ['bg' => '#d5f5e3', 'accent' => '#1a6b37', 'badge' => '#1a6b37'],
];
$iconos = ['Sucursal Norte' => '🟡', 'Sucursal Centro' => '🔵', 'Sucursal Sur' => '🟢'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Casa Matriz — ML Mercado</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Helvetica Neue', Arial, sans-serif; margin: 0; background-color: #f0f2f5; color: #333; }

        /* NAVBAR CASA MATRIZ */
        .navbar {
            background: linear-gradient(135deg, #1c2833 0%, #2c3e50 100%);
            padding: 15px 50px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.3);
        }
        .navbar-brand { display: flex; align-items: center; gap: 12px; }
        .logo { font-size: 24px; font-weight: bold; color: #fff159; text-decoration: none; }
        .matriz-badge {
            background-color: #fff159;
            color: #1c2833;
            padding: 4px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            letter-spacing: 0.5px;
        }
        .nav-sucursales { display: flex; gap: 12px; }
        .nav-suc-btn {
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 13px;
            font-weight: bold;
            transition: opacity 0.2s;
        }
        .nav-suc-btn:hover { opacity: 0.85; }
        .btn-norte  { background-color: #fff159; color: #333; }
        .btn-centro { background-color: #3498db; color: white; }
        .btn-sur    { background-color: #27ae60; color: white; }

        /* CONTENIDO */
        .main { max-width: 1300px; margin: 30px auto; padding: 0 20px; }

        /* KPI CARDS */
        .kpi-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 30px; }
        .kpi-card {
            background: white;
            border-radius: 10px;
            padding: 20px 25px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.07);
            border-left: 5px solid #ccc;
        }
        .kpi-label { font-size: 12px; color: #888; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px; }
        .kpi-value { font-size: 28px; font-weight: bold; color: #333; }
        .kpi-sub   { font-size: 12px; color: #aaa; margin-top: 4px; }

        /* SUCURSAL CARDS */
        .sucursales-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 30px; }
        .suc-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.07);
            transition: transform 0.2s, box-shadow 0.2s;
            text-decoration: none;
            color: inherit;
            display: block;
        }
        .suc-card:hover { transform: translateY(-3px); box-shadow: 0 6px 16px rgba(0,0,0,0.12); }
        .suc-header { display: flex; align-items: center; gap: 12px; margin-bottom: 20px; }
        .suc-icon { font-size: 28px; }
        .suc-name { font-size: 18px; font-weight: bold; }
        .suc-name small { display: block; font-size: 11px; font-weight: normal; color: #888; }
        .suc-stats { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .stat-box { background: #f8f9fa; border-radius: 8px; padding: 12px; text-align: center; }
        .stat-num { font-size: 22px; font-weight: bold; }
        .stat-label { font-size: 11px; color: #888; margin-top: 2px; }
        .btn-ir { display: block; text-align: center; margin-top: 18px; padding: 10px; border-radius: 6px; font-weight: bold; font-size: 14px; text-decoration: none; color: white; transition: opacity 0.2s; }
        .btn-ir:hover { opacity: 0.85; }

        /* TABLA STOCK */
        .section-title { font-size: 18px; font-weight: bold; color: #333; margin-bottom: 15px; display: flex; align-items: center; gap: 8px; }
        .card-tabla { background: white; border-radius: 10px; padding: 25px; box-shadow: 0 2px 6px rgba(0,0,0,0.07); margin-bottom: 25px; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        th { background-color: #f8f9fa; padding: 12px 15px; text-align: left; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; color: #666; border-bottom: 2px solid #eee; }
        td { padding: 12px 15px; border-bottom: 1px solid #f0f0f0; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background-color: #fafafa; }
        .stock-ok   { color: #00a650; font-weight: bold; }
        .stock-bajo { color: #e67e22; font-weight: bold; }
        .stock-cero { color: #cc0000; font-weight: bold; }
        .badge-suc { padding: 3px 10px; border-radius: 12px; font-size: 11px; font-weight: bold; }
        .precio-col { color: #333; }

        /* ULTIMAS VENTAS */
        .venta-row-norte  { border-left: 4px solid #b7950b; }
        .venta-row-centro { border-left: 4px solid #1a5276; }
        .venta-row-sur    { border-left: 4px solid #1a6b37; }

        .footer { text-align: center; color: #aaa; font-size: 12px; padding: 30px; }
    </style>
</head>
<body>

<header class="navbar">
    <div class="navbar-brand">
        <a href="index.php" class="logo">ML Mercado</a>
        <span class="matriz-badge">🏢 CASA MATRIZ</span>
    </div>
    <nav class="nav-sucursales">
        <a href="../sucursal_norte/index.php"  class="nav-suc-btn btn-norte">🟡 Norte</a>
        <a href="../sucursal_centro/index.php" class="nav-suc-btn btn-centro">🔵 Centro</a>
        <a href="../sucursal_sur/index.php"    class="nav-suc-btn btn-sur">🟢 Sur</a>
    </nav>
</header>

<main class="main">

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
                'Sucursal Norte'  => '../sucursal_norte/index.php',
                'Sucursal Centro' => '../sucursal_centro/index.php',
                'Sucursal Sur'    => '../sucursal_sur/index.php',
            ];
            $ruta = $rutas[$nombre] ?? '#';
        ?>
        <div class="suc-card">
            <div class="suc-header">
                <span class="suc-icon"><?php echo $icon; ?></span>
                <div class="suc-name">
                    <?php echo htmlspecialchars($nombre); ?>
                    <?php if ($suc['id_sucursal'] == 1): ?>
                        <small>⭐ Casa Matriz / Sede Principal</small>
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
                    <td class="precio-col">$<?php echo number_format($data['precio'], 0, ',', '.'); ?></td>
                    <td class="<?php echo $claseStock($norte); ?>"><?php echo $norte; ?></td>
                    <td class="<?php echo $claseStock($centro); ?>"><?php echo $centro; ?></td>
                    <td class="<?php echo $claseStock($sur); ?>"><?php echo $sur; ?></td>
                    <td><strong><?php echo $total; ?></strong></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($stockPorProducto)): ?>
                    <tr><td colspan="7" style="text-align:center; color:#aaa; padding:30px;">No hay datos de inventario aún. Asegúrate de tener filas en <code>stock</code>.</td></tr>
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
                    <th>#Venta</th>
                    <th>Cliente</th>
                    <th>Sucursal</th>
                    <th>Total</th>
                    <th>Fecha</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($ultimasVentas)): ?>
                    <tr><td colspan="5" style="text-align:center; color:#aaa; padding:30px;">Aún no hay ventas registradas.</td></tr>
                <?php else: ?>
                    <?php foreach ($ultimasVentas as $v):
                        $claseRow = [
                            'Sucursal Norte'  => 'venta-row-norte',
                            'Sucursal Centro' => 'venta-row-centro',
                            'Sucursal Sur'    => 'venta-row-sur',
                        ][$v['nombre_suc']] ?? '';
                    ?>
                    <tr class="<?php echo $claseRow; ?>">
                        <td>#<?php echo $v['id_venta']; ?></td>
                        <td><?php echo htmlspecialchars($v['cliente']); ?></td>
                        <td>
                            <?php echo $iconos[$v['nombre_suc']] ?? '⚪'; ?>
                            <?php echo htmlspecialchars($v['nombre_suc']); ?>
                        </td>
                        <td><strong>$<?php echo number_format($v['total'], 0, ',', '.'); ?></strong></td>
                        <td style="color:#999; font-size:13px;"><?php echo $v['fecha'] ?? 'N/A'; ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</main>

<footer class="footer">
    ML Mercado — Sistema Distribuido con Transacciones ACID · Casa Matriz controla la red de 3 sucursales
</footer>

</body>
</html>
