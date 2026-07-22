# Libre Mercado - Sistema Distribuido (3 sucursales / 3 nodos)

## Arquitectura

- **3 nodos de base de datos** (MariaDB), uno por sucursal:
  - `db_suc1` → Sucursal Norte
  - `db_suc2` → Sucursal Centro
  - `db_suc3` → Sucursal Sur
- **1 aplicación PHP** (`app`), que es la ÚNICA página web. Internamente
  mantiene conexiones PDO independientes a los 3 nodos y consolida la
  información para mostrarla en una sola pantalla.

Todos los contenedores están en la misma red Docker (`tienda_net`), lo que
simula 3 servidores que se ven entre sí en la red.

## Cómo levantar el proyecto

Requisitos: Docker y Docker Compose instalados.

```bash
cd libre_mercado
docker compose up -d --build
```

La primera vez, MariaDB tardará unos segundos en inicializar cada base
con los datos del archivo `sql/sucX.sql` correspondiente.

Luego abre en el navegador:

```
http://localhost:8080
```

## Verificar que los 3 nodos se ven en la red (Paso 0)

```bash
docker exec -it lm_app bash
ping db_suc1
ping db_suc2
ping db_suc3
php -r "new PDO('mysql:host=db_suc2;dbname=libre_mercado_suc2','app','app123'); echo 'OK suc2';"
```

## Interfaz (estilo LibreMercado)

La página ahora tiene el estilo de marca "LibreMercado": header con estado
de los 3 nodos (punto verde = conectado, rojo = caído), banner principal,
buscador + filtros por categoría, y catálogo en cards. Cada card muestra
el stock de las **3 sucursales en paralelo** (chip verde = stock normal,
naranja = stock bajo ≤5, gris "N/D" = nodo caído).

El botón "Agregar al Carrito" permite elegir desde qué sucursal se
descuenta el stock. El carrito es lateral; "Confirmar Compra" envía una
venta por cada item a `api/procesar_venta.php` (transacción ACID en el
nodo correspondiente). Para esta demo, las ventas del carrito quedan
asociadas al cliente con `id_usuario = 1` (Juan Pérez); puedes cambiarlo
en `app/src/assets/app.js` (constante `ID_CLIENTE_DEMO`) o agregar un
selector de usuario más adelante.

## Panel de Administración (CRUD)

Desde la tienda, el botón "Administración" del header lleva a `admin.php`,
con tres pestañas: **Productos**, **Clientes/Usuarios** y **Proveedores**.

Estas tres entidades son **catálogos replicados** en los 3 nodos. Cada
operación (crear, editar, dar de baja/reactivar) se ejecuta mediante
`MultiNodo::ejecutar()`, que abre transacción en los 3 nodos y solo
confirma si los 3 responden correctamente:

- Si algún nodo no está disponible, la operación se **rechaza por
  completo**, sin aplicar cambios parciales (modelo CP).
- Los IDs (`id_producto`, `id_usuario`, `id_proveedor`) se generan de
  forma centralizada (`MultiNodo::siguienteId()`) para que el mismo
  registro tenga el mismo ID en los 3 nodos.

**Borrado lógico**: ningún "Dar de baja" hace `DELETE`. Solo cambia el
campo `activo` a 0 en los 3 nodos. Los productos inactivos dejan de
aparecer en la tienda (ya filtrados en `stock_consolidado.php`), pero
sus ventas históricas y referencias (`detalle_ventas`, `stock`) quedan
intactas. "Reactivar" vuelve a poner `activo = 1`.

### ⚠️ Migración si ya tenías los contenedores corriendo

La tabla `proveedores` ahora incluye una columna `activo` que no existía
en el esquema anterior. Los scripts de `sql/*.sql` solo se ejecutan en
contenedores **nuevos** (volumen vacío). Tienes dos opciones:

**Opción A — Recrear todo desde cero (pierdes los datos de prueba):**
```bash
docker compose down -v
docker compose up -d --build
```

**Opción B — Migrar sin perder datos**, ejecutando el script en cada nodo:
```bash
docker exec -i lm_db_suc1 mysql -uapp -papp123 libre_mercado_suc1 < sql/migracion_activo_proveedores.sql
docker exec -i lm_db_suc2 mysql -uapp -papp123 libre_mercado_suc2 < sql/migracion_activo_proveedores.sql
docker exec -i lm_db_suc3 mysql -uapp -papp123 libre_mercado_suc3 < sql/migracion_activo_proveedores.sql
```

Luego de cualquiera de las dos opciones, reconstruye la app:
```bash
docker compose up -d --build app
```

## Reabastecimiento (Compras a proveedores)

Pestaña "Reabastecimiento" del panel de administración. A diferencia del
CRUD de catálogo (que escribe en los 3 nodos), una compra es una
**transacción ACID local** al nodo de la sucursal que recibe la
mercadería — igual que una venta, pero en sentido inverso:

1. Seleccionas la sucursal que recibe, el proveedor, y armas una orden
   (producto + cantidad + precio de compra, puedes agregar varios items).
2. Al "Registrar Compra", `api/procesar_compra.php` abre una transacción
   en el nodo de esa sucursal: inserta `compras` y `detalle_compras`, y
   **aumenta** el `stock` de cada producto. Si algo falla a mitad de
   camino, hace rollback completo (no queda stock sumado sin su compra
   registrada, ni una compra sin detalle).
3. Debajo se muestra el historial de compras de esa sucursal
   (`api/compras_listar.php`).

> Como la base de datos no trae proveedores de ejemplo, antes de probar
> esto crea al menos un proveedor en la pestaña "Proveedores".

## Funcionalidades de la página

1. **Stock consolidado**: tabla con cada producto y su stock en las 3
   sucursales, leído en vivo desde los 3 nodos vía AJAX
   (`api/stock_consolidado.php`). Si un nodo está caído, esa columna
   muestra "N/D" y el resto de la página sigue funcionando (lectura
   parcial).

2. **Registrar venta** (`api/procesar_venta.php`): transacción ACID
   local al nodo de la sucursal vendedora. Si el stock es insuficiente,
   hace `rollBack()` y no se registra nada.

3. **Traspaso de stock entre sucursales** (`api/traspaso_stock.php`):
   implementa **Two-Phase Commit manual** entre dos nodos distintos.
   Si cualquiera de los dos nodos no responde o no tiene stock
   suficiente, se hace rollback en AMBOS y la operación se cancela
   completa. Esto demuestra la elección **CP** (Consistencia +
   Tolerancia a Particiones) del Teorema CAP.

## Simular una partición de red (para el informe CAP)

Para demostrar qué pasa cuando un nodo no está disponible:

```bash
docker stop lm_db_suc2
```

- Refresca la página: la columna "Sucursal Centro" mostrará "N/D" en
  vez de romper la página (disponibilidad parcial de lectura).
- Intenta una venta en Sucursal Centro o un traspaso que involucre ese
  nodo: el sistema debe rechazar la operación con un mensaje claro,
  sin dejar el stock inconsistente (consistencia priorizada).

Para reactivar el nodo:

```bash
docker start lm_db_suc2
```

## Estructura de carpetas

```
libre_mercado/
├── docker-compose.yml
├── sql/
│   ├── suc1.sql
│   ├── suc2.sql
│   └── suc3.sql
└── app/
    ├── Dockerfile
    └── src/
        ├── index.php
        ├── ConexionNodos.php
        ├── api/
        │   ├── stock_consolidado.php
        │   ├── procesar_venta.php
        │   ├── traspaso_stock.php
        │   └── clientes.php
        └── assets/
            ├── app.js
            └── style.css
```

## Próximos pasos sugeridos

- Agregar CRUD completo de productos, clientes, proveedores (con
  borrado lógico usando el campo `activo`).
- Agregar módulo de compras/reabastecimiento con proveedores.
- Agregar autenticación básica de usuarios (roles: administrador,
  operador, cliente).
- Completar el documento de arquitectura CAP (ver
  `docs/arquitectura_cap.md`).
