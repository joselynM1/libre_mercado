-- Esquema base (igual en los 3 nodos)
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";
SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS clientes (
  id_cliente int(11) NOT NULL AUTO_INCREMENT,
  cliente varchar(255) NOT NULL,
  rut varchar(20) DEFAULT NULL,
  email varchar(255) NOT NULL,
  telefono varchar(20) DEFAULT NULL,
  direccion varchar(255) DEFAULT NULL,
  PRIMARY KEY (id_cliente),
  UNIQUE KEY uk_email_cli (email),
  UNIQUE KEY uk_rut_cli (rut)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS proveedores (
  id_proveedor int(11) NOT NULL AUTO_INCREMENT,
  proveedor varchar(255) NOT NULL,
  rut varchar(20) DEFAULT NULL,
  email varchar(255) DEFAULT NULL,
  telefono varchar(20) DEFAULT NULL,
  activo tinyint(1) DEFAULT 1,
  PRIMARY KEY (id_proveedor),
  UNIQUE KEY uk_rut_prov (rut)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS sucursales (
  id_sucursal int(11) NOT NULL AUTO_INCREMENT,
  sucursal varchar(100) NOT NULL,
  activa tinyint(1) DEFAULT 1,
  ciudad varchar(100) DEFAULT NULL,
  direccion varchar(255) DEFAULT NULL,
  telefono varchar(20) DEFAULT NULL,
  PRIMARY KEY (id_sucursal)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS usuarios (
  id_usuario int(11) NOT NULL AUTO_INCREMENT,
  usuario varchar(100) DEFAULT NULL,
  nombre varchar(255) NOT NULL,
  email varchar(100) NOT NULL,
  password_hash varchar(255) NOT NULL,
  rol enum('administrador','cliente','operador') DEFAULT 'cliente',
  activo tinyint(1) DEFAULT 1,
  created_at timestamp NOT NULL DEFAULT current_timestamp(),
  id_sucursal int(11) DEFAULT NULL,
  PRIMARY KEY (id_usuario),
  UNIQUE KEY email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS productos (
  id_producto int(11) NOT NULL AUTO_INCREMENT,
  producto varchar(150) NOT NULL,
  precio_unitario decimal(10,2) NOT NULL,
  descripcion text DEFAULT NULL,
  activo tinyint(1) DEFAULT 1,
  categoria varchar(100) DEFAULT NULL,
  creado_en timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (id_producto)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS stock (
  id_stock int(11) NOT NULL AUTO_INCREMENT,
  id_sucursal int(11) NOT NULL,
  id_producto int(11) NOT NULL,
  cantidad int(11) DEFAULT 0,
  stock_minimo int(11) DEFAULT 5,
  actualizado_en timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (id_stock),
  KEY id_prod (id_producto),
  CONSTRAINT stock_ibfk_1 FOREIGN KEY (id_producto) REFERENCES productos (id_producto)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS ventas (
  id_venta int(11) NOT NULL AUTO_INCREMENT,
  id_cliente int(11) NOT NULL,
  id_sucursal int(11) NOT NULL,
  total decimal(10,2) NOT NULL,
  id_usuario int(11) DEFAULT NULL,
  fecha datetime DEFAULT current_timestamp(),
  estado varchar(20) DEFAULT 'completada',
  PRIMARY KEY (id_venta),
  KEY id_cli (id_cliente),
  CONSTRAINT ventas_ibfk_1 FOREIGN KEY (id_cliente) REFERENCES usuarios (id_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS detalle_ventas (
  id_detalle int(11) NOT NULL AUTO_INCREMENT,
  id_venta int(11) NOT NULL,
  id_producto int(11) NOT NULL,
  cantidad int(11) NOT NULL,
  precio_unitario decimal(10,2) NOT NULL,
  subtotal decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (id_detalle),
  KEY id_venta (id_venta),
  KEY id_prod (id_producto),
  CONSTRAINT detalle_ventas_ibfk_1 FOREIGN KEY (id_venta) REFERENCES ventas (id_venta) ON DELETE CASCADE,
  CONSTRAINT detalle_ventas_ibfk_2 FOREIGN KEY (id_producto) REFERENCES productos (id_producto)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS compras (
  id_compra int(11) NOT NULL AUTO_INCREMENT,
  id_proveedor int(11) NOT NULL,
  id_sucursal int(11) NOT NULL,
  id_usuario int(11) DEFAULT NULL,
  fecha datetime DEFAULT current_timestamp(),
  total decimal(10,2) NOT NULL DEFAULT 0.00,
  estado varchar(20) DEFAULT 'recibida',
  PRIMARY KEY (id_compra)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS detalle_compras (
  id_detalle int(11) NOT NULL AUTO_INCREMENT,
  id_compra int(11) NOT NULL,
  id_producto int(11) NOT NULL,
  cantidad int(11) NOT NULL,
  precio_compra decimal(10,2) NOT NULL,
  subtotal decimal(10,2) DEFAULT NULL,
  PRIMARY KEY (id_detalle)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


INSERT INTO clientes (id_cliente, cliente, rut, email, telefono, direccion) VALUES
(1, 'Juan Pérez', NULL, 'juan@email.com', NULL, NULL),
(2, 'Joselyn Montaño', NULL, 'joselyn.montano@userena.cl', NULL, NULL);

INSERT INTO sucursales (id_sucursal, sucursal, activa, ciudad, direccion, telefono) VALUES
(1, 'Sucursal Norte', 1, NULL, NULL, NULL),
(2, 'Sucursal Centro', 1, NULL, NULL, NULL),
(3, 'Sucursal Sur', 1, NULL, NULL, NULL);

INSERT INTO usuarios (id_usuario, usuario, nombre, email, password_hash, rol, activo, created_at, id_sucursal) VALUES
(1, NULL, 'Juan Pérez', 'juan@email.com', 'hash_simulado', 'cliente', 1, '2026-05-26 00:45:10', NULL),
(2, NULL, 'Joselyn Montaño', 'joselyn.montano@userena.cl', 'hash_simulado_pago', 'cliente', 1, '2026-05-26 01:25:55', NULL),
(3, NULL, 'Emilia N', 'emilia@gmail.com', 'hash_simulado_pago', 'cliente', 1, '2026-05-28 23:56:19', NULL),
(4, NULL, 'Pedro K', 'pedro@gmail.com', 'hash_simulado_pago', 'cliente', 1, '2026-05-30 23:57:09', NULL),
(5, NULL, 'Juan J', 'juan@gmail.com', 'hash_simulado_pago', 'cliente', 1, '2026-05-31 18:22:13', NULL);

INSERT INTO productos (id_producto, producto, precio_unitario, descripcion, activo, categoria, creado_en) VALUES
(1, 'Laptop Asus', 850000.00, 'Notebook para trabajo y oficina.', 1, 'Computación', '2026-05-28 17:46:21'),
(2, 'PlayStation 5', 550000.00, 'Consola de videojuegos de última generación.', 1, 'Electrónica', '2026-05-28 17:46:21');


INSERT INTO stock (id_stock, id_sucursal, id_producto, cantidad, stock_minimo) VALUES
(3,2,1,49,5),
(4,2,2,29,5);

INSERT INTO ventas (id_venta, id_cliente, id_sucursal, total, id_usuario, fecha, estado) VALUES
(13,5,2,1400000.0,NULL,'2026-05-31 14:43:00','completada');

INSERT INTO detalle_ventas (id_detalle, id_venta, id_producto, cantidad, precio_unitario, subtotal) VALUES
(11,13,2,1,550000.0,550000.0),
(12,13,1,1,850000.0,850000.0);

-- ============================================================
-- PROCEDIMIENTOS ALMACENADOS
-- ============================================================

DELIMITER $$

-- sp_realizar_compra: valida stock, inserta venta, actualiza inventario y controla errores
DROP PROCEDURE IF EXISTS sp_realizar_compra$$
CREATE PROCEDURE sp_realizar_compra(
    IN  p_id_sucursal INT,
    IN  p_id_cliente  INT,
    IN  p_id_producto INT,
    IN  p_cantidad    INT,
    OUT p_resultado   TINYINT,
    OUT p_mensaje     VARCHAR(255)
)
BEGIN
    DECLARE v_stock   INT          DEFAULT 0;
    DECLARE v_precio  DECIMAL(10,2) DEFAULT 0;
    DECLARE v_total   DECIMAL(10,2) DEFAULT 0;
    DECLARE v_id_venta INT         DEFAULT 0;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_resultado = 0;
        SET p_mensaje   = 'Error interno en la transacción. Rollback ejecutado.';
    END;

    START TRANSACTION;

    -- Validar stock con bloqueo para evitar condiciones de carrera
    SELECT cantidad INTO v_stock
    FROM stock
    WHERE id_sucursal = p_id_sucursal AND id_producto = p_id_producto
    FOR UPDATE;

    IF v_stock IS NULL THEN
        ROLLBACK;
        SET p_resultado = 0;
        SET p_mensaje   = 'Producto no disponible en esta sucursal.';

    ELSEIF v_stock < p_cantidad THEN
        ROLLBACK;
        SET p_resultado = 0;
        SET p_mensaje   = CONCAT('Stock insuficiente. Disponible: ', v_stock, ' unidades.');

    ELSE
        SELECT precio_unitario INTO v_precio
        FROM productos WHERE id_producto = p_id_producto;

        SET v_total = v_precio * p_cantidad;

        INSERT INTO ventas (id_cliente, id_sucursal, total, fecha, estado)
        VALUES (p_id_cliente, p_id_sucursal, v_total, NOW(), 'completada');

        SET v_id_venta = LAST_INSERT_ID();

        INSERT INTO detalle_ventas (id_venta, id_producto, cantidad, precio_unitario, subtotal)
        VALUES (v_id_venta, p_id_producto, p_cantidad, v_precio, v_total);

        UPDATE stock
        SET cantidad = cantidad - p_cantidad, actualizado_en = NOW()
        WHERE id_sucursal = p_id_sucursal AND id_producto = p_id_producto;

        COMMIT;
        SET p_resultado = 1;
        SET p_mensaje   = CONCAT('Venta registrada. ID: ', v_id_venta, '. Total: $', FORMAT(v_total, 0));
    END IF;
END$$

-- sp_actualizar_stock: recibe producto, cantidad y operación (sumar/restar) y modifica existencia
DROP PROCEDURE IF EXISTS sp_actualizar_stock$$
CREATE PROCEDURE sp_actualizar_stock(
    IN  p_id_sucursal INT,
    IN  p_id_producto INT,
    IN  p_cantidad    INT,
    IN  p_operacion   VARCHAR(10),
    OUT p_resultado   TINYINT,
    OUT p_mensaje     VARCHAR(255)
)
BEGIN
    DECLARE v_actual INT DEFAULT 0;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_resultado = 0;
        SET p_mensaje   = 'Error al actualizar stock. Rollback ejecutado.';
    END;

    START TRANSACTION;

    SELECT cantidad INTO v_actual
    FROM stock
    WHERE id_sucursal = p_id_sucursal AND id_producto = p_id_producto
    FOR UPDATE;

    IF v_actual IS NULL THEN
        -- No existe registro: insertar con la cantidad inicial
        INSERT INTO stock (id_sucursal, id_producto, cantidad, actualizado_en)
        VALUES (p_id_sucursal, p_id_producto, p_cantidad, NOW());

        COMMIT;
        SET p_resultado = 1;
        SET p_mensaje   = CONCAT('Stock creado con cantidad inicial: ', p_cantidad);

    ELSEIF p_operacion = 'restar' AND v_actual < p_cantidad THEN
        ROLLBACK;
        SET p_resultado = 0;
        SET p_mensaje   = CONCAT('Stock insuficiente para restar. Disponible: ', v_actual);

    ELSEIF p_operacion = 'sumar' THEN
        UPDATE stock
        SET cantidad = cantidad + p_cantidad, actualizado_en = NOW()
        WHERE id_sucursal = p_id_sucursal AND id_producto = p_id_producto;

        COMMIT;
        SET p_resultado = 1;
        SET p_mensaje   = CONCAT('Stock incrementado. Nueva cantidad: ', v_actual + p_cantidad);

    ELSE
        UPDATE stock
        SET cantidad = cantidad - p_cantidad, actualizado_en = NOW()
        WHERE id_sucursal = p_id_sucursal AND id_producto = p_id_producto;

        COMMIT;
        SET p_resultado = 1;
        SET p_mensaje   = CONCAT('Stock decrementado. Nueva cantidad: ', v_actual - p_cantidad);
    END IF;
END$$

-- sp_reconstruir_stock: recalcula el stock de una sucursal desde cero basándose en compras y ventas
DROP PROCEDURE IF EXISTS sp_reconstruir_stock$$
CREATE PROCEDURE sp_reconstruir_stock(
    IN  p_id_sucursal INT,
    OUT p_resultado   TINYINT,
    OUT p_mensaje     VARCHAR(255)
)
BEGIN
    DECLARE v_filas INT DEFAULT 0;

    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_resultado = 0;
        SET p_mensaje   = 'Error durante la reconstrucción de stock. Rollback ejecutado.';
    END;

    START TRANSACTION;

    -- Recalcula cantidad = total comprado - total vendido para cada producto
    UPDATE stock s
    SET s.cantidad = (
            COALESCE((
                SELECT SUM(dc.cantidad)
                FROM detalle_compras dc
                JOIN compras c ON dc.id_compra = c.id_compra
                WHERE c.id_sucursal = p_id_sucursal
                  AND dc.id_producto = s.id_producto
                  AND c.estado = 'recibida'
            ), 0)
            -
            COALESCE((
                SELECT SUM(dv.cantidad)
                FROM detalle_ventas dv
                JOIN ventas v ON dv.id_venta = v.id_venta
                WHERE v.id_sucursal = p_id_sucursal
                  AND dv.id_producto = s.id_producto
                  AND v.estado = 'completada'
            ), 0)
        ),
        s.actualizado_en = NOW()
    WHERE s.id_sucursal = p_id_sucursal;

    SET v_filas = ROW_COUNT();
    COMMIT;
    SET p_resultado = 1;
    SET p_mensaje   = CONCAT('Stock reconstruido para sucursal ', p_id_sucursal,
                             '. Productos actualizados: ', v_filas);
END$$

DELIMITER ;
