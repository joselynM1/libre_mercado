const SUC_NOMBRES = {1: 'Sucursal Norte', 2: 'Sucursal Centro', 3: 'Sucursal Sur'};

let PRODUCTOS = [];
let FILTRO_CATEGORIA = 'Todos';
let TEXTO_BUSQUEDA = '';
let CARRITO = []; // [{id_producto, producto, precio, id_sucursal, cantidad}]

document.addEventListener('DOMContentLoaded', () => {
    cargarStock();

    document.getElementById('input-buscar').addEventListener('input', e => {
        TEXTO_BUSQUEDA = e.target.value.trim().toLowerCase();
        renderProductos();
    });

    document.querySelectorAll('.pill-filtro').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.pill-filtro').forEach(b => b.classList.remove('activo'));
            btn.classList.add('activo');
            FILTRO_CATEGORIA = btn.dataset.categoria;
            renderProductos();
        });
    });

    document.getElementById('btn-carrito').addEventListener('click', () => togglePanelCarrito(true));
    document.getElementById('btn-cerrar-carrito').addEventListener('click', () => togglePanelCarrito(false));
    document.getElementById('overlay').addEventListener('click', () => togglePanelCarrito(false));
    document.getElementById('btn-confirmar-compra').addEventListener('click', abrirCheckout);

    document.getElementById('btn-confirmar-pago').addEventListener('click', () => confirmarCompra('msg-checkout'));
    document.getElementById('btn-volver-catalogo').addEventListener('click', ev => {
        ev.preventDefault();
        cerrarCheckout();
    });

    // Refresco automático cada 5 segundos para reflejar caídas/recuperaciones de nodos
    setInterval(cargarStock, 5000);
});

/* ---------------- Carga de datos ---------------- */

let PRIMERA_CARGA = true;

async function cargarStock() {
    const grid = document.getElementById('grid-productos');
    if (PRIMERA_CARGA) {
        grid.innerHTML = '<p style="color:#6E7A8A">Consultando los 3 nodos...</p>';
    }

    try {
        const res = await fetch('api/stock_consolidado.php');
        const data = await res.json();

        renderNodosHeader(data.estado_nodos);
        PRODUCTOS = data.productos;

        if (PRIMERA_CARGA) {
            renderCategorias(PRODUCTOS);
            renderProductos();
            PRIMERA_CARGA = false;
        } else {
            actualizarStockEnCards();
        }
    } catch (e) {
        if (PRIMERA_CARGA) {
            grid.innerHTML = '<p style="color:#E25C5C">No se pudo conectar con el servidor.</p>';
        }
    }
}

function renderNodosHeader(estadoNodos) {
    const cont = document.getElementById('nodos-header');
    cont.innerHTML = '';
    for (const [id, estado] of Object.entries(estadoNodos)) {
        const pill = document.createElement('span');
        pill.className = 'nodo-pill' + (estado !== 'ok' ? ' caido' : '');
        pill.innerHTML = `<span class="dot"></span> ${SUC_NOMBRES[id]}`;
        cont.appendChild(pill);
    }
}

function renderCategorias(productos) {
    const cont = document.getElementById('filtros-categoria');
    const categorias = [...new Set(productos.map(p => p.categoria).filter(Boolean))];

    // Deja solo el pill "Todos" + las categorias detectadas
    cont.querySelectorAll('.pill-filtro:not([data-categoria="Todos"])').forEach(el => el.remove());

    categorias.forEach(cat => {
        const btn = document.createElement('button');
        btn.className = 'pill-filtro' + (FILTRO_CATEGORIA === cat ? ' activo' : '');
        btn.dataset.categoria = cat;
        btn.textContent = cat;
        btn.addEventListener('click', () => {
            document.querySelectorAll('.pill-filtro').forEach(b => b.classList.remove('activo'));
            btn.classList.add('activo');
            FILTRO_CATEGORIA = cat;
            renderProductos();
        });
        cont.appendChild(btn);
    });
}

/* ---------------- Render de productos ---------------- */

function renderProductos() {
    const grid = document.getElementById('grid-productos');
    grid.innerHTML = '';

    const visibles = PRODUCTOS.filter(p => {
        const coincideTexto = p.producto.toLowerCase().includes(TEXTO_BUSQUEDA);
        const coincideCategoria = FILTRO_CATEGORIA === 'Todos' || p.categoria === FILTRO_CATEGORIA;
        return coincideTexto && coincideCategoria;
    });

    if (visibles.length === 0) {
        grid.innerHTML = '<p style="color:#6E7A8A">No hay productos que coincidan con la búsqueda.</p>';
        return;
    }

    visibles.forEach(p => grid.appendChild(crearCardProducto(p)));
}

function crearCardProducto(p) {
    const card = document.createElement('div');
    card.className = 'producto-card';
    card.dataset.id = p.id_producto;

    const sinStock = opcionesSucursalHtml(p) === '';

    card.innerHTML = `
        ${p.categoria ? `<span class="producto-cat">${p.categoria}</span>` : ''}
        <h3>${p.producto}</h3>
        <p class="producto-desc">${p.descripcion ? p.descripcion : 'Sin descripción disponible.'}</p>
        <div class="producto-precio">$${Number(p.precio).toLocaleString('es-CL')}</div>
        <div class="nodos-stock">${chipsHtml(p)}</div>
        <div class="producto-footer">
            <select class="select-sucursal-compra" ${sinStock ? 'disabled' : ''}>
                ${sinStock ? '<option>Sin stock</option>' : opcionesSucursalHtml(p)}
            </select>
            <input type="number" class="input-cantidad-card" min="1" value="1" ${sinStock ? 'disabled' : ''}>
            <button class="btn-agregar" ${sinStock ? 'disabled' : ''}>Agregar al Carrito</button>
        </div>
    `;

    card.querySelector('.btn-agregar').addEventListener('click', () => {
        const select = card.querySelector('.select-sucursal-compra');
        const idSucursal = parseInt(select.value, 10);
        const cantidad = parseInt(card.querySelector('.input-cantidad-card').value, 10) || 1;
        if (!select.disabled) agregarAlCarrito(p, idSucursal, cantidad);
    });

    return card;
}

function chipsHtml(p) {
    return [1, 2, 3].map(idSuc => {
        if (p.stock[idSuc] === undefined) {
            return `<div class="nodo-chip caido">
                        <span class="nodo-label">${nombreCorto(idSuc)}</span>
                        <span class="nodo-valor">N/D</span>
                    </div>`;
        }
        const cls = p.stock[idSuc] <= 5 ? 'bajo' : 'ok';
        return `<div class="nodo-chip ${cls}">
                    <span class="nodo-label">${nombreCorto(idSuc)}</span>
                    <span class="nodo-valor">${p.stock[idSuc]}</span>
                </div>`;
    }).join('');
}

function opcionesSucursalHtml(p) {
    return [1, 2, 3]
        .filter(idSuc => p.stock[idSuc] !== undefined && p.stock[idSuc] > 0)
        .map(idSuc => `<option value="${idSuc}">${nombreCorto(idSuc)} (${p.stock[idSuc]})</option>`)
        .join('');
}

/**
 * Actualiza solo los chips de stock y las opciones de sucursal de las cards
 * ya existentes, sin recrear el grid completo (evita el parpadeo en el
 * refresco automático).
 */
function actualizarStockEnCards() {
    PRODUCTOS.forEach(p => {
        const card = document.querySelector(`.producto-card[data-id="${p.id_producto}"]`);
        if (!card) return; // producto no visible por el filtro actual

        card.querySelector('.nodos-stock').innerHTML = chipsHtml(p);

        const select = card.querySelector('.select-sucursal-compra');
        const cantidadInput = card.querySelector('.input-cantidad-card');
        const btn = card.querySelector('.btn-agregar');
        const valorPrevio = select.value;
        const opciones = opcionesSucursalHtml(p);
        const sinStock = opciones === '';

        select.innerHTML = sinStock ? '<option>Sin stock</option>' : opciones;
        select.disabled = sinStock;
        cantidadInput.disabled = sinStock;
        btn.disabled = sinStock;

        if (!sinStock && [...select.options].some(o => o.value === valorPrevio)) {
            select.value = valorPrevio;
        }
    });
}

function nombreCorto(idSuc) {
    return {1: 'Norte', 2: 'Centro', 3: 'Sur'}[idSuc];
}

/* ---------------- Carrito ---------------- */

function agregarAlCarrito(producto, idSucursal, cantidad) {
    const existente = CARRITO.find(i => i.id_producto === producto.id_producto && i.id_sucursal === idSucursal);
    if (existente) {
        existente.cantidad += cantidad;
    } else {
        CARRITO.push({
            id_producto: producto.id_producto,
            producto: producto.producto,
            precio: producto.precio,
            id_sucursal: idSucursal,
            cantidad: cantidad,
        });
    }
    renderCarrito();
    togglePanelCarrito(true);
}

function renderCarrito() {
    document.getElementById('contador-carrito').textContent = CARRITO.reduce((acc, i) => acc + i.cantidad, 0);

    const body = document.getElementById('carrito-body');
    const footer = document.getElementById('carrito-footer');

    if (CARRITO.length === 0) {
        body.innerHTML = '<p class="carrito-vacio">Tu carrito está vacío.<br>Agrega productos desde el catálogo.</p>';
        footer.style.display = 'none';
        return;
    }

    footer.style.display = 'block';
    body.innerHTML = '';
    let total = 0;

    CARRITO.forEach((item, idx) => {
        const subtotal = item.precio * item.cantidad;
        total += subtotal;

        const div = document.createElement('div');
        div.className = 'item-carrito';
        div.innerHTML = `
            <div class="item-carrito-top">
                <span>${item.producto}</span>
                <span>$${subtotal.toLocaleString('es-CL')}</span>
            </div>
            <div class="item-carrito-meta">
                <span>${nombreCorto(item.id_sucursal)} · x${item.cantidad}</span>
                <button class="item-carrito-quitar" data-idx="${idx}">Quitar</button>
            </div>
        `;
        body.appendChild(div);
    });

    body.querySelectorAll('.item-carrito-quitar').forEach(btn => {
        btn.addEventListener('click', () => {
            CARRITO.splice(parseInt(btn.dataset.idx, 10), 1);
            renderCarrito();
        });
    });

    document.getElementById('total-carrito').textContent = '$' + total.toLocaleString('es-CL');
}

function togglePanelCarrito(mostrar) {
    document.getElementById('panel-carrito').classList.toggle('visible', mostrar);
    document.getElementById('overlay').classList.toggle('visible', mostrar);
}

/* ---------------- Pasarela de pago (checkout) ---------------- */

function abrirCheckout() {
    if (CARRITO.length === 0) return;
    togglePanelCarrito(false);
    document.getElementById('msg-checkout').className = 'mensaje';
    renderCheckoutItems();
    document.getElementById('vista-checkout').classList.add('visible');
}

function cerrarCheckout() {
    document.getElementById('vista-checkout').classList.remove('visible');
}

function renderCheckoutItems() {
    const cont = document.getElementById('checkout-items');
    cont.innerHTML = '';
    let total = 0;
    const nodos = new Set();

    CARRITO.forEach(item => {
        const subtotal = item.precio * item.cantidad;
        total += subtotal;
        nodos.add(item.id_sucursal);

        const div = document.createElement('div');
        div.className = 'checkout-item-row';
        div.innerHTML = `
            <div class="checkout-item-info">
                <strong>${item.producto}</strong>
                <div class="checkout-item-meta">Cantidad: ${item.cantidad} × $${item.precio.toLocaleString('es-CL')} · ${nombreCorto(item.id_sucursal)}</div>
            </div>
            <div class="checkout-item-precio">$${subtotal.toLocaleString('es-CL')}</div>
        `;
        cont.appendChild(div);
    });

    document.getElementById('checkout-total').textContent = '$' + total.toLocaleString('es-CL');

    const nodosCont = document.getElementById('checkout-nodos');
    nodosCont.innerHTML = [...nodos].map(id => `<span class="checkout-nodo-badge">Nodo: ${SUC_NOMBRES[id]}</span>`).join('');
}

/* ---------------- Confirmar compra (usado por el carrito y la pasarela) ---------------- */

async function confirmarCompra(idMsg = 'msg-carrito') {
    const msg = document.getElementById(idMsg);
    msg.className = 'mensaje';

    if (CARRITO.length === 0) return;

    // En la pasarela, validamos los datos de facturación (simulación de validación previa a la transacción ACID)
    if (idMsg === 'msg-checkout') {
        const nombre = document.getElementById('checkout-nombre').value.trim();
        const email = document.getElementById('checkout-email').value.trim();
        if (!nombre || !email) {
            msg.classList.add('error');
            msg.textContent = 'Completa el nombre y el correo del comprador antes de confirmar.';
            return;
        }
    }

    const idBtn = idMsg === 'msg-checkout' ? 'btn-confirmar-pago' : 'btn-confirmar-compra';
    const btn = document.getElementById(idBtn);
    const textoOriginal = btn.textContent;
    btn.disabled = true;
    btn.textContent = 'Procesando...';

    // Cliente fijo de demostración (cliente registrado en la BD: id_usuario = 1)
    const ID_CLIENTE_DEMO = 1;

    const resultados = [];
    for (const item of [...CARRITO]) {
        try {
            const res = await fetch('api/procesar_venta.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    id_sucursal: item.id_sucursal,
                    id_cliente: ID_CLIENTE_DEMO,
                    id_producto: item.id_producto,
                    cantidad: item.cantidad,
                }),
            });
            const data = await res.json();
            resultados.push({item, ok: data.ok, mensaje: data.mensaje});
        } catch (e) {
            resultados.push({item, ok: false, mensaje: 'Error de conexión.'});
        }
    }

    // Deja en el carrito solo los items que fallaron
    CARRITO = resultados.filter(r => !r.ok).map(r => r.item);
    renderCarrito();
    if (idMsg === 'msg-checkout') renderCheckoutItems();

    const exitosos = resultados.filter(r => r.ok).length;
    const fallidos = resultados.filter(r => !r.ok);

    if (fallidos.length === 0) {
        msg.classList.add('ok');
        msg.textContent = `Compra confirmada: ${exitosos} producto(s) procesado(s) correctamente. Stock descontado de forma atómica.`;
        if (idMsg === 'msg-checkout') {
            setTimeout(cerrarCheckout, 1600);
        }
    } else {
        msg.classList.add('error');
        msg.textContent = `${exitosos} procesado(s). ${fallidos.length} fallaron: ${fallidos.map(f => f.mensaje).join(' | ')}`;
    }

    btn.disabled = false;
    btn.textContent = textoOriginal;

    cargarStock();
}
