# LibreMercado — Sistema Distribuido de Comercio Electrónico

Prototipo de e-commerce distribuido desarrollado para el taller de **Sistemas Distribuidos**.
Implementa CRUD, transacciones ACID, Two-Phase Commit y una arquitectura **CP** (Consistencia +
Tolerancia a Particiones, según el Teorema CAP), usando **PHP + PDO**, **MariaDB**, **AJAX** y
**Docker**.

---

## 📐 Arquitectura

El sistema corre en **4 contenedores Docker** sobre una misma red:

```
                ┌──────────────────────┐
   Navegador ──▶│   Aplicación PHP      │
    (AJAX)      │   Apache + PDO        │
                │   (lm_app)            │
                └─────────┬─────┬───────┘
                  PDO      │     │ PDO         PDO
                ┌──────────┘     │      └──────────┐
                ▼                ▼                 ▼
        ┌───────────────┐ ┌───────────────┐ ┌───────────────┐
        │ DB Suc. Norte  │ │ DB Suc. Centro │ │ DB Suc. Sur    │
        │ (lm_db_suc1)   │ │ (lm_db_suc2)   │ │ (lm_db_suc3)   │
        └───────────────┘ └───────────────┘ └───────────────┘
```

Cada nodo es una instancia **MariaDB independiente**, una por sucursal. Las tablas se dividen en
dos categorías:

| Tipo | Tablas | Comportamiento |
|---|---|---|
| **Catálogo replicado** | `productos`, `usuarios`, `proveedores`, `sucursales` | Copia idéntica (mismo `id`, mismos datos) en los 3 nodos. Toda escritura se aplica a los 3 a la vez. |
| **Datos locales** | `stock`, `ventas`, `detalle_ventas`, `compras`, `detalle_compras` | Cada nodo solo contiene la información de **su propia** sucursal. |

---

## 🧰 Stack tecnológico

| Capa | Tecnología |
|---|---|
| Backend | PHP 8.2 + PDO (`pdo_mysql`) |
| Base de datos | MariaDB 10.4 (3 instancias independientes) |
| Servidor web | Apache (`php:8.2-apache`) |
| Frontend | HTML5, CSS3 (variables CSS), JavaScript vanilla |
| Comunicación | AJAX (`fetch` + JSON) |
| Orquestación | Docker + Docker Compose |
| Documentación | LaTeX (`pdflatex`) |

---

## 🚀 Instalación y ejecución

**Requisitos**: Docker y Docker Compose.

```bash
git clone <url-del-repo>
cd libre_mercado
docker compose up -d --build
```

La primera vez, MariaDB inicializa cada base con los datos de `sql/sucX.sql`. Luego abre:

- **Tienda**: http://localhost:8080
- **Administración**: http://localhost:8080/admin.php

### Verificar que los 3 nodos se ven en la red

```bash
docker exec -it lm_app bash
ping db_suc1 && ping db_suc2 && ping db_suc3
php -r "new PDO('mysql:host=db_suc2;dbname=libre_mercado_suc2','app','app123'); echo 'OK';"
```

---

## 🛍️ Tienda (`index.php`)

- **Header**: indicador de estado de los 3 nodos (verde = conectado, rojo = caído), actualizado
  automáticamente cada 5 segundos.
- **Catálogo consolidado**: buscador, filtros por categoría, y por cada producto un chip de stock
  por sucursal (Norte / Centro / Sur). Verde = stock normal, naranja = stock bajo (≤5), gris
  "N/D" = nodo caído.
- **Carrito**: cada item puede comprarse desde una sucursal distinta (la que tenga stock).
- **Pasarela de pago**: vista de checkout con resumen de la compra, datos de facturación y botón
  "Confirmar y Descontar Stock de Forma Atómica", que ejecuta una venta por cada item
  (transacción ACID local al nodo correspondiente).

> Para esta demo, las ventas del carrito quedan asociadas al cliente `id_usuario = 1`
> (constante `ID_CLIENTE_DEMO` en `app/src/assets/app.js`).

---

## 🛠️ Panel de Administración (`admin.php`)

| Pestaña | Descripción |
|---|---|
| **Productos** | CRUD con borrado lógico (`activo`). Al crear, se define el stock inicial por sucursal. |
| **Clientes / Usuarios** | CRUD de `usuarios` (roles: cliente, operador, administrador), borrado lógico. |
| **Proveedores** | CRUD con borrado lógico. |
| **Reabastecimiento** | Registra una compra a un proveedor en una sucursal: aumenta stock vía transacción ACID local, con historial de compras. |
| **Traspasos entre Sucursales** | Mueve stock de un producto entre dos sucursales mediante **Two-Phase Commit (2PC)** manual. |

### CRUD distribuido (`MultiNodo`)

Productos, usuarios y proveedores son catálogo replicado: toda escritura pasa por
`MultiNodo::ejecutar()`, que abre transacción en los **3 nodos** y solo confirma si los 3
responden. Si un nodo no está disponible, la operación se rechaza **completa**, sin cambios
parciales (modelo CP). Los `id` se generan de forma centralizada
(`MultiNodo::siguienteId()`) para que el mismo registro tenga el mismo `id` en los 3 nodos.

### Borrado lógico

Ninguna acción de "baja" hace `DELETE`. Solo cambia `activo` a `0` en los 3 nodos, preservando
referencias en `ventas`, `detalle_ventas` y `stock`. "Reactivar" vuelve a poner `activo = 1`.

---

## ⚙️ Mecanismos de consistencia

| Operación | Alcance | Mecanismo |
|---|---|---|
| Venta (`api/procesar_venta.php`) | 1 nodo | Transacción ACID local (`beginTransaction` / `SELECT...FOR UPDATE` / `commit` / `rollBack`) |
| Compra (`api/procesar_compra.php`) | 1 nodo | Transacción ACID local (aumenta stock) |
| Traspaso (`api/traspaso_stock.php`) | 2 nodos | Two-Phase Commit manual |
| CRUD de catálogo (`*_crud.php`) | 3 nodos | Commit atómico vía `MultiNodo` |
| Lectura de stock (`api/stock_consolidado.php`) | 3 nodos | Lectura best-effort; nodo caído → "N/D" |

---

## 🔌 Simular una partición de red

```bash
docker stop lm_db_suc2
```

- La pestaña/columna de "Sucursal Centro" mostrará "N/D" / estado caído en máx. ~5 segundos
  (la tienda sigue funcionando con los otros 2 nodos).
- Una venta, compra, traspaso o CRUD que involucre ese nodo se **rechaza por completo**, sin
  dejar cambios parciales.

Reactivar:

```bash
docker start lm_db_suc2
```

---

## 📁 Estructura del proyecto

```
libre_mercado/
├── docker-compose.yml
├── sql/
│   ├── suc1.sql, suc2.sql, suc3.sql   # esquema + datos iniciales por nodo
│   └── migracion_activo_proveedores.sql
├── docs/
│   ├── arquitectura_cap.tex/.pdf       # documento de arquitectura CAP (2 págs.)
│   └── informe_proyecto.tex/.pdf       # informe completo del proyecto
└── app/
    ├── Dockerfile
    └── src/
        ├── index.php              # tienda
        ├── admin.php              # administración
        ├── ConexionNodos.php      # conexiones PDO a los 3 nodos
        ├── MultiNodo.php          # commit atómico de 3 nodos
        ├── assets/
        │   ├── app.js / admin.js
        │   └── style.css
        └── api/
            ├── stock_consolidado.php
            ├── procesar_venta.php
            ├── procesar_compra.php
            ├── traspaso_stock.php
            ├── compras_listar.php
            ├── productos_crud.php
            ├── clientes_crud.php
            └── proveedores_crud.php
```

---

## 📄 Documentación

- [`docs/arquitectura_cap.pdf`](docs/arquitectura_cap.pdf) — Justificación técnica de la
  elección CAP/CP frente a fallos de conexión entre nodos.
- [`docs/informe_proyecto.pdf`](docs/informe_proyecto.pdf) — Informe completo del proyecto.

---

## 👥 Integrantes

- Joselyn Montaño
- Nicolás Malebrán

**Asignatura**: Sistemas Distribuidos
**Profesor**: Juan Torres
