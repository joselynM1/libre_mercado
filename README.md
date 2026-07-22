# 🛒 LibreMercado — Sistema Distribuido de Comercio Electrónico

![PHP](https://img.shields.io/badge/PHP-8.2-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MariaDB](https://img.shields.io/badge/MariaDB-10.4-003545?style=for-the-badge&logo=mariadb&logoColor=white)
![Docker](https://img.shields.io/badge/Docker-Containerized-2496ED?style=for-the-badge&logo=docker&logoColor=white)
![Architecture](https://img.shields.io/badge/CAP%20Theorem-CP%20Model-orange?style=for-the-badge)

Prototipo de comercio electrónico distribuido desarrollado para la asignatura de **Sistemas Distribuidos**. 

El sistema implementa arquitectura **CP** (Consistencia + Tolerancia a Particiones según el Teorema CAP), manejando **CRUD distribuido atómico**, **transacciones ACID locales**, **Two-Phase Commit (2PC)** manual para traspasos de stock entre nodos, y **borrado lógico** para la preservación de la integridad de los datos.

---

## 👥 Integrantes del Proyecto

- **Joselyn Montaño**
- **Nicolás Malebrán**

- **Asignatura**: Sistemas Distribuidos
- **Profesor**: Juan Torres

---

## 📐 Arquitectura del Sistema

El sistema opera sobre **4 contenedores Docker** interconectados mediante una red dedicada (`tienda_net`):

```
                        ┌──────────────────────┐
           Navegador ──▶│   Aplicación PHP     │
            (AJAX)      │   Apache 8.2 + PDO   │
                        │   (lm_app)           │
                        └─────────┬─────┬──────┘
                          PDO     │     │ PDO         PDO
                        ┌─────────┘     │      └──────────┐
                        ▼               ▼                 ▼
                ┌───────────────┐ ┌───────────────┐ ┌───────────────┐
                │ DB Suc. Norte │ │ DB Suc. Centro│ │ DB Suc. Sur   │
                │ (lm_db_suc1)  │ │ (lm_db_suc2)  │ │ (lm_db_suc3)  │
                └───────────────┘ └───────────────┘ └───────────────┘
```

### 📊 Replicación y Distribución de Datos

Cada nodo es una instancia **MariaDB independiente** correspondiente a una sucursal física. Las tablas del sistema se categorizan según su estrategia de persistencia:

| Tipo | Tablas | Comportamiento en la Red |
|---|---|---|
| **Catálogo Replicado** | `productos`, `usuarios`, `proveedores`, `sucursales` | Copia idéntica en los 3 nodos. Toda escritura/actualización (`MultiNodo`) se aplica de forma atómica en los 3 nodos simultáneamente. |
| **Datos Locales** | `stock`, `ventas`, `detalle_ventas`, `compras`, `detalle_compras` | Cada nodo almacena de forma aislada e independiente la información transaccional de su propia sucursal. |

---

## ⚙️ Mecanismos de Consistencia y Transacciones

| Operación | Alcance | Mecanismo Implementado | Comportamiento |
|---|---|---|---|
| **Venta** (`procesar_venta.php`) | 1 nodo local | Transacción ACID local (`beginTransaction` / `SELECT...FOR UPDATE` / `commit`) | Garantiza aislamiento y atomaticidad al descontar stock. |
| **Reabastecimiento / Compra** (`procesar_compra.php`) | 1 nodo local | Transacción ACID local | Registra la orden del proveedor e incrementa el stock en el nodo receptor. |
| **Traspaso de Stock** (`traspaso_stock.php`) | 2 nodos | **Two-Phase Commit (2PC)** manual | Bloquea y descuenta stock en nodo origen y suma en nodo destino. Rollback coordinado si falla algún nodo. |
| **CRUD de Catálogo** (`*_crud.php`) | 3 nodos | **Commit Atómico MultiNodo** (`MultiNodo::ejecutar()`) | Operación síncrona en los 3 nodos. Si 1 nodo está caído, se rechaza la transacción (Modelo CP). |
| **Lectura de Stock** (`stock_consolidado.php`) | 3 nodos | Lectura Best-Effort | Muestra el stock consolidado en vivo. Si un nodo cae, responde con `N/D` sin tumbar el sistema. |
| **Integridad de Datos** | Global | **Borrado Lógico** (`activo = 0`) | Ninguna baja elimina registros (`DELETE`). Mantiene integridad referencial con el historial de ventas y compras. |

---

## 🧰 Stack Tecnológico

| Capa | Tecnología | Descripción |
|---|---|---|
| **Backend** | PHP 8.2 + PDO (`pdo_mysql`) | Lógica de negocio, conexión PDO multinodo y coordinación de transacciones. |
| **Base de Datos** | MariaDB 10.4 | 3 instancias independientes (`db_suc1`, `db_suc2`, `db_suc3`). |
| **Servidor Web** | Apache (`php:8.2-apache`) | Servidor web embebido en el contenedor de aplicación. |
| **Frontend** | HTML5, CSS3, JavaScript Vanilla | Interfaz dinámica con AJAX (`fetch` API), CSS variables y diseño responsivo. |
| **Orquestación** | Docker & Docker Compose | Redes aisladas y volúmenes persistentes por cada nodo. |
| **Documentación** | LaTeX / PDF / Markdown | Informes técnicos de arquitectura CAP y especificación del sistema. |

---

## 🚀 Instalación y Ejecución

### Requisitos Previos
- [Docker Desktop](https://www.docker.com/) o Docker Engine con Docker Compose instalado.

### Pasos para levantar el proyecto

1. **Clonar el repositorio**:
   ```bash
   git clone <URL_DEL_REPOSITORIO>
   cd libre_mercado2/libre_mercado
   ```

2. **Desplegar los contenedores**:
   ```bash
   docker compose up -d --build
   ```

3. **Acceder a la aplicación**:
   - 🛍️ **Tienda principal**: [http://localhost:8080](http://localhost:8080)
   - 🛠️ **Panel de Administración**: [http://localhost:8080/admin.php](http://localhost:8080/admin.php)

---

## 🔍 Verificación y Diagnóstico de Red

Para verificar la conectividad inter-nodo y las conexiones PDO desde el contenedor de aplicación:

```bash
docker exec -it lm_app bash

# Verificar conectividad de red entre contenedores
ping db_suc1 && ping db_suc2 && ping db_suc3

# Probar conexión PDO directa a un nodo secundario
php -r "new PDO('mysql:host=db_suc2;dbname=libre_mercado_suc2','app','app123'); echo 'Conexión OK';"
```

---

## 🛍️ Vistas y Funcionalidades del Sistema

### 1. Tienda de Clientes (`index.php`)
- **Monitoreo en tiempo real**: Indicador en el header con estado de conexión de los 3 nodos (Verde = Operativo, Rojo = Caído), actualizado automáticamente cada 5 segundos.
- **Catálogo Consolidado**: Buscador por texto, filtro por categorías y chips de stock por sucursal (*Verde = Normal*, *Naranja = Bajo ≤5*, *Gris = N/D nodo caído*).
- **Carrito Multisucursal**: Permite seleccionar la sucursal de origen por cada ítem antes de procesar el pago.
- **Pasarela de Pago (Checkout)**: Genera transacciones ACID en los nodos correspondientes.

### 2. Panel de Administración (`admin.php`)
- **Gestión de Productos**: CRUD de catálogo replicado con borrado/reactivación lógica y asignación de stock inicial por sucursal.
- **Gestión de Clientes / Usuarios**: Administra usuarios y roles (Cliente, Operador, Administrador) sincronizados en los 3 nodos.
- **Gestión de Proveedores**: CRUD distribuido de proveedores.
- **Reabastecimiento (Compras)**: Registra compras a proveedores incrementando stock en el nodo local de la sucursal receptora.
- **Traspaso de Stock**: Transferencia coordinada de productos entre sucursales ejecutada mediante Two-Phase Commit (2PC).

---

## 🔌 Simulación de Partición de Red (Demostración CAP)

Para evaluar la resiliencia y el cumplimiento del modelo **CP**:

1. **Detener una base de datos (Simular caída de Sucursal Centro)**:
   ```bash
   docker stop lm_db_suc2
   ```

2. **Comportamiento esperado**:
   - **Lectura**: El stock de Sucursal Centro cambiará a `N/D` en la tienda, pero las Sucursales Norte y Sur continuarán operando con normalidad.
   - **Escritura**: Cualquier intento de crear/modificar productos, usuarios o realizar un traspaso que involucre a Sucursal Centro será **rechazado por completo** para evitar inconsistencias distribuidas.

3. **Reactivar la sucursal**:
   ```bash
   docker start lm_db_suc2
   ```

---

## ⚠️ Migración de Esquema de Base de Datos

Si ya tienes volúmenes anteriores creados en Docker y necesitas aplicar la columna `activo` en la tabla `proveedores`:

**Opción A — Reiniciar entorno desde cero**:
```bash
docker compose down -v
docker compose up -d --build
```

**Opción B — Ejecutar migración manual en caliente**:
```bash
docker exec -i lm_db_suc1 mysql -uapp -papp123 libre_mercado_suc1 < sql/migracion_activo_proveedores.sql
docker exec -i lm_db_suc2 mysql -uapp -papp123 libre_mercado_suc2 < sql/migracion_activo_proveedores.sql
docker exec -i lm_db_suc3 mysql -uapp -papp123 libre_mercado_suc3 < sql/migracion_activo_proveedores.sql
```

---

## 📁 Estructura del Proyecto

```
libre_mercado/
├── docker-compose.yml                  # Configuración de contenedores (lm_app, lm_db_suc1, lm_db_suc2, lm_db_suc3)
├── sql/
│   ├── suc1.sql                        # Esquema + datos iniciales Sucursal Norte
│   ├── suc2.sql                        # Esquema + datos iniciales Sucursal Centro
│   ├── suc3.sql                        # Esquema + datos iniciales Sucursal Sur
│   └── migracion_activo_proveedores.sql# Script de actualización de esquema
├── docs/
│   ├── arquitectura_cap.md             # Documentación teórica Teorema CAP
│   ├── arquitectura_cap.pdf            # PDF renderizado del informe de arquitectura
│   └── informe_proyecto.pdf            # Informe completo del proyecto
└── app/
    ├── Dockerfile                      # Imagen PHP 8.2 Apache + pdo_mysql
    └── src/
        ├── index.php                   # Vista principal de la tienda
        ├── admin.php                   # Panel de administración distribuido
        ├── ConexionNodos.php           # Gestor de conexiones PDO a los 3 nodos DB
        ├── MultiNodo.php               # Coordinador de transacciones síncronas en 3 nodos
        ├── assets/
        │   ├── app.js                  # Lógica del cliente, carrito y AJAX
        │   ├── admin.js                # Lógica del panel de administración
        │   └── style.css               # Estilos globales y componentes UI
        └── api/
            ├── stock_consolidado.php   # Endpoint de consulta de stock en vivo
            ├── procesar_venta.php      # Endpoint de transacciones de venta (ACID local)
            ├── procesar_compra.php     # Endpoint de reabastecimiento (ACID local)
            ├── traspaso_stock.php      # Endpoint de transferencia de stock (2PC)
            ├── compras_listar.php      # Endpoint de consulta de historial de compras
            ├── productos_crud.php      # API CRUD distribuido de productos
            ├── clientes_crud.php       # API CRUD distribuido de usuarios/clientes
            ├── proveedores_crud.php    # API CRUD distribuido de proveedores
            └── clientes.php            # Endpoint secundario de consulta de clientes
```
