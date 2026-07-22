const SUC_NOMBRES = {1: 'Sucursal Norte', 2: 'Sucursal Centro', 3: 'Sucursal Sur'};

document.addEventListener('DOMContentLoaded', () => {
    cargarEstadoNodos();
    cargarProductos();
    cargarClientes();
    cargarProveedores();

    // Tabs
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('activo'));
            document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('activo'));
            btn.classList.add('activo');
            document.getElementById('tab-' + btn.dataset.tab).classList.add('activo');
        });
    });

    // Cerrar modales (incluye modal-historial que no tiene form que resetear)
    document.querySelectorAll('[data-cerrar-modal]').forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.dataset.cerrarModal;
            document.getElementById(id).classList.remove('visible');
            const form = document.getElementById('form-' + id.replace('modal-', ''));
            if (form) form.reset();
        });
    });

    // Botones "Nuevo"
    document.getElementById('btn-nuevo-producto').addEventListener('click', () => abrirModalProducto());
    document.getElementById('btn-nuevo-cliente').addEventListener('click', () => abrirModalCliente());
    document.getElementById('btn-nuevo-proveedor').addEventListener('click', () => abrirModalProveedor());

    // Forms
    document.getElementById('form-producto').addEventListener('submit', guardarProducto);
    document.getElementById('form-cliente').addEventListener('submit', guardarCliente);
    document.getElementById('form-proveedor').addEventListener('submit', guardarProveedor);

    // Refresco periódico del estado de nodos
    setInterval(cargarEstadoNodos, 5000);

    // Reabastecimiento
    cargarDatosReabastecimiento();
    document.getElementById('btn-agregar-item').addEventListener('click', agregarItemOrden);
    document.getElementById('btn-registrar-compra').addEventListener('click', registrarCompra);
    document.getElementById('compra-sucursal').addEventListener('change', () => {
        document.getElementById('historial-sucursal-nombre').textContent =
            SUC_NOMBRES[document.getElementById('compra-sucursal').value];
        cargarHistorialCompras();
    });

    // Traspasos entre sucursales
    document.getElementById('form-traspaso').addEventListener('submit', procesarTraspaso);

    // Ajuste manual de stock (sp_actualizar_stock)
    document.getElementById('form-ajuste-stock').addEventListener('submit', procesarAjusteStock);

    // Historial de ventas: carga al abrir el tab o al pulsar actualizar
    document.querySelector('[data-tab="historial-ventas"]').addEventListener('click', cargarHistorialVentas);
    document.getElementById('btn-refrescar-historial').addEventListener('click', cargarHistorialVentas);

    // Simulación de nodos: carga al abrir el tab
    document.querySelector('[data-tab="nodos"]').addEventListener('click', cargarEstadoNodosPanel);
});

/* ---------------- Estado de nodos ---------------- */

async function cargarEstadoNodos() {
    try {
        const res = await fetch('api/stock_consolidado.php');
        const data = await res.json();
        const cont = document.getElementById('nodos-header');
        cont.innerHTML = '';
        for (const [id, estado] of Object.entries(data.estado_nodos)) {
            const pill = document.createElement('span');
            pill.className = 'nodo-pill' + (estado !== 'ok' ? ' caido' : '');
            pill.innerHTML = `<span class="dot"></span> ${SUC_NOMBRES[id]}`;
            cont.appendChild(pill);
        }
    } catch (e) { /* silencioso */ }
}

/* ---------------- Helpers de modal ---------------- */

function abrirModal(id) { document.getElementById(id).classList.add('visible'); }
function cerrarModal(id) {
    document.getElementById(id).classList.remove('visible');
    document.getElementById('form-' + id.replace('modal-', '')).reset();
}

function mostrarMensaje(idMsg, ok, texto) {
    const msg = document.getElementById(idMsg);
    msg.className = 'mensaje ' + (ok ? 'ok' : 'error');
    msg.textContent = texto;
}

/* ================= PRODUCTOS ================= */

async function cargarProductos() {
    const tbody = document.getElementById('tbody-productos');
    try {
        const res = await fetch('api/productos_crud.php');
        const data = await res.json();
        if (!data.ok) throw new Error(data.mensaje);

        tbody.innerHTML = '';
        if (data.productos.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6">No hay productos registrados.</td></tr>';
            return;
        }

        data.productos.forEach(p => {
            const tr = document.createElement('tr');
            if (!p.activo) tr.classList.add('inactivo');
            tr.innerHTML = `
                <td>${p.id_producto}</td>
                <td>${p.producto}</td>
                <td>${p.categoria ?? '-'}</td>
                <td>$${Number(p.precio_unitario).toLocaleString('es-CL')}</td>
                <td><span class="estado-pill ${p.activo ? 'activo-si' : 'activo-no'}">${p.activo ? 'Activo' : 'Inactivo'}</span></td>
                <td class="acciones-tabla">
                    <button class="btn-mini" data-accion="editar">Editar</button>
                    <button class="btn-mini ${p.activo ? 'danger' : ''}" data-accion="toggle">${p.activo ? 'Dar de baja' : 'Reactivar'}</button>
                </td>
            `;
            tr.querySelector('[data-accion="editar"]').addEventListener('click', () => abrirModalProducto(p));
            tr.querySelector('[data-accion="toggle"]').addEventListener('click', () => toggleProducto(p));
            tbody.appendChild(tr);
        });
    } catch (e) {
        tbody.innerHTML = `<tr><td colspan="6" style="color:#E25C5C">${e.message}</td></tr>`;
    }
}

function abrirModalProducto(p = null) {
    document.getElementById('titulo-modal-producto').textContent = p ? 'Editar producto' : 'Nuevo producto';
    document.getElementById('producto-id').value = p ? p.id_producto : '';
    document.getElementById('producto-nombre').value = p ? p.producto : '';
    document.getElementById('producto-precio').value = p ? p.precio_unitario : '';
    document.getElementById('producto-categoria').value = p ? (p.categoria ?? '') : '';
    document.getElementById('producto-descripcion').value = p ? (p.descripcion ?? '') : '';

    // El stock inicial por sucursal solo aplica al crear un producto nuevo
    document.getElementById('grupo-stock-inicial').style.display = p ? 'none' : '';
    ['1', '2', '3'].forEach(i => document.getElementById('producto-stock-' + i).value = 0);

    abrirModal('modal-producto');
}

async function guardarProducto(ev) {
    ev.preventDefault();
    const id = document.getElementById('producto-id').value;

    const body = {
        producto: document.getElementById('producto-nombre').value.trim(),
        precio_unitario: parseFloat(document.getElementById('producto-precio').value),
        categoria: document.getElementById('producto-categoria').value.trim(),
        descripcion: document.getElementById('producto-descripcion').value.trim(),
    };

    let metodo = 'POST';
    if (id) {
        body.id_producto = parseInt(id, 10);
        metodo = 'PUT';
    } else {
        body.stock = {
            1: parseInt(document.getElementById('producto-stock-1').value, 10) || 0,
            2: parseInt(document.getElementById('producto-stock-2').value, 10) || 0,
            3: parseInt(document.getElementById('producto-stock-3').value, 10) || 0,
        };
    }

    await ejecutarCrud('api/productos_crud.php', metodo, body, 'msg-productos', () => {
        cerrarModal('modal-producto');
        cargarProductos();
    });
}

async function toggleProducto(p) {
    await ejecutarCrud('api/productos_crud.php', 'DELETE',
        {id_producto: p.id_producto, activo: p.activo ? 0 : 1},
        'msg-productos', cargarProductos);
}

/* ================= CLIENTES / USUARIOS ================= */

async function cargarClientes() {
    const tbody = document.getElementById('tbody-clientes');
    try {
        const res = await fetch('api/clientes_crud.php');
        const data = await res.json();
        if (!data.ok) throw new Error(data.mensaje);

        tbody.innerHTML = '';
        if (data.usuarios.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6">No hay usuarios registrados.</td></tr>';
            return;
        }

        data.usuarios.forEach(u => {
            const tr = document.createElement('tr');
            if (!u.activo) tr.classList.add('inactivo');
            tr.innerHTML = `
                <td>${u.id_usuario}</td>
                <td>${u.nombre}</td>
                <td>${u.email}</td>
                <td>${capitalizar(u.rol)}</td>
                <td><span class="estado-pill ${u.activo ? 'activo-si' : 'activo-no'}">${u.activo ? 'Activo' : 'Inactivo'}</span></td>
                <td class="acciones-tabla">
                    <button class="btn-mini" data-accion="editar">Editar</button>
                    <button class="btn-mini ${u.activo ? 'danger' : ''}" data-accion="toggle">${u.activo ? 'Dar de baja' : 'Reactivar'}</button>
                </td>
                <td>
                    <button class="btn-mini" data-accion="historial">Ver compras</button>
                </td>
            `;
            tr.querySelector('[data-accion="editar"]').addEventListener('click', () => abrirModalCliente(u));
            tr.querySelector('[data-accion="toggle"]').addEventListener('click', () => toggleCliente(u));
            tr.querySelector('[data-accion="historial"]').addEventListener('click', () => abrirHistorialCliente(u));
            tbody.appendChild(tr);
        });
    } catch (e) {
        tbody.innerHTML = `<tr><td colspan="6" style="color:#E25C5C">${e.message}</td></tr>`;
    }
}

function abrirModalCliente(u = null) {
    document.getElementById('titulo-modal-cliente').textContent = u ? 'Editar usuario' : 'Nuevo usuario';
    document.getElementById('cliente-id').value = u ? u.id_usuario : '';
    document.getElementById('cliente-nombre').value = u ? u.nombre : '';
    document.getElementById('cliente-email').value = u ? u.email : '';
    document.getElementById('cliente-rol').value = u ? u.rol : 'cliente';
    document.getElementById('cliente-password').value = '';

    // La contraseña inicial solo aplica al crear
    document.getElementById('grupo-password').style.display = u ? 'none' : '';

    abrirModal('modal-cliente');
}

async function guardarCliente(ev) {
    ev.preventDefault();
    const id = document.getElementById('cliente-id').value;

    const body = {
        nombre: document.getElementById('cliente-nombre').value.trim(),
        email: document.getElementById('cliente-email').value.trim(),
        rol: document.getElementById('cliente-rol').value,
    };

    let metodo = 'POST';
    if (id) {
        body.id_usuario = parseInt(id, 10);
        metodo = 'PUT';
    } else {
        body.password = document.getElementById('cliente-password').value;
    }

    await ejecutarCrud('api/clientes_crud.php', metodo, body, 'msg-clientes', () => {
        cerrarModal('modal-cliente');
        cargarClientes();
    });
}

async function toggleCliente(u) {
    await ejecutarCrud('api/clientes_crud.php', 'DELETE',
        {id_usuario: u.id_usuario, activo: u.activo ? 0 : 1},
        'msg-clientes', cargarClientes);
}

/* ================= PROVEEDORES ================= */

async function cargarProveedores() {
    const tbody = document.getElementById('tbody-proveedores');
    try {
        const res = await fetch('api/proveedores_crud.php');
        const data = await res.json();
        if (!data.ok) throw new Error(data.mensaje);

        tbody.innerHTML = '';
        if (data.proveedores.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7">No hay proveedores registrados.</td></tr>';
            return;
        }

        data.proveedores.forEach(pr => {
            const tr = document.createElement('tr');
            if (!pr.activo) tr.classList.add('inactivo');
            tr.innerHTML = `
                <td>${pr.id_proveedor}</td>
                <td>${pr.proveedor}</td>
                <td>${pr.rut ?? '-'}</td>
                <td>${pr.email ?? '-'}</td>
                <td>${pr.telefono ?? '-'}</td>
                <td><span class="estado-pill ${pr.activo ? 'activo-si' : 'activo-no'}">${pr.activo ? 'Activo' : 'Inactivo'}</span></td>
                <td class="acciones-tabla">
                    <button class="btn-mini" data-accion="editar">Editar</button>
                    <button class="btn-mini ${pr.activo ? 'danger' : ''}" data-accion="toggle">${pr.activo ? 'Dar de baja' : 'Reactivar'}</button>
                </td>
            `;
            tr.querySelector('[data-accion="editar"]').addEventListener('click', () => abrirModalProveedor(pr));
            tr.querySelector('[data-accion="toggle"]').addEventListener('click', () => toggleProveedor(pr));
            tbody.appendChild(tr);
        });
    } catch (e) {
        tbody.innerHTML = `<tr><td colspan="7" style="color:#E25C5C">${e.message}</td></tr>`;
    }
}

function abrirModalProveedor(pr = null) {
    document.getElementById('titulo-modal-proveedor').textContent = pr ? 'Editar proveedor' : 'Nuevo proveedor';
    document.getElementById('proveedor-id').value = pr ? pr.id_proveedor : '';
    document.getElementById('proveedor-nombre').value = pr ? pr.proveedor : '';
    document.getElementById('proveedor-rut').value = pr ? (pr.rut ?? '') : '';
    document.getElementById('proveedor-telefono').value = pr ? (pr.telefono ?? '') : '';
    document.getElementById('proveedor-email').value = pr ? (pr.email ?? '') : '';

    abrirModal('modal-proveedor');
}

async function guardarProveedor(ev) {
    ev.preventDefault();
    const id = document.getElementById('proveedor-id').value;

    const body = {
        proveedor: document.getElementById('proveedor-nombre').value.trim(),
        rut: document.getElementById('proveedor-rut').value.trim(),
        telefono: document.getElementById('proveedor-telefono').value.trim(),
        email: document.getElementById('proveedor-email').value.trim(),
    };

    let metodo = 'POST';
    if (id) {
        body.id_proveedor = parseInt(id, 10);
        metodo = 'PUT';
    }

    await ejecutarCrud('api/proveedores_crud.php', metodo, body, 'msg-proveedores', () => {
        cerrarModal('modal-proveedor');
        cargarProveedores();
    });
}

async function toggleProveedor(pr) {
    await ejecutarCrud('api/proveedores_crud.php', 'DELETE',
        {id_proveedor: pr.id_proveedor, activo: pr.activo ? 0 : 1},
        'msg-proveedores', cargarProveedores);
}

/* ================= Helper CRUD genérico ================= */

async function ejecutarCrud(url, metodo, body, idMsg, onExito) {
    try {
        const res = await fetch(url, {
            method: metodo,
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(body),
        });
        const data = await res.json();
        mostrarMensaje(idMsg, data.ok, data.mensaje);
        if (data.ok && onExito) onExito();
    } catch (e) {
        mostrarMensaje(idMsg, false, 'Error de conexión con el servidor.');
    }
}

function capitalizar(txt) {
    return txt.charAt(0).toUpperCase() + txt.slice(1);
}

/* ================= REABASTECIMIENTO ================= */

let PRODUCTOS_ACTIVOS = [];
let ORDEN_COMPRA = []; // [{id_producto, producto, cantidad, precio, subtotal}]

async function cargarDatosReabastecimiento() {
    // Productos activos para el selector de items
    try {
        const res = await fetch('api/productos_crud.php');
        const data = await res.json();
        if (data.ok) {
            PRODUCTOS_ACTIVOS = data.productos.filter(p => p.activo);
            const opciones = PRODUCTOS_ACTIVOS.map(p => `<option value="${p.id_producto}">${p.producto}</option>`).join('');
            document.getElementById('item-producto').innerHTML = opciones;
            document.getElementById('traspaso-producto').innerHTML = opciones;
            document.getElementById('ajuste-producto').innerHTML = opciones;
        }
    } catch (e) { /* silencioso */ }

    // Proveedores activos
    try {
        const res = await fetch('api/proveedores_crud.php');
        const data = await res.json();
        if (data.ok) {
            const activos = data.proveedores.filter(p => p.activo);
            const sel = document.getElementById('compra-proveedor');
            if (activos.length === 0) {
                sel.innerHTML = '<option value="">No hay proveedores activos</option>';
            } else {
                sel.innerHTML = activos.map(p => `<option value="${p.id_proveedor}">${p.proveedor}</option>`).join('');
            }
        }
    } catch (e) { /* silencioso */ }

    cargarHistorialCompras();
}

function agregarItemOrden() {
    const selProd = document.getElementById('item-producto');
    const idProducto = parseInt(selProd.value, 10);
    const cantidad = parseInt(document.getElementById('item-cantidad').value, 10) || 0;
    const precio = parseFloat(document.getElementById('item-precio').value) || 0;

    if (!idProducto || cantidad <= 0 || precio <= 0) {
        mostrarMensaje('msg-compra', false, 'Selecciona un producto e ingresa cantidad y precio de compra válidos.');
        return;
    }

    const producto = PRODUCTOS_ACTIVOS.find(p => p.id_producto === idProducto);
    const existente = ORDEN_COMPRA.find(i => i.id_producto === idProducto);

    if (existente) {
        existente.cantidad += cantidad;
        existente.precio = precio;
        existente.subtotal = existente.cantidad * existente.precio;
    } else {
        ORDEN_COMPRA.push({
            id_producto: idProducto,
            producto: producto ? producto.producto : ('#' + idProducto),
            cantidad,
            precio,
            subtotal: cantidad * precio,
        });
    }

    document.getElementById('item-cantidad').value = 1;
    document.getElementById('item-precio').value = '';
    document.getElementById('msg-compra').className = 'mensaje';

    renderOrdenCompra();
}

function renderOrdenCompra() {
    const tbody = document.getElementById('tbody-orden-compra');

    if (ORDEN_COMPRA.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" style="color:var(--muted)">Aún no has agregado productos a la orden.</td></tr>';
        document.getElementById('total-orden-compra').textContent = '$0';
        return;
    }

    tbody.innerHTML = '';
    let total = 0;

    ORDEN_COMPRA.forEach((item, idx) => {
        total += item.subtotal;
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>${item.producto}</td>
            <td>${item.cantidad}</td>
            <td>$${item.precio.toLocaleString('es-CL')}</td>
            <td>$${item.subtotal.toLocaleString('es-CL')}</td>
            <td><button class="btn-mini danger" data-idx="${idx}">Quitar</button></td>
        `;
        tr.querySelector('button').addEventListener('click', () => {
            ORDEN_COMPRA.splice(idx, 1);
            renderOrdenCompra();
        });
        tbody.appendChild(tr);
    });

    document.getElementById('total-orden-compra').textContent = '$' + total.toLocaleString('es-CL');
}

async function registrarCompra() {
    const msg = 'msg-compra';

    if (ORDEN_COMPRA.length === 0) {
        mostrarMensaje(msg, false, 'Agrega al menos un producto a la orden antes de registrar la compra.');
        return;
    }

    const idProveedor = document.getElementById('compra-proveedor').value;
    if (!idProveedor) {
        mostrarMensaje(msg, false, 'No hay un proveedor seleccionado.');
        return;
    }

    const body = {
        id_sucursal: parseInt(document.getElementById('compra-sucursal').value, 10),
        id_proveedor: parseInt(idProveedor, 10),
        items: ORDEN_COMPRA.map(i => ({
            id_producto: i.id_producto,
            cantidad: i.cantidad,
            precio_compra: i.precio,
        })),
    };

    const btn = document.getElementById('btn-registrar-compra');
    btn.disabled = true;
    btn.textContent = 'Registrando...';

    await ejecutarCrud('api/procesar_compra.php', 'POST', body, msg, () => {
        ORDEN_COMPRA = [];
        renderOrdenCompra();
        cargarHistorialCompras();
    });

    btn.disabled = false;
    btn.textContent = 'Registrar Compra';
}

async function cargarHistorialCompras() {
    const idSucursal = document.getElementById('compra-sucursal').value;
    const tbody = document.getElementById('tbody-historial-compras');
    tbody.innerHTML = '<tr><td colspan="5">Cargando...</td></tr>';

    try {
        const res = await fetch('api/compras_listar.php?id_sucursal=' + idSucursal);
        const data = await res.json();
        if (!data.ok) throw new Error(data.mensaje);

        if (data.compras.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5">Aún no hay compras registradas en esta sucursal.</td></tr>';
            return;
        }

        tbody.innerHTML = '';
        data.compras.forEach(c => {
            const fecha = new Date(c.fecha.replace(' ', 'T')).toLocaleString('es-CL');
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>#${c.id_compra}</td>
                <td>${fecha}</td>
                <td>${c.proveedor}</td>
                <td>$${Number(c.total).toLocaleString('es-CL')}</td>
                <td><span class="estado-pill activo-si">${capitalizar(c.estado)}</span></td>
            `;
            tbody.appendChild(tr);
        });
    } catch (e) {
        tbody.innerHTML = `<tr><td colspan="5" style="color:#E25C5C">${e.message}</td></tr>`;
    }
}

/* ================= TRASPASOS ENTRE SUCURSALES (2PC) ================= */

/* ================= AJUSTE MANUAL DE STOCK (sp_actualizar_stock) ================= */

async function procesarAjusteStock(ev) {
    ev.preventDefault();
    const msg = document.getElementById('msg-ajuste-stock');
    msg.className = 'mensaje';

    const body = {
        id_sucursal: parseInt(document.getElementById('ajuste-sucursal').value, 10),
        id_producto: parseInt(document.getElementById('ajuste-producto').value, 10),
        cantidad: parseInt(document.getElementById('ajuste-cantidad').value, 10),
        operacion: document.getElementById('ajuste-operacion').value,
    };

    const btn = ev.target.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.textContent = 'Aplicando...';

    try {
        const res = await fetch('api/ajustar_stock.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(body),
        });
        const data = await res.json();

        msg.classList.add(data.ok ? 'ok' : 'error');
        msg.textContent = data.mensaje;
        if (data.ok) cargarEstadoNodos();
    } catch (e) {
        msg.classList.add('error');
        msg.textContent = 'Error de conexión con el servidor.';
    }

    btn.disabled = false;
    btn.textContent = 'Aplicar ajuste';
}

/* ================= HISTORIAL GLOBAL DE VENTAS ================= */

async function cargarHistorialVentas() {
    const tbody  = document.getElementById('tbody-historial-ventas');
    const errDiv = document.getElementById('historial-ventas-errores');
    tbody.innerHTML = '<tr><td colspan="8">Consultando los 3 nodos...</td></tr>';
    errDiv.style.display = 'none';

    try {
        const res  = await fetch('api/ventas_todas.php');
        const data = await res.json();

        if (data.errores && data.errores.length > 0) {
            errDiv.style.display = '';
            errDiv.textContent = 'Nodos no disponibles: ' + data.errores.join(' | ');
        }

        if (!data.ventas || data.ventas.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" style="color:var(--muted)">No hay ventas registradas aún.</td></tr>';
            return;
        }

        tbody.innerHTML = '';
        data.ventas.forEach(v => {
            const fecha = new Date(v.fecha.replace(' ', 'T')).toLocaleString('es-CL');
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>#${v.id_venta}</td>
                <td style="white-space:nowrap">${fecha}</td>
                <td><strong>${v.cliente}</strong></td>
                <td style="font-size:0.82rem; color:var(--muted)">${v.email_cliente}</td>
                <td><span class="estado-pill activo-si" style="font-size:0.78rem">${v.sucursal}</span></td>
                <td style="font-size:0.82rem; color:var(--muted); max-width:260px">${v.detalle}</td>
                <td><strong>$${Number(v.total).toLocaleString('es-CL')}</strong></td>
                <td><span class="estado-pill activo-si">${capitalizar(v.estado)}</span></td>
            `;
            tbody.appendChild(tr);
        });
    } catch (e) {
        tbody.innerHTML = '<tr><td colspan="8" style="color:#E25C5C">Error al cargar el historial.</td></tr>';
    }
}

/* ================= HISTORIAL DE COMPRAS POR CLIENTE ================= */

async function abrirHistorialCliente(u) {
    document.getElementById('titulo-modal-historial').textContent = `Compras de ${u.nombre}`;
    document.getElementById('tbody-historial-cliente').innerHTML = '<tr><td colspan="6">Cargando desde los 3 nodos...</td></tr>';
    document.getElementById('historial-errores').style.display = 'none';
    abrirModal('modal-historial');

    try {
        const res  = await fetch('api/ventas_cliente.php?id_cliente=' + u.id_usuario);
        const data = await res.json();

        const errDiv = document.getElementById('historial-errores');
        if (data.errores && data.errores.length > 0) {
            errDiv.style.display = '';
            errDiv.textContent = 'Nodos no disponibles: ' + data.errores.join(' | ');
        }

        const tbody = document.getElementById('tbody-historial-cliente');
        if (!data.ventas || data.ventas.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" style="color:var(--muted)">Este cliente aún no tiene compras registradas.</td></tr>';
            return;
        }

        tbody.innerHTML = '';
        data.ventas.forEach(v => {
            const fecha = new Date(v.fecha.replace(' ', 'T')).toLocaleString('es-CL');
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>#${v.id_venta}</td>
                <td>${fecha}</td>
                <td>${v.sucursal}</td>
                <td style="font-size:0.83rem; color:var(--muted)">${v.detalle}</td>
                <td>$${Number(v.total).toLocaleString('es-CL')}</td>
                <td><span class="estado-pill activo-si">${capitalizar(v.estado)}</span></td>
            `;
            tbody.appendChild(tr);
        });
    } catch (e) {
        document.getElementById('tbody-historial-cliente').innerHTML =
            '<tr><td colspan="6" style="color:#E25C5C">Error al cargar el historial.</td></tr>';
    }
}

/* ================= SIMULACIÓN DE NODOS ================= */

const SUC_IDS = [1, 2, 3];

async function cargarEstadoNodosPanel() {
    try {
        const res  = await fetch('api/nodo_estado.php');
        const data = await res.json();
        if (!data.ok) return;
        renderPanelNodos(data.estados);
    } catch (e) { /* silencioso */ }
}

function renderPanelNodos(estados) {
    const cont = document.getElementById('panel-nodos');
    cont.innerHTML = '';

    SUC_IDS.forEach(id => {
        const online  = estados[id] === 'online';
        const nombre  = SUC_NOMBRES[id];
        const card    = document.createElement('div');
        card.className = 'nodo-sim-card ' + (online ? 'nodo-ok' : 'nodo-caido');
        card.innerHTML = `
            <div class="nodo-sim-nombre">${nombre}</div>
            <div class="nodo-sim-estado">
                <span class="dot"></span>
                <span class="nodo-sim-label">${online ? 'ONLINE' : 'OFFLINE'}</span>
            </div>
            <div class="nodo-sim-acciones">
                <button class="btn-nodo-toggle ${online ? 'danger' : 'btn-recuperar'}" data-id="${id}" data-accion="${online ? 'offline' : 'online'}">
                    ${online ? 'Simular Falla' : 'Recuperar Nodo'}
                </button>
                ${!online ? `<button class="btn-secundario btn-reconstruir" data-id="${id}" style="margin-top:8px;">Reconstruir Stock (sp)</button>` : ''}
            </div>
        `;

        card.querySelector('.btn-nodo-toggle').addEventListener('click', toggleNodo);
        if (!online) {
            card.querySelector('.btn-reconstruir').addEventListener('click', reconstruirStock);
        }

        cont.appendChild(card);
    });
}

async function toggleNodo(ev) {
    const btn         = ev.currentTarget;
    const idSucursal  = parseInt(btn.dataset.id, 10);
    const nuevoEstado = btn.dataset.accion; // 'online' o 'offline'

    btn.disabled    = true;
    btn.textContent = 'Procesando...';

    try {
        const res  = await fetch('api/nodo_estado.php', {
            method:  'POST',
            headers: {'Content-Type': 'application/json'},
            body:    JSON.stringify({ id_sucursal: idSucursal, estado: nuevoEstado }),
        });
        const data = await res.json();

        mostrarMensaje('msg-nodos', data.ok, data.mensaje);
        if (data.ok) {
            renderPanelNodos(data.estados);
            cargarEstadoNodos(); // refresca los pills del header
        }
    } catch (e) {
        mostrarMensaje('msg-nodos', false, 'Error de conexión al cambiar estado del nodo.');
        btn.disabled    = false;
        btn.textContent = nuevoEstado === 'offline' ? 'Simular Falla' : 'Recuperar Nodo';
    }
}

async function reconstruirStock(ev) {
    const btn        = ev.currentTarget;
    const idSucursal = parseInt(btn.dataset.id, 10);

    btn.disabled    = true;
    btn.textContent = 'Reconstruyendo...';

    try {
        const res  = await fetch('api/reconstruir_stock.php', {
            method:  'POST',
            headers: {'Content-Type': 'application/json'},
            body:    JSON.stringify({ id_sucursal: idSucursal }),
        });
        const data = await res.json();
        mostrarMensaje('msg-nodos', data.ok, data.mensaje);
    } catch (e) {
        mostrarMensaje('msg-nodos', false, 'Error al reconstruir stock.');
    }

    btn.disabled    = false;
    btn.textContent = 'Reconstruir Stock (sp)';
}

async function procesarTraspaso(ev) {
    ev.preventDefault();
    const msg = document.getElementById('msg-traspaso');
    msg.className = 'mensaje';

    const body = {
        id_origen: document.getElementById('traspaso-origen').value,
        id_destino: document.getElementById('traspaso-destino').value,
        id_producto: document.getElementById('traspaso-producto').value,
        cantidad: document.getElementById('traspaso-cantidad').value,
    };

    if (body.id_origen === body.id_destino) {
        msg.classList.add('error');
        msg.textContent = 'La sucursal de origen y destino no pueden ser la misma.';
        return;
    }

    const btn = ev.target.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.textContent = 'Procesando...';

    try {
        const res = await fetch('api/traspaso_stock.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(body),
        });
        const data = await res.json();

        msg.classList.add(data.ok ? 'ok' : 'error');
        msg.textContent = data.mensaje;
    } catch (e) {
        msg.classList.add('error');
        msg.textContent = 'Error de conexión con el servidor.';
    }

    btn.disabled = false;
    btn.textContent = 'Traspasar';
}
