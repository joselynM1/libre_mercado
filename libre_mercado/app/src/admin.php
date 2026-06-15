<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LibreMercado — Administración</title>
    <link rel="stylesheet" href="assets/style.css?v=2">
</head>
<body>

    <!-- Header -->
    <header class="header">
        <div class="logo">Libre<span>Mercado</span> · Admin</div>
        <div class="nodos-header" id="nodos-header">
            <span class="nodo-pill"><span class="dot"></span> Sucursal Norte</span>
            <span class="nodo-pill"><span class="dot"></span> Sucursal Centro</span>
            <span class="nodo-pill"><span class="dot"></span> Sucursal Sur</span>
        </div>
        <a href="index.php" class="nav-link">← Volver a la tienda</a>
    </header>

    <div class="contenedor">

        <section class="hero" style="padding:28px 36px;">
            <div class="hero-texto">
                <span class="eyebrow">CRUD distribuido · Catálogo replicado</span>
                <h1>Panel de <span>Administración</span></h1>
                <p>
                    Productos, clientes y proveedores están replicados en los 3 nodos.
                    Cada operación de creación, edición o baja se ejecuta de forma
                    atómica en las 3 sucursales: si algún nodo no está disponible,
                    la operación se cancela por completo (sin cambios parciales).
                </p>
            </div>
        </section>

        <!-- Tabs -->
        <div class="tabs">
            <button class="tab-btn activo" data-tab="productos">Productos</button>
            <button class="tab-btn" data-tab="clientes">Clientes / Usuarios</button>
            <button class="tab-btn" data-tab="proveedores">Proveedores</button>
            <button class="tab-btn" data-tab="compras">Reabastecimiento</button>
            <button class="tab-btn" data-tab="traspasos">Traspasos entre Sucursales</button>
        </div>

        <!-- Productos -->
        <div class="tab-panel activo" id="tab-productos">
            <div class="barra-admin">
                <h2>Productos</h2>
                <button class="btn-nuevo" id="btn-nuevo-producto">+ Nuevo producto</button>
            </div>
            <div class="tabla-admin-wrap">
                <table class="tabla-admin">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Producto</th>
                            <th>Categoría</th>
                            <th>Precio</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-productos">
                        <tr><td colspan="6">Cargando...</td></tr>
                    </tbody>
                </table>
            </div>
            <div id="msg-productos" class="mensaje"></div>
        </div>

        <!-- Clientes -->
        <div class="tab-panel" id="tab-clientes">
            <div class="barra-admin">
                <h2>Clientes / Usuarios</h2>
                <button class="btn-nuevo" id="btn-nuevo-cliente">+ Nuevo usuario</button>
            </div>
            <div class="tabla-admin-wrap">
                <table class="tabla-admin">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Email</th>
                            <th>Rol</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-clientes">
                        <tr><td colspan="6">Cargando...</td></tr>
                    </tbody>
                </table>
            </div>
            <div id="msg-clientes" class="mensaje"></div>
        </div>

        <!-- Proveedores -->
        <div class="tab-panel" id="tab-proveedores">
            <div class="barra-admin">
                <h2>Proveedores</h2>
                <button class="btn-nuevo" id="btn-nuevo-proveedor">+ Nuevo proveedor</button>
            </div>
            <div class="tabla-admin-wrap">
                <table class="tabla-admin">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Proveedor</th>
                            <th>RUT</th>
                            <th>Email</th>
                            <th>Teléfono</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-proveedores">
                        <tr><td colspan="7">Cargando...</td></tr>
                    </tbody>
                </table>
            </div>
            <div id="msg-proveedores" class="mensaje"></div>
        </div>

        <!-- Reabastecimiento -->
        <div class="tab-panel" id="tab-compras">
            <div class="barra-admin">
                <h2>Reabastecimiento</h2>
            </div>

            <div class="tabla-admin-wrap" style="padding:22px; margin-bottom:24px;">
                <div class="form-grid dos-col">
                    <div>
                        <label>Sucursal que recibe</label>
                        <select id="compra-sucursal">
                            <option value="1">Sucursal Norte</option>
                            <option value="2">Sucursal Centro</option>
                            <option value="3">Sucursal Sur</option>
                        </select>
                    </div>
                    <div>
                        <label>Proveedor</label>
                        <select id="compra-proveedor"></select>
                    </div>
                </div>

                <div class="form-grid dos-col" style="margin-top:18px;">
                    <div>
                        <label>Producto</label>
                        <select id="item-producto"></select>
                    </div>
                    <div style="display:flex; gap:8px;">
                        <div style="flex:1;">
                            <label>Cantidad</label>
                            <input type="number" id="item-cantidad" min="1" value="1">
                        </div>
                        <div style="flex:1;">
                            <label>Precio compra (CLP)</label>
                            <input type="number" id="item-precio" min="1" step="1">
                        </div>
                    </div>
                </div>
                <div class="modal-acciones" style="justify-content:flex-start; margin-top:10px;">
                    <button type="button" class="btn-secundario" id="btn-agregar-item">+ Agregar producto a la orden</button>
                </div>

                <table class="tabla-admin" style="margin-top:14px;" id="tabla-orden-compra">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Cantidad</th>
                            <th>Precio compra</th>
                            <th>Subtotal</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="tbody-orden-compra">
                        <tr><td colspan="5" style="color:var(--muted)">Aún no has agregado productos a la orden.</td></tr>
                    </tbody>
                </table>

                <div class="total-carrito" style="margin-top:14px;">
                    <span>Total orden</span>
                    <span id="total-orden-compra">$0</span>
                </div>

                <div class="modal-acciones" style="justify-content:flex-start;">
                    <button type="button" class="btn-primario" id="btn-registrar-compra">Registrar Compra</button>
                </div>
                <div id="msg-compra" class="mensaje"></div>
            </div>

            <h2 style="font-size:1.05rem;">Historial de compras — <span id="historial-sucursal-nombre">Sucursal Norte</span></h2>
            <div class="tabla-admin-wrap">
                <table class="tabla-admin">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Fecha</th>
                            <th>Proveedor</th>
                            <th>Total</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-historial-compras">
                        <tr><td colspan="5">Cargando...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Traspasos entre sucursales (2PC) -->
        <div class="tab-panel" id="tab-traspasos">
            <div class="barra-admin">
                <h2>Traspaso de stock entre sucursales (2PC)</h2>
            </div>
            <div class="tabla-admin-wrap" style="padding:22px;">
                <p class="desc" style="margin-top:0; color:var(--muted); font-size:0.88rem;">
                    Operación distribuida que afecta dos nodos a la vez. Se usa Two-Phase Commit:
                    si alguno de los dos nodos no responde, o no hay stock suficiente en el origen,
                    AMBOS hacen rollback y no se aplica ningún cambio (consistencia &gt; disponibilidad).
                </p>
                <form id="form-traspaso" class="form-grid">
                    <div>
                        <label>Sucursal origen</label>
                        <select id="traspaso-origen" required>
                            <option value="1">Sucursal Norte</option>
                            <option value="2">Sucursal Centro</option>
                            <option value="3">Sucursal Sur</option>
                        </select>
                    </div>
                    <div>
                        <label>Sucursal destino</label>
                        <select id="traspaso-destino" required>
                            <option value="2">Sucursal Centro</option>
                            <option value="1">Sucursal Norte</option>
                            <option value="3">Sucursal Sur</option>
                        </select>
                    </div>
                    <div>
                        <label>Producto</label>
                        <select id="traspaso-producto" required></select>
                    </div>
                    <div>
                        <label>Cantidad</label>
                        <input type="number" id="traspaso-cantidad" min="1" value="1" required>
                    </div>
                    <div>
                        <button type="submit">Traspasar</button>
                    </div>
                </form>
                <div id="msg-traspaso" class="mensaje"></div>
            </div>
        </div>
    </div>

    <!-- Modal: Producto -->
    <div class="modal-overlay" id="modal-producto">
        <div class="modal-box">
            <h2 id="titulo-modal-producto">Nuevo producto</h2>
            <form id="form-producto">
                <input type="hidden" id="producto-id">
                <div class="form-grid">
                    <div>
                        <label>Nombre</label>
                        <input type="text" id="producto-nombre" required>
                    </div>
                </div>
                <div class="form-grid dos-col">
                    <div>
                        <label>Precio (CLP)</label>
                        <input type="number" id="producto-precio" min="1" step="1" required>
                    </div>
                    <div>
                        <label>Categoría</label>
                        <input type="text" id="producto-categoria" placeholder="Ej: Computación">
                    </div>
                </div>
                <div class="form-grid">
                    <div>
                        <label>Descripción</label>
                        <input type="text" id="producto-descripcion">
                    </div>
                </div>
                <div class="form-grid" id="grupo-stock-inicial">
                    <div>
                        <label>Stock inicial por sucursal</label>
                        <div class="stock-inicial-grid">
                            <div>
                                <label>Norte</label>
                                <input type="number" id="producto-stock-1" min="0" value="0">
                            </div>
                            <div>
                                <label>Centro</label>
                                <input type="number" id="producto-stock-2" min="0" value="0">
                            </div>
                            <div>
                                <label>Sur</label>
                                <input type="number" id="producto-stock-3" min="0" value="0">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-acciones">
                    <button type="button" class="btn-secundario" data-cerrar-modal="modal-producto">Cancelar</button>
                    <button type="submit" class="btn-primario">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Cliente / Usuario -->
    <div class="modal-overlay" id="modal-cliente">
        <div class="modal-box">
            <h2 id="titulo-modal-cliente">Nuevo usuario</h2>
            <form id="form-cliente">
                <input type="hidden" id="cliente-id">
                <div class="form-grid">
                    <div>
                        <label>Nombre completo</label>
                        <input type="text" id="cliente-nombre" required>
                    </div>
                </div>
                <div class="form-grid dos-col">
                    <div>
                        <label>Email</label>
                        <input type="email" id="cliente-email" required>
                    </div>
                    <div>
                        <label>Rol</label>
                        <select id="cliente-rol">
                            <option value="cliente">Cliente</option>
                            <option value="operador">Operador</option>
                            <option value="administrador">Administrador</option>
                        </select>
                    </div>
                </div>
                <div class="form-grid" id="grupo-password">
                    <div>
                        <label>Contraseña inicial</label>
                        <input type="text" id="cliente-password" placeholder="Por defecto: cambiar123">
                    </div>
                </div>
                <div class="modal-acciones">
                    <button type="button" class="btn-secundario" data-cerrar-modal="modal-cliente">Cancelar</button>
                    <button type="submit" class="btn-primario">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Proveedor -->
    <div class="modal-overlay" id="modal-proveedor">
        <div class="modal-box">
            <h2 id="titulo-modal-proveedor">Nuevo proveedor</h2>
            <form id="form-proveedor">
                <input type="hidden" id="proveedor-id">
                <div class="form-grid">
                    <div>
                        <label>Nombre del proveedor</label>
                        <input type="text" id="proveedor-nombre" required>
                    </div>
                </div>
                <div class="form-grid dos-col">
                    <div>
                        <label>RUT</label>
                        <input type="text" id="proveedor-rut">
                    </div>
                    <div>
                        <label>Teléfono</label>
                        <input type="text" id="proveedor-telefono">
                    </div>
                </div>
                <div class="form-grid">
                    <div>
                        <label>Email</label>
                        <input type="email" id="proveedor-email">
                    </div>
                </div>
                <div class="modal-acciones">
                    <button type="button" class="btn-secundario" data-cerrar-modal="modal-proveedor">Cancelar</button>
                    <button type="submit" class="btn-primario">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    <script src="assets/admin.js?v=1"></script>
</body>
</html>
