-- ============================================================
-- MIGRACIÓN: Alinear BD con Diagrama ER
-- Ejecutar en orden en phpMyAdmin sobre libre_mercado_db
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================
-- 1. SUCURSALES: renombrar columnas + agregar campos
-- =====================================================
ALTER TABLE sucursales
    CHANGE id_suc    id_sucursal INT          AUTO_INCREMENT,
    CHANGE nombre_suc sucursal   VARCHAR(100) NOT NULL,
    ADD COLUMN ciudad    VARCHAR(100) DEFAULT NULL,
    ADD COLUMN direccion VARCHAR(255) DEFAULT NULL,
    ADD COLUMN telefono  VARCHAR(20)  DEFAULT NULL,
    ADD COLUMN activa    TINYINT(1)   DEFAULT 1;

-- =====================================================
-- 2. PRODUCTOS: renombrar columnas + agregar campos
-- =====================================================
ALTER TABLE productos
    CHANGE id_prod id_producto    INT          AUTO_INCREMENT,
    CHANGE precio  precio_unitario DECIMAL(10,2) NOT NULL,
    ADD COLUMN categoria  VARCHAR(100)        DEFAULT NULL,
    ADD COLUMN creado_en  TIMESTAMP           DEFAULT CURRENT_TIMESTAMP;

-- =====================================================
-- 3. sucursales_stock → stock (renombrar tabla y columnas)
-- =====================================================
RENAME TABLE sucursales_stock TO stock;

-- Si la tabla tenía PRIMARY KEY compuesta, quítala primero:
ALTER TABLE stock DROP PRIMARY KEY;

ALTER TABLE stock
    ADD COLUMN id_stock      INT AUTO_INCREMENT PRIMARY KEY FIRST,
    CHANGE id_suc  id_sucursal INT NOT NULL,
    CHANGE id_prod id_producto INT NOT NULL,
    CHANGE stock   cantidad    INT DEFAULT 0,
    ADD COLUMN stock_minimo   INT       DEFAULT 5,
    ADD COLUMN actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- =====================================================
-- 4. CLIENTES: nueva tabla separada de usuarios
--    Conserva los mismos IDs para mantener integridad con ventas
-- =====================================================
CREATE TABLE IF NOT EXISTS clientes (
    id_cliente INT          AUTO_INCREMENT PRIMARY KEY,
    cliente    VARCHAR(255) NOT NULL,
    rut        VARCHAR(20)  DEFAULT NULL,
    email      VARCHAR(255) NOT NULL,
    telefono   VARCHAR(20)  DEFAULT NULL,
    direccion  VARCHAR(255) DEFAULT NULL,
    UNIQUE KEY uk_email_cli (email),
    UNIQUE KEY uk_rut_cli   (rut)
);

-- Migrar datos conservando id_cli como id_cliente
INSERT IGNORE INTO clientes (id_cliente, cliente, email)
SELECT id_cli, cliente, email FROM usuarios;

-- =====================================================
-- 5. USUARIOS: renombrar para que sea tabla de personal
-- =====================================================
ALTER TABLE usuarios
    CHANGE id_cli   id_usuario  INT          AUTO_INCREMENT,
    CHANGE cliente  nombre      VARCHAR(255) NOT NULL,
    ADD COLUMN usuario     VARCHAR(100) DEFAULT NULL AFTER id_usuario,
    ADD COLUMN id_sucursal INT          DEFAULT NULL;

-- =====================================================
-- 6. VENTAS: renombrar columnas + agregar campos faltantes
-- =====================================================
ALTER TABLE ventas
    CHANGE id_cli id_cliente  INT NOT NULL,
    CHANGE id_suc id_sucursal INT NOT NULL,
    ADD COLUMN id_usuario INT          DEFAULT NULL,
    ADD COLUMN fecha      DATETIME     DEFAULT CURRENT_TIMESTAMP,
    ADD COLUMN estado     VARCHAR(20)  DEFAULT 'completada';

-- =====================================================
-- 7. DETALLE_VENTAS: renombrar + agregar subtotal
-- =====================================================
ALTER TABLE detalle_ventas
    CHANGE id_prod id_producto INT NOT NULL,
    ADD COLUMN subtotal DECIMAL(10,2) DEFAULT NULL;

UPDATE detalle_ventas
SET subtotal = cantidad * precio_unitario
WHERE subtotal IS NULL;

-- =====================================================
-- 8. PROVEEDORES (nueva tabla del diagrama)
-- =====================================================
CREATE TABLE IF NOT EXISTS proveedores (
    id_proveedor INT          AUTO_INCREMENT PRIMARY KEY,
    proveedor    VARCHAR(255) NOT NULL,
    rut          VARCHAR(20)  DEFAULT NULL,
    email        VARCHAR(255) DEFAULT NULL,
    telefono     VARCHAR(20)  DEFAULT NULL,
    UNIQUE KEY uk_rut_prov (rut)
);

-- =====================================================
-- 9. COMPRAS (nueva tabla del diagrama)
-- =====================================================
CREATE TABLE IF NOT EXISTS compras (
    id_compra    INT          AUTO_INCREMENT PRIMARY KEY,
    id_proveedor INT          NOT NULL,
    id_sucursal  INT          NOT NULL,
    id_usuario   INT          DEFAULT NULL,
    fecha        DATETIME     DEFAULT CURRENT_TIMESTAMP,
    total        DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    estado       VARCHAR(20)  DEFAULT 'recibida'
);

-- =====================================================
-- 10. DETALLE_COMPRAS (nueva tabla del diagrama)
-- =====================================================
CREATE TABLE IF NOT EXISTS detalle_compras (
    id_detalle    INT          AUTO_INCREMENT PRIMARY KEY,
    id_compra     INT          NOT NULL,
    id_producto   INT          NOT NULL,
    cantidad      INT          NOT NULL,
    precio_compra DECIMAL(10,2) NOT NULL,
    subtotal      DECIMAL(10,2) DEFAULT NULL
);

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- VERIFICACIÓN RÁPIDA
-- =====================================================
-- SELECT * FROM sucursales LIMIT 5;
-- SELECT * FROM productos LIMIT 5;
-- SELECT * FROM stock LIMIT 5;
-- SELECT * FROM clientes LIMIT 5;
-- SELECT * FROM ventas LIMIT 5;
