<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LibreMercado — Vista Consolidada</title>
    <link rel="stylesheet" href="assets/style.css?v=2">
</head>
<body>

    <!-- Header -->
    <header class="header">
        <div class="logo">Libre<span>Mercado</span></div>
        <div class="nodos-header" id="nodos-header">
            <span class="nodo-pill"><span class="dot"></span> Sucursal Norte</span>
            <span class="nodo-pill"><span class="dot"></span> Sucursal Centro</span>
            <span class="nodo-pill"><span class="dot"></span> Sucursal Sur</span>
        </div>
        <button class="btn-carrito" id="btn-carrito">
            🛒 Mi Carrito <span class="contador-carrito" id="contador-carrito">0</span>
        </button>
        <a href="admin.php" class="nav-link">Administración</a>
    </header>

    <div class="contenedor">

        <!-- Hero -->
        <section class="hero">
            <div class="hero-texto">
                <span class="eyebrow">Entorno transaccional ACID · Modelo CP</span>
                <h1>Stock unificado de <span>3 sucursales</span> en tiempo real</h1>
                <p>
                    Esta vista consulta simultáneamente los nodos de Sucursal Norte, Centro y Sur.
                    Cada compra descuenta inventario mediante una transacción atómica en el nodo
                    correspondiente; los traspasos entre sucursales usan Two-Phase Commit y se
                    cancelan por completo ante cualquier falla de conexión.
                </p>
            </div>
            <div class="hero-grafico">
                <svg class="nodos-svg" width="220" height="180" viewBox="0 0 220 180">
                    <line class="nodo-linea" x1="110" y1="90" x2="40" y2="30"/>
                    <line class="nodo-linea" x1="110" y1="90" x2="180" y2="30"/>
                    <line class="nodo-linea" x1="110" y1="90" x2="110" y2="160"/>
                    <circle class="nodo-circ" cx="40"  cy="30"  r="22"/>
                    <circle class="nodo-circ" cx="180" cy="30"  r="22"/>
                    <circle class="nodo-circ" cx="110" cy="160" r="22"/>
                    <circle class="nodo-centro" cx="110" cy="90" r="14"/>
                    <text x="40"  y="34"  text-anchor="middle">N</text>
                    <text x="180" y="34"  text-anchor="middle">C</text>
                    <text x="110" y="164" text-anchor="middle">S</text>
                </svg>
            </div>
        </section>

        <!-- Buscador y filtros -->
        <section class="barra-filtros">
            <input type="text" id="input-buscar" class="input-buscar" placeholder="Buscar productos en todas las sucursales...">
            <div id="filtros-categoria" style="display:flex; gap:10px; flex-wrap:wrap;">
                <button class="pill-filtro activo" data-categoria="Todos">Todos los productos</button>
            </div>
        </section>

        <!-- Catalogo -->
        <section class="grid-productos" id="grid-productos">
            <p style="color:#6E7A8A">Cargando catálogo...</p>
        </section>

    </div>

    <!-- Carrito -->
    <div class="overlay" id="overlay"></div>
    <aside class="panel-carrito" id="panel-carrito">
        <div class="panel-carrito-head">
            <h2>Mi Carrito</h2>
            <button class="btn-cerrar" id="btn-cerrar-carrito">×</button>
        </div>
        <div class="panel-carrito-body" id="carrito-body">
            <p class="carrito-vacio">Tu carrito está vacío.<br>Agrega productos desde el catálogo.</p>
        </div>
        <div class="panel-carrito-footer" id="carrito-footer" style="display:none">
            <div class="total-carrito">
                <span>Total</span>
                <span id="total-carrito">$0</span>
            </div>
            <button class="btn-confirmar" id="btn-confirmar-compra">Ir a Pagar</button>
            <div id="msg-carrito" class="mensaje"></div>
        </div>
    </aside>

    <!-- Vista Checkout / Pasarela de Pago -->
    <div class="vista-checkout" id="vista-checkout">
        <div class="checkout-topbar">
            <div class="logo">Libre<span>Mercado</span></div>
            <span class="badge-pasarela">🛒 Pasarela de Pago</span>
            <div class="checkout-nodos" id="checkout-nodos"></div>
        </div>

        <div class="checkout-contenedor">
            <h1>Tu Carrito de Compras</h1>

            <div class="checkout-card">
                <h2>1. Resumen de artículos</h2>
                <div id="checkout-items"></div>
                <div class="checkout-divider"></div>
                <div class="checkout-total-row">
                    <span>Total General:</span>
                    <span id="checkout-total">$0</span>
                </div>
            </div>

            <div class="checkout-card">
                <h2>2. Información del comprador</h2>

                <label>Nombre completo</label>
                <input type="text" id="checkout-nombre" placeholder="Ej. Juan Pérez">

                <label>Correo electrónico</label>
                <input type="email" id="checkout-email" placeholder="ejemplo@correo.com">

                <p style="font-size:0.82rem; color:var(--muted); margin:8px 0 0;">
                    Si no estás registrado, se creará tu cuenta automáticamente.
                </p>

                <button class="btn-pago" id="btn-confirmar-pago">🔒 Confirmar y Descontar Stock de Forma Atómica</button>
                <div id="msg-checkout" class="mensaje"></div>

                <a href="#" class="volver-link" id="btn-volver-catalogo">← Volver al catálogo principal</a>
            </div>
        </div>
    </div>

    <script src="assets/app.js?v=3"></script>
</body>
</html>
