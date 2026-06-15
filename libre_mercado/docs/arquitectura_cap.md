# Documento de Arquitectura — Elección CAP: CP

## 1. Contexto

"Libre Mercado" se implementa como un sistema distribuido con **3 nodos
independientes de base de datos**, uno por sucursal (Norte, Centro,
Sur). Cada nodo mantiene su propio inventario (`stock`) y registra las
ventas realizadas en esa sucursal. El catálogo de productos, clientes y
usuarios está replicado en los 3 nodos.

Una sola aplicación PHP centraliza el acceso: consulta los 3 nodos por
AJAX para mostrar el stock consolidado, y ejecuta las operaciones de
venta y traspaso de inventario contra el o los nodos correspondientes.

## 2. Elección: Consistencia + Tolerancia a Particiones (CP)

Ante una partición de red (un nodo de sucursal deja de responder), el
sistema prioriza la **consistencia** del inventario por sobre la
**disponibilidad** total de escritura en ese nodo. En la práctica:

- **Lecturas**: se mantiene disponibilidad parcial. La página sigue
  funcionando y muestra el stock de los nodos accesibles; el nodo caído
  se marca como "no disponible" en vez de bloquear toda la vista.
- **Escrituras (ventas)**: si el nodo de la sucursal que vende no
  responde, la venta se rechaza por completo. No se permite registrar
  una venta "a medias" ni descontar stock de forma optimista.
- **Traspasos entre sucursales (2PC)**: requieren que AMBOS nodos
  (origen y destino) estén disponibles y confirmen la operación. Se
  implementa un Two-Phase Commit manual con PDO:
  1. *Prepare*: se abre `beginTransaction()` en ambos nodos y se
     ejecutan los `UPDATE` de stock (resta en origen, suma en destino),
     sin confirmar.
  2. *Commit*: solo si ambas preparaciones fueron exitosas se ejecuta
     `commit()` en ambos nodos.
  3. *Rollback*: si cualquiera de los dos nodos falla (caído, sin
     stock, error de red), se ejecuta `rollBack()` en el o los nodos
     que llegaron a abrir transacción, y la operación se cancela
     íntegramente.

## 3. Justificación frente a fallo de conexión entre nodos

Si se prioriza **disponibilidad** (AP) en este dominio, un traspaso o
venta podría confirmarse en un nodo y no en el otro durante una
partición, generando estados como: stock descontado en la sucursal de
origen pero nunca acreditado en destino, o una venta registrada sin
respaldo real de inventario. En un sistema de comercio donde el stock
representa inventario físico real, esa inconsistencia tiene costo
operativo y de confianza directo (sobreventa, descuadres contables).

Por eso se acepta el trade-off de **CP**: durante una partición, ciertas
operaciones de escritura en el nodo afectado (o que dependan de él)
quedarán **temporalmente no disponibles**, devolviendo un mensaje claro
al usuario ("Sucursal X no disponible, intente más tarde"), pero el
estado del inventario permanece siempre correcto y verificable en los
nodos sanos.

## 4. Resumen de comportamiento por escenario

| Escenario                                  | Comportamiento del sistema |
|---------------------------------------------|------------------------------|
| Los 3 nodos disponibles                      | CRUD, ventas y traspasos normales |
| 1 nodo caído, sin operaciones sobre él       | El resto del sistema opera con normalidad; columna del nodo caído muestra "N/D" |
| Venta en sucursal cuyo nodo está caído       | Operación rechazada (HTTP 503), sin cambios en ningún nodo |
| Traspaso donde origen o destino está caído   | Rollback completo (2PC), sin cambios en ningún nodo |
| Stock insuficiente en origen del traspaso    | Rollback completo (2PC), sin cambios en ningún nodo |

## 5. Conclusión

La arquitectura CP es coherente con la naturaleza del dominio (control
de inventario físico distribuido), donde una inconsistencia entre
sucursales es más costosa que una indisponibilidad temporal y acotada
de las operaciones de escritura durante una falla de red.
