# Documento Técnico — Libre Mercado Distribuido
## Sistemas Distribuidos 2026

---

## 1. Arquitectura del Sistema

### 1.1 Visión General

Libre Mercado implementa una arquitectura distribuida de **3 nodos de base de datos independientes**, uno por sucursal, coordinados por una única capa de aplicación PHP. Cada nodo es una instancia MariaDB ejecutándose en su propio contenedor Docker, aislada de las demás.

```
                    ┌─────────────────────┐
                    │    Cliente Web       │
                    │  (Navegador / AJAX)  │
                    └──────────┬──────────┘
                               │ HTTP
                    ┌──────────▼──────────┐
                    │   PHP + Apache       │
                    │  (Nodo Coordinador)  │
                    │  app:8080 (Docker)   │
                    └──┬──────┬──────┬────┘
                       │      │      │  PDO (red tienda_net)
           ┌───────────▼┐  ┌──▼──────┐  ┌▼───────────┐
           │  db_suc1   │  │ db_suc2  │  │  db_suc3   │
           │  MariaDB   │  │ MariaDB  │  │  MariaDB   │
           │Suc. Norte  │  │Suc.Centro│  │  Suc. Sur  │
           │BD Local    │  │BD Local  │  │  BD Local  │
           └────────────┘  └──────────┘  └────────────┘
```

### 1.2 Componentes

| Componente | Tecnología | Función |
|---|---|---|
| Frontend | HTML5 + JavaScript (AJAX) | Interfaz de tienda y administración |
| Coordinador | PHP 8.2 + Apache | Lógica de negocio, coordinación de transacciones |
| Nodo 1 | MariaDB 10.4 (db_suc1) | Base de datos Sucursal Norte |
| Nodo 2 | MariaDB 10.4 (db_suc2) | Base de datos Sucursal Centro |
| Nodo 3 | MariaDB 10.4 (db_suc3) | Base de datos Sucursal Sur |
| Red | Docker bridge (tienda_net) | Comunicación entre contenedores |

### 1.3 Archivos Clave

| Archivo | Rol |
|---|---|
| `ConexionNodos.php` | Pool de conexiones PDO a los 3 nodos; detecta fallas simuladas y reales |
| `MultiNodo.php` | Ejecutor de operaciones atómicas sobre los 3 nodos (2PC para catálogo) |
| `api/procesar_venta.php` | Llama a `sp_realizar_compra()` en el nodo de la sucursal |
| `api/traspaso_stock.php` | Two-Phase Commit entre dos nodos para traspasos de inventario |
| `api/nodo_estado.php` | Activa/desactiva fallas simuladas (Requisito 4) |
| `api/reconstruir_stock.php` | Llama a `sp_reconstruir_stock()` tras recuperar un nodo |
| `api/ajustar_stock.php` | Llama a `sp_actualizar_stock()` para ajustes manuales de inventario |
| `sql/suc1.sql`, `suc2.sql`, `suc3.sql` | Schema, datos iniciales y procedimientos almacenados |

---

## 2. Modelo Distribuido

### 2.1 Estrategia de Distribución de Datos

Se usa una combinación de **particionamiento** y **replicación** según el tipo de dato:

**Datos particionados (locales por nodo):**
- `stock` — cada nodo tiene su propio inventario físico
- `ventas` y `detalle_ventas` — cada sucursal registra solo sus ventas
- `compras` y `detalle_compras` — cada sucursal gestiona su reabastecimiento

**Datos replicados (idénticos en los 3 nodos):**
- `productos` — catálogo de artículos
- `usuarios` y `clientes` — base de clientes
- `proveedores` — catálogo de proveedores
- `sucursales` — lista maestra de sucursales

La replicación del catálogo se mantiene mediante operaciones atómicas multi-nodo coordinadas por `MultiNodo.php`: toda creación, edición o baja en catálogo se aplica a los 3 nodos dentro de una misma transacción distribuida; si algún nodo falla, se hace rollback completo en todos.

### 2.2 Flujo de una Compra Distribuida

```
Cliente → selecciona producto → agrega al carrito → confirma compra
                                                          │
                               ┌──────────────────────────▼───────────────────┐
                               │          PHP: procesar_venta.php              │
                               │                                               │
                               │  CALL sp_realizar_compra(suc, cli, prod, cant)│
                               │                                               │
                               │  ┌─────────────────────────────────────────┐ │
                               │  │  MariaDB — Stored Procedure             │ │
                               │  │  START TRANSACTION                      │ │
                               │  │  SELECT cantidad ... FOR UPDATE         │ │
                               │  │  IF stock < cantidad → ROLLBACK         │ │
                               │  │  INSERT INTO ventas ...                 │ │
                               │  │  INSERT INTO detalle_ventas ...         │ │
                               │  │  UPDATE stock SET cantidad - cant       │ │
                               │  │  COMMIT                                 │ │
                               │  └─────────────────────────────────────────┘ │
                               └──────────────────────────────────────────────┘
                                          │                    │
                                    [Éxito: 200]         [Error: 409/503]
                                   Stock actualizado      Rollback automático
```

### 2.3 Flujo de un Traspaso (Two-Phase Commit)

```
Admin → formulario traspaso → envía a traspaso_stock.php
                                        │
                    ┌───────────────────▼──────────────────────┐
                    │         Two-Phase Commit (2PC)            │
                    │                                          │
                    │  FASE 1 — PREPARE                        │
                    │  ├─ beginTransaction() en nodo ORIGEN    │
                    │  │   UPDATE stock SET cantidad - X       │
                    │  └─ beginTransaction() en nodo DESTINO   │
                    │      UPDATE stock SET cantidad + X       │
                    │                                          │
                    │  FASE 2 — COMMIT o ROLLBACK              │
                    │  Si ambas OK → commit() en AMBOS nodos   │
                    │  Si alguna falla → rollback() en AMBOS   │
                    └──────────────────────────────────────────┘
```

---

## 3. Procedimientos Almacenados

Los tres stored procedures están definidos en los archivos `sql/suc1.sql`, `suc2.sql` y `suc3.sql` (uno por nodo) y se crean automáticamente al inicializar los contenedores Docker.

### 3.1 `sp_realizar_compra`

**Parámetros:** `id_sucursal`, `id_cliente`, `id_producto`, `cantidad`, `OUT resultado`, `OUT mensaje`

**Lógica:**
1. `SELECT cantidad ... FOR UPDATE` — bloqueo de fila para evitar condiciones de carrera en compras simultáneas
2. Valida que `cantidad_disponible >= cantidad_solicitada`; si no → `ROLLBACK`
3. `INSERT INTO ventas` → obtiene `LAST_INSERT_ID()`
4. `INSERT INTO detalle_ventas`
5. `UPDATE stock SET cantidad = cantidad - p_cantidad`
6. `COMMIT`
7. `EXIT HANDLER FOR SQLEXCEPTION` → `ROLLBACK` automático ante cualquier error inesperado

**Llamada desde PHP (`procesar_venta.php`):**
```sql
CALL sp_realizar_compra(:suc, :cli, :prod, :cant, @resultado, @mensaje);
SELECT @resultado, @mensaje;
```

### 3.2 `sp_actualizar_stock`

**Parámetros:** `id_sucursal`, `id_producto`, `cantidad`, `operacion ('sumar'|'restar')`, `OUT resultado`, `OUT mensaje`

**Lógica:**
- Si no existe registro de stock → `INSERT` con cantidad inicial
- Si `operacion = 'restar'` y `stock < cantidad` → `ROLLBACK`
- Si `operacion = 'sumar'` → `UPDATE ... SET cantidad + p_cantidad`
- Si `operacion = 'restar'` y hay stock suficiente → `UPDATE ... SET cantidad - p_cantidad`

Usado para ajustes administrativos de inventario (por ejemplo, tras un conteo físico). No abre transacción a nivel de PHP: como el procedimiento ya maneja su propia transacción, `ajustar_stock.php` lo invoca directamente sin envolverlo en un `beginTransaction()` adicional.

**Llamada desde PHP (`ajustar_stock.php`):**
```sql
CALL sp_actualizar_stock(:suc, :prod, :cant, :op, @resultado, @mensaje);
SELECT @resultado, @mensaje;
```

Accesible desde el panel de administración, pestaña **"Ajuste de Stock"**.

### 3.3 `sp_reconstruir_stock`

**Parámetros:** `id_sucursal`, `OUT resultado`, `OUT mensaje`

**Lógica:** Recalcula el stock de cada producto en una sucursal desde cero, aplicando la fórmula:

```
stock_real = Σ(compras recibidas) - Σ(ventas completadas)
```

```sql
UPDATE stock s
SET s.cantidad = (
    COALESCE(SELECT SUM(dc.cantidad) FROM detalle_compras ... WHERE estado='recibida', 0)
    -
    COALESCE(SELECT SUM(dv.cantidad) FROM detalle_ventas  ... WHERE estado='completada', 0)
)
WHERE s.id_sucursal = p_id_sucursal;
```

Se llama al recuperar un nodo tras una falla, para corregir posibles desincronizaciones ocurridas mientras estuvo OFFLINE.

**Llamada desde PHP (`reconstruir_stock.php`):**
```sql
CALL sp_reconstruir_stock(:suc, @resultado, @mensaje);
SELECT @resultado, @mensaje;
```

---

## 4. Aplicación del Teorema CAP

### 4.1 Elección: CP (Consistencia + Tolerancia a Particiones)

Ante una partición de red (un nodo deja de responder), el sistema prioriza la **consistencia** del inventario sobre la **disponibilidad total**.

**Justificación:** En un sistema de control de inventario físico, una inconsistencia (ej. stock descontado en un nodo pero no en otro) tiene costo operativo real: sobreventas, descuadres contables, pérdida de confianza. Una indisponibilidad temporal y acotada es preferible.

### 4.2 Comportamiento según escenario

| Escenario | Comportamiento | Modelo CAP |
|---|---|---|
| 3 nodos disponibles | CRUD, ventas y traspasos normales | — |
| 1 nodo caído, sin operaciones sobre él | Sistema opera; stock del nodo caído muestra "N/D" | Lectura disponible parcialmente |
| Venta en sucursal con nodo caído | Rechazada (HTTP 503), sin cambios en ningún nodo | **CP**: consistencia > disponibilidad |
| Traspaso con origen o destino caído | Rollback 2PC completo, ningún nodo modificado | **CP**: integridad garantizada |
| Compra simultánea del mismo producto | `FOR UPDATE` serializa el acceso; no se duplica el descuento | **CP**: concurrencia controlada |
| Nodo simulado OFFLINE | `ConexionNodos::get()` lanza excepción antes de conectar | Falla controlada visible |

### 4.3 Comparación con otras estrategias

**AP (Disponibilidad + Partición):** Permitiría completar ventas en nodos sanos aunque otros estén caídos, sincronizando después. Descartado porque durante la partición no hay forma de garantizar unicidad del stock (riesgo de sobreventa).

**CA (Consistencia + Disponibilidad):** Válido en red perfectamente confiable. No aplica a esta arquitectura LAN donde se simulan fallas reales.

---

## 5. Manejo de Fallos

### 5.1 Detección de Nodo Caído

`ConexionNodos::get(id)` implementa dos niveles de detección:

1. **Falla simulada:** Lee `/tmp/libre_mercado_nodos.json`; si el nodo está marcado `offline`, lanza `RuntimeException` inmediatamente (sin intentar conexión TCP).
2. **Falla real:** Si la conexión TCP al contenedor falla dentro de 1 segundo (`PDO::ATTR_TIMEOUT = 1`), lanza `PDOException`.

En ambos casos el código llamador captura `Throwable` y devuelve HTTP 503 con mensaje de error claro.

### 5.2 Respuesta ante Falla por Tipo de Operación

**Venta (`sp_realizar_compra`):**
```
Nodo caído → ConexionNodos::get() lanza excepción
           → procesar_venta.php captura → HTTP 503
           → El carrito no se vacía; el cliente puede reintentar en otra sucursal
           → Stock intacto (no hubo ningún UPDATE)
```

**Traspaso 2PC:**
```
Nodo origen OK, nodo destino CAÍDO:
  Fase 1 → beginTransaction() en origen OK
          → beginTransaction() en destino → excepción
  Fase 2 → rollBack() en origen (deshace el UPDATE de resta)
          → HTTP 503 al cliente
          → Ambos nodos quedan en estado anterior
```

**CRUD de catálogo (`MultiNodo`):**
```
Cualquier nodo falla durante la replicación:
  → rollBack() en todos los nodos que llegaron a abrir transacción
  → HTTP 503 / mensaje de error
  → El catálogo queda sin cambios en todos los nodos
```

### 5.3 Simulación de Falla (Requisito 4)

Desde el panel "Simular Falla de Nodos" en el panel de administración:

1. El administrador hace clic en **"Simular Falla"** sobre una sucursal
2. Se hace `POST /api/nodo_estado.php` con `{id_sucursal: X, estado: "offline"}`
3. El servidor escribe `{"1":"online","2":"offline","3":"online"}` en `/tmp/libre_mercado_nodos.json`
4. A partir de ese momento, toda operación que requiera el nodo 2 recibe error controlado

**Recuperación:**
1. Clic en **"Recuperar Nodo"** → estado vuelve a `online`
2. Clic en **"Reconstruir Stock (sp)"** → llama a `sp_reconstruir_stock()` para recalcular inventario

### 5.4 Pruebas Obligatorias

| Prueba | Cómo reproducirla | Resultado esperado |
|---|---|---|
| Compra normal | Tienda → seleccionar producto → comprar | Venta exitosa, stock decrementado |
| Nodo apagado | Admin → "Simular Falla" en Suc. Centro → intentar comprar ahí | HTTP 503, mensaje claro, stock intacto |
| Recuperación nodo | Admin → "Recuperar Nodo" → "Reconstruir Stock" | Stock recalculado por sp_reconstruir_stock |
| Compra simultánea | Dos compras al mismo tiempo del mismo producto | FOR UPDATE serializa; sin duplicar descuento |
| Pérdida conexión (traspaso) | Simular falla en nodo destino → ejecutar traspaso | Rollback 2PC, ningún nodo modificado |

---

## 6. Conclusiones

El sistema **Libre Mercado Distribuido** demuestra que es posible implementar un modelo de comercio electrónico con tolerancia real a fallos de nodos en una red LAN, manteniendo la integridad de los datos en todo momento.

**Decisiones de diseño validadas:**

- **Modelo CP** es el adecuado para inventario físico: los datos incorrectos tienen mayor costo que la indisponibilidad temporal.
- **Stored Procedures** centralizan la lógica transaccional en la base de datos, asegurando atomicidad con `EXIT HANDLER FOR SQLEXCEPTION` y `FOR UPDATE` para concurrencia.
- **Two-Phase Commit manual** (PHP + PDO) permite operaciones distribuidas entre dos nodos sin depender de un coordinador externo.
- **Replicación de catálogo** mediante `MultiNodo.php` garantiza que productos, clientes y proveedores sean siempre consistentes entre sucursales.
- **Falla rápida** (`ATTR_TIMEOUT = 1s`, lectura de archivo de estado) evita que una sucursal caída bloquee al resto del sistema indefinidamente.

**Limitaciones conocidas:**
- El coordinador PHP es un punto único de falla: si cae el servidor web, todo el sistema deja de funcionar. En producción se requeriría un balanceador de carga con múltiples instancias PHP.
- La sincronización post-falla es manual (`sp_reconstruir_stock`). En un sistema AP real se usaría replicación asíncrona automática.
- El 2PC implementado no es tolerante a fallas del propio coordinador durante la fase commit (problema de los generales bizantinos en 2PC).
